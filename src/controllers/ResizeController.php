<?php namespace Nicolasliu\Laravelfilemanager\controllers;

use Faker\Provider\File;
use Intervention\Image\Facades\Image;
use Nicolasliu\Laravelfilemanager\Events\ImageIsResizing;
use Nicolasliu\Laravelfilemanager\Events\ImageWasResized;
use Nicolasliu\Laravelfilemanager\FileRecord;

/**
 * Class ResizeController
 * @package Nicolasliu\Laravelfilemanager\controllers
 */
class ResizeController extends LfmController
{
    /**
     * Dipsplay image for resizing
     *
     * @return mixed
     */
    public function getResize()
    {
        $ratio = 1.0;
        $image_id = request('img');
        $image = FileRecord::find($image_id);
        $original_image = Image::make($image->realpath . DIRECTORY_SEPARATOR . $image->realname);
        $original_width = $original_image->width();
        $original_height = $original_image->height();

        $scaled = false;

        if ($original_width > 600) {
            $ratio = 600 / $original_width;
            $width = $original_width * $ratio;
            $height = $original_height * $ratio;
            $scaled = true;
        } else {
            $width = $original_width;
            $height = $original_height;
        }

        if ($height > 400) {
            $ratio = 400 / $original_height;
            $width = $original_width * $ratio;
            $height = $original_height * $ratio;
            $scaled = true;
        }

        return view('laravel-filemanager::resize')
            ->with('img_url', parent::getFileUrl($image_id))
            ->with('img_id', $image_id)
            ->with('height', number_format($height, 0))
            ->with('width', $width)
            ->with('original_height', $original_height)
            ->with('original_width', $original_width)
            ->with('scaled', $scaled)
            ->with('ratio', $ratio);
    }

    public function performResize()
    {
        $img_id = request('img');
        $dataX = request('dataX');
        $dataY = request('dataY');
        $height = request('dataHeight');
        $width = request('dataWidth');

        $image = FileRecord::find($img_id);
        $image_path = $image->realpath . DIRECTORY_SEPARATOR . $image->realname;

        try {
            Image::make($image_path)->resize($width, $height)->save();
            event(new ImageWasResized($image_path));
            return $this->success_response;
        } catch (Exception $e) {
            return "width : " . $width . " height: " . $height;
            return $e;
        }
    }
}
