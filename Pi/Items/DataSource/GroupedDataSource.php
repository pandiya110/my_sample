<?php

namespace CodePi\Items\DataSource;

use CodePi\Base\DataSource\DataSource;
use CodePi\Base\Eloquent\ItemsGroups;
use CodePi\Base\Eloquent\ItemsGroupsItems;
use CodePi\Base\Eloquent\Items;
use DB;
use CodePi\Base\Eloquent\Events;
use CodePi\Items\DataSource\ItemsDataSource;
/**
 * Class : GroupedDataSource
 * Handle the Grouped Items Add/Edit/Listing and all Items Actions
 */
class GroupedDataSource {

    function __construct() {
        
    }

    /**
     * To get the grouped Items list 
     * 
     * @param type $command
     * @return Array $arrResponse
     */
    function getGroupedItems($command) {
        $arrItemsID = [];
        $groupName = '';
        $params = $command->dataToArray();
        $objItemsGroup = new ItemsGroups();
        $result = $objItemsGroup->dbTable('i')
                ->select('ig.items_id', 'i.name')
                ->leftJoin('items_groups_items as ig', 'ig.items_groups_id', '=', 'i.id')
                ->where('i.events_id', $params['event_id'])
                ->where('i.items_id', $params['parent_item_id'])
                ->get()->toArray();
       if(!empty($result)){
           foreach ($result as $row){
               $arrItemsID[] = $row->items_id;
               $groupName = $row->name;
           }
       }
        
        return array('items_id' => $arrItemsID, 'name' => $groupName);
    }

    /**
     * To get the Items list which can be grouped 
     * 
     * @param type $command
     * @return Array $arrResponse
     */
    function getItemsGroupList($command) {
        $params = $command->dataToArray();
        $objItems = new Items();
        $objResult = $objItems->dbTable('i')
                ->leftJoin('items_non_editable as ine', 'ine.items_id', '=', 'i.id')
                ->select('i.id as items_id', DB::raw('CONCAT(i.searched_item_nbr, " - ", ine.signing_description) AS value'))
                ->whereIn('i.id', $params['items_id'])
                ->where('i.events_id', $params['event_id'])
                ->where(function($query)use($params) {
            if (isset($params['search']) && trim($params['search']) != '') {
                $query->where('i.searched_item_nbr', 'like', '%' . $params['search'] . '%');
            }
        });
        if (isset($params['page']) && !empty($params['page']) && $params['is_export'] == false) {
            $objResult = $objResult->paginate($params['perPage']);
        } else {
            $objResult = $objResult->get();
        }
        return $objResult;
    }   
    
    /**
     * 
     * @param type $command
     * @return type
     */
    function addGroupItems($command) {
        \DB::beginTransaction();
        $status = false;
        $params = $command->dataToArray();
        $child_items = [];
        
        try {

            $params['items_groups_id'] = isset($params['items_groups_id']) ? $params['items_groups_id'] : 0;
            $objGroupItems = new ItemsGroups();
            $checkItemInGroup = $this->checkItemInGroup($params);


            $checkGroupExists = $objGroupItems->where('id', $params['items_groups_id'])->first();
            if (!empty($checkGroupExists)) {
                $params['id'] = $checkGroupExists->id;
                $params['name'] = $checkGroupExists->name;
                $params['items_id'] = $checkGroupExists->items_id;
            } else {
                $params['id'] = '';
            }

            $groupId = $objGroupItems->saveRecord($params);
            $parent_item = isset($params['items_id']) ? $params['items_id'] : 0;
            unset($params['items_id'], $params['id']);

            $objItemsGroupsItems = new ItemsGroupsItems();
            if (!empty($groupId->id)) {
                $checkItemInGroupItems = $this->checkItemInGroupItems($params);

                if (empty($checkItemInGroupItems)) {

                    foreach ($params['items'] as $item) {
                        if ($item != $parent_item) {
                            $params['items_id'] = $item;
                            $params['items_groups_id'] = $groupId->id;
                            $objResult = $objItemsGroupsItems->saveRecord($params);
                            $child_items[] = $objResult->items_id;
                        }
                    }
                    $status = true;
                }
                /**
                 * Update recent record into items table
                 */
                $objItems = new Items();
                $objItems->where('id', $groupId->items_id)->update(['last_modified' => $params['last_modified'], 
                                                                    'last_modified_by' => $params['last_modified_by'],
                                                                    'ip_address' => $params['ip_address'],
                                                                    'gt_last_modified' => $params['gt_last_modified']
                                                                    ]);
                
            }
            
            $this->updateGroupedItemsColumn(array_merge([$parent_item], $child_items), $params['name']);
            \DB::commit();
        } catch (\Exception $ex) { 
            
            \DB::rollback();
        }

        return array('items_id' => $parent_item, 'deleted_items' => $child_items, 'status' => $status);
    }
    
