<?php

namespace App\Entity;

use Google\Cloud\Core\Timestamp;
use DateTime;
use DateTimeInterface;
use Exception;

class InventoryItem
{
    final public const ITEM_SOLD = 'Soldout';
    final public const ITEM_LISTED = 'Active';
    final public const ITEM_NOT_LISTED = 'Inactive';

    private $id;
    private $viagogoEventId;
    private $viagogoCategoryId;
    private $name;
    /**
     * @var \DateTimeInterface|DateTime|null
     */
    private $eventDate;
    /**
     * @var \DateTimeInterface|DateTime|null
     */
    private $purchaseDate;
    private $country;
    private $city;
    private $location;
    private $section;
    private $row;
    private $seatFrom;
    private $seatTo;
    private $ticketType;
    private $ticketGenre;
    private $retailer;
    private $individualTicketCost;
    private $orderNumber;
    private $orderEmail;
    private $status;
    /**
     * @var \DateTimeInterface|DateTime|null
     */
    private $saleEndDate;
    private $yourPricePerTicket;
    private $totalPayout;
    private $quantity;
    private $quantityRemain;
    /**
     * @var \DateTimeInterface|DateTime|null
     */
    private $dateLastModified;
    private $platform;
    /**
     * @var \DateTimeInterface|DateTime|null
     */
    private $saleDate;
    private $saleId;
    private $listingId;
    /**
     * @var string[]
     */
    private $restrictions;
    /**
     * @var string[]
     */
    private $ticketDetails;

    function __construct(
        $id,
        $viagogoEventId,
        $viagogoCategoryId,
        $name,
        $eventDate,
        $purchaseDate,
        $country,
        $city,
        $location,
        $section,
        $row,
        $seatFrom,
        $seatTo,
        $ticketType,
        $ticketGenre,
        $retailer,
        $individualTicketCost,
        $orderNumber,
        $orderEmail,
        $status,
        $saleEndDate,
        $yourPricePerTicket,
        $totalPayout,
        $quantity,
        $quantityRemain,
        $dateLastModified,
        $platform,
        $saleDate,
        $saleId,
        $listingId,
        $restrictions,
        $ticketDetails,
    ) {
        $this->setId($id);
        $this->setViagogoEventId($viagogoEventId);
        $this->setViagogoCategoryId($viagogoCategoryId);
        $this->setName($name);
        $this->setEventDate($eventDate);
        $this->setPurchaseDate($purchaseDate);
        $this->setSaleEndDate($saleEndDate);
        $this->setDateLastModified($dateLastModified);
        $this->setSaleDate($saleDate);
        $this->setCountry($country);
        $this->setCity($city);
        $this->setLocation($location);
        $this->setSection($section);
        $this->setRow($row);
        $this->setSeatFrom($seatFrom);
        $this->setSeatTo($seatTo);
        $this->setTicketType($ticketType);
        $this->setTicketGenre($ticketGenre);
        $this->setRetailer($retailer);
        $this->setIndividualTicketCost($individualTicketCost);
        $this->setOrderNumber($orderNumber);
        $this->setOrderEmail($orderEmail);
        $this->setStatus($status);
        $this->setYourPricePerTicket($yourPricePerTicket);
        $this->setTotalPayout($totalPayout);
        $this->setQuantity($quantity);
        $this->setQuantityRemain($quantityRemain);
        $this->setPlatform($platform);
        $this->setSaleId($saleId);
        $this->setListingId($listingId);
        $this->setRestrictions($restrictions);
        $this->setTicketDetails($ticketDetails);
    }

