<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of RegularUpload
 *
 * @author raju
 */
namespace CodePi\Base\Libraries\Upload;
use CodePi\Base\Libraries\PiLib; 
class RegularUpload implements FileUpload {
    //put your code here
    private $size=0;
    private $allowedTypes=array();
    private $container;
    private $files;
    
    public function getSize() {
        return $this->size;
    }

    public function getAllowedTypes() {
        return $this->allowedTypes;
    }

    public function getContainer() {
        return $this->container;
    }

    public function getFiles() {
        return $this->files;
    }

    public function setSize($size) {
        $this->size = $size;
    }

    public function setAllowedTypes($allowedTypes) {
        $this->allowedTypes = $allowedTypes;
    }

    public function setContainer($container) {
        $this->container = $container;
    }

    public function setFiles($files) {
        $this->files = $files;
    }
  
        
    function save(){
        //$this->files = $files;
       
        $files=$this->getFiles();
        $validate=$this->validate();
        if(isset($validate['error'])&& $validate['error']=='success' )
        {
            $pathinfo = pathinfo($files['name']);
            $ext = strtolower($pathinfo['extension']);
            $path=$this->getContainer().time().'.'.$ext;
            if(move_uploaded_file($files['tmp_name'], $path)){
                return array('error'=>'success','original_filename'=>$files['name'],'filename'=>$path,'image_name'=>time().'.'.$ext);
            }else{
               return array('error'=> 'Could not save uploaded file.' .
                'The upload was cancelled, or server error encountered');
            }
        }else{
            return $validate;
        }    
    }
    function tempfile($files){
        $this->files = $files;
         $files=$this->getFiles();
        
        $validate=$this->validate(false);
            if($validate['error']!='success')
            {
               return $validate;
            }else{
               return array('error'=>'success','original_filename'=>$files['name'],'filename'=>$path);
            }
    }
    function fileSize()
    {
        $files=$this->getFiles();
        return $files['size'];
    }
    function validate($is_save=true)
    {
       $allowed_types=$this->getAllowedTypes();
       $files=$this->getFiles();
       if($is_save)
       {   
            if (!is_writable($this->getContainer())){
                return array('error' => "Server error. Upload directory isn't writable.");
            }
       }
     if($this->getSize())
       {    
          if($files['size'] > $this->getSize()) {
             $size = PiLib::fileSizeConvert($this->getSize());
             return array('error' => 'Upload file is too large, maximum allowed file size is '. $size);
          }
       }
       
       if(!empty($allowed_types))
       {
            $pathinfo = pathinfo($files['name']);
            
            $ext = strtolower($pathinfo['extension']);
            if(!in_array(strtolower($ext), $allowed_types)){
            $these = implode(', ', $allowed_types);
            return array('error' => 'File has an invalid extension, it should be one of '. $these . '.');
           }
       }
       return array('error'=>'success');
    }
    
    
}
