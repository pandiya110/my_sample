<?php

namespace CodePi\Events\DataSource\DataSourceInterface;

interface iEvents {

    public function saveEvents($command);
    public function getEvents($command);
    
}
