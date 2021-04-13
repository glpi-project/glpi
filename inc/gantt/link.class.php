<?php


if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class Link {
    public $id;
    public $source;
    public $source_uuid;
    public $target;
    public $target_uuid;
    public $type;   // possible values: "finish_to_start":"0", "start_to_start":"1", "finish_to_finish":"2", "start_to_finish":"3" 
    public $lag;
    public $lead;

    public function __construct() {
        $this->id = 0;
        $this->source = 0;
        $this->target = 0;
        $this->source_uuid = "";
        $this->target_uuid = "";
        $this->type = 0;
        $this->lag = 0;
        $this->lead = 0;
    }

    public function jsonSerialize() {
        return (array)$this;
    }    
}