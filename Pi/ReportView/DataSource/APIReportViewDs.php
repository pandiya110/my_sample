<?php

namespace CodePi\ReportView\DataSource;

use CodePi\ReportView\DataSource\DataSourceInterface\iItemsReportView;
use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Libraries\PiLib;
use Auth,
    Session;
use App\User;
use CodePi\Base\DataSource\Elastic;
use CodePi\Items\Utils\ItemsUtils;
use CodePi\Base\Eloquent\Events;

class APIReportViewDs implements iItemsReportView{
    /**
     * 
     * @param type $command
     * @return type
     */
    function getReportViewData($command) {
        $params = $command->dataToArray();
        $headerType = ($params['item_type'] == '0') ? 0 : 2;        
        $arrResult = $arrData = [];
        $limit = $params['perPage'];
        $offset = ($params['page'] - 1) * $limit;
        $totalRecord = $this->eSearchCount('sm_items', 'items', 'must', $headerType);
        $totalPage = ceil($totalRecord / $limit);
        unset($params['page'], $params['perPage']);
        
        $arrResult = $this->getDataFromESearch('sm_items', 'items', 'must', $limit, $offset, $headerType);
        $arrData = $this->formatResult($arrResult, $headerType);   
        unset($arrResult);
        return array('itemValues' => $arrData, 'itemCount' => array('item' => $totalRecord), 'count' => $totalRecord, 'lastpage' => $totalPage);
        
    }
    /**
     * 
     * @param type $index
     * @param type $type
     * @param type $conditions
     * @param type $limit
     * @param type $offset
     * @param type $headerType
     * @return type
     */
    function getDataFromESearch($index, $type, $conditions = 'must', $limit, $offset, $headerType = 0) {
        $match = [];
        $params['index'] = $index;
        $params['type'] = $type;
        $params['from'] = $offset;
        $params['size'] = $limit;

        if ($headerType != 2) {
            $match[] = array('match' => array('items_type' => false));
            $bool['bool'] = array($conditions => array($match));
        } else {
            $match[] = array('match' => array('items_type' => true));
            $bool['bool'] = array($conditions => array($match));
        }

        $params['body'] = array('query' => $bool);
        //print_r(\GuzzleHttp\json_encode($params));exit;
        $objElastic = new Elastic();
        $result = $objElastic->search($params);
        $response = $objElastic->formResult($result);
        return $response;
    }
    /**
     * 
     * @param type $index
     * @param type $type
     * @param type $conditions
     * @param type $headerType
     * @return type
     */
    function eSearchCount($index, $type, $conditions = 'must', $headerType = 0) {
        $match = [];
        $params['index'] = $index;
        $params['type'] = $type;

        if ($headerType != 2) {
            $match[] = array('match' => array('items_type' => false));
            $bool['bool'] = array($conditions => array($match));
        } else {
            $match[] = array('match' => array('items_type' => true));
            $bool['bool'] = array($conditions => array($match));
        }

        $params['body'] = array('query' => $bool);
        $objElastic = new Elastic();
        $result = $objElastic->search($params);
        $total = isset($result['hits']) && isset($result['hits']['total']) ? $result['hits']['total'] : 0;
        return $total;
    }
    /**
     * 
     * @param type $result
     * @param type $headerType
     * @return type
     */
    function formatResult($result, $headerType = 0) {

        $arrData = $channels = [];
        if ($result) {
            foreach ($result as $val) {

                $val['cost'] = isset($val['cost']) ? ItemsUtils::formatPriceValues($val['cost']) : '';
                $val['base_unit_retail'] = isset($val['base_unit_retail']) ? ItemsUtils::formatPriceValues($val['base_unit_retail']) : '';
                $val['events_id'] = isset($val['events_id']) ? PiLib::piEncrypt($val['events_id']) : 0;

                if ($headerType == 0) {
                    if (isset($val['channels'])) {
                        $channels = $this->formatChannels($val['channels'], $val['id']);
                        unset($val['channels']);
                    }
                    //$val['no_of_linked_item'] = $this->getLinkedItemsCountByUpc($val['events_id'], $val['upc_nbr']);
                    $val['price_id'] = isset($val['price_id']) ? trim(strtoupper($val['price_id'])) : '';
                    $val['dotcom_price'] = isset($val['dotcom_price']) ? ItemsUtils::formatPriceValues($val['dotcom_price']) : '';
                    $val['advertised_retail'] = isset($val['advertised_retail']) ? ItemsUtils::formatAdRetaliValue($val['advertised_retail']) : '';
                    $val['was_price'] = isset($val['was_price']) ? ItemsUtils::formatPriceValues($val['was_price']) : '';
                    $val['save_amount'] = isset($val['save_amount']) ? ItemsUtils::formatPriceValues($val['save_amount']) : '';
                    $val['forecast_sales'] = isset($val['forecast_sales']) ? ItemsUtils::formatPriceValues($val['forecast_sales']) : '';
                    $val['adretails_highlight'] = ItemsUtils::getAdRetailSoldOutStaus((object) $val);
                    $val['grouped_item'] = isset($val['grouped_item']) && !empty($val['grouped_item']) ? $val['grouped_item'] : '';
                    $val['acitivity'] = isset($val['id']) ? $val['id'] : 0;
                    $val['status'] = !empty($val['status']) ? $val['status'] : '';                    
                    $val['priority'] = !empty($val['priority']) ? $val['priority'] : '--';
                    $val['local_sources'] = isset($val['local_sources']) && !empty($val['local_sources']) && ($val['local_sources'] != 'Yes') ? 'No - '.$val['local_sources'] : 'Yes';
                    $val['landing_url'] = isset($val['landing_url']) ? ItemsUtils::addhttp($val['landing_url']) : '';
                    $val['landing_url'] = PiLib::isValidURL($val['landing_url']);
                    $val['item_image_url'] = isset($val['item_image_url']) ? PiLib::isValidURL($val['item_image_url']) : '';
                    $val['original_image'] = PiLib::isValidURL($val['item_image_url']);
                    if (!empty($val['item_image_url'])) {
                        $val['item_image_url'] = $val['item_image_url'] . config('smartforms.iqsThumbnail_60x60');
                    }
                    $val['dotcom_thumbnail'] = isset($val['dotcom_thumbnail']) && (!empty($val['dotcom_thumbnail'])) ? PiLib::isValidURL($val['dotcom_thumbnail']) : PiLib::isValidURL($val['original_image']);
                    if (!empty($val['dotcom_thumbnail'])) {
                        $val['dotcom_thumbnail'] = $val['dotcom_thumbnail'] . config('smartforms.iqsThumbnail_60x60');
                    }
                }
                $arrData[] = array_merge($val, isset($channels[$val['id']]) ? $channels[$val['id']] : []);
            }
        }
        unset($result);
        return $arrData;
    }

    /**
     * 
     * @param type $intEvent
     * @param type $upcNbr
     * @return type
     */
    function getLinkedItemsCountByUpc($intEvent, $upcNbr) {
        $total = 0;
        if (!empty($upcNbr)) {
            $intEvent = PiLib::piDecrypt($intEvent);
            $objElastic = new Elastic();
            $params['index'] = 'sm_items';
            $params['type'] = 'items';
            $searchArray = array('events_id' => $intEvent, 'upc_nbr' => $upcNbr, 'items_type' => true);
            foreach ($searchArray as $key => $value) {
                $match[] = array('match' => array($key => $value));
            }
            $params['body'] = array('query' => array('bool' => array('must' => $match)));
            $result = $objElastic->search($params);
            $total = isset($result['hits']) && isset($result['hits']['total']) ? $result['hits']['total'] : 0;
        }
        return $total;
    }
    /**
     * 
     * @param type $val
     * @param type $id
     * @return type
     */
    function formatChannels($val, $id) {
        $array = [];
        if ($val) {
            foreach ($val as $v) {
                $channel[$v['channel_name']][] = $v['ad_types_name'];
            }
            foreach ($channel as $k => $value) {
                $array[$id][$k] = implode(',', $value);
            }
        }
        return $array;
    }

}
