<?php

/**
 * Result data
 *
 * @author Kenneth Shook
 */
class Result
{
    /**
     * @var string player name
     */
    public $playerName;
    /**
     * @var int number of votes the caption received
     */
    public $votes;
    /**
     * @var string caption
     */
    public $caption;
    /**
     * @var bool whether or not this caption is disqualified
     */
    public $disqualified;
    
    /**
     * Creates a new Result object
     * 
     * @param string $playerName
     * @param int $votes
     * @param string $caption
     * @param bool $disqualified 
     */
    public function __construct($playerName, $votes, $caption, $disqualified = false)
    {
        $this->playerName = $playerName;
        $this->votes = $votes;
        $this->caption = $caption;
        $this->disqualified = $disqualified;
    }
    
    /**
     *
     * @return string xml
     */
    public function toXML()
    {
        if ($this->disqualified) {
            $disqualified = 'disqualified="disqualified"';
        } else {
            $disqualified = '';
        }
        return sprintf('<result name="%s" votes="%s" %s>%s</result>',
                htmlspecialchars($this->playerName, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($this->votes, ENT_QUOTES, 'UTF-8'),
                $disqualified,
                htmlspecialchars($this->caption, ENT_QUOTES, 'UTF-8')
                );
    }
}
