<?php

namespace CodePi\Items\Utils;

use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Libraries\PiLib;
use App\Events\ItemActions;
use CodePi\Items\Commands\GetItemsList;
use CodePi\ItemsCardView\DataTransformers\CardViewTransformers as CardViewTs;

class BroadcastResponse {
    /**
     *
     * @var Array 
     */
    public $arrItemsID;
    /**
     *
     * @var Integer
     */
    public $intEventID;
    /**
     *
     * @var Integer
     */
    public $intParentID;
    /**
     *
     * @var Array
     */
    public $arrResult;
    /**
     *
     * @var String
     */
    public $broadcastAction;
    /**
     *
     * @var Array
     */
    public $broadcastData;
    
    public $objCardViewTs;
    /**
     * 
     * @param Array $postData
     */
    function __construct($postData) {
        if (!empty($postData)) {
            $this->arrItemsID = $postData['items_id'];
            $this->intEventID = PiLib::piEncrypt($postData['events_id']);
            $this->intParentID = isset($postData['parent_item_id']) ? $postData['parent_item_id'] : 0;
            $this->arrResult = $postData['result'];
        }
        $this->objCardViewTs = new CardViewTs();
    }

    /**
     * Set Action for broadcast
     * @param String $action
     */
    function setAction($action) {
        $this->broadcastAction = $action;
    }
    /**
     * Get Action type
     * @return String
     */
    function getAction() {
        return $this->broadcastAction;
    }
    /**
     * Set Data to update into broadcast
     * @param Array $arrData
     */
    function setData($arrData) {
        $this->broadcastData = $arrData;
    }
    /**
     * Get the broadcast response data
     * @return Array
     */
    function getData() {
        return $this->broadcastData;
    }
    /**
     * 
     * @return array
     */
    function getRowData() {

        $arrItemRows = [];
        if (!empty($this->arrItemsID) && !empty($this->intEventID)) {
            $objCommand = new GetItemsList(['items_id' => $this->arrItemsID, 'event_id' => $this->intEventID, 'parent_item_id' => $this->intParentID]);
            $cmdResponse = CommandFactory::getCommand($objCommand);
            $arrItemRows = isset($cmdResponse['items']) ? $cmdResponse['items'] : [];
            $arrItemRows['itemPage'] = $this->pageGroupingArray($arrItemRows['itemValues']);
        }
        $arrItemRows['status'] = isset($this->arrResult['status']) ? $this->arrResult['status'] : $this->arrResult['status'];
        if (isset($this->arrResult['deleted_items'])) {
            $arrItemRows['deleted_items'] = is_array($this->arrResult['deleted_items']) ? $this->arrResult['deleted_items'] : [$this->arrResult['deleted_items']];
        } else if (isset($this->arrResult['deleted_id'])) {
            $arrItemRows['deleted_items'] = is_array($this->arrResult['deleted_id']) ? $this->arrResult['deleted_id'] : [$this->arrResult['deleted_id']];
        } else {
            $arrItemRows['deleted_items'] = [];
        }
        
        return $arrItemRows;
    }

    /**
     * Send Data to Broadcasting
     */
    function updateToBroadcast() {
        $url = config('smartforms.socket_host');
        $port = config('smartforms.socket_id');
        $split = parse_url($url);
        $ip = preg_replace('/^www\./', '', $split['host']);
        
        $isPortOpen = PiLib::isPortOpen($ip, $port);
        if ($isPortOpen) {
            if (!empty($this->getData())) {
                broadcast(new ItemActions($this->getData(), $this->getAction()))->toOthers();
            }
        }
    }
    /**
     * 
     * @param type $arrResponse
     * @return type
     */
    function pageGroupingArray($arrResponse) {
        $arrPage = $array = [];
        if ($arrResponse) {
            foreach ($arrResponse as $data) {
                $page = isset($data['page']) && !empty($data['page']) ? $data['page'] : 0;
                $arrPage[$page]['page'] = $page;
                $arrPage[$page]['itemsCount'][] = $data['id'];
                $arrPage[$page]['versionsCount'][] = $this->objCardViewTs->findVersionsCount([$data['versions']]);
            }
            if ($arrPage) {
                foreach ($arrPage as $values) {
                    $array[] = array('page' => $values['page'], 'itemsCount' => count($values['itemsCount']), 'versionsCount' => array_sum($values['versionsCount']));
                }
            } else {
                $array = array('page' => null, 'itemsCount' => 0, 'versionsCount' => 0);
            }
            unset($arrResponse, $arrPage);
        }
        return array_values($array);
    }

}

?>
