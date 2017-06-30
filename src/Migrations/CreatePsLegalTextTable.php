<?php

namespace ProtectedShops\Migrations;

use ProtectedShops\Models\PsLegalText;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

class CreatePsLegalTextTable
{
    /**
     * @param Migrate $migrate
     */
    public function run(Migrate $migrate)
    {
        $migrate->deleteTable(PsLegalText::class);
        $migrate->createTable(PsLegalText::class);
    }
}