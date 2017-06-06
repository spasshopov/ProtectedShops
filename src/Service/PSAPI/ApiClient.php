<?php

namespace ProtectedShops\Service\PSAPI;

/**
 * Class ApiClient
 * @package ProtectedShops\Service\PSAPI
 */
class ApiClient
{
    /**
     * @var string
     */
    private $apiUrl;

    /**
     * ApiClient constructor.
     * @param string $apiUrl
     */
    public function __construct($apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    /**
     * @param $shopId
     * @param $documentType
     * @return string
     */
    public function getDocument($shopId, $documentType)
    {
        $apiFunction = 'documents/' . $documentType . '/contentformat/html';
        $response = $this->apiRequest($shopId, $apiFunction);

        return $response;
    }

    /**
     * @param $shopId
     * @param $apiFunction
     * @return mixed
     */
    private function apiRequest($shopId, $apiFunction)
    {
        $dsUrl = "https://$this->apiUrl/v2.0/de/partners/demo/shops/$shopId/$apiFunction/format/json";

        // Open connection
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $dsUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        //close connection
        curl_close($ch);

        return $response;
    }
}
