<?php
namespace CodePi\Export\Executables;
use CodePi\Base\Commands\iCommands;
use CodePi\Export\DataSource\ExportItemsSftpDs;
 
class ExportItemsToSftp implements iCommands {
    /**
     *
     * @var object 
     */
    private $dataSource;
    /**
     * 
     * @param ExportExcel $objExport
     */
    public function __construct(ExportItemsSftpDs $objExport) {

        $this->dataSource = $objExport;
    }
    
    public function execute($command) {
        $params = $command->dataToArray();
        return $this->dataSource->getItemsDataToExport($params);
    }

}
