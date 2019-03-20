<?php

namespace CodePi\Base\Libraries\Upload;

use CodePi\Base\Libraries\Attachments\Resolutions;
use CodePi\Base\Libraries\Attachments\Attachments;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Attachments\Commands\AddAttachment;
use Image;
use CodePi\Base\Libraries\Download;

class PiUpload {

    public $filename = 'filename';
    public $size = 20 * 1024 * 1024;
    public $isFlowUpload = false;
    public $isFlowAdvanceUpload = false;
    public $extensions = array(
        'jpeg',
        'jpg',
        'PNG',
        'png',
        'GIF',
        'gif'
    );
    public $imageExtensions = array(
        'jpeg',
        'jpg',
        'png',
        'gif',
        'bmp',
    );
    public $container = '/Uploads/users_logo/';

    function save() {

        if (isset($_FILES[$this->filename]['tmp_name'])) {
            if (!$this->isFlowUpload) {
                $upload = UploadType::Factory('Regular');
            } else {
                $upload = UploadType::Factory('Flow');
            }
            $files = $_FILES[$this->filename];
        } else {
            if (!empty($_SERVER ['HTTP_X_FILE_NAME'])) {
                $files = $_SERVER ['HTTP_X_FILE_NAME'];
            } else {
                $files = $_REQUEST [$this->filename];
            }
            if (!$this->isFlowUpload) {
                $upload = UploadType::Factory('Stream');
            } else {
                $upload = UploadType::Factory('Flow');
            }
        }

        $upload->setSize($this->size);
        $upload->setAllowedTypes($this->extensions);
        $upload->setContainer($this->container);

        $tmpfile = $upload->save($files);
        return $tmpfile;
    }
    
    
    function imageProcess($source, $original_name, $type = 'tactic_types', $attachmentId = '') {
        
        $resolution = Resolutions::DeatailByName($type);

        // print_r($resolution); die;
        if ($resolution && file_exists($source)) {

            $container = $resolution->container;
            $settings = json_decode($resolution->settings);

            $listSource = array();
            $delList = array();

            $source_parts = pathinfo($source);

            $listSource [] = array(
                'path' => $source,
                'name' => $resolution->folders . "/" . $source_parts ['filename'] . "." . $source_parts ['extension']
            );
            $delList [] = $source;

            if (in_array(strtolower($source_parts ['extension']), $this->imageExtensions)) {


                foreach ($settings as $size => $options) {

                    $new_filename = $source_parts ['filename'] . "_" . $size . "." . $source_parts ['extension'];

                    $destination_path = $source_parts ['dirname'] . "/" . $new_filename;
                    $delList [] = $destination_path;
                    $listSource [] = array(
                        'path' => $destination_path,
                        'name' => $resolution->folders . "/" . $new_filename
                    );


                    Image::make($source)->resize($options->width, null, function ($constraint) {
                        $constraint->aspectRatio();
                    })->save($destination_path);
                }
            }
            return true;
            if ($resolution->is_cloud == true) {
                $cloud = new Cloud ();
                $isCloudStatus = $cloud->uploadMultipleObjects($container, $listSource);
                if($resolution->id == 1){
                    $this->updateAttachmentStatus();
                }
                array_map('unlink', $delList);
            }

            $attachmentDetails = array(
                'id' => $attachmentId,
                'db_name' => $source_parts ['basename'],
                'original_name' => $original_name,
                'screen_name' => $original_name,
                'resolutions_id' => $resolution->id
            );

            $command = new AddAttachment($attachmentDetails);

            return CommandFactory::getCommand($command,true); 
        } else {
            return false;
        }
    }


    /**
     *  update status of Attaachments.
     * 
     */
    function updateAttachmentStatus() {
        $objAttachments = new Attachments;
        $attachments = $objAttachments->where('status', 'local')->get();
        $objAttachments = new Attachments;
        foreach ($attachments as $key => $value) {
            $objAttachments->where('id', $value->id)
                    ->update(['status' => 'cloud']);
        }
    }

    function downloadUrl($filename, $originalname, $reolutionId) {
        
    }


    /**
     *  @param $image, $container, $type
     *  get cloud urls.
     *  @return url
     * 
     */
    function cloudUrl($image, $container = 'tactic_types', $type = 'small') {

        $resolution = Resolutions::DeatailByName($container);

        if ($resolution && !empty($image)) {
            $container = $resolution->container;
            $settings = json_decode($resolution->settings);
            $imgInfo = pathinfo($image);

            if ($type == 'original' || !in_array(strtolower($imgInfo['extension']), $this->imageExtensions)) {
                $thumbName = $resolution->folders . "/" . $imgInfo['filename'] . "." . $imgInfo['extension'];
            } else {
                $thumbName = $resolution->folders . "/" . $imgInfo['filename'] . "_" . $type . "." . $imgInfo['extension'];
            }
            //$thumbName=$image;
            return $resolution->url . "/" . $thumbName;
        } else {
            return false;
        }
    }

    function downloadAsset($resolutionId, $fileName, $originalName) {

        $resolutionData = Resolutions::DeatailById($resolutionId);

        if ($resolutionData->is_cloud == true) {
            $cloud = new Cloud ();
            $path = !empty($resolutionData->folders) ? $resolutionData->folders . '/' . $fileName : '';

            $url = $cloud->downloadObject($resolutionData->container, $path);
            return Download::start($url, $originalName);
        } else if ($resolutionData->is_cloud == false) {
            $path = !empty($resolutionData->folders) ? str_replace('/', '\\', $resolutionData->folders) . '\\' . $fileName : $fileName;
            return Download::start($path, $originalName);
        }
    }

    /**
     * Save the document to attachment table
     * @param array $source
     * @param string $original_name
     * @param string $type
     * @return object|boolen
     */
    function piSaveDocument($source, $original_name, $type = 'briefs') {

        $resolution = Resolutions::DeatailByName($type);

        if ($resolution && file_exists($source)) {
            $source_parts = pathinfo($source);
            $attachmentDetails = array(
                'db_name' => $source_parts ['basename'],
                'original_name' => $original_name,
                'screen_name' => $original_name,
                'resolutions_id' => $resolution->id
            );

            $command = new AddAttachment($attachmentDetails);

            return CommandFactory::getCommand($command,true);
        } else {
            return false;
        }
    }

    function moveDocToCloud($source, $type = 'briefs') {

        $resolution = Resolutions::DeatailByName($type);

        if ($resolution && file_exists($source)) {

            $container = $resolution->container;

            $listSource = array();
            $delList = array();

            $source_parts = pathinfo($source);

            $listSource [] = array(
                'path' => $source,
                'name' => $resolution->folders . "/" . $source_parts ['filename'] . "." . $source_parts ['extension']
            );
            $delList [] = $source;      
           
               $cloud = new Cloud ();

               $isCloudStatus = $cloud->uploadMultipleObjects($container, $listSource);            
               array_map('unlink', $delList);
                    
           return true;
        } else {
            return false;
        }
    }

}
