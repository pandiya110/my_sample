<?php

namespace CodePi\Users\DataSource\DataSourceInterface;

interface iUsersData {

    public function getUsersData($command);
    public function saveUser($command);

}
