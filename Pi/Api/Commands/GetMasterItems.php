<?php

namespace CodePi\Api\Commands;

use CodePi\Base\Libraries\PiLib;
use CodePi\Base\Commands\BaseCommand;
/**
 * Class GetMasterItems
 * This command class for to get the only api columns data from master items
 * This classs extends from Class BaseCommand
 */
class GetMasterItems extends BaseCommand{  
    /**
     * @access public
     * @var array $item_nbr
     */            
    public $item_nbr;
    /**
     * @access public 
     * @var string $search_key
     */
    public $search_key;
    /**
     * Constructor 
     * @param array $data
     */
    function __construct($data) {

        parent::__construct(empty($data['id']));
        $this->item_nbr = PiLib::piIsset($data, 'item_nbr', []);
        $this->search_key = PiLib::piIsset($data, 'search_key', 'searched_item_nbr');
        
    }

}
