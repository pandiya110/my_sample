<?php

namespace CodePi\Attachments\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Attachments\DataSource\AttachmentsDSource;


class AddAttachment implements iCommands {

    private $dataSource;

    function __construct(AttachmentsDSource $objAttachmentsDSource) {
        $this->dataSource = $objAttachmentsDSource;
    }

    /**
     * @param object $command
     * @return object It will return $result
     */
    function execute($command) {
        $result = $this->dataSource->addAttachment($command);
        return $result;
    }

}
