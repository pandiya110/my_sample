<?php

namespace CodePi\RestApiSync\DataSource\DataSourceInterface;

interface iEvents {

    public function getEvents($command);

    public function getSyncData($data);

    public function prepareSynData($data);
}
