<?php

namespace CodePi\Base\Validations;

use PhpParser\Node\Stmt\Foreach_;
use CodePi\Base\Exceptions;
use Illuminate\Validation\Factory as Validator;
use CodePi\Base\Exceptions\DataValidationException;

abstract class DataValidator {

    /**
     *
     * @var Validator
     */
    protected $validator;

    /**
     *
     * @var [type]
     */
    protected $validation;

    /**
     *
     * @param Validator $validator        	
     */
    public function __construct(Validator $validator) {
        $this->validator = $validator;
    }

    /**
     *
     * @param array $data        	
     * @throws DataValidationException 
     */
    public function validate(array $data) {



        $this->validator->extend('isDynamicRule', function ($attribute, $value) use ($data) {

            return $this->doValidation($data);
        });

        $this->validator->replacer('isDynamicRule', function($message, $attribute, $rule, $parameters) {
            $messages = $this->getValidationMessages();
            if (isset($messages[$attribute . '.' . $rule]))
                return $messages[$attribute . '.' . $rule];
        });

        $rules = $this->getValidationRules();
        $intersectionRules = array_intersect_key($rules, $data);
      
        $this->validation = $this->validator->make($data, $intersectionRules, $this->getValidationMessages());
        if ($this->validation->fails()) {
            throw new DataValidationException($this->getValidateMessage(), $this->getValidationErrors());
        }
        return true;
    }

    /**
     * @return array
     */
    protected function getValidationMessages() {
        return property_exists($this, 'messages') ? $this->messages : [];
    }

    /**
     *
     * @return array
     */
    protected function getValidationRules() {
        return $this->rules;
    }

    /**
     *
     * @return array
     */
    protected function getValidationErrors() {
        return $this->validation->errors();
    }

    /**
     *
     * @return string
     */
    protected function getValidateMessage() {
        return $this->validation->messages()->first();
    }

}
