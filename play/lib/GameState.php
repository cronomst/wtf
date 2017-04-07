<?php

/**
 * Handles database queries and XML output for managing game state 
 */
class GameState
{

    /**
     * GameState instance
     * 
     * @var GameState
     */
    protected static $_instance;

    /**
     * Database adapter
     * 
     * @var DbAdapter
     */
    public $dbAdapter;

    /**
     * Get the current game state instance
     * 
     * @return GameState
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new GameState();
        }

        return self::$_instance;
    }

    public function __construct()
    {
        $this->dbAdapter = DbAdapter::getInstance();
    }

    // === Player state methods ===

    public function getPlayer($pid)
    {
        $sql = sprintf("SELECT * FROM players WHERE (player_id='%s')", $this->dbAdapter->realEscapeString($pid));
        $result = $this->dbAdapter->query($sql);
        return $this->dbAdapter->fetchAssoc($result);
    }

    public function getPlayersInRoom($rid)
    {
        $sql = sprintf("SELECT * FROM players WHERE (room_id='%s') ORDER BY score DESC", $this->dbAdapter->realEscapeString($rid));
        $result = $this->dbAdapter->query($sql);
        return $result;
    }

    public function getTotalPlayers()
    {
        $sql = "SELECT COUNT(player_id) AS pcount FROM players";
        $result = $this->dbAdapter->query($sql);
        $row = $this->dbAdapter->fetchAssoc($result);
        return $row['pcount'];
    }

    public function countPlayersInRoom($rid)
    {
        $sql = sprintf("SELECT COUNT(player_id) AS pcount FROM players WHERE (room_id='%s')", $this->dbAdapter->realEscapeString($rid));
        $result = $this->dbAdapter->query($sql);
        $row = $this->dbAdapter->fetchAssoc($result);
        return $row['pcount'];
    }

    public function isPlayerInRoom($pid, $rid)
    {
        $sql = sprintf("SELECT player_id FROM players WHERE (player_id='%s' AND room_id='%s')", $this->dbAdapter->realEscapeString($pid), $this->dbAdapter->realEscapeString($rid));
        $result = $this->dbAdapter->query($sql);
        if ($this->dbAdapter->numRows($result) == 0)
            return false;
        else
            return true;
    }

    public function createPlayer($pname, $room_id)
    {
        $sql = sprintf("INSERT INTO players (name, room_id) VALUES('%s', %s)",
                $this->dbAdapter->realEscapeString($pname),
                $this->dbAdapter->realEscapeString($room_id));
        $result = $this->dbAdapter->query($sql);
        if (!$result) {
            echo 'Error adding new player ' . $this->dbAdapter->realEscapeString($pname);
            exit;
        }
        $_SESSION['player_id'] = $this->dbAdapter->insertId(); // Register player ID in session.
        return $_SESSION['player_id'];
    }

    public function removePlayer($pid)
    {
        $sql = sprintf("DELETE FROM players WHERE (player_id='%s')", $this->dbAdapter->realEscapeString($pid));
        $result = $this->dbAdapter->query($sql);
        unset($_SESSION['player_id']);

        return $result;
    }

    public function keepPlayerAlive($pid)
    {
        $sql = sprintf("UPDATE players SET timestamp=NOW() WHERE (player_id='%s')", $this->dbAdapter->realEscapeString($pid));
        return $this->dbAdapter->query($sql);
    }

    public function updatePlayer($player)
    {
        $sql = sprintf("UPDATE players SET score='%s', caption='%s', vote_id='%s' WHERE (player_id='%s')",
                $this->dbAdapter->realEscapeString($player['score']),
                $this->dbAdapter->realEscapeString($player['caption']),
                $this->dbAdapter->realEscapeString($player['vote_id']),
                $this->dbAdapter->realEscapeString($player['player_id']));
        return $this->dbAdapter->query($sql);
    }

    public function getVoteTotals($rid)
    {
        $rid = $this->dbAdapter->realEscapeString($rid);
        $sql = "SELECT playerlist.player_id, playerlist.name, playerlist.vote_id, playerlist.caption, COUNT(votelist.vote_id) AS votes"
                . " FROM players AS playerlist"
                . " LEFT JOIN players as votelist"
                . " ON playerlist.player_id = votelist.vote_id"
                . " WHERE (playerlist.room_id='$rid' AND playerlist.caption != \"\" AND playerlist.caption IS NOT NULL)"
                . " GROUP BY playerlist.player_id"
                . " ORDER BY votes DESC;";
        return $this->dbAdapter->query($sql);
    }

    public function updatePlayerScores($rid, $round)
    {
        $result = $this->getVoteTotals($rid);
        // Add vote total times multiplier to player scores
        $mult = 1;
        if ($round > 3)
            $mult = 2;
        if ($round == 7)
            $mult = 3;

        while ($row = $this->dbAdapter->fetchAssoc($result)) {
            // Only add to their score if they voted for somebody.
            if ($row['vote_id'] > 0) {
                $sql = sprintf("UPDATE players SET score=score+%s WHERE (player_id='%s')", $this->dbAdapter->realEscapeString($row['votes'] * $mult), $this->dbAdapter->realEscapeString($row['player_id']));
                $q = $this->dbAdapter->query($sql);
            }
        }
    }

    // === Room state methods ===

    /**
     * Returns a result set of all rooms
     * 
     * @return resource
     */
    public function getRooms()
    {
        $sql = "SELECT room_id, name, clean, password, caption_time FROM rooms";
        $result = $this->dbAdapter->query($sql);
        return $result;
    }

