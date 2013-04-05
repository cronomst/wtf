<?php

class FeaturedCaps
{
    /**
     * Featured caption instance
     * 
     * @var FeaturedCaps
     */
    protected static $_instance;
    
    /**
     * Database adapter
     * 
     * @var DbAdapter
     */
    public $dbAdapter;
    
    /**
     * Game state
     * @var GameState
     */
    public $gameState;
    
    /**
     * Number of votes required to qualify as a featured caption
     * 
     * @var int
     */
    protected $_votesRequired;
    
    /**
     * Get featured caps instance
     * @return FeaturedCaps
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new FeaturedCaps();
        }
        return self::$_instance;
    }
    
    public function __construct()
    {
        $this->dbAdapter = DbAdapter::getInstance();
        $this->gameState = GameState::getInstance();
        $this->_votesRequired = Configuration::getInstance()->get('featured.votes_required');
    }

    public function updateFeaturedCaptions($rid, $img)
    {
        // Check if img is a flickr image.  If so, return without doing anything.
        if (preg_match('/^flickr:/', $img))
            return;
        // Not a flickr image, so continue
        $voteresults = $this->gameState->getVoteTotals($rid);
        while ($row = $this->dbAdapter->fetchAssoc($voteresults)) {
            if ($row['votes'] >= $this->_votesRequired)
                $this->_insertFeaturedCaption($row['name'], $row['caption'], $img);
        }
    }

    protected function _insertFeaturedCaption($name, $caption, $img)
    {
        $sql = sprintf('INSERT INTO featured (name, caption, image) VALUES ("%s", "%s", "%s")',
                $this->dbAdapter->realEscapeString($name),
                $this->dbAdapter->realEscapeString($caption),
                $this->dbAdapter->realEscapeString($img));

        return $this->dbAdapter->query($sql);
    }

    public function getFeaturedCaption()
    {
        $sql = "SELECT name, caption, image FROM featured ORDER BY RAND() LIMIT 1";
        $result = $this->dbAdapter->query($sql);
        return $this->dbAdapter->fetchAssoc($result);
    }

    public function getFeaturedCaptionInfo($id)
    {
        $sql = sprintf('SELECT name, caption, image FROM featured WHERE (id="%s")',
                $this->dbAdapter->realEscapeString($id));
        $result = $this->dbAdapter->query($sql);
        return $this->dbAdapter->fetchAssoc($result);
    }

    public function getFeaturedCaptionPage($page, $per_page = 4)
    {
        $offset = $page * $per_page;
        $sql = sprintf('SELECT id, name, caption, image FROM featured ORDER BY id DESC LIMIT %d, %d',
                $this->dbAdapter->realEscapeString($offset),
                $this->dbAdapter->realEscapeString($per_page));
        $result = $this->dbAdapter->query($sql);
        return $result;
    }

    public function getFeaturedCaptionPageCount($per_page = 4)
    {
        $sql = 'SELECT COUNT(id) AS capnum FROM featured';
        $result = $this->dbAdapter->query($sql);
        $row = $this->dbAdapter->fetchAssoc($result);
        return ceil($row['capnum'] / $per_page);
    }

}
