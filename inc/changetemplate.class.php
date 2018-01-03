<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Change Template class
 *
 * since version 9.3
**/
class ChangeTemplate extends CommonDropdown {

   // From CommonDBTM
   public $dohistory                 = true;

   // From CommonDropdown
   public $first_level_menu          = "helpdesk";
   public $second_level_menu         = "change";
   public $third_level_menu          = "ChangeTemplate";

   public $display_dropdowntitle     = false;

   static $rightname                 = 'changetemplate';

   public $can_be_translated            = false;

   // Specific fields
   /// Mandatory Fields
   public $mandatory  = [];
   /// Hidden fields
   public $hidden     = [];
   /// Predefined fields
   public $predefined = [];


   /**
    * Retrieve an item from the database with additional datas
    *
    * @since version 9.3
    *
    * @param $ID                    integer  ID of the item to get
    * @param $withtypeandcategory   boolean  with type and category (true by default)
    *
    * @return true if succeed else false
   **/
   function getFromDBWithDatas($ID, $withtypeandcategory = true) {
      global $DB;

      if ($this->getFromDB($ID)) {
         $change       = new Change();
         $cth          = new ChangeTemplateHiddenField();
         $this->hidden = $cth->getHiddenFields($ID, $withtypeandcategory);

         // Force items_id if itemtype is defined
         if (isset($this->hidden['itemtype'])
             && !isset($this->hidden['items_id'])) {
            $this->hidden['items_id'] = $change->getSearchOptionIDByField('field', 'items_id',
                                                                          'glpi_changes_items');
         }
         // Always get all mandatory fields
         $ctm             = new ChangeTemplateMandatoryField();
         $this->mandatory = $ctm->getMandatoryFields($ID);

         // Force items_id if itemtype is defined
         if (isset($this->mandatory['itemtype'])
             && !isset($this->mandatory['items_id'])) {
            $this->mandatory['items_id'] = $change->getSearchOptionIDByField('field', 'items_id',
                                                                             'glpi_changes_items');
         }

         $ctp              = new ChangeTemplatePredefinedField();
         $this->predefined = $ctp->getPredefinedFields($ID, $withtypeandcategory);
         // Compute time_to_resolve
         if (isset($this->predefined['time_to_resolve'])) {
            $this->predefined['time_to_resolve']
                        = Html::computeGenericDateTimeSearch($this->predefined['time_to_resolve'], false);
         }

         // Compute internal_time_to_resolve
         if (isset($this->predefined['internal_time_to_resolve'])) {
            $this->predefined['internal_time_to_resolve']
               = Html::computeGenericDateTimeSearch($this->predefined['internal_time_to_resolve'], false);
         }

         // Compute date
         if (isset($this->predefined['date'])) {
            $this->predefined['date']
                        = Html::computeGenericDateTimeSearch($this->predefined['date'], false);
         }
         return true;
      }
      return false;
   }


   static function getTypeName($nb = 0) {
      return _n('Change template', 'Change templates', $nb);
   }