    /**
     * Returns an assoc array of the fields of the given room
     * @param int $rid Room ID
     * @return string[]
     */
    public function getRoom($rid)
    {
        $sql = sprintf("SELECT * FROM rooms WHERE (room_id='%s')", $this->dbAdapter->realEscapeString($rid));
        $result = $this->dbAdapter->query($sql);
        return $this->dbAdapter->fetchAssoc($result);
    }

    /**
     * Update the given room (rid) with the given assoc array of ALL field values (except timestamp and room_id)
     * 
     * @param int $rid
     * @param string[] $roomdata
     * @return resource
     */
    public function updateRoom($rid, $roomdata)
    {
        // Fields: round, state, image, next_image, rule, rule_data, timedata
        $sql = sprintf("UPDATE rooms SET round='%s', state='%s', image='%s', next_image='%s', rule='%s', rule_data='%s', timedata='%s' WHERE (room_id='%s')", $this->dbAdapter->realEscapeString($roomdata['round']), $this->dbAdapter->realEscapeString($roomdata['state']), $this->dbAdapter->realEscapeString($roomdata['image']), $this->dbAdapter->realEscapeString($roomdata['next_image']), $this->dbAdapter->realEscapeString($roomdata['rule']), $this->dbAdapter->realEscapeString($roomdata['rule_data']), $this->dbAdapter->realEscapeString($roomdata['timedata']), $this->dbAdapter->realEscapeString($rid));

        $result = $this->dbAdapter->query($sql);
        return $result;
    }

    /**
     * Set's the given player's room_id to the given room_id
     * 
     * @param int $pid Player ID
     * @param int $rid Room ID
     * @return resource
     */
    public function joinRoom($pid, $rid)
    {
        $sql = sprintf("UPDATE players SET room_id='%s', score='0', caption='', vote_id='0' WHERE (player_id='%s')", $this->dbAdapter->realEscapeString($rid), $this->dbAdapter->realEscapeString($pid));
        $result = $this->dbAdapter->query($sql);
        return $result;
    }

    /* Creates a new room and returns the room id */

