<?php

namespace ProtectedShops\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\ConfigRepository;

class ProtectedShopsController extends Controller
{
    /**
     * @var string
     */
    private $apiUrl;

    public function protectedShopsInfo(Twig $twig, ConfigRepository $config):string
    {
        $shopId = $config->get('ProtectedShopsForPlenty.shopId');
        $data['shopId'] = $shopId;
        $data['document'] = $this->getDocument($shopId, 'agb');

        return $twig->render('ProtectedShopsForPlenty::content.info', $data);
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