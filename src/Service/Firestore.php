<?php

namespace App\Service;

use App\Entity\InventoryItem;
use App\Entity\ViagogoAnalytics;
use Google\Cloud\Firestore\FirestoreClient;
use Google\Cloud\Core\Timestamp;
use DateTime;
use DateTimeInterface;

final class Firestore
{

    private $con;

    public function __construct(string $projectId, string $keyFilePath, private Utils $utils)
    {
        // Create the Cloud Firestore client
        if (empty($projectId)) {
            // The `projectId` parameter is optional and represents which project the
            // client will act on behalf of. If not supplied, the client falls back to
            // the default project inferred from the environment.
            $this->con = new FirestoreClient();
        } else {
            /**
             * The JSON Key file is located in Google’s “well known path”
             * on Linux/MacOS: $HOME/.config/gcloud/application_default_credentials.json
             * on Windows: $APPDATA/gcloud/application_default_credentials.json
             */
            $this->con = new FirestoreClient([
                'projectId' => $projectId,
            ]);
        }
    }

    function add_item_to_inventory(InventoryItem $inventoryEvent, $userId)
    {

        // Reference to the root "users" collection
        $usersCollectionRef = $this->con->collection('users');

        // Reference to the "inventory" subcollection
        $inventoryCollectionRef = $usersCollectionRef->document((string) $userId)->collection('inventory');

        try {
            // Add the document with the specified data
            $inventoryData = [
                'name' => $inventoryEvent->getName(),
                'viagogoEventId' => $inventoryEvent->getViagogoEventId(),
                'viagogoCategoryId' => $inventoryEvent->getViagogoCategoryId(),
                'country' => $inventoryEvent->getCountry(),
                'city' => $inventoryEvent->getCity(),
                'location' => $inventoryEvent->getLocation(),
                'section' => $inventoryEvent->getSection(),
                'row' => $inventoryEvent->getRow(),
                'seatFrom' => $inventoryEvent->getSeatFrom(),
                'seatTo' => $inventoryEvent->getSeatTo(),
                'ticketType' => $inventoryEvent->getTicketType(),
                'ticketGenre' => $inventoryEvent->getTicketGenre(),
                'retailer' => $inventoryEvent->getRetailer(),
                'individualTicketCost' => $inventoryEvent->getIndividualTicketCost(),
                'orderNumber' => $inventoryEvent->getOrderNumber(),
                'orderEmail' => $inventoryEvent->getOrderEmail(),
                'status' => $inventoryEvent->getStatus(),
                'yourPricePerTicket' => $inventoryEvent->getYourPricePerTicket(),
                'totalPayout' => $inventoryEvent->getTotalPayout(),
                'quantity' => $inventoryEvent->getQuantity(),
                'quantityRemain' => $inventoryEvent->getQuantityRemain(),
                'platform' => $inventoryEvent->getPlatform(),
                'saleId' => $inventoryEvent->getSaleId(),
                'listingId' => $inventoryEvent->getListingId(),
            ];

            if ($inventoryEvent->getEventDate() !== null) {
                $inventoryData['eventDate'] = new Timestamp($inventoryEvent->getEventDate());
            }

            if ($inventoryEvent->getPurchaseDate() !== null) {
                $inventoryData['purchaseDate'] = new Timestamp($inventoryEvent->getPurchaseDate());
            } else {
                $inventoryData['purchaseDate'] = new Timestamp(new DateTime());
            }

            if ($inventoryEvent->getSaleEndDate() !== null) {
                $inventoryData['saleEndDate'] = new Timestamp($inventoryEvent->getSaleEndDate());
            }

            if ($inventoryEvent->getDateLastModified() !== null) {
                $inventoryData['dateLastModified'] = new Timestamp($inventoryEvent->getDateLastModified());
            }

            if ($inventoryEvent->getSaleDate() !== null) {
                $inventoryData['saleDate'] = new Timestamp($inventoryEvent->getSaleDate());
            }

            $inventoryDocumentRef = $inventoryCollectionRef->add($inventoryData);

            return $inventoryDocumentRef;
        } catch (\Google\Cloud\Core\Exception\NotFoundException $e) {
            if ($usersCollectionRef->documents()->isEmpty()) {
                // Create the "users" collection and "inventory" subcollection
                $usersCollectionRef->document((string) $userId)->set([]);
                $inventoryCollectionRef->add([]);
            } elseif ($inventoryCollectionRef->documents()->isEmpty()) {
                // Create the "inventory" subcollection
                $inventoryCollectionRef->add([]);
            }

            // Now, attempt to add the document again
            try {
                $inventoryDocumentRef = $inventoryCollectionRef->add($inventoryData);
                return $inventoryDocumentRef;
            } catch (\Exception $e) {
                throw $e;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    function edit_inventory_item($id, InventoryItem $inventoryItem, $userId)
    {
        // Reference to the root "users" collection
        $usersCollectionRef = $this->con->collection('users');

        // Reference to the "inventory" subcollection
        $inventoryCollectionRef = $usersCollectionRef->document((string) $userId)->collection('inventory');

        $inventoryDocumentRef = $inventoryCollectionRef->document($id);

        try {
            // Update the Firestore document
            $inventoryDocumentRef->update($inventoryItem->toFirestoreArray());
            $inventoryItem->setId($id);

            return $inventoryItem;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Updates multiple fields on multiple items
     *
     * @param  array  $itemIds The items to be updated
     * @param  array  $attributes The attribute name => attribute value array of attributes to update
     * @param  Integer  $userId The user whose inventory belongs to
     * @throws \Exception if an error occurs during the update
     * @return array array of updated items as map of updated attributes
     */
    function bulk_edit_inventory_items(array $itemIds, array $attributes, $userId)
    {
        // Reference to the root "users" collection
        $usersCollectionRef = $this->con->collection('users');

        // Reference to the "inventory" subcollection
        $inventoryCollectionRef = $usersCollectionRef->document((string) $userId)->collection('inventory');

        try {
            $updates = [];
            // Iterate over the document IDs and update each one
            foreach ($itemIds as $documentId) {
                // Prepare an array for batched updates
                $batchedUpdates = [];
                $docRef = $inventoryCollectionRef->document($documentId);

                // Iterate over the attributes and add them to the batched updates array
                foreach ($attributes as $path => $value) {
                    $batchedUpdates[] = ['path' => $path, 'value' => $value];
                }

                // Perform the batched updates
                $docRef->update($batchedUpdates);
                $updates[$documentId] = $batchedUpdates;
            }

            return $updates;
        } catch (\Exception $e) {
            throw $e;
        }
    }


    function delete_inventory_item($itemId, $userId)
    {
        // Reference to the root "users" collection
        $usersCollectionRef = $this->con->collection('users');

        // Reference to the "inventory" subcollection
        $inventoryCollectionRef = $usersCollectionRef->document((string) $userId)->collection('inventory');

        $inventoryCollectionRef->document((string) $itemId)->delete();
    }

    function get_inventory_item($inventoryEventId, $userId): InventoryItem
    {
        // Reference to the root "users" collection
        $usersCollectionRef = $this->con->collection('users');

        // Reference to the "inventory" subcollection
        $inventoryCollectionRef = $usersCollectionRef->document((string) $userId)->collection('inventory');

        // Reference to the document in the "inventory" subcollection
        $inventoryDocumentRef = $inventoryCollectionRef->document($inventoryEventId);
        $snapshot = $inventoryDocumentRef->snapshot();

        if ($snapshot->exists()) {
            $documentData = $snapshot->data();
            return new InventoryItem(
                $inventoryEventId,
                $documentData["viagogoEventId"],
                $documentData["viagogoCategoryId"],
                $documentData["name"],
                $documentData["eventDate"] ?? null,
                $documentData["purchaseDate"] ?? null,
                $documentData["country"],
                $documentData["city"],
                $documentData["location"],
                $documentData["section"],
                $documentData["row"],
                $documentData["seatFrom"],
                $documentData["seatTo"],
                $documentData["ticketType"],
                $documentData["ticketGenre"],
                $documentData["retailer"],
                $documentData["individualTicketCost"],
                $documentData["orderNumber"],
                $documentData["orderEmail"],
                $documentData["status"],
                $documentData["saleEndDate"] ?? null,
                $documentData["yourPricePerTicket"],
                $documentData["totalPayout"],
                $documentData["quantity"],
                $documentData["quantityRemain"],
                $documentData["dateLastModified"] ?? null,
                $documentData["platform"],
                $documentData["saleDate"] ?? null,
                $documentData["saleId"],
                $documentData["listingId"] ?? null,
                $documentData["restrictions"] ?? null,
                $documentData["ticketDetails"] ?? null,
            );
        } else {
            throw new \Exception(sprintf('Document %s does not exist!', $snapshot->id()));
        }
    }

    /**
     * Return user's inventory
     *
     * @return  InventoryItem[]
     */
    function get_user_inventory($user_id): array
    {
        // Reference to the root "users" collection
        $usersCollectionRef = $this->con->collection('users');

        // Reference to the "inventory" subcollection
        $inventoryCollectionRef = $usersCollectionRef->document((string) $user_id)->collection('inventory');

        try {
            $querySnapshot = $inventoryCollectionRef->documents();
            $inventoryEvents = array();
            foreach ($querySnapshot as $documentSnapshot) {
                $documentData = $documentSnapshot->data();

                $inventoryEvents[] = new InventoryItem(
                    $documentSnapshot->id(),
                    $documentData["viagogoEventId"],
                    $documentData["viagogoCategoryId"],
                    $documentData["name"],
                    $documentData["eventDate"] ?? null,
                    $documentData["purchaseDate"] ?? null,
                    $documentData["country"],
                    $documentData["city"],
                    $documentData["location"],
                    $documentData["section"],
                    $documentData["row"],
                    $documentData["seatFrom"],
                    $documentData["seatTo"],
                    $documentData["ticketType"],
                    $documentData["ticketGenre"],
                    $documentData["retailer"],
                    $documentData["individualTicketCost"],
                    $documentData["orderNumber"],
                    $documentData["orderEmail"],
                    $documentData["status"],
                    $documentData["saleEndDate"] ?? null,
                    $documentData["yourPricePerTicket"],
                    $documentData["totalPayout"],
                    $documentData["quantity"],
                    $documentData["quantityRemain"],
                    $documentData["dateLastModified"] ?? null,
                    $documentData["platform"],
                    $documentData["saleDate"] ?? null,
                    $documentData["saleId"],
                    $documentData["listingId"] ?? null,
                    $documentData["restrictions"] ?? null,
                    $documentData["ticketDetails"] ?? null,
                );
            }
            return $inventoryEvents;
        } catch (\Google\Cloud\Core\Exception\NotFoundException $e) {
            if ($usersCollectionRef->documents()->isEmpty()) {
                // Create the "users" collection and "inventory" subcollection
                $usersCollectionRef->document((string) $user_id)->set([]);
                $inventoryCollectionRef->add([]);
            } elseif ($inventoryCollectionRef->documents()->isEmpty()) {
                // Create the "inventory" subcollection
                $inventoryCollectionRef->add([]);
            }

            return array();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Return user's sales
     *
     * @return  InventoryItem[]
     */
    function get_user_sales($user_id): array
    {
        // Reference to the root "users" collection
        $usersCollectionRef = $this->con->collection('users');

        // Reference to the "inventory" subcollection
        $inventoryCollectionRef = $usersCollectionRef->document((string) $user_id)->collection('inventory');

        try {
            // Create a query to filter documents where "status" is "Soldout"
            $query = $inventoryCollectionRef->where('status', '=', 'Soldout');

            // Get the documents that match the query
            $documents = $query->documents();

            $sales = [];
            // Loop through the documents and process them
            foreach ($documents as $document) {
                $documentData = $document->data();

                $sales[] = new InventoryItem(
                    $document->id(),
                    $documentData["viagogoEventId"],
                    $documentData["viagogoCategoryId"],
                    $documentData["name"],
                    $documentData["eventDate"] ?? null,
                    $documentData["purchaseDate"] ?? null,
                    $documentData["country"],
                    $documentData["city"],
                    $documentData["location"],
                    $documentData["section"],
                    $documentData["row"],
                    $documentData["seatFrom"],
                    $documentData["seatTo"],
                    $documentData["ticketType"],
                    $documentData["ticketGenre"],
                    $documentData["retailer"],
                    $documentData["individualTicketCost"],
                    $documentData["orderNumber"],
                    $documentData["orderEmail"],
                    $documentData["status"],
                    $documentData["saleEndDate"] ?? null,
                    $documentData["yourPricePerTicket"],
                    $documentData["totalPayout"],
                    $documentData["quantity"],
                    $documentData["quantityRemain"],
                    $documentData["dateLastModified"] ?? null,
                    $documentData["platform"],
                    $documentData["saleDate"] ?? null,
                    $documentData["saleId"],
                    $documentData["listingId"] ?? null,
                    $documentData["restrictions"] ?? null,
                    $documentData["ticketDetails"] ?? null,
                );
            }
            return $sales;
        } catch (\Google\Cloud\Core\Exception\NotFoundException $e) {
            if ($usersCollectionRef->documents()->isEmpty()) {
                // Create the "users" collection and "inventory" subcollection
                $usersCollectionRef->document((string) $user_id)->set([]);
                $inventoryCollectionRef->add([]);
            } elseif ($inventoryCollectionRef->documents()->isEmpty()) {
                // Create the "inventory" subcollection
                $inventoryCollectionRef->add([]);
            }

            return array();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    function get_viagogo_analytics($user_id, $currency, $exchangeRates): ViagogoAnalytics
    {
        // Reference to the root "users" collection
        $usersCollectionRef = $this->con->collection('users');

        // Reference to the "inventory" subcollection
        $inventoryCollectionRef = $usersCollectionRef->document((string) $user_id)->collection('inventory');

        $lastSale = null;
        $quantitySold = 0;
        $quantityRemaining = 0;
        $totalSpent = ['amount' => 0, 'currency' => $currency];
        $todaySpent = ['amount' => 0, 'currency' => $currency];
        $netAmount = ['amount' => 0, 'currency' => $currency];
        $todayNetAmount = ['amount' => 0, 'currency' => $currency];

        try {
            $querySnapshot = $inventoryCollectionRef->documents();
            $inventory = [];
            $sales = [];

            // Fill inventory array
            foreach ($querySnapshot as $documentSnapshot) {
                $documentData = $documentSnapshot->data();

                $inventoryItem = new InventoryItem(
                    $documentSnapshot->id(),
                    $documentData["viagogoEventId"],
                    $documentData["viagogoCategoryId"],
                    $documentData["name"],
                    $documentData["eventDate"] ?? null,
                    $documentData["purchaseDate"] ?? null,
                    $documentData["country"],
                    $documentData["city"],
                    $documentData["location"],
                    $documentData["section"],
                    $documentData["row"],
                    $documentData["seatFrom"],
                    $documentData["seatTo"],
                    $documentData["ticketType"],
                    $documentData["ticketGenre"],
                    $documentData["retailer"],
                    $documentData["individualTicketCost"],
                    $documentData["orderNumber"],
                    $documentData["orderEmail"],
                    $documentData["status"],
                    $documentData["saleEndDate"] ?? null,
                    $documentData["yourPricePerTicket"],
                    $documentData["totalPayout"],
                    $documentData["quantity"],
                    $documentData["quantityRemain"],
                    $documentData["dateLastModified"] ?? null,
                    $documentData["platform"],
                    $documentData["saleDate"] ?? null,
                    $documentData["saleId"],
                    $documentData["listingId"] ?? null,
                    $documentData["restrictions"] ?? null,
                    $documentData["ticketDetails"] ?? null,
                );

                // Separate sales & listed/not listed items
                if ($inventoryItem->getStatus() === InventoryItem::ITEM_SOLD) {
                    $sales[] = $inventoryItem;
                } else {
                    $inventory[] = $inventoryItem;
                }
            }

            foreach ($inventory as $inventoryItem) {
                $quantitySold += $inventoryItem->getQuantitySold();
                $quantityRemaining += $inventoryItem->getQuantityRemain();
                $totalSpent["amount"] += $this->utils->convertCurrency($inventoryItem->getTotalCost()["amount"], $exchangeRates, $inventoryItem->getTotalCost()["currency"]);
                if ($inventoryItem->getPurchaseDate() instanceof DateTimeInterface) {
                    $date = new DateTime();
                    if ($date->format('Y-m-d') === $inventoryItem->getPurchaseDate()->format('Y-m-d')) {
                        // purchase date corresponds to current day
                        $todaySpent["amount"] += $this->utils->convertCurrency($inventoryItem->getTotalCost()["amount"], $exchangeRates, $inventoryItem->getTotalCost()["currency"]);
                    }
                }
            }
        } catch (\Google\Cloud\Core\Exception\NotFoundException $e) {
            if ($usersCollectionRef->documents()->isEmpty()) {
                // Create the "users" collection and "inventory" subcollection
                $usersCollectionRef->document((string) $user_id)->set([]);
                $inventoryCollectionRef->add([]);
            } elseif ($inventoryCollectionRef->documents()->isEmpty()) {
                // Create the "inventory" subcollection
                $inventoryCollectionRef->add([]);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        try {
            foreach ($sales as $sale) {
                $totalSpent["amount"] += $this->utils->convertCurrency($sale->getTotalCost()["amount"], $exchangeRates, $sale->getTotalCost()["currency"]);
                $quantitySold += $sale->getQuantity();
                $netAmount["amount"] += $this->utils->convertCurrency($sale->getTotalPayout()["amount"], $exchangeRates, $sale->getTotalPayout()["currency"]);
                if ($sale->getSaleDate() instanceof DateTimeInterface) {
                    $date = new DateTime();
                    if ($date->format('Y-m-d') === $sale->getSaleDate()->format('Y-m-d')) {
                        // purchase date corresponds to current day
                        $todayNetAmount["amount"] += $this->utils->convertCurrency($sale->getTotalPayout()["amount"], $exchangeRates, $sale->getTotalPayout()["currency"]);
                    }
                }
            }
        } catch (\Google\Cloud\Core\Exception\NotFoundException $e) {
            if ($usersCollectionRef->documents()->isEmpty()) {
                // Create the "users" collection and "inventory" subcollection
                $usersCollectionRef->document((string) $user_id)->set([]);
                $inventoryCollectionRef->add([]);
            } elseif ($inventoryCollectionRef->documents()->isEmpty()) {
                // Create the "inventory" subcollection
                $inventoryCollectionRef->add([]);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        $analytics = new ViagogoAnalytics($user_id, $lastSale, $quantitySold, $quantityRemaining, $totalSpent, $todaySpent, $netAmount, $todayNetAmount, $sales);

        return $analytics;
    }

    function get_viagogo_leaderboard_by_total_sold(): array
    {
        $leaderboard = array();

        return $leaderboard;
    }

    function set_inventory_value(float $inventoryValue, $currency, $userId)
    {

        // Reference to the root "users" collection
        $usersCollectionRef = $this->con->collection('users');

        // Reference to the "inventoryValue" subcollection
        $inventoryValueCollectionRef = $usersCollectionRef->document((string) $userId)->collection('inventoryValue');

        try {
            // Add the document with the specified data
            $valueData = [
                'value' => $inventoryValue,
                'currency' => $currency,
                'timestamp' => new Timestamp(new DateTime()),
            ];

            $inventoryDocumentRef = $inventoryValueCollectionRef->add($valueData);

            return $inventoryDocumentRef;
        } catch (\Google\Cloud\Core\Exception\NotFoundException $e) {
            if ($usersCollectionRef->documents()->isEmpty()) {
                // Create the "users" collection and "inventory" subcollection
                $usersCollectionRef->document((string) $userId)->set([]);
                $inventoryValueCollectionRef->add([]);
            } elseif ($inventoryValueCollectionRef->documents()->isEmpty()) {
                // Create the "inventoryValue" subcollection
                $inventoryValueCollectionRef->add([]);
            }

            // Now, attempt to add the document again
            try {
                $inventoryDocumentRef = $inventoryValueCollectionRef->add([
                    'value' => $inventoryValue,
                    'timestamp' => new Timestamp(new DateTime()),
                ]);

                return $inventoryDocumentRef;
            } catch (\Exception $e) {
                throw $e;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    function get_inventory_values($userId): array
    {

        // Reference to the root "users" collection
        $usersCollectionRef = $this->con->collection('users');

        // Reference to the "inventoryValue" subcollection
        $inventoryValueCollectionRef = $usersCollectionRef->document((string) $userId)->collection('inventoryValue');

        try {
            $querySnapshot = $inventoryValueCollectionRef->documents();
            $inventoryValues = array();
            foreach ($querySnapshot as $documentSnapshot) {
                $documentData = $documentSnapshot->data();

                $inventoryValues[] = [new DateTime($documentData['timestamp']), $documentData['value'], $documentData['currency']];
            }
            return $inventoryValues;
        } catch (\Google\Cloud\Core\Exception\NotFoundException $e) {
            if ($usersCollectionRef->documents()->isEmpty()) {
                // Create the "users" collection and "inventory" subcollection
                $usersCollectionRef->document((string) $userId)->set([]);
                $inventoryValueCollectionRef->add([]);
            } elseif ($inventoryValueCollectionRef->documents()->isEmpty()) {
                // Create the "inventory" subcollection
                $inventoryValueCollectionRef->add([]);
            }

            return array();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
