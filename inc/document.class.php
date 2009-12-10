<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/**
 * Document class
 */
class Document extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_documents';
   public $type = DOCUMENT_TYPE;
   public $may_be_recursive = true;
   public $entity_assign = true;

   /*
    * Retrieve an item from the database using the filename
    *
    *@param $filename filename of the document
    *@return true if succeed else false
   function getFromDBbyFilename($filename) {
      global $DB;

      $query="SELECT `id`
              FROM `".$this->table."`
              WHERE `filename`='$filename'";
      $result=$DB->query($query);
      if ($DB->numrows($result)==1) {
         return $this->getFromDB($DB->result($result,0,0));
      }
      return false;
   }
   **/

   static function getTypeName() {
      global $LANG;

      return $LANG['Menu'][27];
   }

   function cleanDBonPurge($ID) {
      global $DB,$CFG_GLPI,$LANG;

      $di = new Document_Item();
      $di->cleanDBonItemDelete($this->type,$ID);

      // UNLINK DU FICHIER
      if (!empty($this->fields["filepath"])) {
         if (is_file(GLPI_DOC_DIR."/".$this->fields["filepath"])
             && !is_dir(GLPI_DOC_DIR."/".$this->fields["filepath"])
             && countElementsInTable($this->table,"`sha1sum`='".$this->fields["sha1sum"]."'")<=1) {
            if (unlink(GLPI_DOC_DIR."/".$this->fields["filepath"])) {
               addMessageAfterRedirect($LANG['document'][24]." ".GLPI_DOC_DIR."/".
                                       $this->fields["filepath"]);
            } else {
               addMessageAfterRedirect($LANG['document'][25]." ".GLPI_DOC_DIR."/".
                                       $this->fields["filepath"],false,ERROR);
            }
         }
      }
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=array();
      if ($ID>0) {
         $ong[1]=$LANG['financial'][104];
         $ong[5]=$LANG['document'][21];
         if (haveRight("notes","r")) {
            $ong[10]=$LANG['title'][37];
         }
      } else { // New item
         $ong[1]=$LANG['title'][26];
      }
      return $ong;
   }

   function prepareInputForAdd($input) {
      global $LANG, $INFOFORM_PAGES, $CFG_GLPI, $DB;

      // security (don't accept filename from $_POST)
      unset($input['filename']);

      $input["users_id"] = $_SESSION["glpiID"];

      if (isset($input["item"]) && isset($input["itemtype"])
          && class_exists($input["itemtype"]) && $input["item"]>0) {
         $item=new $input["itemtype"]();
         $typename = $item->getTypeName();
         $name=NOT_AVAILABLE;
         if ($item->getFromDB($input["item"])) {
            $name=$item->getNameID();
         }
         $input["name"]=addslashes(resume_text($LANG['document'][18]." $typename - ".
                                               $name,200));
      }

      if (isset($input["upload_file"]) && !empty($input["upload_file"])) {
         // Move doc from upload dir
         $this->moveUploadedDocument($input,$input["upload_file"]);

      } else if (isset($_FILES) && isset($_FILES['filename'])) {
         // Move doc send with form
         $this->uploadDocument($input,$_FILES['filename']);
      }

      // Default document name
      if ((!isset($input['name']) || empty($input['name']))
          && isset($input['filename'])){
         $input['name']=$input['filename'];
      }

      unset($input["upload_file"]);

      // Don't add if no file
      if (isset($input["_only_if_upload_succeed"]) && $input["_only_if_upload_succeed"]
          && (!isset($input['filename']) || empty($input['filename']))) {
         return false;
      }

      /* Unicity check
      if (isset($input['sha1sum'])) {
         // Check if already upload in the current entity
         $crit = array('sha1sum'=>$input['sha1sum'],
                       'entities_id'=>$input['entities_id']);
         foreach ($DB->request($this->table, $crit) as $data) {
            addMessageAfterRedirect($LANG['document'][48].
               "&nbsp;: <a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$this->type]."?id=".
                     $data['id']."\">".$data['name']."</a>",
               false, ERROR, true);
            return false;
         }
      } */
      return $input;
   }

   function post_addItem($newID,$input) {
      global $LANG;

      if (isset($input["items_id"]) && isset($input["itemtype"]) && $input["items_id"] > 0
          && !empty($input["itemtype"])){

         $docitem=new Document_Item();
         $docitem->add(array('documents_id' => $newID,
                             'itemtype' => $input["itemtype"],
                             'items_id' => $input["items_id"]));
         Event::log($newID, "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG['log'][32]);
      }
   }

   function prepareInputForUpdate($input) {

      // security (don't accept filename from $_POST)
      unset($input['filename']);

      if (isset($_FILES['filename']['type']) && !empty($_FILES['filename']['type'])) {
         $input['mime']=$_FILES['filename']['type'];
      }

      if (isset($input['current_filename'])) {
         if (isset($input["upload_file"]) && !empty($input["upload_file"])) {
            $this->moveUploadedDocument($input,$input["upload_file"]);
         } else if (isset($_FILES['filename'])) {
            $this->uploadDocument($input,$_FILES['filename']);
         }
      }

      if (empty($input['filename'])) {
         unset($input['filename']);
      }
      unset($input['current_filename']);

      return $input;
   }

   /**
    * Print the document form
    *
    *@param $target form target
    *@param $ID Integer : Id of the computer or the template to print
    *@param $withtemplate='' boolean : template or basic computer
    *
    *@return Nothing (display)
    **/
   function showForm ($target,$ID,$withtemplate='') {
      global $CFG_GLPI,$LANG;

      if (!haveRight("document","r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $this->showTabs($ID, $withtemplate,getActiveTab($this->type));
      $this->showFormHeader($target,$ID,$withtemplate,2, " enctype='multipart/form-data'");

      if ($ID>0) {
         echo "<tr><th colspan='2'>";
         if ($this->fields["users_id"]>0) {
            echo $LANG['document'][42]." ".getUserName($this->fields["users_id"],1);
         } else {
            echo "&nbsp;";
         }
         echo "</th>";
         echo "<th colspan='2'>".$LANG['common'][26]."&nbsp;: ".convDateTime($this->fields["date_mod"])."</th>";
         echo "</tr>\n";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],45,
                              $this->fields["entities_id"]);
      echo "</td>";
      echo "<td rowspan='6' class='middle right'>".$LANG['common'][25].
      "&nbsp;: </td>";
      echo "<td class='center middle' rowspan='6'>.<textarea cols='45' rows='8'
         name='comment' >".$this->fields["comment"]."</textarea></td></tr>";

      if ($ID>0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['document'][22]."&nbsp;:</td>";
         echo "<td>".$this->getDownloadLink('',45);
         echo "<input type='hidden' name='current_filename' value='".$this->fields["filename"]."'>";
         echo "</td></tr>";
      }
      $max_size=return_bytes_from_ini_vars(ini_get("upload_max_filesize"));
      $max_size/=1024*1024;
      $max_size=round($max_size,1);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['document'][2]." (".$max_size." Mb max)&nbsp;:</td>";
      echo "<td><input type='file' name='filename' value=\"".
                 $this->fields["filename"]."\" size='39'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['document'][36]."&nbsp;:</td>";
      echo "<td>";
      $this->showUploadedFilesDropdown("upload_file");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['document'][33]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("link",$this->table,"link",$this->fields["link"],45,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['document'][3]."&nbsp;:</td>";
      echo "<td>";
      CommonDropdown::dropdownValue("glpi_documentcategories","documentcategories_id",
                    $this->fields["documentcategories_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['document'][4]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("mime",$this->table,"mime",$this->fields["mime"],45,
                              $this->fields["entities_id"]);
      if ($ID>0) {
         echo "</td><td>".$LANG['document'][1]."&nbsp;:</td>";
         echo "<td>".$this->fields["sha1sum"];
      }
      echo "</td></tr>";

      $this->showFormButtons($ID,$withtemplate,2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

   return true;
   }

   /**
    * Send a document to navigator
    *
    */
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
    * @param $params additonal parameters to be added to the link
    * @param $len maximum length of displayed string
    *
    **/
   function getDownloadLink($params='', $len=20){
      global $DB,$CFG_GLPI;

      $splitter=explode("/",$this->fields['filename']);
      if (count($splitter)==2) {
         // Old documents in EXT/filename
         $fileout=$splitter[1];
      } else {
         // New document
         $fileout=$this->fields['filename'];
      }

      if (utf8_strlen($fileout)>$len) {
         $fileout=utf8_substr($fileout,0,$len)."&hellip;";
      }

      $out = "<a href=\"".$CFG_GLPI["root_doc"]."/front/document.send.php?docid=".
               $this->fields['id'].$params."\" target=\"_blank\">";

      $splitter=explode("/",$this->fields['filepath']);
      if (count($splitter)) {

         $query="SELECT *
                 FROM `glpi_documenttypes`
                 WHERE `ext` LIKE '".$splitter[0]."'
                       AND `icon` <> ''";

         if ($result=$DB->query($query)) {
            if ($DB->numrows($result)>0) {
               $icon=$DB->result($result,0,'icon');
               $out .= "&nbsp;<img class='middle' style=\"margin-left:3px; margin-right:6px;\" alt='".
                               $fileout."' title='".$fileout."' src=\"".
                               $CFG_GLPI["typedoc_icon_dir"]."/$icon\" >";
            }
         }
      }
      $out.= "<strong>$fileout</strong></a>";

      return $out;
   }

   /**
    * find a document with a file attached
    *
    * @param $entity of the document
    * @param $path of the searched file
    *
    * @return boolean
    */
   function getFromDBbyContent ($entity, $path) {
      global $DB;

      $sum = sha1_file($path);
      if (!$sum) {
         return false;
      }
      $crit = array('sha1sum' => $sum,
                    'entities_id' => $entity);

      foreach ($DB->request($this->table, $crit) as $data) {
         $this->fields = $data;
         return true;
      }
      return false;
   }

   /**
    * Check is the curent user is allowed to see the file
    *
    * @return boolean
    */
   function canViewFile() {
      global $DB, $CFG_GLPI;

      if (isset($_SESSION["glpiactiveprofile"]["interface"])
          && $_SESSION["glpiactiveprofile"]["interface"]=="central") {
         // My doc Check and Common doc right access
         if ($this->can($this->fields["id"],'r') || $this->fields["users_id"]==$_SESSION["glpiID"]) {
            return true;
         }

         // Knowbase Case
         if (haveRight("knowbase","r")) {
            $query = "SELECT *
               FROM `glpi_documents_items`
               LEFT JOIN `glpi_knowbaseitems`
                      ON (`glpi_knowbaseitems`.`id` = `glpi_documents_items`.`items_id`)
               WHERE `glpi_documents_items`.`itemtype` = '".KNOWBASE_TYPE."'
                  AND `glpi_documents_items`.`documents_id`='".$this->fields["id"]."'".
                  getEntitiesRestrictRequest('AND', 'glpi_knowbaseitems', '', '', true);

            $result=$DB->query($query);
            if ($DB->numrows($result)>0) {
               return true;
            }
         }

         if (haveRight("faq","r")) {
            $query = "SELECT *
               FROM `glpi_documents_items`
               LEFT JOIN `glpi_knowbaseitems`
                      ON (`glpi_knowbaseitems`.`id` = `glpi_documents_items`.`items_id`)
               WHERE `glpi_documents_items`.`itemtype` = '".KNOWBASE_TYPE."'
                  AND `glpi_documents_items`.`documents_id`='".$this->fields["id"]."'
                  AND `glpi_knowbaseitems`.`is_faq`='1'".
                  getEntitiesRestrictRequest('AND', 'glpi_knowbaseitems', '', '', true);

            $result=$DB->query($query);
            if ($DB->numrows($result)>0) {
               return true;
            }
         }

         // Tracking Case
         if (isset($_GET["tickets_id"])) {
            $job=new Ticket;

            if ($job->can($_GET["tickets_id"],'r')) {
               $query = "SELECT *
                  FROM `glpi_documents_items`
                  WHERE `glpi_documents_items`.`items_id` = '".$_GET["tickets_id"]."'
                     AND `glpi_documents_items`.`itemtype` = '".TRACKING_TYPE."'
                     AND `documents_id`='".$this->fields["id"]."'";
               $result=$DB->query($query);
               if ($DB->numrows($result)>0) {
                  return true;
               }
            }
         }
      } else if (isset($_SESSION["glpiID"])) { // ! central

         // Check if it is my doc
         if ($this->fields["users_id"]==$_SESSION["glpiID"]) {
            return true;
         }
         if (haveRight("faq","r")) {
            // Check if it is a FAQ document
            $query = "SELECT *
               FROM `glpi_documents_items`
                  LEFT JOIN `glpi_knowbaseitems`
                         ON (`glpi_knowbaseitems`.`id` = `glpi_documents_items`.`items_id`)
               WHERE `glpi_documents_items`.`itemtype` = '".KNOWBASE_TYPE."'
                  AND `glpi_documents_items`.`documents_id`='".$this->fields["id"]."'
                  AND `glpi_knowbaseitems`.`is_faq`='1'".
                  getEntitiesRestrictRequest('AND', 'glpi_knowbaseitems', '', '', true);

            $result=$DB->query($query);
            if ($DB->numrows($result)>0) {
               return true;
            }
         }

         // Tracking Case
         if (isset($_GET["tickets_id"])) {
            $job=new Ticket;

            if ($job->can($_GET["tickets_id"],'r')) {
               $query = "SELECT *
                  FROM `glpi_documents_items`
                  WHERE `glpi_documents_items`.`items_id` = '".$_GET["tickets_id"]."'
                     AND `glpi_documents_items`.`itemtype` = '".TRACKING_TYPE."'
                     AND `documents_id`='".$this->fields["id"]."'";
               $result=$DB->query($query);
               if ($DB->numrows($result)>0) {
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
            WHERE `glpi_documents_items`.`itemtype` = '".KNOWBASE_TYPE."'
               AND `glpi_documents_items`.`documents_id`='".$this->fields["id"]."'
               AND `glpi_knowbaseitems`.`is_faq`='1'
               AND `glpi_knowbaseitems`.`entities_id`='0'
               AND `glpi_knowbaseitems`.`is_recursive`='1'";

         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            return true;
         }
      }
      return false;
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = 'glpi_documents';
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = DOCUMENT_TYPE;

      $tab[2]['table']     = 'glpi_documents';
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[3]['table']     = 'glpi_documents';
      $tab[3]['field']     = 'filename';
      $tab[3]['linkfield'] = '';
      $tab[3]['name']      = $LANG['document'][2];

      $tab[4]['table']     = 'glpi_documents';
      $tab[4]['field']     = 'link';
      $tab[4]['linkfield'] = 'link';
      $tab[4]['name']      = $LANG['document'][33];
      $tab[4]['datatype']  = 'weblink';

      $tab[5]['table']     = 'glpi_documents';
      $tab[5]['field']     = 'mime';
      $tab[5]['linkfield'] = 'mime';
      $tab[5]['name']      = $LANG['document'][4];

      $tab[16]['table']     = 'glpi_documents';
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[90]['table']     = 'glpi_documents';
      $tab[90]['field']     = 'notepad';
      $tab[90]['linkfield'] = '';
      $tab[90]['name']      = $LANG['title'][37];

      $tab[7]['table']     = 'glpi_documentcategories';
      $tab[7]['field']     = 'name';
      $tab[7]['linkfield'] = 'documentcategories_id';
      $tab[7]['name']      = $LANG['document'][3];

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      $tab[86]['table']     = 'glpi_documents';
      $tab[86]['field']     = 'is_recursive';
      $tab[86]['linkfield'] = 'is_recursive';
      $tab[86]['name']      = $LANG['entity'][9];
      $tab[86]['datatype']  = 'bool';

      $tab[19]['table']     = 'glpi_documents';
      $tab[19]['field']     = 'date_mod';
      $tab[19]['linkfield'] = '';
      $tab[19]['name']      = $LANG['common'][26];
      $tab[19]['datatype']  = 'datetime';

      return $tab;
   }
   /**
    * Show items links to a document
    *
    * @return nothing (HTML display)
    **/
   function showItems() {
      global $DB,$CFG_GLPI, $LANG,$INFOFORM_PAGES;

      $instID = $this->fields['id'];
      if (!$this->can($instID,"r")) {
         return false;
      }
      $canedit=$this->can($instID,'w');

      // for a document,
      // don't show here others documents associated to this one,
      // it's done for both directions in Document::showAssociated
      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_documents_items`
                WHERE `glpi_documents_items`.`documents_id` = '$instID'
                      AND `glpi_documents_items`.`itemtype` != '".DOCUMENT_TYPE."'
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $rand=mt_rand();
      echo "<form method='post' name='document_form$rand' id='document_form$rand' action=\"".
             $CFG_GLPI["root_doc"]."/front/document.form.php\">";

      echo "<br><br><div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='".($canedit?6:5)."'>".$LANG['document'][19]."&nbsp;:</th></tr><tr>";
      if ($canedit) {
         echo "<th>&nbsp;</th>";
      }
      echo "<th>".$LANG['common'][17]."</th>";
      echo "<th>".$LANG['common'][16]."</th>";
      echo "<th>".$LANG['entity'][0]."</th>";
      echo "<th>".$LANG['common'][19]."</th>";
      echo "<th>".$LANG['common'][20]."</th>";
      echo "</tr>";

      for ($i=0 ; $i < $number ; $i++) {
         $itemtype=$DB->result($result, $i, "itemtype");
         if (!class_exists($itemtype)) {
            continue;
         }

         if (haveTypeRight($itemtype,"r")) {
            $column="name";
            if ($itemtype==TRACKING_TYPE) {
               $column="id";
            }
            if ($itemtype==KNOWBASE_TYPE) {
               $column="question";
            }
            $itemtable=getTableForItemType($itemtype);
            $query = "SELECT `$itemtable`.*, `glpi_documents_items`.`id` AS IDD, ";
            if ($itemtype == ENTITY_TYPE) {
               // Left join because root entity not storeed
               $query .= "`glpi_documents_items`.`items_id` AS entity
                          FROM `glpi_documents_items`
                          LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id`=`glpi_documents_items`.`items_id`)
                          WHERE ";
            } else {
               $query .= "`glpi_entities`.`id` AS entity
                          FROM `glpi_documents_items`, `$itemtable`
                          LEFT JOIN `glpi_entities` ON (`glpi_entities`.`id`=`$itemtable`.`entities_id`)
                          WHERE `$itemtable`.`id` = `glpi_documents_items`.`items_id`
                          AND ";
            }
            $query .= "`glpi_documents_items`.`itemtype`='$itemtype' AND `glpi_documents_items`.`documents_id` = '$instID' "
               . getEntitiesRestrictRequest(" AND ",$itemtable,'','',isset($CFG_GLPI["recursive_type"][$itemtype]));
            if (in_array($itemtable,$CFG_GLPI["template_tables"])){
               $query.=" AND `$itemtable`.`is_template`='0'";
            }
            $query.=" ORDER BY `glpi_entities`.`completename`, `$itemtable`.`$column`";

            if ($itemtype==SOFTWARELICENSE_TYPE) {
               $soft=new Software();
            }

            if ($result_linked=$DB->query($query)) {
               if ($DB->numrows($result_linked)) {
                  $item = new $itemtype();

                  while ($data=$DB->fetch_assoc($result_linked)) {
                     $ID="";
                     if ($itemtype==ENTITY_TYPE && !$data['entity']) {
                        $data['id']=0;
                        $data['name']=$LANG['entity']['2'];
                     }
                     if ($itemtype==TRACKING_TYPE) {
                        $data["name"]=$LANG['job'][38]." ".$data["id"];
                     }
                     if ($itemtype==KNOWBASE_TYPE) {
                        $data["name"]=$data["question"];
                     }
                     if ($itemtype==SOFTWARELICENSE_TYPE) {
                        $soft->getFromDB($data['softwares_id']);
                        $data["name"]=$data["name"].' - '.$soft->fields['name'];
                     }
                     if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                        $ID= " (".$data["id"].")";
                     }
                     $name= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$itemtype]."?id=".
                              $data["id"]."\">".$data["name"]."$ID</a>";

                     echo "<tr class='tab_bg_1'>";

                     if ($canedit) {
                        echo "<td width='10'>";
                        $sel="";
                        if (isset($_GET["select"]) && $_GET["select"]=="all") {
                           $sel="checked";
                        }
                        echo "<input type='checkbox' name='item[".$data["IDD"]."]' value='1' $sel>";
                        echo "</td>";
                     }
                     echo "<td class='center'>".$item->getTypeName($itemtype)."</td>";
                     echo "<td ".(isset($data['is_deleted']) &&
                                  $data['is_deleted']?"class='tab_bg_2_2'":"").">".$name."</td>";
                     echo "<td class='center'>".CommonDropdown::getDropdownName("glpi_entities",$data['entity']);
                     echo "</td>";
                     echo "<td class='center'>".(isset($data["serial"])? "".
                                                 $data["serial"]."" :"-")."</td>";
                     echo "<td class='center'>".(isset($data["otherserial"])? "".
                                                 $data["otherserial"]."" :"-")."</td>";
                     echo "</tr>";
                  }
               }
            }
         }
      }
      if ($canedit) {
         echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
         echo "<input type='hidden' name='documents_id' value='$instID'>";
         dropdownAllItems("items_id",0,0,($this->fields['is_recursive']?-1:$this->fields['entities_id']),
                          $CFG_GLPI["doc_types"]);
         echo "</td>";
         echo "<td colspan='2' class='center'>";
         echo "<input type='submit' name='adddocumentitem' value=\"".
                $LANG['buttons'][8]."\" class='submit'>";
         echo "</td></tr>";
         echo "</table></div>" ;

         openArrowMassive("document_form$rand", true);
         closeArrowMassive('deletedocumentitem', $LANG['buttons'][6]);

      } else {
         echo "</table></div>";
      }
      echo "</form>";

   }


   /**
    * Move a file to a new location
    * Work even if dest file already exists
    *
    * @param $srce source file path
    * @param $dest destination file path
    *
    * @return boolean : success
    */
   static function renameForce ($srce, $dest) {

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
    * @param $filename filename to move
    * @param $input array of data used in adding process (need current_filename)
    *
    * @return boolean for success / $input array is updated
    *
    **/
   static function moveUploadedDocument(&$input,$filename) {
      global $CFG_GLPI,$LANG;

      /// TODO add check of $input['current_filename']

      $fullpath = GLPI_DOC_DIR."/_uploads/".$filename;

      if (!is_dir(GLPI_DOC_DIR."/_uploads")) {
         addMessageAfterRedirect($LANG['document'][35], false, ERROR);
         return false;
      }
      if (!is_file($fullpath)) {
         addMessageAfterRedirect($LANG['document'][38]."&nbsp;: ".$fullpath, false, ERROR);
         return false;
      }
      $sha1sum = sha1_file($fullpath);
      $dir = self::isValidDoc($filename);
      $new_path = self::getUploadFileValidLocationName($dir, $sha1sum);

      if (!$sha1sum || !$dir || !$new_path) {
         return false;
      }

      // Delete old file (if not used by another doc)
      if (isset($input['current_filename'])
          && !empty($input['current_filename'])
          && is_file(GLPI_DOC_DIR."/".$input['current_filename'])
          && countElementsInTable('glpi_documents',"`sha1sum`='$sha1sum'")<=1) {
         if (unlink(GLPI_DOC_DIR."/".$input['current_filename'])) {
            addMessageAfterRedirect($LANG['document'][24]." ".GLPI_DOC_DIR."/".$input['current_filename']);
         } else {
            addMessageAfterRedirect($LANG['document'][25]." ".GLPI_DOC_DIR."/".$input['current_filename'],
                                    false,ERROR);
         }
      }

      // Local file : try to detect mime type
      if (function_exists('finfo_open') && $finfo = finfo_open(FILEINFO_MIME)) {
         $input['mime'] = finfo_file($finfo, $fullpath);
         finfo_close($finfo);
      } else if (function_exists('mime_content_type')) {
         $input['mime'] = mime_content_type($fullpath);
      }

      if (is_writable(GLPI_DOC_DIR."/_uploads/") && is_writable ($fullpath)) { // Move if allowed
         if (self::renameForce($fullpath, GLPI_DOC_DIR."/".$new_path)) {
            addMessageAfterRedirect($LANG['document'][39]);
         } else {
            addMessageAfterRedirect($LANG['document'][40],false,ERROR);
            return false;
         }
      } else { // Copy (will overwrite dest file is present)
         if (copy($fullpath, GLPI_DOC_DIR."/".$new_path)) {
            addMessageAfterRedirect($LANG['document'][41]);
         } else {
            addMessageAfterRedirect($LANG['document'][40],false,ERROR);
            return false;
         }
      }

      // For display
      $input['filename'] = addslashes($filename);
      // Storage path
      $input['filepath'] = $new_path;
      // Checksum
      $input['sha1sum'] = $sha1sum;
      return true;
   }

   /**
    * Upload a new file
    *
    * @param $input data need for add/update (will be completed)
    * @param $FILEDESC FILE descriptor
    *
    * @return true on success
    **/
   static function uploadDocument(&$input,$FILEDESC) {
      global $LANG;

      if (!count($FILEDESC) || empty($FILEDESC['name']) || !is_file($FILEDESC['tmp_name'])) {
         return false;
      }

      $sha1sum = sha1_file($FILEDESC['tmp_name']);
      $dir = self::isValidDoc($FILEDESC['name']);
      $path = self::getUploadFileValidLocationName($dir,$sha1sum);
      if (!$sha1sum || !$dir || !$path) {
         return false;
      }

      // Delete old file (if not used by another doc)
      if (isset($input['current_filename'])
          && !empty($input['current_filename'])
          && countElementsInTable('glpi_documents',"`sha1sum`='$sha1sum'")<=1) {
         if (unlink(GLPI_DOC_DIR."/".$input['current_filename'])) {
            addMessageAfterRedirect($LANG['document'][24]." ".GLPI_DOC_DIR."/".$input['current_filename']);
         } else {
            addMessageAfterRedirect($LANG['document'][25]." ".GLPI_DOC_DIR."/".$input['current_filename'],
                                    false,ERROR);
         }
      }

      // Mime type from client
      if (isset($FILEDESC['type'])&& !empty($FILEDESC['type'])) {
         $input['mime'] = $FILEDESC['type'];
      }

      // Move uploaded file
      if (self::renameForce($FILEDESC['tmp_name'], GLPI_DOC_DIR."/".$path)) {
         addMessageAfterRedirect($LANG['document'][26]);
         // For display
         $input['filename'] = addslashes($FILEDESC['name']);
         // Storage path
         $input['filepath'] = $path;
         // Checksum
         $input['sha1sum'] = $sha1sum;
         return true;
      }
      addMessageAfterRedirect($LANG['document'][27],false,ERROR);
      return false;
   }

   /**
    * Find a valid path for the new file
    *
    * @param $dir dir to search a free path for the file
    * @param $sha1sum SHA1 of the file

    * @return nothing
    **/
   static function getUploadFileValidLocationName($dir,$sha1sum) {
      global $CFG_GLPI,$LANG;

      if (empty($dir)) {
         addMessageAfterRedirect($LANG['document'][32],false,ERROR);
         return '';
      }
      if (!is_dir(GLPI_DOC_DIR)) {
         addMessageAfterRedirect($LANG['document'][31]." ".GLPI_DOC_DIR,false,ERROR);
         return '';
      }
      $subdir = $dir.'/'.substr($sha1sum,0,2);
      if (!is_dir(GLPI_DOC_DIR."/".$subdir) && @mkdir(GLPI_DOC_DIR."/".$subdir,0777,true)) {
         addMessageAfterRedirect($LANG['document'][34]." ".GLPI_DOC_DIR."/".$subdir);
      }
      if (!is_dir(GLPI_DOC_DIR."/".$subdir)) {
         addMessageAfterRedirect($LANG['document'][29]." ".GLPI_DOC_DIR."/".$subdir." ".
                                 $LANG['document'][30],false,ERROR);
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
      global $CFG_GLPI,$LANG;

      if (is_dir(GLPI_DOC_DIR."/_uploads")) {
         $uploaded_files=array();
         if ($handle = opendir(GLPI_DOC_DIR."/_uploads")) {
            while (false !== ($file = readdir($handle))) {
               if ($file != "." && $file != "..") {
                  $dir = self::isValidDoc($file);
                  if (!empty($dir)) {
                     $uploaded_files[]=$file;
                  }
               }
            }
            closedir($handle);
         }

         if (count($uploaded_files)) {
            echo "<select name='$myname'>";
            echo "<option value=''>-----</option>";
            foreach ($uploaded_files as $key => $val) {
               echo "<option value=\"$val\">$val</option>";
            }
            echo "</select>";
         } else {
            echo $LANG['document'][37];
         }
      } else {
         echo $LANG['document'][35];
      }
   }

   /**
    * Is this file a valid file ? check based on file extension
    *
    * @param $filename filename to clean
    **/
   static function isValidDoc($filename) {
      global $DB;

      $splitter=explode(".",$filename);
      $ext=end($splitter);

      $query="SELECT *
              FROM `glpi_documenttypes`
              WHERE `ext` LIKE '$ext'
                    AND `is_uploadable`='1'";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            return utf8_strtoupper($ext);
         }
      }
      return "";
   }

   /**
    * Show documents associated to an item
    *
    * @param $item CommonDBTM object for which associated documents must be displayed
    * @param $withtemplate
    **/
   static function showAssociated(CommonDBTM $item, $withtemplate='') {
      global $DB, $CFG_GLPI, $LANG;

      $ID = $item->getField('id');
      if (!(($item instanceof kbItem) && $CFG_GLPI["use_public_faq"] && $item->getEntityID()==0)) {
         if (!$ID || !haveRight('document','r') || !$item->can($item->fields['id'],'r')) {
            return false;
         }
      }
      if (empty($withtemplate)) {
         $withtemplate=0;
      }
      $canedit=$item->can($ID,'w');
      $is_recursive=$item->isRecursive();

      $query = "SELECT `glpi_documents_items`.`id` AS assocID, `glpi_entities`.`id` AS entity,
                       `glpi_documents`.`name` AS assocName, `glpi_documents`.*
                FROM `glpi_documents_items`
                LEFT JOIN `glpi_documents`
                          ON (`glpi_documents_items`.`documents_id`=`glpi_documents`.`id`)
                LEFT JOIN `glpi_entities` ON (`glpi_documents`.`entities_id`=`glpi_entities`.`id`)
                WHERE `glpi_documents_items`.`items_id` = '$ID'
                      AND `glpi_documents_items`.`itemtype` = '".$item->type."' ";

      if (isset($_SESSION["glpiID"])) {
         $query .= getEntitiesRestrictRequest(" AND","glpi_documents",'','',true);
      } else {
         // Anonymous access from FAQ
         $query .= " AND `glpi_documents`.`entities_id`= '0' ";
      }
      // Document : search links in both order using union
      if ($item->type==DOCUMENT_TYPE) {
         $query .= "UNION
                    SELECT `glpi_documents_items`.`id` AS assocID, `glpi_entities`.`id` AS entity,
                           `glpi_documents`.`name` AS assocName, `glpi_documents`.*
                    FROM `glpi_documents_items`
                    LEFT JOIN `glpi_documents`
                              ON (`glpi_documents_items`.`items_id`=`glpi_documents`.`id`)
                    LEFT JOIN `glpi_entities` ON (`glpi_documents`.`entities_id`=`glpi_entities`.`id`)
                    WHERE `glpi_documents_items`.`documents_id` = '$ID'
                          AND `glpi_documents_items`.`itemtype` = '".$item->type."' ";

         if (isset($_SESSION["glpiID"])) {
            $query .= getEntitiesRestrictRequest(" AND","glpi_documents",'','',true);
         } else {
            // Anonymous access from FAQ
            $query .= " AND `glpi_documents`.`entities_id`='0' ";
         }
      }
      $query .= " ORDER BY `assocName`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;

      if ($withtemplate!=2) {
         echo "<form method='post' action=\"".
                $CFG_GLPI["root_doc"]."/front/document.form.php\" enctype=\"multipart/form-data\">";
      }
      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='7'>".$LANG['document'][21]."&nbsp;:</th></tr>";
      echo "<tr><th>".$LANG['common'][16]."</th>";
      echo "<th>".$LANG['entity'][0]."</th>";
      echo "<th>".$LANG['document'][2]."</th>";
      echo "<th>".$LANG['document'][33]."</th>";
      echo "<th>".$LANG['document'][3]."</th>";
      echo "<th>".$LANG['document'][4]."</th>";
      if ($withtemplate<2) {
         echo "<th>&nbsp;</th>";
      }
      echo "</tr>";
      $used=array();
      if ($number) {
         // Don't use this for document associated to document
         // To not loose navigation list for current document
         if ($item->type!=DOCUMENT_TYPE) {
            initNavigateListItems(DOCUMENT_TYPE, $item->getTypeName()." = ".$item->getName());
         }

         $document = new Document();
         while ($data=$DB->fetch_assoc($result)) {
            $docID=$data["id"];
            if (!$document->getFromDB($docID)) {
               continue;
            }
            if ($item->type!=DOCUMENT_TYPE) {
               addToNavigateListItems(DOCUMENT_TYPE,$docID);
            }
            $used[$docID]=$docID;
            $assocID=$data["assocID"];

            echo "<tr class='tab_bg_1".($data["is_deleted"]?"_2":"")."'>";
            echo "<td class='center'>".$document->getLink()."</td>";
            echo "<td class='center'>".CommonDropdown::getDropdownName("glpi_entities",$data['entity'])."</td>";
            echo "<td class='center'>".$document->getDownloadLink()."</td>";
            echo "<td class='center'>";
            if (!empty($data["link"])) {
               echo "<a target=_blank href='".formatOutputWebLink($data["link"])."'>".$data["link"]."</a>";
            } else {;
               echo "&nbsp;";
            }
            echo "</td>";
            echo "<td class='center'>".CommonDropdown::getDropdownName("glpi_documentcategories",
                                                       $data["documentcategories_id"])."</td>";
            echo "<td class='center'>".$data["mime"]."</td>";

            if ($withtemplate<2) {
               echo "<td class='tab_bg_2 center b'>";
               if ($canedit) {
                  echo "<a href='".$CFG_GLPI["root_doc"];
                  echo "/front/document.form.php?deletedocumentitem=1&amp;id=$assocID";
                  echo "&amp;itemtype=".$item->type."&amp;items_id=$ID&amp;documents_id=$docID'>";
                  echo $LANG['buttons'][6]."</a>";
               } else {
                  echo "&nbsp;";
               }
               echo "</td>";
            }
            echo "</tr>";
            $i++;
         }
      }

      if ($canedit) {
         // Restrict entity for knowbase
         $entities="";
         $entity=$_SESSION["glpiactive_entity"];
         if ($item->isEntityAssign()) {
            $entity=$item->getEntityID();
            if ($item->isRecursive()) {
               $entities = getSonsOf('glpi_entities',$entity);
            } else {
               $entities = $entity;
            }
         }
         $limit = getEntitiesRestrictRequest(" AND ","glpi_documents",'',$entities,true);
         $q="SELECT count(*)
             FROM `glpi_documents`
             WHERE `is_deleted`='0' $limit";

         $result = $DB->query($q);
         $nb = $DB->result($result,0,0);

         if ($withtemplate<2) {
            echo "<tr class='tab_bg_1'><td class='center' colspan='3'>";
            echo "<input type='hidden' name='entities_id' value='$entity'>";
            echo "<input type='hidden' name='is_recursive' value='$is_recursive'>";
            echo "<input type='hidden' name='itemtype' value='".$item->type."'>";
            echo "<input type='hidden' name='items_id' value='$ID'>";
            echo "<input type='file' name='filename' size='25'>&nbsp;&nbsp;";
            echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'></td>";

            if ($item->type==DOCUMENT_TYPE) {
               $used[$ID]=$ID;
            }

            if ($nb>count($used)) {
               echo "<td class='left' colspan='2'>";
               echo "<div class='software-instal'>";
               Document::dropdown("documents_id",$entities,$used);
               echo "</div></td><td class='center'>";
               echo "<input type='submit' name='adddocumentitem' value=\"".
                      $LANG['buttons'][8]."\" class='submit'>";
               echo "</td><td>&nbsp;</td>";
            } else {
               echo "<td colspan='4'>&nbsp;</td>";
            }
            echo "</tr>";
         }
      }
      echo "</table></div>"    ;
      echo "</form>";
   }

   /**
    * Make a select box for link document
    *
    * @param $myname name of the select box
    * @param $entity_restrict restrict multi entity
    * @param $used Already used items ID: not to display in dropdown
    * @return nothing (print out an HTML select box)
    */
   static function dropdown($myname,$entity_restrict='',$used=array()) {
      global $DB,$LANG,$CFG_GLPI;

      $rand=mt_rand();

      $where=" WHERE `glpi_documents`.`is_deleted`='0' ".
                     getEntitiesRestrictRequest("AND","glpi_documents",'',$entity_restrict,true);
      if (count($used)) {
         $where .= " AND `id` NOT IN ('0','".implode("','",$used)."')";
      }

      $query="SELECT *
              FROM `glpi_documentcategories`
              WHERE `id` IN (SELECT DISTINCT `documentcategories_id`
                             FROM `glpi_documents`
                             $where)
              ORDER BY `name`";
      $result=$DB->query($query);

      echo "<select name='_rubdoc' id='rubdoc$rand'>";
      echo "<option value='0'>------</option>";
      while ($data=$DB->fetch_assoc($result)) {
         echo "<option value='".$data['id']."'>".$data['name']."</option>";
      }
      echo "</select>";

      $params=array('rubdoc'=>'__VALUE__',
                    'entity_restrict'=>$entity_restrict,
                    'rand'=>$rand,
                    'myname'=>$myname,
                    'used'=>$used);

      ajaxUpdateItemOnSelectEvent("rubdoc$rand","show_$myname$rand",$CFG_GLPI["root_doc"].
                                  "/ajax/dropdownRubDocument.php",$params);

      echo "<span id='show_$myname$rand'>";
      $_POST["entity_restrict"]=$entity_restrict;
      $_POST["rubdoc"]=0;
      $_POST["myname"]=$myname;
      $_POST["rand"]=$rand;
      $_POST["used"]=$used;
      include (GLPI_ROOT."/ajax/dropdownRubDocument.php");
      echo "</span>\n";

      return $rand;
   }

}

?>
