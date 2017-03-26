<?php
namespace Nicolasliu\Laravelfilemanager\controllers;

use Illuminate\Http\Request;
use Nicolasliu\Laravelfilemanager\FileRecord;

/**
 * Class DownloadController
 * @package Nicolasliu\Laravelfilemanager\controllers
 */
class ImageController extends LfmController
{
    /**
     * Get an image
     *
     * @return mixed
     */
    public function preview($id)
    {
        $file = FileRecord::find($id);
        return response()->file($file->realpath . DIRECTORY_SEPARATOR . $file->realname);
    }

    /**
     * Get an image thumb
     *
     * @return mixed
     */
    public function thumb($id)
    {
        $fileRecord = FileRecord::find($id);
        $url = base_path(
            $this->getPathPrefix('thumb') .
            $this->getInternalPath($fileRecord->realpath . DIRECTORY_SEPARATOR . $fileRecord->realname));
        return response()->file($url);
    }
}
