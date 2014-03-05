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

/**
 * Document class
**/
class Document extends CommonDBTM {

   // From CommonDBTM
   public $dohistory                   = true;

   static protected $forward_entity_to = array('Document_Item');


   static function getTypeName($nb=0) {
      return _n('Document', 'Documents', $nb);
   }


   static function canCreate() {
      // Have right to add document OR ticket followup
      return Session::haveRight('document', 'w') || Session::haveRight('add_followups', '1');
   }


   static function canUpdate() {
      return Session::haveRight('document', 'w');
   }


   function canCreateItem() {
      if (isset($this->input['itemtype']) && isset($this->input['items_id'])) {
         if ($item = getItemForItemtype($this->input['itemtype'])) {
            if ($item->canAddItem('Document')) {
               return true;
            }
         }
      }

      // From Ticket Document Tab => check right to add followup.
      if (isset($this->fields['tickets_id'])
          && ($this->fields['tickets_id'] > 0)) {

         $ticket = new Ticket();
         if ($ticket->getFromDB($this->fields['tickets_id'])) {
            return $ticket->canAddFollowups();
         }
      }

      if (Session::haveRight('document', 'w')) {
         return parent::canCreateItem();
      }
      return false;
   }


   static function canView() {
      return Session::haveRight('document', 'r');
   }


   function cleanDBonPurge() {

      $di = new Document_Item();
      $di->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      // UNLINK DU FICHIER
      if (!empty($this->fields["filepath"])) {
         if (is_file(GLPI_DOC_DIR."/".$this->fields["filepath"])
             && !is_dir(GLPI_DOC_DIR."/".$this->fields["filepath"])
             && (countElementsInTable($this->getTable(),
                                     "`sha1sum`='".$this->fields["sha1sum"]."'") <= 1)) {

            if (unlink(GLPI_DOC_DIR."/".$this->fields["filepath"])) {
               Session::addMessageAfterRedirect(sprintf(__('Succesful deletion of the file %s'),
                                                         GLPI_DOC_DIR."/".$this->fields["filepath"]));
            } else {
               Session::addMessageAfterRedirect(sprintf(__('Failed to delete the file %s'),
                                                        GLPI_DOC_DIR."/".$this->fields["filepath"]),
                                                false, ERROR);
            }
         }
      }
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Note', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {
      global $CFG_GLPI, $DB;

      // security (don't accept filename from $_POST)
      unset($input['filename']);

      if ($uid = Session::getLoginUserID()) {
         $input["users_id"] = Session::getLoginUserID();
      }

      // Create a doc only selecting a file from a item form
      $create_from_item = false;

      if (isset($input["items_id"])
          && isset($input["itemtype"])
          && ($item = getItemForItemtype($input["itemtype"]))
          && ($input["items_id"] > 0)) {

         $typename = $item->getTypeName(1);
         $name     = NOT_AVAILABLE;

         if ($item->getFromDB($input["items_id"])) {
            $name = $item->getNameID();
         }
         //TRANS: %1$s is Document, %2$s is item type, %3$s is item name
         $input["name"] = addslashes(Html::resume_text(sprintf(__('%1$s: %2$s'),
                                                               __('Document'),
                                                       sprintf(__('%1$s - %2$s'),$typename, $name)),
                                                       200));
         $create_from_item = true;
      }

      if (isset($input["upload_file"]) && !empty($input["upload_file"])) {
         // Move doc from upload dir
         $this->moveUploadedDocument($input, $input["upload_file"]);

      } else if (isset($_FILES) && isset($_FILES['filename'])) {
         // Move doc send with form
         $upload_result = $this->uploadDocument($input, $_FILES['filename']);
         // Upload failed : do not create document
         if ($create_from_item && !$upload_result) {
            return false;
         }
         // Document is moved, so $_FILES is no more useful
         unset($_FILES['filename']);
      }

      // Default document name
      if ((!isset($input['name']) || empty($input['name']))
          && isset($input['filename'])) {
         $input['name'] = $input['filename'];
      }

      unset($input["upload_file"]);

      // Don't add if no file
      if (isset($input["_only_if_upload_succeed"])
          && $input["_only_if_upload_succeed"]
          && (!isset($input['filename']) || empty($input['filename']))) {
         return false;
      }

      // Set default category for document linked to tickets
      if (isset($input['itemtype'])
         && $input['itemtype'] == 'Ticket'
         && (!isset($input['documentcategories_id'])
            || $input['documentcategories_id'] == 0)) {
         $input['documentcategories_id'] = $CFG_GLPI["documentcategories_id_forticket"];
      }

      /* Unicity check
      if (isset($input['sha1sum'])) {
         // Check if already upload in the current entity
         $crit = array('sha1sum'=>$input['sha1sum'],
                       'entities_id'=>$input['entities_id']);
         foreach ($DB->request($this->getTable(), $crit) as $data) {
            $link=$this->getFormURL();
            Session::addMessageAfterRedirect(__('"A document with that filename has already been attached to another record.').
               "&nbsp;: <a href=\"".$link."?id=".
                     $data['id']."\">".$data['name']."</a>",
               false, ERROR, true);
            return false;
         }
      } */
      return $input;
   }


