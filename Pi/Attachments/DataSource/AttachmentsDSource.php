<?php

namespace CodePi\Attachments\DataSource;

use CodePi\Base\Eloquent\Attachments;
use CodePi\Base\Eloquent\Resolutions;
use CodePi\Attachments\DataSource\DataSourceInterface\iAttachmentsDSource;
use CodePi\Attachments\Utils\UploadType;
use CodePi\Attachments\Utils\Cloud;
use CodePi\Attachments\Utils\ImageProcess;
use CodePi\Base\Libraries\Download;

use Exception;

class AttachmentsDSource implements iAttachmentsDSource {

    function uploadFiles($command) {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $params = $command->dataToArray();
        $this->createDirectory($params['container']);
        $upload = $this->getUploadFactory($command);
        $upload->setSize($params['size']);
        $upload->setAllowedTypes($params['extensions']);
        $upload->setContainer($params['container']);
        $tmpfile = $upload->save();
        return $tmpfile;
    }

    function getUploadFactory($command) {
        $params = $command->dataToArray();
        if (isset($params['flowChunkNumber']) && $params['flowChunkNumber'] > 0) {
            $upload = UploadType::Factory('Flow');
            $files = $_FILES[$params['filename']];
        } elseif ($_FILES[$params['filename']]['tmp_name']) {
            $upload = UploadType::Factory('Regular');
            $files = $_FILES[$params['filename']];
        } else {
            if (!empty($_SERVER ['HTTP_X_FILE_NAME'])) {
                $files = $_SERVER ['HTTP_X_FILE_NAME'];
            } else {
                $files = $_REQUEST [$params['filename']];
            }
            $upload = UploadType::Factory('Stream');
        }
        $upload->setFiles($files);
        return $upload;
    }

    /**
     * save Attachment Details.
     *
     * @param  $command
     * @return object $resultAttachment
     */
    function addAttachment($command) {

        $params = $command->dataToArray();
        $resultAttachment = false;
        \DB::beginTransaction();
        try {
            $resolution = Resolutions::DeatailByName($params['resolution_name']);
            $source = storage_path('app/public') . $resolution->local_url . '/' . $params['db_name'];
            
            
            if ($resolution && file_exists($source)) {
                dd($source);
                $params['resolutions_id'] = $resolution->id;
                $params['filesize'] = filesize($source);
                $objAttachment = new Attachments();
                //to avoid duplicate entry in version tacti image upload;
                $objResult = $objAttachment->where('db_name', $params['db_name'])->first();
                if (!empty($objResult) && isset($objResult->id) && $params['id'] == '') {
                    $params['id'] = $objResult->id;
                }

                $resultAttachment = $objAttachment->saveRecord($params);
                $listSource = [];
                if ($params['local_img_process'] == 'true' && $params['process_status'] == 0) {

                    $listSource = $this->doImageProcess($source, $resolution, $params);

                    if ($listSource) {
                        $objAttachment->where('id', $resultAttachment->id)->update(['process_status' => '2', 'images' => json_encode([$params['db_name']])]);
                    }
                    $listSource[] = array('path' => $source,
                        'name' => $resolution->folders . "/" . $params['db_name']
                    );
                } else {
                    $destination_path = storage_path('app/public') . $resolution->local_url . '/' . $params['db_name'];
                    $listSource[] = array(
                        'path' => storage_path('app/public') . $resolution->local_url . '/' . $params['db_name'],
                        'name' => $resolution->folders . "/" . $params['db_name']
                    );
                }

                if ($params['status'] == 'cloud' && !empty($listSource)) {
                    $this->doCloudProcess($source, $resolution, $listSource);
//                 $resultVal= $this->getImageUrl($resultAttachment->id,$params['resolution_name']);
//                  if(!empty($resultVal)) {
//                      return $resultVal[0];  
//                   } else {
//                       return false;
//                  }
                }
            }
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }
        return $resultAttachment;
    }

    function doImageProcess($source, $resolution, $params) {
        $settings = json_decode($resolution->settings);
        $source_parts = pathinfo($source);
        $listSource = [];

        if (in_array(strtolower($source_parts ['extension']), $params['imageExtensions'])) {

            foreach ($settings as $size => $options) {
                $new_filename = $source_parts ['filename'] . "_" . $size . "." . $source_parts ['extension'];
                $destination_path = $source_parts ['dirname'] . "/" . $new_filename;
                $delList [] = $destination_path;
                $objImageProcess = new ImageProcess($source, $destination_path, $options->width, NULL);
                $objImageProcess->process();
                $listSource [] = array(
                    'path' => $destination_path,
                    'name' => $resolution->folders . "/" . $new_filename
                );
            }
            return $listSource;
        }
    }

