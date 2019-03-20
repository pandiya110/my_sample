<?php

namespace CodePi\Users\DataSource;


use CodePi\Base\DataSource\DataSource;

use CodePi\Base\Libraries\Upload\UploadType;
use CodePi\Base\Libraries\ImageLib;
use CodePi\Base\Eloquent\Users;

use CodePi\Base\Libraries\Upload\PiUpload;
//use CodePi\Events\Eloquant\Attachments;
use URL,DB; 

class UsersDataSource  {

    /**
     * Upldating user profile picture
     * @param array $data
     * @return array
     */
    function uploadUserImage($data) {
       // echo "<pre>";print_r($data);exit;
        $returnArr = array();
        $user_id = isset($data['id']) ? $data['id'] : 0;
        //if (isset($user_id) && !empty($user_id)) {
            $objPiUpload=new PiUpload; 
            $objPiUpload->filename='file';    
            $objPiUpload->container = storage_path('app/public') . '/Uploads/users_logo/'; 
            $objPiUpload->size=10*1024*1024;

            $tmpfile = $objPiUpload->save();
            if (isset($tmpfile['error']) && $tmpfile['error'] == 'success') {
               $tmpfile['url'] = URL::to('storage/app/public/Uploads/users_logo/'.$tmpfile['image_name']); 
                return $returnArr = $tmpfile;  
            }else{
                return $returnArr = [];     
            }
            
            if (isset($tmpfile['error']) && $tmpfile['error'] == 'success') {
                $source = $tmpfile['filename'];
                //$original_name = $tmpfile['original_filename'];
                $attachment=$objPiUpload->imageProcess($source,$data['file'],'users'); 
                //$attachment=$objPiUpload->imageProcess($source,$original_name,'users'); 
                $command = array('attachments_id'=>$attachment->id);  
                //$this->addUserProfile($command,$user_id); 
                
                $imageName=$objPiUpload->cloudUrl($attachment->db_name,'users','medium');   

                $info['attachment']=$imageName;
                $info['attachment_name']=$attachment->db_name;
                $info['attachment_id']=$attachment->id; 
                return $returnArr = array('success' => true, 'attachment'=>$info['attachment'],'attachment_name'=>$info['attachment_name'], 'attachment_id'=>$info['attachment_id']);  
            }else { 
                 return $returnArr = array('success' => false, 'data' => '');     
            }  
        //}
        /*$returnArr = array();
        $user_id = isset($data['id']) ? $data['id'] : 0;
        if (isset($user_id) && !empty($user_id)) {
            $tmpfile = $this->uploadImage();
            if (isset($tmpfile['error']) && $tmpfile['error'] == 'success') {
                $source = $tmpfile['filename'];
                $pathname = public_path() . '/uploads/users/';
                $image_data = array();
                $imglib = new ImageLib;
                $logo_data['file_name'] = basename($source);
                $img_size = getimagesize($source);
                $resize_img = "resize_" . basename($source);
                @copy($pathname . basename($source), $pathname . $resize_img);
                if ($img_size[0] > 505 || $img_size[1] > 290) {
                    $image_cfg['image_library'] = 'GD2';
                    $image_cfg['source_image'] = $pathname . $resize_img;
                    $image_cfg['maintain_ratio'] = TRUE;
                    $image_cfg['width'] = '505';
                    $image_cfg['height'] = '290';
                    $imglib->initialize($image_cfg);
                    $imglib->resize();
                    $orig_width = '505';
                    $orig_height = '290';
                    $data['logo'] = $logo_data['file_name'];
                    $image_data['src'] = URL::to('') . "/public/uploads/users/" . $resize_img;
                    $image_data['source_path'] = public_path() . "/uploads/users/" . basename($source);
                    $image_data['width'] = $orig_width;
                    $image_data['height'] = $orig_height;
                    $image_data['filename'] = $logo_data['file_name'];
                } else {
                    $orig_width = $img_size[0];
                    $orig_height = $img_size[1];
                    $data['logo'] = $logo_data['file_name'];
                    $image_data['src'] = URL::to('') . "/public/uploads/users/" . basename($source);
                    $image_data['source_path'] = public_path() . "/uploads/users/" . basename($source);
                    $image_data['success'] = true;
                    $image_data['width'] = $orig_width;
                    $image_data['height'] = $orig_height;
                    $image_data['filename'] = $logo_data['file_name'];
                }
                $image_data['orginal_name'] = $data['qqfile'];
                $profileData = array('image_name' => $image_data['filename'], 'orginal_name' => $image_data['orginal_name']);
                Users::where('id', $user_id)->update($profileData);
                $returnArr = array('status' => true, 'data' => $image_data);
            } else {
                $returnArr = array('status' => false, 'data' => $tmpfile);
            }
        } else {
            $returnArr = array('status' => false, 'data' => 'User does not exists !');
        }
        return $returnArr;*/
    }

    function addUserProfile($data,$id){
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Getting uploaded image information
     * @return array
     */ 
    function uploadImage() {
        if (isset($_FILES['file']['tmp_name'])) {
            $upload = UploadType::Factory('Regular');
            $files = $_FILES['file'];
        } else {
            if (!empty($_SERVER['HTTP_X_FILE_NAME'])) {
                $files = $_SERVER['HTTP_X_FILE_NAME'];
            } else {
                $files = $_REQUEST['file'];
            }
            $upload = UploadType::Factory('Stream');
        }
        $upload->setFiles($files);
        $upload->setSize(5 * 1024 * 1024);
        $upload->setAllowedTypes(array('jpeg', 'jpg', 'PNG', 'png', 'GIF', 'gif', 'svg', 'SVG'));
        $upload->setContainer(storage_path('app/public') . '/Uploads/users_logo/');
        $tmpfile = $upload->save();
       
        if (isset($tmpfile['error']) && $tmpfile['error'] == 'success') {
            $tmpfile['url'] = '/storage/app/public/Uploads/users_logo/' . $tmpfile['image_name'];
            $fileInfo = pathinfo($tmpfile['image_name']);            
            $source = $tmpfile['filename'];
            $objPiUpload=new PiUpload; 
            $attachment = $objPiUpload->imageProcess($source,$_FILES['file'],'users_logo');
            $tmpfile['users_thumbnail'] = '/storage/app/public/Uploads/users_logo/' . $fileInfo['filename'].'_small.'.$fileInfo['extension']; 
            $tmpfile['users_image'] = '/storage/app/public/Uploads/users_logo/' . $fileInfo['filename'].'_medium.'.$fileInfo['extension']; 
        } 
        return $tmpfile;
    }

}
