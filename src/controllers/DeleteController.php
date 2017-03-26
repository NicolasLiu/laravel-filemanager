<?php namespace Nicolasliu\Laravelfilemanager\controllers;

use Illuminate\Support\Facades\File;
use Nicolasliu\Laravelfilemanager\Events\FileIsDeleting;
use Nicolasliu\Laravelfilemanager\Events\FileWasDeleted;
use Nicolasliu\Laravelfilemanager\FileRecord;

/**
 * Class CropController
 * @package Nicolasliu\Laravelfilemanager\controllers
 */
class DeleteController extends LfmController
{
    /**
     * Delete image and associated thumbnail
     *
     * @return mixed
     */
    public function getDelete()
    {
        $delete_id = request('items');
        $fileRecord = FileRecord::find($delete_id);
        if ($fileRecord->directory == true) {
            $this->deleteDir($fileRecord);
        } else {
            $fileRecord->delete();
        }

        event(new FileWasDeleted($fileRecord->realpath . DIRECTORY_SEPARATOR . $fileRecord->filename));
        return $this->success_response;
    }

    private function deleteDir($dir)
    {
        $path = $dir->realpath . DIRECTORY_SEPARATOR . $dir->realname;
        $files = FileRecord::where('realpath', $path)->get();
        foreach ($files as $file) {
            if ($file->directory == true) {
                $this->deleteDir($file);
            } else {
                $file->delete();
            }
        }
        $dir->delete();
    }
}
