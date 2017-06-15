<?php

namespace ProtectedShops\Cron;

use Plenty\Modules\Cron\Contracts\CronHandler;
use Plenty\Plugin\ConfigRepository;
use Plenty\Modules\Frontend\LegalInformation\Contracts\LegalInformationRepositoryContract;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Plugin\Log\Loggable;

class ProtectedShopsCronHandler extends CronHandler
{
    use Loggable;

    /**
     * @var string
     */
    private $apiStageUrl = 'api.stage.protectedshops.de';

    /**
     * @var string
     */
    private $apiUrl = 'api.protectedshops.de';

    /**
     * @var array
     */
    private $docMap = [
        'TermsConditions'    => 'AGB',
        'CancellationRights' => 'Widerruf',
        'PrivacyPolicy'      => 'Datenschutz',
        'LegalDisclosure'    => 'Impressum'
    ];

    /**
     * @param ConfigRepository $config
     * @param LegalInformationRepositoryContract $legalInfoRepository
     */
    public function handle(ConfigRepository $config, LegalInformationRepositoryContract $legalInfoRepository, AuthHelper $authHelper):void
    {
        try {
            $shopId = $config->get('ProtectedShopsForPlenty.shopId');
            $plentyId = $config->get('ProtectedShopsForPlenty.plentyId');
            $apiUrl = $config->get('ProtectedShopsForPlenty.apiUrl');
            if ($apiUrl) {
                $this->apiUrl = $this->apiStageUrl;
            }
            $legalTextsToSync = explode(", ", $config->get('ProtectedShopsForPlenty.legalTexts'));
            $data['shopId'] = $shopId;
            $documents = [];

            foreach ($legalTextsToSync as $legalText) {
                $remoteResponse = $this->getDocument($shopId, $this->docMap[$legalText]);
                $documents[$legalText] = json_decode($remoteResponse);
                $data['updated'][] = $legalText;
            }

            if (!$this->updateDocuments($authHelper, $legalInfoRepository, $documents, $plentyId)) {
                $this->getLogger(__FUNCTION__)->error('ProtectedShops::Sync error: ', 'Could not update legal texts');
            }

        } catch (\Exception $e) {
            $this->getLogger(__FUNCTION__)->error('ProtectedShops::Sync error: ', $e->getMessage());
        }
    }


    /**
     * @param AuthHelper $authHelper
     * @param $documents
     * @param $plentyId
     * @return bool
     */
    private function updateDocuments(AuthHelper $authHelper, LegalInformationRepositoryContract $legalInfoRepository, $documents, $plentyId):bool
    {
        $authHelper->processUnguarded(
            function () use ($documents, $legalInfoRepository, $plentyId) {
                try {
                    foreach($documents as $legalText => $document) {
                        foreach ($document as $key => $value) {
                            if ('content' === $key) {
                                $legalInfoRepository->save(array('htmlText' => $value), $plentyId, 'de', $legalText);
                                break;
                            }
                        }
                    }
                    return true;
                } catch (\Exception $e) {
                    return false;
                }
            }
        );
        return true;
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