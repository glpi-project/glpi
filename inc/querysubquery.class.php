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
class QuerySubQuery {
   private $dbiterator;
   private $allowed_operators = ['IN', 'NOT IN'];
   private $operator = 'IN';

   /**
    * Create a sub query
    *
    * @param array $crit Arry of query criteria. Any valid DBmysqliterator parameters are valid.
    */
   public function __construct(array $crit, $operator = null) {
      global $DB;

      if (empty($crit)) {
         throw new \RuntimeException('Cannot build an empty subquery');
      }

      $this->dbiterator = new DBmysqliterator($DB);
      if ($operator !== null) {
         $this->setOperator($operator);
      }
      $this->dbiterator->buildQuery($crit);
   }

   /**
    * Set query operator
    *
    * @param string $operator Query operator
    *
    * @return QuerySubQuery
    */
   public function setOperator($operator) {
      if (!$this->dbiterator->isOperator($operator) &&!in_array($operator, $this->allowed_operators, true)) {
         throw new \RuntimeException("Unknown query operator $operator");
      }
      $this->operator = $operator;
      return $this;
   }

   /**
    * Get operator
    *
    * @return string
    */
   public function getOperator() {
      return $this->operator;
   }

   /**
    * Query sub query
    *
    * @return string
    */
   public function getSubQuery() {
      return $this->dbiterator->getSql();
   }
}
