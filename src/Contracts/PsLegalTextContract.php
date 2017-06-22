<?php

namespace ProtectedShops\Contracts;

use ProtectedShops\Models\PsLegalText;

/**
 * Class ToDoRepositoryContract
 * @package ToDoList\Contracts
 */
interface PsLegalTextContract
{
    /**
     * Add PsLegalText to the table
     *
     * @param array $data
     * @return PsLegalText
     */
    public function createPsLegalText(array $data): PsLegalText;

    /**
     * List all tasks of the To Do list
     *
     * @return PsLegalText[]
     */
    public function getPsLegalTexts(): array;

    /**
     * Update the PsLegalText
     *
     * @param int $id
     * @return PsLegalText
     */
    public function updatePsLegalText($id): PsLegalText;

    /**
     * Delete PsLegalText
     *
     * @param int $id
     * @return PsLegalText
     */
    public function deleteTask($id): PsLegalText;
}
