<?php

namespace CodePi\Base\Exceptions;

use Exception;

class CommandException extends Exception {
	public function __construct($message) {
		parent::__construct ( $message );
	}
}