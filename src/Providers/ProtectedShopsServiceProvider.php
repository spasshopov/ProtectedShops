<?php

namespace ProtectedShops\Providers;

use Plenty\Plugin\ServiceProvider;
use Plenty\Modules\Cron\Services\CronContainer;
use ProtectedShops\Cron\ProtectedShopsCronHandler;
use ProtectedShops\Repositories\ProtectedShopsLegalTextRepository;
use ProtectedShops\Contracts\ProtectedShopsLegalTextContract;

class ProtectedShopsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->getApplication()->register(ProtectedShopsRouteServiceProvider::class);
        $this->getApplication()->bind(ProtectedShopsLegalTextContract::class, ProtectedShopsLegalTextRepository::class);
    }

    /**
     * @param CronContainer $container
     */
    public function boot(CronContainer $container)
    {
        $container->add(CronContainer::DAILY, ProtectedShopsCronHandler::class);
    }
}

