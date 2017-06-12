<?php

namespace ProtectedShops\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;

class ProtectedShopsRouteServiceProvider extends RouteServiceProvider
{
    public function map(Router $router)
    {
        $router->get('/protectedshops/sync/TermsConditions', 'ProtectedShops\Controllers\ProtectedShopsController@updateAGB');
        $router->get('/protectedshops/sync/CancellationRights', 'ProtectedShops\Controllers\ProtectedShopsController@updateCancellationRights');
        $router->get('/protectedshops/sync/PrivacyPolicy', 'ProtectedShops\Controllers\ProtectedShopsController@updatePrivacyPolicy');
        $router->get('/protectedshops/sync/LegalDisclosure', 'ProtectedShops\Controllers\ProtectedShopsController@updateLegalDisclosure');
    }
}