    public function createRoom($rname, $is_clean, $password, $cap_time)
    {
        $timems = $this->getCurTimeData();
        if ($password !== null) {
            $sql = sprintf("INSERT INTO rooms (name, timedata, clean, password, caption_time) VALUES('%s', '$timems', '$is_clean', '%s', '$cap_time')", $this->dbAdapter->realEscapeString($rname), $this->dbAdapter->realEscapeString($password));
        } else {
            $sql = sprintf("INSERT INTO rooms (name, timedata, clean, caption_time) VALUES('%s', '$timems', '$is_clean', '$cap_time')", $this->dbAdapter->realEscapeString($rname));
        }
        $result = $this->dbAdapter->query($sql);
        $room_id = $this->dbAdapter->insertId();
        return $room_id;
    }

    /**
     * Remove rooms that have not had their timestamps updated for longer than 1 minute
     * 
     * @return resource
     */
    public function removeInactiveRooms()
    {
        // Get a list of all the rooms to be removed.
        $sql = "SELECT rooms.room_id FROM rooms LEFT JOIN players"
                . " ON players.room_id = rooms.room_id"
                . " WHERE players.player_id IS NULL AND DATE_SUB( NOW(), INTERVAL 1 MINUTE ) >= rooms.timestamp";
        $result = $this->dbAdapter->query($sql);

        // Remove rooms with no players where the timestamp is older than 1 minute.
        $del_sql = "DELETE rooms FROM rooms LEFT JOIN players"
                . " ON players.room_id = rooms.room_id"
                . " WHERE players.player_id IS NULL AND DATE_SUB( NOW(), INTERVAL 1 MINUTE ) >= rooms.timestamp";
        $del_result = $this->dbAdapter->query($del_sql);

        // Remove the chat files
        while ($row = $this->dbAdapter->fetchAssoc($result)) {
            $fn = "./tmp/chat" . $row['room_id'] . ".xml";
            @unlink($fn);

            // Remove chat rows since cascade deletion of foreign keys is currently broken in this version of MySQL
            $del_chat_sql = sprintf("DELETE FROM chat WHERE (room_id='%d')", $row['room_id']);
            $del_chat_result = $this->dbAdapter->query($del_chat_sql);
        }

        return $del_result;
    }

    /**
     * Returns true if the given room exists in the table, otherwise returns false
     * 
     * @param int $rid Room ID
     * @return boolean 
     */
    public function roomExists($rid)
    {
        $sql = sprintf("SELECT room_id FROM rooms WHERE (room_id='%s')", $this->dbAdapter->realEscapeString($rid));
        $result = $this->dbAdapter->query($sql);
        if ($this->dbAdapter->numRows($result) > 0) {
            return true;
        }
        return false;
    }

    // === XML output methods ===

    public function getResultList($rid)
    {
        $resultList = array();
        
        $result = $this->getVoteTotals($rid);
        while ($row = $this->dbAdapter->fetchAssoc($result)) {
            $disqualified = ($row['vote_id'] == 0) ? true : false;
            $resultList[] = new Result(
                    $row['name'],
                    $row['votes'],
                    $row['caption'],
                    $disqualified);
        }
        
        return $resultList;
    }

    public function getWinnerList($rid)
    {
        $winners = array();
        $result = $this->getPlayersInRoom($rid);
        $minPlayers = Configuration::getInstance()->get('game.min_players');
        
        if ($result && $this->dbAdapter->numRows($result) >= $minPlayers) {
            $winner = $this->dbAdapter->fetchAssoc($result);
            // Only consider them a winner if the score isn't 0.
            if ($winner['score'] > 0) {
                $winners[] = $winner['name'];
                while ($row = $this->dbAdapter->fetchAssoc($result)) {
                    if ($row['score'] == $winner['score'])
                        $winners[] = $row['name'];
                    else
                        break; // We can stop the loop because these are sorted by descending score
                }
            }
        }
        return $winners;
    }

