<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class Contact_Supplier extends CommonDBRelation{

   // From CommonDBRelation
   static public $itemtype_1 = 'Contact';
   static public $items_id_1 = 'contacts_id';

   static public $itemtype_2 = 'Supplier';
   static public $items_id_2 = 'suppliers_id';



   static function getTypeName($nb=0) {
      return _n('Link Contact/Supplier','Links Contact/Supplier',$nb);
   }

   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {
      return parent::getSearchOptions();
   }


   /**
    * @see CommonDBTM::doSpecificMassiveActions()
   **/
   function doSpecificMassiveActions($input=array()) {

      $res = array('ok'      => 0,
                   'ko'      => 0,
                   'noright' => 0);

      switch ($input['action']) {
         case "add_contact_supplier" :
            $contactsupplier = new Contact_Supplier();
            foreach ($input["item"] as $key => $val) {
               if (isset($input['contacts_id'])) {
                  $input = array('suppliers_id' => $key,
                                 'contacts_id'  => $input['contacts_id']);
               } else if (isset($input['suppliers_id'])) {
                  $input = array('suppliers_id' => $input['suppliers_id'],
                                 'contacts_id'  => $key);
               } else {
                  return false;
               }
               if ($contactsupplier->can(-1, 'w', $input)) {
                  if ($contactsupplier->add($input)) {
                     $res['ok']++;
                  } else {
                     $res['ko']++;
                  }
               } else {
                  $res['noright']++;
               }
            }
            break;

         default :
            return parent::doSpecificMassiveActions($input);
      }
      return $res;
   }


   /**
    * @see CommonDBTM::showSpecificMassiveActionsParameters()
   **/
   function showSpecificMassiveActionsParameters($input=array()) {

      switch ($input['action']) {
         case "add_contact_supplier" :
            if ($input['itemtype'] == 'Supplier') {
               Contact::dropdown(array('name' => "contacts_id"));
               echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                              _sx('button', 'Add')."'>";
               return true;
            }
            if ($input['itemtype'] == 'Contact') {
               Supplier::dropdown(array('name' => "suppliers_id"));
               echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                              _sx('button', 'Add')."'>";
               return true;
            }
            break;

         default :
            return parent::showSpecificMassiveActionsParameters($input);
      }
      return false;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate && Session::haveRight("contact_enterprise","r")) {
         switch ($item->getType()) {
            case 'Supplier' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(_n('Contact', 'Contacts', 2),
                                              self::countForSupplier($item));
               }
               return _n('Contact', 'Contacts', 2);

            case 'Contact' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(_n('Supplier', 'Suppliers', 2),
                                              self::countForContact($item));
               }
               return _n('Supplier', 'Suppliers', 2);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Supplier' :
            self::showForSupplier($item);
            break;

         case 'Contact' :
            self::showForContact($item);
            break;
      }
      return true;
   }


   /**
    * @param $item   string   Supplier object
   **/
   static function countForSupplier(Supplier $item) {

      $restrict = "`glpi_contacts_suppliers`.`suppliers_id` = '".$item->getField('id') ."'
                    AND `glpi_contacts_suppliers`.`contacts_id` = `glpi_contacts`.`id` ".
                    getEntitiesRestrictRequest(" AND ", "glpi_contacts", '',
                                               $_SESSION['glpiactiveentities'], true);

      return countElementsInTable(array('glpi_contacts_suppliers', 'glpi_contacts'), $restrict);
   }


   /**
    * @param $item   string   Contact object
   **/
   static function countForContact(Contact $item) {

      $restrict = "`glpi_contacts_suppliers`.`contacts_id` = '".$item->getField('id') ."'
                    AND `glpi_contacts_suppliers`.`suppliers_id` = `glpi_suppliers`.`id` ".
                    getEntitiesRestrictRequest(" AND ", "glpi_suppliers", '',
                                               $_SESSION['glpiactiveentities'], true);

      return countElementsInTable(array('glpi_contacts_suppliers', 'glpi_suppliers'), $restrict);
   }


   /**
    * Print the HTML array for entreprises on the current contact
    *
    *@return Nothing (display)
   **/
   static function showForContact(Contact $contact) {
      global $DB,$CFG_GLPI;

      $instID = $contact->fields['id'];

      if (!$contact->can($instID,'r')) {
         return false;
      }

      $canedit = $contact->can($instID,'w');
      $rand = mt_rand();

      $query = "SELECT `glpi_contacts_suppliers`.`id`,
                       `glpi_suppliers`.`id` AS entID,
                       `glpi_suppliers`.`name` AS name,
                       `glpi_suppliers`.`website` AS website,
                       `glpi_suppliers`.`fax` AS fax,
                       `glpi_suppliers`.`phonenumber` AS phone,
                       `glpi_suppliers`.`suppliertypes_id` AS type,
                       `glpi_suppliers`.`is_deleted`,
                       `glpi_entities`.`id` AS entity
                FROM `glpi_contacts_suppliers`, `glpi_suppliers`
                LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id`=`glpi_suppliers`.`entities_id`)
                WHERE `glpi_contacts_suppliers`.`contacts_id` = '$instID'
                      AND `glpi_contacts_suppliers`.`suppliers_id` = `glpi_suppliers`.`id`".
                      getEntitiesRestrictRequest(" AND","glpi_suppliers",'','',true) ."
                ORDER BY `glpi_entities`.`completename`, `name`";

      $result = $DB->query($query);

      $suppliers = array();
      $used = array();
      if ($number = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $suppliers[$data['id']] = $data;
            $used[$data['entID']] = $data['entID'];
         }
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='contactsupplier_form$rand' id='contactsupplier_form$rand' method='post' action='";
         echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='2'>".__('Add a supplier')."</tr>";

         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<input type='hidden' name='contacts_id' value='$instID'>";
         Supplier::dropdown(array('used'        => $used,
                                  'entity'      => $contact->fields["entities_id"],
                                  'entity_sons' => $contact->fields["is_recursive"]));
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button','Add')."\" class='submit'>";
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('num_displayed'  => $number);
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";
      if ($canedit && $number) {
         echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
      }
      echo "<th>".__('Supplier')."</th>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>".__('Third party type')."</th>";
      echo "<th>". __('Phone')."</th>";
      echo "<th>".__('Fax')."</th>";
      echo "<th>".__('Website')."</th>";
      echo "</tr>";

      $used = array();
      if ($number > 0) {
         Session::initNavigateListItems('Supplier',
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                Contact::getTypeName(1), $contact->getName()));

         foreach ($suppliers as $data) {
            $ID = $data["id"];
            Session::addToNavigateListItems('Supplier', $data["entID"]);
            $used[$data["entID"]] = $data["entID"];
            $website              = $data["website"];

            if (!empty($website)) {
               $website = $data["website"];

               if (!preg_match("?https*://?",$website)) {
                  $website = "http://".$website;
               }
               $website = "<a target=_blank href='$website'>".$data["website"]."</a>";
            }

            echo "<tr class='tab_bg_1".($data["is_deleted"]?"_2":"")."'>";
            if ($canedit) {
               echo "<td>".Html::getMassiveActionCheckBox(__CLASS__, $data["id"])."</td>";
            }
            echo "<td class='center'>";
            echo "<a href='".$CFG_GLPI["root_doc"]."/front/supplier.form.php?id=".$data["entID"]."'>".
                   Dropdown::getDropdownName("glpi_suppliers", $data["entID"])."</a></td>";
            echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities", $data["entity"]);
            echo "</td>";
            echo "<td class='center'>".Dropdown::getDropdownName("glpi_suppliertypes", $data["type"]);
            echo "</td>";
            echo "<td class='center' width='80'>".$data["phone"]."</td>";
            echo "<td class='center' width='80'>".$data["fax"]."</td>";
            echo "<td class='center'>".$website."</td>";
            echo "</tr>";

         }
      }


      echo "</table>";
      if ($canedit && $number) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }

   /**
    * Show contacts asociated to an enterprise
   **/
   static function showForSupplier(Supplier $supplier) {
      global $DB,$CFG_GLPI;

      $instID = $supplier->fields['id'];
      if (!$supplier->can($instID,'r')) {
         return false;
      }
      $canedit = $supplier->can($instID,'w');
      $rand = mt_rand();

      $query = "SELECT `glpi_contacts`.*,
                       `glpi_contacts_suppliers`.`id` AS ID_ent,
                       `glpi_entities`.`id` AS entity
                FROM `glpi_contacts_suppliers`, `glpi_contacts`
                LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id`=`glpi_contacts`.`entities_id`)
                WHERE `glpi_contacts_suppliers`.`contacts_id`=`glpi_contacts`.`id`
                      AND `glpi_contacts_suppliers`.`suppliers_id` = '$instID'" .
                      getEntitiesRestrictRequest(" AND", "glpi_contacts", '', '', true) ."
                ORDER BY `glpi_entities`.`completename`, `glpi_contacts`.`name`";

      $result = $DB->query($query);

      $contacts = array();
      $used = array();
      if ($number = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $contacts[$data['ID_ent']] = $data;
            $used[$data['id']] = $data['id'];
         }
      }
      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='contactsupplier_form$rand' id='contactsupplier_form$rand' method='post' action='";
         echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='2'>".__('Add a contact')."</tr>";

         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<input type='hidden' name='suppliers_id' value='$instID'>";

         Contact::dropdown(array('used'        => $used,
                                 'entity'      => $supplier->fields["entities_id"],
                                 'entity_sons' => $supplier->fields["is_recursive"]));

         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button','Add')."\" class='submit'>";
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('num_displayed'  => $number);
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr>";
      if ($canedit && $number) {
         echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
      }
      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>". __('Phone')."</th>";
      echo "<th>". __('Phone 2')."</th>";
      echo "<th>".__('Mobile phone')."</th>";
      echo "<th>".__('Fax')."</th>";
      echo "<th>"._n('Email', 'Emails', 1)."</th>";
      echo "<th>".__('Type')."</th>";
      echo "</tr>";

      $used = array();
      if ($number) {
         Session::initNavigateListItems('Contact',
         //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'), Supplier::getTypeName(1),
                                                $supplier->getName()));

         foreach ($contacts as $data) {
            $ID                = $data["ID_ent"];
            $used[$data["id"]] = $data["id"];
            Session::addToNavigateListItems('Contact',$data["id"]);

            echo "<tr class='tab_bg_1".($data["is_deleted"]?"_2":"")."'>";
            if ($canedit) {
               echo "<td>".Html::getMassiveActionCheckBox(__CLASS__, $data["ID_ent"])."</td>";
            }
            echo "<td class='center'>";
            echo "<a href='".$CFG_GLPI["root_doc"]."/front/contact.form.php?id=".$data["id"]."'>".
                   sprintf(__('%1$s %2$s'), $data["name"], $data["firstname"])."</a></td>";
            echo "<td class='center' width='100'>".Dropdown::getDropdownName("glpi_entities",
                                                                             $data["entity"])."</td>";
            echo "<td class='center' width='100'>".$data["phone"]."</td>";
            echo "<td class='center' width='100'>".$data["phone2"]."</td>";
            echo "<td class='center' width='100'>".$data["mobile"]."</td>";
            echo "<td class='center' width='100'>".$data["fax"]."</td>";
            echo "<td class='center'>";
            echo "<a href='mailto:".$data["email"]."'>".$data["email"]."</a></td>";
            echo "<td class='center'>".Dropdown::getDropdownName("glpi_contacttypes",
                                                                 $data["contacttypes_id"])."</td>";
            echo "</tr>";
         }
      }

      echo "</table>";
      if ($canedit && $number) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions(__CLASS__, $massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }
}
?>