    public static function fromDataArray(User $user, array $inventoryItem): InventoryItem
    {
        $name = $inventoryItem['eventName'] ?? '';
        $eventDate = isset($inventoryItem['eventDate']) ? new \DateTime($inventoryItem['eventDate']) : '';
        $purchaseDate = isset($inventoryItem['purchaseDate']) ? new \DateTime($inventoryItem['purchaseDate']) : '';
        $country = $inventoryItem['country'] ?? '';
        $city = $inventoryItem['city'] ?? '';
        $location = $inventoryItem['location'] ?? '';
        $section = $inventoryItem['customSection'] && $inventoryItem['customSection'] != "" ? $inventoryItem['customSection'] : ($inventoryItem['section'] ?? '');
        $row = $inventoryItem['row'] ?? '';
        $seatFrom = $inventoryItem['seatFrom'] ?? null;
        $seatTo = $inventoryItem['seatTo'] ?? null;
        if ($inventoryItem['quantity'] === null || $inventoryItem['quantity'] <= 0) {
            try {
                $seatFromInt = intval($seatFrom);
                $seatToInt = intval($seatTo);
                $quantity = $seatToInt - $seatFromInt + 1;
            } catch (\Exception $e) {
                $quantity = 1;
            }
        } else {
            $quantity = $inventoryItem['quantity'];
        }
        $quantityRemaining = $quantity;
        $ticketGenre = $inventoryItem['ticketGenre'] ?? '';
        $ticketType = $inventoryItem['ticketType'] ?? '';
        $retailer = $inventoryItem['retailer'] ?? '';
        $currency = $inventoryItem['currency'] ?? $user->getCurrency();
        $individualTicketCost = isset($inventoryItem['ticketCost']) ? ['amount' => floatval($inventoryItem['ticketCost']), 'currency' => $currency] : null;
        $orderNumber = $inventoryItem['orderNumber'] ?? '';
        $orderEmail = $inventoryItem['orderEmail'] ?? '';
        $viagogoEventId = $inventoryItem['eventId'] ?? null;
        $viagogoCategoryId = $inventoryItem['categoryId'] ?? null;
        $saleDate = isset($inventoryItem['saleDate']) ? new \DateTime($inventoryItem['saleDate']) : '';
        $platform = $inventoryItem['platform'] ?? '';

        return new InventoryItem(
            null,
            $viagogoEventId,
            $viagogoCategoryId,
            $name,
            $eventDate,
            $purchaseDate,
            $country,
            $city,
            $location,
            $section,
            $row,
            $seatFrom,
            $seatTo,
            $ticketType,
            $ticketGenre,
            $retailer,
            $individualTicketCost,
            $orderNumber,
            $orderEmail,
            InventoryItem::ITEM_NOT_LISTED,
            null,
            null,
            null,
            $quantity,
            $quantityRemaining,
            null,
            $platform,
            $saleDate,
            null,
            null,
            null,
            null,
        );
    }

