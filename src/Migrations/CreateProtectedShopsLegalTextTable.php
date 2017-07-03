<?php

namespace ProtectedShops\Migrations;

use ProtectedShops\Models\ProtectedShopsLegalText;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

class CreateProtectedShopsLegalTextTable
{
    /**
     * @param Migrate $migrate
     */
    public function run(Migrate $migrate)
    {
        $migrate->createTable(ProtectedShopsLegalText::class);
    }
}