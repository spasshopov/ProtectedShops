<?php

namespace ProtectedShops\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;

class ProtectedShopsRouteServiceProvider extends RouteServiceProvider
{
    public function map(Router $router)
    {
        $router->get('/protectedshops', 'ProtectedShops\Controllers\ProtectedShopsController@protectedShopsInfo');
    }
}
