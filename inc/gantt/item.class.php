<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class Item implements JsonSerializable {
    public $id;
    public $start_date; // format 2019-09-07 04:06:15
    public $end_date;
    public $text;
    public $note;
    public $type;       // project / task / milestone
    public $progress;
    public $parent;
    public $open;       // 1 / 0

    public function __construct() {
        $this->id = 0;
        $this->start_date = date("Y-m-d H:i:s");
        $this->progress = 0.0;
        $this->parent = "";
        $this->open = 1;
    }

    public function populateFrom($json) {
        $this->id = $json["id"];
        if (isset($json["start_date"]))
            $this->start_date = $json["start_date"];
        if (isset($json["end_date"]))
            $this->end_date = $json["end_date"];
        if (isset($json["progress"]))
            $this->progress = $json["progress"];
        if (isset($json["name"]))
            $this->text = $json["name"];
    }

    public function jsonSerialize() {
        return (array)$this;
    }
}