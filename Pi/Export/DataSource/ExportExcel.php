<?php

namespace CodePi\Export\DataSource;

use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Border,
    PHPExcel_Style_Alignment,
    PHPExcel_Style_Fill;
use PHPExcel_Style_NumberFormat;
use PHPExcel_Cell_DataType;
use CodePi\Export\DataSource\ExportItemsExcel;
use CodePi\Base\Eloquent\Events;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Libraries\DefaultIniSettings;
use CodePi\Base\Commands\CommandFactory;

/**
 * Class : ExportExcel
 * Descriptions : This is Class will handle the Export the Items Excel/CSV File
 */
class ExportExcel {
    
   
    /**
     * Export excel
     * @param type $command
     * @return type
     */
    function export($command) {
        DefaultIniSettings::apply();
        $exportResponse = [];
        try {
            $objPHPExcel = new PHPExcel();
            $sheetTabs = 0;
            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex($sheetTabs);
            $sheet = $objPHPExcel->getActiveSheet($sheetTabs);
            $sheet->setTitle('ResultItems');

            $sheetTabs = 1;
            $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex($sheetTabs);
            $sheet1 = $objPHPExcel->getActiveSheet($sheetTabs);
            $sheet1->setTitle('LinkedItems');

            $headerStyle = $this->getHeaderStyle();
            $dataStyle = $this->getDataStyle();

            $objExport = new ExportItemsExcel($sheet, $command, $headerStyle, $dataStyle, 0);
            $objExport->getExportData();

            $objExport = new ExportItemsExcel($sheet1, $command, $headerStyle, $dataStyle, 1);
            $objExport->getExportData();

            $objPHPExcel->removeSheetByIndex(2);
            $objPHPExcel->setActiveSheetIndex(0);
            $filename = $objExport->getEventName() . ' ' . date('m-d-Y h_i_s A') . '.xlsx';
            $dirPath = storage_path('app') . '/public/Export/export_items/' . $filename;
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save($dirPath);
            sleep(2);
            if (file_exists($dirPath)) {
                chmod($dirPath, 0777);
            }
            $exportResponse = ['status' => true, 'filename' => PiLib::piEncrypt($filename)];
            $objExport->saveExportLog($exportResponse, true);
        } catch (\Exception $ex) {
            $exMsg = 'Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);
            $exportResponse = ['status' => false, 'filename' => null, 'message' => $ex->getMessage()];
        }

        return $exportResponse;
    }

    /**
     * Set Headers column style
     * 
     * @return array
     */
    public function getHeaderStyle() {
        return $header_styles = array(
            'font' => array(
                'bold' => true,
                'color' => array('rgb' => 'FFFFFF'),
                'size' => 10,
                'name' => 'arial'
            ),
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => '177DC4')
                )
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => '177DC4')
            ),
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            )
        );
    }

    /**
     * Set Data cellls style
     * 
     * @return array
     */
    public function getDataStyle() {
        return $header_styles = array(
            'font' => array(
                'bold' => false,
                'color' => array('rgb' => '000000'),
                'size' => 10,
                'name' => 'arial'
            ),
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => '000000')
                )
            )
        );
    }


}
