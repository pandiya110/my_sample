<?php
namespace CodePi\Base\Libraries\Transfer;

use CodePi\Base\Libraries\Transfer\iTransfer;

class FtpUpload implements iTransfer {
    
    private $container;
    private $fileName = 'filename';
    function setContainer($container) {
        $this->container = $container;
    }
    function getContainer() {
        return $this->container;
    }
    function upload() {
       
    }
}