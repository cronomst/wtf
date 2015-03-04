<?php

class DbAdapter
{
    /**
     * @var DbAdapter adapter instance
     */
    protected static $_instance;
    
    /**
     * @var mysqli MySQLi object
     */
    protected $_mysqli;
    
    /**
     * Gets the DbAdapter instance
     * 
     * @return DbAdapter
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new DbAdapter();
            self::$_instance->connect();
        }
            
        return self::$_instance;
    }
    
    /**
     * Sets the DbAdapter instance
     * 
     * @param DbAdapter $dbadapter 
     */
    public static function setInstance($dbadapter)
    {
        self::$_instance = $dbadapter;
    }
    
    /**
     * Connect to the database using the values specified
     * in the configuration. 
     */
    public function connect()
    {
        $wtfConfig = Configuration::getInstance();
        
        $mysqli = new mysqli(
                $wtfConfig->get('db.host'),
                $wtfConfig->get('db.username'),
                $wtfConfig->get('db.password'),
                $wtfConfig->get('db.name')
                );
        
        // Using non-OO version of mysqli_connect_error since the OO version is
        // supposedly broken with my PHP version.
        if ($mysqli->connect_error) {
			error_log($mysqli->connect_error);
            $this->_displayDBError();
            exit;
        }
        
        $mysqli->query('SET NAMES utf8');
        $mysqli->query('SET CHARACTER SET utf8');
        $mysqli->set_charset('utf8');
        
        $this->_mysqli = $mysqli;
    }
    
    /**
     * Execute mysql query
     * 
     * @param string $sql SQL query string
     * @return mysqli_result Query results
     */
    public function query($sql)
    {
        $result = $this->_mysqli->query($sql);
        return $result;
    }
    
    /**
     * Fetch associative array of next row's values
     * 
     * @param mysqli_result $result
     * @return string[]
     */
    public function fetchAssoc($result)
    {
        $assoc = $result->fetch_assoc();
        return $assoc;
    }
    
    /**
     * Get number of rows in the given result
     * 
     * @param mysqli_result $result
     * @return int
     */
    public function numRows($result)
    {
        return $result->num_rows;
    }
    
    /**
     * Get ID of last insert statement
     * 
     * @return int
     */
    public function insertId()
    {
        return $this->_mysqli->insert_id;
    }
    
    public function realEscapeString($escapestr)
    {
        return $this->_mysqli->real_escape_string($escapestr);
    }
    
    /**
     * Display an error page 
     */
    protected function _displayDBError() {
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
        <head>
            <title>Words That Follow</title>
            <link rel="stylesheet" href="index.css" type="text/css" />
            <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        </head>
        <body>
            <div id="container">
                <div id="header">
                    <div id="logo">
                        <img src="logo.gif" alt="Words That Follow" />
                    </div>
                </div>
                <div id="main">
                    We are experiencing temporary technical issues.  Please try again shortly.
                </div>
            </div>
        </body>
    </html>
    <?php
    }
}
