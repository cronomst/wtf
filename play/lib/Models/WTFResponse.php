<?php

/**
 * WTF Response
 *
 * @author Kenneth Shook
 */
class WTFResponse
{
    /**
     * @var StateResponse
     */
    public $state;
    /**
     * @var string
     */
    public $response;
    /**
     * @var string[] 
     */
    public $chat;
    /**
     * @var string
     */
    public $error;
    
    public function __construct()
    {
    }
    
    /**
     * Generate WTF response XML
     * 
     * @return string xml
     */
    public function toXML()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL
                .'<wtf>' . PHP_EOL;
        
        if (isset($this->state)) {
            $xml .= $this->state->toXML();
        }
        if (isset($this->response)) {
            $xml .= "<response>{$this->response}</response>" . PHP_EOL;
        }
        if (isset($this->chat)) {
            $xml .= '<chat>' . PHP_EOL;
            foreach ($this->chat as $msg) {
                $msg = htmlspecialchars($msg);
                $xml .= "<msg>$msg</msg>" . PHP_EOL;
            }
            $xml .= '</chat>' . PHP_EOL;
        }
        if (isset($this->error)) {
            $msg = htmlspecialchars($this->error);
            $xml .= "<error><msg>$msg</msg><url>./</url></error>" . PHP_EOL;
        }        
        
        $xml .= '</wtf>';
        
        return $xml;
    }
    
    public function toJson()
    {
        return json_encode($this);
    }
    
}
