<?php

namespace ProtectedShops\Service;

use ProtectedShops\Service\PSAPI\ApiClient;

class ProtectedShopsDocumentService
{
    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var string
     */
    private $apiUrl = 'api.stage.protectedshops.de';

    /**
     * ProtectedShopsDocumentService constructor.
     */
    public function __construct()
    {
        $this->apiClient = new ApiClient($this->apiUrl);
    }

    /**
     * @param $shopId
     * @param $documentType
     * @return string
     */
    public function getDocument($shopId, $documentType)
    {
        return $this->apiClient->getDocument($shopId, $documentType);
    }
}