   function post_addItem() {

      if (isset($this->input["items_id"])
          && isset($this->input["itemtype"])
          && (($this->input["items_id"] > 0)
              || (($this->input["items_id"] == 0)
                  && ($this->input["itemtype"] == 'Entity')))
          && !empty($this->input["itemtype"])) {

         $docitem = new Document_Item();
         $docitem->add(array('documents_id' => $this->fields['id'],
                             'itemtype'     => $this->input["itemtype"],
                             'items_id'     => $this->input["items_id"]));

         Event::log($this->fields['id'], "documents", 4, "document",
                  //TRANS: %s is the user login
                    sprintf(__('%s adds a link with an item'), $_SESSION["glpiname"]));
      }
   }


   /**
    * @see CommonDBTM::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      // security (don't accept filename from $_POST)
      unset($input['filename']);

      if (isset($_FILES['filename']['type']) && !empty($_FILES['filename']['type'])) {
         $input['mime'] = $_FILES['filename']['type'];
      }

      if (isset($input['current_filepath'])) {
         if (isset($input["upload_file"]) && !empty($input["upload_file"])) {
            $this->moveUploadedDocument($input, $input["upload_file"]);
         } else if (isset($_FILES['filename'])) {
            $this->uploadDocument($input, $_FILES['filename']);
            // Document is moved, so $_FILES is no more useful
            unset($_FILES['filename']);
         }
      }

      if (empty($input['filename'])) {
         unset($input['filename']);
      }
      unset($input['current_filepath']);
      unset($input['current_filename']);

      return $input;
   }


   /**
    * Print the document form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return Nothing (display)
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showTabs($options);
      $options['formoptions'] = " enctype='multipart/form-data'";
      $this->showFormHeader($options);

      $showuserlink = 0;
      if (Session::haveRight('user','r')) {
         $showuserlink = 1;
      }

      if ($ID > 0) {
         echo "<tr><th colspan='2'>";
         if ($this->fields["users_id"] > 0) {
             printf(__('Added by %s'), getUserName($this->fields["users_id"], $showuserlink));
         } else {
            echo "&nbsp;";
         }
         echo "</th>";
         echo "<th colspan='2'>";

         //TRANS: %s is the datetime of update
         printf(__('Last update on %s'), Html::convDateTime($this->fields["date_mod"]));

         echo "</th></tr>\n";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='6' class='middle right'>".__('Comments')."</td>";
      echo "<td class='center middle' rowspan='6'>";
      echo "<textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      if ($ID > 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Current file')."</td>";
         echo "<td>".$this->getDownloadLink('',45);
         echo "<input type='hidden' name='current_filepath' value='".$this->fields["filepath"]."'>";
         echo "<input type='hidden' name='current_filename' value='".$this->fields["filename"]."'>";
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".sprintf(__('%1$s (%2$s)'), __('File'), self::getMaxUploadSize())."</td>";
      echo "<td><input type='file' name='filename' value='".$this->fields["filename"]."' size='39'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Use a FTP installed file')."</td>";
      echo "<td>";
      $this->showUploadedFilesDropdown("upload_file");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Web Link')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "link");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Heading')."</td>";
      echo "<td>";
      DocumentCategory::dropdown(array('value' => $this->fields["documentcategories_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('MIME type')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "mime");

      if ($ID > 0) {
         echo "</td><td>".sprintf(__('%1$s (%2$s)'), __('Checksum'), __('SHA1'))."</td>";
         echo "<td>".$this->fields["sha1sum"];
      }
      echo "</td></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   /**
    * Get max upload size from php config
   **/
   static function getMaxUploadSize() {

      $max_size  = Toolbox::return_bytes_from_ini_vars(ini_get("upload_max_filesize"));
      $max_size /= 1024*1024;
      //TRANS: %s is a size
      return sprintf(__('%s Mio max'), round($max_size, 1));
   }


