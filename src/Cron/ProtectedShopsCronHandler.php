<?php

namespace ProtectedShops\Cron;

use Plenty\Modules\Cron\Contracts\CronHandler;
use Plenty\Plugin\ConfigRepository;
use Plenty\Modules\Frontend\LegalInformation\Contracts\LegalInformationRepositoryContract;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Plugin\Log\Loggable;
use ProtectedShops\Repositories\ProtectedShopsLegalTextRepository;

class ProtectedShopsCronHandler extends CronHandler
{
    use Loggable;

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
     * @param ProtectedShopsLegalTextRepository $legalTextRepository
     */
    public function handle(ConfigRepository $config, LegalInformationRepositoryContract $legalInfoRepository, AuthHelper $authHelper, ProtectedShopsLegalTextRepository $legalTextRepository):void
    {
        try {
            $shopId = $config->get('ProtectedShops.shopId');
            $plentyId = $config->get('ProtectedShops.plentyId');
            $legalTextsFromConfig = $legalTextRepository->getPsLegalTexts();

            foreach ($legalTextsFromConfig as $legalText) {
                if (!$legalText->shouldSync) {
                    continue;
                }
                $remoteResponseHtml = $this->getDocument($shopId, $this->docMap[$legalText->legalText], 'html');
                $remoteResponseText = $this->getDocument($shopId, $this->docMap[$legalText->legalText], 'text');
                $document = json_decode($remoteResponseHtml);
                $successHtml = $this->updateDocument($authHelper, $legalInfoRepository, $document, $plentyId, $legalText->legalText, 'htmlText');
                $document = json_decode($remoteResponseText);
                $successText = $this->updateDocument($authHelper, $legalInfoRepository, $document, $plentyId, $legalText->legalText, 'plainText');

                if (!($successHtml && $successText)) {
                    $this->getLogger(__FUNCTION__)->error('ProtectedShops::Sync error: ', $legalText . ' could not be updated');
                    $legalText->success = false;
                } else {
                    $legalText->success = true;
                }
                $legalText->updated = time();
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
     * @param $formatType
     * @return bool
     */
    function updateDocument(AuthHelper $authHelper, LegalInformationRepositoryContract $legalInfoRepository, $document, $plentyId, $legalText, $formatType):bool
    {
        $logger = $this->getLogger(__FUNCTION__);
        $success = false;
        $authHelper->processUnguarded(
            function () use ($document, $legalInfoRepository, $plentyId, $legalText, &$success, $logger, $formatType) {
                try {
                    foreach ($document as $key => $value) {
                        if ('content' === $key) {
                            $legalInfoRepository->save(array($formatType => $value), $plentyId, 'de', $legalText);
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
     * @param string $shopId
     * @param string $documentType
     * @param string $docFormat
     *
     * @return string
     */
    private function getDocument($shopId, $documentType, $docFormat):string
    {
        $apiFunction = 'documents/' . $documentType . '/contentformat/' . $docFormat;
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
