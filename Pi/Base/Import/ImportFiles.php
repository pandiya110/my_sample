<?php
namespace CodePi\Base\Import;

use CodePi\Base\Libraries\FileReader\ReaderFactory;
use CodePi\Base\Libraries\Transfer\TransferType;
use CodePi\Base\Exceptions\ValidateFileException;

abstract class ImportFiles  {
    private $fileData;
    private $objValidate;
    public $fileName;
        
    function getFilesData ($file,$delimeter) {
        $objReader = ReaderFactory::select($file, $delimeter);
        $objReader->setSettings($this->getObjValidate());
        $this->fileData = $objReader->getData($file,true);          
    }
    function setObjValidate  ($obj) {
        $this->objValidate = $obj;
    }
    function getObjValidate () {
        return $this->objValidate;
    }
    
    function uploadFile($filePath = "products",$type="normal") {
        if(!empty($filePath)) {
            $objUpload = TransferType::select($filePath,$type);
            $objUpload->setContainer($filePath);
            //$objUpload->fileName = 'file';
            //echo "<pre>";print_r($objUpload);exit;
            return $objUpload->upload();
        }else{
            return FALSE;
        }
    }

    function importData($file) {
        try{
            
            $this->objValidate->setFiles($file); 
            if($this->objValidate->validateProperties()) {
               $upload_file_data = $this->uploadFile($this->objValidate->getContainer()); 
               if(!empty($upload_file_data) && $upload_file_data['error'] == 'success') {
                   $filename = $upload_file_data['filename'];
                   @chmod($upload_file_data['filename'], 0777);
                   $this->fileName = $upload_file_data['filename'];
                   $this->getFilesData($filename, "|"); 
                   
                   if(!empty($this->fileData)) {
                       $headers = [];
                       $this->fileData = array_values($this->fileData);  
                       foreach($this->fileData as $key => $row){
                         if(isset($this->fileData[$key][$this->objValidate->getHeaderRow()])){
                           $headers[] = $this->fileData[$key][$this->objValidate->getHeaderRow()];   
                         }    
                       }   
                       if($this->objValidate->validateHeaders($headers)){
                           $this->objValidate->setData($this->fileData); 
                           if($this->objValidate->execute()){
                               return $this->objValidate->getData();
                           }else{
                               return $this->objValidate->errMessage;
                           }
                       }else{
                           return $this->objValidate->errMessage;
                       }

                   }else{
                        return "Empty file uploaded... Please check the file once";  
                   }                               
               }else{
                 return "File is not uploaded, Please try again.";  
               } 

            }else{
                return $this->objValidate->errMessage;
            }            
        } catch (ValidateFileException $ex) {
            return $ex->getMessage();
        } catch (\Exception  $ex){
            return $ex->getMessage();
        }

    }    
    abstract function importHandler($command);
}

