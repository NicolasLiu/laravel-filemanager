<?php namespace Nicolasliu\Laravelfilemanager\controllers;

use Illuminate\Support\Facades\File;
use Nicolasliu\Laravelfilemanager\FileRecord;

/**
 * Class FolderController
 * @package Nicolasliu\Laravelfilemanager\controllers
 */
class FolderController extends LfmController
{
    /**
     * Get list of folders as json to populate treeview
     *
     * @return mixed
     */
    public function getFolders()
    {
        $folder_types = [];
        $root_folders = [];

        if (parent::allowMultiUser()) {
            $folder_types['user'] = 'root';
        }

        if ((parent::allowMultiUser() && parent::enabledShareFolder()) || !parent::allowMultiUser()) {
            $folder_types['share'] = 'shares';
        }

        foreach ($folder_types as $folder_type => $lang_key) {
            $root_folder_path = parent::getRootFolderPath($folder_type);

            array_push($root_folders, (object)[
                'name' => trans('laravel-filemanager::lfm.title-' . $lang_key),
                'path' => parent::getInternalPath($root_folder_path),
                'children' => parent::getDirectories($root_folder_path),
                'has_next' => !($lang_key == end($folder_types))
            ]);
        }

        return view('laravel-filemanager::tree')
            ->with(compact('root_folders'));
    }


    /**
     * Add a new folder
     *
     * @return mixed
     */
    public function getAddfolder()
    {
        $folder_name = request('name');
        $path = parent::getCurrentPath($folder_name);
        $realpath = parent::getCurrentPath();

        if (empty($folder_name)) {
            return $this->error('folder-name');
        } elseif (File::exists($path)) {
            return $this->error('folder-exist');
        } elseif (config('lfm.alphanumeric_directory') && preg_match('/[^\w-]/i', $folder_name)) {
            return $this->error('folder-alnum');
        } else {
            $this->createFolderByPath($path);
            $filerecord = new FileRecord();
            $filerecord->owner = 'private';
            $filerecord->owner_id = parent::getUserSlug();
            $filerecord->uploader_id = $filerecord->owner_id;
            $filerecord->filename = $folder_name;
            $filerecord->realname = $folder_name;
            $filerecord->realpath = $realpath;
            $filerecord->filesize = 0;
            $filerecord->mimetype = 'folder';
            $filerecord->directory = true;
            $filerecord->save();

            return $this->success_response;
        }
    }
}
