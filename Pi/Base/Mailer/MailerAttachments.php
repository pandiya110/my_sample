<?php
namespace CodePi\Base\Mailer;

use CodePi\Base\Eloquent\Attachments;
use CodePi\Base\Libraries\ZipFileFunctions;
use CodePi\Base\Libraries\Logger;
use CodePi\Base\Libraries\Upload\Cloud; 
use CodePi\Base\Eloquent\Resolutions;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Attachments\Commands\AddAttachment;
use CodePi\Attachments\DataSource\AttachmentsDSource;


class MailerAttachments{
    //{%attachment%}
    private $arrAttachmentIds=[];
    
    private $maxSize=20*1048*1048;//20MB
    
    private $isLink=true;
    
    private $linkTitle="Please click {%here%} to download.";
    
    private $mailAttachments=[];
    
    private $fileName = '';
    
    private $storePath = '';
    
    private $exportFileName = '';
    
    private $folderPath = '';
    
    private $destination = '';
    
    function __construct($config) {
       
        if(isset($config['attachmentsIds']))
        {
            $this->arrAttachmentIds=$config['attachmentsIds'];
            
        }
        
        if(isset($config['maxSize']))
        {
            $this->maxSize=$config['maxSize'];
            
        }
        
        if(isset($config['fileName']))
        {
            $this->fileName=$config['fileName'];
            
        }        
    }
    /*

     * @aprams $arr
     * 
     *      */
    
    function setAttchmentIds(array $arrAttachmentIds){
        
        
        $this->arrAttachmentIds=$arrAttachmentIds;
    }
    
    function setMaxSize($maxSize){
        
        
        $this->maxSize=$maxSize;
    }
    
    function setLinkTitle(){
        
        
        
    }
    
    function setStorePath($storePath){
  
        $this->storePath=$storePath;
    }
    
    
    function setFileName($fileName){
        
        $this->fileName=$fileName;
        
    }    
    
    function getAttachmentSize(){
        $objAttachments = new Attachments();
        $attachmentIds=$this->arrAttachmentIds;
        $fileSize = 0;
        if(!empty($attachmentIds)){
            $objResult = $objAttachments->select('id','filesize')->whereIn('id',$attachmentIds)->get();
            if($objResult->count() > 0){
                foreach($objResult as $res){
                    $fileSize  = $fileSize+$res->filesize;
                }
//                $fileSize = number_format($fileSize / 1048576, 2);
            }
        }
        return $fileSize;
    }
    
    
    function handle(){        
        if(!empty($this->arrAttachmentIds)){
            $this->downloadAttachements();
            if($this->getAttachmentSize() > $this->maxSize){
               $this->isLink=true; 
               $link = $this->createZipLink();
               return (array('isLink'=> $this->isLink,'link'=> $link)); 
            }else{
               $this->mailAttachments=[];
            }
        }
    }
    
    function createZipLink(){
        $link = '';
        $filename = $this->createZip();
        if(!empty($filename) && $filename != ''){
            $link = $this->zipLink($filename);
        }
        return $link;
    }
    
    function createZip(){
        $objFile  = new ZipFileFunctions;
        if(!empty($this->mailAttachments)){
            $file = $objFile->compressFolder($this->exportFileName, $this->folderPath, $this->destination);
            $zipFileName = substr($file, strrpos($file, '/') + 1);
        }
        return $zipFileName;
    }
    
    function zipLink($file){
        $resolutionName = "order_form_attachments";
        $attachment = [];
        $attachment['status'] = 'cloud'; // changed by naga due to cloud upload issue
        $attachment['resolution_name'] = $resolutionName;
        $attachment['db_name'] = $file;
        $attachment['original_name'] = $file;
        $attachment['local_to_cloud'] = 'false';
        $attachment['local_img_process'] = 'false';

        $deatails = new AddAttachment($attachment);
        $link = CommandFactory::getCommand($deatails);
        if ($link instanceof Attachments) {
            $link = implode(AttachmentsDSource::getImageUrl($link->id, $resolutionName));
        }
        sleep(1);

        return $link;        
        
    }
    
    function getAttachmentsInfo(){
        $objAttachments = new Attachments();  
        $attachmentsinfo = [];
        $objResult = $objAttachments->dbTable('a')->select('a.id','a.resolutions_id','r.name','r.local_url','a.db_name','a.original_name','a.status')
                     ->join('resolutions as r ','r.id','=','a.resolutions_id')
                     ->whereIn('a.id',$this->arrAttachmentIds)
                     ->get();  
        if($objResult->count() > 0){
            foreach($objResult as $key => $res){
                $attachmentsinfo[$key]['id'] = $res->id;
                $attachmentsinfo[$key]['resolutions_id'] = $res->resolutions_id;  
                $attachmentsinfo[$key]['name'] = $res->name;
                $attachmentsinfo[$key]['local_url'] = $res->local_url;
                $attachmentsinfo[$key]['db_name'] = $res->db_name;  
                $attachmentsinfo[$key]['original_name'] = $res->original_name;  
                $attachmentsinfo[$key]['status'] = $res->status;                  
            }
        }
        return $attachmentsinfo;
    }
    
    function downloadAttachements(){
        $objFile         = new ZipFileFunctions;
        $objLogger       = new Logger;
        $objCloud        = new Cloud;  
        $attachmentsinfo = $this->getAttachmentsInfo();
        $this->exportFileName  = $objLogger->getExportFileName($this->fileName);
        $this->folderPath      = storage_path('app/public') . '/Uploads/'.$this->storePath.'/'  . $this->exportFileName;
        $this->destination     = storage_path('app/public') . '/Uploads/'.$this->storePath.'/';
        $objFile->createFolder($this->folderPath);  
        @chmod($filename, 0777);
      
        $fileAttachments = [];
        if(!empty($attachmentsinfo)){
            foreach($attachmentsinfo as $key => $attach){  
                $fileName = $attach['db_name'];
                $filePath = $this->folderPath.'/'.$fileName;
                $saveFilePath  = $this->folderPath.'/'.$attach['original_name'];
                $resolutionData = Resolutions::DeatailByName($attach['name']);
                if($attach['status'] == 'cloud'){
                    $path = !empty($resolutionData->folders) ? $resolutionData->folders . '/' . $fileName : ''; 
                    $fileStoredPath =$objCloud->downloadObject($resolutionData->container,$path,$saveFilePath);
                    $fileAttachments[$key] = $fileStoredPath;
                }else if ($attach['status'] == 'local') {
                    $path = !empty($resolutionData->local_url) ? storage_path("app/public") . $resolutionData->local_url .  '/' .$fileName : "";
                    if(file_exists($path)){
                        copy($path, $saveFilePath); 
                        $fileAttachments[$key] = $saveFilePath;
                    }
                }    
            }
        }
        $this->mailAttachments = $fileAttachments;
    }
    
}