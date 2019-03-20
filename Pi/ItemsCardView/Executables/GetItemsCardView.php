<?php

namespace CodePi\ItemsCardView\Executables;

use CodePi\ItemsCardView\DataSource\ItemsCardViewDs;
use CodePi\ItemsCardView\DataTransformers\CardViewTransformers;

class GetItemsCardView {

    private $dataSource;
    private $objCardViewTs;

    /**
     * 
     * @param ItemsCardViewDs $objItemsDs
     * @param CardViewTransformers $objCardViewTs
     */
    public function __construct(ItemsCardViewDs $objItemsDs, CardViewTransformers $objCardViewTs) {
        $this->dataSource = $objItemsDs;
        $this->objCardViewTs = $objCardViewTs;
    }

    /**
     * Execution of Get Card view
     * @param Object of GetItemsCardView $command
     * @return array
     */
    public function execute($command) {

        $collection = $this->dataSource->getCardViewData($command);
        $itemsColumns = $this->dataSource->getPermissionsColumns($command->event_id, $command->columns_array);
        $arrResponse['count'] = isset($collection['collection']) && !empty($collection['collection']) ? count($collection['collection']->toArray()) : 0;
        $arrResponse['items'] = $this->objCardViewTs->customFormatCardView($collection, $command->columns_array, $command->event_id, $command, $itemsColumns);
        $arrResponse['itemsColumns'] = $itemsColumns;
        return $arrResponse;
    }

}