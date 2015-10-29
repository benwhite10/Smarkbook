<?php

class Message
{
    public $type;
    public $message;
    
    function __construct($type, $message) {
        $this->setType($type);
        $this->setMessage($message);
        return $this;
    }
    
    function getType() {
        return $this->type;
    }

    function getMessage() {
        return $this->message;
    }

    function setType($type) {
        $this->type = $type;
    }

    function setMessage($message) {
        $this->message = $message;
    }
}

