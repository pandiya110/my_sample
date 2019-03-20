<?php

namespace CodePi\Admin\DataSource\DataSourceInterface;

interface iDepartments {
    /**
     * 
     * @param type $command
     */
    public function saveDepartments($command);
    /**
     * 
     * @param type $command
     */
    public function getDepartments($command);
}
