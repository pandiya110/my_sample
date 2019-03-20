<?php

namespace CodePi\Campaigns\DataSource; 

use CodePi\Base\Eloquent\Campaigns;
use CodePi\Base\Eloquent\Events;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;
use CodePi\Base\Commands\CommandFactory;
use CodePi\Base\Eloquent\CampaignsProjects;

class CampaignsDataSource { 
	
    /**
     * Get the list of campaigns
     * @param array $params
     * @return collection
     */
    function getCampaignsList($params) {
        $totalCount = 0;    
        $objCampaigns = new Campaigns();
        $objCampaigns = $objCampaigns->where(function($query) use($params) {
                                        if (isset($params['id']) && !empty($params['id'])) {
                                            $query->where('id', $params['id']);
                                        }
                                    })->where(function($query)use($params) {
                                        if (isset($params['search']) && trim($params['search']) != '') {
                                            //$query->whereRaw("name like '%" . str_replace(" ", "", $params['search']) . "%' ");
                                            $query->whereRaw("name like '%" . $params['search'] . "%' or aprimo_campaign_id like '%" . $params['search'] . "%' ");
                                        }
                                    })->where(function($query)use($params) {
                                        if (isset($params['status']) && trim($params['status']) != '') {
                                            $query->where('status', $params['status']);
                                        }
                                    })->where('name', '!=', '');//->whereRaw('date(end_date) >= current_date()');
                                    /**
                                     * Sorting, default recently modified by desc order
                                     */
                                    if (isset($params['sort']) && !empty($params['sort'])) {
                                        $objCampaigns->orderBy('name', $params['sort']);
                                    } else {
                                        $objCampaigns->orderBy('last_modified', 'DESC');
                                    }
                                    /**
                                     * Paginations
                                     */
                                    if (isset($params['page']) && !empty($params['page'])) {
                                        $objCampaigns = $objCampaigns->paginate($params['perPage']);
                                        $totalCount = $objCampaigns->total();
                                    } else {
                                        $objCampaigns = $objCampaigns->get();
                                    }
                                    $objCampaigns->totalCount = $totalCount;
      
        return $objCampaigns;                                    
    }
    /**
     * Add/Update the campaigns informations
     * @param type $params
     */
    function saveCampaigns($params) {
        
        $saveDetails = [];
        $objCampaigns = new Campaigns();
        $objCampaigns->dbTransaction();
        try {
            $saveDetails = $objCampaigns->saveRecord($params);
            $objCampaigns->dbCommit();
        } catch (Exception $ex) {
            $objCampaigns->dbRollback();
            $exMsg = 'SaveCampaigns->Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);            
            throw new DataValidationException($ex->getMessage(), new MessageBag());
            
        }
        return $saveDetails;
    }
    /**
     * Update the campaigns already assigned to events or not
     * @param array $params
     * @return boolean
     */
    function assignCampaignToEvents($oldCampId = 0, $newCampId = 0) {
        \DB::beginTransaction();
        try {

            $objCampaigns = new Campaigns();
            $objCampaigns->where('id', $newCampId)->update(['assign_status' => '1']);
            $objEvents = new Events();
            $isAssigned = $objEvents->where('campaigns_id', $oldCampId)->count();
            $objCampaigns->where('id', $oldCampId)->update(['assign_status' => ($isAssigned > 0) ? '1' : '0']);
            \DB::commit();
            
        } catch (Exception $ex) {
            \DB::rollback();
            $exMsg = 'assignCampaignToEvents->Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);            
            throw new DataValidationException($ex->getMessage(), new MessageBag());
        }
    }   
    /**
     * 
     * @param type $intEventId
     * @return type
     */
    function getAssignedCampaignsIdByEvents($intEventId = 0) {
        $objEvents = new Events();
        $dbResult = $objEvents->where('id', $intEventId)->first();
        $intCamapignId = isset($dbResult->campaigns_id) ? $dbResult->campaigns_id : "";
        return $intCamapignId;
    }
    /**
     * Remove campaigns form events, if the campaigns is make inactive
     * @param type $intCampaignId
     * @return boolean
     */
    function removeInactiveCampaignsEvent($intCampaignId = 0) {
        \DB::beginTransaction();
        try {
            $objEvents = new Events();
            $objEvents->where('campaigns_id', $intCampaignId)->update(['campaigns_id' => 0]);
            $this->assignCampaignToEvents($intCampaignId, $newCampId = 0);
            \DB::commit();
        } catch (\Exception $ex) {
            \DB::rollback();
            $exMsg = 'removeInactiveCampaignsEvent->Message:' . $ex->getMessage() . ' ;Line No:' . $ex->getLine() . ';File:' . $ex->getFile();
            CommandFactory::getCommand(new \CodePi\ImportExportLog\Commands\ErrorLog(array('message' => $exMsg)), TRUE);            
            throw new DataValidationException($ex->getMessage(), new MessageBag());
            
        }
        return true;
    }
    
    /**
     * Get campaigns list by search values
     * @param array $params
     * @return collection
     */
    function getCampaignsDropdown($params) {
              
        $objCampaigns = new Campaigns();
        $objCampaigns = $objCampaigns->where(function($query) use($params) {
                                        if (isset($params['id']) && !empty($params['id'])) {
                                            $query->where('id', $params['id']);
                                        }
                                    })->where(function($query)use($params) {
                                        if (isset($params['search']) && trim($params['search']) != '') {
                                            //$query->whereRaw("name like '%" . $params['search'] . "%' or aprimo_campaign_id like '%" . str_replace(" ", "", $params['search']) . "%' ");
                                            $query->whereRaw("concat(name,'-',aprimo_campaign_id) like '%" . $params['search'] . "%' ");
                                        }                                    
                                    })->where('status', '1')->where('name', '!=', '')->orderBy('name', 'asc')->limit(50)->get();
                                   
      
        return $objCampaigns;                                    
    }
    /**
     * Get list of projects by campaigns id and search values
     * @param type $params
     * @return collection
     */
    function getProjectsByCampaignsID($params) {
        
        $objCampProject = new CampaignsProjects();
        $intCampId = isset($params['campaigns_id']) ? $params['campaigns_id'] : 0;
        $dbData = $objCampProject->where('campaigns_id', $intCampId)
                                 ->where(function($query)use($params) {
                                    if (isset($params['search']) && trim($params['search']) != '') {
                                        $query->whereRaw("concat(title,'-',aprimo_project_id) like '%" . $params['search'] . "%' ");
                                    }
                                 })->where('title', '!=', '')
                                   ->orderBy('title', 'asc')
                                   //->limit(50)
                                   ->get();
        return $dbData;
    }

}
