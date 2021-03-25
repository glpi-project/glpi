<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\Toolbox\RichText;

/**
 * ITILSolution Class
**/
class ITILSolution extends CommonDBChild {
   use \Glpi\Features\UserMention;

   // From CommonDBTM
   public $dohistory                   = true;
   private $item                       = null;

   static public $itemtype = 'itemtype'; // Class name or field name (start with itemtype) for link to Parent
   static public $items_id = 'items_id'; // Field name

   public static function getNameField() {
      return 'id';
   }

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

      TemplateRenderer::getInstance()->display('components/itilobject/form_solution.html.twig', [
         'item'      => $options['parent'],
         'subitem'   => $this
      ]);
      return;

      //TODO Legacy form rendering kept for reference only. Remove when twig template is complete.

      if (!isset($options['item']) && isset($options['parent'])) {
         //when we came from aja/viewsubitem.php
         $options['item'] = $options['parent'];
      }
      $options['formoptions'] = ($options['formoptions'] ?? '') . ' data-track-changes=true';

      $item = $options['item'];
      $this->item = $item;
      $item->check($item->getID(), READ);

      $entities_id = isset($options['entities_id']) ? $options['entities_id'] : $item->getEntityID();

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

      $rand = mt_rand();
      $content_id = "content$rand";

      if ($canedit) {
         echo "<tr class='tab_bg_2'>";
         echo "<td>"._n('Solution template', 'Solution templates', 1)."</td><td>";

         SolutionTemplate::dropdown([
            'value'    => 0,
            'entity'   => $entities_id,
            'rand'     => $rand,
            'on_change' => "solutiontemplate_update{$rand}(this.value)"
         ]);

         $JS = <<<JAVASCRIPT
            function solutiontemplate_update{$rand}(value) {
               $.ajax({
                  url: '{$CFG_GLPI['root_doc']}/ajax/solution.php',
                  type: 'POST',
                  data: {
                     solutiontemplates_id: value
                  }
               }).done(function(data) {
                  tinymce.get("{$content_id}").setContent(data.content);

                  var solutiontypes_id = isNaN(parseInt(data.solutiontypes_id))
                     ? 0
                     : parseInt(data.solutiontypes_id);
                  $("#dropdown_solutiontypes_id{$rand}").trigger("setValue", solutiontypes_id);
               });
            }
JAVASCRIPT;
         echo Html::scriptBlock($JS);

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
      echo "<td>".SolutionType::getTypeName(1)."</td><td>";

      echo Html::hidden('itemtype', ['value' => $item->getType()]);
      echo Html::hidden('items_id', ['value' => $item->getID()]);
      echo Html::hidden('_no_message_link', ['value' => 1]);

      // Settings a solution will set status to solved
      if ($canedit) {
         SolutionType::dropdown(['value'  => $this->getField('solutiontypes_id'),
                                 'rand'   => $rand,
                                 'entity' => $entities_id]);
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
         Html::textarea(['name'              => 'content',
                         'value'             => RichText::getSafeHtml($this->fields['content'], true, true),
                         'rand'              => $rand,
                         'editor_id'         => $content_id,
                         'enable_fileupload' => false,
                         'enable_richtext'   => true,
                         'cols'              => 12,
                         'rows'              => 80]);
         Html::activateUserMentions($content_id);
      } else {
         echo '<div class="rich_text_container">';
         echo RichText::getSafeHtml($this->fields['content'], true);
         echo '</div>';
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
      if (!isset($input['users_id']) && (Session::isCron() || strpos($_SERVER['REQUEST_URI'], 'crontask.form.php') !== false)) {
         $input['users_id'] = Session::getLoginUserID();
      }

      if ($this->item == null
         || (isset($input['itemtype']) && isset($input['items_id']))
      ) {
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

      //adding a solution mean the ITIL object is now solved
      //and maybe closed (according to entitiy configuration)
      if ($this->item == null) {
         $this->item = new $this->fields['itemtype'];
         $this->item->getFromDB($this->fields['items_id']);
      }

      $item = $this->item;

      // Replace inline pictures
      $this->input["_job"] = $this->item;
      $this->input = $this->addFiles(
         $this->input, [
            'force_update' => true,
            'name' => 'content',
            'content_field' => 'content',
         ]
      );

      // Add solution to duplicates
      if ($this->item->getType() == 'Ticket' && !isset($this->input['_linked_ticket'])) {
         Ticket_Ticket::manageLinkedTicketsOnSolved($this->item->getID(), $this);
      }

      if (!isset($this->input['_linked_ticket'])) {
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
      }

      parent::post_addItem();
   }

   function prepareInputForUpdate($input) {

      if (!isset($this->fields['itemtype'])) {
         return false;
      }
      $input["_job"] = new $this->fields['itemtype']();
      if (!$input["_job"]->getFromDB($this->fields["items_id"])) {
         return false;
      }

      return $input;
   }

   function post_updateItem($history = 1) {
      // Replace inline pictures
      $options = [
         'force_update' => true,
         'name' => 'content',
         'content_field' => 'content',
      ];
      $this->input = $this->addFiles($this->input, $options);

      parent::post_updateItem($history);
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
         CommonITILValidation::REFUSED  => _x('solution', 'Refused'),
         CommonITILValidation::ACCEPTED => __('Accepted'),
      ];
   }
}
