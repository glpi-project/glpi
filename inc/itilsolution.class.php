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
 * ITILSolution Class
**/
class ITILSolution extends CommonDBChild {

   // From CommonDBTM
   public $dohistory                   = true;
   private $item                       = null;

   static public $itemtype = 'itemtype'; // Class name or field name (start with itemtype) for link to Parent
   static public $items_id = 'items_id'; // Field name

   static function getTypeName($nb = 0) {
      return _n('Solution', 'Solutions', $nb);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->isNewItem()) {
         return;
      }
      if ($item->maySolve()) {
         $nb    = 0;
         $title = self::getTypeName(Session::getPluralNumber());
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = self::countFor($item->getType(), $item->getID());
         }
         return self::createTabEntry($title, $nb);
      }
   }

   static function canView() {
      return Session::haveRight('ticket', READ)
             || Session::haveRight('change', READ)
             || Session::haveRight('problem', READ);
   }

   public static function canUpdate() {
      //always true, will rely on ITILSolution::canUpdateItem
      return true;
   }

   public function canUpdateItem() {
      return $this->item->maySolve();
   }

   public static function canCreate() {
      //always true, will rely on ITILSolution::canCreateItem
      return true;
   }

   public function canCreateItem() {
      $item = new $this->fields['itemtype'];
      $item->getFromDB($this->fields['items_id']);
      return $item->canSolve();
   }

   function canEdit($ID) {
      return $this->item->maySolve();
   }

   function post_getFromDB() {
      $this->item = new $this->fields['itemtype'];
      $this->item->getFromDB($this->fields['items_id']);
   }

   /**
    * Print the phone form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - item: CommonITILObject instance
    *     - kb_id_toload: load new item content from KB entry
    *
    * @return boolean item found
   **/
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      if ($this->isNewItem()) {
         $this->getEmpty();
      }

      if (!isset($options['item']) && isset($options['parent'])) {
         //when we came from aja/viewsubitem.php
         $options['item'] = $options['parent'];
      }

      $item = $options['item'];
      $this->item = $item;
      $item->check($item->getID(), READ);

      if ($item instanceof Ticket && $this->isNewItem()) {
         $ti = new Ticket_Ticket();
         $open_child = $ti->countOpenChildren($item->getID());
         if ($open_child > 0) {
            echo "<div class='tab_cadre_fixe warning'>" . __('Warning: non closed children tickets depends on current ticket. Are you sure you want to close it?')  . "</div>";
         }
      }

      $canedit = $item->maySolve();

      if (isset($options['kb_id_toload']) && $options['kb_id_toload'] > 0) {
         $kb = new KnowbaseItem();
         if ($kb->getFromDB($options['kb_id_toload'])) {
            $this->fields['content'] = $kb->getField('answer');
         }
      }

      // Alert if validation waiting
      $validationtype = $item->getType().'Validation';
      if (method_exists($validationtype, 'alertValidation') && $this->isNewItem()) {
         $validationtype::alertValidation($item, 'solution');
      }

      if (!isset($options['noform'])) {
         $this->showFormHeader($options);
      }

      $show_template = $canedit;
      $rand_template = mt_rand();
      $rand_text     = $rand_type = 0;
      if ($canedit) {
         $rand_text = mt_rand();
         $rand_type = mt_rand();
      }
      if ($show_template) {
         echo "<tr class='tab_bg_2'>";
         echo "<td>"._n('Solution template', 'Solution templates', 1)."</td><td>";

         $entity = isset($options['entities_id']) ? $options['entities_id'] : $this->getEntityID();

         SolutionTemplate::dropdown([
            'value'    => 0,
            'entity'   => $entity,
            'rand'     => $rand_template,
            // Load type and solution from bookmark
            'toupdate' => [
               'value_fieldname' => 'value',
               'to_update'       => 'solution'.$rand_text,
               'url'             => $CFG_GLPI["root_doc"]. "/ajax/solution.php",
               'moreparams' => [
                  'type_id' => 'dropdown_solutiontypes_id'.$rand_type
               ]
            ]
         ]);

         echo "</td><td colspan='2'>";
         if (Session::haveRightsOr('knowbase', [READ, KnowbaseItem::READFAQ])) {
            echo "<a class='vsubmit' title=\"".__s('Search a solution')."\"
                   href='".$CFG_GLPI['root_doc']."/front/knowbaseitem.php?item_itemtype=".
                   $item->getType()."&amp;item_items_id=".$item->getID().
                   "&amp;forcetab=Knowbase$1'>".__('Search a solution')."</a>";
         }
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Solution type')."</td><td>";

      echo Html::hidden('itemtype', ['value' => $item->getType()]);
      echo Html::hidden('items_id', ['value' => $item->getID()]);
      echo html::hidden('_no_message_link', ['value' => 1]);

      // Settings a solution will set status to solved
      if ($canedit) {
         SolutionType::dropdown(['value'  => $this->getField('solutiontypes_id'),
                                 'rand'   => $rand_type,
                                 'entity' => $this->getEntityID()]);
      } else {
         echo Dropdown::getDropdownName('glpi_solutiontypes',
                                        $this->getField('solutiontypes_id'));
      }
      echo "</td><td colspan='2'>";

      if (Session::haveRightsOr('knowbase', [READ, KnowbaseItem::READFAQ]) && isset($options['kb_id_toload']) && $options['kb_id_toload'] != 0) {
         echo '<br/><input type="checkbox" name="kb_linked_id" id="kb_linked_id" value="' . $kb->getID() . '" checked="checked">';
         echo ' <label for="kb_linked_id">' . str_replace('%id', $kb->getID(), __('Link to knowledge base entry #%id')) . '</label>';
      } else {
         echo '&nbsp;';
      }
      echo "</td></tr>";
      if ($canedit && Session::haveRight('knowbase', UPDATE) && !isset($options['nokb'])) {
         echo "<tr class='tab_bg_2'><td>".__('Save and add to the knowledge base')."</td><td>";
         Dropdown::showYesNo('_sol_to_kb', false);
         echo "</td><td colspan='2'>&nbsp;</td></tr>";
      }
      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Description')."</td><td colspan='3'>";

      if ($canedit) {
         $rand = mt_rand();
         Html::initEditorSystem("content$rand");

         echo "<div id='solution$rand_text'>";
         echo "<textarea id='content$rand' name='content' rows='12' cols='80'>".
                $this->getField('content')."</textarea></div>";

         // Hide file input to handle only images pasted in text editor
         echo '<div style="display:none;">';
         Html::file(['editor_id' => "content$rand",
                     'filecontainer' => "filecontainer$rand",
                     'onlyimages' => true,
                     'showtitle' => false,
                     'multiple' => true]);
         echo '</div>';
      } else {
         echo Toolbox::unclean_cross_side_scripting_deep($this->getField('content'));
      }
      echo "</td></tr>";

      if (!isset($options['noform'])) {
         $options['candel']   = false;
         $options['canedit']  = $canedit;
         $this->showFormButtons($options);
      }
   }


   /**
    * Count solutions for specific item
    *
    * @param string  $itemtype Item type
    * @param integer $items_id Item ID
    *
    * @return integer
    */
   public static function countFor($itemtype, $items_id) {
      return countElementsInTable(
         self::getTable(), [
            'WHERE' => [
               'itemtype'  => $itemtype,
               'items_id'  => $items_id
            ]
         ]
      );
   }

   function prepareInputForAdd($input) {
      $input['users_id'] = Session::getLoginUserID();

      if ($this->item == null) {
         $this->item = new $input['itemtype'];
         $this->item->getFromDB($input['items_id']);
      }

      // check itil object is not already solved
      if (in_array($this->item->fields["status"], $this->item->getSolvedStatusArray())) {
         Session::addMessageAfterRedirect(__("The item is already solved, did anyone pushed a solution before you ?"),
                                          false, ERROR);
         return false;
      }

      //default status for global solutions
      $status = CommonITILValidation::ACCEPTED;

      //handle autoclose, for tickets only
      if ($input['itemtype'] == Ticket::getType()) {
         $autoclosedelay =  Entity::getUsedConfig(
            'autoclose_delay',
            $this->item->getEntityID(),
            '',
            Entity::CONFIG_NEVER
         );

         // 0 = immediatly
         if ($autoclosedelay != 0) {
            $status = CommonITILValidation::WAITING;
         }
      }

      //Accepted; store user and date
      if ($status == CommonITILValidation::ACCEPTED) {
         $input['users_id_approval'] = Session::getLoginUserID();
         $input['date_approval'] = $_SESSION["glpi_currenttime"];
      }

      $input['status'] = $status;

      return $input;
   }

   function post_addItem() {

      // Replace inline pictures
      $this->input = $this->addFiles($this->input, ['force_update' => true]);

      //adding a solution mean the ITIL object is now solved
      //and maybe closed (according to entitiy configuration)
      if ($this->item == null) {
         $this->item = new $this->fields['itemtype'];
         $this->item->getFromDB($this->fields['items_id']);
      }

      $item = $this->item;
      $status = $item::SOLVED;

      //handle autoclose, for tickets only
      if ($item->getType() == Ticket::getType()) {
         $autoclosedelay =  Entity::getUsedConfig(
            'autoclose_delay',
            $this->item->getEntityID(),
            '',
            Entity::CONFIG_NEVER
         );

         // 0 = immediatly
         if ($autoclosedelay == 0) {
            $status = $item::CLOSED;
         }
      }

      $this->item->update([
         'id'     => $this->item->getID(),
         'status' => $status
      ]);
      if ($this->item->getType() == 'Ticket' && !isset($this->input['_linked_ticket'])) {
         Ticket_Ticket::manageLinkedTicketsOnSolved($this->item->getID(), $this);
      }
      parent::post_addItem();
   }


   /**
    * @see CommonDBTM::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      // Replace inline pictures
      $input = $this->addFiles($input);

      return $input;
   }


   /**
    * {@inheritDoc}
    * @see CommonDBTM::getSpecificValueToDisplay()
    */
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'status':
            $value = $values[$field];
            $statuses = self::getStatuses();

            return (isset($statuses[$value]) ? $statuses[$value] : $value);
            break;
      }

      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   /**
    * {@inheritDoc}
    * @see CommonDBTM::getSpecificValueToSelect()
    */
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'status':
            $options['display'] = false;
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, self::getStatuses(), $options);
            break;
      }

      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * Return list of statuses.
    * Key as status values, values as labels.
    *
    * @return string[]
    */
   static function getStatuses() {
      return [
         CommonITILValidation::WAITING  => __('Waiting for approval'),
         CommonITILValidation::REFUSED  => __('Refused'),
         CommonITILValidation::ACCEPTED => __('Accepted'),
      ];
   }
}
