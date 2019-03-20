<?php

namespace CodePi\Base\Libraries;

use Illuminate\Support\Str;
use CodePi\Base\Exceptions\DataValidationException;
use Illuminate\Support\MessageBag;
class PiValidations {

    public $rules = [];
    public $messages = [];
    public $data = [];

    function __construct(array $data, array $rules = [], array $messages = []) {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    function validation() {

        $this->rules = $this->explodeRules($this->rules);

        foreach ($this->rules as $attribute => $rules) {
            foreach ($rules as $rule) {
                $this->validate($attribute, $this->parseStringRule($rule));
            }
        }
        return true;
    }

    protected function explodeRules(array $rules) {
        foreach ($rules as $key => $rule) {

            if (is_string($rule)) {
                $rules[$key] = explode('|', $rule);
            } elseif (is_object($rule)) {
                $rules[$key] = [$rule];
            } else {
                $rules[$key] = $rule;
            }
        }

        return $rules;
    }

    protected function parseStringRule($rules) {
        $parameters = [];

        // The format for specifying validation rules and parameters follows an
        // easy {rule}:{parameters} formatting convention. For instance the
        // rule "Max:3" states that the value may only be three letters.
        if (strpos($rules, ':') !== false) {
            list($rules, $parameter) = explode(':', $rules, 2);

            $parameters = $this->parseParameters($parameter);
        }

        return [Str::studly(trim($rules)), $parameters];
    }

    protected function parseParameters($parameter) {

        return str_getcsv($parameter);
    }

    protected function validate($attribute, array $rule) {
        $method = 'validate' . $rule[0];
        $paramters = $rule[1];
        $message = $this->getMessages($attribute, strtolower($rule[0]));
        return $this->$method($attribute, $paramters, $message);
    }

    protected function validateUnique($attribute, $paramters, $message = null) {
        $table = $paramters[0];
        unset($paramters[0]);
        $objDb = \DB::table($table)->whereRaw('UPPER(REPLACE(' . $attribute . ',\' \', \'\')) = ?', array(preg_replace('/\s+/', '', strtoupper($this->data[$attribute]))));

        foreach ($paramters as $column) {
            $columnsAndCond = explode('.', $column);
            if (!isset($columnsAndCond[1])) {
                $columnsAndCond[1] = 'neq';
            }
            if ($columnsAndCond[1] == 'in') {
                $objDb = $objDb->whereIn($columnsAndCond[0], array($this->data[$columnsAndCond[0]]));
            } elseif ($columnsAndCond[1] == 'notin') {
                $objDb = $objDb->whereNotIn($columnsAndCond[0], array($this->data[$columnsAndCond[0]]));
            } else {
                $objDb = $objDb->where($columnsAndCond[0], $this->getConditions($columnsAndCond[1]), $this->data[$columnsAndCond[0]]);
            }
        }

        $intCount = $objDb->count();
        if ($intCount == 0) {
            return true;
        }
        $message = !empty($message) ? $message : 'The ' . str_replace('_', ' ', $attribute) . ' already exist';
        throw new DataValidationException($message, new MessageBag());
    }

    protected function validateLessthan($attribute, $paramters, $message = null) {
        $maxValue = $paramters[0];
        if (isset($this->data[$attribute]) && $this->data[$attribute] <= $maxValue) {
            return true;
        }

        $message = !empty($message) ? $message : 'The ' . str_replace('_', ' ', $attribute) . ' must be less than ' . $maxValue;
        throw new DataValidationException($message, new MessageBag());
    }

    protected function validateGreaterthan($attribute, $paramters, $message = null) {
        $minValue = $paramters[0];
        if (isset($this->data[$attribute]) && $this->data[$attribute] >= $minValue) {
            return true;
        }
        $message = !empty($message) ? $message : 'The ' . str_replace('_', ' ', $attribute) . ' must be greater than ' . $minValue;
        throw new DataValidationException($message, new MessageBag());
    }

    function getConditions($sting = 'neq') {
        $condtions = ['neq' => '!=', 'eq' => '=', 'like' => 'like', 'ilike' => 'ilike'];

        return $condtions[$sting];
    }

    function getMessages($attribute, $rule) {
        return isset($this->messages[$attribute . '.' . $rule]) ? $this->messages[$attribute . '.' . $rule] : null;
    }

}
