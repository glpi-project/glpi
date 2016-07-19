<?php

// include glpi core
define('GLPI_ROOT', realpath('.'));
include_once (GLPI_ROOT . "/config/define.php");
include_once (GLPI_ROOT . "/config/based_config.php");
include_once (GLPI_ROOT . "/inc/dbmysql.class.php");
include_once (GLPI_CONFIG_DIR . "/config_db.php");

$DBvars = get_class_vars('DB');
$cmd_mysql = constructMysqlOptions($DBvars['dbuser'], $DBvars['dbhost'], $DBvars['dbpassword']);

// confirm execution of script
if (!in_array('--force', $_SERVER['argv'])) {
   echo "This will delete and recreate '".$DBvars['dbdefault']."' database.\n";
   echo "Are you sure you want to do this?  Type 'yes' to continue:\n";
   $handle = fopen ("php://stdin","r");
   $line = fgets($handle);
   if(trim($line) != 'yes'){
       echo "ABORTING!\n";
       exit;
   }
   fclose($handle);
   echo "\n";
}


echo "drop all existing table in database: ";
$res = dropTables($DBvars['dbuser'],
                  $DBvars['dbhost'],
                  $DBvars['dbdefault'],
                  $DBvars['dbpassword']);
displayCommandResult($res);


echo "fill database: ";
$res = loadMysqlFile($DBvars['dbuser'],
                     $DBvars['dbhost'],
                     $DBvars['dbdefault'],
                     $DBvars['dbpassword'],
                     GLPI_ROOT ."/install/mysql/glpi-".GLPI_VERSION."-empty.sql");
displayCommandResult($res);


echo "install GLPI: ";
$output = array();
$returncode = 0;
exec(
   "php -f ".GLPI_ROOT. "/tools/cliupdate.php -- --upgrade",
   $output, $returncode
);
displayCommandResult(array('returncode' => $returncode,
                             'output'     => $output));


echo "Enable API: ";
exec(
   $cmd_mysql . " -e 'UPDATE glpi_configs SET value = 1 WHERE name LIKE \"enable_api%\"' ".$DBvars['dbdefault'],
   $output, $returncode
);
displayCommandResult(array('returncode' => $returncode,
                             'output'     => $output));


echo "Change url in GLPI: ";
exec(
   $cmd_mysql . " -e 'UPDATE glpi_configs SET value = \"http://localhost/glpi_testsuite/\" WHERE name = \"url_base\"' ".$DBvars['dbdefault'],
   $output, $returncode
);
displayCommandResult(array('returncode' => $returncode,
                             'output'     => $output));



exit;




function constructMysqlOptions($dbuser='', $dbhost='', $dbpassword='', $cmd_mysql='mysql') {
   if ( empty($dbuser) || empty($dbhost)) {
      return array(
         'returncode' => 2,
         'output' => array("ERROR: missing mysql parameters (user='{$dbuser}', host='{$dbhost}')")
      );
   }

   if (strpos($dbhost, ':') !== FALSE) {
      $dbhost = explode( ':', $dbhost);
      if ( !empty($dbhost[0]) ) {
         $cmd_mysql.= " --host ".$dbhost[0];
      }
      if ( is_numeric($dbhost[1]) ) {
         $cmd_mysql.= " --port ".$dbhost[1];
      } else {
         // The dbhost's second part is assumed to be a socket file if it is not numeric.
         $cmd_mysql.=  "--socket ".$dbhost[1];
      }
   } else {
      $cmd_mysql.= " --host ".$dbhost;
   }

   $cmd_mysql.= " --user ".$dbuser;

   if (!empty($dbpassword)) {
      $cmd_mysql.= " -p'".urldecode($dbpassword)."'";
   }

   return $cmd_mysql;
}


function dropTables($dbuser='', $dbhost='', $dbdefault='', $dbpassword='') {
   $cmd_mysql = constructMysqlOptions($dbuser, $dbhost, $dbpassword);
   $cmd_mysqldump = constructMysqlOptions($dbuser, $dbhost, $dbpassword, 'mysqldump');

   $cmd = $cmd_mysqldump.' --no-data --add-drop-table '.$dbdefault .' | grep ^DROP | '.$cmd_mysql.' -v '.$dbdefault;

   $returncode = 0;
   $output = array();
   exec($cmd, $output, $returncode);
   return array('returncode' => $returncode,
                'output'     => $output);
}


function loadMysqlFile($dbuser='', $dbhost='', $dbdefault='', $dbpassword='', $file = NULL) {
   if (!file_exists($file)) {
      return array(
         'returncode' => 1,
         'output' => array("ERROR: File '{$file}' does not exist !")
      );
   }

   $mysql_cmd = constructMysqlOptions($dbuser, $dbhost, $dbpassword, 'mysql');
   $cmd = $mysql_cmd . " " . $dbdefault . " < ". $file ." 2>&1";

   $returncode = 0;
   $output = array();
   exec($cmd, $output, $returncode);
   return array('returncode' => $returncode,
                'output'     => $output);
}

function displayCommandResult($result) {
   $codes = [true => "OK", false => "KO"];

   $bool_result = ($result['returncode'] == 0);
   echo $codes[$bool_result]."\n";
   if (!$bool_result) {
      echo implode("\n", $result['output'])."\n";
      exit($result['returncode']);
   }
}