<?php
namespace CodePi\Export\Executables;
use CodePi\Base\Commands\iCommands;
#use CodePi\Export\DataSource\ExportExcel;
use CodePi\Export\DataSource\ExportFactory;
 
class ExportItems implements iCommands {
    /**
     *
     * @var object 
     */
    private $dataSource;
    
    /**
     * 
     * @param ExportExcel $objExport
     */
//    public function __construct(ExportExcel $objExport) {
//
//        $this->dataSource = $objExport;
//    }
    /**
     * Execution of generating export the excel/csv file
     * @param object $command
     * @return array
     */
//    public function execute($command) {
//        $arrResponse = [];
//        $objResult = $this->dataSource->getExportData($command);
//        if (!empty($objResult)) {
//            $arrResponse = $objResult;
//        }
//
//        return $arrResponse;
//    }
    
    public function execute($command) {
        $arrResponse = [];
        $exportType = isset($command->type) ? $command->type : 1;        
        $this->dataSource = ExportFactory::Factory($exportType);        
        $objResult = $this->dataSource->export($command);        
        if (!empty($objResult)) {
            $arrResponse = $objResult;
        }

        return $arrResponse;
    }

}
