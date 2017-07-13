<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

// class Central
/**
 * Migration Class
 *
 * @since version 0.80
**/
class Migration {

   private   $change     = array();
   protected $version;
   private   $deb;
   private   $lastMessage;
   private   $log_errors = 0;
   private   $current_message_area_id;


   /**
    * @param $ver    number of new version of GLPI
   **/
   function __construct($ver) {

      $this->deb = time();
      $this->setVersion($ver);
   }


   /**
    * @since version 0.84
    *
    * @param $ver    number of new version
   **/
   function setVersion($ver) {

      $this->flushLogDisplayMessage();
      $this->version = $ver;
      $this->addNewMessageArea("migration_message_$ver");
   }


   /**
    * @since version 0.84
    *
    * @param $id
   **/
   function addNewMessageArea($id) {

      $this->current_message_area_id = $id;
      echo "<div id='".$this->current_message_area_id."'>
            <p class='center'>".__('Work in progress...')."</p></div>";

      $this->flushLogDisplayMessage();
   }


   /**
    * Flush previous display message in log file
    *
    * @since version 0.84
   **/
   function flushLogDisplayMessage() {

      if (isset($this->lastMessage)) {
         $tps = Html::timestampToString(time() - $this->lastMessage['time']);
         $this->log($tps . ' for "' . $this->lastMessage['msg'] . '"', false);
         unset($this->lastMessage);
      }
   }


   /**
    * Additional message in global message
    *
    * @param $msg    text  to display
   **/
   function displayMessage($msg) {

      $now = time();
      $tps = Html::timestampToString($now-$this->deb);
      echo "<script type='text/javascript'>document.getElementById('".
             $this->current_message_area_id."').innerHTML=\"<p class='center'>".addslashes($msg).
             " ($tps)</p>\";".
           "</script>\n";

      $this->flushLogDisplayMessage();
      $this->lastMessage = array('time' => time(),
                                 'msg'  => $msg);

      Html::glpi_flush();
   }


   /**
    * log message for this migration
    *
    * @since version 0.84
    *
    * @param $message
    * @param $warning
   **/
   function log($message, $warning) {

      if ($warning) {
         $log_file_name = 'warning_during_migration_to_'.$this->version;
      } else {
         $log_file_name = 'migration_to_'.$this->version;
      }

     // Do not log if more than 3 log error
     if ($this->log_errors < 3
         && !Toolbox::logInFile($log_file_name, $message . ' @ ', true)) {
         $this->log_errors++;
     }
   }


   /**
    * Display a title
    *
    * @param $title string
   **/
   function displayTitle($title) {
      echo "<h3>".Html::entities_deep($title)."</h3>";
   }


   /**
    * Display a Warning
    *
    * @param $msg    string
    * @param $red    boolean (false by default)
   **/
   function displayWarning($msg, $red=false) {

      echo ($red ? "<div class='red'><p>" : "<p><span class='b'>") .
            Html::entities_deep($msg) . ($red ? "</p></div>" : "</span></p>");

      $this->log($msg, true);
   }


   /**
    * Define field's format
    *
    * @param $type            string   can be bool, string, integer, date, datatime, text, longtext,
    *                                         autoincrement, char
    * @param $default_value   string   new field's default value,
    *                                  if a specific default value needs to be used
    * @param $nodefault       bolean   (false by default)
   **/
   private function fieldFormat($type, $default_value, $nodefault=false) {

      $format = '';
      switch ($type) {
         case 'bool' :
            $format = "TINYINT(1) NOT NULL";
            if (!$nodefault) {
               if (is_null($default_value)) {
                  $format .= " DEFAULT '0'";
               } else if (in_array($default_value, array('0', '1'))) {
                  $format .= " DEFAULT '$default_value'";
               } else {
                  trigger_error(__('default_value must be 0 or 1'), E_USER_ERROR);
               }
            }
            break;

         case 'char' :
            $format = "CHAR(1)";
            if (!$nodefault) {
               if (is_null($default_value)) {
                  $format .= " DEFAULT NULL";
               } else {
                  $format .= " NOT NULL DEFAULT '$default_value'";
               }
            }
            break;

         case 'string' :
            $format = "VARCHAR(255) COLLATE utf8_unicode_ci";
            if (!$nodefault) {
               if (is_null($default_value)) {
                  $format .= " DEFAULT NULL";
               } else {
                  $format .= " NOT NULL DEFAULT '$default_value'";
               }
            }
            break;

         case 'integer' :
            $format = "INT(11) NOT NULL";
            if (!$nodefault) {
               if (is_null($default_value)) {
                  $format .= " DEFAULT '0'";
               } else if (is_numeric($default_value)) {
                  $format .= " DEFAULT '$default_value'";
               } else {
                  trigger_error(__('default_value must be numeric'), E_USER_ERROR);
               }
            }
            break;

         case 'date' :
            $format = "DATE";
            if (!$nodefault) {
               if (is_null($default_value)) {
                  $format.= " DEFAULT NULL";
               } else {
                  $format.= " DEFAULT '$default_value'";
               }
            }
            break;

         case 'datetime' :
            $format = "DATETIME";
            if (!$nodefault) {
               if (is_null($default_value)) {
                  $format.= " DEFAULT NULL";
               } else {
                  $format.= " DEFAULT '$default_value'";
               }
            }
            break;

         case 'text' :
            $format = "TEXT COLLATE utf8_unicode_ci";
            if (!$nodefault) {
               if (is_null($default_value)) {
                  $format.= " DEFAULT NULL";
               } else {
                  $format.= " NOT NULL DEFAULT '$default_value'";
               }
            }
            break;

         case 'longtext' :
            $format = "LONGTEXT COLLATE utf8_unicode_ci";
            if (!$nodefault) {
               if (is_null($default_value)) {
                  $format .= " DEFAULT NULL";
               } else {
                  $format .= " NOT NULL DEFAULT '$default_value'";
               }
            }
            break;

         // for plugins
         case 'autoincrement' :
            $format = "INT(11) NOT NULL AUTO_INCREMENT";
            break;

         default :
            // for compatibility with old 0.80 migrations
            $format = $type;
            break;
      }
      return $format;
   }


