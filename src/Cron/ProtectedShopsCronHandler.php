<?php

namespace ProtectedShops\Cron;

use Plenty\Modules\Cron\Contracts\CronHandler;
use Plenty\Plugin\ConfigRepository;
use Plenty\Modules\Frontend\LegalInformation\Contracts\LegalInformationRepositoryContract;

class ProtectedShopsCronHandler extends CronHandler
{
    public function handle(ConfigRepository $config, LegalInformationRepositoryContract $legalInfoRepository):void
    {
        $shopId = $config->get('ProtectedShopsForPlenty.shopId');
        $plentyId = $config->get('ProtectedShopsForPlenty.plentyId');

        if (!$shopId || !$plentyId) {
            return;
        }

        $remoteResponse = json_decode($this->getDocument($shopId, 'agb'));

        if ($remoteResponse['content']) {
            $legalInfoRepository->save(array('htmlText' => $remoteResponse['content']), $plentyId, 'de', 'TermsConditions');
        }
    }

    /**
     * @param $shopId
     * @param $documentType
     * @return string
     */
    private function getDocument($shopId, $documentType):string
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
    private function apiRequest($shopId, $apiFunction):string
    {
        $dsUrl = "https://$this->apiUrl/v2.0/de/partners/protectedshops/shops/$shopId/$apiFunction/format/json";

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