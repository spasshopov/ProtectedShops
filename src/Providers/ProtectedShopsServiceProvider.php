<?php

namespace ProtectedShops\Providers;

use Plenty\Plugin\ServiceProvider;
use Plenty\Modules\Cron\Services\CronContainer;
use ProtectedShops\Cron\ProtectedShopsCronHandler;
use ProtectedShops\Repositories\PsLegalTextRepository;
use ProtectedShops\Contracts\PsLegalTextContract;

class ProtectedShopsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->getApplication()->register(ProtectedShopsRouteServiceProvider::class);
    }

    /**
     * @param CronContainer $container
     * @param PsLegalTextRepository $psLegalTextRepository
     */
    public function boot(CronContainer $container, PsLegalTextRepository $psLegalTextRepository)
    {
        $container->add(CronContainer::DAILY, ProtectedShopsCronHandler::class);
        $this->getApplication()->bind(PsLegalTextContract::class, PsLegalTextRepository::class);

        $psLegalTextRepository->createPsLegalText(array(
            'legalText' => 'TermsConditions',
            'success' => false,
            'shouldSync' => false
        ));
        $psLegalTextRepository->createPsLegalText(array(
            'legalText' => 'CancellationRights',
            'success' => false,
            'shouldSync' => false
        ));
        $psLegalTextRepository->createPsLegalText(array(
            'legalText' => 'PrivacyPolicy',
            'success' => false,
            'shouldSync' => false
        ));
        $psLegalTextRepository->createPsLegalText(array(
            'legalText' => 'LegalDisclosure',
            'success' => false,
            'shouldSync' => false
        ));
    }
}

