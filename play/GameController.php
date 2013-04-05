<?php

/**
 * Controller for the interaction between the players and the game
 *
 * @author Kenneth Shook
 */
class GameController
{

    /**
     * @var string[] Parameters used by the actions
     */
    protected $_fields;
    
    /**
     * @var WTFResponse
     */
    protected $_response;

    /**
     * Constructs a new GameController and populates the fields if any
     * are provided
     * 
     * @param string[] $fields 
     */
    public function __construct($fields = false, $response = false)
    {
        if ($fields) {
            $this->setFields($fields);
        }
        if ($response) {
            $this->setResponse($response);
        }
    }

    public function setFields($fields)
    {
        $this->_fields = $fields;
    }

    /**
     * Gets the field value for the given key
     * 
     * @param type $fieldKey
     * @return string|bool Field value or false if key does not exist
     */
    public function getField($fieldKey)
    {
        if (isset($this->_fields[$fieldKey])) {
            return $this->_fields[$fieldKey];
        }

        return false;
    }
    
    /**
     * Merge the current field array with the given array
     * 
     * @param string[] $newFields 
     */
    public function addFields($newFields)
    {
        if (!isset($this->_fields)) {
            $this->_fields = array();
        }
        
        $this->_fields = array_merge($this->_fields, $newFields);
    }
    
    /**
     * Set Response
     * 
     * @param WTFResponse $response 
     */
    public function setResponse($response)
    {
        $this->_response = $response;
    }
    
    /**
     * Get Response
     * 
     * @return WTFResponse
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Invoke the given action.  Does nothing if the action does not match
     * a recognized value.
     * 
     * @param string $action Action name
     */
    public function invokeAction($action)
    {
        switch ($action) {
            case 'sayChat':
                $this->chatAction();
                break;
            case 'getState':
                $this->stateAction();
                break;
            case 'setCaption':
                $this->captionAction();
                break;
            case 'setVote':
                $this->voteAction();
                break;
            default:
                break;
        }
    }

    /**
     * Action to add a chat message
     */
    public function chatAction()
    {
        $wtfGameState = GameState::getInstance();
        $wtfChat = Chat::getInstance();

        $playerId = $this->getField('player_id');
        $playerData = $wtfGameState->getPlayer($playerId);
        $playerName = $playerData['name'];
        $roomId = $playerData['room_id'];
        $room = $wtfGameState->getRoom($roomId); // Get room so we can determine if it's a "clean" room or not.

        $msg = Util::urldecodeUTF8($this->getField('msg'));

        if (get_magic_quotes_gpc()) {
            $playerName = stripslashes($playerName);
            $msg = stripslashes($msg);
        }
        if ($room['clean'])
            $msg = $wtfChat->filterLanguage($msg);

        $wtfChat->parseChatCommands($roomId, $playerName, $msg);

        $chatMsgs = $wtfChat->getChatMessages($roomId);
        $this->getResponse()->chat = $chatMsgs;
    }

    /**
     * State update action 
     */
    public function stateAction()
    {
        $wtfGameState = GameState::getInstance();
        $playerId = $this->getField('player_id');
        $player = $wtfGameState->getPlayer($playerId);
        $roomId = $player['room_id'];
        
        $room = $wtfGameState->getRoom($roomId);
        if ($room === false) {
            // Room doesn't exists for some reason
            $this->getResponse()->error = "Error: This game no longer exists.  Please choose another one.";
            return;
        }

        $wtfGameState->updateState($roomId);
        
        // While we're here, keep the player alive by updating his timestamp
        $wtfGameState->keepPlayerAlive($playerId);
        
        // Build the StateResponse object for the current state
        $stateResponse = StateResponse::createStateResponse($roomId, $playerId);
        $this->getResponse()->state = $stateResponse;
    }

    /**
     * Action to set a caption 
     */
    public function captionAction()
    {
        $wtfGameState = GameState::getInstance();
        $playerId = $this->getField('player_id');
        $player = $wtfGameState->getPlayer($playerId);
        $roomId = $player['room_id'];

        $caption = $this->getField('caption');

        // Make sure the game is still in the caption state before accepting the caption
        $room = $wtfGameState->getRoom($roomId);
        if ($room['state'] == "caption" || $room['state'] == "caption_wait") {
            $player['caption'] = Util::urldecodeUTF8($caption);

            if (get_magic_quotes_gpc())
                $player['caption'] = stripslashes($player['caption']);

            if ($room['clean'])
                $player['caption'] = $wtfChat->filterLanguage($player['caption']);

            $wtfGameState->updatePlayer($player);

            $this->getResponse()->response = 'caption_ok';
        }
    }

    /**
     * Action to set a player's vote 
     */
    public function voteAction()
    {
        $wtfGameState = GameState::getInstance();
        $playerId = $this->getField('player_id');
        $player = $wtfGameState->getPlayer($playerId);
        $roomId = $player['room_id'];
        
        $voteId = $this->getField('vote_id');
        
        // Make sure the game is in the vote state first.
        $room = $wtfGameState->getRoom($roomId);
        if ($room['state'] == "vote" || $room['state'] == "vote_wait") {
            $player['vote_id'] = $voteId;
            $wtfGameState->updatePlayer($player);
            
            $this->getResponse()->response = 'vote_ok';
        }
    }

}
