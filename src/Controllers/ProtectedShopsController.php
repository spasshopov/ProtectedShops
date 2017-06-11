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
    public function protectedShopsInfo(Twig $twig, ConfigRepository $config):string
    {
        $shopId = $config->get('ProtectedShopsForPlenty.shopId');
        $plentyId = $config->get('ProtectedShopsForPlenty.plentyId');
        $legalTextsToSync = $config->get('ProtectedShopsForPlenty.legalTexts');

        $data['shopId'] = $shopId;

        foreach ($legalTextsToSync as $legalText) {
            $remoteResponse = $this->getDocument($shopId, $this->docMap[$legalText]);
            $data['doc'] = json_decode($remoteResponse);
            $this->updateDocument($data['doc'], $plentyId, $legalText);
        }

        //$cron->add(CronContainer::EVERY_FIFTEEN_MINUTES, "ProtectedShops\\Cron\\ProtectedShopsCronHandler");

        return $twig->render('ProtectedShopsForPlenty::content.info', $data);
    }

    /**
     * @param $document
     * @param $plentyId
     * @param $legalText
     */
    private function updateDocument($document, $plentyId, $legalText):void
    {
        try {
            $legalInfoRepository = $this->legalInfoRepository;
            $this->authHelper->processUnguarded(
                function () use ($document, $legalInfoRepository, $plentyId, $legalText) {
                    try {
                        foreach ($document  as $key => $value) {
                            if ('content' === $key) {
                                echo "Updating ..." . $plentyId . " " . $legalText;
                                $legalInfoRepository->save(array('htmlText' => $value), $plentyId, 'de', $legalText);
                            }
                        }
                    } catch (\Exception $e) {
                        echo "Failed ..." . $plentyId . " " . $legalText;
                        echo $e->getMessage();
                    }

                }
            );

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param $shopId
     * @param $documentType
     * @return string
     */
    public function getDocument($shopId, $documentType):string
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