   /**
    * @param $withtypeandcategory   (default 0)
    * @param $withitemtype         (default 0)
   **/
   static function getAllowedFields($withtypeandcategory = 0, $withitemtype = 0) {

      static $allowed_fields = [];

      // For integer value for index
      if ($withtypeandcategory) {
         $withtypeandcategory = 1;
      } else {
         $withtypeandcategory = 0;
      }

      if ($withitemtype) {
         $withitemtype = 1;
      } else {
         $withitemtype = 0;
      }

      if (!isset($allowed_fields[$withtypeandcategory][$withitemtype])) {
         $change = new Change();

         // SearchOption ID => name used for options
         $allowed_fields[$withtypeandcategory][$withitemtype]
             = [$change->getSearchOptionIDByField('field', 'name',
                                                       'glpi_changes')   => 'name',
                     $change->getSearchOptionIDByField('field', 'content',
                                                       'glpi_changes')   => 'content',
                     $change->getSearchOptionIDByField('field', 'status',
                                                       'glpi_changes')   => 'status',
                     $change->getSearchOptionIDByField('field', 'urgency',
                                                       'glpi_changes')   => 'urgency',
                     $change->getSearchOptionIDByField('field', 'impact',
                                                       'glpi_changes')   => 'impact',
                     $change->getSearchOptionIDByField('field', 'priority',
                                                       'glpi_changes')   => 'priority',
                     $change->getSearchOptionIDByField('field', 'time_to_resolve',
                                                       'glpi_changes')   => 'time_to_resolve',
                     $change->getSearchOptionIDByField('field', 'date',
                                                       'glpi_changes')   => 'date',
                     $change->getSearchOptionIDByField('field', 'actiontime',
                                                       'glpi_changes')   => 'actiontime',
                     $change->getSearchOptionIDByField('field', 'global_validation',
                                                       'glpi_changes')   => 'global_validation',

                                                       4                 => '_users_id_requester',
                                                       71                => '_groups_id_requester',
                                                       5                 => '_users_id_assign',
                                                       8                 => '_groups_id_assign',
                     $change->getSearchOptionIDByField('field', 'name',
                                                       'glpi_suppliers') => '_suppliers_id_assign',

                                                       66                => '_users_id_observer',
                                                       65                => '_groups_id_observer',
             ];

         if ($withtypeandcategory) {
            $allowed_fields[$withtypeandcategory][$withitemtype]
               [$change->getSearchOptionIDByField('field', 'completename',
                                                  'glpi_itilcategories')]  = 'itilcategories_id';
         }

         if ($withitemtype) {
            $allowed_fields[$withtypeandcategory][$withitemtype]
               [$change->getSearchOptionIDByField('field', 'itemtype',
                                                  'glpi_changes_items')] = 'itemtype';
         }

         $allowed_fields[$withtypeandcategory][$withitemtype]
            [$change->getSearchOptionIDByField('field', 'items_id',
                                               'glpi_changes_items')] = 'items_id';

         // Add validation request
         $allowed_fields[$withtypeandcategory][$withitemtype][-2] = '_add_validation';
      }

      return $allowed_fields[$withtypeandcategory][$withitemtype];
   }


   /**
    * @param $withtypeandcategory   (default 0)
    * @param $with_items_id         (default 0)
   **/
   function getAllowedFieldsNames($withtypeandcategory = 0, $with_items_id = 0) {

      $searchOption = Search::getOptions('Change');
      $tab          = $this->getAllowedFields($withtypeandcategory, $with_items_id);
      foreach ($tab as $ID => $shortname) {
         switch ($ID) {
            case -2 :
               $tab[-2] = __('Approval request');
               break;

            case 175 :
               $tab[175] = CommonITILTask::getTypeName();
               break;

            default :
               if (isset($searchOption[$ID]['name'])) {
                  $tab[$ID] = $searchOption[$ID]['name'];
               }
         }
      }
      return $tab;
   }


   function defineTabs($options = []) {

      $ong          = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('ChangeTemplateMandatoryField', $ong, $options);
      $this->addStandardTab('ChangeTemplatePredefinedField', $ong, $options);
      $this->addStandardTab('ChangeTemplateHiddenField', $ong, $options);
      $this->addStandardTab('ChangeTemplate', $ong, $options);
      $this->addStandardTab('ITILCategory', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'ChangeTemplate' :
            switch ($tabnum) {
               case 1 :
                  $item->showPreview($item);
                  return true;

            }
            break;
      }
      return false;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (Session::haveRight(self::$rightname, READ)) {
         switch ($item->getType()) {
            case 'ChangeTemplate' :
               $ong[1] = __('Change preview');
               return $ong;
         }
      }
      return '';
   }


   /**
    * Get mandatory mark if field is mandatory
    *
    * @since version 9.3
    *
    * @param $field  string   field
    * @param $force  boolean  force display based on global config (false by default)
    *
    * @return string to display
   **/
   function getMandatoryMark($field, $force = false) {

      if ($force || $this->isMandatoryField($field)) {
         return "<span class='required'>*</span>";
      }
      return '';
   }