    /**
     * 
     * @param type $params
     * @return type
     */
    function checkItemInGroup($params) {
        $id = 0;
        $objItemsGroups = new ItemsGroups();
        $result = $objItemsGroups->where('events_id', $params['events_id'])                                   
                                 ->where('items_id', $params['items_id'])->first();
        if(!empty($result)){
            $id = $result->items_id;
        }
        return $id;
    }
    /**
     * 
     * @param type $params
     * @return type
     */
    function checkItemInGroupItems($params) {
        $objItemsGroupsItems = new ItemsGroupsItems();    
        $result = $objItemsGroupsItems->whereIn('items_id', $params['items'])->count();
        $query = \DB::getQueryLog();        
        return $result;
    }
    /**
     * 
     * @param type $params
     * @return type
     */
    function checkParentItemsExistsAsChildItems($params) {
        $objItemsGroups = new ItemsGroups();    
        $result = $objItemsGroups->whereIn('items_id', $params['items'])->count();                
        return $result;
    }

    /**
     * To get the groups list 
     * 
     * @param type $command
     * @return Array $arrResponse
     */
    function getGroupsList($command) {
        $params = $command->dataToArray();
        $objItemsGroups = new ItemsGroups();
        $result = $objItemsGroups->select('id', 'name')->where('events_id', $params['event_id'])->get();
        return $result;
    }
    
    /**
     * 
     * @param type $command
     * @return type
     */
    function unGroupItems($command) {

        $status = false;
        $childItems = $deleted_items = $items_id = [];
        $total = 0;
        \DB::beginTransaction();
        try {
            $params = $command->dataToArray();            
            if (is_array($params['items_id']) && !empty($params['items_id'])) {
                
                $objGroupItems = new ItemsGroups();
                $objItemsGroupsItems = new ItemsGroupsItems();
                $checkCount = $objGroupItems->whereIn('items_id', $params['items_id'])->count();
                
                if ($checkCount > 0) {
                    $result = $objGroupItems->dbTable('ig')
                            ->join('items_groups_items as igi', 'ig.id', '=', 'igi.items_groups_id')
                            ->select('igi.items_id')
                            ->whereIn('ig.items_id', $params['items_id'])
                            ->get()
                            ->toArray();
                    foreach ($result as $row) {
                        $childItems[] = (string) $row->items_id;
                    }                    
                }
                
               
                if ($checkCount == 0) {                    
                    $deleted_items[] = $params['items_id'];
                }
                
                $sub_items = !empty($childItems) ? $childItems : $params['items_id'];                    
                $objItemsGroupsItems->whereIn('items_id', $sub_items)->delete();
                /**
                 * if ungroup all child items from group, break the grouped realtions                 
                 */
                if (isset($params['parent_item_id']) && !empty($params['parent_item_id'])) {
                    
                    $isEmptyGroup = $objGroupItems->dbTable('ig')
                                                  ->join('items_groups_items as igi', 'ig.id', '=', 'igi.items_groups_id')
                                                  ->where('ig.items_id', $params['parent_item_id'])
                                                  ->count();

                    if ($isEmptyGroup == 0) {
                        $params['items_id'] = array_merge($params['items_id'], [(string)$params['parent_item_id']]);
                    }
                }
                
                $objGroupItems->whereIn('items_id', $params['items_id'])->delete();                
                $this->updateGroupedItemsColumn(array_merge($params['items_id'], $childItems), '');
                $status = true;
            }
            $items_id = array_merge($childItems, $params['items_id']);
            
            \DB::commit();
        } catch (Exception $ex) {            
            \DB::rollback();
        }
        
        return array('items_id' => $items_id, 'deleted_items' => $deleted_items, 'status' => $status);
    }
    