   /**
    * Add a new GLPI normalized field
    *
    * @param $table     string
    * @param $field     string   to add
    * @param $type      string   (see fieldFormat)
    * @param $options   array
    *    - update    : if not empty = value of $field (must be protected)
    *    - condition : if needed
    *    - value     : default_value new field's default value, if a specific default value needs to be used
    *    - nodefault : do not define default value (default false)
    *    - comment   : comment to be added during field creation
    *    - after     : where adding the new field
   **/
   function addField($table, $field, $type, $options=array()) {
      global $DB;

      $params['update']    = '';
      $params['condition'] = '';
      $params['value']     = NULL;
      $params['nodefault'] = false;
      $params['comment']   = '';
      $params['after']     = '';
      $params['first']     = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $format = $this->fieldFormat($type, $params['value'], $params['nodefault']);

      if ($params['comment']) {
         $params['comment'] = " COMMENT '".addslashes($params['comment'])."'";
      }

      if ($params['after']) {
         $params['after'] = " AFTER `".$params['after']."`";
      } else if (isset($params['first'])) {
         $params['first'] = " FIRST ";
      }

      if ($format) {
         if (!FieldExists($table, $field, false)) {
            $this->change[$table][] = "ADD `$field` $format ".$params['comment'] ." ".
                                           $params['first'].$params['after']."";

            if (isset($params['update']) && strlen($params['update'])) {
               $this->migrationOneTable($table);
               $query = "UPDATE `$table`
                         SET `$field` = ".$params['update']." ".
                         $params['condition']."";
               $DB->queryOrDie($query, $this->version." set $field in $table");
            }
            return true;
         }
         return false;
      }
   }


   /**
    * Modify field for migration
    *
    * @param $table        string
    * @param $oldfield     string   old name of the field
    * @param $newfield     string   new name of the field
    * @param $type         string   (see fieldFormat)
    * @param $options      array
    *    - default_value new field's default value, if a specific default value needs to be used
    *    - comment comment to be added during field creation
    *    - nodefault : do not define default value (default false)
   **/
   function changeField($table, $oldfield, $newfield, $type, $options=array()) {

      $params['value']     = NULL;
      $params['nodefault'] = false;
      $params['comment']   = '';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $format = $this->fieldFormat($type, $params['value'], $params['nodefault']);

      if ($params['comment']) {
         $params['comment'] = " COMMENT '".addslashes($params['comment'])."'";
      }


      if (FieldExists($table, $oldfield, false)) {
         // in order the function to be replayed
         // Drop new field if name changed
         if (($oldfield != $newfield)
             && FieldExists($table, $newfield)) {
            $this->change[$table][] = "DROP `$newfield` ";
         }

         if ($format) {
            $this->change[$table][] = "CHANGE `$oldfield` `$newfield` $format ".$params['comment']."";
         }
         return true;
      }

      return false;
   }


   /**
    * Drop field for migration
    *
    * @param $table  string
    * @param $field  string   field to drop
   **/
   function dropField($table, $field) {

      if (FieldExists($table, $field, false)) {
         $this->change[$table][] = "DROP `$field`";
      }
   }


