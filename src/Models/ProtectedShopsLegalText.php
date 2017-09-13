<?php

namespace ProtectedShops\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

/**
 * Class PsLegalText
 *
 * @property int     $id
 * @property string  $legalText
 * @property int     $updated
 * @property boolean $success
 * @property boolean $shouldSync
 */
class ProtectedShopsLegalText extends Model
{
    public $id;
    public $legalText;
    public $updated;
    public $success;
    public $shouldSync;

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return 'ProtectedShops::ProtectedShopsLegalText';
    }
}