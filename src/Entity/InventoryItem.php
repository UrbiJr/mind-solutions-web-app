<?php

namespace App\Entity;

use Google\Cloud\Core\Timestamp;
use DateTime;
use DateTimeInterface;
use Exception;

use App\Repository\InventoryItemRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Service\Utils;

#[ORM\Table(name: 'InventoryItems')]
#[ORM\Index(name: 'fk_user_id', columns: ['user_id'])]
#[ORM\Entity(repositoryClass: InventoryItemRepository::class)]
class InventoryItem
{
    final public const ITEM_SOLD = 'Soldout';
    final public const ITEM_LISTED = 'Active';
    final public const ITEM_NOT_LISTED = 'Inactive';

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @var \User
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[ORM\ManyToOne(targetEntity: 'User')]
    private $user;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'viagogo_event_id', type: 'string', length: 256, nullable: true)]
    private $viagogoEventId;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'viagogo_category_id', type: 'string', length: 256, nullable: true)]
    private $viagogoCategoryId;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'name', type: 'string', length: 256, nullable: true)]
    private $name;

    /**
     * @var \DateTimeInterface|DateTime|null
     */
    #[ORM\Column(name: 'event_date', type: 'datetime', nullable: true)]
    private $eventDate;

    /**
     * @var \DateTimeInterface|DateTime|null
     */
    #[ORM\Column(name: 'purchase_date', type: 'datetime', nullable: true)]
    private $purchaseDate;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'country', type: 'string', length: 256, nullable: true)]
    private $country;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'city', type: 'string', length: 256, nullable: true)]
    private $city;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'location', type: 'string', length: 256, nullable: true)]
    private $location;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'section', type: 'string', length: 256, nullable: true)]
    private $section;

    /**
     * @var string|null
     */
    #[ORM\Column(name: '`row`', type: 'string', length: 256, nullable: true)]
    private $row;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'seat_from', type: 'string', length: 10, nullable: true)]
    private $seatFrom;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'seat_to', type: 'string', length: 10, nullable: true)]
    private $seatTo;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'floor_seats', type: 'integer', nullable: true)]
    private $floorSeats;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'ticket_type', type: 'string', length: 256, nullable: true)]
    private $ticketType;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'ticket_genre', type: 'string', length: 256, nullable: true)]
    private $ticketGenre;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'retailer', type: 'string', length: 256, nullable: true)]
    private $retailer;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'individual_ticket_cost', type: 'json', nullable: true)]
    private $individualTicketCost;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'order_number', type: 'string', length: 256, nullable: true)]
    private $orderNumber;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'order_email', type: 'string', length: 256, nullable: true)]
    private $orderEmail;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'status', type: 'string', length: 256, nullable: true)]
    private $status;

    /**
     * @var \DateTimeInterface|DateTime|null
     */
    #[ORM\Column(name: 'sale_end_date', type: 'datetime', nullable: true)]
    private $saleEndDate;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'your_price_per_ticket', type: 'json', nullable: true)]
    private $yourPricePerTicket;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'total_payout', type: 'json', nullable: true)]
    private $totalPayout;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'quantity_remain', type: 'integer', nullable: true)]
    private $quantityRemain;

    /**
     * @var \DateTimeInterface|DateTime|null
     */
    #[ORM\Column(name: 'date_last_modified', type: 'datetime', nullable: true)]
    private $dateLastModified;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'platform', type: 'string', length: 256, nullable: true)]
    private $platform;

    /**
     * @var \DateTimeInterface|DateTime|null
     */
    #[ORM\Column(name: 'sale_date', type: 'datetime', nullable: true)]
    private $saleDate;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'sale_id', type: 'string', length: 256, nullable: true)]
    private $saleId;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'listing_id', type: 'string', length: 256, nullable: true)]
    private $listingId;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'restrictions', type: 'json', nullable: true)]
    private $restrictions;

    /**
     * @var array|null
     */
    #[ORM\Column(name: 'ticket_details', type: 'json', nullable: true)]
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
        $floorSeats,
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
        $this->setFloorSeats($floorSeats);
        $this->setTicketType($ticketType);
        $this->setTicketGenre($ticketGenre);
        $this->setRetailer($retailer);
        $this->setIndividualTicketCost($individualTicketCost);
        $this->setOrderNumber($orderNumber);
        $this->setOrderEmail($orderEmail);
        $this->setStatus($status);
        $this->setYourPricePerTicket($yourPricePerTicket);
        $this->setTotalPayout($totalPayout);
        $this->setQuantityRemain($quantityRemain);
        $this->setPlatform($platform);
        $this->setSaleId($saleId);
        $this->setListingId($listingId);
        $this->setRestrictions($restrictions);
        $this->setTicketDetails($ticketDetails);
    }

    public static function fromAssociativeArray(array $inventoryItem, User $user): InventoryItem
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
        $floorSeats = $inventoryItem['floorSeats'] ?? 0;

        $ticketGenre = $inventoryItem['ticketGenre'] ?? '';
        $ticketType = $inventoryItem['ticketType'] ?? '';
        $retailer = $inventoryItem['retailer'] ?? '';
        $currency = $inventoryItem['individualTicketCostCurrency'] ?? $user->getCurrency();
        $individualTicketCost = isset($inventoryItem['individualTicketCost']) ? ['amount' => floatval($inventoryItem['individualTicketCost']), 'currency' => $currency] : null;
        $orderNumber = $inventoryItem['orderNumber'] ?? '';
        $orderEmail = $inventoryItem['orderEmail'] ?? '';
        $viagogoEventId = $inventoryItem['eventId'] ?? null;
        $viagogoCategoryId = $inventoryItem['categoryId'] ?? null;
        $saleDate = isset($inventoryItem['saleDate']) ? new \DateTime($inventoryItem['saleDate']) : '';
        $platform = $inventoryItem['platform'] ?? '';
        $status = $inventoryItem['status'] ?? InventoryItem::ITEM_NOT_LISTED;

        $i = new InventoryItem(
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
            $floorSeats,
            $ticketType,
            $ticketGenre,
            $retailer,
            $individualTicketCost,
            $orderNumber,
            $orderEmail,
            $status,
            null,
            null,
            null,
            0,
            null,
            $platform,
            $saleDate,
            null,
            null,
            null,
            null,
        );

        $i->setQuantityRemain($i->getPurchasedQuantity());

        return $i;
    }

    public static function fromArray(array $data, User $user): InventoryItem
    {
        $itemId = $data[0];
        $viagogoEventId = $data[1];
        $viagogoCategoryId = $data[2];
        $name = $data[3];
        $country = $data[4];
        $city = $data[5];
        $location = $data[6];
        $section = $data[7];
        $row = $data[8];
        $seatFrom = $data[9];
        $seatTo = $data[10];
        $floorSeats = $data[11];
        $ticketType = $data[12];
        $ticketGenre = $data[13];
        $retailer = $data[14];
        $individualTicketCostAmount = $data[15];
        $individualTicketCostCurrency = $data[16];
        $orderNumber = $data[17];
        $orderEmail = $data[18];
        $status = $data[19];
        $yourPricePerTicketAmount = $data[20];
        $yourPricePerTicketCurrency = $data[21];
        $totalPayoutAmount = $data[22];
        $totalPayoutCurrency = $data[23];
        $quantityRemain = $data[24];
        $platform = $data[25];
        $saleId = $data[26];
        $listingId = $data[27];
        $listingRestrictions = $data[28] != "" ? explode(',', $data[28]) : null;
        $listingTicketDetails = $data[29] != "" ? explode(',', $data[29]) : null;
        $saleDate = $data[30] && $data[30] != "" ? Utils::unixTimestampToDateTime((int) $data[30]) : null;
        $eventDate = $data[31] && $data[31] != "" ? Utils::unixTimestampToDateTime((int) $data[31]) : null;
        $purchaseDate = $data[32] && $data[32] != "" ? Utils::unixTimestampToDateTime((int) $data[32]) : null;
        $saleEndDate = $data[33] && $data[33] != "" ? Utils::unixTimestampToDateTime((int) $data[33]) : null;
        $dateLastModified = $data[34] && $data[34] != "" ? Utils::unixTimestampToDateTime((int) $data[34]) : null;

        $i = new InventoryItem(
            $itemId,
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
            $floorSeats,
            $ticketType,
            $ticketGenre,
            $retailer,
            $individualTicketCostAmount != "" && $individualTicketCostCurrency != "" ? ['amount' => $individualTicketCostAmount, 'currency' => $individualTicketCostCurrency] : null,
            $orderNumber,
            $orderEmail,
            $status,
            $saleEndDate,
            $yourPricePerTicketAmount != "" && $yourPricePerTicketCurrency != "" ? ['amount' => $yourPricePerTicketAmount, 'currency' => $yourPricePerTicketCurrency] : null,
            $totalPayoutAmount != "" && $totalPayoutCurrency != "" ? ['amount' => $totalPayoutAmount, 'currency' => $totalPayoutCurrency] : null,
            $quantityRemain,
            $dateLastModified,
            $platform,
            $saleDate,
            $saleId,
            $listingId,
            $listingRestrictions,
            $listingTicketDetails,
        );

        $i->setUser($user);

        return $i;
    }

    public function setId($id)
    {
        $this->id = isset($id) ? htmlspecialchars($id, ENT_NOQUOTES, 'UTF-8') : null;
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
        $this->name = isset($name) ? htmlspecialchars($name, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->location = isset($location) ? htmlspecialchars($location, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->section = isset($section) ? htmlspecialchars($section, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->row = isset($row) ? htmlspecialchars($row, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->ticketType = isset($ticketType) ? htmlspecialchars($ticketType, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->retailer = isset($retailer) ? htmlspecialchars($retailer, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->orderNumber = isset($orderNumber) ? htmlspecialchars($orderNumber, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->orderEmail = isset($orderEmail) ? htmlspecialchars($orderEmail, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->status = isset($status) ? htmlspecialchars($status, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->ticketGenre = isset($ticketGenre) ? htmlspecialchars($ticketGenre, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->country = isset($country) ? htmlspecialchars($country, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->city = isset($city) ? htmlspecialchars($city, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->viagogoEventId = isset($viagogoEventId) ? htmlspecialchars($viagogoEventId, ENT_NOQUOTES, 'UTF-8') : null;

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
                    $expectedTotalPayout = $payoutPerTicket * $this->getPurchasedQuantity();
                    return ["amount" => $expectedTotalPayout, "currency" => $this->getYourPricePerTicket()['currency']];

                default:
                    $payoutPerTicket = $this->getYourPricePerTicket()['amount'];
                    $expectedTotalPayout = $payoutPerTicket * $this->getPurchasedQuantity();
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
     * Get number of purchased tickets
     */
    public function getPurchasedQuantity()
    {
        try {
            if (str_contains(strtolower($this->getSection()), 'floor')) {
                return $this->getFloorSeats();
            }

            /* calculate quantity from seats */
            $seatFromInt = intval($this->getSeatFrom());
            $seatToInt = intval($this->getSeatTo());
            $quantity = $seatToInt - $seatFromInt + 1;
            return $quantity;
        } catch (\Exception $e) {
            return 0;
        }
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
        $this->quantityRemain = isset($quantityRemain) ? htmlspecialchars($quantityRemain, ENT_NOQUOTES, 'UTF-8') : $this->getPurchasedQuantity();

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
        return $this->getPurchasedQuantity() - $this->getQuantityRemain();
    }


    public function getTotalCost()
    {
        return ['amount' => $this->getPurchasedQuantity() * $this->getIndividualTicketCost()["amount"], 'currency' => $this->getIndividualTicketCost()["currency"]];
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
        $this->viagogoCategoryId = isset($viagogoCategoryId) ? htmlspecialchars($viagogoCategoryId, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->platform = isset($platform) ? htmlspecialchars($platform, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->saleId = isset($saleId) ? htmlspecialchars($saleId, ENT_NOQUOTES, 'UTF-8') : $this->saleId;

        return $this;
    }

    /**
     * Returns inventory data as associative array
     */
    public function toArray($sanitizeAsString = false)
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
            'floorSeats' =>  $this->getFloorSeats(),
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
            'quantityRemain' =>  $this->getQuantityRemain(),
            'platform' =>  $this->getPlatform(),
            'saleId' =>  $this->getSaleId(),
            'listingId' =>  $this->getListingId(),
            'listingRestrictions' =>  implode(',', $this->getRestrictions()),
            'listingTicketDetails' =>  implode(',', $this->getTicketDetails()),
            'saleDate' => $sanitizeAsString && $this->getSaleDate() !== null ? $this->getSaleDate()->getTimestamp() : $this->getSaleDate(),
            'eventDate' => $sanitizeAsString && $this->getEventDate() !== null ? $this->getEventDate()->getTimestamp() : $this->getEventDate(),
            'purchaseDate' => $sanitizeAsString && $this->getPurchaseDate() !== null ? $this->getPurchaseDate()->getTimestamp() : $this->getPurchaseDate(),
            'saleEndDate' => $sanitizeAsString && $this->getSaleEndDate() !== null ? $this->getSaleEndDate()->getTimestamp() : $this->getSaleEndDate(),
            'dateLastModified' => $sanitizeAsString && $this->getDateLastModified() !== null ? $this->getDateLastModified()->getTimestamp() : $this->getDateLastModified()
        ];

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
        $this->listingId = isset($listingId) ? htmlspecialchars($listingId, ENT_NOQUOTES, 'UTF-8') : null;

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
        // Check if $restrictions is set and is an array.
        if (isset($restrictions) && is_array($restrictions)) {
            // Set $this->restrictions to $restrictions.
            $this->restrictions = $restrictions;
        } else {
            // If $restrictions is not set or is not an array, check if $this->ticketDetails is set.
            if (isset($this->restrictions)) {
                // Set $this->restrictions to its current value.
                $this->restrictions = $this->restrictions;
            } else {
                // Set $this->restrictions to an empty array.
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
        $this->seatFrom = isset($seatFrom) ? htmlspecialchars($seatFrom, ENT_NOQUOTES, 'UTF-8') : null;

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
        $this->seatTo = isset($seatTo) ? htmlspecialchars($seatTo, ENT_NOQUOTES, 'UTF-8') : null;

        return $this;
    }

    /**
     * Get the value of user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @return  self
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the value of saleEndDate
     */
    public function getSaleEndDate()
    {
        return $this->saleEndDate;
    }

    /**
     * Set the value of saleEndDate
     *
     * @return  self
     */
    public function setSaleEndDate($saleEndDate)
    {
        if (is_string($saleEndDate)) {
            // Parse the string to create a DateTime object
            try {
                $saleEndDate = new DateTime($saleEndDate);
            } catch (Exception $e) {
                return $this;
            }
        }

        $this->saleEndDate = $saleEndDate;

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
        if (is_string($eventDate)) {
            // Parse the string to create a DateTime object
            try {
                $eventDate = new DateTime($eventDate);
            } catch (Exception $e) {
                return $this;
            }
        }

        $this->eventDate = $eventDate;

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
        if (is_string($purchaseDate)) {
            // Parse the string to create a DateTime object
            try {
                $purchaseDate = new DateTime($purchaseDate);
            } catch (Exception $e) {
                return $this;
            }
        }

        $this->purchaseDate = $purchaseDate;

        return $this;
    }

    /**
     * Get the value of dateLastModified
     */
    public function getDateLastModified()
    {
        return $this->dateLastModified;
    }

    /**
     * Set the value of dateLastModified
     *
     * @return  self
     */
    public function setDateLastModified($dateLastModified)
    {
        if (is_string($dateLastModified)) {
            // Parse the string to create a DateTime object
            try {
                $dateLastModified = new DateTime($dateLastModified);
            } catch (Exception $e) {
                return $this;
            }
        }

        $this->dateLastModified = $dateLastModified;

        return $this;
    }

    /**
     * Get the value of saleDate
     */
    public function getSaleDate()
    {
        return $this->saleDate;
    }

    /**
     * Set the value of saleDate
     *
     * @return  self
     */
    public function setSaleDate($saleDate)
    {
        if (is_string($saleDate)) {
            // Parse the string to create a DateTime object
            try {
                $saleDate = new DateTime($saleDate);
            } catch (Exception $e) {
                return $this;
            }
        }

        $this->saleDate = $saleDate;

        return $this;
    }

    /**
     * Get the value of floorSeats
     */
    public function getFloorSeats()
    {
        return $this->floorSeats;
    }

    /**
     * Set the value of floorSeats
     *
     * @return  self
     */
    public function setFloorSeats($floorSeats)
    {
        $this->floorSeats = $floorSeats;

        return $this;
    }

    public function __clone()
    {
        $this->id = null;
    }
}
