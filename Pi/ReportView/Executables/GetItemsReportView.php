<?php

namespace CodePi\ReportView\Executables;

use CodePi\Base\Libraries\PiLib;
use CodePi\ReportView\Utils\ReportViewFactory;

class GetItemsReportView {
    /**
     *
     * @var Class 
     */
    private $dataSource;

    public function __construct() {
        
    }
    /**
     * 
     * @param Object $command
     * @return Array
     */
    public function execute($command) {
        $type = config('smartforms.reportView');
        $this->dataSource = ReportViewFactory::Factory($type);
        return $this->dataSource->getReportViewData($command);
    }

}