   /**
    * Get hidden field begin enclosure for text
    *
    * @since version 9.3
    *
    * @param $field string field
    *
    * @return string to display
   **/
   function getBeginHiddenFieldText($field) {

      if ($this->isHiddenField($field) && !$this->isPredefinedField($field)) {
         return "<span id='hiddentext$field' style='display:none'>";
      }
      return '';
   }


   /**
    * Get hidden field end enclosure for text
    *
    * @since version 9.3
    *
    * @param $field string field
    *
    * @return string to display
   **/
   function getEndHiddenFieldText($field) {

      if ($this->isHiddenField($field) && !$this->isPredefinedField($field)) {
         return "</span>";
      }
      return '';
   }


   /**
    * Get hidden field begin enclosure for value
    *
    * @since version 9.3
    *
    * @param $field string field
    *
    * @return string to display
   **/
   function getBeginHiddenFieldValue($field) {

      if ($this->isHiddenField($field)) {
         return "<span id='hiddenvalue$field' style='display:none'>";
      }
      return '';
   }


   /**
    * Get hidden field end enclosure with hidden value
    *
    * @since version 9.3
    *
    * @param $field  string   field
    * @param $change          change object (default NULL)
    *
    * @return string to display
   **/
   function getEndHiddenFieldValue($field, &$change = null) {

      $output = '';
      if ($this->isHiddenField($field)) {
         $output .= "</span>";
         if ($change && isset($change->fields[$field])) {
            $output .= "<input type='hidden' name='$field' value=\"".$change->fields[$field]."\">";
         }
         if ($this->isPredefinedField($field)
             && !is_null($change)) {
            if ($num = array_search($field, $this->getAllowedFields())) {
               $display_options = ['comments' => true,
                                   'html'     => true];
               $output .= $change->getValueToDisplay($num, $change->fields[$field], $display_options);

               /// Display items_id
               if ($field == 'itemtype') {
                  $output .= "<input type='hidden' name='items_id' value=\"".
                               $change->fields['items_id']."\">";
                  if ($num = array_search('items_id', $this->getAllowedFields())) {
                     $output = sprintf(__('%1$s - %2$s'), $output,
                                       $change->getValueToDisplay($num, $change->fields,
                                                                  $display_options));
                  }
               }
            }
         }
      }
      return $output;
   }


   /**
    * Is it an hidden field ?
    *
    * @since version 9.3
    *
    * @param $field string field
    *
    * @return bool
   **/
   function isHiddenField($field) {

      if (isset($this->hidden[$field])) {
         return true;
      }
      return false;
   }


   /**
    * Is it an predefined field ?
    *
    * @since version 9.3
    *
    * @param $field string field
    *
    * @return bool
   **/
   function isPredefinedField($field) {

      if (isset($this->predefined[$field])) {
         return true;
      }
      return false;
   }


   /**
    * Is it an mandatory field ?
    *
    * @since version 9.3
    *
    * @param $field string field
    *
    * @return bool
   **/
   function isMandatoryField($field) {

      if (isset($this->mandatory[$field])) {
         return true;
      }
      return false;
   }


   /**
    * Print preview for Change template
    *
    * @since version 9.3
    *
    * @param $ct ChangeTemplate object
    *
    * @return Nothing (call to classes members)
   **/
   static function showPreview(ChangeTemplate $ct) {

      if (!$ct->getID()) {
         return false;
      }
      if ($ct->getFromDBWithDatas($ct->getID())) {
         $change = new Change();
         $change->showForm(0, ['template_preview' => $ct->getID()]);
      }
   }



