<?php

namespace ProtectedShops\Controllers;


use Plenty\Plugin\Controller;
use Plenty\Plugin\Templates\Twig;

class ProtectedShopsController extends Controller
{
    public function index(Twig $twig)
    {
        return $twig->render('ProtectedShopsForPlenty::content.index');
    }
}