    /**
     * Duplicate child items inside the parent items
     * @param type $params
     */
    function duplicateGroupedItems($params) {
        \DB::beginTransaction();
        try {
            if (isset($params['duplicate_item']) && !empty($params['duplicate_item'])) {
                $objGroupItems = new ItemsGroups();
                $objItemsGroupsItems = new ItemsGroupsItems();
                $arrData = $arrItemsId = [];
                $newItems = array_keys($params['duplicate_item']);
                $result = $objItemsGroupsItems->whereIn('items_id', $newItems)->get()->toArray();
                
                if (!empty($result)) {
                    foreach ($result as $row) {
                        
                        if (isset($params['duplicate_item'][$row['items_id']])) {
                            unset($row['id']);
                            $row['items_id'] = $params['duplicate_item'][$row['items_id']];  
                            $parent_items = $objGroupItems->where('id', $row['items_groups_id'])->first();
                            $name = isset($parent_items->name) ? $parent_items->name : '';
                            $arrData[] = $row;                            
                            $this->updateGroupedItemsColumn([$row['items_id']], $name);
                        }
                        
                    }
                    
                    if (!empty($arrData)) {                        
                        $objItemsGroupsItems->insertMultiple($arrData);
                    }
                    
                }
                
            }
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }
    }
    /**
     * Duplicate parent item as a child items
     * @param type $params
     */
    function duplicateParentItem($params) {
        \DB::beginTransaction();
        try {
            $objGroupItems = new ItemsGroups();
            $objItemsGroupsItems = new ItemsGroupsItems();
            $newItems = array_keys($params['duplicate_item']);

            $result = $objGroupItems->whereIn('items_id', $newItems)->get()->toArray();

            if (!empty($result)) {
                foreach ($result as $row) {
                    if (isset($params['duplicate_item'][$row['items_id']])) {
                        $row['items_id'] = $params['duplicate_item'][$row['items_id']];
                        $row['items_groups_id'] = $row['id'];
                        unset($row['id']);
                        $objItemsGroupsItems->saveRecord($row);
                        $this->updateGroupedItemsColumn([$row['items_id']], $row['name']);
                    }
                }
            }
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }
    }