    function doCloudProcess($source, $resolution, $listSource) {
        $delList = [];
        foreach ($listSource as $value) {
            $delList[] = $value['path'];
        }
        $container = $resolution->container;
        $cloud = new Cloud ();
        $isCloudStatus = $cloud->uploadMultipleObjects($container, $listSource);

        if ($resolution->move_to_box) {
            $fileIds = $this->moveToBox($resolution, $listSource);
            $this->updateBoxIds($fileIds);
        }
        array_map('unlink', $delList);
    }

    function moveToBox($resolution, $listSource = []) {
        $box = new Box(config('poet.box_client_id'), config('poet.box_client_secret'), config('poet.box_redirect_uri'));
        $path = storage_path('app/public') . '/Uploads/';
        $box->setTokenPath($path);
        $token = $box->getAccessToken();
        if (empty($token)) {
            throw new Exception('token not available. To genrate token run the "setboxtoken" follwed by application url');
        }
        $parent_id = $resolution->box_folder_id;
        $file = [];
        foreach ($listSource as $value) {
            $name = basename($value['path']);
            $response = $box->put_file($value['path'], $name, $parent_id);
            if (isset($response['type']) && $response['type'] == 'error') {
                $file[$name]['error'] = json_encode($response);
                $file[$name]['id'] = 0;
                continue;
            }
            foreach ($response['entries'] as $r) {
                $file[$name]['id'] = $r['id'];
                $file[$name]['error'] = '';
            }
        }

        return $file;
    }

    function updateBoxIds(array $fileIds) {
        $objAttachment = new Attachments();
        foreach ($fileIds as $key => $row) {
            if (empty($row['error'])) {
                $objAttachment->where('db_name', $key)->update(array('box_file_id' => $row['id']));
            }
        }

        return true;
    }

    static function getImageUrl($id, $container, $type = 'small') {
        if (empty($id)) {
            return [];
        }

        $objAttachments = new Attachments();
        $objAttachments = $objAttachments->findRecord($id);

//        $resolution = Resolutions::DeatailByName($container);
        $resolution = Resolutions::DeatailById($objAttachments->resolutions_id);
        $imgageUrl = [];
        $url = url('storage/app/public') . $resolution->local_url . "/";
        if ($resolution && !empty($objAttachments)) {

            if ($objAttachments->images != '' && $objAttachments->images != null) {
                $arrImages = json_decode($objAttachments->images);
                foreach ($arrImages as $image) {
                    $imgInfo = pathinfo($image);
                    if ($objAttachments->process_status == '2' && $objAttachments->status == 'cloud') {
                        $url = $resolution->url . '/' . $resolution->folders . "/";
                    }
                    if ($type == 'original') {
                        $imgageUrl[] = $url . $imgInfo['filename'] . "." . $imgInfo['extension'];
                    } else {
                        $imgageUrl[] = $url . $imgInfo['filename'] . "_" . $type . "." . $imgInfo['extension'];
                    }
                }
            } elseif ($objAttachments->status == 'cloud') {
                $imgageUrl[] = $resolution->url . '/' . $resolution->folders . "/" . $objAttachments->db_name;
            } else {
                $imgageUrl[] = url('storage/app/public') . $resolution->local_url . "/" . $objAttachments->db_name;
            }
            return !empty($imgageUrl) ? $imgageUrl : false;
        } else {
            return false;
        }
    }
    
    static function getImageInfo($id, $container, $type = 'small') {
        $arrImageInfo =['url'=>[],'name'=>''];
        if (empty($id)) {
            return $arrImageInfo;
        }

        $objAttachments = new Attachments();
        $objAttachments = $objAttachments->findRecord($id);
        $arrImageInfo['name']=$objAttachments->original_name;
//        $resolution = Resolutions::DeatailByName($container);
        $resolution = Resolutions::DeatailById($objAttachments->resolutions_id);
        $imgageUrl = [];
        $url = url('storage/app/public') . $resolution->local_url . "/";
        if ($resolution && !empty($objAttachments)) {

            if ($objAttachments->images != '' && $objAttachments->images != null) {
                $arrImages = json_decode($objAttachments->images);
                foreach ($arrImages as $image) {
                    $imgInfo = pathinfo($image);
                    if ($objAttachments->process_status == '2' && $objAttachments->status == 'cloud') {
                        $url = $resolution->url . '/' . $resolution->folders . "/";
                    }
                    if ($type == 'original') {
                        $imgageUrl[] = $url . $imgInfo['filename'] . "." . $imgInfo['extension'];
                    } else {
                        $imgageUrl[] = $url . $imgInfo['filename'] . "_" . $type . "." . $imgInfo['extension'];
                    }
                }
            } elseif ($objAttachments->status == 'cloud') {
                $imgageUrl[] = $resolution->url . '/' . $resolution->folders . "/" . $objAttachments->db_name;
            } else {
                $imgageUrl[] = url('storage/app/public') . $resolution->local_url . "/" . $objAttachments->db_name;
            }
            $arrImageInfo['url']=!empty($imgageUrl) ? $imgageUrl : [];
            //return !empty($imgageUrl) ? $imgageUrl : false;
        } //else {
          //  return false;
        //}
        return $arrImageInfo;
    }
    
