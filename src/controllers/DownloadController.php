<?php
namespace Nicolasliu\Laravelfilemanager\controllers;
use Nicolasliu\Laravelfilemanager\FileRecord;

/**
 * Class DownloadController
 * @package Nicolasliu\Laravelfilemanager\controllers
 */
class DownloadController extends LfmController
{
    /**
     * Download a file
     *
     * @return mixed
     */
    public function getDownload($id)
    {
        $file = FileRecord::find($id);
        return response()->download($file->realpath.DIRECTORY_SEPARATOR.$file->realname,
            $file->filename);
    }
}
