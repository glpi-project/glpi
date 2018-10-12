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
 * ChangeValidation class
 */
class ChangeValidation  extends CommonITILValidation {

   // From CommonDBChild
   static public $itemtype           = 'Change';
   static public $items_id           = 'changes_id';

   static $rightname                 = 'changevalidation';

      static function rawSearchOptionsToAdd() {
      $tab = [];
      $tab[] = [
         'id'                 => 'validation',
         'name'               => __('Approval')
      ];
      $tab[] = [
         'id'                 => '52',
         'table'              => getTableForItemtype(static::$itemtype),
         'field'              => 'global_validation',
         'name'               => __('Approval'),
         'searchtype'         => 'equals',
         'datatype'           => 'specific'
      ];
      $tab[] = [
         'id'                 => '53',
         'table'              => static::getTable(),
         'field'              => 'comment_submission',
         'name'               => __('Request comments'),
         'datatype'           => 'text',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];
      $tab[] = [
         'id'                 => '54',
         'table'              => static::getTable(),
         'field'              => 'comment_validation',
         'name'               => __('Approval comments'),
         'datatype'           => 'text',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];
      $tab[] = [
         'id'                 => '55',
         'table'              => static::getTable(),
         'field'              => 'status',
         'datatype'           => 'specific',
         'name'               => __('Approval status'),
         'searchtype'         => 'equals',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];
      $tab[] = [
         'id'                 => '56',
         'table'              => static::getTable(),
         'field'              => 'submission_date',
         'name'               => __('Request date'),
         'datatype'           => 'datetime',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];
      $tab[] = [
         'id'                 => '57',
         'table'              => static::getTable(),
         'field'              => 'validation_date',
         'name'               => __('Approval date'),
         'datatype'           => 'datetime',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];
      $tab[] = [
         'id'                 => '58',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('Requester'),
         'datatype'           => 'itemlink',
         'right'              => (static::$itemtype == 'Ticket' ? 'create_ticket_validate' : 'create_validate'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => static::getTable(),
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];
      $tab[] = [
         'id'                 => '59',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_validate',
         'name'               => __('Approver'),
         'datatype'           => 'itemlink',
         'right'              => (static::$itemtype == 'Ticket' ?
            ['validate_request', 'validate_incident'] :
            'validate'
         ),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => static::getTable(),
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];
      return $tab;
   }

   /**
    * Print the validation list into item
    *
    * @param CommonDBTM $item
   **/
   function showSummary(CommonDBTM $item) {
      global $DB, $CFG_GLPI;
      if (!Session::haveRightsOr(static::$rightname,
                                 array_merge(static::getCreateRights(),
                                             static::getValidateRights(),
                                             static::getPurgeRights()))) {
         return false;
      }
      $tID    = $item->fields['id'];
      $tmp    = [static::$items_id => $tID];
      $canadd = $this->can(-1, CREATE, $tmp);
      $rand   = mt_rand();
      if ($canadd) {
         $itemtype = static::$itemtype;
         echo "<form method='post' name=form action='".$itemtype::getFormURL()."'>";
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";
      echo "<th colspan='3'>".self::getTypeName(Session::getPluralNumber())."</th>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Global approval status')."</td>";
      echo "<td colspan='2'>";
      if (Session::haveRightsOr(static::$rightname, TicketValidation::getValidateRights())) {
         self::dropdownStatus("global_validation",
                              ['value'    => $item->fields["global_validation"]]);
      } else {
         echo TicketValidation::getStatus($item->fields["global_validation"]);
      }
      echo "</td></tr>";
      echo "<tr>";
      echo "<th colspan='2'>"._x('item', 'State')."</th>";
      echo "<th colspan='2'>";
      echo self::getValidationStats($tID);
      echo "</th>";
      echo "</tr>";
      echo "</table>";
      if ($canadd) {
         Html::closeForm();
      }
      echo "<div id='viewvalidation" . $tID . "$rand'></div>\n";
      if ($canadd) {
         echo "<script type='text/javascript' >\n";
         echo "function viewAddValidation" . $tID . "$rand() {\n";
         $params = ['type'             => $this->getType(),
                         'parenttype'       => static::$itemtype,
                         static::$items_id  => $tID,
                         'id'               => -1];
         Ajax::updateItemJsCode("viewvalidation" . $tID . "$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php",
                                $params);
         echo "};";
         echo "</script>\n";
      }
      $iterator = $DB->Request([
         'FROM'   => $this->getTable(),
         'WHERE'  => [static::$items_id => $item->getField('id')],
         'ORDER'  => 'submission_date DESC'
      ]);
      $colonnes = [_x('item', 'State'), __('Request date'), __('Approval requester'),
                     __('Request comments'), __('Approval status'),
                     __('Approver'), __('Approval comments')];
      $nb_colonnes = count($colonnes);
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='noHover'><th colspan='".$nb_colonnes."'>".__('Approvals for the ticket').
           "</th></tr>";
      if ($canadd) {
         if (!in_array($item->fields['status'], array_merge($item->getSolvedStatusArray(),
            $item->getClosedStatusArray()))) {
               echo "<tr class='tab_bg_1 noHover'><td class='center' colspan='" . $nb_colonnes . "'>";
               echo "<a class='vsubmit' href='javascript:viewAddValidation".$tID."$rand();'>";
               echo __('Send an approval request')."</a></td></tr>\n";
         }
      }
      if (count($iterator)) {
         $header = "<tr>";
         foreach ($colonnes as $colonne) {
            $header .= "<th>".$colonne."</th>";
         }
         $header .= "</tr>";
         echo $header;
         Session::initNavigateListItems($this->getType(),
               //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'), $item->getTypeName(1),
                                                $item->fields["name"]));
         while ($row = $iterator->next()) {
            $canedit = $this->canEdit($row["id"]);
            Session::addToNavigateListItems($this->getType(), $row["id"]);
            $bgcolor = self::getStatusColor($row['status']);
            $status  = self::getStatus($row['status']);
            echo "<tr class='tab_bg_1' ".
                   ($canedit ? "style='cursor:pointer' onClick=\"viewEditValidation".
                               $item->fields['id'].$row["id"]."$rand();\""
                             : '') .
                  " id='viewvalidation" . $this->fields[static::$items_id] . $row["id"] . "$rand'>";
            echo "<td>";
            if ($canedit) {
               echo "\n<script type='text/javascript' >\n";
               echo "function viewEditValidation" .$item->fields['id']. $row["id"]. "$rand() {\n";
               $params = ['type'             => $this->getType(),
                               'parenttype'       => static::$itemtype,
                               static::$items_id  => $this->fields[static::$items_id],
                               'id'               => $row["id"]];
               Ajax::updateItemJsCode("viewvalidation" . $item->fields['id'] . "$rand",
                                      $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php",
                                      $params);
               echo "};";
               echo "</script>\n";
            }
            echo "<div style='background-color:".$bgcolor.";'>".$status."</div></td>";
            echo "<td>".Html::convDateTime($row["submission_date"])."</td>";
            echo "<td>".getUserName($row["users_id"])."</td>";
            echo "<td>".$row["comment_submission"]."</td>";
            echo "<td>".Html::convDateTime($row["validation_date"])."</td>";
            echo "<td>".getUserName($row["users_id_validate"])."</td>";
            echo "<td>".$row["comment_validation"]."</td>";
            echo "</tr>";
         }
         echo $header;
      } else {
         //echo "<div class='center b'>".__('No item found')."</div>";
         echo "<tr class='tab_bg_1 noHover'><th colspan='" . $nb_colonnes . "'>";
         echo __('No item found')."</th></tr>\n";
      }
      echo "</table>";
   }

   function post_addItem() {
        global $CFG_GLPI;

	$change = new static::$itemtype();
        $change->getFromDB($this->fields['changes_id']);
        if ($change->isStatusExists(Change::EVALUATION) && (($change->fields["status"] == Change::INCOMING) || ($change->fields["status"] == Change::EVALUATION))) {
               $input = [
                  'id'            => $change->getID(),
                  'status'        => Change::APPROVAL,
		  'changeisnotupdate' => 1
               ];
               $change->update($input);
        }
	parent::post_addItem();
   }

   function post_updateItem($history = 1) {
     global $CFG_GLPI;
     parent::post_updateItem($history);

     $change = new static::$itemtype();
     $change->getFromDB($this->fields['changes_id']);

     if ($this->fields["status"] == self::WAITING) {
	$input = [
                  'id'            => $change->getID(),
                  'status'        => Change::APPROVAL,
                  'changeisnotupdate' => 1
               ];
        $change->update($input);
     }
     if ($this->fields["status"] == self::ACCEPTED) {
        $input = [
                  'id'            => $change->getID(),
                  'status'        => Change::ACCEPTED,
                  'changeisnotupdate' => 1
               ];
        $change->update($input);
     }
     if ($this->fields["status"] == self::REFUSED) {
        $input = [
                  'id'            => $change->getID(),
                  'status'        => Change::CLOSED,
                  'changeisnotupdate' => 1
               ];
        $change->update($input);
     }
   }
}
