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
            $useStaging = $config->get('ProtectedShopsForPlenty.useStaging');
            $legalTextsToSync = explode(", ", $config->get('ProtectedShopsForPlenty.legalTexts'));

            if ($useStaging === 'true') {
                $this->apiUrl = $this->apiStageUrl;
            }

            foreach ($legalTextsToSync as $legalText) {
                $remoteResponse = $this->getDocument($shopId, $this->docMap[$legalText]);
                $document = json_decode($remoteResponse);
                if (!$this->updateDocument($authHelper, $legalInfoRepository, $document, $plentyId, $legalText)) {
                    $this->getLogger(__FUNCTION__)->error('ProtectedShops::Sync error: ', $legalText . ' could not be updated');
                }
            }
        } catch (\Exception $e) {
            $this->getLogger(__FUNCTION__)->error('ProtectedShops::Sync error: ', $e->getMessage());
        }
    }

    /**
     * @param AuthHelper $authHelper
     * @param LegalInformationRepositoryContract $legalInfoRepository
     * @param $document
     * @param $plentyId
     * @param $legalText
     * @return bool
     */
    function updateDocument(AuthHelper $authHelper, LegalInformationRepositoryContract $legalInfoRepository, $document, $plentyId, $legalText):bool
    {
        $logger = $this->getLogger(__FUNCTION__);
        $success = false;
        $authHelper->processUnguarded(
            function () use ($document, $legalInfoRepository, $plentyId, $legalText, &$success, $logger) {
                try {
                    foreach ($document as $key => $value) {
                        if ('content' === $key) {
                            $legalInfoRepository->save(array('htmlText' => $value), $plentyId, 'de', $legalText);
                            $success = true;
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    $logger->error('ProtectedShops::Sync error: ', $e->getMessage());
                }
            }
        );

        return $success;
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
