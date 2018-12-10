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
 * Relation between Itil items and Projects
 *
 * @since 9.4.0
**/
class Itil_Project extends CommonDBRelation {

   static public $itemtype_1 = 'itemtype';
   static public $items_id_1 = 'items_id';
   static public $itemtype_2 = 'Project';
   static public $items_id_2 = 'projects_id';

   static function getTypeName($nb = 0) {

      return _n('Link Project/Itil', 'Links Project/Itil', $nb);
   }

   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $label = '';

      if (static::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case Change::class :
            case Problem::class :
            case Ticket::class :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable(
                     self::getTable(),
                     [
                        'itemtype' => $item->getType(),
                        'items_id' => $item->getID(),
                     ]
                  );
               }
               $label = self::createTabEntry(Project::getTypeName(Session::getPluralNumber()), $nb);
               break;

            case Project::class :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable(self::getTable(), ['projects_id' => $item->getID()]);
               }
               $label = self::createTabEntry(
                  _n('Itil item', 'Itil items', Session::getPluralNumber()),
                  $nb
               );
               break;

         }
      }

      return $label;
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case Change::class :
         case Problem::class :
         case Ticket::class :
            self::showForItil($item);
            break;

         case Project::class :
            self::showForProject($item);
            break;
      }
      return true;
   }


   /**
    * Show ITIL items for a project.
    *
    * @param Project $project
    * @return void
    **/
   static function showForProject(Project $project) {
      global $DB;

      $ID = $project->getField('id');
      if (!$project->can($ID, READ)) {
         return false;
      }

      $canedit = $project->canEdit($ID);

      /** @var CommonITILObject $itemtype */
      foreach ([Change::class, Problem::class, Ticket::class] as $itemtype) {
         $rand    = mt_rand();

         $selfTable = self::getTable();
         $itemTable = $itemtype::getTable();

         $iterator = $DB->request([
            'SELECT DISTINCT' => "$selfTable.id AS linkID",
            'FIELDS'          => "$itemTable.*",
            'FROM'            => $selfTable,
            'LEFT JOIN'       => [
               $itemTable => [
                  'FKEY' => [
                     $selfTable => 'items_id',
                     $itemTable => 'id',
                  ],
               ],
            ],
            'WHERE'           => [
               "{$selfTable}.itemtype"    => $itemtype,
               "{$selfTable}.projects_id" => $ID,
               'NOT'                      => ["{$itemTable}.id" => null],
            ],
            'ORDER'  => "{$itemTable}.name",
         ]);

         $numrows = $iterator->count();

         $items = [];
         $used  = [];
         while ($data = $iterator->next()) {
            $items[$data['id']] = $data;
            $used[$data['id']]  = $data['id'];
         }
         if ($canedit) {
            echo '<div class="firstbloc">';
            $formId = 'itilproject_' . strtolower($itemtype) . '_form' . $rand;
            echo '<form name="' . $formId .'"
                        id="' . $formId . '"
                        method="post"
                        action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '">';
            echo '<table class="tab_cadre_fixe">';

            $label = null;
            switch ($itemtype) {
               case Change::class :
                  $label = __('Add a change');
                  break;
               case Problem::class :
                  $label = __('Add a problem');
                  break;
               case Ticket::class :
                  $label = __('Add a ticket');
                  break;
            }
            echo '<tr class="tab_bg_2"><th colspan="2">' . $label . '</th></tr>';
            echo '<tr class="tab_bg_2">';
            echo '<td>';
            echo '<input type="hidden" name="projects_id" value="' . $ID . '" />';
            echo '<input type="hidden" name="itemtype" value="' . $itemtype . '" />';
            $itemtype::dropdown(
               [
                  'entity'      => $project->getEntityID(),
                  'entity_sons' => $project->isRecursive(),
                  'name'        => 'items_id',
                  'used'        => $used,
               ]
            );
            echo '</td>';
            echo '<td class="center">';
            echo '<input type="submit" name="add" value="' . _sx('button', 'Add') . '" class="submit" />';
            echo '</td>';
            echo '</tr>';
            echo '</table>';
            Html::closeForm();
            echo '</div>';
         }

         echo '<div class="spaced">';
         $massContainerId = 'mass' . __CLASS__ . $rand;
         if ($canedit && $numrows) {
            Html::openMassiveActionsForm($massContainerId);
            $massiveactionparams = [
               'num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
               'container'     => $massContainerId,
            ];
            Html::showMassiveActions($massiveactionparams);
         }

         echo '<table class="tab_cadre_fixehov">';
         echo '<tr class="noHover">';
         echo '<th colspan="12">' . $itemtype::getTypeName($numrows) . '</th>';
         echo '</tr>';
         if ($numrows) {
            $itemtype::commonListHeader(Search::HTML_OUTPUT, $massContainerId);
            Session::initNavigateListItems(
               $itemtype,
               //TRANS : %1$s is the itemtype name,
               //        %2$s is the name of the item (used for headings of a list)
               sprintf(__('%1$s = %2$s'), Project::getTypeName(1), $project->fields['name'])
            );

            $i = 0;
            foreach ($items as $data) {
               Session::addToNavigateListItems($itemtype, $data['id']);
               $itemtype::showShort(
                  $data['id'],
                  [
                     'row_num'                => $i,
                     'type_for_massiveaction' => __CLASS__,
                     'id_for_massiveaction'   => $data['linkID']
                  ]
               );
               $i++;
            }
            $itemtype::commonListHeader(Search::HTML_OUTPUT, $massContainerId);
         }
         echo '</table>';

         if ($canedit && $numrows) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }
         echo '</div>';
      }
   }

   /**
    * Show projects for an ITIL item.
    *
    * @param CommonITILObject $itil
    * @return void
   **/
   static function showForItil(CommonITILObject $itil) {
      global $DB;

      $ID = $itil->getField('id');
      if (!$itil->can($ID, READ)) {
         return false;
      }

      $canedit = $itil->canEdit($ID);
      $rand    = mt_rand();

      $selfTable = self::getTable();
      $projectTable = Project::getTable();

      $iterator = $DB->request([
         'SELECT DISTINCT' => "$selfTable.id AS linkID",
         'FIELDS'          => "$projectTable.*",
         'FROM'            => $selfTable,
         'LEFT JOIN'       => [
            $projectTable => [
               'FKEY' => [
                  $selfTable    => 'projects_id',
                  $projectTable => 'id',
               ],
            ],
         ],
         'WHERE'           => [
            "{$selfTable}.itemtype" => $itil->getType(),
            "{$selfTable}.items_id" => $ID,
            'NOT'                   => ["{$projectTable}.id" => null],
         ],
         'ORDER'  => "{$projectTable}.name",
      ]);

      $numrows = $iterator->count();

      $projects = [];
      $used     = [];
      while ($data = $iterator->next()) {
         $projects[$data['id']] = $data;
         $used[$data['id']]     = $data['id'];
      }

      if ($canedit) {
         echo '<div class="firstbloc">';
         $formId = 'itilproject_form' . $rand;
         echo '<form name="' . $formId .'"
                     id="' . $formId . '"
                     method="post"
                     action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '">';
         echo '<table class="tab_cadre_fixe">';
         echo '<tr class="tab_bg_2"><th colspan="2">' . __('Add a project') . '</th></tr>';
         echo '<tr class="tab_bg_2">';
         echo '<td>';
         echo '<input type="hidden" name="itemtype" value="' . $itil->getType() . '" />';
         echo '<input type="hidden" name="items_id" value="' . $ID . '" />';
         Project::dropdown(
            [
               'used'   => $used,
               'entity' => $itil->getEntityID()
            ]
         );
         echo '</td>';
         echo '<td class="center">';
         echo '<input type="submit" name="add" value=" ' . _sx('button', 'Add') . '" class="submit" />';
         echo '</td>';
         echo '</tr>';
         echo '</table>';
         Html::closeForm();
         echo '</div>';
      }

      echo '<div class="spaced">';
      $massContainerId = 'mass' . __CLASS__ . $rand;
      if ($canedit && $numrows) {
         Html::openMassiveActionsForm($massContainerId);
         $massiveactionparams = [
            'num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
            'container'     => $massContainerId,
         ];
         Html::showMassiveActions($massiveactionparams);
      }

      echo '<table class="tab_cadre_fixehov">';
      echo '<tr class="noHover">';
      echo '<th colspan="12">' . Project::getTypeName($numrows) . '</th>';
      echo '</tr>';
      if ($numrows) {
         Project::commonListHeader(Search::HTML_OUTPUT, $massContainerId);
         Session::initNavigateListItems(
            Project::class,
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(__('%1$s = %2$s'), $itil::getTypeName(1), $itil->fields['name'])
         );

         $i = 0;
         foreach ($projects as $data) {
            Session::addToNavigateListItems(Project::class, $data['id']);
            Project::showShort(
               $data['id'],
               [
                  'row_num'               => $i,
                  'type_for_massiveaction' => __CLASS__,
                  'id_for_massiveaction'   => $data['linkID']
               ]
            );
            $i++;
         }
         Project::commonListHeader(Search::HTML_OUTPUT, $massContainerId);
      }
      echo '</table>';

      if ($canedit && $numrows) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo '</div>';
   }

   /**
    * Duplicate all itil items from a project template to his clone.
    *
    * @param integer $oldid  ID of the item to clone
    * @param integer $newid  ID of the item cloned
    *
    * @return void
    **/
   static function cloneItilProject($oldid, $newid) {

      global $DB;

      $itil_items = $DB->request(self::getTable(), ['WHERE'  => "`projects_id` = '$oldid'"]);
      foreach ($itil_items as $data) {
         unset($data['id']);
         $data['projects_id'] = $newid;
         $data                = Toolbox::addslashes_deep($data);

         $itil_project = new Itil_Project();
         $itil_project->add($data);
      }
   }
}
