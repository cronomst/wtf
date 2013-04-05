<?php

/**
 * Caption data
 *
 * @author Kenneth Shook
 */
class Caption
{
    /**
     * @var int player ID
     */
    public $playerId;
    
    /**
     * @var string caption
     */
    public $caption;

    /**
     * Constructs a new Caption object
     * 
     * @param int $playerId
     * @param string $caption 
     */
    public function __construct($playerId, $caption)
    {
        $this->playerId = $playerId;
        $this->caption = $caption;        
    }
    
    /**
     * Get as XML
     * 
     * @return string xml
     */
    public function toXML()
    {
        return "<caption id=\"{$this->playerId}\">"
                . htmlspecialchars($this->caption, ENT_QUOTES, 'UTF-8')
                . "</caption>";
    }
}
