<?php

namespace ProtectedShops\Controllers;

use Plenty\Plugin\Application;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\ConfigRepository;
use Plenty\Modules\Frontend\LegalInformation\Contracts\LegalInformationRepositoryContract;
use Plenty\Modules\Cron\Services\CronContainer;
use Plenty\Modules\Authorization\Services\AuthHelper;

class ProtectedShopsController extends Controller
{
    /**
     * @var string
     */
    private $apiUrl = 'api.stage.protectedshops.de';

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
     * ProtectedShopsController constructor.
     * @param AuthHelper $authHelper
     * @param LegalInformationRepositoryContract $legalInfoRepository
     */
    public function __construct(AuthHelper $authHelper, LegalInformationRepositoryContract $legalInfoRepository)
    {
        $this->authHelper = $authHelper;
        $this->legalInfoRepository = $legalInfoRepository;
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
            $legalTextsToSync = explode(", ", $config->get('ProtectedShopsForPlenty.legalTexts'));
            $data['shopId'] = $shopId;
            $documents = [];

            foreach ($legalTextsToSync as $legalText) {
                $remoteResponse = $this->getDocument($shopId, $this->docMap[$legalText]);
                $documents[$legalText] = json_decode($remoteResponse);
                $data['updated'][] = $legalText;
            }

            $data['success'] = $this->updateDocuments($documents, $plentyId);
            return $twig->render('ProtectedShopsForPlenty::content.info', $data);
            //$cron->add(CronContainer::EVERY_FIFTEEN_MINUTES, "ProtectedShops\\Cron\\ProtectedShopsCronHandler");
        } catch (\Exception $e) {
            $data['success'] = false;
            return $twig->render('ProtectedShopsForPlenty::content.info', $data);
        }
    }

    /**
     * @param $documents
     * @param $plentyId
     * @return bool
     */
    private function updateDocuments($documents, $plentyId):bool
    {
        $legalInfoRepository = $this->legalInfoRepository;
        $this->authHelper->processUnguarded(
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