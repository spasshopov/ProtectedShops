<?php

namespace ProtectedShops\Controllers;


use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;

class ProtectedShopsController extends Controller
{
    public function protectedShopsInfo(Twig $twig):string
    {
        return $twig->render('ProtectedShopsForPlenty::content.info');
    }
}