    /**
     * Update chat xml fil with player list
     * 
     * @param resource $fh File handle
     * @param int $rid room ID
     */
    public function writePlayerListXml($fh, $rid)
    {
        fwrite($fh, "<playerlist>\n");
        $result = $this->getPlayersInRoom($rid);
        if ($result) {
            while ($row = $this->dbAdapter->fetchAssoc($result)) {
                fwrite($fh, "<player>\n");
                fwrite($fh, "<name>" . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . "</name>\n");
                fwrite($fh, "<score>" . $row['score'] . "</score>\n");
                fwrite($fh, "</player>\n");
            }
        }
        fwrite($fh, "</playerlist>\n");
    }
    
    /**
     * Returns the list of captions visible to the given player in the given room
     * 
     * @param int $rid
     * @param int $pid
     * @return Caption[]
     */
    public function getCaptionList($rid, $pid)
    {
        // Get the list of players and then shuffle it.
        $sql = sprintf("SELECT player_id, caption FROM players WHERE (room_id='%s' AND player_id != '%s' AND caption != '' AND caption IS NOT NULL)", $this->dbAdapter->realEscapeString($rid), $this->dbAdapter->realEscapeString($pid));
        $result = $this->dbAdapter->query($sql);
        $captions = array();
        while ($row = $this->dbAdapter->fetchAssoc($result)) {
            $captions[] = new Caption($row['player_id'], $row['caption']);
        }
        shuffle($captions);
        return $captions;
    }

    /* Returns the number of players that have valid captions set in the given room */

    public function getCaptionCount($rid)
    {
        $sql = sprintf("SELECT COUNT(player_id) FROM players WHERE (room_id='%s' AND caption != '' AND caption IS NOT NULL)", $this->dbAdapter->realEscapeString($rid));
        $result = $this->dbAdapter->query($sql);
        $row = $this->dbAdapter->fetchAssoc($result);
        $count = $row['COUNT(player_id)'];
        return $count;
    }

    public function removeInactivePlayers()
    {
        $sql = "DELETE FROM players WHERE DATE_SUB( NOW(), INTERVAL 2 MINUTE ) >= timestamp";
        $this->dbAdapter->query($sql);
    }

    public function resetPlayerRoundData($rid)
    {
        $sql = sprintf("UPDATE players SET caption='', vote_id='0' WHERE (room_id='%s' AND (caption != '' OR vote_id != '0'))", $this->dbAdapter->realEscapeString($rid));
        return $this->dbAdapter->query($sql);
    }

    public function resetPlayerGameData($rid)
    {
        $sql = sprintf("UPDATE players SET score='0' WHERE (room_id='%s' AND score != '0')", $this->dbAdapter->realEscapeString($rid));
        return $this->dbAdapter->query($sql);
    }

    public function getCurTimeData()
    {
        return round(microtime(true) * 1000);
    }

