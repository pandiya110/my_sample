<?php
namespace CodePi\Base\Libraries\Transfer;

use CodePi\Base\Libraries\Transfer\iTransfer;
use CodePi\Base\Libraries\Upload\UploadType;

class NormalUpload implements iTransfer {
    
    private $container;
    private $fileName = 'file';
    function setContainer($container) {
        $this->container = $container;
    }
    function getContainer() {
        return $this->container;
    }
    function upload() {
       if (isset ( $_FILES [$this->fileName] ['tmp_name'] )) {
            $upload = UploadType::Factory ( 'Regular' );
            $files = $_FILES [$this->fileName];

        } else {
            if (! empty ( $_SERVER ['HTTP_X_FILE_NAME'] )) {
                $files = $_SERVER ['HTTP_X_FILE_NAME'];
            } else {
                $files = $_REQUEST [$this->fileName];
            }
            $upload = UploadType::Factory ( 'Stream' );
        }
        $upload->setFiles($files);
        //$upload->setSize ($this->size);
        //$upload->setAllowedTypes ($this->extensions);
        $upload->setContainer ($this->getContainer()); 
        $tmpfile = $upload->save ();
        return $tmpfile;
    }
}