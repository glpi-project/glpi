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

   /**
    * Constructor
   **/
   function __construct () {
      $this->table="glpi_documents";
      $this->type=DOCUMENT_TYPE;
      $this->entity_assign=true;
      $this->may_be_recursive=true;
   }

   /**
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

   function cleanDBonPurge($ID) {
      global $DB,$CFG_GLPI,$LANG;

      $di = new DocumentItem();
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

      $input["users_id"] = $_SESSION["glpiID"];

      if (isset($_FILES['filename']['type'] )&& !empty($_FILES['filename']['type'])) {
         $input['mime']=$_FILES['filename']['type'];
      }

      if (isset($input["item"]) && isset($input["itemtype"])
          && $input["itemtype"]>0 && $input["item"]>0) {
         $ci=new CommonItem();
         $ci->getFromDB($input["itemtype"],$input["item"]);
         $input["name"]=addslashes(resume_text($LANG['document'][18]." ".$ci->getType()." - ".
                                               $ci->getNameID(),200));
      }

      if (isset($input["upload_file"]) && !empty($input["upload_file"])) {
         $input['filename']=moveUploadedDocument($input["upload_file"]);

      } else if (isset($_FILES) && isset($_FILES['filename'])) {
         uploadDocument($input,$_FILES['filename']);
      }

      if ((!isset($input['name']) || empty($input['name']))
          && isset($input['filename'])){
         $input['name']=$input['filename'];
      }

      unset($input["upload_file"]);
      if (isset($input["_only_if_upload_succeed"]) && $input["_only_if_upload_succeed"]
          && empty($input['filename'])) {
         return false;
      }
      if (isset($input['sha1sum'])) {
         // Check if already upload in the current entity
         $crit = array('sha1sum'=>$input['sha1sum'],
                       'entities_id'=>$input['entities_id']);
         foreach ($DB->request($this->table, $crit) as $data) {
            addMessageAfterRedirect($LANG['document'][48].
               "&nbsp;: <a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$this->type]."?id=".
                     $data['id']."\">".$data['name']."</a>",
               false, ERROR);
            return false;
         }
      }
      return $input;
   }

   function post_addItem($newID,$input) {
      global $LANG;

      if (isset($input["items_id"]) && isset($input["itemtype"]) && $input["items_id"] > 0
          && $input["itemtype"] > 0){

         $docitem=new DocumentItem();
         $docitem->add(array('documents_id' => $newID,
                             'itemtype' => $input["itemtype"],
                             'items_id' => $input["items_id"]));
         logEvent($newID, "documents", 4, "document", $_SESSION["glpiname"]." ".$LANG['log'][32]);
      }
   }

   function prepareInputForUpdate($input) {

      if (isset($_FILES['filename']['type']) && !empty($_FILES['filename']['type'])) {
         $input['mime']=$_FILES['filename']['type'];
      }

      if (isset($input['current_filename'])) {
         if (isset($input["upload_file"]) && !empty($input["upload_file"])) {
            $input['filename']=moveUploadedDocument($input["upload_file"],$input['current_filename']);
         } else {
            uploadDocument($input,$_FILES['filename']);
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

      $this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);
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
      showUploadedFilesDropdown("upload_file");
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
      dropdownValue("glpi_documentscategories","documentscategories_id",
                    $this->fields["documentscategories_id"]);
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
                 FROM `glpi_documentstypes`
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


}

// Relation between Documents and Items
class DocumentItem extends CommonDBRelation{

   /**
    * Constructor
    **/
   function __construct () {
      $this->table = 'glpi_documents_items';
      $this->type = DOCUMENTITEM_TYPE;

      $this->itemtype_1 = DOCUMENT_TYPE;
      $this->items_id_1 = 'documents_id';

      $this->itemtype_2 = 'itemtype';
      $this->items_id_2 = 'items_id';
   }

}
?>
