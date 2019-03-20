<?php

namespace CodePi\Attachments\Utils;

use Image;

class ImageProcess {

    public $width = 400;
    public $height = 400;
    public $source;
    public $desitnationPath;
    public $imageExtensions = array('jpeg', 'jpg', 'png', 'gif', 'bmp');

    public function __construct($source, $desitnationPath, $width, $height) {

        $this->source = $source;
        $this->desitnationPath = $desitnationPath;
        $this->setWidth($width);
        $this->setHeight($height);
    }

    function getSourceParts($source) {
        return pathinfo($source);
    }

    function setAllowedExtension($allowedExtensions) {
        $this->imageExtensions = $allowedExtensions;
    }

    function setWidth($width) {
        $this->width = $width;
    }

    function getWidth() {
        return $this->width;
    }

    function setHeight($height) {
        $this->height = $height;
    }

    function getHeight() {
        return $this->height;
    }

    function process() {
        $source_parts = $this->getSourceParts($this->source);
        if (in_array(strtolower($source_parts ['extension']), $this->imageExtensions)) {
            $result = Image::make($this->source)->resize($this->getWidth(), null, function ($constraint) {
                        $constraint->aspectRatio();
                    })->save($this->desitnationPath);
            return $result;
        } else {
            return false;
        }
    }

}
