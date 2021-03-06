<?php namespace Nicolasliu\Laravelfilemanager\controllers;

use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Nicolasliu\Laravelfilemanager\Events\FileIsUploading;
use Nicolasliu\Laravelfilemanager\Events\FileWasUploaded;
use Nicolasliu\Laravelfilemanager\FileRecord;

/**
 * Class UploadController
 * @package Nicolasliu\Laravelfilemanager\controllers
 */
class UploadController extends LfmController
{
    /**
     * Upload an image/file and (for images) create thumbnail
     *
     * @param UploadRequest $request
     * @return string
     */
    public function upload()
    {
        $files = request()->file('upload');

        $error_bag = [];
        foreach (is_array($files) ? $files : [$files] as $file) {
            $validation_message = $this->uploadValidator($file);
            $new_filename = $this->proceedSingleUpload($file);

            if ($validation_message !== 'pass') {
                array_push($error_bag, $validation_message);
            } elseif ($new_filename == 'invalid') {
                array_push($error_bag, $response);
            }

        }

        if (is_array($files)) {
            $response = count($error_bag) > 0 ? $error_bag : $this->success_response;
        } else { // upload via ckeditor 'Upload' tab
            $response = $this->useFile($new_filename);
        }

        return $response;
    }

    private function proceedSingleUpload($file)
    {
        $validation_message = $this->uploadValidator($file);
        if ($validation_message !== 'pass') {
            return $validation_message;
        }

        $new_filename  = $this->getNewName($file);
        $new_file_path = parent::getCurrentPathWithoutName();

        try {
            $filerecord = new FileRecord();
            $filerecord->owner = 'private';
            $filerecord->owner_id = parent::getUserSlug();
            $filerecord->uploader_id = $filerecord->owner_id;
            $filerecord->filename = $file->getClientOriginalName();
            $filerecord->realname = $new_filename;
            $filerecord->realpath = $new_file_path;
            $filerecord->filesize = $file->getClientSize();
            $filerecord->mimetype = $file->getClientMimeType();
            $filerecord->directory = false;
            $filerecord->save();

            $new_file_path = parent::getCurrentPath($new_filename);

            if ($this->fileIsImage($file)) {
                Image::make($file->getRealPath())
                    ->orientate() //Apply orientation from exif data
                    ->save($new_file_path, 90);

                $this->makeThumb($new_filename);
            } else {
                File::move($file->path(), $new_file_path);
            }

        } catch (\Exception $e) {
            return $this->error('invalid');
        }
        event(new FileWasUploaded(realpath($new_file_path)));

        return $new_filename;
    }

    private function uploadValidator($file)
    {
        $is_valid = false;
        $force_invalid = false;

        if (empty($file)) {
            return $this->error('file-empty');
        } elseif (!$file instanceof UploadedFile) {
            return $this->error('instance');
        } elseif ($file->getError() == UPLOAD_ERR_INI_SIZE) {
            $max_size = ini_get('upload_max_filesize');
            return $this->error('file-size', ['max' => $max_size]);
        } elseif ($file->getError() != UPLOAD_ERR_OK) {
            return 'File failed to upload. Error code: ' . $file->getError();
        }

        $new_filename = $this->getNewName($file);

        if (File::exists(parent::getCurrentPath($new_filename))) {
            return $this->error('file-exist');
        }

        $mimetype = $file->getMimeType();

        // size to kb unit is needed
        $file_size = $file->getSize() / 1000;
        $type_key = $this->currentLfmType();

        if (config('lfm.should_validate_mime')) {
            $mine_config = 'lfm.valid_' . $type_key . '_mimetypes';
            $valid_mimetypes = config($mine_config, []);
            if (false === in_array($mimetype, $valid_mimetypes)) {
                return $this->error('mime') . $mimetype;
            }
        }

        if (config('lfm.should_validate_size')) {
            $max_size = config('lfm.max_' . $type_key . '_size', 0);
            if ($file_size > $max_size) {
                return $this->error('size') . $mimetype;
            }
        }

        return 'pass';
    }

    private function getNewName($file)
    {
        $new_filename = $this->translateFromUtf8(trim(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)));

        if (config('lfm.rename_file') === true) {
            $new_filename = uniqid();
        } elseif (config('lfm.alphanumeric_filename') === true) {
            $new_filename = preg_replace('/[^A-Za-z0-9\-\']/', '_', $new_filename);
        }

        return $new_filename . '.' . $file->getClientOriginalExtension();
    }

    private function makeThumb($new_filename)
    {
        // create thumb folder
        $this->createFolderByPath(parent::getThumbPath());

        // create thumb image
        Image::make(parent::getCurrentPath($new_filename))
            ->fit(config('lfm.thumb_img_width', 200), config('lfm.thumb_img_height', 200))
            ->save(parent::getThumbPath($new_filename));
    }

    private function useFile($new_filename)
    {
        $file = parent::getFileUrl($new_filename);

        return "<script type='text/javascript'>

        function getUrlParam(paramName) {
            var reParam = new RegExp('(?:[\?&]|&)' + paramName + '=([^&]+)', 'i');
            var match = window.location.search.match(reParam);
            return ( match && match.length > 1 ) ? match[1] : null;
        }

        var funcNum = getUrlParam('CKEditorFuncNum');

        var par = window.parent,
            op = window.opener,
            o = (par && par.CKEDITOR) ? par : ((op && op.CKEDITOR) ? op : false);

        if (op) window.close();
        if (o !== false) o.CKEDITOR.tools.callFunction(funcNum, '$file');
        </script>";
    }
}
