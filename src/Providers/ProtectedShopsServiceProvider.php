<?php

namespace ProtectedShops\Providers;

use \Plenty\Plugin\ServiceProvider;

class ProtectedShopsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->getApplication()->register(ProtectedShopsRouteServiceProvider::class);
    }
}