    static function getSource($id, $container) {
        if (empty($id)) {
            return;
        }

        $objAttachments = new Attachments();
        $objAttachments = $objAttachments->findRecord($id);

        $resolution = Resolutions::DeatailByName($container);
        $sourceUrl = '';
        $url = url('storage/app/public') . $resolution->local_url . "/";
        if ($resolution && !empty($objAttachments)) {
            if ($objAttachments->status == 'cloud') {
                $url = $resolution->url . '/' . $resolution->folders . "/";
            }
            $sourceUrl = $url . $objAttachments->db_name;
            return !empty($sourceUrl) ? $sourceUrl : false;
        } else {
            return false;
        }
    }

    function downloadAttachment($command) {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $params = $command->dataToArray();

        $fileName = $params['db_name'];
        $objAttachments = new Attachments();

        $objResult = $objAttachments->where('db_name', $params['db_name'])->first();

        $originalName = $objResult->original_name;

        $resolutionData = Resolutions::DeatailByName($params['resolution_name']);

        if ($objResult->status == 'cloud') {
            $cloud = new Cloud ();
            $path = !empty($resolutionData->folders) ? $resolutionData->folders . '/' . $fileName : '';

            $url = $cloud->downloadObject($resolutionData->container, $path);
            return Download::start($url, $originalName);
        } else if ($objResult->status == 'local') {
            $path = !empty($resolutionData->local_url) ? storage_path("app/public") . $resolutionData->local_url . '/' . $fileName : $fileName;
            return Download::start($path, $originalName);
        }
    }

    function createDirectory($directory) {
        if (!is_dir($directory)) {
            return mkdir($directory, 0777);
        }
        return false;
    }

    function updateFileSize($command) {
        $objAttachments = new Attachments();
        $objCloud = new Lcloud();
        $objResolutions = new Resolutions();

        $objResult = $objAttachments->dbTable('a')->distinct()->select('a.db_name', 'a.status', 'a.resolutions_id', 'r.name')
                ->join('resolutions as r', 'r.id', '=', 'a.resolutions_id')
                ->where('a.db_name', 'not like', '%.zip%')
                ->whereNull('a.filesize')
                ->limit(30)
                ->get();
//        echo "<pre>";print_r($objResult);exit;
        if ($objResult->count()) {
            foreach ($objResult as $attach) {
                $name = $attach->name;
                $resolutionData = Resolutions::DeatailByName($name);
                $fileName = $attach->db_name;
                if ($attach->status == 'cloud') {
                    $path = !empty($resolutionData->folders) ? $resolutionData->folders . '/' . $fileName : '';
                    $size = $objCloud->getFileSize($resolutionData->container, $path);
                    $objAttachments->where('db_name', $fileName)->update(['filesize' => $size]);
                } else if ($attach->status == 'local') {
                    $path = !empty($resolutionData->local_url) ? storage_path("app/public") . $resolutionData->local_url . '/' . $fileName : $fileName;
                    if (file_exists($path)) {
                        $size = filesize($path);
                        $objAttachments->where('db_name', $fileName)->update(['filesize' => $size]);
                    }
                }
            }
        }
    }

    function getPreview($command) {
        $data = $command->dataToArray();
        $arrResult = [];
        $objAttachments = Attachments::find($data['attachments_id']);
        if (!empty($objAttachments)) {
            $box = new Box(config('poet.box_client_id'), config('poet.box_client_secret'), config('poet.box_redirect_uri'));
            $path = storage_path('app/public') . '/Uploads/';
            $box->setTokenPath($path);
            $token = $box->getAccessToken();
            if (empty($token)) {
                throw new Exception('token not available. To genrate token run the "setboxtoken" follwed by application url');
            }

            $arrResult = ['box_file_id' => $objAttachments->box_file_id, 'box_token' => $token];
        }

        return $arrResult;
    }

    

}