   /**
    * @since version 9.3
    *
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin
          &&  $this->maybeRecursive()
          && (count($_SESSION['glpiactiveentities']) > 1)) {
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'merge'] = __('Transfer and merge');
      }

      return $actions;
   }


   /**
    * @since version 9.3
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'merge' :
            echo "&nbsp;".$_SESSION['glpiactive_entity_shortname'];
            echo "<br><br>".Html::submit(_x('button', 'Merge'), ['name' => 'massiveaction']);
            return true;
      }

      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since version 9.3
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'merge' :
            foreach ($ids as $key) {
               if ($item->can($key, UPDATE)) {
                  if ($item->getEntityID() == $_SESSION['glpiactive_entity']) {
                     if ($item->update(['id'           => $key,
                                             'is_recursive' => 1])) {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else {
                     $input2 = $item->fields;
                     // Change entity
                     $input2['entities_id']  = $_SESSION['glpiactive_entity'];
                     $input2['is_recursive'] = 1;
                     $input2 = Toolbox::addslashes_deep($input2);

                     if (!$item->import($input2)) {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                     }
                  }

               } else {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   /**
    * Merge fields linked to template
    *
    * @since version 9.3
    *
    * @param $target_id
    * @param  $source_id
   **/
   function mergeTemplateFields($target_id, $source_id) {
      global $DB;

      // Tables linked to change template
      $to_merge = ['predefinedfields', 'mandatoryfields', 'hiddenfields'];

      // Source fields
      $source = [];
      foreach ($to_merge as $merge) {
         $source[$merge]
            = $this->formatFieldsToMerge(getAllDatasFromTable('glpi_changetemplate'.$merge,
                                                              "changetemplates_id='".$source_id."'"));
      }

      // Target fields
      $target = [];
      foreach ($to_merge as $merge) {
         $target[$merge]
            = $this->formatFieldsToMerge(getAllDatasFromTable('glpi_changetemplate'.$merge,
                                                              "changetemplates_id='".$target_id."'"));
      }

      // Merge
      foreach ($source as $merge => $data) {
         foreach ($data as $key => $val) {
            if (!array_key_exists($key, $target[$merge])) {
               $DB->query("UPDATE `glpi_changetemplate".$merge."`
                           SET `changetemplates_id` = '".$target_id."'
                           WHERE `id` = '".$val['id']."'");
            }
         }
      }
   }


   /**
    * Merge Itilcategories linked to template
    *
    * @since version 9.3
    *
    * @param $target_id
    * @param $source_id
    */
   function mergeTemplateITILCategories($target_id, $source_id) {
      global $DB;

      $to_merge = ['changetemplates_id'];

      // Source categories
      $source = [];
      foreach ($to_merge as $merge) {
         $source[$merge] = getAllDatasFromTable('glpi_itilcategories', "$merge='".$source_id."'");
      }

      // Target categories
      $target = [];
      foreach ($to_merge as $merge) {
         $target[$merge] = getAllDatasFromTable('glpi_itilcategories', "$merge='".$target_id."'");
      }

      // Merge
      $temtplate = new self();
      foreach ($source as $merge => $data) {
         foreach ($data as $key => $val) {
            $temtplate->getFromDB($target_id);
            if (!array_key_exists($key, $target[$merge])
                && in_array($val['entities_id'], $_SESSION['glpiactiveentities'])) {
               $DB->query("UPDATE `glpi_itilcategories`
                           SET `$merge` = '".$target_id."'
                           WHERE `id` = '".$val['id']."'");
            }
         }
      }
   }


   /**
    * Format template fields to merge
    *
    * @since version 9.3
    *
    * @param $data
   **/
   function formatFieldsToMerge($data) {

      $output = [];
      foreach ($data as $val) {
         $output[$val['num']] = $val;
      }

      return $output;
   }


   /**
    * Import a dropdown - check if already exists
    *
    * @since version 9.3
    *
    * @param $input  array of value to import (name, ...)
    *
    * @return the ID of the new or existing dropdown
   **/
   function import(array $input) {

      if (!isset($input['name'])) {
         return -1;
      }
      // Clean datas
      $input['name'] = trim($input['name']);

      if (empty($input['name'])) {
         return -1;
      }

      // Check twin
      $ID = $this->findID($input);
      if ($ID > 0) {
         // Merge data
         $this->mergeTemplateFields($ID, $input['id']);
         $this->mergeTemplateITILCategories($ID, $input['id']);

         // Delete source
         $this->delete($input, 1);

         // Update destination with source input
         $input['id'] = $ID;
      }

      $this->update($input);
      return true;

   }


   /**
    * Forbidden massive action
    *
    * @since version 9.3
    *
    * @see CommonDBTM::getForbiddenStandardMassiveAction()
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'merge';

      return $forbidden;
   }
}
