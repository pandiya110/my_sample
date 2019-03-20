<?php

namespace CodePi\Base\DataTransformers;

use League\Fractal\TransformerAbstract;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

class DataResponse {

    function itemFormat($data, TransformerAbstract $objTransFormer) {
        $fractal = new Manager ();
        $resource = new Item($data, $objTransFormer);
        return $fractal->createData($resource)->toArray() ['data'];
    }

    function collectionFormat($data, TransformerAbstract $objTransFormer) {
        $fractal = new Manager ();
        $resource = new Collection($data, $objTransFormer);
        return $fractal->createData($resource)->toArray() ['data'];
    }

    function customFormat($data, TransformerAbstract $objTransFormer) {
        $result = $this->collectionFormat($data, $objTransFormer);

        $response = array();
        if (!empty($result)) {

            foreach ($result as $l => $m) {

                foreach ($m as $a => $b) {
                    $response[$a] = $b;
                }
            }
        }

        return $response;
    }

}
