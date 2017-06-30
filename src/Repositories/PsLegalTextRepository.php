<?php

namespace ProtectedShops\Repositories;

use Plenty\Exceptions\ValidationException;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use ProtectedShops\Contracts\PsLegalTextContract;
use ProtectedShops\Models\PsLegalText;
use ProtectedShops\Validators\PsLegalTextValidator;
use Plenty\Modules\Frontend\Services\AccountService;

class PsLegalTextRepository implements PsLegalTextContract
{
    /**
     * @var AccountService
     */
    private $accountService;

    /**
     * UserSession constructor.
     * @param AccountService $accountService
     */
    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    public function createPsLegalText(array $data): PsLegalText
    {
        $database = pluginApp(DataBase::class);

        /**
         * @var PsLegalText $psLegalText
         */
        $psLegalText = pluginApp(PsLegalText::class);
        $psLegalText->legalText = $data['legalText'];
        $psLegalText->success = $data['success'];
        $psLegalText->shouldSync = $data['shouldSync'];
        $psLegalText->updated = time();

        $database->save($psLegalText);

        return $psLegalText;
    }

    /**
     * List all items of the To Do list
     *
     * @return PsLegalText[]
     */
    public function getPsLegalTexts(): array
    {
        $database = pluginApp(DataBase::class);

        return $database->query(PsLegalText::class)->get();
    }

    /**
     * Update the status of the item
     *
     * @param int $id.
     * @param array $data
     * @return PsLegalText
     */
    public function updatePsLegalText($id, $data): PsLegalText
    {
        /**
         * @var DataBase $database
         */
        $database = pluginApp(DataBase::class);

        $psLegalText = $database->query(PsLegalText::class)
            ->where('id', '=', $id)
            ->get();

        $psLegalText = $psLegalText[0];
        $psLegalText->updated = time();
        $psLegalText->success = $data['success'];
        $psLegalText->shouldSync = $data['shouldSync'];
        $database->save($psLegalText);

        return $psLegalText;
    }
}