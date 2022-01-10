<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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
 * Planning CSV export Class
**/
class PlanningCsv extends CommonGLPI {

   private $users_id;
   private $groups_id;
   private $limititemtype;

   private $eol = "\r\n";
   private $quote = '"';
   private $titles = [];
   private $lines = null;
   private $filename = 'planning.csv';

   /**
    * @param integer $who          user ID
    * @param integer $whogroup     group ID
    * @param string $limititemtype itemtype only display this itemtype (default '')
   **/

   public function __construct($who, $whogroup = null, $limititemtype = '') {
      $this->users_id      = $who;
      $this->groups_id     = $whogroup;
      $this->limititemtype = $limititemtype;
      $this->titles        = [
         __('Actor'),
         __('Title'),
         __('Item type'),
         __('Item id'),
         __('Begin date'),
         __('End date')
      ];
   }

   /**
    * Build CSV lines
    *
    * @return void
    */
   private function buildLines() {
      global $CFG_GLPI;

      $interv = [];
      $this->lines = [];
      $begin  = time()-MONTH_TIMESTAMP*12;
      $end    = time()+MONTH_TIMESTAMP*12;
      $begin  = date("Y-m-d H:i:s", $begin);
      $end    = date("Y-m-d H:i:s", $end);
      $params = [
         'who'       => $this->users_id,
         'whogroup'  => $this->groups_id,
         'begin'     => $begin,
         'end'       => $end
      ];

      if (empty($this->limititemtype)) {
         foreach ($CFG_GLPI['planning_types'] as $itemtype) {
            $interv = array_merge($interv, $itemtype::populatePlanning($params));
         }
      } else {
         $interv = $this->limititemtype::populatePlanning($params);
      }

      if (count($interv) > 0) {
         foreach ($interv as $val) {
            $dateBegin = new DateTime($val["begin"]);
            $dateBegin->setTimeZone(new DateTimeZone('UTC'));

            $dateEnd = new DateTime($val["end"]);
            $dateEnd->setTimeZone(new DateTimeZone('UTC'));

            $itemtype = new $val['itemtype'];

            $user = new User();
            $user->getFromDB($val['users_id']);

            //(acteur;titre item;id item;date-heure début,date-heure fin;catégorie)
            $this->lines[] = [
               'actor'     => $user->getFriendlyName(),
               'title'     => $val['name'],
               'itemtype'  => $itemtype->getTypeName(1),
               'items_id'  => $val[$itemtype->getForeignKeyField()],
               'begindate' => $dateBegin->format('Y-m-d H:i:s'),
               'enddate'   => $dateEnd->format('Y-m-d H:i:s')
            ];
         }
      }
   }

   /**
    * Outputs CSV export
    *
    * @param boolean $text Outputs text only, without headers
    */
   public function output($text = false) {
      $this->getlines();

      if ($text === false) {
         header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
         header('Pragma: private'); /// IE BUG + SSL
         header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
         header("Content-disposition: filename=".$this->filename);
         header('Content-type: application/octetstream');
         // zero width no break space (for excel)
      }

      echo implode(
         $_SESSION["glpicsv_delimiter"],
         array_map(function ($value) { return $this->quote($value); }, $this->titles)
      ) . $this->eol;
      foreach ($this->lines as $line) {
         echo implode(
            $_SESSION["glpicsv_delimiter"],
            array_map(function ($value) { return $this->quote($value); }, $line)
         ) . $this->eol;
      }
   }

   /**
    * Get lines
    *
    * @return array
    */
   public function getlines() {
      if ($this->lines === null) {
         $this->buildLines();
      }
      return $this->lines;
   }

   /**
    * Quote value for CSV
    *
    * @param string $value Value to quote
    *
    * @return string
    */
   public function quote($value) {
      return $this->quote . str_replace($this->quote, $this->quote.$this->quote, $value) . $this->quote;
   }

   public function __get($name) {
      switch ($name) {
         case 'eol':
         case 'quote':
            return $this->$name;
         case 'lines':
            return $this->getlines();
         default:
            Toolbox::logWarning('Unable to get ' . $name);
      }
   }
}
