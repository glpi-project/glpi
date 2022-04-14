<?php
class DB extends DBmysql {
   public $dbhost = 'db';
   public $dbuser = 'glpi';
   public $dbpassword = 'glpi';
   public $dbdefault = '';
   public $log_deprecation_warnings = true;
   public $use_utf8mb4 = true;
   public $allow_datetime = false;
   public $allow_myisam = false;
   public $allow_signed_keys = false;
   public $use_timezones = true;
}
