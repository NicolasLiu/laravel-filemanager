<?php namespace Nicolasliu\Laravelfilemanager\controllers;

use Illuminate\Support\Facades\File;
use Nicolasliu\Laravelfilemanager\Events\FileIsRenaming;
use Nicolasliu\Laravelfilemanager\Events\FileWasRenamed;
use Nicolasliu\Laravelfilemanager\Events\FolderIsRenaming;
use Nicolasliu\Laravelfilemanager\Events\FolderWasRenamed;

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
        $old_name = $this->translateFromUtf8(request('file'));
        $new_name = $this->translateFromUtf8(trim(request('new_name')));

        $old_file = parent::getCurrentPath($old_name);

        if (empty($new_name)) {
            if (File::isDirectory($old_file)) {
                return $this->error('folder-name');
            } else {
                return $this->error('file-name');
            }
        }

        if (!File::isDirectory($old_file)) {
            $extension = File::extension($old_file);
            $new_name = str_replace('.' . $extension, '', $new_name) . '.' . $extension;
        }

        $new_file = parent::getCurrentPath($new_name);

        if (File::isDirectory($old_file)) {
            event(new FolderIsRenaming($old_file, $new_file));
        } else {
            event(new FileIsRenaming($old_file, $new_file));
        }

        if (config('lfm.alphanumeric_directory') && preg_match('/[^\w-]/i', $new_name)) {
            return $this->error('folder-alnum');
        } elseif (File::exists($new_file)) {
            return $this->error('rename');
        }

        if (File::isDirectory($old_file)) {
            File::move($old_file, $new_file);
            event(new FolderWasRenamed($old_file, $new_file));
            return $this->success_response;
        }

        if ($this->fileIsImage($old_file)) {
            File::move(parent::getThumbPath($old_name), parent::getThumbPath($new_name));
        }

        File::move($old_file, $new_file);

        event(new FileWasRenamed($old_file, $new_file));

        return $this->success_response;
    }
}
