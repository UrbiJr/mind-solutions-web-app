<?php

namespace App\Service;

use DateTime;
use DateTimeInterface;
use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Intl\Countries;

/**
 * See https://symfony.com/doc/current/service_container.html#creating-configuring-services-in-the-container.
 *
 */
final class Utils
{

    public function __construct(private Client $client, private string $exchangeRateApiKey, private string $jwtKey, private readonly MemcachedAdapter $cache)
    {
    }

    public static function unixTimestampToDateTime(int $timestamp): DateTimeInterface
    {
        // Create a DateTime object
        $dateTime = new DateTime();
        $dateTime->setTimestamp($timestamp);

        return $dateTime;
    }

    /**
     * Fetch exchange rates from cache and if not available, fetch from API and cache.
     * Returns an associative array with the exchange rates.
     */
    function cacheExchangeRates($currency)
    {
        $cacheItem = $this->cache->getItem("exchangeRates_" . strtoupper($currency));
        if (!$cacheItem->isHit()) {
            $exchangeRates = $this->fetchExchangeRates($currency);
            $cacheItem->set($exchangeRates);
            $cacheItem->expiresAfter(3600); // Cache for 1 hour
            $this->cache->save($cacheItem);
        }
        return $cacheItem->get();
    }

    function getCountryNameFromIsoCode($isoCode)
    {
        return Countries::getName(strtoupper($isoCode));
    }

    function getIsoCodeFromCountryName($countryName)
    {
        $countries = Countries::getNames();
        $isoCode = array_search($countryName, $countries);

        return $isoCode !== false ? $isoCode : null;
    }

    function formatAmountArrayAsSymbol($amountArray)
    {
        switch (strtoupper($amountArray["currency"])) {
            case 'EUR':
                return "€" . number_format($amountArray["amount"], 2);
            case 'GBP':
                return "£" . number_format($amountArray["amount"], 2);
            case 'USD':
                return "$" . number_format($amountArray["amount"], 2);
            case 'CAD':
                return "C$" . number_format($amountArray["amount"], 2);
            case 'CHF':
                return "₣" . number_format($amountArray["amount"], 2);
        }
    }

    function formatAmountAndCurrencyAsSymbol($amount, $stringCurrency)
    {
        switch (strtoupper($stringCurrency)) {
            case 'EUR':
                return "€" . number_format($amount, 2);
            case 'GBP':
                return "£" . number_format($amount, 2);
            case 'USD':
                return "$" . number_format($amount, 2);
            case 'CAD':
                return "C$" . number_format($amount, 2);
            case 'CHF':
                return "₣" . number_format($amount, 2);
            default:
                return strtoupper($stringCurrency) . number_format($amount, 2);
        }
    }

    function currencySymbolToString($currencySymbol)
    {
        switch ($currencySymbol) {
            case '€':
                return "EUR";
            case '£':
                return "GBP";
            case '$':
                return "USD";
            case 'C$':
                return "CAD";
            case '₣':
                return "CHF";
        }
    }

    function currencyStringToSymbol($currencyString)
    {
        switch ($currencyString) {
            case 'EUR':
                return "€";
            case 'GBP':
                return "£";
            case 'USD':
                return "$";
            case 'CAD':
                return "C$";
            case 'CHF':
                return "₣";
        }
    }

    function fetchExchangeRates($currency)
    {
        // Your Open Exchange Rates API URL with your app ID
        $apiUrl = "https://v6.exchangerate-api.com/v6/" . $this->exchangeRateApiKey . "/latest/" . strtoupper($currency) . "/";

        try {
            // Make a GET request to the API using Guzzle
            $response = $this->client->get($apiUrl);

            // Get the response body
            $exchangeRatesJson = $response->getBody()->getContents();

            // Decode the JSON response into an associative array
            $exchangeRates = json_decode($exchangeRatesJson, true);

            return $exchangeRates;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Handle exceptions or errors here
            // You can access the error response using $e->getResponse()
            // Example: $errorResponse = $e->getResponse()->getBody()->getContents();
        }
    }

    function convertCurrency(float|int $amount, $outputExchangeRates, $inputCurrency)
    {
        // Check if the exchange rates are available
        if (isset($outputExchangeRates['conversion_rates'])) {
            $rates = $outputExchangeRates['conversion_rates'];

            if (isset($rates[strtoupper($inputCurrency)])) {
                $convertedAmount = $amount / $rates[strtoupper($inputCurrency)];
                // Format the result with 2 decimal places
                return $convertedAmount;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    // Function to generate a JWT token
    function generateToken($expiresIn)
    {
        // Calculate the expiration time (current timestamp + expiresIn seconds)
        $expirationTime = time() + $expiresIn;

        // Create a payload with the data you want to include in the token
        $payload = array(
            "exp" => $expirationTime // Expiry time in seconds
        );

        // Encode the payload into a JWT token
        $token = JWT::encode($payload, $this->jwtKey, 'HS256');

        return $token;
    }

    // Helper function to check if a date is in MySQL timestamp format
    function isMySQLTimestamp($date)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date) === 1;
    }

    function pathCombine($parts)
    {
        $path = implode(DIRECTORY_SEPARATOR, $parts);
        return preg_replace('#/+#', '/', $path);
    }

    function getGenreNameById($genreId)
    {
        switch ($genreId) {
            case 1:
                return 'Theater Tickets';
            case 2:
                return 'Sports Tickets';
            case 3:
                return 'Concert Tickets';

            default:
                return false;
        }
    }
}
