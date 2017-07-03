<?php

namespace ProtectedShops\Repositories;

use Plenty\Exceptions\ValidationException;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use ProtectedShops\Contracts\ProtectedShopsLegalTextContract;
use ProtectedShops\Models\ProtectedShopsLegalText;
use ProtectedShops\Validators\PsLegalTextValidator;
use Plenty\Modules\Frontend\Services\AccountService;

class ProtectedShopsLegalTextRepository implements ProtectedShopsLegalTextContract
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

    public function createPsLegalText(array $data): ProtectedShopsLegalText
    {
        $database = pluginApp(DataBase::class);

        /**
         * @var PsLegalText $psLegalText
         */
        $psLegalText = pluginApp(ProtectedShopsLegalText::class);
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
     * @return ProtectedShopsLegalText[]
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
     * @return ProtectedShopsLegalText
     */
    public function updatePsLegalText($id, $data): ProtectedShopsLegalText
    {
        /**
         * @var DataBase $database
         */
        $database = pluginApp(DataBase::class);

        $psLegalText = $database->query(ProtectedShopsLegalText::class)
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