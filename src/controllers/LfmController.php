<?php namespace Nicolasliu\Laravelfilemanager\controllers;

use Nicolasliu\Laravelfilemanager\traits\LfmHelpers;

/**
 * Class LfmController
 * @package Nicolasliu\Laravelfilemanager\controllers
 */
class LfmController extends Controller
{
    use LfmHelpers;

    protected $success_response = 'OK';

    public function __construct()
    {
        if (!$this->isProcessingImages() && !$this->isProcessingFiles()) {
            throw new \Exception('unexpected type parameter');
        }
    }

    /**
     * Show the filemanager
     *
     * @return mixed
     */
    public function show()
    {
        return view('laravel-filemanager::index');
    }

    public function getErrors()
    {
        $arr_errors = [];

        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            array_push($arr_errors, trans('laravel-filemanager::lfm.message-extension_not_found'));
        }

        return $arr_errors;
    }
}
