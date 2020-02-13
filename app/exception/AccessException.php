<?php

namespace app\exception;

class AccessException extends \Exception
{
    public function __construct($message, Exception $previous = null) {
        parent::__construct($message, ErrorCode::$accessError, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
