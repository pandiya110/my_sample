<?php

namespace CodePi\Attachments\Http\Controllers;

use CodePi\Base\Http\PiController;
use Illuminate\Http\Request;
use CodePi\Attachments\Commands\AddAttachment;

/**
 * @access public
 * @ignore It will handle master module operations
 */
class AttachmentsController extends PiController {

    /**
     * @param object $request
     * @throws Exception It will throws an exception on invalid access
     * @return object It will upload attachment and returns response
     */
    public function uploadAttachments(Request $request) {
        $data = ['extensions' => array('pdf'), 'filename' => '', 'flowChunkNumber' => 1];
        $command = new UploadAttachment($data);
        return $this->run($command, 'Fileupload success', 'Fileupload failure');
    }

}
