<?php

namespace CodePi\Channels\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class SaveItemsChannels extends BaseCommand {

    /**
     *
     * @var int
     */
    public $id;

    /**
     *
     * @var int
     */
    public $items_id;

    /**
     *
     * @var int
     */
    public $channels_id;

    /**
     *
     * @var array
     */
    public $channels_adtypes_id;
    /**
     *
     * @var int
     */
    public $events_id;
    /**
     *
     * @var int 
     */
    public $parent_item_id;


    /**
     * 
     * @param array $data['id']                 = (int)
     *                   ['items_id']           = (int) Row id of particular item
     *                   ['channels_id']        = (int) Channels ID
     *                   ['channels_adtypes_id']= (array)  This is selected array values of adtypes
     *                   ['event_id']           = (string) This is a Event id, it should be Encrypted value
     */
    function __construct($data) {
        parent::__construct(empty($data['id']));
        $this->id = PiLib::piIsset($data, 'id', '');
        $this->items_id = PiLib::piIsset($data, 'items_id', '');
        $this->channels_id = PiLib::piIsset($data, 'channels_id', '');
        $this->channels_adtypes_id = PiLib::piIsset($data, 'channels_adtypes_id', []);
        $this->events_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;
        $this->parent_item_id = PiLib::piIsset($data, 'parent_item_id', 0);
        $this->post = $data;
    }

}
