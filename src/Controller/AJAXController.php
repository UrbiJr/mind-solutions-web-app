<?php

namespace App\Controller;

use App\Entity\InventoryItem;
use App\Entity\Release;
use App\Entity\User;
use App\Entity\ViagogoUser;
use App\Repository\ReleaseRepository;
use App\Repository\UserRepository;
use App\Service\Firestore;
use App\Service\InventoryService;
use App\Service\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Exception;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use DateTime;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;

class AJAXController extends AbstractController
{
    private const JWT_EXPIRY_IN_SECONDS = 3600;

    function __construct(
        private readonly MemcachedAdapter $cache,
        private readonly Utils $utils,
        private readonly Firestore $firestore,
        private readonly InventoryService $inventoryService,
        private readonly Client $client,
    ) {
    }

    #[Route('/api/viagogo/user', methods: ['POST'], name: 'api_viagogo_user')]
    public function set_viagogo_user(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            $username = $request->request->get('username');
            $password = $request->request->get('password');
            $cookies = json_decode($request->request->get('cookies'), true);
            if (!is_array($cookies) || count($cookies) < 2) {
                throw new Exception("Both cookies must be set.");
            }
            $wsu2Cookie = $cookies[0];
            $rvtCookie = $cookies[1];
            $viagogoUser = new ViagogoUser($username, $password, $wsu2Cookie, $rvtCookie);

            /* Cache viagogo connection (set it to never expire) */
            $cacheItem = $this->cache->getItem("viagogoUser_" . $user->getId());
            $cacheItem->set($viagogoUser);
            $this->cache->save($cacheItem);

            $response = [
                "success" => true,
                "message" => "Viagogo user set successfully.",
                "redirectUrl" => $this->generateUrl('viagogo_connection_show'),
            ];

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/viagogo/user', methods: ['GET'], name: 'api_viagogo_user_get')]
    public function get_viagogo_user(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            /* Get viagogo connection from cache */
            $cacheItem = $this->cache->getItem("viagogoUser_" . $user->getId());
            /** @var ViagogoUser $viagogoUser */
            $viagogoUser = $cacheItem->get();
            if (!$viagogoUser) {
                return new Response("Viagogo user not found.", Response::HTTP_NOT_FOUND);
            }

            $response = [
                "success" => true,
                "message" => "Viagogo user fetched successfully.",
                "viagogoUser" => array(
                    "username" => $viagogoUser->getUsername(),
                    "password" => $viagogoUser->getPassword(),
                    "wsu2Cookie" => $viagogoUser->getWsu2Cookie(),
                    "rvtCookie" => $viagogoUser->getRvtCookie(),
                ),
            ];

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/viagogo/user', methods: ['DELETE'], name: 'api_viagogo_user_delete')]
    public function delete_viagogo_user(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            /* Delete Viagogo connection from cache */
            $this->cache->deleteItem("viagogoUser_" . $user->getId());

            $response = [
                "success" => true,
                "message" => "Viagogo user deleted successfully.",
            ];

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/viagogo/sync', methods: ['POST'], name: 'api_viagogo_sync')]
    public function sync_viagogo(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            /** @var array $listings  */
            $listings = $request->request->all('listings');

            /** @var array $sales  */
            $sales = $request->request->all('sales');

            $inventory = $this->firestore->get_user_inventory($user->getId());
            $db_sales = $this->firestore->get_user_sales($user->getId());

            // Create an associative array of inventory items with EventId as the key
            $inventoryMap = [];
            foreach ($inventory as $inventoryItem) {
                $inventoryMap[$inventoryItem->getViagogoEventId()] = $inventoryItem;
            }

            foreach ($listings as $viagogoListing) {
                // skip adding sold out items (will do later using sales data)
                if ($viagogoListing["Status"] === InventoryItem::ITEM_SOLD) {
                    continue;
                }
                $eventId = $viagogoListing['EventId'];

                if (
                    // If an existing inventory item with the same EventId was found..
                    isset($inventoryMap[$eventId])
                ) {
                    $updated = $this->inventoryService->updateWithListing($inventoryMap[$eventId], $viagogoListing);
                    // .. and the inventory item was updated
                    if ($updated !== $inventoryMap[$eventId]) {
                        // Update the inventory item in the database
                        $this->firestore->edit_inventory_item($inventoryMap[$eventId]->getId(), $updated, $user->getId());
                    }
                } else {
                    // No matching inventory item found, create a new item
                    $listingSeats = (isset($viagogoListing["Seats"])) ? explode("-", $viagogoListing["Seats"]) : array();
                    if (sizeof($listingSeats) > 0) {
                        $listingSeatFrom = $listingSeats[0];
                        $listingSeatTo = $listingSeats[sizeof($listingSeats) - 1];
                    } else {
                        $listingSeatFrom = null;
                        $listingSeatTo = null;
                    }
                    $isoCode = $this->utils->getIsoCodeFromCountryName($viagogoListing["Country"]);
                    if (!isset($isoCode)) {
                        $isoCode = $viagogoListing["Country"];
                    }
                    $pricePerTicket = ['amount' => $viagogoListing["PricePerTicket"]["Amount"], 'currency' => $viagogoListing["PricePerTicket"]["Currency"]];
                    $newItem = new InventoryItem(
                        null,
                        (string)$viagogoListing["EventId"],
                        (string)$viagogoListing["CategoryId"],
                        $viagogoListing["EventDescription"],
                        $viagogoListing["EventDate"],
                        null,
                        $isoCode,
                        $viagogoListing["City"],
                        $viagogoListing["VenueDescription"],
                        $viagogoListing["Section"],
                        $viagogoListing["Rows"],
                        $listingSeatFrom,
                        $listingSeatTo,
                        $viagogoListing["TicketType"],
                        $this->utils->getGenreNameById($viagogoListing["GenreId"]),
                        null,
                        null,
                        null,
                        null,
                        $viagogoListing["Status"],
                        $viagogoListing["SaleEndDate"],
                        $pricePerTicket,
                        null,
                        $viagogoListing["Quantity"],
                        $viagogoListing["QuantityRemain"],
                        $viagogoListing["DateLastModified"],
                        'Viagogo',
                        null,
                        null,
                        $viagogoListing["Id"] ?? null,
                        null,
                        null,
                    );
                    $ref = $this->firestore->add_item_to_inventory($newItem, $user->getId());
                }
            }

            // Create an associative array of sales to avoid duplicates
            $db_sale_ids = [];
            foreach ($db_sales as $db_sale) {
                $db_sale_ids[] = $db_sale->getSaleId();
            }
            foreach ($sales as $sale) {
                // Check if already existent
                if (isset($sale['SaleId']) && !in_array($sale['SaleId'], $db_sale_ids)) {
                    $saleSeats = (isset($sale["Seats"]) && preg_match('/\S/', $sale["Seats"])) ? explode(' ', $sale["Seats"]) : array();
                    if (sizeof($saleSeats) > 0) {
                        $saleSeatFrom = $saleSeats[0];
                        $saleSeatTo = $saleSeats[sizeof($saleSeats) - 1];
                    } else {
                        $saleSeatFrom = null;
                        $saleSeatTo = null;
                    }
                    $isoCode = $this->utils->getIsoCodeFromCountryName($sale["Country"]);
                    if (!isset($isoCode)) {
                        $isoCode = $sale["Country"];
                    }
                    $pricePerTicket = ['amount' => $viagogoListing["PricePerTicket"]["Amount"], 'currency' => $viagogoListing["PricePerTicket"]["Currency"]];
                    $totalPayout = ['amount' => $sale["TotalPayout"]["Amount"], 'currency' => $sale["TotalPayout"]["Currency"]];
                    $newItem = new InventoryItem(
                        null,
                        (string)$sale["EventId"],
                        null,
                        $sale["EventDescription"],
                        $sale["EventDate"],
                        null,
                        $isoCode,
                        $sale["City"],
                        $sale["VenueDescription"],
                        $sale["Section"],
                        $sale["Row"],
                        $saleSeatFrom,
                        $saleSeatTo,
                        $sale["TicketType"],
                        $this->utils->getGenreNameById($sale["GenreId"]),
                        null,
                        null,
                        null,
                        null,
                        InventoryItem::ITEM_SOLD,
                        null,
                        $pricePerTicket,
                        $totalPayout,
                        $sale["Quantity"],
                        0,
                        $sale["DateLastModified"],
                        'Viagogo',
                        $sale["SaleDate"],
                        $sale["SaleId"],
                        null,
                        null,
                        null,
                    );
                    $ref = $this->firestore->add_item_to_inventory($newItem, $user->getId());
                }
            }

            $response = [
                "success" => true,
                "message" => "Viagogo synced successfully.",
            ];

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/user/inventory/add', methods: ['POST'], name: 'api_user_inventory_add')]
    public function add_to_inventory(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            /* fetch inventory item */

            /** @var array $dataArray */
            $dataArray = $request->request->all()['inventory_item'] ?? [];
            $inventoryItem = InventoryItem::fromDataArray($user, $dataArray);

            $ref = $this->firestore->add_item_to_inventory($inventoryItem, $user->getId());

            $response = [
                "success" => true,
                "id" => $ref->id(),
                "message" => "Item added successfully.",
                "eventName" => $inventoryItem->getName(),
            ];

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/user/inventory/copy/{id}', methods: ['POST'], name: 'api_user_inventory_copy')]
    public function copy_inventory_item(#[CurrentUser] ?User $user, string $id): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            /* fetch inventory item */
            $inventoryItem = $this->firestore->get_inventory_item($id, $user->getId());

            /* copy inventory item */
            $ref = $this->firestore->add_item_to_inventory($inventoryItem, $user->getId());

            $response = [
                "success" => true,
                "id" => $ref->id(),
                "message" => "Item copied successfully.",
                "eventName" => $inventoryItem->getName(),
            ];

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/user/inventory/{id}', methods: ['DELETE'], name: 'api_user_inventory_delete')]
    public function delete_inventory_item(#[CurrentUser] ?User $user, string $id): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            $this->firestore->delete_inventory_item($id, $user->getId());

            $response = [
                "success" => true,
                "message" => "Item deleted successfully.",
            ];

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/user/inventory/{id}', methods: ['PUT'], name: 'api_user_inventory_edit')]
    public function edit_inventory_item(#[CurrentUser] ?User $user, string $id, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            /* fetch inventory item */

            /** @var array $dataArray */
            $dataArray = $request->request->all()['inventory_item'] ?? [];
            $inventoryItem = InventoryItem::fromDataArray($user, $dataArray);

            $this->firestore->edit_inventory_item($id, $inventoryItem, $user->getId());

            $response = [
                "success" => true,
                "message" => "Item edited successfully.",
            ];

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/user/inventory/bulk', methods: ['PUT'], name: 'api_user_inventory_bulk_edit')]
    public function bulk_edit_inventory_items(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            $ids = $request->request->all()['ids'] ?? [];
            $attributes = $request->request->all()['attributes'] ?? [];

            $updated = $this->firestore->bulk_edit_inventory_items($ids, $attributes, $user->getId());

            $response = [
                "success" => true,
                "count" => count($updated),
                "updates" => $updated,
                "message" => "Items edited successfully.",
            ];

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/user/inventory/{id}', methods: ['GET'], name: 'api_user_inventory_get')]
    public function get_inventory_item(#[CurrentUser] ?User $user, string $id): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            /* fetch inventory item */
            $inventoryItem = $this->firestore->get_inventory_item($id, $user->getId());

            $response = [
                "success" => true,
                "message" => "Item fetched successfully.",
                "item" => $inventoryItem->toArray(),
            ];

            return new JsonResponse($response, Response::HTTP_OK);
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Return inventory as JSON object
     */
    #[Route('/api/user/inventory', methods: ['GET'], name: 'api_user_inventory')]
    public function inventory(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            // second parameter is default value
            $format = $request->query->get('format', 'json');

            if ($format === 'list') {
                return $this->inventoryList($user, $request);
            }

            $offset = $request->query->get('offset', 0);
            $itemsPerPage = $request->query->get('limit', 10);
            $sort = $request->query->get('sort', null);
            $order = $request->query->get('order', 'desc');

            $inventory = $this->firestore->get_user_inventory($user->getId());
            $inventory = array_values(array_filter($inventory, function ($item) {
                return $item->getStatus() === InventoryItem::ITEM_NOT_LISTED;
            }));

            $currency = $user->getCurrency();
            $exchangeRates = $this->utils->cacheExchangeRates($currency);

            $floorPricesToFetch = array();

            if (isset($sort)) {
                switch ($sort) {
                    case 'name':
                        if ($order === 'asc') {
                            usort($inventory, function ($a, $b) {
                                return strcmp($a->getName(), $b->getName());
                            });
                        } else {
                            usort($inventory, function ($a, $b) {
                                return strcmp($b->getName(), $a->getName());
                            });
                        }
                        break;

                    case 'status':
                        if ($order === 'asc') {
                            usort($inventory, function ($a, $b) {
                                return strcmp($a->getStatus(), $b->getStatus());
                            });
                        } else {
                            usort($inventory, function ($a, $b) {
                                return strcmp($b->getStatus(), $a->getStatus());
                            });
                        }
                        break;

                    case 'tickets':
                        if ($order === 'asc') {
                            usort($inventory, function ($a, $b) {
                                $aTickets = $a->getQuantityRemain();
                                $bTickets = $b->getQuantityRemain();

                                if ($aTickets === null && $bTickets === null) {
                                    return 0;
                                }

                                if ($aTickets === null) {
                                    return 1;
                                }

                                if ($bTickets === null) {
                                    return -1;
                                }

                                // Compare the numeric values
                                return $aTickets - $bTickets;
                            });
                        } else {
                            usort($inventory, function ($a, $b) {
                                $aTickets = $a->getQuantityRemain();
                                $bTickets = $b->getQuantityRemain();

                                if ($aTickets === null && $bTickets === null) {
                                    return 0;
                                }

                                if ($aTickets === null) {
                                    return 1;
                                }

                                if ($bTickets === null) {
                                    return -1;
                                }

                                // Compare the numeric values
                                return $bTickets - $aTickets;
                            });
                        }
                        break;

                    case 'section':
                        if ($order === 'asc') {
                            usort($inventory, function ($a, $b) {
                                return strcmp($a->getSection(), $b->getSection());
                            });
                        } else {
                            usort($inventory, function ($a, $b) {
                                return strcmp($b->getSection(), $a->getSection());
                            });
                        }
                        break;

                    case 'roi':
                        if ($order === 'asc') {
                            usort($inventory, function ($a, $b) {
                                $aRoi = $a->getRoi();
                                $bRoi = $b->getRoi();

                                if (is_string($aRoi) && is_string($bRoi)) {
                                    return 0;
                                }

                                if (is_string($aRoi)) {
                                    return 1;
                                }

                                if (is_string($bRoi)) {
                                    return -1;
                                }

                                // Compare the numeric values in descending order
                                return $aRoi - $bRoi;
                            });
                        } else {
                            usort($inventory, function ($a, $b) {
                                $aRoi = $a->getRoi();
                                $bRoi = $b->getRoi();

                                if (is_string($aRoi) && is_string($bRoi)) {
                                    return 0;
                                }

                                if (is_string($aRoi)) {
                                    return 1;
                                }

                                if (is_string($bRoi)) {
                                    return -1;
                                }

                                // Compare the numeric values in descending order
                                return $bRoi - $aRoi;
                            });
                        }
                        break;

                    case 'yourPrice':
                        if ($order === 'asc') {
                            usort($inventory, function ($a, $b) {
                                $aPrice = $a->getYourPricePerTicket()["amount"];
                                $bPrice = $b->getYourPricePerTicket()["amount"];

                                // Compare the numeric values
                                return $aPrice - $bPrice;
                            });
                        } else {
                            usort($inventory, function ($a, $b) {
                                $aPrice = $a->getYourPricePerTicket()["amount"];
                                $bPrice = $b->getYourPricePerTicket()["amount"];

                                // Compare the numeric values in descending order
                                return $bPrice - $aPrice;
                            });
                        }
                        break;

                    case 'date':
                        if ($order === 'asc') {
                            usort($inventory, function ($a, $b) {
                                $aDate = $a->getEventDate();
                                $bDate = $b->getEventDate();

                                if ($aDate === null && $bDate === null) {
                                    return 0;
                                }

                                if ($aDate === null) {
                                    return 1;
                                }

                                if ($bDate === null) {
                                    return -1;
                                }

                                return $aDate <=> $bDate;
                            });
                        } else {
                            usort($inventory, function ($a, $b) {
                                $aDate = $a->getEventDate();
                                $bDate = $b->getEventDate();

                                if ($aDate === null && $bDate === null) {
                                    return 0;
                                }

                                if ($aDate === null) {
                                    return -1;
                                }

                                if ($bDate === null) {
                                    return 1;
                                }

                                return $bDate <=> $aDate;
                            });
                        }
                        break;

                    default:
                        break;
                }
            }

            $today = new DateTime();
            $inventoryData = array();
            for ($i = 0; $i < $itemsPerPage && $offset + $i < count($inventory); $i++) {
                $item = $inventory[$offset + $i];
                $eventId = $item->getViagogoEventId(); // Replace with the actual event ID
                $categoryId = $item->getViagogoCategoryId(); // Replace with the actual category ID
                $section = $item->getSection(); // Replace with the actual section name
                $userCurrency = $user->getCurrency(); // Replace with the actual currency
                $floorPriceFormatted = "N/A";

                $cacheItem = $this->cache->getItem('viagogoSectionFloorPrice_' . str_replace(' ', '', $section) . $eventId);
                $floorPrice = $cacheItem->get();

                if ($cacheItem->isHit() && !is_bool($floorPrice)) {
                    if ($floorPrice === 'N/A') {
                        $floorPriceFormatted = $floorPrice;
                    } else if (strtoupper($userCurrency) === strtoupper($floorPrice["currency"])) {
                        $floorPriceFormatted = $this->utils->formatAmountAndCurrencyAsSymbol($floorPrice["floorPrice"], $userCurrency);
                    } else {
                        $floorPriceConverted = $this->utils->convertCurrency(floatval($floorPrice["floorPrice"]), $exchangeRates, $floorPrice["currency"]);
                        $floorPriceFormatted = (isset($floorPriceConverted)) ? $this->utils->formatAmountAndCurrencyAsSymbol($floorPriceConverted, $user->getCurrency()) : "N/A";
                    }
                } else if ($item->getEventDate() >= $today) {
                    // floor price not in cache, fetch it later
                    $floorPricesToFetch[] = array("itemId" => $item->getId(), "eventId" => $eventId, "categoryId" => $categoryId, "section" => $section);

                    // set floor price to N/A for now
                    $cacheItem->set($floorPriceFormatted);
                    $cacheItem->expiresAfter(600); // 10 minutes
                    // save the cache item
                    $this->cache->save($cacheItem);
                }

                $amount = $item->getYourPricePerTicket()["amount"];
                $currency = $item->getYourPricePerTicket()["currency"];
                if (strtoupper($currency) === strtoupper($user->getCurrency())) {
                    $yourPrice = $this->utils->formatAmountAndCurrencyAsSymbol($amount, $currency);
                } else {
                    $converted = $this->utils->convertCurrency(floatval($amount), $exchangeRates, $currency);
                    $yourPrice = (isset($converted)) ? $this->utils->formatAmountAndCurrencyAsSymbol($converted, $user->getCurrency()) : "N/A";
                }

                $itemData = '<span data-status="' . $item->getStatus() . '" data-row="' . $item->getRow() . '" data-seat-from="' . $item->getSeatFrom() . '" data-seat-to="' . $item->getSeatTo() . '" data-section="' . $item->getSection() . '" data-individual-ticket-cost="' . $this->utils->formatAmountArrayAsSymbol($item->getIndividualTicketCost()) . '" data-quantity="' . $item->getQuantity() . '"  data-your-price="' . $yourPrice . '" data-purchase-date="' . $item->getPurchaseDate()->format('F j, Y \a\t h:i A') . '" data-retailer="' . $item->getRetailer() . '" data-item-id="' . $item->getId() . '" data-category-id="' . $categoryId . '" data-event-id="' . $eventId . '" data-section="' . $section . '"></span>';

                $actions = '
    <button name="copy-inventory-item" type="button" class="btn btn-soft-primary" data-item-id="' . $item->getId() . '">
        <svg class="feather feather-copy" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
            <rect height="13" rx="2" ry="2" width="13" x="9" y="9" />
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
        </svg>
    </button>
    <button name="edit-inventory-item" type="button" class="btn btn-soft-primary" data-item-id="' . $item->getId() . '">
        <svg class="icon-24" width="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M11.4925 2.78906H7.75349C4.67849 2.78906 2.75049 4.96606 2.75049 8.04806V16.3621C2.75049 19.4441 4.66949 21.6211 7.75349 21.6211H16.5775C19.6625 21.6211 21.5815 19.4441 21.5815 16.3621V12.3341" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.82812 10.921L16.3011 3.44799C17.2321 2.51799 18.7411 2.51799 19.6721 3.44799L20.8891 4.66499C21.8201 5.59599 21.8201 7.10599 20.8891 8.03599L13.3801 15.545C12.9731 15.952 12.4211 16.181 11.8451 16.181H8.09912L8.19312 12.401C8.20712 11.845 8.43412 11.315 8.82812 10.921Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M15.1655 4.60254L19.7315 9.16854" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
    </button>
    <button name="delete-inventory-item" data-toggle="modal" data-item-id="' . $item->getId() . '" type="button" class="btn btn-soft-danger">
        <svg class="icon-24" width="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19.3248 9.46826C19.3248 9.46826 18.7818 16.2033 18.4668 19.0403C18.3168 20.3953 17.4798 21.1893 16.1088 21.2143C13.4998 21.2613 10.8878 21.2643 8.27979 21.2093C6.96079 21.1823 6.13779 20.3783 5.99079 19.0473C5.67379 16.1853 5.13379 9.46826 5.13379 9.46826" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M20.708 6.23975H3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M17.4406 6.23973C16.6556 6.23973 15.9796 5.68473 15.8256 4.91573L15.5826 3.69973C15.4326 3.13873 14.9246 2.75073 14.3456 2.75073H10.1126C9.53358 2.75073 9.02558 3.13873 8.87558 3.69973L8.63258 4.91573C8.47858 5.68473 7.80258 6.23973 7.01758 6.23973" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
    </button>
    ';
                if (null !== $item->getEventPageUrl() && $item->getEventPageUrl() !== "") {
                    $actions .= '
        <a class="btn btn-soft-info" href="' . $item->getEventPageUrl() . '" target="_blank">
                <svg class="feather feather-external-link" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                    <polyline points="15 3 21 3 21 9" />
                    <line x1="10" x2="21" y1="14" y2="3" />
                </svg>
        </a>
        ';
                }

                $statusHtml = '';
                switch ($item->getStatus()) {
                    case InventoryItem::ITEM_NOT_LISTED:
                        $statusHtml = '<span class="text-warning"><b>Not Listed</b></span>';
                        break;

                    default:
                        # code...
                        break;
                }

                $itemRoi = $this->inventoryService->calculateRoi($item);
                $rowData = array(
                    'eventData' => $itemData,
                    'name' => ($item->getId() !== null) ? "<a href='/{$request->getLocale()}/inventory/{$item->getId()}'>{$item->getName()} - {$item->getCity()}</a>" : "{$item->getName()} - {$item->getCity()}",
                    'date' => ($item->getEventDate() !== null) ? $item->getEventDate()->format('F j, Y \a\t h:i A') : '',
                    'tickets' => ($item->getQuantity() !== null) ? $item->getQuantity() . "/" . $item->getQuantityRemain() : '',
                    'section' => $item->getSection(),
                    'floorPrice' => $floorPriceFormatted,
                    'yourPrice' => $yourPrice,
                    'roi' => ($itemRoi !== "N/A") ? number_format($itemRoi, 2, '.', '') . "%" : $itemRoi,
                    'status' => $statusHtml,
                    'actions' => $actions,
                );

                $inventoryData[$item->getId()] = $rowData;
            }

            if (sizeof($floorPricesToFetch) > 0) {
                /* fetch floor prices */
                $apiEndpoint = 'https://api.mindsolutions.app/'; // Replace with the URL of your PHP script
                $jwtToken = $this->utils->generateToken(self::JWT_EXPIRY_IN_SECONDS);

                $data = [
                    'action' => 'get_bulk_event_section_floor_price',
                    'items' => $floorPricesToFetch,
                    'currency' => $userCurrency,
                ];

                // Convert the POST data array to JSON
                $postDataJson = json_encode($data);

                // Make the POST request to the API using Guzzle
                try {
                    $response = $this->client->post($apiEndpoint, [
                        'headers' => [
                            'Authorization' => "Bearer $jwtToken",
                            'Content-Type' => 'application/json',
                        ],
                        'body' => $postDataJson,
                    ]);

                    // Get the response body
                    $responseBody = $response->getBody()->getContents();

                    $floorPrices = json_decode($responseBody);
                    if (isset($floorPrices) && is_array($floorPrices)) {
                        foreach ($floorPrices as $floorPrice) {
                            $floorPrice = (array) $floorPrice;
                            if (isset($inventoryData[$floorPrice["itemId"]])) {
                                $floorPriceFormatted = (isset($floorPrice['floorPrice'])) ? $this->utils->formatAmountAndCurrencyAsSymbol($floorPrice['floorPrice'], $user->getCurrency()) : "N/A";
                                $inventoryData[$floorPrice["itemId"]]["floorPrice"] = $floorPriceFormatted;
                            }

                            $cacheItem = $this->cache->getItem('viagogoSectionFloorPrice_' . str_replace(' ', '', $floorPrice['section']) . $floorPrice['eventId']);
                            $cacheItem->set($floorPrice);
                            $cacheItem->expiresAfter(600); // 10 minutes
                            // save the cache item
                            $this->cache->save($cacheItem);
                        }
                    }
                } catch (\GuzzleHttp\Exception\RequestException $e) {
                    // Handle exceptions or errors here
                }
            }

            $result = array(
                "total" => count($inventory),
                "totalNotFiltered" => count($inventory),
                "rows" => array_values($inventoryData),
            );
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    public function inventoryList(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            // second parameter is default value
            $offset = $request->query->get('offset', 0);
            $itemsPerPage = $request->query->get('limit', 10);
            $sort = $request->query->get('sort', null);
            $order = $request->query->get('order', 'desc');

            $inventory = $this->firestore->get_user_inventory($user->getId());
            $inventory = array_values(array_filter($inventory, function ($item) {
                return $item->getStatus() === InventoryItem::ITEM_NOT_LISTED || $item->getStatus() === InventoryItem::ITEM_LISTED;
            }));

            $userCurrency = $user->getCurrency();
            $exchangeRates = $this->utils->cacheExchangeRates($userCurrency);

            $floorPricesToFetch = array();

            if (isset($sort)) {
                switch ($sort) {
                    case 'name':
                        if ($order === 'asc') {
                            usort($inventory, function ($a, $b) {
                                return strcmp($a->getName(), $b->getName());
                            });
                        } else {
                            usort($inventory, function ($a, $b) {
                                return strcmp($b->getName(), $a->getName());
                            });
                        }
                        break;

                    case 'status':
                        if ($order === 'asc') {
                            usort($inventory, function ($a, $b) {
                                return strcmp($a->getStatus(), $b->getStatus());
                            });
                        } else {
                            usort($inventory, function ($a, $b) {
                                return strcmp($b->getStatus(), $a->getStatus());
                            });
                        }
                        break;

                    case 'tickets':
                        if ($order === 'asc') {
                            usort($inventory, function ($a, $b) {
                                $aTickets = $a->getQuantityRemain();
                                $bTickets = $b->getQuantityRemain();

                                if ($aTickets === null && $bTickets === null) {
                                    return 0;
                                }

                                if ($aTickets === null) {
                                    return 1;
                                }

                                if ($bTickets === null) {
                                    return -1;
                                }

                                // Compare the numeric values
                                return $aTickets - $bTickets;
                            });
                        } else {
                            usort($inventory, function ($a, $b) {
                                $aTickets = $a->getQuantityRemain();
                                $bTickets = $b->getQuantityRemain();

                                if ($aTickets === null && $bTickets === null) {
                                    return 0;
                                }

                                if ($aTickets === null) {
                                    return 1;
                                }

                                if ($bTickets === null) {
                                    return -1;
                                }

                                // Compare the numeric values
                                return $bTickets - $aTickets;
                            });
                        }
                        break;

                    case 'section':
                        if ($order === 'asc') {
                            usort($inventory, function ($a, $b) {
                                return strcmp($a->getSection(), $b->getSection());
                            });
                        } else {
                            usort($inventory, function ($a, $b) {
                                return strcmp($b->getSection(), $a->getSection());
                            });
                        }
                        break;

                    case 'roi':
                        if ($order === 'asc') {
                            usort($inventory, function ($a, $b) {
                                $aRoi = $a->getRoi();
                                $bRoi = $b->getRoi();

                                if (is_string($aRoi) && is_string($bRoi)) {
                                    return 0;
                                }

                                if (is_string($aRoi)) {
                                    return 1;
                                }

                                if (is_string($bRoi)) {
                                    return -1;
                                }

                                // Compare the numeric values in descending order
                                return $aRoi - $bRoi;
                            });
                        } else {
                            usort($inventory, function ($a, $b) {
                                $aRoi = $a->getRoi();
                                $bRoi = $b->getRoi();

                                if (is_string($aRoi) && is_string($bRoi)) {
                                    return 0;
                                }

                                if (is_string($aRoi)) {
                                    return 1;
                                }

                                if (is_string($bRoi)) {
                                    return -1;
                                }

                                // Compare the numeric values in descending order
                                return $bRoi - $aRoi;
                            });
                        }
                        break;

                    case 'yourPrice':
                        if ($order === 'asc') {
                            usort($inventory, function ($a, $b) {
                                $aPrice = $a->getYourPricePerTicket()["amount"];
                                $bPrice = $b->getYourPricePerTicket()["amount"];

                                // Compare the numeric values
                                return $aPrice - $bPrice;
                            });
                        } else {
                            usort($inventory, function ($a, $b) {
                                $aPrice = $a->getYourPricePerTicket()["amount"];
                                $bPrice = $b->getYourPricePerTicket()["amount"];

                                // Compare the numeric values in descending order
                                return $bPrice - $aPrice;
                            });
                        }
                        break;

                    case 'date':
                        if ($order === 'asc') {
                            usort($inventory, function ($a, $b) {
                                $aDate = $a->getEventDate();
                                $bDate = $b->getEventDate();

                                if ($aDate === null && $bDate === null) {
                                    return 0;
                                }

                                if ($aDate === null) {
                                    return 1;
                                }

                                if ($bDate === null) {
                                    return -1;
                                }

                                return $aDate <=> $bDate;
                            });
                        } else {
                            usort($inventory, function ($a, $b) {
                                $aDate = $a->getEventDate();
                                $bDate = $b->getEventDate();

                                if ($aDate === null && $bDate === null) {
                                    return 0;
                                }

                                if ($aDate === null) {
                                    return -1;
                                }

                                if ($bDate === null) {
                                    return 1;
                                }

                                return $bDate <=> $aDate;
                            });
                        }
                        break;

                    default:
                        break;
                }
            }

            $today = new DateTime();
            $inventoryData = array();
            for ($i = 0; $i < $itemsPerPage && $offset + $i < count($inventory); $i++) {
                $item = $inventory[$offset + $i];
                $eventId = $item->getViagogoEventId(); // Replace with the actual event ID
                $categoryId = $item->getViagogoCategoryId(); // Replace with the actual category ID
                $section = $item->getSection(); // Replace with the actual section name
                $projectedProfit = 'N/A';
                $floorPriceFormatted = 'N/A';
                $floorPriceFormatted = false;

                if ($item->getTotalCost()["currency"] === $userCurrency) {
                    $totalCostConverted = $item->getTotalCost()["amount"];
                } else {
                    $totalCostConverted = $this->utils->convertCurrency(floatval($item->getTotalCost()["amount"]), $exchangeRates, $item->getTotalCost()["currency"]);
                }

                $cacheItem = $this->cache->getItem('viagogoSectionFloorPrice_' . str_replace(' ', '', $section) . $eventId);
                $floorPrice = $cacheItem->get();

                if ($cacheItem->isHit() && !is_bool($floorPrice)) {
                    if ($floorPrice === 'N/A') {
                        $floorPriceFormatted = $floorPrice;
                    } else if (strtoupper($userCurrency) === strtoupper($floorPrice["currency"])) {
                        $floorPriceFormatted = $this->utils->formatAmountAndCurrencyAsSymbol($floorPrice["floorPrice"], $userCurrency);
                    } else {
                        $floorPriceConverted = $this->utils->convertCurrency(floatval($floorPrice["floorPrice"]), $exchangeRates, $floorPrice["currency"]);
                        $floorPriceFormatted = (isset($floorPriceConverted)) ? $this->utils->formatAmountAndCurrencyAsSymbol($floorPriceConverted, $user->getCurrency()) : "N/A";

                        // Calculate projected profit
                        if ($item->getTotalCost()["currency"] === $userCurrency) {
                            $projectedProfit = $floorPrice["floorPrice"] * $item->getQuantityRemain() - $item->getTotalCost()["amount"];
                        } else {
                            $projectedProfit = $floorPrice["floorPrice"] * $item->getQuantityRemain() - $totalCostConverted;
                        }
                    }
                } else if ($item->getEventDate() >= $today) {
                    // floor price not in cache, fetch it later
                    $floorPricesToFetch[] = array("itemId" => $item->getId(), "eventId" => $eventId, "categoryId" => $categoryId, "section" => $section);

                    // set floor price to N/A for now
                    $cacheItem->set($floorPriceFormatted);
                    $cacheItem->expiresAfter(600); // 10 minutes
                    // save the cache item
                    $this->cache->save($cacheItem);
                }

                $amount = $item->getYourPricePerTicket()["amount"];
                $currency = $item->getYourPricePerTicket()["currency"];
                if (strtoupper($currency) === strtoupper($user->getCurrency())) {
                    $yourPrice = $this->utils->formatAmountAndCurrencyAsSymbol($amount, $currency);
                } else {
                    $converted = $this->utils->convertCurrency(floatval($amount), $exchangeRates, $currency);
                    $yourPrice = (isset($converted)) ? $this->utils->formatAmountAndCurrencyAsSymbol($converted, $user->getCurrency()) : "N/A";
                }

                $statusHtml = '';
                switch ($item->getStatus()) {
                    case InventoryItem::ITEM_NOT_LISTED:
                        $statusHtml = '<span class="text-warning"><b>Not Listed</b></span>';
                        break;

                    default:
                        # code...
                        break;
                }

                $itemRoi = $this->inventoryService->calculateRoi($item);
                $rowData = array(
                    'name' => $item->getName() . " - " . $item->getCity(),
                    'link' => "/{$request->getLocale()}/inventory/{$item->getId()}",
                    'date' => ($item->getEventDate() !== null) ? $item->getEventDate()->format('F j, Y \a\t h:i A') : '',
                    'quantityRemain' => $item->getQuantityRemain(),
                    'section' => $item->getSection(),
                    'floorPrice' => $floorPriceFormatted,
                    'totalCost' => $totalCostConverted,
                    'yourPrice' => $yourPrice,
                    'projectedProfit' => $projectedProfit,
                    'roi' => ($itemRoi !== "N/A") ? number_format($itemRoi, 2, '.', '') . "%" : $itemRoi,
                );

                $inventoryData[$item->getId()] = $rowData;
            }

            if (sizeof($floorPricesToFetch) > 0) {
                /* fetch floor prices */
                $apiEndpoint = 'https://api.mindsolutions.app/'; // Replace with the URL of your PHP script
                $jwtToken = $this->utils->generateToken(self::JWT_EXPIRY_IN_SECONDS);

                $data = [
                    'action' => 'get_bulk_event_section_floor_price',
                    'items' => $floorPricesToFetch,
                    'currency' => $userCurrency,
                ];

                // Convert the POST data array to JSON
                $postDataJson = json_encode($data);

                // Make the POST request to the API using Guzzle
                try {
                    $response = $this->client->post($apiEndpoint, [
                        'headers' => [
                            'Authorization' => "Bearer $jwtToken",
                            'Content-Type' => 'application/json',
                        ],
                        'body' => $postDataJson,
                    ]);

                    // Get the response body
                    $responseBody = $response->getBody()->getContents();

                    $floorPrices = json_decode($responseBody);
                    if (isset($floorPrices) && is_array($floorPrices)) {
                        foreach ($floorPrices as $floorPrice) {
                            $floorPrice = (array) $floorPrice;
                            if (isset($inventoryData[$floorPrice["itemId"]])) {
                                $floorPriceFormatted = (isset($floorPrice['floorPrice'])) ? $this->utils->formatAmountAndCurrencyAsSymbol($floorPrice['floorPrice'], $user->getCurrency()) : "N/A";
                                $inventoryData[$floorPrice["itemId"]]["floorPrice"] = $floorPriceFormatted;
                                // Calculate projected profit
                                $quantityRemain = $inventoryData[$floorPrice["itemId"]]["quantityRemain"];
                                $totalCost = $inventoryData[$floorPrice["itemId"]]["totalCost"];
                                $inventoryData[$floorPrice["itemId"]]["projectedProfit"] = $floorPrice["floorPrice"] * $quantityRemain - $totalCost;
                            }

                            $cacheItem = $this->cache->getItem('viagogoSectionFloorPrice_' . str_replace(' ', '', $floorPrice['section']) . $floorPrice['eventId']);
                            $cacheItem->set($floorPrice);
                            $cacheItem->expiresAfter(600); // 10 minutes
                            $this->cache->save($cacheItem);
                        }
                    }
                } catch (\GuzzleHttp\Exception\RequestException $e) {
                    // Handle exceptions or errors here
                }
            }

            $result = array(
                "total" => count($inventory),
                "totalNotFiltered" => count($inventory),
                "rows" => array_values($inventoryData),
            );
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    #[Route('/api/user/listings', methods: ['GET'], name: 'api_user_listings')]
    public function listings(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            // second parameter is default value
            $offset = $request->query->get('offset', 0);
            $itemsPerPage = $request->query->get('limit', 10);
            $sort = $request->query->get('sort', null);
            $order = $request->query->get('order', 'desc');

            $inventory = $this->firestore->get_user_inventory($user->getId());
            $listings = array_values(array_filter($inventory, function ($item) {
                return $item->getStatus() === InventoryItem::ITEM_LISTED;
            }));

            $userCurrency = $user->getCurrency();
            $exchangeRates = $this->utils->cacheExchangeRates($userCurrency);

            $floorPricesToFetch = array();

            if (isset($sort)) {
                switch ($sort) {
                    case 'name':
                        if ($order === 'asc') {
                            usort($listings, function ($a, $b) {
                                return strcmp($a->getName(), $b->getName());
                            });
                        } else {
                            usort($listings, function ($a, $b) {
                                return strcmp($b->getName(), $a->getName());
                            });
                        }
                        break;

                    case 'tickets':
                        if ($order === 'asc') {
                            usort($listings, function ($a, $b) {
                                $aTickets = $a->getQuantityRemain();
                                $bTickets = $b->getQuantityRemain();

                                if ($aTickets === null && $bTickets === null) {
                                    return 0;
                                }

                                if ($aTickets === null) {
                                    return 1;
                                }

                                if ($bTickets === null) {
                                    return -1;
                                }

                                // Compare the numeric values
                                return $aTickets - $bTickets;
                            });
                        } else {
                            usort($listings, function ($a, $b) {
                                $aTickets = $a->getQuantityRemain();
                                $bTickets = $b->getQuantityRemain();

                                if ($aTickets === null && $bTickets === null) {
                                    return 0;
                                }

                                if ($aTickets === null) {
                                    return 1;
                                }

                                if ($bTickets === null) {
                                    return -1;
                                }

                                // Compare the numeric values
                                return $bTickets - $aTickets;
                            });
                        }
                        break;

                    case 'section':
                        if ($order === 'asc') {
                            usort($listings, function ($a, $b) {
                                return strcmp($a->getSection(), $b->getSection());
                            });
                        } else {
                            usort($listings, function ($a, $b) {
                                return strcmp($b->getSection(), $a->getSection());
                            });
                        }
                        break;

                    case 'roi':
                        if ($order === 'asc') {
                            usort($listings, function ($a, $b) {
                                $aRoi = $a->getRoi();
                                $bRoi = $b->getRoi();

                                if (is_string($aRoi) && is_string($bRoi)) {
                                    return 0;
                                }

                                if (is_string($aRoi)) {
                                    return 1;
                                }

                                if (is_string($bRoi)) {
                                    return -1;
                                }

                                // Compare the numeric values in descending order
                                return $aRoi - $bRoi;
                            });
                        } else {
                            usort($listings, function ($a, $b) {
                                $aRoi = $a->getRoi();
                                $bRoi = $b->getRoi();

                                if (is_string($aRoi) && is_string($bRoi)) {
                                    return 0;
                                }

                                if (is_string($aRoi)) {
                                    return 1;
                                }

                                if (is_string($bRoi)) {
                                    return -1;
                                }

                                // Compare the numeric values in descending order
                                return $bRoi - $aRoi;
                            });
                        }
                        break;

                    case 'yourPrice':
                        if ($order === 'asc') {
                            usort($listings, function ($a, $b) {
                                $aPrice = $a->getYourPricePerTicket()["amount"];
                                $bPrice = $b->getYourPricePerTicket()["amount"];

                                // Compare the numeric values
                                return $aPrice - $bPrice;
                            });
                        } else {
                            usort($listings, function ($a, $b) {
                                $aPrice = $a->getYourPricePerTicket()["amount"];
                                $bPrice = $b->getYourPricePerTicket()["amount"];

                                // Compare the numeric values in descending order
                                return $bPrice - $aPrice;
                            });
                        }
                        break;

                    case 'date':
                        if ($order === 'asc') {
                            usort($listings, function ($a, $b) {
                                $aDate = $a->getEventDate();
                                $bDate = $b->getEventDate();

                                if ($aDate === null && $bDate === null) {
                                    return 0;
                                }

                                if ($aDate === null) {
                                    return 1;
                                }

                                if ($bDate === null) {
                                    return -1;
                                }

                                return $aDate <=> $bDate;
                            });
                        } else {
                            usort($listings, function ($a, $b) {
                                $aDate = $a->getEventDate();
                                $bDate = $b->getEventDate();

                                if ($aDate === null && $bDate === null) {
                                    return 0;
                                }

                                if ($aDate === null) {
                                    return -1;
                                }

                                if ($bDate === null) {
                                    return 1;
                                }

                                return $bDate <=> $aDate;
                            });
                        }
                        break;

                    default:
                        break;
                }
            }

            $listingsData = array();
            for ($i = 0; $i < $itemsPerPage && $offset + $i < count($listings); $i++) {
                $item = $listings[$offset + $i];
                $eventId = $item->getViagogoEventId(); // Replace with the actual event ID
                $categoryId = $item->getViagogoCategoryId(); // Replace with the actual category ID
                $section = $item->getSection(); // Replace with the actual section name
                $userCurrency = $user->getCurrency(); // Replace with the actual currency
                $floorPriceFormatted = 'N/A';

                $cacheItem = $this->cache->getItem('viagogoSectionFloorPrice_' . str_replace(' ', '', $section) . $eventId);
                $floorPrice = $cacheItem->get();

                if ($cacheItem->isHit() && !is_bool($floorPrice)) {
                    if ($floorPrice === 'N/A') {
                        $floorPriceFormatted = $floorPrice;
                    } else if (strtoupper($userCurrency) === strtoupper($floorPrice["currency"])) {
                        $floorPriceFormatted = $this->utils->formatAmountAndCurrencyAsSymbol($floorPrice["floorPrice"], $userCurrency);
                    } else {
                        $floorPriceConverted = $this->utils->convertCurrency(floatval($floorPrice["floorPrice"]), $exchangeRates, $floorPrice["currency"]);
                        $floorPriceFormatted = (isset($floorPriceConverted)) ? $this->utils->formatAmountAndCurrencyAsSymbol($floorPriceConverted, $user->getCurrency()) : "N/A";
                    }
                } else {
                    // floor price not in cache, fetch it later
                    $floorPricesToFetch[] = array("itemId" => $item->getId(), "eventId" => $eventId, "categoryId" => $categoryId, "section" => $section);

                    // set floor price to N/A for now
                    $cacheItem->set($floorPriceFormatted);
                    $cacheItem->expiresAfter(600); // 10 minutes
                    // save the cache item
                    $this->cache->save($cacheItem);
                }

                $amount = $item->getYourPricePerTicket()["amount"];
                $currency = $item->getYourPricePerTicket()["currency"];
                if (strtoupper($currency) === strtoupper($user->getCurrency())) {
                    $yourPrice = $this->utils->formatAmountAndCurrencyAsSymbol($amount, $currency);
                } else {
                    $converted = $this->utils->convertCurrency(floatval($amount), $exchangeRates, $currency);
                    $yourPrice = (isset($converted)) ? $this->utils->formatAmountAndCurrencyAsSymbol($converted, $user->getCurrency()) : "N/A";
                }

                $itemRoi = $this->inventoryService->calculateRoi($item);
                $rowData = array(
                    'name' => ($item->getId() !== null) ? "<a href='/{$request->getLocale()}/inventory/{$item->getId()}'>{$item->getName()} - {$item->getCity()}</a>" : "{$item->getName()} - {$item->getCity()}",
                    'date' => ($item->getEventDate() !== null) ? $item->getEventDate()->format('F j, Y \a\t h:i A') : '',
                    'tickets' => ($item->getQuantity() !== null) ? $item->getQuantity() . "/" . $item->getQuantityRemain() : '',
                    'section' => $item->getSection(),
                    'floorPrice' => $floorPriceFormatted,
                    'yourPrice' => $yourPrice,
                    'roi' => ($itemRoi !== "N/A") ? number_format($itemRoi, 2, '.', '') . "%" : $itemRoi,
                );

                $listingsData[$item->getId()] = $rowData;
            }

            if (sizeof($floorPricesToFetch) > 0) {
                /* fetch floor prices */
                $apiEndpoint = 'https://api.mindsolutions.app/'; // Replace with the URL of your PHP script
                $jwtToken = $this->utils->generateToken(self::JWT_EXPIRY_IN_SECONDS);

                $data = [
                    'action' => 'get_bulk_event_section_floor_price',
                    'items' => $floorPricesToFetch,
                    'currency' => $userCurrency,
                ];

                // Convert the POST data array to JSON
                $postDataJson = json_encode($data);

                // Make the POST request to the API using Guzzle
                try {
                    $response = $this->client->post($apiEndpoint, [
                        'headers' => [
                            'Authorization' => "Bearer $jwtToken",
                            'Content-Type' => 'application/json',
                        ],
                        'body' => $postDataJson,
                    ]);

                    // Get the response body
                    $responseBody = $response->getBody()->getContents();

                    $floorPrices = json_decode($responseBody);
                    if (isset($floorPrices) && is_array($floorPrices)) {
                        foreach ($floorPrices as $floorPrice) {
                            $floorPrice = (array) $floorPrice;
                            if (isset($listingsData[$floorPrice["itemId"]])) {
                                $floorPriceFormatted = (isset($floorPrice['floorPrice'])) ? $this->utils->formatAmountAndCurrencyAsSymbol($floorPrice['floorPrice'], $user->getCurrency()) : "N/A";
                                $listingsData[$floorPrice["itemId"]]["floorPrice"] = $floorPriceFormatted;
                            }
                            // Store section foor price in cache for 10 minutes (adjust TTL as needed)
                            $cacheItem = $this->cache->getItem('viagogoSectionFloorPrice_' . str_replace(' ', '', $floorPrice['section']) . $floorPrice['eventId']);
                            $cacheItem->set($floorPrice);
                            $cacheItem->expiresAfter(600); // 10 minutes
                            $this->cache->save($cacheItem);
                        }
                    }
                } catch (\GuzzleHttp\Exception\RequestException $e) {
                    // Handle exceptions or errors here
                }
            }

            $result = array(
                "total" => count($listings),
                "totalNotFiltered" => count($listings),
                "rows" => array_values($listingsData),
            );
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    #[Route('/api/releases', methods: ['GET'], name: 'api_releases')]
    public function releases(#[CurrentUser] ?User $user, Request $request, ReleaseRepository $releaseRepository, UserRepository $userRepository): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            // second parameter is default value
            $offset = $request->query->get('offset', 0);
            $itemsPerPage = $request->query->get('limit', 10);
            $sort = $request->query->get('sort', null);
            $order = $request->query->get('order', 'desc');

            $releases = array_values($releaseRepository->getAll());
            if (isset($sort)) {
                switch ($sort) {
                    case 'description':
                        if ($order === 'asc') {
                            usort($releases, function ($a, $b) {
                                return strcmp($a->getDescription(), $b->getDescription());
                            });
                        } else {
                            usort($releases, function ($a, $b) {
                                return strcmp($b->getDescription(), $a->getDescription());
                            });
                        }
                        break;

                    case 'eventDate':
                        if ($order === 'asc') {
                            usort($releases, function ($a, $b) {
                                $aDate = $a->getEventDateAsDateTime();
                                $bDate = $b->getEventDateAsDateTime();

                                if ($aDate === null && $bDate === null) {
                                    return 0;
                                }

                                if ($aDate === null) {
                                    return 1;
                                }

                                if ($bDate === null) {
                                    return -1;
                                }

                                return $aDate <=> $bDate;
                            });
                        } else {
                            usort($releases, function ($a, $b) {
                                $aDate = $a->getEventDateAsDateTime();
                                $bDate = $b->getEventDateAsDateTime();

                                if ($aDate === null && $bDate === null) {
                                    return 0;
                                }

                                if ($aDate === null) {
                                    return -1;
                                }

                                if ($bDate === null) {
                                    return 1;
                                }

                                return $bDate <=> $aDate;
                            });
                        }
                        break;

                    case 'releaseDate':
                        if ($order === 'asc') {
                            usort($releases, function ($a, $b) {
                                $aDate = $a->getReleaseDateAsDateTime();
                                $bDate = $b->getReleaseDateAsDateTime();

                                if ($aDate === null && $bDate === null) {
                                    return 0;
                                }

                                if ($aDate === null) {
                                    return 1;
                                }

                                if ($bDate === null) {
                                    return -1;
                                }

                                return $aDate <=> $bDate;
                            });
                        } else {
                            usort($releases, function ($a, $b) {
                                $aDate = $a->getReleaseDateAsDateTime();
                                $bDate = $b->getReleaseDateAsDateTime();

                                if ($aDate === null && $bDate === null) {
                                    return 0;
                                }

                                if ($aDate === null) {
                                    return -1;
                                }

                                if ($bDate === null) {
                                    return 1;
                                }

                                return $bDate <=> $aDate;
                            });
                        }
                        break;

                    default:
                        break;
                }
            }

            $releasesData = array();
            for ($i = 0; $i < $itemsPerPage && $offset + $i < count($releases); $i++) {
                $release = $releases[$offset + $i];
                $cacheItem = $this->cache->getItem('user_' . $release->getAuthor());
                if (!$cacheItem->isHit()) {
                    // User object is not in cache, fetch from the database
                    $author = $userRepository->findOneBy(['id' => $release->getAuthor()->getId()]);
                    // Store user object in cache for 10 minutes (adjust TTL as needed)
                    $cacheItem->set($author);
                    $cacheItem->expiresAfter(600); // 10 minutes
                    $this->cache->save($cacheItem);
                }

                $itemData = '<span data-item-id="' . $release->getId() . '" data-location="' . $release->getLocation() . '" data-city="' . $release->getCity() . '" data-country="' . $release->getCountryCode() . '" data-retailer="' . $release->getRetailer() . '" data-early-link="' . $release->getEarlyLink() . '" data-author="' . $author->getDiscordUsername() . '"  data-comments="' . $release->getComments() . '"></span>';

                $actions = '
    <button name="copy-release" type="button" class="btn btn-soft-primary" data-item-id="' . $release->getId() . '">
        <svg class="feather feather-copy" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
            <rect height="13" rx="2" ry="2" width="13" x="9" y="9" />
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
        </svg>
    </button>
    <button name="edit-release" type="button" class="btn btn-soft-primary" data-item-id="' . $release->getId() . '">
        <svg class="icon-24" width="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M11.4925 2.78906H7.75349C4.67849 2.78906 2.75049 4.96606 2.75049 8.04806V16.3621C2.75049 19.4441 4.66949 21.6211 7.75349 21.6211H16.5775C19.6625 21.6211 21.5815 19.4441 21.5815 16.3621V12.3341" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M8.82812 10.921L16.3011 3.44799C17.2321 2.51799 18.7411 2.51799 19.6721 3.44799L20.8891 4.66499C21.8201 5.59599 21.8201 7.10599 20.8891 8.03599L13.3801 15.545C12.9731 15.952 12.4211 16.181 11.8451 16.181H8.09912L8.19312 12.401C8.20712 11.845 8.43412 11.315 8.82812 10.921Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M15.1655 4.60254L19.7315 9.16854" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
    </button>
    <button name="delete-release" data-toggle="modal" data-item-id="' . $release->getId() . '" type="button" class="btn btn-soft-danger">
        <svg class="icon-24" width="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19.3248 9.46826C19.3248 9.46826 18.7818 16.2033 18.4668 19.0403C18.3168 20.3953 17.4798 21.1893 16.1088 21.2143C13.4998 21.2613 10.8878 21.2643 8.27979 21.2093C6.96079 21.1823 6.13779 20.3783 5.99079 19.0473C5.67379 16.1853 5.13379 9.46826 5.13379 9.46826" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M20.708 6.23975H3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M17.4406 6.23973C16.6556 6.23973 15.9796 5.68473 15.8256 4.91573L15.5826 3.69973C15.4326 3.13873 14.9246 2.75073 14.3456 2.75073H10.1126C9.53358 2.75073 9.02558 3.13873 8.87558 3.69973L8.63258 4.91573C8.47858 5.68473 7.80258 6.23973 7.01758 6.23973" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
    </button>
    ';
                $rowData = array(
                    'releaseData' => $itemData,
                    'description' => $release->getDescription() . " - " . $release->getCity(),
                    'country' => $release->getCountryCode(),
                    'eventDate' => ($release->getEventDateAsDateTime() !== null) ? $release->getEventDateAsDateTime()->format('F j, Y \a\t h:i A') : '',
                    'releaseDate' => ($release->getReleaseDateAsDateTime() !== null) ? $release->getReleaseDateAsDateTime()->format('F j, Y \a\t h:i A') : '',
                    'actions' => $actions,
                );

                $releasesData[] = $rowData;
            }

            $result = array(
                "total" => count($releases),
                "totalNotFiltered" => count($releases),
                "rows" => $releasesData,
            );
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    #[Route('/api/user/sales', methods: ['GET'], name: 'api_user_sales')]
    public function sales(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            // second parameter is default value
            $offset = $request->query->get('offset', 0);
            $itemsPerPage = $request->query->get('limit', 10);
            $sort = $request->query->get('sort', null);
            $order = $request->query->get('order', 'desc');

            $sales = $this->firestore->get_user_sales($user->getId());
            $userCurrency = $user->getCurrency();
            $exchangeRates = $this->utils->cacheExchangeRates($userCurrency);

            if (isset($sort)) {
                switch ($sort) {
                    case 'eventName':
                        if ($order === 'asc') {
                            usort($sales, function ($a, $b) {
                                return strcmp($a->getEventDescription(), $b->getEventDescription());
                            });
                        } else {
                            usort($sales, function ($a, $b) {
                                return strcmp($b->getEventDescription(), $a->getEventDescription());
                            });
                        }
                        break;

                    case 'quantity':
                        if ($order === 'asc') {
                            usort($sales, function ($a, $b) {
                                $aTickets = $a->getQuantity();
                                $bTickets = $b->getQuantity();

                                if ($aTickets === null && $bTickets === null) {
                                    return 0;
                                }

                                if ($aTickets === null) {
                                    return 1;
                                }

                                if ($bTickets === null) {
                                    return -1;
                                }

                                // Compare the numeric values
                                return $aTickets - $bTickets;
                            });
                        } else {
                            usort($sales, function ($a, $b) {
                                $aTickets = $a->getQuantity();
                                $bTickets = $b->getQuantity();

                                if ($aTickets === null && $bTickets === null) {
                                    return 0;
                                }

                                if ($aTickets === null) {
                                    return 1;
                                }

                                if ($bTickets === null) {
                                    return -1;
                                }

                                // Compare the numeric values
                                return $bTickets - $aTickets;
                            });
                        }
                        break;

                    case 'platform':
                        if ($order === 'asc') {
                            usort($sales, function ($a, $b) {
                                return strcmp($a->getPlatform(), $b->getPlatform());
                            });
                        } else {
                            usort($sales, function ($a, $b) {
                                return strcmp($b->getPlatform(), $a->getPlatform());
                            });
                        }
                        break;

                    case 'totalPayout':
                        if ($order === 'asc') {
                            usort($sales, function ($a, $b) {
                                $aPrice = $a->getTotalPayout()["amount"];
                                $bPrice = $b->getTotalPayout()["amount"];

                                // Compare the numeric values
                                return $aPrice - $bPrice;
                            });
                        } else {
                            usort($sales, function ($a, $b) {
                                $aPrice = $a->getTotalPayout()["amount"];
                                $bPrice = $b->getTotalPayout()["amount"];

                                // Compare the numeric values in descending order
                                return $bPrice - $aPrice;
                            });
                        }
                        break;

                    case 'saleDate':
                        if ($order === 'asc') {
                            usort($sales, function ($a, $b) {
                                $aDate = $a->getSaleDate();
                                $bDate = $b->getSaleDate();

                                if ($aDate === null && $bDate === null) {
                                    return 0;
                                }

                                if ($aDate === null) {
                                    return 1;
                                }

                                if ($bDate === null) {
                                    return -1;
                                }

                                return $aDate <=> $bDate;
                            });
                        } else {
                            usort($sales, function ($a, $b) {
                                $aDate = $a->getSaleDate();
                                $bDate = $b->getSaleDate();

                                if ($aDate === null && $bDate === null) {
                                    return 0;
                                }

                                if ($aDate === null) {
                                    return -1;
                                }

                                if ($bDate === null) {
                                    return 1;
                                }

                                return $bDate <=> $aDate;
                            });
                        }
                        break;

                    default:
                        break;
                }
            }

            $salesData = array();
            for ($i = 0; $i < $itemsPerPage && $offset + $i < count($sales); $i++) {
                $item = $sales[$offset + $i];
                $userCurrency = $user->getCurrency(); // Replace with the actual currency

                $amount = $item->getTotalPayout()["amount"];
                $currency = $item->getTotalPayout()["currency"];
                if (strtoupper($currency) === strtoupper($user->getCurrency())) {
                    $totalPayout = $this->utils->formatAmountAndCurrencyAsSymbol($amount, $currency);
                } else {
                    $converted = $this->utils->convertCurrency(floatval($amount), $exchangeRates, $currency);
                    $totalPayout = (isset($converted)) ? $this->utils->formatAmountAndCurrencyAsSymbol($converted, $user->getCurrency()) : "N/A";
                }

                $saleData = '<span data-sale-id="' . $item->getSaleId() . '" data-event-id="' . $item->getViagogoEventId() . '" data-seat-from="' . $item->getSeatFrom() . '" data-seat-to="' . $item->getSeatTo() . '" data-section="' . $item->getSection() . '" data-row="' . $item->getRow() . '"></span>';

                $rowData = array(
                    'saleData' => $saleData,
                    'eventName' => ($item->getId() !== null) ? "<a href='/{$request->getLocale()}/inventory/{$item->getId()}'>{$item->getName()} - {$item->getCity()}</a>" : "{$item->getName()} - {$item->getCity()}",
                    'platform' => $item->getPlatform(),
                    'quantity' => $item->getQuantity(),
                    'ticketType' => $item->getTicketType(),
                    'totalPayout' => $totalPayout,
                    'saleDate' => ($item->getSaleDate() !== null) ? $item->getSaleDate()->format('F j, Y \a\t h:i A') : '',
                );

                $salesData[$item->getId()] = $rowData;
            }

            $result = array(
                "total" => count($sales),
                "totalNotFiltered" => count($sales),
                "rows" => array_values($salesData),
            );
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    public function get_calendar_data($userId, $startDate, $endDate, $sort, $order, ReleaseRepository $releaseRepository, string $locale)
    {
        $inventory = $this->firestore->get_user_inventory($userId);
        $inventory = array_values(array_filter($inventory, function ($item) use ($startDate, $endDate) {
            return $item->getEventDate() !== null && $item->getEventDate() >= $startDate && $item->getEventDate() <= $endDate;
        }));

        $releases = $releaseRepository->getAll();
        $releases = array_values(array_filter($releases, function (Release $release) use ($startDate, $endDate) {
            return $release->getReleaseDateAsDateTime() !== null && $release->getReleaseDateAsDateTime() >= $startDate && $release->getReleaseDateAsDateTime() <= $endDate;
        }));

        if (isset($sort)) {
            switch ($sort) {
                case 'date':
                    if ($order === 'asc') {
                        usort($inventory, function ($a, $b) {
                            $aDate = $a->getEventDate();
                            $bDate = $b->getEventDate();

                            if ($aDate === null && $bDate === null) {
                                return 0;
                            }

                            if ($aDate === null) {
                                return 1;
                            }

                            if ($bDate === null) {
                                return -1;
                            }

                            return $aDate <=> $bDate;
                        });

                        usort($releases, function ($a, $b) {
                            $aDate = $a->getReleaseDateAsDateTime();
                            $bDate = $b->getReleaseDateAsDateTime();

                            if ($aDate === null && $bDate === null) {
                                return 0;
                            }

                            if ($aDate === null) {
                                return 1;
                            }

                            if ($bDate === null) {
                                return -1;
                            }

                            return $aDate <=> $bDate;
                        });
                    } else {
                        usort($inventory, function ($a, $b) {
                            $aDate = $a->getEventDate();
                            $bDate = $b->getEventDate();

                            if ($aDate === null && $bDate === null) {
                                return 0;
                            }

                            if ($aDate === null) {
                                return -1;
                            }

                            if ($bDate === null) {
                                return 1;
                            }

                            return $bDate <=> $aDate;
                        });

                        usort($releases, function ($a, $b) {
                            $aDate = $a->getReleaseDateAsDateTime();
                            $bDate = $b->getReleaseDateAsDateTime();

                            if ($aDate === null && $bDate === null) {
                                return 0;
                            }

                            if ($aDate === null) {
                                return -1;
                            }

                            if ($bDate === null) {
                                return 1;
                            }

                            return $bDate <=> $aDate;
                        });
                    }
                    break;

                default:
                    break;
            }
        }

        $calendarData = [];

        // add user's inventory items to calendar data
        foreach ($inventory as $item) {
            $eventDate = $item->getEventDate();
            $today = new DateTime();
            if ($eventDate->format('Y-m-d') === $today->format('Y-m-d')) {
                // event is today
                $backgroundColor = "rgba(255,127,80, 0.2)";
                $textColor = "rgba(255,127,80, 1)";
                $borderColor = "rgba(255,127,80, 1)";
            } elseif ($eventDate < $today) {
                // past event
                $backgroundColor = "rgba(169,169,169,0.2)";
                $textColor = "rgba(169,169,169,1)";
                $borderColor = "rgba(169,169,169,1)";
            } else {
                // future event
                $backgroundColor = "rgba(58, 87, 232, 0.2)";
                $textColor = "rgba(58, 87, 232, 1)";
                $borderColor = "rgba(58, 87, 232, 1)";
            }
            $calendarData[] = [
                "title" => $item->getName(),
                "start" => $item->getEventDate()->format('Y-m-d\TH:i:s.000\Z'),
                "backgroundColor" => $backgroundColor,
                "textColor" => $textColor,
                "borderColor" => $borderColor,
                "eventId" => $item->getId(),
                "eventDate" => $item->getEventDate()->format('F j, Y \a\t h:i A'),
                "eventDescription" => $item->getName(),
                "eventLocation" => $item->getCity() . " - " . $item->getLocation(),
                "eventGenre" => $item->getTicketGenre(),
                "pageUrl" => ($item->getId() !== null) ? "/{$locale}/inventory/{$item->getId()}"  : null,
            ];
        }

        // add Mind Solutions provided releases to calendar
        foreach ($releases as $item) {
            $releaseDate = $item->getReleaseDateAsDateTime();
            $today = new DateTime();
            if ($releaseDate->format('Y-m-d') === $today->format('Y-m-d')) {
                // event is today
                $backgroundColor = "rgba(255,127,80, 0.2)";
                $textColor = "rgba(255,127,80, 1)";
                $borderColor = "rgba(255,127,80, 1)";
            } elseif ($releaseDate < $today) {
                // past event
                $backgroundColor = "rgba(169,169,169,0.2)";
                $textColor = "rgba(169,169,169,1)";
                $borderColor = "rgba(169,169,169,1)";
            } else {
                // future event
                $backgroundColor = "rgba(255, 165, 0, 0.2)";
                $textColor = "rgba(255, 165, 0, 1)";
                $borderColor = "rgba(255, 165, 0, 1)";
            }
            $calendarData[] = [
                "title" => $item->getDescription(),
                "start" => $item->getReleaseDateAsDateTime()->format('Y-m-d\TH:i:s.000\Z'),
                "backgroundColor" => $backgroundColor,
                "textColor" => $textColor,
                "borderColor" => $borderColor,
                "releaseId" => $item->getId(),
                "releaseDate" => $item->getReleaseDateAsDateTime()->format('F j, Y \a\t h:i A'),
                "eventDate" => $item->getEventDateAsDateTime()->format('F j, Y \a\t h:i A'),
                "eventDescription" => $item->getDescription(),
                "eventLocation" => $item->getCity() . " - " . $item->getLocation(),
                "comments" => $item->getComments(),
                "pageUrl" => $item->getEarlyLink(),
                "retailer" => $item->getRetailer(),
            ];
        }

        return ["calendar" => $calendarData, "total" => sizeof($inventory)];
    }

    #[Route('/api/user/calendar/merged', methods: ['GET'], name: 'api_user_calendar_merged')]
    public function calendarMerged(#[CurrentUser] ?User $user, Request $request, ReleaseRepository $releaseRepository): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            $today = new DateTime();
            $oneYearAgo = (clone $today)->modify('-365 days');
            $oneYearSinceNow = (clone $today)->modify('+365 days');
            $sort = $request->query->get('sort', 'date');
            $order = $request->query->get('order', 'asc');
            $startDate = $request->query->get('startDate') ? new DateTime($request->query->get('startDate')) : $oneYearAgo;
            $endDate = $request->query->get('endDate') ? new DateTime($request->query->get('endDate')) : $oneYearSinceNow;
            $calendar = $this->get_calendar_data($user->getId(), $startDate, $endDate, $sort, $order, $releaseRepository, $request->getLocale());
            $result = array(
                "success" => true,
                "calendar" => $calendar["calendar"],
                "total" => $calendar["total"],
            );
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    function charts_get_sales_series(User $user, $startDate, $endDate, $sort, $order)
    {
        $sales = $this->firestore->get_user_sales($user->getId());
        $sales = array_values(array_filter($sales, function ($sale) use ($startDate, $endDate) {
            return $sale->getSaleDate() !== null && $sale->getSaleDate() >= $startDate && $sale->getSaleDate() <= $endDate;
        }));
        $userCurrency = $user->getCurrency();
        $exchangeRates = $this->utils->cacheExchangeRates($userCurrency);

        if (isset($sort)) {
            switch ($sort) {
                case 'date':
                    if ($order === 'asc') {
                        usort($sales, function ($a, $b) {
                            $aDate = $a->getSaleDate();
                            $bDate = $b->getSaleDate();

                            if ($aDate === null && $bDate === null) {
                                return 0;
                            }

                            if ($aDate === null) {
                                return 1;
                            }

                            if ($bDate === null) {
                                return -1;
                            }

                            return $aDate <=> $bDate;
                        });
                    } else {
                        usort($sales, function ($a, $b) {
                            $aDate = $a->getSaleDate();
                            $bDate = $b->getSaleDate();

                            if ($aDate === null && $bDate === null) {
                                return 0;
                            }

                            if ($aDate === null) {
                                return -1;
                            }

                            if ($bDate === null) {
                                return 1;
                            }

                            return $bDate <=> $aDate;
                        });
                    }
                    break;

                default:
                    break;
            }
        }

        $seriesData = [];
        $salesTotal = 0;

        foreach ($sales as $item) {

            $amount = $item->getTotalPayout()["amount"];
            $currency = $item->getTotalPayout()["currency"];
            if (strtoupper($currency) === strtoupper($user->getCurrency())) {
                $convertedAmount = $amount;
            } else {
                $converted = $this->utils->convertCurrency(floatval($amount), $exchangeRates, $currency);
                $convertedAmount = $converted;
            }
            if (isset($convertedAmount)) {
                $saleData = [$item->getSaleDate()->getTimestamp() * 1000, number_format(floatval($convertedAmount), 2)];
                $seriesData[] = $saleData;
                $salesTotal += floatval($convertedAmount);
            }
        }

        return ["series" => $seriesData, "total" => $salesTotal, "currency" => $user->getCurrency()];
    }

    function charts_get_purchases_series(User $user, $startDate, $endDate, $sort, $order)
    {
        $inventory = $this->firestore->get_user_inventory($user->getId());
        $inventory = array_values(array_filter($inventory, function ($item) use ($startDate, $endDate) {
            return $item->getPurchaseDate() !== null && $item->getPurchaseDate() >= $startDate && $item->getPurchaseDate() <= $endDate;
        }));

        $userCurrency = $user->getCurrency();
        $exchangeRates = $this->utils->cacheExchangeRates($userCurrency);

        if (isset($sort)) {
            switch ($sort) {
                case 'date':
                    if ($order === 'asc') {
                        usort($inventory, function ($a, $b) {
                            $aDate = $a->getPurchaseDate();
                            $bDate = $b->getPurchaseDate();

                            if ($aDate === null && $bDate === null) {
                                return 0;
                            }

                            if ($aDate === null) {
                                return 1;
                            }

                            if ($bDate === null) {
                                return -1;
                            }

                            return $aDate <=> $bDate;
                        });
                    } else {
                        usort($inventory, function ($a, $b) {
                            $aDate = $a->getPurchaseDate();
                            $bDate = $b->getPurchaseDate();

                            if ($aDate === null && $bDate === null) {
                                return 0;
                            }

                            if ($aDate === null) {
                                return -1;
                            }

                            if ($bDate === null) {
                                return 1;
                            }

                            return $bDate <=> $aDate;
                        });
                    }
                    break;

                default:
                    break;
            }
        }

        $seriesData = [];
        $purchasesTotal = 0;

        foreach ($inventory as $item) {

            $amount = $item->getTotalCost()["amount"];
            $currency = $item->getTotalCost()["currency"];
            if (strtoupper($currency) === strtoupper($user->getCurrency())) {
                $convertedAmount = $amount;
            } else {
                $converted = $this->utils->convertCurrency(floatval($amount), $exchangeRates, $currency);
                $convertedAmount = $converted;
            }
            if (isset($convertedAmount)) {
                $purchaseDate = [$item->getPurchaseDate()->getTimestamp() * 1000, number_format(floatval($convertedAmount), 2)];
                $seriesData[] = $purchaseDate;
                $purchasesTotal += floatval($convertedAmount);
            }
        }

        return ["series" => $seriesData, "total" => $purchasesTotal, "currency" => $user->getCurrency()];
    }

    function charts_get_inventory_value_series(User $user, $startDate, $endDate, $sort, $order)
    {
        $inventory = $this->firestore->get_inventory_values($user->getId());
        $inventory = array_values(array_filter($inventory, function ($inventoryValue) use ($startDate, $endDate) {
            return $inventoryValue[0] >= $startDate && $inventoryValue[0] <= $endDate;
        }));

        $userCurrency = $user->getCurrency();
        $exchangeRates = $this->utils->cacheExchangeRates($userCurrency);

        if (isset($sort)) {
            switch ($sort) {
                case 'date':
                    if ($order === 'asc') {
                        usort($inventory, function ($a, $b) {
                            $aDate = $a[0];
                            $bDate = $b[0];

                            if ($aDate === null && $bDate === null) {
                                return 0;
                            }

                            if ($aDate === null) {
                                return 1;
                            }

                            if ($bDate === null) {
                                return -1;
                            }

                            return $aDate <=> $bDate;
                        });
                    } else {
                        usort($inventory, function ($a, $b) {
                            $aDate = $a[0];
                            $bDate = $b[0];

                            if ($aDate === null && $bDate === null) {
                                return 0;
                            }

                            if ($aDate === null) {
                                return -1;
                            }

                            if ($bDate === null) {
                                return 1;
                            }

                            return $bDate <=> $aDate;
                        });
                    }
                    break;

                default:
                    break;
            }
        }

        $seriesData = [];
        foreach ($inventory as $inventoryValue) {

            $amount = $inventoryValue[1];
            $currency = $inventoryValue[2];
            if (strtoupper($currency) === strtoupper($user->getCurrency())) {
                $convertedAmount = $amount;
            } else {
                $converted = $this->utils->convertCurrency(floatval($amount), $exchangeRates, $currency);
                $convertedAmount = $converted;
            }
            if (isset($convertedAmount)) {
                $valueData = [$inventoryValue[0]->getTimestamp() * 1000, number_format($convertedAmount, 2, '.', '')];
                $seriesData[] = $valueData;
            }
        }

        return ["series" => $seriesData, "total" => $convertedAmount ?? 0, "currency" => $user->getCurrency()];
    }

    #[Route('/api/user/chart/sales', methods: ['GET'], name: 'api_user_chart_sales')]
    public function salesChart(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            $sort = $request->query->get('sort', 'date');
            $order = $request->query->get('order', 'asc');
            $startDate = $request->query->get('startDate') ? new DateTime($request->query->get('startDate')) : new DateTime('1920-01-01');
            $endDate = $request->query->get('endDate') ? new DateTime($request->query->get('endDate')) : new DateTime();
            $chartsSeries = $this->charts_get_sales_series($user, $startDate, $endDate, $sort, $order);
            $result = array(
                "success" => true,
                "series" => array_values($chartsSeries["series"]),
                "total" => $chartsSeries["total"],
                "currency" => $chartsSeries["currency"],
            );
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    #[Route('/api/user/chart/purchases', methods: ['GET'], name: 'api_user_chart_purchases')]
    public function purchasesChart(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            $sort = $request->query->get('sort', 'date');
            $order = $request->query->get('order', 'asc');
            $startDate = $request->query->get('startDate') ? new DateTime($request->query->get('startDate')) : new DateTime('1920-01-01');
            $endDate = $request->query->get('endDate') ? new DateTime($request->query->get('endDate')) : new DateTime();
            $chartsSeries = $this->charts_get_purchases_series($user, $startDate, $endDate, $sort, $order);
            $result = array(
                "success" => true,
                "series" => array_values($chartsSeries["series"]),
                "total" => $chartsSeries["total"],
                "currency" => $chartsSeries["currency"],
            );
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    #[Route('/api/user/chart/inventory', methods: ['GET'], name: 'api_user_chart_inventory')]
    public function inventoryChart(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                return new Response("Unauthorized", Response::HTTP_UNAUTHORIZED);
            }

            $sort = $request->query->get('sort', 'date');
            $order = $request->query->get('order', 'asc');
            $startDate = $request->query->get('startDate') ? new DateTime($request->query->get('startDate')) : new DateTime('1920-01-01');
            $endDate = $request->query->get('endDate') ? new DateTime($request->query->get('endDate')) : new DateTime();
            $chartsSeries = $this->charts_get_inventory_value_series($user, $startDate, $endDate, $sort, $order);
            $result = array(
                "success" => true,
                "series" => array_values($chartsSeries["series"]),
                "total" => $chartsSeries["total"],
                "currency" => $chartsSeries["currency"],
            );
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }
}
