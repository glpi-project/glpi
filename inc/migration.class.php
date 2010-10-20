<?php
/*
 * @version $Id: central.class.php 11581 2010-06-09 11:30:44Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// class Central
class Migration {

   private $change    = array();
   private $version;


   function __construct($ver) {
      $this->version = $ver;
   }


   /**
    * Add field for migration
    *
    * @param $table
    * @param $field to add
    * @param $format of the field (ex: int(11) not null default 0)
   **/
   function addField($table, $field, $format) {

      if (!FieldExists($table,$field)) {
         $this->change[$table][] = "ADD `$field` $format";
         return true;
      }
      return false;
   }


   /**
    * Modify field for migration
    *
    * @param $table
    * @param $oldfield : old name of the field
    * @param $newfield : new name of the field
    * @param $format : new format of the field (ex: int(11) not null default 0)
   **/
   function changeField($table, $oldfield, $newfield, $format) {

      if (FieldExists($table,$oldfield)) {
         $this->change[$table][] = "CHANGE `$oldfield` `$newfield` $format";
         return true;
      }
      return false;
   }


   /**
    * Drop field for migration
    *
    * @param $table
    * @param $field to drop
   **/
   function dropField($table, $field) {

      if (FieldExists($table,$field)) {
         $this->change[$table][] = "DROP `$field`";
      }
   }


   /**
    * Add index for migration
    *
    * @param $table
    * @param $fields : string or array
    * @param $indexname : if empty =$fields
    * @param $type : index or unique
   **/
   function addKey($table, $fields, $indexname='', $type='INDEX') {

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
            $fields = implode($fields, "`, `");
         }

         $this->change[$table][] = "ADD $type `$indexname` (`$fields`)";
      }
   }


   /**
    * Drop index for migration
    *
    * @param $table
    * @param $indexname
   **/
   function dropKey($table, $indexname) {

      if (isIndex($table,$indexname)) {
         $this->change[$table][] = "DROP INDEX `$indexname`";
      }
   }


   /**
    * Execute migration for only one table
    *
    * @param $table
   **/

   function migrationOneTable($table) {
      global $DB, $LANG;

      if (isset($this->change[$table])) {
         $query = "ALTER TABLE `$table` ".implode($this->change[$table], " ,\n")." ";
         displayMigrationMessage($this->version, $LANG['update'][141] . ' - '.$table);
         $DB->query($query)
         or die($this->version." multiple alter in $table " . $LANG['update'][90] . $DB->error());

         unset($this->change[$table]);
      }
   }


   /**
    * Execute global migration
   **/

   function executeMigration() {
      global $LANG;

      foreach ($this->change as $table => $tab) {
         $this->migrationOneTable($table);
       }
   }

}

?>
