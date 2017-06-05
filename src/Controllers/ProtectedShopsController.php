<?php

namespace ProtectedShops\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\ConfigRepository;

class ProtectedShopsController extends Controller
{
    public function protectedShopsInfo(Twig $twig, ConfigRepository $config):string
    {
        $data['shopId'] = $config->get('ProtectedShopsForPlenty.shopId');
        return $twig->render('ProtectedShopsForPlenty::content.info', $data);
    }
}