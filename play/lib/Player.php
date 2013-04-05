<?php

class Player
{
    /**
     * Player ID
     * @var int
     */
    public $player_id;
    
    /**
     * Player name
     * @var string
     */
    public $name;
    
    /**
     * Player score
     * @var int
     */
    public $score;
    
    /**
     * Player caption
     * @var string
     */
    public $caption;
    
    /**
     * ID of the room the player is in
     * @var int
     */
    public $room_id;
    
    /**
     * ID of the player that this player has voted for
     * @var int
     */
    public $vote_id;
    
    /**
     * Timestamp of the last time this player has performed an action
     * @var string
     */
    public $timestamp;
    
}