    /**
     * Performs any need state transition for the given room ID
     * 
     * @param int $rid 
     */
    public function updateState($rid)
    {
        $wtfChat = Chat::getInstance();
        $roomLock = RoomLock::getInstance();
        $wtfConfig = Configuration::getInstance();
        
        $minPlayers = $wtfConfig->get('game.min_players');
        $introTime = $wtfConfig->get('game.time.intro');
        $finalCaptionExtraTime = $wtfConfig->get('game.time.final_caption_extra');
        $waitTime = $wtfConfig->get('game.time.wait');
        $voteTime = $wtfConfig->get('game.time.vote');
        $resultsTime = $wtfConfig->get('game.time.results');
        $gameOverTime = $wtfConfig->get('game.time.gameover');
        $totalRounds = $wtfConfig->get('game.total_rounds');

        $room = $this->getRoom($rid);
        if ($room) {
            if ($room['state'] == "pregame") {
                $playerCount = $this->countPlayersInRoom($rid);
                if ($playerCount >= $minPlayers && $roomLock->lock($rid)) {
                    $room['round'] = 1;
                    $room['state'] = "intro";
                    $room['image'] = RandomImage::getRandomImage();
                    $room['next_image'] = $room['image']; // No need to load two different pics yet.
                    // Set rule
                    // Set rule_data
                    $room['timedata'] = $this->getCurTimeData();
                    $result = $this->updateRoom($rid, $room);
                    $roomLock->unlock($rid);
                }
            } elseif ($room['state'] == "intro") {
                if ($this->getCurTimeData() >= $room['timedata'] + $introTime && $roomLock->lock($rid)) {
                    $room['state'] = "caption";
                    $room['image'] = $room['next_image'];
                    $room['next_image'] = RandomImage::getRandomImage($room['image']);
                    $room['timedata'] = $this->getCurTimeData();
                    $this->updateRoom($rid, $room);
                    $roomLock->unlock($rid);
                }
            } elseif ($room['state'] == "caption") {
                // Check for final round.  If so, use final round caption time instead.
                if ($room['round'] < $totalRounds)
                    $cap_time = $room['caption_time'] * 1000;
                else
                    $cap_time = $room['caption_time'] * 1000 + $finalCaptionExtraTime;
                if ($this->getCurTimeData() >= $room['timedata'] + $cap_time && $roomLock->lock($rid)) {
                    // Send to the waiting state to give last second updates a chance to hit the server.
                    $room['state'] = "caption_wait";
                    $room['timedata'] = $this->getCurTimeData();
                    $this->updateRoom($rid, $room);
                    $roomLock->unlock($rid);
                }
            } else if ($room['state'] == "caption_wait") {
                if ($this->getCurTimeData() >= $room['timedata'] + $waitTime && $roomLock->lock($rid)) {
                    // Check how many captions were submitted.  If less than 2, then don't bother voting.
                    if ($this->getCaptionCount($rid) > 1) {
                        $room['state'] = "vote";
                    } else {
                        $room['state'] = "results";
                    }
                    $room['timedata'] = $this->getCurTimeData();
                    $this->updateRoom($rid, $room);
                    $roomLock->unlock($rid);
                }
            } elseif ($room['state'] == "vote") {
                if ($this->getCurTimeData() >= $room['timedata'] + $voteTime && $roomLock->lock($rid)) {
                    $room['state'] = "vote_wait";
                    $room['timedata'] = $this->getCurTimeData();
                    $this->updateRoom($rid, $room);
                    $roomLock->unlock($rid);
                }
            } elseif ($room['state'] == "vote_wait") {
                if ($this->getCurTimeData() >= $room['timedata'] + $waitTime && $roomLock->lock($rid)) {
                    $room['state'] = "results";
                    $room['timedata'] = $this->getCurTimeData();
                    $this->updatePlayerScores($rid, $room['round']);
                    $this->updateRoom($rid, $room);
                    $wtfChat->writeChatFile($rid); // Update scores in the chat file for this room.
                    $roomLock->unlock($rid);
                }
            } elseif ($room['state'] == "results") {
                if ($this->getCurTimeData() >= $room['timedata'] + $resultsTime && $roomLock->lock($rid)) {
                    $room['round']++;
                    if ($room['round'] > $totalRounds)
                        $room['state'] = "gameover";
                    else
                        $room['state'] = "intro";

                    // Set rule if this is the final round
                    if ($room['round'] == $totalRounds) {
                        $room = Rules::getInstance()->setRandomRule($room);
                    } else {
                        $room['rule'] = Rules::NO_RULE;
                    }

                    $room['timedata'] = $this->getCurTimeData();
                    $this->updateRoom($rid, $room);
                    $this->resetPlayerRoundData($rid); // Reset captions and votes for all players in room
                    $roomLock->unlock($rid);
                }
            } elseif ($room['state'] == "gameover") {
                if ($this->getCurTimeData() >= $room['timedata'] + $gameOverTime && $roomLock->lock($rid)) {
                    $room['state'] = "pregame";
                    $this->updateRoom($rid, $room);
                    $this->resetPlayerGameData($rid); // Reset scores for all players in the room
                    $wtfChat->writeChatFile($rid); // Update scores in the chat file for this room.
                    $roomLock->unlock($rid);
                }
            }
        }
    }

}