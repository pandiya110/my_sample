<?php

namespace CodePi\Items\DataSource\DataSourceInterface;

interface iItems {
        
        public function saveItem();
        public function deleteItem();
        public function editItem();
        public function updateStatus();
}