    public function setId($id)
    {
        $this->id = isset($id) ? htmlspecialchars($id, ENT_QUOTES, 'UTF-8') : null;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setName($name)
    {
        $this->name = isset($name) ? htmlspecialchars($name, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of eventDate
     */
    public function getEventDate()
    {
        return $this->eventDate;
    }

    /**
     * Set the value of eventDate
     *
     * @return  self
     */
    public function setEventDate($eventDate)
    {
        if (isset($eventDate)) {
            // Check if $eventDate is an instance of Google\Cloud\Core\Timestamp
            if ($eventDate instanceof Timestamp) {
                // Convert Google\Cloud\Core\Timestamp to DateTimeInterface
                $this->eventDate = $eventDate->get();
            } elseif (is_string($eventDate)) {
                // Parse the string to create a DateTime object
                try {
                    $this->eventDate = new DateTime($eventDate);
                } catch (Exception $e) {
                    return $this;
                }
            } elseif ($eventDate instanceof DateTime) {
                $this->eventDate = $eventDate;
            }
        }

        return $this;
    }

    /**
     * Get the value of purchaseDate
     */
    public function getPurchaseDate()
    {
        return $this->purchaseDate;
    }

    /**
     * Set the value of purchaseDate
     *
     * @return  self
     */
    public function setPurchaseDate($purchaseDate)
    {
        if (isset($purchaseDate)) {
            // Check if $eventDate is an instance of Google\Cloud\Core\Timestamp
            if ($purchaseDate instanceof Timestamp) {
                // Convert Google\Cloud\Core\Timestamp to DateTimeInterface
                $this->purchaseDate = $purchaseDate->get();
            } elseif (is_string($purchaseDate)) {
                // Parse the string to create a DateTime object
                try {
                    $this->purchaseDate = new DateTime($purchaseDate);
                } catch (Exception $e) {
                    return $this;
                }
            } elseif ($purchaseDate instanceof DateTime) {
                $this->purchaseDate = $purchaseDate;
            }
        }

        return $this;
    }

    /**
     * Get the value of location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set the value of location
     *
     * @return  self
     */
    public function setLocation($location)
    {
        $this->location = isset($location) ? htmlspecialchars($location, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of section
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * Set the value of section
     *
     * @return  self
     */
    public function setSection($section)
    {
        $this->section = isset($section) ? htmlspecialchars($section, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of row
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * Set the value of row
     *
     * @return  self
     */
    public function setRow($row)
    {
        $this->row = isset($row) ? htmlspecialchars($row, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }


    /**
     * Get the value of ticketType
     */
    public function getTicketType()
    {
        return $this->ticketType;
    }

    /**
     * Get the description of ticketType 
     */
    public function getTicketTypeAsString()
    {
        switch ((string) $this->ticketType) {
            case '0':
                return 'Paper';
            case '1':
                return 'E-Ticket';
            case '9':
                return 'AXS';
            case '10':
                return 'Ticketmaster Mobile Ticket';
            case '11':
                return 'Mobile Tickets';
            case '13':
                return 'Mobile QR Code';

            default:
                return '';
        }
    }

    /**
     * Set the value of ticketType
     *
     * @return  self
     */
    public function setTicketType($ticketType)
    {
        $this->ticketType = isset($ticketType) ? htmlspecialchars($ticketType, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of retailer
     */
    public function getRetailer()
    {
        return $this->retailer;
    }

    /**
     * Set the value of retailer
     *
     * @return  self
     */
    public function setRetailer($retailer)
    {
        $this->retailer = isset($retailer) ? htmlspecialchars($retailer, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of orderNumber
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * Set the value of orderNumber
     *
     * @return  self
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = isset($orderNumber) ? htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of orderEmail
     */
    public function getOrderEmail()
    {
        return $this->orderEmail;
    }

    /**
     * Set the value of orderEmail
     *
     * @return  self
     */
    public function setOrderEmail($orderEmail)
    {
        $this->orderEmail = isset($orderEmail) ? htmlspecialchars($orderEmail, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the value of status
     *
     * @return  self
     */
    public function setStatus($status)
    {
        $this->status = isset($status) ? htmlspecialchars($status, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of ticketGenre
     */
    public function getTicketGenre()
    {
        return $this->ticketGenre;
    }

    /**
     * Set the value of ticketGenre
     *
     * @return  self
     */
    public function setTicketGenre($ticketGenre)
    {
        $this->ticketGenre = isset($ticketGenre) ? htmlspecialchars($ticketGenre, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set the value of country
     *
     * @return  self
     */
    public function setCountry($country)
    {
        $this->country = isset($country) ? htmlspecialchars($country, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of city
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set the value of city
     *
     * @return  self
     */
    public function setCity($city)
    {
        $this->city = isset($city) ? htmlspecialchars($city, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of viagogoEventId
     */
    public function getViagogoEventId()
    {
        return $this->viagogoEventId;
    }

    /**
     * Set the value of viagogoEventId
     *
     * @return  self
     */
    public function setViagogoEventId($viagogoEventId)
    {
        $this->viagogoEventId = isset($viagogoEventId) ? htmlspecialchars($viagogoEventId, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of saleEndDate
     *
     * @return  \DateTimeInterface|DateTime|null
     */
    public function getSaleEndDate()
    {
        return $this->saleEndDate;
    }

    /**
     * Set the value of saleEndDate
     *
     * @param  \DateTimeInterface|DateTime|null  $saleEndDate
     *
     * @return  self
     */
    public function setSaleEndDate($saleEndDate)
    {
        if (isset($saleEndDate)) {
            // Check if $saleEndDate is an instance of Google\Cloud\Core\Timestamp
            if ($saleEndDate instanceof Timestamp) {
                // Convert Google\Cloud\Core\Timestamp to DateTimeInterface
                $this->saleEndDate = $saleEndDate->get();
            } elseif (is_string($saleEndDate)) {
                // Parse the string to create a DateTime object
                try {
                    $this->saleEndDate = new DateTime($saleEndDate);
                } catch (Exception $e) {
                    return $this;
                }
            } elseif ($saleEndDate instanceof DateTime) {
                $this->saleEndDate = $saleEndDate;
            }
        }

        return $this;
    }

    /**
     * Get the value of yourPricePerTicket
     */
    public function getYourPricePerTicket()
    {
        return isset($this->yourPricePerTicket) && is_array($this->yourPricePerTicket) ? $this->yourPricePerTicket : ["amount" => 0, "currency" => "EUR"];
    }

    /**
     * Set the value of yourPricePerTicket
     *
     * @return  self
     */
    public function setYourPricePerTicket($yourPricePerTicket)
    {
        // Check if $ticketDetails is set and is an array.
        if (isset($yourPricePerTicket) && is_array($yourPricePerTicket)) {
            // Set $this->ticketDetails to $ticketDetails.
            $this->yourPricePerTicket = $yourPricePerTicket;
        } else {
            // If $ticketDetails is not set or is not an array, check if $this->ticketDetails is set.
            if (isset($this->yourPricePerTicket)) {
                // Set $this->ticketDetails to its current value.
                $this->yourPricePerTicket = $this->yourPricePerTicket;
            } else {
                // Set $this->ticketDetails to an empty array.
                $this->yourPricePerTicket = ["amount" => 0, "currency" => "EUR"];
            }
        }

        return $this;
    }

    /**
     * Get the value of totalPayout
     */
    public function getTotalPayout()
    {
        if ($this->getStatus() !== InventoryItem::ITEM_SOLD) {
            // item not sold yet, calculate expected payout
            switch ($this->getPlatform()) {
                case 'Viagogo':
                    // - 10% viagogo fees
                    $payoutPerTicket = $this->getYourPricePerTicket()['amount'] - ($this->getYourPricePerTicket()['amount'] * 0.1);
                    $expectedTotalPayout = $payoutPerTicket * $this->getQuantity();
                    return ["amount" => $expectedTotalPayout, "currency" => $this->getYourPricePerTicket()['currency']];

                default:
                    $payoutPerTicket = $this->getYourPricePerTicket()['amount'];
                    $expectedTotalPayout = $payoutPerTicket * $this->getQuantity();
                    return ["amount" => $expectedTotalPayout, "currency" => $this->getYourPricePerTicket()['currency']];
            }
        }
        return isset($this->totalPayout) && is_array($this->totalPayout) ? $this->totalPayout : ["amount" => 0, "currency" => "EUR"];
    }

    /**
     * Set the value of totalPayout
     *
     * @return  self
     */
    public function setTotalPayout($totalPayout)
    {
        // Check if $ticketDetails is set and is an array.
        if (isset($totalPayout) && is_array($totalPayout)) {
            // Set $this->ticketDetails to $ticketDetails.
            $this->totalPayout = $totalPayout;
        } else {
            // If $ticketDetails is not set or is not an array, check if $this->ticketDetails is set.
            if (isset($this->totalPayout)) {
                // Set $this->ticketDetails to its current value.
                $this->totalPayout = $this->totalPayout;
            } else {
                // Set $this->ticketDetails to an empty array.
                $this->totalPayout = ["amount" => 0, "currency" => "EUR"];
            }
        }

        return $this;
    }

    /**
     * Get the value of quantity
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set the value of quantity
     *
     * @return  self
     */
    public function setQuantity($quantity)
    {
        $this->quantity = isset($quantity) ? htmlspecialchars($quantity, ENT_QUOTES, 'UTF-8') : $this->quantity;

        return $this->quantity;
    }

    /**
     * Get the value of quantityRemain
     */
    public function getQuantityRemain()
    {
        return $this->quantityRemain;
    }

    /**
     * Set the value of quantityRemain
     *
     * @return  self
     */
    public function setQuantityRemain($quantityRemain)
    {
        $this->quantityRemain = isset($quantityRemain) ? htmlspecialchars($quantityRemain, ENT_QUOTES, 'UTF-8') : $this->quantity;

        return $this;
    }

    /**
     * Get the Viagogo Event Page url
     */
    public function getEventPageUrl()
    {
        return (null === $this->getViagogoEventId() || "" == $this->getViagogoEventId()) ? null : "https://www.viagogo.com/ww/E-{$this->getViagogoEventId()}";
    }

    /**
     * Get the value of individualTicketCost
     */
    public function getIndividualTicketCost()
    {
        return isset($this->individualTicketCost) && is_array($this->individualTicketCost) ? $this->individualTicketCost : ["amount" => 0, "currency" => "EUR"];
    }

    /**
     * Set the value of individualTicketCost
     *
     * @return  self
     */
    public function setIndividualTicketCost($individualTicketCost)
    {
        // Check if $ticketDetails is set and is an array.
        if (isset($individualTicketCost) && is_array($individualTicketCost)) {
            // Set $this->ticketDetails to $ticketDetails.
            $this->individualTicketCost = $individualTicketCost;
        } else {
            // If $ticketDetails is not set or is not an array, check if $this->ticketDetails is set.
            if (isset($this->individualTicketCost)) {
                // Set $this->ticketDetails to its current value.
                $this->individualTicketCost = $this->individualTicketCost;
            } else {
                // Set $this->ticketDetails to an empty array.
                $this->individualTicketCost = ["amount" => 0, "currency" => "EUR"];
            }
        }

        return $this;
    }

    public function getQuantitySold()
    {
        return $this->getQuantity() - $this->getQuantityRemain();
    }


    public function getTotalCost()
    {
        return ['amount' => $this->getQuantity() * $this->getIndividualTicketCost()["amount"], 'currency' => $this->getIndividualTicketCost()["currency"]];
    }

    /**
     * Get the value of dateLastModified
     *
     * @return  \DateTimeInterface|DateTime|null
     */
    public function getDateLastModified()
    {
        return $this->dateLastModified;
    }

    /**
     * Set the value of dateLastModified
     *
     * @param  \DateTimeInterface|DateTime|null  $dateLastModified
     *
     * @return  self
     */
    public function setDateLastModified($dateLastModified)
    {
        if (isset($dateLastModified)) {
            // Check if $dateLastModified is an instance of Google\Cloud\Core\Timestamp
            if ($dateLastModified instanceof Timestamp) {
                // Convert Google\Cloud\Core\Timestamp to DateTimeInterface
                $this->dateLastModified = $dateLastModified->get();
            } elseif (is_string($dateLastModified)) {
                // Parse the string to create a DateTime object
                try {
                    $this->dateLastModified = new DateTime($dateLastModified);
                } catch (Exception $e) {
                    return $this;
                }
            } elseif ($dateLastModified instanceof DateTime) {
                $this->dateLastModified = $dateLastModified;
            }
        }

        return $this;
    }

    /**
     * Get the value of viagogoCategoryId
     */
    public function getViagogoCategoryId()
    {
        return $this->viagogoCategoryId;
    }

    /**
     * Set the value of viagogoCategoryId
     *
     * @return  self
     */
    public function setViagogoCategoryId($viagogoCategoryId)
    {
        $this->viagogoCategoryId = isset($viagogoCategoryId) ? htmlspecialchars($viagogoCategoryId, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of platform
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Set the value of platform
     *
     * @return  self
     */
    public function setPlatform($platform)
    {
        $this->platform = isset($platform) ? htmlspecialchars($platform, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of saleDate
     *
     * @return  \DateTimeInterface|DateTime|null
     */
    public function getSaleDate()
    {
        return $this->saleDate;
    }

    /**
     * Set the value of saleDate
     *
     * @param  \DateTimeInterface|DateTime|null  $saleDate
     *
     * @return  self
     */
    public function setSaleDate($saleDate)
    {
        if (isset($saleDate)) {
            // Check if $dateLastModified is an instance of Google\Cloud\Core\Timestamp
            if ($saleDate instanceof Timestamp) {
                // Convert Google\Cloud\Core\Timestamp to DateTimeInterface
                $this->saleDate = $saleDate->get();
            } elseif (is_string($saleDate)) {
                // Parse the string to create a DateTime object
                try {
                    $this->saleDate = new DateTime($saleDate);
                } catch (Exception $e) {
                    return $this;
                }
            } elseif ($saleDate instanceof DateTime) {
                $this->saleDate = $saleDate;
            }
        }

        return $this;
    }

    /**
     * Get the value of saleId
     */
    public function getSaleId()
    {
        return $this->saleId;
    }

    /**
     * Set the value of saleId
     *
     * @return  self
     */
    public function setSaleId($saleId)
    {
        $this->saleId = isset($saleId) ? htmlspecialchars($saleId, ENT_QUOTES, 'UTF-8') : $this->saleId;

        return $this;
    }

    /**
     * Merges Firestore-like data into the targeted InventoryItem, checking for conflicts and special data types
     */
    public function mergeData($data)
    {
        // Check if $this has a Viagogo Event ID
        if ($this->getViagogoEventId() === null && isset($data) && isset($data["viagogoEventId"])) {
            // Use $data["viagogoEventId"] if it exists in $data
            $this->setViagogoEventId($data["viagogoEventId"]);
        }

        if ($this->getViagogoCategoryId() === null && isset($data) && isset($data["viagogoCategoryId"])) {
            $this->setViagogoCategoryId($data["viagogoCategoryId"]);
        }

        if ($this->getSaleEndDate() === null && isset($data) && isset($data["saleEndDate"])) {
            $this->setSaleEndDate($data["saleEndDate"]);
        }

        if ($this->getYourPricePerTicket()["amount"] == 0 && isset($data) && isset($data["yourPricePerTicket"])) {
            $this->setYourPricePerTicket($data["yourPricePerTicket"]);
        }

        if ($this->getTotalPayout()["amount"] == 0 && isset($data) && isset($data["totalPayout"])) {
            $this->setTotalPayout($data["totalPayout"]);
        }

        if ($this->getStatus() === null && isset($data) && isset($data["status"])) {
            $this->setStatus($data["status"]);
        }

        if ($this->getDateLastModified() === null && isset($data) && isset($data["dateLastModified"])) {
            $this->setDateLastModified($data["dateLastModified"]);
        }

        if ($this->getSaleDate() === null && isset($data) && isset($data["saleDate"])) {
            $this->setSaleDate($data["saleDate"]);
        }

        // If new quantity is greater than stored quantity
        if ($data["quantity"] < $this->getQuantity()) {
            // We have to increase remaining quantity
            $this->setQuantityRemain($data["quantityRemain"] + $this->getQuantity() - $data["quantity"]);
        } else if ($data["quantity"] > $this->getQuantity()) {
            // Otherwise, we have to lower remaining quantity to match new total quantity
            $this->setQuantityRemain($this->getQuantity());
        }
    }

    /**
     * Returns data ready to be used in Firestore operations
     */
    public function toFirestoreArray()
    {
        $firestoreData = [
            ['path' => 'viagogoEventId', 'value' => $this->getViagogoEventId()],
            ['path' => 'viagogoCategoryId', 'value' => $this->getViagogoCategoryId()],
            ['path' => 'name', 'value' => $this->getName()],
            ['path' => 'country', 'value' => $this->getCountry()],
            ['path' => 'city', 'value' => $this->getCity()],
            ['path' => 'location', 'value' => $this->getLocation()],
            ['path' => 'section', 'value' => $this->getSection()],
            ['path' => 'row', 'value' => $this->getRow()],
            ['path' => 'seatFrom', 'value' => $this->getSeatFrom()],
            ['path' => 'seatTo', 'value' => $this->getSeatTo()],
            ['path' => 'ticketType', 'value' => $this->getTicketType()],
            ['path' => 'ticketGenre', 'value' => $this->getTicketGenre()],
            ['path' => 'retailer', 'value' => $this->getRetailer()],
            ['path' => 'individualTicketCost', 'value' => $this->getIndividualTicketCost()],
            ['path' => 'orderNumber', 'value' => $this->getOrderNumber()],
            ['path' => 'orderEmail', 'value' => $this->getOrderEmail()],
            ['path' => 'status', 'value' => $this->getStatus()],
            ['path' => 'yourPricePerTicket', 'value' => $this->getYourPricePerTicket()],
            ['path' => 'totalPayout', 'value' => $this->getTotalPayout()],
            ['path' => 'quantity', 'value' => $this->getQuantity()],
            ['path' => 'quantityRemain', 'value' => $this->getQuantityRemain()],
            ['path' => 'platform', 'value' => $this->getPlatform()],
            ['path' => 'saleId', 'value' => $this->getSaleId()],
            ['path' => 'listingId', 'value' => $this->getListingId()],
        ];

        if ($this->getSaleDate() !== null) {
            $firestoreData[] = ['path' => 'saleDate', 'value' => new Timestamp($this->getSaleDate())];
        }

        if ($this->getEventDate() !== null) {
            $firestoreData[] = ['path' => 'eventDate', 'value' => new Timestamp($this->getEventDate())];
        }

        if ($this->getPurchaseDate() !== null) {
            $firestoreData[] = ['path' => 'purchaseDate', 'value' => new Timestamp($this->getPurchaseDate())];
        }

        if ($this->getSaleEndDate() !== null) {
            $firestoreData[] = ['path' => 'saleEndDate', 'value' => new Timestamp($this->getSaleEndDate())];
        }

        if ($this->getDateLastModified() !== null) {
            $firestoreData[] = ['path' => 'dateLastModified', 'value' => new Timestamp($this->getDateLastModified())];
        }

        return $firestoreData;
    }

    /**
     * Returns data ready to be used in Firestore operations
     */
    public function toArray()
    {
        $inventoryData = [
            'id' =>  $this->getId(),
            'viagogoEventId' =>  $this->getViagogoEventId(),
            'viagogoCategoryId' =>  $this->getViagogoCategoryId(),
            'name' =>  $this->getName(),
            'country' =>  $this->getCountry(),
            'city' =>  $this->getCity(),
            'location' =>  $this->getLocation(),
            'section' =>  $this->getSection(),
            'row' =>  $this->getRow(),
            'seatFrom' =>  $this->getSeatFrom(),
            'seatTo' =>  $this->getSeatTo(),
            'ticketType' =>  $this->getTicketType(),
            'ticketGenre' =>  $this->getTicketGenre(),
            'retailer' =>  $this->getRetailer(),
            'individualTicketCostAmount' =>  $this->getIndividualTicketCost()['amount'],
            'individualTicketCostCurrency' =>  $this->getIndividualTicketCost()['currency'],
            'orderNumber' =>  $this->getOrderNumber(),
            'orderEmail' =>  $this->getOrderEmail(),
            'status' =>  $this->getStatus(),
            'yourPricePerTicketAmount' =>  $this->getYourPricePerTicket()['amount'],
            'yourPricePerTicketCurrency' =>  $this->getYourPricePerTicket()['currency'],
            'totalPayoutAmount' =>  $this->getTotalPayout()['amount'],
            'totalPayoutCurrency' =>  $this->getTotalPayout()['currency'],
            'quantity' =>  $this->getQuantity(),
            'quantityRemain' =>  $this->getQuantityRemain(),
            'platform' =>  $this->getPlatform(),
            'saleId' =>  $this->getSaleId(),
            'listingId' =>  $this->getListingId(),
            'listingRestrictions' =>  implode(',', $this->getRestrictions()),
            'listingTicketDetails' =>  implode(',', $this->getTicketDetails()),
        ];

        if ($this->getSaleDate() !== null) {
            $inventoryData['saleDate'] = new Timestamp($this->getSaleDate());
        } else {
            $inventoryData['saleDate'] = null;
        }

        if ($this->getEventDate() !== null) {
            $inventoryData['eventDate'] = new Timestamp($this->getEventDate());
        } else {
            $inventoryData['eventDate'] = null;
        }

        if ($this->getPurchaseDate() !== null) {
            $inventoryData['purchaseDate'] = new Timestamp($this->getPurchaseDate());
        } else {
            $inventoryData['purchaseDate'] = null;
        }

        if ($this->getSaleEndDate() !== null) {
            $inventoryData['saleEndDate'] = new Timestamp($this->getSaleEndDate());
        } else {
            $inventoryData['saleEndDate'] = null;
        }

        if ($this->getDateLastModified() !== null) {
            $inventoryData['dateLastModified'] = new Timestamp($this->getDateLastModified());
        } else {
            $inventoryData['dateLastModified'] = null;
        }

        return $inventoryData;
    }

    /**
     * Get the value of listingId
     */
    public function getListingId()
    {
        return $this->listingId;
    }

    /**
     * Set the value of listingId
     *
     * @return  self
     */
    public function setListingId($listingId)
    {
        $this->listingId = isset($listingId) ? htmlspecialchars($listingId, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of restrictions
     *
     * @return  string[]
     */
    public function getRestrictions()
    {
        return $this->restrictions;
    }

    /**
     * Set the value of restrictions
     *
     * @param  string[]  $restrictions
     *
     * @return  self
     */
    public function setRestrictions($restrictions)
    {
        // Check if $ticketDetails is set and is an array.
        if (isset($restrictions) && is_array($restrictions)) {
            // Set $this->ticketDetails to $ticketDetails.
            $this->restrictions = $restrictions;
        } else {
            // If $ticketDetails is not set or is not an array, check if $this->ticketDetails is set.
            if (isset($this->restrictions)) {
                // Set $this->ticketDetails to its current value.
                $this->restrictions = $this->restrictions;
            } else {
                // Set $this->ticketDetails to an empty array.
                $this->restrictions = [];
            }
        }

        return $this;
    }

    /**
     * Get the value of ticketDetails
     *
     * @return  string[]
     */
    public function getTicketDetails()
    {
        return $this->ticketDetails;
    }

    /**
     * Set the value of ticketDetails
     *
     * @param  string[]  $ticketDetails
     *
     * @return  self
     */
    public function setTicketDetails($ticketDetails)
    {
        // Check if $ticketDetails is set and is an array.
        if (isset($ticketDetails) && is_array($ticketDetails)) {
            // Set $this->ticketDetails to $ticketDetails.
            $this->ticketDetails = $ticketDetails;
        } else {
            // If $ticketDetails is not set or is not an array, check if $this->ticketDetails is set.
            if (isset($this->ticketDetails)) {
                // Set $this->ticketDetails to its current value.
                $this->ticketDetails = $this->ticketDetails;
            } else {
                // Set $this->ticketDetails to an empty array.
                $this->ticketDetails = [];
            }
        }

        return $this;
    }

    /**
     * Get the value of seatFrom
     */
    public function getSeatFrom()
    {
        return $this->seatFrom;
    }

    /**
     * Set the value of seatFrom
     *
     * @return  self
     */
    public function setSeatFrom($seatFrom)
    {
        $this->seatFrom = isset($seatFrom) ? htmlspecialchars($seatFrom, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of seatTo
     */
    public function getSeatTo()
    {
        return $this->seatTo;
    }

    /**
     * Set the value of seatTo
     *
     * @return  self
     */
    public function setSeatTo($seatTo)
    {
        $this->seatTo = isset($seatTo) ? htmlspecialchars($seatTo, ENT_QUOTES, 'UTF-8') : null;

        return $this;
    }
}
