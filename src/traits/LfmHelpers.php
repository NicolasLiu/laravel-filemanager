<?php

namespace Nicolasliu\Laravelfilemanager\traits;

use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Nicolasliu\Laravelfilemanager\FileRecord;

trait LfmHelpers
{
    /*****************************
     ***       Path / Url      ***
     *****************************/

    private $ds = '/';

    public function getThumbPath($image_name = null)
    {
        return $this->getCurrentPath($image_name, 'thumb');
    }

    public function getCurrentPath($file_name = null, $is_thumb = null)
    {
        $path = $this->composeSegments($is_thumb, $file_name);

        $path = $this->translateToOsPath($path);

        return base_path($path);
    }

    public function getCurrentPathWithoutName($is_thumb = null)
    {
        $path = $this->composeSegmentsWithoutName($is_thumb);

        $path = $this->translateToOsPath($path);

        return base_path($path);
    }

    public function getThumbUrl($image_id = null)
    {
        return $this->getFileUrl($image_id, 'thumb');
    }

    public function getFileUrl($id, $is_thumb = null)
    {
        if (empty($is_thumb)) {
            return url(config('lfm.prefix') . '/download/' . $id);
        } else {
            return url(config('lfm.prefix') . '/thumb/' . $id);
        }

    }
    public function getPreviewUrl($id)
    {
        return url(config('lfm.prefix') . '/preview/' . $id);
    }

    private function composeSegments($is_thumb, $file_name)
    {
        $full_path = implode($this->ds, [
            $this->getPathPrefix($is_thumb),
            $this->getFormatedWorkingDir(),
            $file_name
        ]);

        $full_path = $this->removeDuplicateSlash($full_path);
        $full_path = $this->translateToLfmPath($full_path);

        return $this->removeLastSlash($full_path);
    }

    private function composeSegmentsWithoutName($is_thumb)
    {
        $full_path = implode($this->ds, [
            $this->getPathPrefix($is_thumb),
            $this->getFormatedWorkingDir()
        ]);

        $full_path = $this->removeDuplicateSlash($full_path);
        $full_path = $this->translateToLfmPath($full_path);

        return $this->removeLastSlash($full_path);
    }

    public function getPathPrefix($is_thumb = null)
    {

        $p = config('lfm.base_directory', 'storage');

        if ($is_thumb === 'thumb') {
            $prefix = $p . DIRECTORY_SEPARATOR . config('lfm.thumb_folder_name', 'thumbs');
        } else {
            $prefix = $p . DIRECTORY_SEPARATOR . config('lfm.files_folder_name', 'files');
        }

        return $prefix;
    }

    private function canReadWorkingDir($dir)
    {

    }

    private function getFormatedWorkingDir()
    {
        $working_dir = request('working_dir');

        if (empty($working_dir)) {
            $default_folder_type = 'share';
            if ($this->allowMultiUser()) {
                $default_folder_type = 'user';
            }

            $working_dir = $this->rootFolder($default_folder_type);
        }

        return $this->removeFirstSlash($working_dir);
    }

    private function appendThumbFolderPath($is_thumb)
    {
        if (!$is_thumb) {
            return;
        }

        $thumb_folder_name = config('lfm.thumb_folder_name');
        //if user is inside thumbs folder there is no need
        // to add thumbs substring to the end of $url
        $in_thumb_folder = preg_match('/' . $thumb_folder_name . '$/i', $this->getFormatedWorkingDir());

        if (!$in_thumb_folder) {
            return $thumb_folder_name . $this->ds;
        }
    }

    public function rootFolder($type)
    {
        if ($type === 'user') {
            $folder_name = $this->getUserSlug();
        } else {
            $folder_name = config('lfm.shared_folder_name');
        }

        return $this->ds . $folder_name;
    }

    public function getRootFolderPath($type)
    {
        return base_path($this->getPathPrefix('dir') . $this->rootFolder($type));
    }

    public function getName($file)
    {
        $lfm_file_path = $this->getInternalPath($file);

        $arr_dir = explode($this->ds, $lfm_file_path);
        $file_name = end($arr_dir);

        return $file_name;
    }

    public function getInternalPath($full_path)
    {
        $full_path = $this->translateToLfmPath($full_path);
        $lfm_dir_start = strpos($full_path, $this->getPathPrefix());
        $working_dir_start = $lfm_dir_start + strlen($this->getPathPrefix());
        $lfm_file_path = $this->ds . substr($full_path, $working_dir_start);

        return $this->removeDuplicateSlash($lfm_file_path);
    }

