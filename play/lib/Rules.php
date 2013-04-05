<?php

class Rules
{
    /**
     * Rules instance
     * 
     * @var Rules
     */
    protected static $_instance;
    
    /**
     * List of rules and any associated attributes
     * 
     * @var string[]
     */
    public $_ruleList;
    
    const NO_RULE = 0;
    
    /**
     * Gets the current Rules instance
     * 
     * @return Rules
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new Rules();
        }
        
        return self::$_instance;
    }
    
    public function __construct()
    {
        $config = Configuration::getInstance();
        $json = file_get_contents($config->get('rules.file'), FILE_USE_INCLUDE_PATH);
        $this->_ruleList = json_decode($json, true);
    }
    
    protected function _getRandomRule()
    {
        return mt_rand(1, $this->getRuleCount());
    }

    protected function _createAcronym()
    {
        $chars = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K',
            'L', 'M', 'L', 'O', 'P', 'R', 'S', 'T', 'U', 'V', 'W', 'Y');
        $len = mt_rand(3, 4);
        $result = "";
        for ($i = 0; $i < $len; $i++)
            $result.=$chars[mt_rand(0, count($chars) - 1)];
        return $result;
    }
    
    protected function _createRuleData($type)
    {
        if ($type == 'acronym') {
            return $this->_createAcronym();
        }
        
        return '';
    }

    /**
     * Assigns a random rule to the given room data array
     * 
     * @param string[] $room_array
     * @return string[] the modified room array
     */
    public function setRandomRule($room_array)
    {
        $ruleId = $this->_getRandomRule();
        $ruleType = $this->_getRuleType($ruleId);
        $ruledata = $this->_createRuleData($ruleType);
        $room_array['rule'] = $ruleId;
        $room_array['rule_data'] = $ruledata;
        return $room_array;
    }

    /**
     * Return a string describing the caption to write.  This will be output in the <rule> xml tag.
     * 
     * @param string[] $room_array
     * @return string 
     */
    public function getRuleString($room_array)
    {
        $ruleId = $room_array['rule'];
        $ruleText = sprintf($this->_ruleList[$ruleId]['rule'],
                $room_array['rule_data']);
        
        return $ruleText;
    }
    
    /**
     * Returns the total number of rules that can appear in the final round
     * 
     * @return int
     */
    public function getRuleCount()
    {
        // Subtracts 1 since the first rule on the list is always the "none" rule.
        return count($this->_ruleList) - 1;
    }
    
    /**
     * Gets the given rule's type, if it is set.
     * 
     * @param int $ruleId
     * @return string rule type, or boolean false if no type is set
     */
    public function _getRuleType($ruleId)
    {
        $type = isset($this->_ruleList[$ruleId]['type']) ? $this->_ruleList[$ruleId]['type'] : false;
        return $type;
    }
}