<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/**
 * DBTableSchema class.
 * This class provides a builder for creating database tables without creating DBMS-specific queries.
 * @since 10.0.0
 */
class DBTableSchema {
   private $table    = '';
   private $fields   = [];
   private $keys     = [];

   /**
    * Resets this object's schema and sets the table name.
    * @since 10.0.0
    * @param string $table The name of this table.
    * @param bool $add_id  If true, it adds an auto-increment integer `id` as the primary key.
    *    This is true by default.
    * @return DBTableSchema This function returns the object to allow chaining functions.
    */
   public function init(string $table, bool $add_id = true)
   {
      $this->table = $table;
      $this->fields = [];
      $this->keys = [];
      if ($add_id) {
         $this->addID();
      }
      return $this;
   }

   /**
    * Adds an auto-increment integer `id` column as the primary key.
    * @since 10.0.0
    * @return DBTableSchema This function returns the object to allow chaining functions.
    */
    public function addID()
    {
        $this->fields['id'] = "int(11) NOT NULL AUTO_INCREMENT";
        $this->keys['PRIMARY'] = 'id';
        return $this;
    }

    /**
     * Adds a field to the table schema with the given name, type and options.
     * @since 10.0.0
     * @param string $name The name of the field.
     * @param string $type The datatype of the field.
     * @param array $options Options related to the default value and comment.
     *   'value' - The default value. This is null by default.
     *   'nodefault' - If true, the 'value' option is ignored.
     *   'comment' - The comment to add onto the field.
     * @return DBTableSchema This function returns the object to allow chaining functions.
     */
    public function addField(string $name, string $type, array $options = [])
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

    /**
     * Adds a field to the table schema with the given name, type and options and also adds an key for it.
     * @since 10.0.0
     * @param string $name The name of the field.
     * @param string $type The datatype of the field.
     * @param array $options Options related to the default value and comment.
     *   'value' - The default value. This is null by default.
     *   'nodefault' - If true, the 'value' option is ignored.
     *   'comment' - The comment to add onto the field.
     * @return DBTableSchema This function returns the object to allow chaining functions.
     */
    public function addIndexedField(string $name, string $type, array $options = []) {
        return $this->addField($name, $type, $options)
            ->addKey($name);
    }

    /**
     * Changes the primary key
     * @since 10.0.0
     * @param type $name The name of the new primary key
     * @return DBTableSchema This function returns the object to allow chaining functions.
     */
    public function setPrimaryKey(string $name)
    {
        $this->keys['PRIMARY'] = $name;
        return $this;
    }

    /**
     * Adds a key with the given name and constraints.
     * @since 10.0.0
     * @param string $name The name of the key
     * @param array $constraints An array of field constraints for the key.
     *   If none are specified, the name of the key is used as the constraint.
     * @return DBTableSchema This function returns the object to allow chaining functions.
     */
    public function addKey(string $name, array $constraints = [])
    {
        if (empty($constraints)) {
            $constraints = [$name];
        }
        $this->keys['KEY'][$name] = $constraints;
        return $this;
    }

    /**
     * Adds a fulltext key with the given name and constraints.
     * @since 10.0.0
     * @param string $name The name of the key
     * @param array $constraints An array of field constraints for the key.
     * @return DBTableSchema This function returns the object to allow chaining functions.
     */
    public function addFullTextKey(string $name, array $constraints = [])
    {
        if (empty($constraints)) {
            $constraints = [$name];
        }
        $this->keys['FULLTEXT'][$name] = $constraints;
        return $this;
    }

    /**
     * Adds a unique key with the given name and constraints.
     * @since 10.0.0
     * @param string $name The name of the key
     * @param array $constraints An array of field constraints for the key.
     * @return DBTableSchema This function returns the object to allow chaining functions.
     */
    public function addUniqueKey(string $name, array $constraints = [])
    {
        if (empty($constraints)) {
            $constraints = [$name];
        }
        $this->keys['UNIQUE'][$name] = $constraints;
        return $this;
    }

    /**
     * Creates the table with the defined schema.
     * @since 10.0.0
     * @param bool $drop If true, an attempt is made to drop the existing table first before re-creating it.
     * @return void This is an endpoint function and does not return the object.
     */
    public function create(bool $drop = false)
    {
        global $DB;

        if ($drop) {
            $DB->drop($this->table);
        }
        $DB->create($this->table, $this->fields, $this->keys);
    }

    /**
     * Creates the table with the defined schema. On error, the script dies.
     * @since 10.0.0
     * @param bool $drop If true, an attempt is made to drop the existing table first before re-creating it.
     * @param string $message The message to display if there is an error.
     * @return void This is an endpoint function and does not return the object.
     */
    public function createOrDie(bool $drop = false, string $message = '')
    {
        global $DB;

        if ($drop) {
            $DB->dropOrDie($this->table, $message);
        }
        $DB->createOrDie($this->table, $this->fields, $this->keys, $message);
    }

    /**
    * Define field's format
    * @since 10.0.0
    * @param string  $type          can be bool, byte, char, string, integer, date, datetime, text, longtext or autoincrement
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
