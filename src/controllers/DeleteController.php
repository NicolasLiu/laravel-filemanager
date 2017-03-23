<?php namespace Nicolasliu\Laravelfilemanager\controllers;

use Illuminate\Support\Facades\File;
use Nicolasliu\Laravelfilemanager\Events\FileIsDeleting;
use Nicolasliu\Laravelfilemanager\Events\FileWasDeleted;

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
        $name_to_delete = request('items');

        $file_to_delete = parent::getCurrentPath($name_to_delete);
        $thumb_to_delete = parent::getThumbPath($name_to_delete);

        event(new FileIsDeleting($file_to_delete));

        if (is_null($name_to_delete)) {
            return $this->error('folder-name');
        }

        if (!File::exists($file_to_delete)) {
            return $this->error('folder-not-found', ['folder' => $file_to_delete]);
        }

        if (File::isDirectory($file_to_delete)) {
            if (!parent::directoryIsEmpty($file_to_delete)) {
                return $this->error('delete-folder');
            }

            File::deleteDirectory($file_to_delete);

            return $this->success_response;
        }

        if ($this->fileIsImage($file_to_delete)) {
            File::delete($thumb_to_delete);
        }

        File::delete($file_to_delete);

        event(new FileWasDeleted($file_to_delete));

        return $this->success_response;
    }
}
