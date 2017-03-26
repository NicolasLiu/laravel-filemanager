<?php namespace Nicolasliu\Laravelfilemanager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;



/**
 * Class File
 * @package Nicolasliu\Laravelfilemanager
 */
class FileRecord extends Model {
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $table = 'files';
}
