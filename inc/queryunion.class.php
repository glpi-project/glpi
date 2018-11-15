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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * UNION query class
**/
class QueryUnion {
   private $queries = [];
   private $alias;
   private $distinct = false;

   /**
    * Create a sub query
    *
    * @param array   $queries  An array of queries to union. Either SubQuery objects
    *                          or an array of criteria to build them.
    * @param boolean $distinct Include duplicatesi or not. Turning on may has
    *                          huge cost on queries performances.
    * @param string  $alias    Union ALIAS. Defaults to null.
    */
   public function __construct(array $queries, $distinct = false, $alias = null) {
      if (empty($queries)) {
         throw new \RuntimeException('Cannot build an empty union query');
      }

      $this->alias = $alias;
      $this->distinct = $distinct;

      foreach ($queries as $query) {
         if (!$query instanceof \QuerySubQuery) {
            $query = new \QuerySubQuery($query);
         }
         $this->queries[] = $query;
      }
   }


   /**
    * Get queries
    *
    * @return array
    */
   public function getQueries() {
      return $this->queries;
   }

   /**
    * Get alias
    *
    * @return string|null
    */
   public function getAlias() {
      return $this->alias;
   }

   /**
    *
    * Get SQL query
    *
    * @return string
    */
   public function getQuery() {
      global $DB;

      $queries = [];
      foreach ($this->getQueries() as $uquery) {
         $queries[] = $uquery->getSubQuery();
      }

      $keyword = 'UNION';
      if (!$this->distinct) {
         $keyword .= ' ALL';
      }
      $query = '(' . implode(" $keyword ", $queries) . ')';
      if ($this->alias !== null) {
         $query .= ' AS ' . $DB->quoteName($this->alias);
      }
      return $query;
   }
}
