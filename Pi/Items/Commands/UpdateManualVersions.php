<?php

namespace CodePi\Items\Commands;

use Symfony\Component\HttpFoundation\Session\Session;
use Request,
    Hash,
    Crypt;
use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;

class UpdateManualVersions extends BaseCommand {

    /**
     *
     * @var int
     * @access public
     */
    public $item_id;

    /**
     *
     * @var int
     * @access public
     */
    public $events_id;

    /**
     *
     * @var string, edted value
     * @access public
     */
    public $value;
    public $type;
    public $omit_versions;
    /**
     * Constructor
     * @param array $data
     */
    function __construct($data) {

        parent::__construct($data);
        $this->item_id = PiLib::piIsset($data, 'item_id', 0);
        $this->events_id = (isset($data['event_id']) && !empty($data['event_id'])) ? PiLib::piDecrypt($data['event_id']) : 0;       
        $this->value = isset($data['value']) ? $data['value'] : [];
        $this->omit_versions = isset($data['omit_versions']) ? $data['omit_versions'] : [];
        $this->type = isset($data['type']) ? $data['type'] : 1; //1 -> add/remove 2 -> through add searchitems
        $this->post = $data;
        
    }

}
