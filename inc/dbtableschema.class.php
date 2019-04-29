<?php

/* 
 * Copyright (C) 2019 CJ Development Studios and contributors
 * Based on GLPI Copyright (C) 2015-2018 Teclib' and contributors
 * Based on GLPI Copyright (C) 2003-2014 INDEPNET Development team
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class DBTableSchema {
   private $table    = '';
   private $fields   = [];
   private $keys     = [];

   
   public function init($table, $add_id = true)
   {
      $this->table = $table;
      $this->fields = [];
      $this->keys = [];
      if ($add_id) {
         $this->addID();
      }
      return $this;
   }

    public function addID()
    {
        $this->fields['id'] = "int(11) NOT NULL AUTO_INCREMENT";
        $this->keys['PRIMARY'] = 'id';
        return $this;
    }

    public function addField($name, $type, $options = [])
    {
         global $DB;

         $params = [
            'value'      => null,
            'nodefault'  => false,
            'comment'    => ''
         ];
         $params = array_replace($params, $options);

         $fieldparams = $this->fieldFormat($type, $params['value'], $params['nodefault']);

         if (!empty($params['comment'])) {
            $fieldparams .= " COMMENT '".addslashes($params['comment'])."'";
         }

        $this->fields[$name] = $fieldparams;

        return $this;
    }
   
    public function addIndexedField($name, $type, $options = []) {
        return $this->addField($name, $type, $options)
            ->addKey($name);
    }

    public function setPrimaryKey($name)
    {
        $this->keys['PRIMARY'] = $name;
        return $this;
    }

    public function addKey($name, $constraints = [])
    {
        if (empty($constraints)) {
            $constraints = [$name];
        }
        $this->keys['KEY'][$name] = $constraints;
        return $this;
    }

    public function addFullTextKey($name, $constraints = [])
    {
        if (empty($constraints)) {
            $constraints = [$name];
        }
        $this->keys['FULLTEXT'][$name] = $constraints;
        return $this;
    }

    public function addUniqueKey($name, $constraints = [])
    {
        if (empty($constraints)) {
            $constraints = [$name];
        }
        $this->keys['UNIQUE'][$name] = $constraints;
        return $this;
    }

    public function create($drop = false)
    {
        global $DB;

        if ($drop) {
            $DB->drop($this->table);
        }
        $DB->create($this->table, $this->fields, $this->keys);
    }

    public function createOrDie($drop = false, $message = '')
    {
        global $DB;

        if ($drop) {
            $DB->dropOrDie($this->table, $message);
        }
        $DB->createOrDie($this->table, $this->fields, $this->keys, $message);
    }

    public function addFKField($foreign_table, $default = 0, $comment = '')
    {
        $fk_table = preg_replace(preg_quote('glpi_'), '', $foreign_table, 1);
        $fk .= "{$fk_table}_id";
        return $this->addField($fk, 'int(11)', $default, false, $comment);
    }

    public function addIndexedFKField($foreign_table, $default = 0, $comment = '')
    {
        $fk_table = preg_replace(preg_quote('glpi_'), '', $foreign_table, 1);
        $fk .= "{$fk_table}_id";
        return $this->addField($fk, 'int(11)', $default, false, $comment)
            ->addKey($fk);
    }

    /**
    * Define field's format
    *
    * @param string  $type          can be bool, char, string, integer, date, datetime, text, longtext or autoincrement
    * @param string  $default_value new field's default value,
    *                               if a specific default value needs to be used
    * @param boolean $nodefault     No default value (false by default)
    *
    * @return string
   **/
   private function fieldFormat($type, $default_value, $nodefault = false) {

      $format = '';
      switch ($type) {
         case 'boolean':
         case 'bool' :
            $format = "TINYINT(1) NOT NULL";
            if (!$nodefault) {
               if (is_null($default_value)) {
                  $format .= " DEFAULT '0'";
               } else {
                  $format .= " DEFAULT '$default_value'";
               }
            }
            break;

         case 'byte' ;
            $format = "TINYINT(4) NOT NULL";
            if (!$nodefault) {
               $format .= " DEFAULT '$default_value'";
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
         case 'int':
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

         case 'timestamp' :
         case 'datetime' :
            $format = "TIMESTAMP";
            if (!$nodefault) {
               if (is_null($default_value)) {
                  $format.= " NULL DEFAULT NULL";
               } else {
                  $format.= " NULL DEFAULT '$default_value'";
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
         case 'primary':
         case 'autoincrement' :
            $format = "INT(11) NOT NULL AUTO_INCREMENT";
            break;

         default :
            $format = $type;
            if (!$nodefault) {
               if (is_null($default_value)) {
                  $format .= " DEFAULT NULL";
               } else {
                  $format .= " NOT NULL DEFAULT '$default_value'";
               }
            }
            break;
      }
      return $format;
   }
} 
