<?php namespace Nicolasliu\Laravelfilemanager\controllers;

use Nicolasliu\Laravelfilemanager\controllers\Controller;
use Intervention\Image\Facades\Image;
use Nicolasliu\Laravelfilemanager\Events\ImageIsCropping;
use Nicolasliu\Laravelfilemanager\Events\ImageWasCropped;
use Nicolasliu\Laravelfilemanager\FileRecord;

/**
 * Class CropController
 * @package Nicolasliu\Laravelfilemanager\controllers
 */
class CropController extends LfmController
{
    /**
     * Show crop page
     *
     * @return mixed
     */
    public function getCrop()
    {
        $working_dir = request('working_dir');
        $img_id = request('img');
        $img_url = parent::getFileUrl(request('img'));

        return view('laravel-filemanager::crop')
            ->with(compact('working_dir', 'img_id', 'img_url'));
    }


    /**
     * Crop the image (called via ajax)
     */
    public function getCropimage()
    {
        $img_id      = request('img');
        $dataX      = request('dataX');
        $dataY      = request('dataY');
        $dataHeight = request('dataHeight');
        $dataWidth  = request('dataWidth');
        $image = FileRecord::find($img_id);
        $image_path = $image->realpath . DIRECTORY_SEPARATOR . $image->realname;
        // crop image
        Image::make($image_path)
            ->crop($dataWidth, $dataHeight, $dataX, $dataY)
            ->save($image_path);
        event(new ImageIsCropping($image_path));

        // make new thumbnail
        Image::make($image_path)
            ->fit(config('lfm.thumb_img_width', 200), config('lfm.thumb_img_height', 200))
            ->save(parent::getThumbPath(parent::getName($image_path)));
        event(new ImageWasCropped($image_path));
    }
}
