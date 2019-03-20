<?php

namespace CodePi\Items\Executables;

use CodePi\Base\Commands\iCommands;
use CodePi\Items\DataSource\ItemsDataSource as ItemDs;
use CodePi\Base\DataTransformers\DataResponse;
#use CodePi\Events\Commands\GetEventDetails;
use \CodePi\Base\Libraries\PiLib;
#use CodePi\Base\Commands\CommandFactory;
use App\Events\ItemActions;
#use CodePi\Items\Commands\GetItemsList;
use CodePi\Events\DataSource\EventsDataSource;
use CodePi\Items\Utils\ItemsGridDataResponse;
use CodePi\Items\DataSource\CopyItemsDataSource as CopyDs;
use CodePi\Items\DataSource\GroupedDataSource as GroupDs;

/**
 * Handle the execution of delete items
 */
class DeleteEventItem implements iCommands {

    private $dataSource;
    private $objDataResponse;

    /**
     * 
     * @param ItemDs $objItemDs
     * @param DataResponse $objDataResponse
     */
    function __construct(ItemDs $objItemDs, DataResponse $objDataResponse) {
        $this->dataSource = $objItemDs;
        $this->objDataResponse = $objDataResponse;
    }

    /**
     * @param object $command
     * @return array $arrResponse
     */
    function execute($command) {

        $arrResponseParent = [];
        $objResult = $this->dataSource->deleteEventItem($command);
        $deleteResponse = ['status' => $objResult, 'deleted_items' => $command->id];
        $objEventDs = new EventsDataSource();
        $arrEvent = $objEventDs->getEventAdditionalInfoByPermissions($command);
        $arrResponse = array_merge($deleteResponse, $arrEvent);

        $objCopyDs = new CopyDs();
        $dataParent['items_id'] = [$command->parent_id];
        $dataParent['event_id'] = $command->event_id;
        $returnResult['objResult'] = $objCopyDs->getItemListById($dataParent);
        $users_id = (isset($command->users_id) && $command->users_id != 0) ? $command->users_id : $command->last_modified_by;
        $returnResult['permissions'] = $this->dataSource->getAccessPermissions($users_id);
        if (!empty($command->parent_item_id)) {
            $objGroupDs = new GroupDs();
            $itemCount = $objGroupDs->getGroupedItemsCount($command->parent_item_id);
            $arrResponse['itemCount'] = array('item' => $itemCount);
        }
        $objGridResponse = new ItemsGridDataResponse();
        $arrResponseParent = $objGridResponse->getGridResponse($returnResult, $command);
        $arrResponseParent['event_id'] = PiLib::piEncrypt($command->event_id);
        if ($arrResponse) {
            broadcast(new ItemActions($arrResponseParent, 'update'));
            broadcast(new ItemActions($arrResponse, 'remove'))->toOthers();
        }

        return $arrResponse;
    }

}
