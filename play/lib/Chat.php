<?php

// Functions for adding to chat and cleanup of old chat messages.

class Chat {
    
    /**
     * Chat instance
     * 
     * @var Chat
     */
    protected static $_instance;
    
    /**
     * Chat buffer size.  Only this many lines are sent to the players.
     * 
     * @const int
     */
    const CHAT_LIMIT = 20;
    
    /**
     * Database adapter
     * 
     * @var DbAdapter
     */
    public $dbAdapter;
    
    /**
     * Game state
     * 
     * @var GateState
     */
    public $gameState;
    
    /**
     * Get the current Chat instance
     * 
     * @return Chat
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new Chat();
        }
        return self::$_instance;
    }
    
    public function __construct()
    {
        $this->dbAdapter = DbAdapter::getInstance();
        $this->gameState = GameState::getInstance();
    }

    public function addChat($room_id, $msg, $username = NULL) {
        // If the username is sent, attribute this message to that user (for muting purposes)
        if ($username != NULL) {
            $escapedName = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
            $msg = "<user>$escapedName</user>" . $msg;
        }

        $sql = sprintf("INSERT INTO chat (room_id, msg) VALUES(%s, '%s')",
                $this->dbAdapter->realEscapeString($room_id),
                $this->dbAdapter->realEscapeString($msg));

        $result = $this->dbAdapter->query($sql);

        $this->writeChatFile($room_id);

        return $result;
    }

    /**
     * Return the list of chat messages
     * 
     * @param int $room_id 
     * @return string[]
     */
    public function getChatMessages($room_id) {
        $chatList = array();
        $sql = sprintf("SELECT msg FROM " .
                "(SELECT msg, timestamp FROM chat " .
                "WHERE (room_id='%s') " .
                "ORDER BY timestamp DESC LIMIT 0," . self::CHAT_LIMIT . ") AS limitchat " .
                "ORDER BY timestamp ASC", $this->dbAdapter->realEscapeString($room_id));
        $result = $this->dbAdapter->query($sql);
        while ($row = $this->dbAdapter->fetchAssoc($result)) {
            $chatList[] = $row['msg'];
        }
        
        return $chatList;
    }

    protected function _writeChatXml($fh, $room_id) {
        /*
          // Open the chat file and then lock it.
          $fh = fopen("./tmp/chat".$room_id.".xml", "w");
          flock($fh, LOCK_EX);
         */

        // Get the chat data
        $sql = sprintf("SELECT msg FROM " .
                "(SELECT msg, timestamp FROM chat " .
                "WHERE (room_id='%s') " .
                "ORDER BY timestamp DESC LIMIT 0," . self::CHAT_LIMIT . ") AS limitchat " .
                "ORDER BY timestamp ASC", $this->dbAdapter->realEscapeString($room_id));
        $result = $this->dbAdapter->query($sql);

        // Write chat data
        /* fwrite($fh, '<?xml version="1.0" encoding="UTF-8" ?>'."\n"); */
        fwrite($fh, "<chat>\n");
        while ($row = $this->dbAdapter->fetchAssoc($result)) {
            fwrite($fh, "<msg>" . htmlspecialchars($row['msg']) . "</msg>\n");
        }
        fwrite($fh, "</chat>\n");
        /*
          // Unlock the file and close it.
          flock($fh, LOCK_UN);
          fclose($fh);
         */
    }

    /**
     * Parses the message string and adds it to chat
     * 
     * @param int $room_id
     * @param string $name Player name
     * @param string $msg Chat field contents
     */
    public function parseChatCommands($room_id, $name, $msg) {
        $escapedName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $escapedMsg = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
        $result = "<b>$escapedName</b>: $escapedMsg";
        if (strcasecmp("/em", substr($escapedMsg, 0, 3)) == 0 ||
                strcasecmp("/me", substr($escapedMsg, 0, 3)) == 0) {
            $emote = trim(substr($escapedMsg, 3));
            $result = "<i>$escapedName $emote</i>";
        }
        $this->addChat($room_id, $result, $name);
    }

    public function filterLanguage($str) {
        $WORDS = array("fucking", "fucker", "nigger", "fuck", "shit", "cunt", "faggot");
        $REPLACE = array("waffles", "omelette", "hashbrown", "bacon", "syrup", "honeybun", "fruitcup");

        return str_ireplace($WORDS, $REPLACE, $str);
    }

    public function writeChatFile($rid) {
        $fh = fopen("tmp/chat$rid.xml", "w");
        flock($fh, LOCK_EX);

        fwrite($fh, '<?xml version="1.0" encoding="UTF-8" ?>' . "\n");
        fwrite($fh, "<wtf>");

        $this->_writeChatXml($fh, $rid);
        $this->gameState->writePlayerListXml($fh, $rid);

        fwrite($fh, "</wtf>");

        flock($fh, LOCK_UN);
        fclose($fh);
    }

}
