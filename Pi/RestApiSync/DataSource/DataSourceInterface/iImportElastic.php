<?php

namespace CodePi\RestApiSync\DataSource\DataSourceInterface;

interface iImportElastic {

    public function getAllData($command);

    public function getSyncData($data);

    public function prepareSynData($data);

    public function importDataToEs($command);
    
    public function syncDataToElastic($command);
}
