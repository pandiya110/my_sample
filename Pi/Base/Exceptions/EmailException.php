<?php
namespace CodePi\Base\Exceptions;

use Illuminate\Support\MessageBag;

class EmailException extends \Exception {
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