    private function translateToOsPath($path)
    {
        if ($this->isRunningOnWindows()) {
            $path = str_replace($this->ds, '\\', $path);
        }
        return $path;
    }

    private function translateToLfmPath($path)
    {
        if ($this->isRunningOnWindows()) {
            $path = str_replace('\\', $this->ds, $path);
        }
        return $path;
    }

    private function removeDuplicateSlash($path)
    {
        return str_replace($this->ds . $this->ds, $this->ds, $path);
    }

    private function removeFirstSlash($path)
    {
        if (starts_with($path, $this->ds)) {
            $path = substr($path, 1);
        }

        return $path;
    }

    private function removeLastSlash($path)
    {
        // remove last slash
        if (ends_with($path, $this->ds)) {
            $path = substr($path, 0, -1);
        }

        return $path;
    }

    public function translateFromUtf8($input)
    {
        if ($this->isRunningOnWindows()) {
            $input = iconv('UTF-8', 'GB2312', $input);
        }

        return $input;
    }

    public function translateToUtf8($input)
    {
        if ($this->isRunningOnWindows()) {
            $input = iconv('GB2312', 'UTF-8', $input);
        }

        return $input;
    }


    /****************************
     ***   Config / Settings  ***
     ****************************/

    public function isProcessingImages()
    {
        return $this->currentLfmType() === 'image';
    }

    public function isProcessingFiles()
    {
        return $this->currentLfmType() === 'file';
    }

    public function currentLfmType($is_for_url = false)
    {
        $file_type = request('type', 'Images');

        if ($is_for_url) {
            return ucfirst($file_type);
        } else {
            return lcfirst(str_singular($file_type));
        }
    }

    public function allowMultiUser()
    {
        return config('lfm.allow_multi_user') === true;
    }

    public function enabledShareFolder()
    {
        return config('lfm.allow_share_folder') === true;
    }


    /****************************
     ***     File System      ***
     ****************************/

    public function getDirectories($path)
    {
        $arr_dir = [];
        $dirs = FileRecord::where('realpath', $path)->where('directory', true)->get();

        foreach ($dirs as $directory) {
            $directory_name = $directory->filename;

            $arr_dir[] = (object)[
                'id'   => $directory->id,
                'name' => $directory_name,
                'path' => $this->getInternalPath($directory->realpath . DIRECTORY_SEPARATOR . $directory_name)
            ];

        }

        return $arr_dir;
    }

    public function getFilesWithInfo($path)
    {
        $arr_files = [];
        $files = FileRecord::where('realpath', $path)->where('directory', false)->get();
        foreach ($files as $key => $file) {
            $file_name = $file->filename;
            $file_id = $file->id;
            if ($this->fileIsImage($file)) {
                $file_type = $file->mimetype;
                $icon = 'fa-image';
                $thumb = $this->getFileUrl($file_id, 'thumb');
                $preview = $this->getPreviewUrl($file_id);
            } else {
                $extension = strtolower(File::extension($file_name));
                $file_type = config('lfm.file_type_array.' . $extension) ?: 'File';
                $icon = config('lfm.file_icon_array.' . $extension) ?: 'fa-file';
                $thumb = null;
                $preview = null;
            }

            $arr_files[$key] = [
                'id' => $file_id,
                'name' => $file_name,
                'url' => $this->getFileUrl($file_id),
                'size' => $file->filesize,
                'updated' => $file->updated_at,
                'type' => $file_type,
                'icon' => $icon,
                'thumb' => $thumb,
                'preview' => $preview
            ];
        }

        return $arr_files;
    }

    public function createFolderByPath($path)
    {
        if (!File::exists($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
    }

    public function directoryIsEmpty($directory_path)
    {
        return count(File::allFiles($directory_path)) == 0;
    }

    public function fileIsImage($file)
    {
        if ($file instanceof UploadedFile) {
            $mime_type = $file->getMimeType();
        } else if ($file instanceof FileRecord) {
            $mime_type = $file->mimetype;
        } else {
            $mime_type = File::mimeType($file);
        }

        return starts_with($mime_type, 'image');
    }


    /****************************
     ***    Miscellaneouses   ***
     ****************************/

    public function getUserSlug()
    {
        $slug_of_user = config('lfm.user_field');

        return empty(auth()->user()) ? '' : auth()->user()->$slug_of_user;
    }

    public function error($error_type, $variables = [])
    {
        return trans('laravel-filemanager::lfm.error-' . $error_type, $variables);
    }

    public function humanFilesize($bytes, $decimals = 2)
    {
        $size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
    }

    public function isRunningOnWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}
