<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace CodePi\Base\Exceptions;

use Illuminate\Support\MessageBag;

class ValidateFileException extends \Exception {
	/**
	 *
	 * @var MessageBag
	 */
	private $errors;
	
	/**
	 *
	 * @param
	 *        	$message
	 * @param MessageBag $errors        	
	 */
	public function __construct($message) {
		parent::__construct ( $message );
	}
	
	/**
	 *
	 * @return MessageBag
	 */
	public function getErrors() {
		return $this->errors;
	}
}