<?php

namespace ProtectedShops\Controllers;

use Plenty\Plugin\Application;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\ConfigRepository;
use Plenty\Modules\Frontend\LegalInformation\Contracts\LegalInformationRepositoryContract;
use Plenty\Modules\Cron\Services\CronContainer;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Plugin\Log\Loggable;
use ProtectedShops\Repositories\PsLegalTextRepository;

class ProtectedShopsController extends Controller
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
     * @var AuthHelper
     */
    private $authHelper;

    /**
     * @var LegalInformationRepositoryContract
     */
    private $legalInfoRepository;

    /**
     * @var PsLegalTextRepository
     */
    private $psLegalTextRepository;

    /**
     * ProtectedShopsController constructor.
     * @param AuthHelper $authHelper
     * @param LegalInformationRepositoryContract $legalInfoRepository
     * @param PsLegalTextRepository $psLegalTextRepository
     */
    public function __construct(AuthHelper $authHelper, LegalInformationRepositoryContract $legalInfoRepository, PsLegalTextRepository $psLegalTextRepository)
    {
        $this->authHelper = $authHelper;
        $this->legalInfoRepository = $legalInfoRepository;
        $this->psLegalTextRepository = $psLegalTextRepository;
    }

    /**
     * @param Twig $twig
     * @param ConfigRepository $config
     * @return string
     */
    public function protectedShopsUpdateDocuments(Twig $twig, ConfigRepository $config):string
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
                $data['updated'][] = [
                    'type' => $legalText,
                    'success' => $this->updateDocument($document, $plentyId, $legalText)
                ];
            }

            $data['success'] = true;
            return $twig->render('ProtectedShopsForPlenty::content.info', $data);
        } catch (\Exception $e) {
            $data['success'] = false;
            $this->getLogger(__FUNCTION__)->error('ProtectedShops::Sync error: ', $e->getMessage());
            return $twig->render('ProtectedShopsForPlenty::content.info', $data);
        }
    }

    /**
     * @param $document
     * @param $plentyId
     * @return bool
     */
    private function updateDocument($document, $plentyId, $legalText):bool
    {
        $legalInfoRepository = $this->legalInfoRepository;
        $logger = $this->getLogger(__FUNCTION__);
        $success = false;
        $this->authHelper->processUnguarded(
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