<?php

namespace CodePi\RestApiSync\DataTransformers;

use CodePi\Base\DataTransformers\PiTransformer;
use CodePi\Base\Libraries\PiLib;
use CodePi\RestApiSync\DataSource\EventsDataSource;

class EventsTransformer extends PiTransformer {

    function transform($objEvents) {
        $objEventsDs = new EventsDataSource();
        return $this->filterData([
                    'id' => $objEvents->id,
                    'name' => PiLib::filterStringDecode($objEvents->name),
                    'start_date' => $objEvents->start_date,
                    'end_date' => $objEvents->end_date,
                    'status' => $objEventsDs->StatusArray($objEvents->statuses_id),
                    'statuses_id' => $objEvents->statuses_id,
                    'is_draft' => $objEvents->is_draft,
                    'campaigns_id' => $objEvents->campaigns_id,
                    'campaigns_projects_id' => $objEvents->campaigns_projects_id,
                    'aprimo_campaign_name' => PiLib::filterStringDecode($objEvents->campaigns_name),
                    'aprimo_campaign_id' => $objEvents->aprimo_campaign_id,
                    'aprimo_project_name' => PiLib::filterStringDecode($objEvents->aprimo_project_name),
                    'aprimo_project_id' => $objEvents->aprimo_project_id,
                    'created_by' => $objEvents->created_by,
                    'last_modified_by' => $objEvents->last_modified_by,
                    'date_added' => $objEvents->date_added,
                    'last_modified' => $objEvents->last_modified,
                    'ip_address' => $objEvents->ip_address
        ]);
    }

}
