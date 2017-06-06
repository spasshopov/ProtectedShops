<?php

namespace ProtectedShops\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\ConfigRepository;
use ProtectedShops\Service\ProtectedShopsDocumentService;

class ProtectedShopsController extends Controller
{
    /**
     * @var ProtectedShopsDocumentService
     */
    private $psService;

    public function protectedShopsInfo(Twig $twig, ConfigRepository $config):string
    {
        $this->psService = new ProtectedShopsDocumentService();

        $shopId = $config->get('ProtectedShopsForPlenty.shopId');
        $data['shopId'] = $shopId;
        $data['document'] = $this->psService->getDocument($shopId, 'agb');

        return $twig->render('ProtectedShopsForPlenty::content.info', $data);
    }
}