   /**
    * Drop immediatly a table if it exists
    *
    * @param table   string
   **/
   function dropTable($table) {
      global $DB;

      if (TableExists($table)) {
         $DB->query("DROP TABLE `$table`");
      }
   }


   /**
    * Add index for migration
    *
    * @param $table        string
    * @param $fields       string or array
    * @param $indexname    string            if empty =$fields (default '')
    * @param $type         string            index or unique (default 'INDEX')
    * @param $len          integer           for field length (default 0)
   **/
   function addKey($table, $fields, $indexname='', $type='INDEX', $len=0) {

      // si pas de nom d'index, on prend celui du ou des champs
      if (!$indexname) {
         if (is_array($fields)) {
            $indexname = implode($fields, "_");
         } else {
            $indexname = $fields;
         }
      }

      if (!isIndex($table,$indexname)) {
         if (is_array($fields)) {
            if ($len) {
               $fields = "`".implode($fields, "`($len), `")."`($len)";
            } else {
               $fields = "`".implode($fields, "`, `")."`";
            }
         } else if ($len) {
            $fields = "`$fields`($len)";
         } else {
            $fields = "`$fields`";
         }

         $this->change[$table][] = "ADD $type `$indexname` ($fields)";
      }
   }


   /**
    * Drop index for migration
    *
    * @param $table     string
    * @param $indexname string
   **/
   function dropKey($table, $indexname) {

      if (isIndex($table,$indexname)) {
         $this->change[$table][] = "DROP INDEX `$indexname`";
      }
   }


   /**
    * Rename table for migration
    *
    * @param $oldtable  string
    * @param $newtable  string
   **/
   function renameTable($oldtable, $newtable) {
      global $DB;

      if (!TableExists("$newtable") && TableExists("$oldtable")) {
         $query = "RENAME TABLE `$oldtable` TO `$newtable`";
         $DB->queryOrDie($query, $this->version." rename $oldtable");
      }
   }


   /**
    * Copy table for migration
    *
    * @since version 0.84
    *
    * @param $oldtable  string   The name of the table already inside the database
    * @param $newtable  string   The copy of the old table
   **/
   function copyTable($oldtable, $newtable) {
      global $DB;

      if (!TableExists($newtable)
          && TableExists($oldtable)) {

//          // Try to do a flush tables if RELOAD privileges available
//          $query = "FLUSH TABLES `$oldtable`, `$newtable`";
//          $DB->query($query);

         $query = "CREATE TABLE `$newtable` LIKE `$oldtable`";
         $DB->queryOrDie($query, $this->version." create $newtable");

         $query = "INSERT INTO `$newtable`
                          (SELECT *
                           FROM `$oldtable`)";
         $DB->queryOrDie($query, $this->version." copy from $oldtable to $newtable");
      }
   }


   /**
    * Insert an entry inside a table
    *
    * @since version 0.84
    *
    * @param $table  string   The table to alter
    * @param $input  array    The elements to add inside the table
    *
    * @return id of the last item inserted by mysql
   **/
   function insertInTable($table, array $input) {
      global $DB;

      if (TableExists("$table")
          && is_array($input) && (count($input) > 0)) {

         $fields = array();
         $values = array();
         foreach ($input as $field => $value) {
            if (FieldExists($table, $field)) {
               $fields[] = "`$field`";
               $values[] = "'$value'";
            }
         }
         $query = "INSERT INTO `$table`
                          (" . implode(', ', $fields) . ")
                   VALUES (" .implode(', ', $values) . ")";
         $DB->queryOrDie($query, $this->version." insert in $table");

         return $DB->insert_id();
      }
   }


   /**
    * Execute migration for only one table
    *
    * @param $table  string
   **/
   function migrationOneTable($table) {
      global $DB;

      if (isset($this->change[$table])) {
         $query = "ALTER TABLE `$table` ".implode($this->change[$table], " ,\n")." ";
         $this->displayMessage( sprintf(__('Change of the database layout - %s'), $table));
         $DB->queryOrDie($query, $this->version." multiple alter in $table");

         unset($this->change[$table]);
      }
   }


   /**
    * Execute global migration
   **/
   function executeMigration() {

      foreach ($this->change as $table => $tab) {
         $this->migrationOneTable($table);
      }

      // as some tables may have be renamed, unset session matching between tables and classes
      unset($_SESSION['glpi_table_of']);

      // end of global message
      $this->displayMessage(__('Task completed.'));
   }


