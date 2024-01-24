<?php
class DB extends DBmysql {
   public $dbhost = 'localhost';
   public $dbuser = 'teclib';
   public $dbpassword = 'teclib';
   public $dbdefault = 'main-tests';
   public $use_timezones = true;
   public $use_utf8mb4 = true;
   public $allow_myisam = false;
   public $allow_datetime = false;
   public $allow_signed_keys = false;
}
