<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

   static $rightname                   = 'document';
   static $tag_prefix                  = '#';
   protected $usenotepad               = true;


   static function getTypeName($nb=0) {
      return _n('Document', 'Documents', $nb);
   }


   /**
    * Check if given object can have Document
    *
    * @since version 0.85
    *
    * @param $item  an object or a string
    *
    * @return true if $object is an object that can have InfoCom
   **/
   static function canApplyOn($item) {
      global $CFG_GLPI;

      // All devices are subjects to infocom !
      if (Toolbox::is_a($item, 'Item_Devices')
          || Toolbox::is_a($item, 'CommonDevice')) {
         return true;
      }

      // We also allow direct items to check
      if ($item instanceof CommonGLPI) {
         $item = $item->getType();
      }

      if (in_array($item, $CFG_GLPI['document_types'])){
         return true;
      }

      return false;
   }


   /**
    * Get all the types that can have a document
    *
    * @since version 0.85
    *
    * @return array of the itemtypes
   **/
   static function getItemtypesThatCanHave() {
      global $CFG_GLPI;

      return array_merge($CFG_GLPI['document_types'],
                         CommonDevice::getDeviceTypes(),
                         Item_Devices::getDeviceTypes());
   }


   /**
    * @see CommonGLPI::getMenuShorcut()
    *
    * @since version 0.85
   **/
   static function getMenuShorcut() {
      return 'd';
   }


   static function canCreate() {

      // Have right to add document OR ticket followup
      return (Session::haveRight('document', CREATE)
              || Session::haveRight('followup', TicketFollowup::ADDMYTICKET));
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

      if (Document::canCreate()) {
         return parent::canCreateItem();
      }
      return false;
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
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
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

      $upload_ok = false;
      if (isset($input["_filename"]) && !(empty($input["_filename"]) == 1)) {
         $upload_ok = $this->moveDocument($input, stripslashes(array_shift($input["_filename"])));
      } else if (isset($input["upload_file"]) && !empty($input["upload_file"])) {
         // Move doc from upload dir
         $upload_ok = $this->moveUploadedDocument($input, $input["upload_file"]);
      }

      // Tag
      if (isset($input["_tag_filename"]) && !empty($input["_tag_filename"]) == 1) {
         $input['tag'] = array_shift($input["_tag_filename"]);
      }

      if (!isset($input["tag"]) || empty($input["tag"])) {
         $input['tag'] = Rule::getUuid();
      }

      // Upload failed : do not create document
      if ($create_from_item && !$upload_ok) {
         return false;
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
      if (isset($input['itemtype']) && ($input['itemtype'] == 'Ticket')
          && (!isset($input['documentcategories_id']) || ($input['documentcategories_id'] == 0))) {
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

      if (isset($input['current_filepath'])) {
         if (isset($input["_filename"]) && !empty($input["_filename"]) == 1) {
            $this->moveDocument($input, stripslashes(array_shift($input["_filename"])));
         } else if (isset($input["upload_file"]) && !empty($input["upload_file"])) {
            // Move doc from upload dir
            $this->moveUploadedDocument($input, $input["upload_file"]);
         }
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
//       $options['formoptions'] = " enctype='multipart/form-data'";
      $this->showFormHeader($options);

      $showuserlink = 0;
      if (Session::haveRight('user', READ)) {
         $showuserlink = 1;
      }
      if ($ID > 0) {
         echo "<tr><th colspan='2'>";
         if ($this->fields["users_id"]>0) {
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
      if ($ID > 0) {
         echo "<td>".__('Current file')."</td>";
         echo "<td>".$this->getDownloadLink('',45);
         echo "<input type='hidden' name='current_filepath' value='".$this->fields["filepath"]."'>";
         echo "<input type='hidden' name='current_filename' value='".$this->fields["filename"]."'>";
         echo "</td>";
      } else {
         echo "<td colspan=2>&nbsp;</td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Heading')."</td>";
      echo "<td>";
      DocumentCategory::dropdown(array('value' => $this->fields["documentcategories_id"]));
      echo "</td>";
      if ($ID > 0) {
         echo "<td>".sprintf(__('%1$s (%2$s)'), __('Checksum'), __('SHA1'))."</td>";
         echo "<td>".$this->fields["sha1sum"];
         echo "</td>";
      } else {
         echo "<td colspan=2>&nbsp;</td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Web Link')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "link");
      echo "</td>";
      echo "<td rowspan='3' class='middle'>".__('Comments')."</td>";
      echo "<td class='middle' rowspan='3'>";
      echo "<textarea cols='45' rows='6' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('MIME type')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "mime");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Blacklisted for import')."</td>";
      echo "<td>";
      Dropdown::showYesNo("is_blacklisted", $this->fields["is_blacklisted"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Use a FTP installed file')."</td>";
      echo "<td>";
      $this->showUploadedFilesDropdown("upload_file");
      echo "</td>";

      echo "<td>".sprintf(__('%1$s (%2$s)'), __('File'), self::getMaxUploadSize())."</td>";
      echo "<td>";
      echo Html::file();
      echo "</td></tr>";

      $this->showFormButtons($options);

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

      $out   = '';
      $open  = '';
      $close = '';
      if (self::canView()
          || self::canViewFile(array('tickets_id' =>$this->fields['tickets_id']))) {
         $open  = "<a href='".$CFG_GLPI["root_doc"]."/front/document.send.php?docid=".
                    $this->fields['id'].$params."' alt=\"".$initfileout."\"
                    title=\"".$initfileout."\"target='_blank'>";
         $close = "</a>";
      }
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
      $out .= "$open<span class='b'>$fileout</span>$close";

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
         if ($this->can($this->fields["id"], READ)
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
         if (Session::haveRight("knowbase", READ)) {
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

         if (Session::haveRight('knowbase', KnowbaseItem::READFAQ)) {
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

            if ($job->can($options["tickets_id"], READ)) {
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

         if (Session::haveRight('knowbase', KnowbaseItem::READFAQ)) {
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

            if ($job->can($options["tickets_id"], READ)) {
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
      $tab['document']           = self::getTypeName(Session::getPluralNumber());

      $tab[119]['table']         = 'glpi_documents_items';
      $tab[119]['field']         = 'id';
      $tab[119]['name']          = _x('quantity', 'Number of documents');
      $tab[119]['forcegroupby']  = true;
      $tab[119]['usehaving']     = true;
      $tab[119]['datatype']      = 'count';
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
         MassiveAction::getAddTransferList($actions);
      }

      return $actions;
   }


   function getSearchOptions() {
      global $CFG_GLPI;

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

      if ($CFG_GLPI['use_rich_text']) {
         $tab[6]['table']           = $this->getTable();
         $tab[6]['field']           = 'tag';
         $tab[6]['name']            = __('Tag');
         $tab[6]['datatype']        = 'text';
         $tab[6]['massiveaction']   = false;
      }

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
      $tab[72]['field']          = 'id';
      $tab[72]['name']           = _x('quantity', 'Number of associated items');
      $tab[72]['forcegroupby']   = true;
      $tab[72]['usehaving']      = true;
      $tab[72]['datatype']       = 'count';
      $tab[72]['massiveaction']  = false;
      $tab[72]['joinparams']     = array('jointype' => 'child');

      $tab += Notepad::getSearchOptionsToAdd();

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

      $fullpath = GLPI_UPLOAD_DIR."/".$filename;

      if (!is_dir(GLPI_UPLOAD_DIR)) {
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
      $input['mime'] = Toolbox::getMime($fullpath);

      if (is_writable(GLPI_UPLOAD_DIR)
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
    * Move a document (files in GLPI_DOC_DIR."/_tmp" dir)
    *
    * @param $input     array of datas used in adding process (need current_filepath)
    * @param $filename        filename to move
    *
    * @return boolean for success / $input array is updated
   **/
   static function moveDocument(array &$input, $filename) {
      global $CFG_GLPI;

      $fullpath = GLPI_TMP_DIR."/".$filename;
      if (!is_dir(GLPI_TMP_DIR)) {
         Session::addMessageAfterRedirect(__("Temporary directory doesn't exist"), false, ERROR);
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
      $input['mime'] = Toolbox::getMime($fullpath);

      if (is_writable(GLPI_TMP_DIR)
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

         if (Session::haveRight('dropdown', READ)) {
            $dt       = new DocumentType();
            $message .= " <a target='_blank' href='".$dt->getSearchURL()."'>
                         <img src=\"".$CFG_GLPI["root_doc"]."/pics/info-small.png\" class='pointer'></a>";
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

      if (is_dir(GLPI_UPLOAD_DIR)) {
         $uploaded_files = array('' => Dropdown::EMPTY_VALUE);

         if ($handle = opendir(GLPI_UPLOAD_DIR)) {
            while (false !== ($file = readdir($handle))) {
               if (($file != ".") && ($file != "..")) {
                  $dir = self::isValidDoc($file);
                  if (!empty($dir)) {
                     $uploaded_files[$file] = $file;
                  }
               }
            }
            closedir($handle);
         }

         if (count($uploaded_files) >1) {
            Dropdown::showFromArray($myname, $uploaded_files);
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


      $p['name']    = 'documents_id';
      $p['entity']  = '';
      $p['used']    = array();
      $p['display'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $where = " WHERE `glpi_documents`.`is_deleted` = '0' ".
                       getEntitiesRestrictRequest("AND", "glpi_documents", '', $p['entity'], true);

      if (count($p['used'])) {
         $where .= " AND `id` NOT IN (0, ".implode(",",$p['used']).")";
      }

      $query = "SELECT *
                FROM `glpi_documentcategories`
                WHERE `id` IN (SELECT DISTINCT `documentcategories_id`
                               FROM `glpi_documents`
                             $where)
                ORDER BY `name`";
      $result = $DB->query($query);

      $values = array(0 => Dropdown::EMPTY_VALUE);

      while ($data = $DB->fetch_assoc($result)) {
         $values[$data['id']] = $data['name'];
      }
      $rand = mt_rand();
      $out  = Dropdown::showFromArray('_rubdoc', $values, array('width'   => '30%',
                                                                'rand'    => $rand,
                                                                'display' => false));
      $field_id = Html::cleanId("dropdown__rubdoc$rand");

      $params   = array('rubdoc' => '__VALUE__',
                        'entity' => $p['entity'],
                        'rand'   => $rand,
                        'myname' => $p['name'],
                        'used'   => $p['used']);

      $out .= Ajax::updateItemOnSelectEvent($field_id,"show_".$p['name'].$rand,
                                            $CFG_GLPI["root_doc"]."/ajax/dropdownRubDocument.php",
                                            $params, false);
      $out .= "<span id='show_".$p['name']."$rand'>";
      $out .= "</span>\n";

      $params['rubdoc'] = 0;
      $out .= Ajax::updateItem("show_".$p['name'].$rand,
                               $CFG_GLPI["root_doc"]. "/ajax/dropdownRubDocument.php",
                               $params, false);
      if ($p['display']) {
         echo $out;
         return $rand;
      }
      return $out;
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::getMassiveActionsForItemtype()
   **/
   static function getMassiveActionsForItemtype(array &$actions, $itemtype, $is_deleted=0,
                                                CommonDBTM $checkitem=NULL) {
      global $CFG_GLPI;

      $action_prefix = 'Document_Item'.MassiveAction::CLASS_ACTION_SEPARATOR;

      if (self::canApplyOn($itemtype)) {
         if (Document::canView()) {
            $actions[$action_prefix.'add']    = _x('button', 'Add a document');
            $actions[$action_prefix.'remove'] = _x('button', 'Remove a document');
         }
      }

      if ((Toolbox::is_a($itemtype, __CLASS__)) && (static::canUpdate())) {
         $actions[$action_prefix.'add_item']    = _x('button', 'Add an item');
         $actions[$action_prefix.'remove_item'] = _x('button', 'Remove an item');
      }
   }


   /**
    * @since version 0.85
    *
    * @param $string
    *
    * @return string
   **/
   static function getImageTag($string){
      return self::$tag_prefix.$string.self::$tag_prefix;
   }

}
?>
