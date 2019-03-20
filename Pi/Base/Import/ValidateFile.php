<?php
namespace CodePi\Base\Import;
use CodePi\Base\Exceptions\ValidateFileException;
use CodePi\Base\Libraries\PiLib;
class ValidateFile {
    private $minLinesCount = 1;
    private $maxCountLines = 80000;
    private $headers = [];
    private $data = [];
    private $size=0;
    private $allowedTypes=array();                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  
    private $container;
    private $files;
    private $sheetNo = 0;
    private $headerRow = 0;
    
    public $isSetMinLinesCount = FALSE;
    public $isSetMaxLinesCount = FALSE;
    public $isSetMatchHeaders = TRUE;
    public $isValidateHeaders = TRUE;
    
    public $errMessage = '';
    
    public $mergeHeadersWithData = true;
    
    function execute () {
        if($this->checkMinLinesCount()) {
            if($this->checkMaxLinesCount()) {
                if($this->checkHeadersMatch() ){
                    return TRUE;
                }else{
                	
                    $this->errMessage = 'Invalid Format. Excel Headers are not matching..';
                    throw new ValidateFileException($this->errMessage);
                    //return FALSE;
                }
            }else{
                $this->errMessage = 'Max Rows in File is More';
                throw new ValidateFileException($this->errMessage);
                //return FALSE;
            }
        }else{
            $this->errMessage = "Please upload a file with valid data.";
            throw new ValidateFileException($this->errMessage);
            // return FALSE;
        }
    }
    
    function checkMinLinesCount () {
        if((count($this->getData()) >= $this->getMinLinesCount() && $this->isSetMinLinesCount)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }   
    function checkMaxLinesCount () {
        if($this->isSetMaxLinesCount) {
          if((count($this->getData()) <= $this->getMaxLinesCount())){
            return TRUE;
            } else {
                return FALSE;
            }  
        }else{
            return TRUE;
        }
        
    }   
    function setMinLinesCount ($value) {
        $this->minLinesCount = $value;
    }
    function setMaxLinesCount ($value) {
        $this->maxCountLines = $value;
    }
    function getMinLinesCount () {
        return $this->minLinesCount;
    }
    function getMaxLinesCount () {
        return $this->maxCountLines;
    }
    function checkHeadersMatch () {
        if($this->isSetMatchHeaders){
            $count = 0;
            $headers = $this->getHeaders(); 
            $fileData = $this->getData();
            foreach($fileData as $row => $data){
              if(isset($data[$this->headerRow])){  
                $fileDataHeader = strtolower(implode('',$data[$this->headerRow]));
                foreach($headers as $header){
                  $headerString = strtolower(implode('',$header));
                  if(strcmp($headerString,$fileDataHeader) == 0){
                    $this->setData($fileData[$row]);
                    $count++;
                  }
                }
              }
            }
            if($count == 0){
               return FALSE;
            }else{
               return TRUE;  
            }
        }else{
            return TRUE;
        }
        
    }
    
    function setHeaders ($value) {
        $this->headers = $value;
    }    
    
    function setData ($value) {
        /*if(is_array($value)) {
            unset($value[$this->getHeaderRow()]);
        }*/   
        $this->data = array_values($value);
    }
    
    public function setSize($size) {
        $this->size = $size;
    }
    
    function setHeaderRow($value) {
        $this->headerRow = $value;
    }
    
    function setSheetNo($value) {
        $this->sheetNo = $value;
    }
    
    function setAllowedTypes($allowedTypes) {
        $this->allowedTypes = $allowedTypes;
    }

    function setContainer($container) {
        $this->container = $container;
    }

    public function setFiles($files) {
        $this->files = $files;
    }
    function getHeaders () {
        return $this->headers;
    }
    
    function getData () {
        return $this->data;
    }    
    
    function getSheetNo() {
        return $this->sheetNo;
    }  
    
    function getSize() {
        return $this->size;
    }
    
    function getHeaderRow() {
        return $this->headerRow;
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
    
    function validateProperties ($is_save = true) {
       $allowed_types = $this->getAllowedTypes();
       $files = $this->getFiles();
       if(!file_exists($files->getPathName())) { 
           $this->errMessage = "File is not uploaded, Please try again.";
           throw new ValidateFileException($this->errMessage);
           //return FALSE;
       }
       if($is_save)
       { 
//           echo "<pre>";print_r($this->getContainer());
//           if(!is_writable($this->getContainer())){
//              echo 'false'; 
//           }else{
//               echo 'true';
//           }
//           exit;
            if (!is_writable($this->getContainer())){
                $this->errMessage = "Server error. Upload directory isn't writable.";
                throw new ValidateFileException($this->errMessage);
                //return FALSE;
                //return array('error' => "Server error. Upload directory isn't writable.");
            }
       }
       if($this->getSize())
       {  
          if($files->getSize() > $this->getSize()) {
              $this->errMessage = 'Upload file is too large, maximum allowed file size is '. PiLib::fileSizeConvert($this->getSize());
              throw new ValidateFileException($this->errMessage);
              //return FALSE;
          }
       }
       
       if(!empty($allowed_types))
       {
            $pathinfo = pathinfo($files->getClientOriginalName());
            $ext = strtolower($pathinfo['extension']);
            if(!in_array(strtolower($ext), $allowed_types)){
                $these = implode(', ', $allowed_types);
                $this->errMessage = 'You have selected an invalid file type. Only  '. $these . ' files are allowed.';
                throw new ValidateFileException($this->errMessage);
                //return FALSE;
           }
       }
       $this->errMessage = '';
       return TRUE;
    }
    
    function validateHeaders ($headers) {
        if($this->isValidateHeaders){
            $check = 0;
            foreach($headers as $header){
                if( (count(array_intersect($header, $this->getHeaders())) == count($this->getHeaders())) && (count($header) == count($this->getHeaders()))  ) {
                    //if(empty(array_diff($headers,$this->getHeaders()))) {
                    $check++;
                }
            }
            if($check >= 1){
                return TRUE;
            }else{
                $this->errMessage = 'Excel file header doesn\'t match. Header count is not equal.';
                throw new ValidateFileException($this->errMessage);
                //return FALSE;
            }
        }else{
            return TRUE;
        }
        
    }
   
}
