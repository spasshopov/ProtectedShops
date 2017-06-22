<?php

namespace ProtectedShops\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

/**
 * Class ToDo
 *
 * @property int     $id
 * @property string  $legalText
 * @property int     $updated
 * @property boolean $success
 */
class PsLegalText extends Model
{
    public $id;
    public $legalText;
    public $updated;
    public $success;

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return 'ProtectedShopsForPlenty::PsLegalText';
    }
}