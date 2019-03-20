<?php

namespace CodePi\Export\DataSource;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Items\DataSource\ItemsDataSource;
use CodePi\Base\Libraries\DefaultIniSettings;
use CodePi\Items\Commands\GetItemsList;
use CodePi\Items\Commands\GetLinkedItemsList;
use CodePi\Templates\DataSource\UsersTemplatesDS;
use Auth;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;
use CodePi\Export\Commands\ExportItems;
class ExportData {
    /**
     *
     * @var Instance of ExportItems 
     */
    private $objCommand;
    /**
     * 
     * @param type $objCommand
     */
    function __construct($objCommand = null) {
        
        if ($objCommand instanceof ExportItems) {
            $this->objCommand = $objCommand;
        }
    }

    /**
     * Get Result & Linked Items Data
     * @return Array
     */
    function getData() {
        $data = [];
        try {
            $params = $this->objCommand->dataToArray();
            $params['event_id'] = PiLib::piEncrypt($params['event_id']);
            $params['is_export'] = true;
            if ($params['exportSheetIndex'] == 0) {
                $objCommand = new GetItemsList($params);
                $cmdResponseItems = CommandFactory::getCommand($objCommand);
            } else {
                $objCommand = new GetLinkedItemsList($params);
                $cmdResponseItems = CommandFactory::getCommand($objCommand);
            }

            $exportItemsData = isset($cmdResponseItems['items']['itemValues']) ? $cmdResponseItems['items']['itemValues'] : [];
            unset($cmdResponseItems);
            $headerType = isset($params['exportSheetIndex']) && $params['exportSheetIndex'] == 1 ? 2 : 0;
            $data = $this->prepareExportFormat($exportItemsData, $params['event_id'], $params['users_id'], $headerType);
            unset($exportItemsData);
        } catch (\Exception $ex) {
            throw new DataValidationException($ex->getMessage().$ex->getFile().$ex->getLine(), new MessageBag());
        }
        return $data;
    }

    /**
     * Format the Export Data
     * 
     * @param array $exportData
     * @return array
     */
    function prepareExportFormat($exportData, $eventID, $intUsersId, $headerType) {
        $arrData = $sheetHeaders = [];
        if (!empty($exportData)) {
            $arrHeaders = $this->getSheetHeaders($eventID, $intUsersId, $headerType);

            $i = 0;
            unset($arrHeaders['acitivity']);
            unset($arrHeaders['dotcom_thumbnail']);
            foreach ($arrHeaders as $column => $label) {
                foreach ($exportData as $data) {
                    $arrData[$data['id']]['is_excluded'] = $data['is_excluded'];
                    $arrData[$data['id']][$label] = isset($data[$column]) ? $data[$column] : "";
                }
                $i++;
            }
        }

        return array_values($arrData);
    }

    /**
     * Get Export Excel/Csv headers columns
     * @param Int $eventID
     * @param Int $intUsersId
     * @param Int $headerType
     * @return Array
     */
    function getSheetHeaders($eventID = 0, $intUsersId, $headerType) {
        $objItemsDs = new ItemsDataSource();
        $params['linked_item_type'] = $headerType;
        $params['events_id'] = PiLib::piDecrypt($eventID);
        $params['users_id'] = (\Auth::check()) ? Auth::user()->id : 0;

        $objTemp = new UsersTemplatesDS();
        $userHeaders = $objTemp->getActiveTemplateColumns($params);
        if (!empty($userHeaders)) {

            $arrHeaders = isset($userHeaders['itemHeaders']) ? $userHeaders['itemHeaders'] : [];
            $hiddenHeaders = isset($userHeaders['hiddenColumns']) ? $userHeaders['hiddenColumns'] : [];
        } else {

            $arrHeaders = $objItemsDs->getMappedItemHeaders($params);
            $hiddenHeaders = isset($arrHeaders['hiddenColumns']) ? $arrHeaders['hiddenColumns'] : [];
            $arrHeaders = isset($arrHeaders['itemHeaders']) ? $arrHeaders['itemHeaders'] : [];
        }

        $sheetHeaders = [];
        foreach ($arrHeaders as $header) {
            if (isset($header['column'])) {
                $sheetHeaders[$header['column']] = $header['name'];
            }
            if (!empty($hiddenHeaders) && $headerType != 2) {
                foreach ($hiddenHeaders as $row) {
                    if (isset($sheetHeaders[$row])) {
                        unset($sheetHeaders[$row]);
                    }
                }
            }
        }
        unset($arrHeaders);
        return $sheetHeaders;
    }

}
