<?php

namespace ProtectedShops\Providers;

use Plenty\Plugin\ServiceProvider;
use Plenty\Plugin\Routing\Router;

class ProtectedShopsRouteServiceProvider extends ServiceProvider
{
    public function map(Router $router)
    {
        $router->get('index','ProtectedShops\Controllers\ProtectedShopsController@index');
    }
}
