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
 * Show documents associated to an item
 *
 * @param $itemtype item type
 * @param $ID item ID
 * @param $withtemplate if 3 -> view via helpdesk -> no links
 **/
function showDocumentAssociated($itemtype,$ID,$withtemplate='') {
   global $DB, $CFG_GLPI, $LANG, $LINK_ID_TABLE;

   if ($itemtype!=KNOWBASE_TYPE) {
      if (!haveRight("document","r") || !haveTypeRight($itemtype,"r")) {
         return false;
      }
   }

   if (empty($withtemplate)) {
      $withtemplate=0;
   }
   $ci=new CommonItem();
   $ci->getFromDB($itemtype,$ID);
   $canread=$ci->obj->can($ID,'r');
   $canedit=$ci->obj->can($ID,'w');
   $is_recursive=0;
   if ($ci->getField('is_recursive')) {
      $is_recursive=1;
   }

   $query = "SELECT `glpi_documents_items`.`id` AS assocID, `glpi_entities`.`id` AS entity,
                    `glpi_documents`.`name` AS assocName, `glpi_documents`.*
             FROM `glpi_documents_items`
             LEFT JOIN `glpi_documents`
                       ON (`glpi_documents_items`.`documents_id`=`glpi_documents`.`id`)
             LEFT JOIN `glpi_entities` ON (`glpi_documents`.`entities_id`=`glpi_entities`.`id`)
             WHERE `glpi_documents_items`.`items_id` = '$ID'
                   AND `glpi_documents_items`.`itemtype` = '$itemtype' ";

   if (isset($_SESSION["glpiID"])) {
      $query .= getEntitiesRestrictRequest(" AND","glpi_documents",'','',true);
   } else {
      // Anonymous access from FAQ
      $query .= " AND `glpi_documents`.`entities_id`= '0' ";
   }
   // Document : search links in both order using union
   if ($itemtype==DOCUMENT_TYPE) {
      $query .= "UNION
                 SELECT `glpi_documents_items`.`id` AS assocID, `glpi_entities`.`id` AS entity,
                        `glpi_documents`.`name` AS assocName, `glpi_documents`.*
                 FROM `glpi_documents_items`
                 LEFT JOIN `glpi_documents`
                           ON (`glpi_documents_items`.`items_id`=`glpi_documents`.`id`)
                 LEFT JOIN `glpi_entities` ON (`glpi_documents`.`entities_id`=`glpi_entities`.`id`)
                 WHERE `glpi_documents_items`.`documents_id` = '$ID'
                       AND `glpi_documents_items`.`itemtype` = '$itemtype' ";

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
      if ($itemtype!=DOCUMENT_TYPE) {
         initNavigateListItems(DOCUMENT_TYPE,$ci->getType()." = ".$ci->getName());
      }

      $document = new Document();
      while ($data=$DB->fetch_assoc($result)) {
         $docID=$data["id"];
         if (!$document->getFromDB($docID)) {
            continue;
         }
         if ($itemtype!=DOCUMENT_TYPE) {
            addToNavigateListItems(DOCUMENT_TYPE,$docID);
         }
         $used[$docID]=$docID;
         $assocID=$data["assocID"];

         echo "<tr class='tab_bg_1".($data["is_deleted"]?"_2":"")."'>";
         echo "<td class='center'>".$document->getLink()."</td>";
         echo "<td class='center'>".getDropdownName("glpi_entities",$data['entity'])."</td>";
         echo "<td class='center'>".$document->getDownloadLink()."</td>";
         echo "<td class='center'>";
         if (!empty($data["link"])) {
            echo "<a target=_blank href='".formatOutputWebLink($data["link"])."'>".$data["link"]."</a>";
         } else {;
            echo "&nbsp;";
         }
         echo "</td>";
         echo "<td class='center'>".getDropdownName("glpi_documentscategories",
                                                    $data["documentscategories_id"])."</td>";
         echo "<td class='center'>".$data["mime"]."</td>";

         if ($withtemplate<2) {
            echo "<td class='tab_bg_2 center'>";
            if ($canedit) {
               echo "<a href='".
                      $CFG_GLPI["root_doc"]."/front/document.form.php?deletedocumentitem=1&amp;"
                      ."id=$assocID&amp;itemtype=$itemtype&amp;items_id=$ID&amp;documents_id=$docID'>
                      <strong>".$LANG['buttons'][6]."</strong></a>";
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
      if ($ci->obj->isEntityAssign()) {
         $entity=$ci->obj->getEntityID();
         if ($ci->obj->isRecursive()) {
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
         echo "<input type='hidden' name='itemtype' value='$itemtype'>";
         echo "<input type='hidden' name='items_id' value='$ID'>";
         echo "<input type='file' name='filename' size='25'>&nbsp;&nbsp;";
         echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'></td>";

         if ($itemtype==DOCUMENT_TYPE) {
            $used[$ID]=$ID;
         }

         if ($nb>count($used)) {
            echo "<td class='left' colspan='2'>";
            echo "<div class='software-instal'>";
            dropdownDocument("documents_id",$entities,$used);
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


?>
