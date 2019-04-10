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
 *  Sub query class
**/
class QuerySubQuery extends AbstractQuery {
   private $dbiterator;

   /**
    * Create a sub query
    *
    * @param array  $crit      Array of query criteria. Any valid DBmysqliterator parameters are valid.
    * @param string $alias     Alias for the whole subquery
    */
   public function __construct(array $crit, $alias = null) {
      global $DB;

      parent::__construct($alias);
      if (empty($crit)) {
         throw new \RuntimeException('Cannot build an empty subquery');
      }

      $this->dbiterator = new DBmysqliterator($DB);
      $this->dbiterator->buildQuery($crit);
   }

   /**
    *
    * Get SQL query
    *
    * @return string
    */
   public function getQuery() {
      global $DB;

      $sql = "(" . $this->dbiterator->getSql() . ")";

      if ($this->alias !== null) {
         $sql .= ' AS ' . $DB->quoteName($this->alias);
      }
      return $sql;
   }

   public function getParameters() {
      return $this->dbiterator->getParameters();
   }
}