   /**
    * Send a document to navigator
   **/
   function send() {

      $file = GLPI_DOC_DIR."/".$this->fields['filepath'];

      if (!file_exists($file)) {
         die("Error file ".$file." does not exist");
      }
      // Now send the file with header() magic
      header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
      header('Pragma: private'); /// IE BUG + SSL
      header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
      header("Content-disposition: filename=\"".$this->fields['filename']."\"");
      header("Content-type: ".$this->fields['mime']);

      readfile($file) or die ("Error opening file $file");
   }


   /**
    * Get download link for a document
    *
    * @param $params    additonal parameters to be added to the link (default '')
    * @param $len       maximum length of displayed string (default 20)
    *
   **/
   function getDownloadLink($params='', $len=20) {
      global $DB,$CFG_GLPI;

      $splitter = explode("/",$this->fields['filename']);

      if (count($splitter) == 2) {
         // Old documents in EXT/filename
         $fileout = $splitter[1];
      } else {
         // New document
         $fileout = $this->fields['filename'];
      }

      $initfileout = $fileout;
      if (Toolbox::strlen($fileout) > $len) {
         $fileout = Toolbox::substr($fileout,0,$len)."&hellip;";
      }

      $out = "<a href='".$CFG_GLPI["root_doc"]."/front/document.send.php?docid=".
               $this->fields['id'].$params."' alt=\"".$initfileout.
               "\" title=\"".$initfileout."\" target='_blank'>";

      $splitter = explode("/",$this->fields['filepath']);

      if (count($splitter)) {
         $query = "SELECT *
                   FROM `glpi_documenttypes`
                   WHERE `ext` LIKE '".$splitter[0]."'
                         AND `icon` <> ''";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               $icon = $DB->result($result,0,'icon');
               $out .= "&nbsp;<img class='middle' style='margin-left:3px; margin-right:6px;' alt=\"".
                               $initfileout."\" title=\"".$initfileout."\" src='".
                               $CFG_GLPI["typedoc_icon_dir"]."/$icon'>";
            }
         }
      }
      $out .= "<span class='b'>$fileout</span></a>";

      return $out;
   }


   /**
    * find a document with a file attached
    *
    * @param $entity    of the document
    * @param $path      of the searched file
    *
    * @return boolean
   **/
   function getFromDBbyContent($entity, $path) {

      if (empty($path)) {
         return false;
      }

      $sum = sha1_file($path);
      if (!$sum) {
         return false;
      }

      return $this->getFromDBByQuery("WHERE `".$this->getTable()."`.`sha1sum` = '$sum'
                                     AND `".$this->getTable()."`.`entities_id` = '$entity'");
   }


   /**
    * Check is the curent user is allowed to see the file
    *
    * @param $options array of options (only 'tickets_id' used)
    *
    * @return boolean
   **/
   function canViewFile($options) {
      global $DB, $CFG_GLPI;

      if (isset($_SESSION["glpiactiveprofile"]["interface"])
          && ($_SESSION["glpiactiveprofile"]["interface"] == "central")) {

         // My doc Check and Common doc right access
         if ($this->can($this->fields["id"],'r')
             || ($this->fields["users_id"] === Session::getLoginUserID())) {
            return true;
         }

         // Reminder Case
         $query = "SELECT *
                   FROM `glpi_documents_items`
                   LEFT JOIN `glpi_reminders`
                        ON (`glpi_reminders`.`id` = `glpi_documents_items`.`items_id`
                            AND `glpi_documents_items`.`itemtype` = 'Reminder')
                   ".Reminder::addVisibilityJoins()."
                   WHERE `glpi_documents_items`.`documents_id` = '".$this->fields["id"]."'
                         AND ".Reminder::addVisibilityRestrict();
         $result = $DB->query($query);
         if ($DB->numrows($result) > 0) {
            return true;
         }

         // Knowbase Case
         if (Session::haveRight("knowbase","r")) {
            $query = "SELECT *
                      FROM `glpi_documents_items`
                      LEFT JOIN `glpi_knowbaseitems`
                           ON (`glpi_knowbaseitems`.`id` = `glpi_documents_items`.`items_id`
                               AND `glpi_documents_items`.`itemtype` = 'KnowbaseItem')
                      ".KnowbaseItem::addVisibilityJoins()."
                      WHERE `glpi_documents_items`.`documents_id` = '".$this->fields["id"]."'
                            AND ".KnowbaseItem::addVisibilityRestrict();
            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               return true;
            }
         }

         if (Session::haveRight("faq","r")) {
            $query = "SELECT *
                      FROM `glpi_documents_items`
                      LEFT JOIN `glpi_knowbaseitems`
                           ON (`glpi_knowbaseitems`.`id` = `glpi_documents_items`.`items_id`
                               AND `glpi_documents_items`.`itemtype` = 'KnowbaseItem')
                      ".KnowbaseItem::addVisibilityJoins()."
                      WHERE `glpi_documents_items`.`documents_id` = '".$this->fields["id"]."'
                            AND `glpi_knowbaseitems`.`is_faq` = '1'
                            AND ".KnowbaseItem::addVisibilityRestrict();
            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               return true;
            }
         }

         // Tracking Case
         if (isset($options["tickets_id"])) {
            $job = new Ticket();

            if ($job->can($options["tickets_id"],'r')) {
               $query = "SELECT *
                         FROM `glpi_documents_items`
                         WHERE `glpi_documents_items`.`items_id` = '".$options["tickets_id"]."'
                               AND `glpi_documents_items`.`itemtype` = 'Ticket'
                               AND `documents_id`='".$this->fields["id"]."'";

               $result = $DB->query($query);
               if ($DB->numrows($result) > 0) {
                  return true;
               }
            }
         }

      } else if (Session::getLoginUserID()) { // ! central

         // Check if it is my doc
         if ($this->fields["users_id"] === Session::getLoginUserID()) {
            return true;
         }

         // Reminder Case
         $query = "SELECT *
                   FROM `glpi_documents_items`
                   LEFT JOIN `glpi_reminders`
                        ON (`glpi_reminders`.`id` = `glpi_documents_items`.`items_id`
                            AND `glpi_documents_items`.`itemtype` = 'Reminder')
                   ".Reminder::addVisibilityJoins()."
                   WHERE `glpi_documents_items`.`documents_id` = '".$this->fields["id"]."'
                         AND ".Reminder::addVisibilityRestrict();
         $result = $DB->query($query);
         if ($DB->numrows($result) > 0) {
            return true;
         }

         if (Session::haveRight("faq","r")) {
            // Check if it is a FAQ document
            $query = "SELECT *
                      FROM `glpi_documents_items`
                      LEFT JOIN `glpi_knowbaseitems`
                           ON (`glpi_knowbaseitems`.`id` = `glpi_documents_items`.`items_id`)
                      ".KnowbaseItem::addVisibilityJoins()."
                      WHERE `glpi_documents_items`.`itemtype` = 'KnowbaseItem'
                            AND `glpi_documents_items`.`documents_id` = '".$this->fields["id"]."'
                            AND `glpi_knowbaseitems`.`is_faq` = '1'
                            AND ".KnowbaseItem::addVisibilityRestrict();

            $result = $DB->query($query);
            if ($DB->numrows($result) > 0) {
               return true;
            }
         }

         // Tracking Case
         if (isset($options["tickets_id"])) {
            $job = new Ticket();

            if ($job->can($options["tickets_id"],'r')) {
               $query = "SELECT *
                         FROM `glpi_documents_items`
                         WHERE `glpi_documents_items`.`items_id` = '".$options["tickets_id"]."'
                               AND `glpi_documents_items`.`itemtype` = 'Ticket'
                               AND `documents_id` = '".$this->fields["id"]."'";

               $result = $DB->query($query);
               if ($DB->numrows($result) > 0) {
                  return true;
               }
            }
         }
      }

      // Public FAQ for not connected user
      if ($CFG_GLPI["use_public_faq"]) {
         $query = "SELECT *
                   FROM `glpi_documents_items`
                   LEFT JOIN `glpi_knowbaseitems`
                        ON (`glpi_knowbaseitems`.`id` = `glpi_documents_items`.`items_id`)
                   LEFT JOIN `glpi_entities_knowbaseitems`
                        ON (`glpi_knowbaseitems`.`id` = `glpi_entities_knowbaseitems`.`knowbaseitems_id`)
                   WHERE `glpi_documents_items`.`itemtype` = 'KnowbaseItem'
                         AND `glpi_documents_items`.`documents_id` = '".$this->fields["id"]."'
                         AND `glpi_knowbaseitems`.`is_faq` = '1'
                         AND `glpi_entities_knowbaseitems`.`entities_id` = '0'
                         AND `glpi_entities_knowbaseitems`.`is_recursive` = '1'";

         $result = $DB->query($query);
         if ($DB->numrows($result) > 0) {
            return true;
         }
      }

      return false;
   }


   /**
    * @since version 0.84
   **/
   static function getSearchOptionsToAdd() {

      $tab                       = array();
      $tab['document']           = self::getTypeName(2);

      $tab[119]['table']         = 'glpi_documents_items';
      $tab[119]['field']         = 'count';
      $tab[119]['name']          = _x('quantity', 'Number of documents');
      $tab[119]['forcegroupby']  = true;
      $tab[119]['usehaving']     = true;
      $tab[119]['datatype']      = 'number';
      $tab[119]['massiveaction'] = false;
      $tab[119]['joinparams']    = array('jointype' => 'itemtype_item');

      return $tab;
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         $actions['add_document_item']    = _x('button', 'Add an item');
         $actions['remove_document_item'] = _x('button', 'Remove an item');
      }
      if (Session::haveRight('transfer','r')
          && Session::isMultiEntitiesMode()
          && $isadmin) {
         $actions['add_transfer_list'] = _x('button', 'Add to transfer list');
      }
      return $actions;
   }


   /**
    * @see CommonDBTM::showSpecificMassiveActionsParameters()
   **/
   function showSpecificMassiveActionsParameters($input=array()) {
      global $CFG_GLPI;

      switch ($input['action']) {
         case "add_document_item" :
            Dropdown::showAllItems("items_id", 0, 0, -1,
                                    $CFG_GLPI["document_types"], false, true, 'item_itemtype');
            echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                           _sx('button', 'Add')."'>";
            return true;

         case "remove_document_item" :
            Dropdown::showAllItems("items_id", 0, 0, -1,
                                    $CFG_GLPI["document_types"], false, true, 'item_itemtype');
            echo "<br><br><input type='submit' name='massiveaction' class='submit' value='".
                           _sx('button', 'Delete permanently')."'>";
            return true;

         default :
            return parent::showSpecificMassiveActionsParameters($input);
      }
      return false;
   }


   /**
    * @see CommonDBTM::doSpecificMassiveActions()
   **/
   function doSpecificMassiveActions($input=array()) {

      $res = array('ok'      => 0,
                   'ko'      => 0,
                   'noright' => 0);

      switch ($input['action']) {
         case "add_document" :
         case "add_document_item" :
            $documentitem = new Document_Item();
            foreach ($input["item"] as $key => $val) {
               if (isset($input['items_id'])) {
                  // Add items to documents
                  $input2 = array('itemtype'     => $input["item_itemtype"],
                                  'items_id'     => $input["items_id"],
                                  'documents_id' => $key);
               } else if (isset($input['documents_id'])) { // Add document to item
                  $input2 = array('itemtype'     => $input["itemtype"],
                                  'items_id'     => $key,
                                  'documents_id' => $input['documents_id']);
               } else {
                  return false;
               }

               if ($documentitem->can(-1, 'w', $input2)) {
                  if ($documentitem->add($input2)) {
                     $res['ok']++;
                  } else {
                     $res['ko']++;
                  }
               } else {
                  $res['noright']++;
               }
            }
            break;

         case "remove_document" :
         case "remove_document_item" :
            foreach ($input["item"] as $key => $val) {
               if (isset($input['items_id'])) {
                  // Remove item to documents
                  $input2 = array('itemtype'     => $input["item_itemtype"],
                                  'items_id'     => $input["items_id"],
                                  'documents_id' => $key);
               } else if (isset($input['documents_id'])) {
                  // Remove contract to items
                  $input2 = array('itemtype'     => $input["itemtype"],
                                  'items_id'     => $key,
                                  'documents_id' => $input['documents_id']);
               } else {
                  return false;
               }
               $docitem = new Document_Item();
               if ($docitem->can(-1, 'w', $input2)) {
                  if ($item = getItemForItemtype($input2["itemtype"])) {
                     if ($item->getFromDB($input2['items_id'])) {
                        $doc = new self();
                        if ($doc->getFromDB($input2['documents_id'])) {
                           if ($docitem->getFromDBForItems($doc, $item)) {
                              if ($docitem->delete(array('id' => $docitem->getID()))) {
                                 $res['ok']++;
                              } else {
                                 $res['ko']++;
                              }
                           } else {
                              $res['ko']++;
                           }
                        } else {
                           $res['ko']++;
                        }
                     } else {
                        $res['ko']++;
                     }
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


   function getSearchOptions() {

      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['massiveaction']   = false;

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false;
      $tab[2]['datatype']        = 'number';

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'filename';
      $tab[3]['name']            = __('File');
      $tab[3]['massiveaction']   = false;
      $tab[3]['datatype']        = 'string';

      $tab[4]['table']           = $this->getTable();
      $tab[4]['field']           = 'link';
      $tab[4]['name']            = __('Web Link');
      $tab[4]['datatype']        = 'weblink';

      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'mime';
      $tab[5]['name']            = __('MIME type');
      $tab[5]['datatype']        = 'string';

      $tab[16]['table']          = $this->getTable();
      $tab[16]['field']          = 'comment';
      $tab[16]['name']           = __('Comments');
      $tab[16]['datatype']       = 'text';

      $tab[90]['table']          = $this->getTable();
      $tab[90]['field']          = 'notepad';
      $tab[90]['name']           = __('Notes');
      $tab[90]['massiveaction']  = false;
      $tab[90]['datatype']       = 'text';

      $tab[7]['table']           = 'glpi_documentcategories';
      $tab[7]['field']           = 'completename';
      $tab[7]['name']            = __('Heading');
      $tab[7]['datatype']        = 'dropdown';

      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['massiveaction']  = false;
      $tab[80]['datatype']       = 'dropdown';

      $tab[86]['table']          = $this->getTable();
      $tab[86]['field']          = 'is_recursive';
      $tab[86]['name']           = __('Child entities');
      $tab[86]['datatype']       = 'bool';

      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      $tab[20]['table']          = $this->getTable();
      $tab[20]['field']          = 'sha1sum';
      $tab[20]['name']           = sprintf(__('%1$s (%2$s)'), __('Checksum'), __('SHA1'));
      $tab[20]['massiveaction']  = false;
      $tab[20]['datatype']       = 'string';

      $tab[72]['table']          = 'glpi_documents_items';
      $tab[72]['field']          = 'count';
      $tab[72]['name']           = __('Number of associated items');
      $tab[72]['forcegroupby']   = true;
      $tab[72]['usehaving']      = true;
      $tab[72]['datatype']       = 'number';
      $tab[72]['massiveaction']  = false;
      $tab[72]['joinparams']     = array('jointype' => 'child');

      return $tab;
   }


   /**
    * Move a file to a new location
    * Work even if dest file already exists
    *
    * @param $srce   source file path
    * @param $dest   destination file path
    *
    * @return boolean : success
   **/
   static function renameForce($srce, $dest) {

      // File already present
      if (is_file($dest)) {
         // As content is the same (sha1sum), no need to copy
         @unlink($srce);
         return true;
      }
      // Move
      return rename($srce,$dest);
   }


   /**
    * Move an uploadd document (files in GLPI_DOC_DIR."/_uploads" dir)
    *
    * @param $input     array of datas used in adding process (need current_filepath)
    * @param $filename        filename to move
    *
    * @return boolean for success / $input array is updated
   **/
   static function moveUploadedDocument(array &$input, $filename) {
      global $CFG_GLPI;

      $fullpath = GLPI_DOC_DIR."/_uploads/".$filename;

      if (!is_dir(GLPI_DOC_DIR."/_uploads")) {
         Session::addMessageAfterRedirect(__("Upload directory doesn't exist"), false, ERROR);
         return false;
      }

      if (!is_file($fullpath)) {
         Session::addMessageAfterRedirect(sprintf(__('File %s not found.'), $fullpath),
                                          false, ERROR);
         return false;
      }
      $sha1sum  = sha1_file($fullpath);
      $dir      = self::isValidDoc($filename);
      $new_path = self::getUploadFileValidLocationName($dir, $sha1sum);

      if (!$sha1sum || !$dir || !$new_path) {
         return false;
      }

      // Delete old file (if not used by another doc)
      if (isset($input['current_filepath'])
          && !empty($input['current_filepath'])
          && is_file(GLPI_DOC_DIR."/".$input['current_filepath'])
          && (countElementsInTable('glpi_documents',
                                   "`sha1sum`='".sha1_file(GLPI_DOC_DIR."/".
                                             $input['current_filepath'])."'") <= 1)) {

         if (unlink(GLPI_DOC_DIR."/".$input['current_filepath'])) {
            Session::addMessageAfterRedirect(sprintf(__('Succesful deletion of the file %s'),
                                                    $input['current_filename']));
         } else {
            // TRANS: %1$s is the curent filename, %2$s is its directory
            Session::addMessageAfterRedirect(sprintf(__('Failed to delete the file %1$s (%2$s)'),
                                                     $input['current_filename'],
                                                     GLPI_DOC_DIR."/".$input['current_filepath']),
                                             false, ERROR);
         }
      }

      // Local file : try to detect mime type
      if (function_exists('finfo_open')
          && ($finfo = finfo_open(FILEINFO_MIME))) {
         $input['mime'] = finfo_file($finfo, $fullpath);
         finfo_close($finfo);

      } else if (function_exists('mime_content_type')) {
         $input['mime'] = mime_content_type($fullpath);
      }

      if (is_writable(GLPI_DOC_DIR."/_uploads/")
          && is_writable ($fullpath)) { // Move if allowed

         if (self::renameForce($fullpath, GLPI_DOC_DIR."/".$new_path)) {
            Session::addMessageAfterRedirect(__('Document move succeeded.'));
         } else {
            Session::addMessageAfterRedirect(__('File move failed.'), false, ERROR);
            return false;
         }

      } else { // Copy (will overwrite dest file is present)
         if (copy($fullpath, GLPI_DOC_DIR."/".$new_path)) {
            Session::addMessageAfterRedirect(__('Document copy succeeded.'));
         } else {
            Session::addMessageAfterRedirect(__('File move failed'), false, ERROR);
            return false;
         }
      }

      // For display
      $input['filename'] = addslashes($filename);
      // Storage path
      $input['filepath'] = $new_path;
      // Checksum
      $input['sha1sum']  = $sha1sum;
      return true;
   }


   /**
    * Upload a new file
    *
    * @param &$input    array of datas need for add/update (will be completed)
    * @param $FILEDESC        FILE descriptor
    *
    * @return true on success
   **/
   static function uploadDocument(array &$input, $FILEDESC) {

      if (!count($FILEDESC)
          || empty($FILEDESC['name'])
          || !is_file($FILEDESC['tmp_name'])) {

         switch ($FILEDESC['error']) {
            case 1 :
            case 2 :
               Session::addMessageAfterRedirect(__('File too large to be added.'), false, ERROR);
               break;

            case 4 :
//                Session::addMessageAfterRedirect(__('No file specified.'),false,ERROR);
               break;
         }

         return false;
      }

      $sha1sum = sha1_file($FILEDESC['tmp_name']);
      $dir     = self::isValidDoc($FILEDESC['name']);
      $path    = self::getUploadFileValidLocationName($dir,$sha1sum);

      if (!$sha1sum || !$dir || !$path) {
         return false;
      }

      // Delete old file (if not used by another doc)
      if (isset($input['current_filepath'])
          && !empty($input['current_filepath'])
          && (countElementsInTable('glpi_documents',
                                  "`sha1sum`='".sha1_file(GLPI_DOC_DIR."/".
                                             $input['current_filepath'])."'") <= 1)) {

         if (unlink(GLPI_DOC_DIR."/".$input['current_filepath'])) {
            Session::addMessageAfterRedirect(sprintf(__('Succesful deletion of the file %s'),
                                                     $input['current_filename']));
         } else {
            // TRANS: %1$s is the curent filename, %2$s is its directory
            Session::addMessageAfterRedirect(sprintf(__('Failed to delete the file %1$s (%2$s)'),
                                                     $input['current_filename'],
                                                     GLPI_DOC_DIR."/".$input['current_filepath']),
                                             false, ERROR);
         }
      }

      // Mime type from client
      if (isset($FILEDESC['type']) && !empty($FILEDESC['type'])) {
         $input['mime'] = $FILEDESC['type'];
      }

      // Move uploaded file
      if (self::renameForce($FILEDESC['tmp_name'], GLPI_DOC_DIR."/".$path)) {
         Session::addMessageAfterRedirect(__('The file is valid. Upload is successful.'));
         // For display
         $input['filename'] = addslashes($FILEDESC['name']);
         // Storage path
         $input['filepath'] = $path;
         // Checksum
         $input['sha1sum']  = $sha1sum;
         return true;
      }
      Session::addMessageAfterRedirect(__('Potential upload attack or file too large. Moving temporary file failed.'),
                                       false, ERROR);
      return false;
   }


   /**
    * Find a valid path for the new file
    *
    * @param $dir       dir to search a free path for the file
    * @param $sha1sum   SHA1 of the file
    *
    * @return nothing
   **/
   static function getUploadFileValidLocationName($dir, $sha1sum) {
      global $CFG_GLPI;

      if (empty($dir)) {
         $message = __('Unauthorized file type');

         if (Session::haveRight('dropdown','r')) {
            $dt       = new DocumentType();
            $message .= " <a target='_blank' href='".$dt->getSearchURL()."'>
                         <img src=\"".$CFG_GLPI["root_doc"]."/pics/aide.png\"></a>";
         }
         Session::addMessageAfterRedirect($message, false, ERROR);
         return '';
      }

      if (!is_dir(GLPI_DOC_DIR)) {
         Session::addMessageAfterRedirect(sprintf(__("The directory %s doesn't exist."),
                                                  GLPI_DOC_DIR),
                                          false, ERROR);
         return '';
      }
      $subdir = $dir.'/'.substr($sha1sum,0,2);

      if (!is_dir(GLPI_DOC_DIR."/".$subdir)
          && @mkdir(GLPI_DOC_DIR."/".$subdir,0777,true)) {
         Session::addMessageAfterRedirect(sprintf(__('Create the directory %s'),
                                                  GLPI_DOC_DIR."/".$subdir));
      }

      if (!is_dir(GLPI_DOC_DIR."/".$subdir)) {
         Session::addMessageAfterRedirect(sprintf(__('Failed to create the directory %s. Verify that you have the correct permission'),
                                                  GLPI_DOC_DIR."/".$subdir),
                                          false, ERROR);
         return '';
      }
      return $subdir.'/'.substr($sha1sum,2).'.'.$dir;
   }


   /**
    * Show dropdown of uploaded files
    *
    * @param $myname dropdown name
   **/
   static function showUploadedFilesDropdown($myname) {
      global $CFG_GLPI;

      if (is_dir(GLPI_DOC_DIR."/_uploads")) {
         $uploaded_files = array();

         if ($handle = opendir(GLPI_DOC_DIR."/_uploads")) {
            while (false !== ($file = readdir($handle))) {
               if (($file != ".") && ($file != "..")) {
                  $dir = self::isValidDoc($file);
                  if (!empty($dir)) {
                     $uploaded_files[] = $file;
                  }
               }
            }
            closedir($handle);
         }

         if (count($uploaded_files)) {
            echo "<select name='$myname'>";
            echo "<option value=''>".Dropdown::EMPTY_VALUE."</option>";

            foreach ($uploaded_files as $key => $val) {
               echo "<option value='$val'>$val</option>";
            }
            echo "</select>";

         } else {
           _e('No file available');
         }

      } else {
         _e("Upload directory doesn't exist");
      }
   }


   /**
    * Is this file a valid file ? check based on file extension
    *
    * @param $filename filename to clean
   **/
   static function isValidDoc($filename) {
      global $DB;

      $splitter = explode(".",$filename);
      $ext      = end($splitter);

      $query="SELECT *
              FROM `glpi_documenttypes`
              WHERE `ext` LIKE '$ext'
                    AND `is_uploadable`='1'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            return Toolbox::strtoupper($ext);
         }
      }
      // Not found try with regex one
      $query = "SELECT *
                FROM `glpi_documenttypes`
                WHERE `ext` LIKE '/%/'
                      AND `is_uploadable` = '1'";

      foreach ($DB->request($query) as $data) {
         if (preg_match(Toolbox::unclean_cross_side_scripting_deep($data['ext'])."i",
                        $ext, $results) > 0) {
            return Toolbox::strtoupper($ext);
         }
      }

      return "";
   }

   /**
    * Make a select box for link document
    *
    * Parameters which could be used in options array :
    *    - name : string / name of the select (default is documents_id)
    *    - entity : integer or array / restrict to a defined entity or array of entities
    *                   (default -1 : no restriction)
    *    - used : array / Already used items ID: not to display in dropdown (default empty)
    *
    * @param $options array of possible options
    *
    * @return nothing (print out an HTML select box)
   **/
   static function dropdown($options=array()) {
      global $DB, $CFG_GLPI;


      $p['name']   = 'documents_id';
      $p['entity'] = '';
      $p['used']   = array();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $rand = mt_rand();

      $where = " WHERE `glpi_documents`.`is_deleted` = '0' ".
                       getEntitiesRestrictRequest("AND", "glpi_documents", '', $p['entity'], true);

      if (count($p['used'])) {
         $where .= " AND `id` NOT IN ('0','".implode("','",$p['used'])."')";
      }

      $query = "SELECT *
                FROM `glpi_documentcategories`
                WHERE `id` IN (SELECT DISTINCT `documentcategories_id`
                               FROM `glpi_documents`
                             $where)
                ORDER BY `name`";
      $result = $DB->query($query);

      echo "<select name='_rubdoc' id='rubdoc$rand'>";
      echo "<option value='0'>".Dropdown::EMPTY_VALUE."</option>";

      while ($data = $DB->fetch_assoc($result)) {
         echo "<option value='".$data['id']."'>".$data['name']."</option>";
      }
      echo "</select>";

      $params = array('rubdoc' => '__VALUE__',
                      'entity' => $p['entity'],
                      'rand'   => $rand,
                      'myname' => $p['name'],
                      'used'   => $p['used']);

      Ajax::updateItemOnSelectEvent("rubdoc$rand","show_".$p['name']."$rand",
                                    $CFG_GLPI["root_doc"]."/ajax/dropdownRubDocument.php", $params);

      echo "<span id='show_".$p['name']."$rand'>";
      $_POST["entity"] = $p['entity'];
      $_POST["rubdoc"] = 0;
      $_POST["myname"] = $p['name'];
      $_POST["rand"]   = $rand;
      $_POST["used"]   = $p['used'];
      include (GLPI_ROOT."/ajax/dropdownRubDocument.php");
      echo "</span>\n";

      return $rand;
   }

}
?>