    /**
     * 
     * @param array $arrItemsID
     * @return type
     */
    function getChildItemsByParentItemId(array $arrItemsID) {
        $objGroupItems = new ItemsGroups();

        $result = $objGroupItems->whereIn('items_id', $arrItemsID)->get()->toArray();
        $arrGroupID = $arrChildItemsID = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                $arrGroupID[] = $row['id'];
            }
            $objItemsGroupsItems = new ItemsGroupsItems();
            $response = $objItemsGroupsItems->whereIn('items_groups_id', $arrGroupID)->get()->toArray();
            foreach ($response as $val) {
                $arrChildItemsID[] = (string)$val['items_id'];
            }
        }
        unset($arrGroupID);
        return $arrChildItemsID;
    }
    /**
     * 
     * @param type $params
     */
    function saveAppendReplaceGroupItems($params) {
        \DB::beginTransaction();
        try {
            $objItemsGroupsItems = new ItemsGroupsItems();
            $objGroupItems = new ItemsGroups();
            if (isset($params['old_item_id']) && !empty($params['old_item_id'])) {
                $isExistsParent = $objGroupItems->whereIn('items_id', $params['old_item_id'])->where('events_id', $params['events_id'])->get()->toArray();
                $isExistsChild = $objItemsGroupsItems->whereIn('items_id', $params['old_item_id'])->count();
                if ($isExistsChild > 0) {
                    $parent_item = isset($params['parent_item_id']) ? $params['parent_item_id'] : 0;
                    $result = $objGroupItems->where('items_id', $parent_item)->where('events_id', $params['events_id'])->first();
                    if (isset($result->id) && !empty($result->id)) {
                        $objItemsGroupsItems->whereIn('items_id', $params['old_item_id'])->delete();
                        $data['id'] = '';
                        $data['items_groups_id'] = isset($result->id) ? $result->id : 0;
                        $data['items_id'] = is_array($params['new_item_id']) ? $params['new_item_id'][0] : $params['new_item_id'];
                        $objItemsGroupsItems->saveRecord($data);
                    }
                } else if (count($isExistsParent) > 0) {
                    $items_id = is_array($params['new_item_id']) ? $params['new_item_id'][0] : $params['new_item_id'];
                    $objGroupItems->whereIn('items_id', $params['old_item_id'])->where('events_id', $params['events_id'])->update(['items_id' => $items_id]);
                }
            }
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }
    }

    /**
     * 
     * @param type $intEventId
     * @return type
     */
    function getEventsInformationsById($intEventId){
        $arrInfo = [];
        $objEvents  = new Events();
        $sql = 'SELECT e.id, s.name AS statusname, e.name AS eventname
                FROM events AS e
                INNER JOIN statuses AS s ON s.id = e.statuses_id
                WHERE e.id = '.$intEventId.'';
        $result = $objEvents->dbSelect($sql);
        foreach ($result as $row){
            $arrInfo['event_id'] = \CodePi\Base\Libraries\PiLib::piEncrypt($row->id);
            $arrInfo['event_status'] = $row->statusname;
        }
        return $arrInfo;
    }
    /**
     * 
     */
    function getGroupedItemsCount($intItemsid) {
        $objGroupItems = new ItemsGroups();
        $groupCount = 0;
        $sql = 'SELECT COUNT(*) AS cnt
                FROM items_groups AS ig
                LEFT JOIN items_groups_items AS igi ON igi.items_groups_id = ig.id
                WHERE ig.items_id = '.$intItemsid.'
                ORDER BY ig.id ASC
                LIMIT 1';
        $result = $objGroupItems->dbSelect($sql);
        foreach ($result as $row) {
            $groupCount = $row->cnt;
        }
        return $groupCount;
    }
    /**
     * 
     * @param type $itemsId
     * @param type $groupName
     */
    function updateGroupedItemsColumn($itemsId, $groupName) {
        \DB::beginTransaction();
        try {            
            if(!empty($itemsId)){
            $objItemEdit = new \CodePi\Base\Eloquent\ItemsEditable();
            $objItemEdit->whereIn('items_id', $itemsId)->update(['grouped_item' => $groupName]);
            }
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }
    }
    
    /**
     * This is method used to find the selected items is a grouped item or not, 
     * If it's grouped items, along with child items also will be move to linked item, non movable items will be removed from grouped items,
     * after items moved to linked items , grouped relation will be removed.
     * @param array $params
     * @return Array
     */
    function moveGroupedItems($params) {

        $non_movable = $movable = $arrMoveId = [];
        DB::beginTransaction();
        try {
            if (!empty($params['items_id']) && !empty($params['events_id'])) {
                $objItemsGroupsItems = new ItemsGroupsItems();
                $objGroupItems = new ItemsGroups();
                $arrItems = $this->getChildItemsByParentItemId($params['items_id']);

                if (!empty($arrItems)) {
                    $objItemsDs = new ItemsDataSource();
                    $isMove = $objItemsDs->isMovable($arrItems, $params['events_id']);
                    $final = array_intersect($arrItems, $isMove);
                    foreach ($arrItems as $item) {
                        if (!in_array($item, $final)) {
                            $non_movable[] = $item;
                        } else {
                            $movable[] = $item;
                        }
                    }
                    if (!empty($non_movable)) {
                        $objItemsGroupsItems->where('items_id', $non_movable)->delete();
                        $this->updateGroupedItemsColumn($non_movable, '');
                    }
                }
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
        }

        $arrMoveId = array_merge($movable, $params['items_id']);
        
        return $arrMoveId;
    }

    /**
     * Add items to group , while move items from linked to result
     * @param type $params
     * @param type $parent_item_id
     */
//    function saveMovedItemsInGroup($params, $parent_item_id) {
//        DB::beginTransaction();
//        try {
//            $objItemsGroupsItems = new ItemsGroupsItems();
//            $insert = [];
//            if (!empty($parent_item_id)) {
//                $objGroups = new ItemsGroups();
//                $result = $objGroups->whereIn('items_id', $parent_item_id)->get()->toArray();
//                if (!empty($result)) {
//                    foreach ($params['items_id'] as $row) {
//                        $row['items_groups_id'] = isset($result[0]) && isset($result[0]['id']) ? $result[0]['id'] : '';
//                        $row['items_id'] = $row;
//                        $insert[] = $row;
//                    }
//                    if (!empty($insert)) {
//                        $objItemsGroupsItems->insertMultiple($insert);
//                    }
//                    $group_name = isset($result[0]['name']) ? $result[0]['name'] : '';
//                    $itemsId = array_merge($parent_item_id, $params['items_id']);
//                    $this->updateGroupedItemsColumn($itemsId, $group_name);
//                }
//            }
//            DB::commit();
//        } catch (\Exception $ex) {
//            DB::rollback();
//        }
//    }
//    
    function saveMovedItemsInGroup($params, $parent_item_id) {
        DB::beginTransaction();
        try {
            $objItemsGroupsItems = new ItemsGroupsItems();
            $insert = [];
            if (!empty($parent_item_id)) {
                $objGroups = new ItemsGroups();
                $supItemsId = $objItemsGroupsItems->where('items_id', $parent_item_id)->first();                
                $result = $objGroups->where('id', $supItemsId->items_groups_id)->get()->toArray();                
                if (!empty($result)) {
                    foreach ($params['items_id'] as $row) {
                        $data['items_groups_id'] = isset($result[0]) && isset($result[0]['id']) ? $result[0]['id'] : 0;
                        $data['items_id'] = $row;
                        $insert[] = $data;
                    }
                    
                    if (!empty($insert)) {
                        $objItemsGroupsItems->insertMultiple($insert);
                    }
                    $group_name = isset($result[0]) && isset($result[0]['name']) ? $result[0]['name'] : '';
                    $itemsId = array_merge([$parent_item_id], $params['items_id']);
                    $this->updateGroupedItemsColumn($itemsId, $group_name);
                }
            }
            DB::commit();
        } catch (\Exception $ex) {            
            DB::rollback();            
        }
    }
    /**
     * 
     * @param type $params
     */
    function addGroupItemsFromEdit($params) {
        \DB::beginTransaction();
        $status = false;
        $items_id = [];
        try {
            $objGroupItems = new ItemsGroups();
            $parent_item = $objGroupItems->whereRaw('lower(trim(name)) = ' . trim(strtolower(\DB::connection()->getPdo()->quote($params['value']))) . '')
                                    ->where('events_id', $params['event_id'])
                                    ->first();

            if (!empty($parent_item)) {
                $objItemsGroupsItems = new ItemsGroupsItems();
                /**
                 * Already exists in group delete from group and add it to selected groups
                 */
                $objItemsGroupsItems->where('items_id', $params['item_id'])->delete();
                $params['id'] = '';
                $params['items_id'] = $params['item_id'];
                $params['items_groups_id'] = $parent_item->id;                
                $result = $objItemsGroupsItems->saveRecord($params);                
                $items_id[] = $result->items_id;
                $status = true;                
            }
            $group_item_id = isset($parent_item->items_id) && !empty($parent_item->items_id) ? $parent_item->items_id : 0;
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
        }
        return array('items_id' => $items_id, 'group_item_id' => $group_item_id);
    }
    /**
     * 
     * @param type $params
     * @return type
     */
    function getDuplicatedGroupedItems($params) {
        $arrNewId = $arrOldId = [];
        if (isset($params['duplicate_item']) && !empty($params['duplicate_item'])) {
            $objGroupItems = new ItemsGroups();
            $original_id = array_keys($params['duplicate_item']);
            $result = $objGroupItems->whereIn('items_id', $original_id)->get()->toArray();
            if (count($result) > 0) {
                foreach ($result as $row) {
                    $arrOldId[] = $row['items_id'];
                    $arrNewId[] = isset($params['duplicate_item'][$row['items_id']]) ? $params['duplicate_item'][$row['items_id']] : [];
                }
            }
            return ['group_item_id' => $arrOldId, 'deleted_items' => $arrNewId];
        }
    }

}
