<?php namespace Nicolasliu\Laravelfilemanager\controllers;

use Illuminate\Support\Facades\File;
use Nicolasliu\Laravelfilemanager\Events\FileIsRenaming;
use Nicolasliu\Laravelfilemanager\Events\FileWasRenamed;
use Nicolasliu\Laravelfilemanager\Events\FolderIsRenaming;
use Nicolasliu\Laravelfilemanager\Events\FolderWasRenamed;
use Nicolasliu\Laravelfilemanager\FileRecord;

/**
 * Class RenameController
 * @package Nicolasliu\Laravelfilemanager\controllers
 */
class RenameController extends LfmController
{
    /**
     * @return string
     */
    public function getRename()
    {
        $file_id = request('file');
        $new_name = $this->translateFromUtf8(trim(request('new_name')));
        $file = FileRecord::find($file_id);
        $file->filename = $new_name;
        $file->save();

        event(new FileWasRenamed($file_id, $new_name));

        return $this->success_response;
    }
}
