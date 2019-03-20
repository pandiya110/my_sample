<?php

namespace CodePi\Base\DataSource;

use Elasticsearch\ClientBuilder;

class Elastic {

    public $parent_id;

    public function eSearch($index = '', $type = '', $source = [], $search = '', $matchPath = '', $matchIn = [], $matchOut = [], $condition = 'should', $innerHits = false) {
        $host = array(config('smartforms.elasticSearchHost'));
        //$host = array('http://192.168.1.2:9200');
        $client = ClientBuilder::create()->setHosts($host)->build();

        $nested = $this->nestedArr($matchOut, $matchPath, $search, $innerHits);
        $match = $this->matchIn($matchIn, $innerHits);

        $params['index'] = $index;
        $params['type'] = $type;
        $params['body'] = array('_source' => $source,
            'query' => array(
                'bool' => array(
                    $condition => [
                        $match, ['nested' => $nested]
                    ]
                )
            )
        );
        $result = $client->search($params);
        return $this->formResult($result);
    }

    public function nestedArr($matchOut = [], $path = '', $search = '', $innerHits = false) {

        $match = [];
        $inner = array('inner_hits' => ['highlight' => ['fields' => []]]);
        foreach ($matchOut as $val) {
            $match[] = ['match' => [ "$path." . "$val" => $search]];
        }
        $return = array('path' => $path,
            'score_mode' => 'max',
            'query' => ['bool' => ['should' => $match]]);
        if ($innerHits) {
            $return = array_merge($return, $inner);
        }
        return $return;
    }

    public function matchIn($matchIn = [], $search = '') {

        $match = [];
        foreach ($matchIn as $val) {
            $match[] = ['match' => [ $val => $search]];
        }
        return $match;
    }

    public function formResult($result) {

        if (!empty($result['hits']) && !empty($result['hits']['hits']) && !empty($result['hits']['hits'][0]['inner_hits'])) {

            foreach ($result['hits']['hits'][0]['inner_hits']['events']['hits']['hits'] as $value) {
                $response[] = $value['_source'];
            }
        } elseif (!empty($result['hits']) && !empty($result['hits']['hits'])) {

            foreach ($result['hits']['hits'] as $value) {
                //print_r($value);exit;
                $value['_source']['id'] = $value['_id'];
                //$value['_source']['sm_events'] = $value['_source']['sm_events']['id']; 
                $response[] = $value['_source'];
            }
        } else {
            $response = [];
        }
        return $response;
    }

    public function insert($params) {
        $host = array(config('smartforms.elasticSearchHost'));
        //$host = array('http://192.168.1.2:9200');
        $client = ClientBuilder::create()->setHosts($host)->build();

        return $client->create($params);
    }

    public function update($params) {
        $host = array(config('smartforms.elasticSearchHost'));
        //$host = array('http://192.168.1.2:9200');
        $client = ClientBuilder::create()->setHosts($host)->build();

        return $client->update($params);
    }

    public function delete($params) {
        $host = array(config('smartforms.elasticSearchHost'));
        //$host = array('http://192.168.1.2:9200');
        $client = ClientBuilder::create()->setHosts($host)->build();
        return $client->delete($params);
    }

    public function search($params) {
        $host = array(config('smartforms.elasticSearchHost'));        
        //$host = array('http://192.168.1.2:9200');
        $client = ClientBuilder::create()->setHosts($host)->build();
        return $client->search($params);
    }

    public function count($params) {
        $host = array(config('smartforms.elasticSearchHost'));
        //$host = array('http://192.168.1.2:9200');
        $client = ClientBuilder::create()->setHosts($host)->build();
        return $client->count($params);
    }

    public function deleteByQuery($params) {
        $host = array(config('smartforms.elasticSearchHost'));
        //$host = array('http://192.168.1.2:9200');
        $client = ClientBuilder::create()->setHosts($host)->build();
        return $client->deleteByQuery($params);
    }

    public function bulk($params) {
        $host = array(config('smartforms.elasticSearchHost'));
        //$host = array('http://192.168.1.2:9200');
        $client = ClientBuilder::create()->setHosts($host)->build();
        return $client->bulk($params);
    }

}
