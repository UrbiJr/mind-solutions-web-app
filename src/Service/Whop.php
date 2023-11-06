<?php

namespace App\Service;

use App\Exception\BadResponseException;
use App\Exception\BadStatusCodeException;
use App\Exception\InvalidLicenseKeyException;
use GuzzleHttp\Client;

class Whop
{

    private $client;
    private $apiKey;
    private $mindSolutionsPassId;

    public function __construct(Client $client, string $apiKey, string $mindSolutionsPassId)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
        $this->mindSolutionsPassId = $mindSolutionsPassId;
    }

    public function getAuthToken($code, $clientId, $clientSecret, $redirectUri)
    {
        $postData = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        );

        $apiUrl = 'https://api.whop.com/api/v2/oauth/token';

        $response = $this->client->request("POST", $apiUrl, [
            'form_params' => $postData,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Bad status code: " . $response->getStatusCode());
        }

        // Convert JSON response to an associative array
        $respData = json_decode($response->getBody(), true);

        if (isset($respData["error"])) {
            throw new \Exception($respData["error_description"]);
        }

        return  $respData["access_token"];
    }

    public function hasAccess($accessPassId, $accessToken)
    {
        $apiUrl = "https://api.whop.com/api/v2/me/has_access/{$accessPassId}";

        $response = $this->client->request('GET', $apiUrl, [
            'headers' => [
                'Authorization: Bearer ' . $accessToken
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Bad status code: " . $response->getStatusCode());
        }

        // Convert JSON response to an associative array
        $respData = json_decode($response->getBody(), true);

        if (isset($respData["error"])) {
            throw new \Exception($respData["error_description"]);
        }

        if (isset($respData["valid"]) && $respData["valid"] === true) {
            return true;
        }

        return false;
    }

    public function validateLicenseKey($licenseKey)
    {
        if (!isset($licenseKey) || count($licenseKey) <= 0) {
            throw new InvalidLicenseKeyException('empty value');
        }

        $response = $this->client->get('https://api.whop.com/api/v2/memberships/' . $licenseKey, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'accept' => 'application/json',
            ],
            'http_errors' => false,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new BadStatusCodeException($response->getStatusCode());
        }

        // Convert JSON response to an associative array
        $respData = json_decode($response->getBody(), true);

        if (isset($respData["error"])) {
            throw new BadResponseException($respData["message"]);
        }

        if (!isset($respData['id'])) {
            throw new BadResponseException("membership id not found");
        }

        if ($respData['valid'] !== true) {
            if (isset($respData['status'])) {
                throw new InvalidLicenseKeyException("status: " . $respData['status']);
            } else {
                throw new InvalidLicenseKeyException("license is expired");
            }
        } else {
            return $respData;
        }

        throw new BadResponseException("Error parsing response data");
    }

    public function validateMembership($accessToken)
    {
        $client = new \GuzzleHttp\Client();

        $response = $client->request('GET', 'https://api.whop.com/api/v2/oauth/user/memberships?page=1&per=10&valid=true', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'accept' => 'application/json',
            ],
        ]);

        // Convert JSON response to an associative array
        $respData = json_decode($response->getBody(), true);

        if (isset($respData["error"])) {
            throw new \Exception($respData["message"]);
        }

        if (count($respData['data']) == 0) {
            throw new \Exception("No valid memberships found");
        }

        foreach ($respData['data'] as $entry) {
            if ($entry['access_pass'] === $this->mindSolutionsPassId) {
                return $entry;
            }
        }

        throw new \Exception("Mind Solutions pass not found. It sounds like you have a valid license for a different product.");
    }
}
