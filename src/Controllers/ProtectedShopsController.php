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
use ProtectedShops\Repositories\ProtectedShopsLegalTextRepository;
use Plenty\Plugin\Http\Request;

class ProtectedShopsController extends Controller
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
     * @var AuthHelper
     */
    private $authHelper;

    /**
     * @var LegalInformationRepositoryContract
     */
    private $legalInfoRepository;

    /**
     * @var ProtectedShopsLegalTextRepository
     */
    private $psLegalTextRepository;

    /**
     * ProtectedShopsController constructor.
     * @param AuthHelper $authHelper
     * @param LegalInformationRepositoryContract $legalInfoRepository
     * @param ProtectedShopsLegalTextRepository $psLegalTextRepository
     */
    public function __construct(AuthHelper $authHelper, LegalInformationRepositoryContract $legalInfoRepository, ProtectedShopsLegalTextRepository $psLegalTextRepository)
    {
        $this->authHelper = $authHelper;
        $this->legalInfoRepository = $legalInfoRepository;
        $this->psLegalTextRepository = $psLegalTextRepository;

        $legalTextsFromConfig = $this->psLegalTextRepository->getPsLegalTexts();

        if (!$legalTextsFromConfig) {
            $this->psLegalTextRepository->createPsLegalText(array(
                'legalText' => 'TermsConditions',
                'success' => false,
                'shouldSync' => false
            ));
            $this->psLegalTextRepository->createPsLegalText(array(
                'legalText' => 'CancellationRights',
                'success' => false,
                'shouldSync' => false
            ));
            $this->psLegalTextRepository->createPsLegalText(array(
                'legalText' => 'PrivacyPolicy',
                'success' => false,
                'shouldSync' => false
            ));
            $this->psLegalTextRepository->createPsLegalText(array(
                'legalText' => 'LegalDisclosure',
                'success' => false,
                'shouldSync' => false
            ));
        }
    }

    /**
     * @param Twig $twig
     * @return mixed
     */
    public function listLegalTexts(Twig $twig):string
    {
        $data['legalTexts'] = $this->psLegalTextRepository->getPsLegalTexts();
        $data['legalTextsToGerman'] = $this->docMap;
        return $twig->render('ProtectedShops::content.list', $data);
    }

    /**
     * @param Twig $twig
     * @param ConfigRepository $config
     * @param Request $request
     * @return string
     */
    public function protectedShopsUpdateLegalTexts(Twig $twig, ConfigRepository $config, Request $request):string
    {
        try {
            $shopId = $config->get('ProtectedShops.shopId');
            $plentyId = $config->get('ProtectedShops.plentyId');
            $legalTextsToSync = array_unique($request->get('psLegalTexts'));
            $legalTextsFromConfig = $this->psLegalTextRepository->getPsLegalTexts();
            $data['legalTextsToGerman'] = $this->docMap;

            $updated = [];
            foreach ($legalTextsToSync as $legalText) {
                $remoteResponseHtml = $this->getDocument($shopId, $this->docMap[$legalText], 'html');
                $remoteResponseText = $this->getDocument($shopId, $this->docMap[$legalText], 'text');
                $document = json_decode($remoteResponseHtml);
                $successHtml = $this->updateDocument($document, $plentyId, $legalText, 'htmlText');
                $document = json_decode($remoteResponseText);
                $successText = $this->updateDocument($document, $plentyId, $legalText, 'plainText');

                $data['updated'][] = [
                    'type' => $legalText,
                    'success' => $successHtml && $successText
                ];

                $updated[$legalText] = $successHtml && $successText;
            }

            foreach ($legalTextsFromConfig as $legalTextFromConfig) {
                $legalTextFromConfig->shouldSync = false;
                if (in_array($legalTextFromConfig->legalText, $legalTextsToSync)) {
                    $legalTextFromConfig->shouldSync = true;
                    $legalTextFromConfig->success = $updated[$legalTextFromConfig->legalText];
                    $legalTextFromConfig->updated = time();
                }

                $this->psLegalTextRepository->updatePsLegalText($legalTextFromConfig);
            }

            $data['success'] = true;
            $data['legalTexts'] = $this->psLegalTextRepository->getPsLegalTexts();
            return $twig->render('ProtectedShops::content.list', $data);
        } catch (\Exception $e) {
            $data['success'] = false;
            echo $e->getMessage();
            $this->getLogger(__FUNCTION__)->error('ProtectedShops::Sync error: ', $e->getMessage());
            return $twig->render('ProtectedShops::content.list', $data);
        }
    }

    /**
     * @param $document
     * @param $plentyId
     * @param $legalText
     * @param $formatType
     * @return bool
     */
    private function updateDocument($document, $plentyId, $legalText, $formatType):bool
    {
        $legalInfoRepository = $this->legalInfoRepository;
        $logger = $this->getLogger(__FUNCTION__);
        $success = false;
        $this->authHelper->processUnguarded(
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
     * @param $shopId
     * @param $documentType
     * @param $format
     *
     * @return string
     */
    private function getDocument($shopId, $documentType, $format):string
    {
        $apiFunction = 'documents/' . $documentType . '/contentformat/' . $format;
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