   /**
    * Register a new rule
    *
    * @param $rule      Array of fields of glpi_rules
    * @param $criteria  Array of Array of fields of glpi_rulecriterias
    * @param $actions   Array of Array of fields of glpi_ruleactions
    *
    * @since version 0.84
    *
    * @return integer : new rule id
   **/
   function createRule(Array $rule, Array $criteria, Array $actions) {
      global $DB;

      // Avoid duplicate - Need to be improved using a rule uuid of other
      if (countElementsInTable('glpi_rules', "`name`='".$DB->escape($rule['name'])."'")) {
         return 0;
      }
      $rule['comment']     = sprintf(__('Automatically generated by GLPI %s'), $this->version);
      $rule['description'] = '';

      // Compute ranking
      $sql = "SELECT MAX(`ranking`) AS rank
              FROM `glpi_rules`
              WHERE `sub_type` = '".$rule['sub_type']."'";
      $result = $DB->query($sql);

      $ranking = 1;
      if ($DB->numrows($result) > 0) {
         $datas = $DB->fetch_assoc($result);
         $ranking = $datas["rank"] + 1;
      }

      // The rule itself
      $fields = "`ranking`";
      $values = "'$ranking'";
      foreach ($rule as $field => $value) {
         $fields .= ", `$field`";
         $values .= ", '".$DB->escape($value)."'";
      }
      $sql = "INSERT INTO `glpi_rules`
                     ($fields)
              VALUES ($values)";
      $DB->queryOrDie($sql);
      $rid = $DB->insert_id();

      // The rule criteria
      foreach ($criteria as $criterion) {
         $fields = "`rules_id`";
         $values = "'$rid'";
         foreach ($criterion as $field => $value) {
            $fields .= ", `$field`";
            $values .= ", '".$DB->escape($value)."'";
         }
         $sql = "INSERT INTO `glpi_rulecriterias`
                        ($fields)
                 VALUES ($values)";
         $DB->queryOrDie($sql);
      }

      // The rule criteria actions
      foreach ($actions as $action) {
         $fields = "`rules_id`";
         $values = "'$rid'";
         foreach ($action as $field => $value) {
            $fields .= ", `$field`";
            $values .= ", '".$DB->escape($value)."'";
         }
         $sql = "INSERT INTO `glpi_ruleactions`
                        ($fields)
                 VALUES ($values)";
         $DB->queryOrDie($sql);
      }
   }


   /**
    * Update display preferences
    *
    * @since version 0.85
    *
    * @param $toadd   array   items to add : itemtype => array of values
    * @param $todel   array   items to del : itemtype => array of values
   **/
   function updateDisplayPrefs($toadd=array(), $todel=array()) {
      global $DB;

      //TRANS: %s is the table or item to migrate
      $this->displayMessage(sprintf(__('Data migration - %s'), 'glpi_displaypreferences'));
      if (count($toadd)) {
         foreach ($toadd as $type => $tab) {
            $query = "SELECT DISTINCT `users_id`
                      FROM `glpi_displaypreferences`
                      WHERE `itemtype` = '$type'";

            if ($result = $DB->query($query)) {
               if ($DB->numrows($result) > 0) {
                  while ($data = $DB->fetch_assoc($result)) {
                     $query = "SELECT MAX(`rank`)
                               FROM `glpi_displaypreferences`
                               WHERE `users_id` = '".$data['users_id']."'
                                     AND `itemtype` = '$type'";
                     $result = $DB->query($query);
                     $rank   = $DB->result($result,0,0);
                     $rank++;

                     foreach ($tab as $newval) {
                        $query = "SELECT *
                                  FROM `glpi_displaypreferences`
                                  WHERE `users_id` = '".$data['users_id']."'
                                        AND `num` = '$newval'
                                        AND `itemtype` = '$type'";
                        if ($result2 = $DB->query($query)) {
                           if ($DB->numrows($result2) == 0) {
                              $query = "INSERT INTO `glpi_displaypreferences`
                                               (`itemtype` ,`num` ,`rank` ,`users_id`)
                                        VALUES ('$type', '$newval', '".$rank++."',
                                                '".$data['users_id']."')";
                              $DB->query($query);
                           }
                        }
                     }
                  }

               } else { // Add for default user
                  $rank = 1;
                  foreach ($tab as $newval) {
                     $query = "INSERT INTO `glpi_displaypreferences`
                                      (`itemtype` ,`num` ,`rank` ,`users_id`)
                               VALUES ('$type', '$newval', '".$rank++."', '0')";
                     $DB->query($query);
                  }
               }
            }
         }
      }

      if (count($todel)) {
         // delete display preferences
         foreach ($todel as $type => $tab) {
            if (count($tab)) {
               $query = "DELETE
                         FROM `glpi_displaypreferences`
                         WHERE `itemtype` = '$type'
                               AND `num` IN (".implode(',', $tab).")";
               $DB->query($query);
            }
         }
      }
   }

}
?>
