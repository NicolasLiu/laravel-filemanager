<?php namespace Nicolasliu\Laravelfilemanager\controllers;

/**
 * Class DemoController
 * @package Nicolasliu\Laravelfilemanager\controllers
 */
class DemoController extends LfmController
{

    /**
     * @return mixed
     */
    public function index()
    {
        return view('laravel-filemanager::demo');
    }
}
