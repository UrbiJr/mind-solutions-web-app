<?php

namespace App\Controller;

use App\Entity\InventoryItem;
use App\Entity\User;
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

    /**
     * Return inventory as JSON object
     */
    #[Route('/inventory', methods: ['GET'], name: 'rest_inventory')]
    public function inventory(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                header('HTTP/1.0 401 Unauthorized');
                exit;
            }

            // second parameter is default value
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

                $floorPrice = $this->cache->getItem('viagogoSectionFloorPrice_' . str_replace(' ', '', $section) . $eventId);
                $floorPriceFormatted = 'N/A';
                // floor price not in cache, fetch it later
                if (!$floorPrice->isHit() || !isset($floorPrice["currency"]) || !isset($floorPrice["floorPrice"])) {
                    if ($item->getEventDate() >= $today) {
                        $floorPricesToFetch[] = array("itemId" => $item->getId(), "eventId" => $eventId, "categoryId" => $categoryId, "section" => $section);
                    }
                } else {
                    if (strtoupper($userCurrency) === strtoupper($floorPrice["currency"])) {
                        $floorPriceFormatted = $this->utils->formatAmountAndCurrencyAsSymbol($floorPrice["floorPrice"], $userCurrency);
                    } else {
                        $floorPriceConverted = $this->utils->convertCurrency(floatval($floorPrice["floorPrice"]), $exchangeRates, $floorPrice["currency"]);
                        $floorPriceFormatted = (isset($floorPriceConverted)) ? $this->utils->formatAmountAndCurrencyAsSymbol($floorPriceConverted, $user->getCurrency()) : "N/A";
                    }
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
                    'name' => ($item->getId() !== null) ? '<a href="/?model=inventory&action=itemOverview&id=' . $item->getId() . '">' . $item->getName() . " - " . $item->getCity() . '</a>' : $item->getName() . " - " . $item->getCity(),
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
            // Token has expired, handle accordingly
            header('HTTP/1.0 400 Bad Request');
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "error" => $e->getMessage()]);
            exit;
        }

        http_response_code(200);
        header("Content-Type: application/json");
        echo json_encode($result);
        exit;
    }

    /**
     * Return inventory as JSON object
     */
    #[Route('/inventoryList', methods: ['GET'], name: 'rest_inventory_list')]
    public function inventoryList(#[CurrentUser] ?User $user, Request $request): Response
    {
        try {
            if (!$user || !in_array('ROLE_MEMBER', $user->getRoles())) {
                header('HTTP/1.0 401 Unauthorized');
                exit;
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

                $floorPrice = $this->cache->getItem('viagogoSectionFloorPrice_' . str_replace(' ', '', $section) . $eventId);
                // floor price not in cache, fetch it later
                if (!$floorPrice->isHit() || !isset($floorPrice["currency"]) || !isset($floorPrice["floorPrice"])) {
                    if ($item->getEventDate() >= $today) {
                        $floorPricesToFetch[] = array("itemId" => $item->getId(), "eventId" => $eventId, "categoryId" => $categoryId, "section" => $section);
                    }
                } else {
                    if (strtoupper($userCurrency) === strtoupper($floorPrice["currency"])) {
                        $floorPriceFormatted = $this->utils->formatAmountAndCurrencyAsSymbol($floorPrice["floorPrice"], $userCurrency);
                    } else {
                        $floorPriceConverted = $this->utils->convertCurrency(floatval($floorPrice["floorPrice"]), $exchangeRates, $floorPrice["currency"]);
                        $floorPriceFormatted = (isset($floorPriceConverted)) ? $this->utils->formatAmountAndCurrencyAsSymbol($floorPriceConverted, $user->getCurrency()) : "N/A";
                    }
                    // Calculate projected profit
                    if ($item->getTotalCost()["currency"] === $userCurrency) {
                        $projectedProfit = $floorPrice["floorPrice"] * $item->getQuantityRemain() - $item->getTotalCost()["amount"];
                    } else {
                        $projectedProfit = $floorPrice["floorPrice"] * $item->getQuantityRemain() - $totalCostConverted;
                    }
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
                    'link' => '/?model=inventory&action=itemOverview&id=' . $item->getId(),
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
            // Token has expired, handle accordingly
            header('HTTP/1.0 400 Bad Request');
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "error" => $e->getMessage()]);
            exit;
        }

        http_response_code(200);
        header("Content-Type: application/json");
        echo json_encode($result);
        exit;
    }
}
