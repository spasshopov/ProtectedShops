<?php

namespace ProtectedShops\Contracts;

use ProtectedShops\Models\ProtectedShopsLegalText;

/**
 * Class ToDoRepositoryContract
 * @package ToDoList\Contracts
 */
interface ProtectedShopsLegalTextContract
{
    /**
     * Add PsLegalText to the table
     *
     * @param array $data
     * @return ProtectedShopsLegalText
     */
    public function createPsLegalText(array $data): ProtectedShopsLegalText;

    /**
     * List all tasks of the To Do list
     *
     * @return ProtectedShopsLegalText[]
     */
    public function getPsLegalTexts(): array;

    /**
     * Update the PsLegalText
     *
     * @param int $id
     * @param array $data
     * @return ProtectedShopsLegalText
     */
    public function updatePsLegalText($id, $data): ProtectedShopsLegalText;
}

