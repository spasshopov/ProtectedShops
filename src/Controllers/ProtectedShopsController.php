<?php

namespace ProtectedShops\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\ConfigRepository;
use ProtectedShops\Service\ProtectedShopsDocumentService;

class ProtectedShopsController extends Controller
{
    private $psService;

    public function __construct()
    {
        parent::__construct();
        $this->psService = new ProtectedShopsDocumentService();
    }

    public function protectedShopsInfo(Twig $twig, ConfigRepository $config):string
    {
        $shopId = $config->get('ProtectedShopsForPlenty.shopId');
        $data['shopId'] = $shopId;
        $data['document'] = $this->psService->getDocument($shopId, 'agb');

        return $twig->render('ProtectedShopsForPlenty::content.info', $data);
    }
}