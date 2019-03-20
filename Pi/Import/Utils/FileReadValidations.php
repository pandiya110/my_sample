<?php
/**
 * 
 */
namespace CodePi\Import\Utils;
use CodePi\Base\Libraries\FileReader\ReaderFactory;
use CodePi\Base\Libraries\DefaultIniSettings;
use CodePi\Base\Import\ValidateFile;

/**
 * Class : FileReadValidations
 * 
 */
class FileReadValidations {
    /**
     *
     * @var string
     */
    public $files;
    /**
     *
     * @var string
     */
    public $errorMsg = 'success';
    
    function setFiles($files){
        $this->files = $files;
    }
    
    function getFiles(){
        return $this->files;
    }
    /**
     * Do validations for import items data
     * 
     * @return array;
     */
    function validate() {
        
        $files = $this->files;
        
        $returnArr = [];
        $fileData = $this->getData($files, '|');
        
        $ignoredData = $firstColumn = $duplicateValues = [];
        if (empty($fileData)) {
            $this->errorMsg = 'Empty file uploaded... Please check the file once';
        }

        if (!empty($fileData)) {
            $fileDataWithoutHeaders = array_shift($fileData);
            foreach ($fileDataWithoutHeaders as $key => $records) {                
                if (isset($records[0]) && !empty($records[0])) {
                        if (is_numeric($records[0])) {
                            $firstColumn[] = $records[0];
                            unset($records[0]);
                            $otherColumn = $records;
                        } else {
                            $ignoredData[] = $records[0];
                        }
                    }
            }
            $duplicateValues = $this->findDuplicateValueFromArray($firstColumn);
            if (empty($firstColumn)) {

                $this->errorMsg = 'No data found on Column 1 of the uploaded file.';
            }

            if (!empty($otherColumn)) {

                $this->errorMsg = 'Additional data found in the file. Only column 1 data will be uploaded.';
            }
        }
   
        $returnArr['error'] = $this->errorMsg;
        $totalValues = count($firstColumn)+ count($ignoredData); 
        $returnArr['TotalValues'] = $totalValues;
        $returnArr['ignoredValues'] = count($ignoredData);
        $returnArr['duplicateValues'] = count($duplicateValues);
        $returnArr['inputValues'] = array_unique($firstColumn);
        
        return $returnArr;
    }
    
    /**
     * Find the reader method based on file extentions
     * 
     * Read and Get data from uploaded files
     * 
     * @param string file path $file
     * @param string $delimeter
     * @return array
     */
    function getData($file, $delimeter) {        
        DefaultIniSettings::apply();
        $objReader = ReaderFactory::select($file, $delimeter);        
        return $objReader->getData($file, true);
    }
    
    function findDuplicateValueFromArray($array) {
        return array_unique(array_diff_assoc($array, array_unique($array)));
    }

}
?>