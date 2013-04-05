<?php

class RoomLock
{
    /**
     * @var RoomLock Room lock instance
     */
    protected static $_instance;
    
    /**
     * Get current room lock instance
     * 
     * @return RoomLock
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new RoomLock();
        }
        return self::$_instance;
    }
    
    public function lock($rid)
    {
        // Check if lock exists and is not expired)
        if ($this->_lockExpired($rid) == false)
            return false;

        // Lock doesn't exist or has expired, so create the new lock.
        $fh = @fopen($this->_getLockName($rid), "xb");

        // Couldn't create the file for some reason, so return false.
        if ($fh === false)
            return false;

        // File created, so write the current timestamp to it.
        $tm = microtime(true) * 1000;
        fwrite($fh, $tm);
        fclose($fh);

        return true;
    }

    public function unlock($rid)
    {
        // Remove the lock, if it exists.
        $rm = $this->_getLockName($rid);
        if (file_exists($rm))
            unlink($rm);
    }

    protected function _lockExpired($rid)
    {
        // Returns true if lock has expired (over 1.5 seconds old)
        $tm = microtime(true) * 1000;
        $timestamp = @file_get_contents($this->_getLockName($rid));
        if ($timestamp === false)
            return true;
        if ($tm - $timestamp > 1500) {
            $this->unlock($rid);
            return true;
        }

        return false;
    }

    protected function _getLockName($rid)
    {
        return "./tmp/room_$rid.lock";
    }
}
