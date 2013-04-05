<?php

/**
 * State Response model
 *
 * @author Kenneth Shook
 */
class StateResponse
{

    /**
     * @var string Type (name) of this state
     */
    public $type;

    /**
     * @var string image name
     */
    public $image;

    /**
     * @var string name of next image
     */
    public $nextImage;

    /**
     * @var int number of current round
     */
    public $round;

    /**
     * @var string rule description
     */
    public $rule;

    /**
     * @var int Number of milliseconds that client should wait before requesting the state again
     */
    public $checkback;

    /**
     * @var int Number of milliseconds remaining on the caption/voting timer
     */
    public $timer;

    /**
     * @var Caption[] List of player captions
     */
    public $captionList;

    /**
     * @var Result[] List of voting results
     */
    public $resultList;

    /**
     * @var string[] List of winners' names
     */
    public $winners;

    public function __construct()
    {
        
    }

    /**
     * Add a caption to the caption list
     * 
     * @param Caption $caption 
     */
    public function addCaption($caption)
    {
        if (!isset($this->captionList)) {
            $this->captionList = array();
        }
        $this->captionList[] = $caption;
    }

    /**
     * Add a result to the result list
     * 
     * @param Result $result 
     */
    public function addResult($result)
    {
        if (!isset($this->resultList)) {
            $this->resultList = array();
        }
        $this->resultList[] = $result;
    }

    public function addWinner($winnerName)
    {
        if (!isset($this->winners)) {
            $this->winners = array();
        }
        $this->winners[] = $winnerName;
    }

    /**
     * Generates an XML representation of this object 
     * 
     * @return string
     */
    public function toXML()
    {
        $xml = '<state>'
                . "<type>{$this->type}</type>"
                . "<image>{$this->image}</image>"
                . "<next_image>{$this->nextImage}</next_image>"
                . "<round>{$this->round}</round>";

        if (isset($this->rule)) {
            $xml .= "<rule>{$this->rule}</rule>";
        }
        if (isset($this->checkback)) {
            $xml .= "<checkback>{$this->checkback}</checkback>";
        }
        if (isset($this->timer)) {
            $xml .= "<timer>{$this->timer}</timer>";
        }
        if (isset($this->captionList)) {
            $xml .= '<captionlist>';
            foreach ($this->captionList as $caption) {
                $xml .= $caption->toXML();
            }
            $xml .= '</captionlist>';
        }
        if (isset($this->resultList)) {
            $xml .= '<resultlist>';
            foreach ($this->resultList as $result) {
                $xml .= $result->toXML();
            }
            $xml .= '</resultlist>';
        }
        if (isset($this->winners)) {
            $xml .= '<winners>';
            foreach ($this->winners as $winner)
                $xml .= '<winner>'
                        . htmlspecialchars($winner, ENT_QUOTES, 'UTF-8')
                        . '</winner>';
            $xml .= '</winners>';
        }

        $xml .= '</state>';

        return $xml;
    }

    /**
     * Factory method to create a StateResponse for the given room/player
     * 
     * @param int $roomId
     * @param int  $playerId
     * @return null|StateResponse 
     */
    public static function createStateResponse($roomId, $playerId)
    {
        $wtfGameState = GameState::getInstance();
        $wtfConfig = Configuration::getInstance();
        $room = $wtfGameState->getRoom($roomId);

        if ($room === false)
            return null;

        $caption_time = $room['caption_time'] * 1000;
        $final_caption_time = $caption_time + $wtfConfig->get('game.time.final_caption_extra');

        $response = new StateResponse();
        $response->type = $room['state'];
        $response->image = $room['image'];
        $response->nextImage = $room['next_image'];
        $response->round = $room['round'];

        if ($room['rule'] != Rules::NO_RULE) {
            $response->rule = Rules::getInstance()->getRuleString($room);
        }

        // State-specific elements
        if ($room['state'] == "pregame") {
            $response->checkback = $wtfConfig->get('game.time.pregame');
        } elseif ($room['state'] == "intro") {
            $response->checkback = self::_getCheckback($room['timedata'], $wtfConfig->get('game.time.intro'));
        } elseif ($room['state'] == "caption") {
            if ($room['round'] < $wtfConfig->get('game.total_rounds'))
                $tm = $caption_time;
            else
                $tm = $final_caption_time;
            $response->timer = self::_getTimer($room['timedata'], $tm);
            $response->checkback = self::_getCheckback($room['timedata'], $tm);
        } elseif ($room['state'] == "caption_wait" || $room['state'] == "vote_wait") {
            $response->checkback = self::_getCheckback($room['timedata'], $wtfConfig->get('game.time.wait'));
        } elseif ($room['state'] == "vote") {
            $captionList = $wtfGameState->getCaptionList($roomId, $playerId);
            $response->captionList = $captionList;
            $response->timer = self::_getTimer($room['timedata'], $wtfConfig->get('game.time.vote'));
            $response->checkback = self::_getCheckback($room['timedata'], $wtfConfig->get('game.time.vote'));
        } elseif ($room['state'] == "results") {
            $response->resultList = $wtfGameState->getResultList($roomId);
            $response->checkback = self::_getCheckback($room['timedata'], $wtfConfig->get('game.time.results'));
        } elseif ($room['state'] == "gameover") {
            $response->winners = $wtfGameState->getWinnerList($roomId);
            $response->checkback = self::_getCheckback($room['timedata'], $wtfConfig->get('game.time.gameover'));
        }

        return $response;
    }

    protected static function _getCheckback($timedata, $delay)
    {
        $wtfGameState = GameState::getInstance();
        $wtfConfig = Configuration::getInstance();
        $diff = ($timedata + $delay) - $wtfGameState->getCurTimeData();
        $refreshInterval = $wtfConfig->get('game.time.refresh');
        
        if ($diff < 0)
            $diff = 500; // Check back in 500ms if the difference ends up being less than 0. (probably not necessary, but just in case...)

        if ($diff > $refreshInterval) {
            return $refreshInterval;
        } else {
            return $diff;
        }
    }

    protected static function _getTimer($timedata, $delay)
    {
        $wtfGameState = GameState::getInstance();
        $diff = ($timedata + $delay) - $wtfGameState->getCurTimeData();
        return $diff;
    }

}
