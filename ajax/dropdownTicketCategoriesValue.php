<?php
/*
 * @version $Id: dropdownValue.php 9968 2009-12-28 14:15:18Z moyo $
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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"dropdownTicketCategoriesValue.php")) {
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

checkLoginUser();

// Security
if (!class_exists($_POST['itemtype']) ) {
   exit();
}
$item = new $_POST['itemtype']();
$table = $item->getTable();

// No define value
if (!isset($_POST['value'])) {
   $_POST['value']='';
}
// No define rand
if (!isset($_POST['rand'])) {
   $_POST['rand']=mt_rand();
}


if (isset($_POST["entity_restrict"]) && !is_numeric($_POST["entity_restrict"])
    && !is_array($_POST["entity_restrict"])) {

   $_POST["entity_restrict"]=unserialize(stripslashes($_POST["entity_restrict"]));
}

// Make a select box with preselected values
if (!isset($_POST["limit"])) {
   $_POST["limit"]=$_SESSION["glpidropdown_chars_limit"];
}

$where="WHERE 1 ";

$NBMAX=$CFG_GLPI["dropdown_max"];
$LIMIT="LIMIT 0,$NBMAX";
if ($_POST['searchText']==$CFG_GLPI["ajax_wildcard"]) {
   $LIMIT="";
}


if ($item instanceof CommonTreeDropdown) {
   if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]) {
      $where.=" AND `completename` ".makeTextSearch($_POST['searchText']);
   }
   $multi=false;

   // Manage multiple Entities dropdowns
   $add_order="";
   if ($item->isEntityAssign()) {
      $recur=$item->maybeRecursive();

      if (isset($_POST["entity_restrict"]) && !($_POST["entity_restrict"]<0)) {
         $where.=getEntitiesRestrictRequest(" AND ", $table, '',
                                            $_POST["entity_restrict"],$recur);
         if (is_array($_POST["entity_restrict"]) && count($_POST["entity_restrict"])>1) {
            $multi=true;
         }
      } else {
         $where.=getEntitiesRestrictRequest(" AND ",$table,'', '', $recur);
         if (count($_SESSION['glpiactiveentities'])>1) {
            $multi=true;
         }
      }

      // Force recursive items to multi entity view
      if ($recur) {
         $multi=true;
         $add_order='entities_id, ';
      }

   }

   $query = "SELECT *
             FROM `$table`
             $where
             ORDER BY $add_order `completename`
             $LIMIT";
//   echo $query;
   if ($result = $DB->query($query)) {

      echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name=\"".
            $_POST['myname']."\" size='1'";
      if (isset($_POST["auto_submit"]) && $_POST["auto_submit"]==1) {
         echo " onChange='submit()'";
      }
      echo ">";

      if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"] && $DB->numrows($result)==$NBMAX) {
         echo "<option class='tree' value='0'>--".$LANG['common'][11]."--</option>";
      }
      $display_selected=true;
      echo "<option class='tree' value='0'>-----</option>";

      if ($display_selected) {
         $outputval=Dropdown::getDropdownName($table,$_POST['value']);
         if (!empty($outputval) && $outputval!="&nbsp;") {
            if (utf8_strlen($outputval)>$_POST["limit"]) {
               // Completename for tree dropdown : keep right
               $outputval = "&hellip;".utf8_substr($outputval,-$_POST["limit"]);
            }
            if ($_SESSION["glpiis_ids_visible"] || empty($outputval)) {
               $outputval.=" (".$_POST['value'].")";
            }
            echo "<option class='tree' selected value='".$_POST['value']."'>".$outputval."</option>";
         }
      }

      $tohide = array();

      if ($DB->numrows($result)) {
         $prev=-1;
         while ($data =$DB->fetch_array($result)) {
            $ID = $data['id'];
            $level = $data['level'];
            $output=$data['name'];

           $hide = false;
           if ($_POST['helpdesk_restrict'] && (!in_array($data['id'],$tohide)
               && !$data['is_helpdeskvisible'])
                  || ($data['ticketcategories_id'] && in_array($data['ticketcategories_id'],$tohide))) {
               $tohide[] = $ID;
               $hide = true;
            }

            if ($multi && $data["entities_id"]!=$prev) {
                     if ($prev>=0) {
                           echo "</optgroup>";
                     }
                     $prev=$data["entities_id"];
                     echo "<optgroup label=\"". Dropdown::getDropdownName("glpi_entities", $prev) ."\">";
            }

            if (!$hide) {

               $class=" class='tree' ";
               $raquo="&raquo;";
               if ($level==1) {
                  $class=" class='treeroot'";
                  $raquo="";
               }
               if ($_SESSION['glpiuse_flat_dropdowntree']) {
                  $output=$data['completename'];
                  if ($level>1) {
                     $class="";
                     $raquo="";
                     $level=0;
                  }
               }
               if (utf8_strlen($output)>$_POST["limit"]) {
                  if ($_SESSION['glpiuse_flat_dropdowntree']) {
                     $output="&hellip;".utf8_substr($output,-$_POST["limit"]);
                  } else {
                     $output=utf8_substr($output,0,$_POST["limit"])."&hellip;";
                  }
               }
               if ($_SESSION["glpiis_ids_visible"] || empty($output)) {
                  $output.=" ($ID)";
               }
               $style=$class;
               $addcomment="";
               if (isset($data["comment"])) {
                  $addcomment=" - ".$data["comment"];
               }
               echo "<option value='$ID' $style title=\"".cleanInputText($data['completename'].
                     $addcomment)."\">".str_repeat("&nbsp;&nbsp;&nbsp;", $level).$raquo.$output."</option>";
            }
         }
         if ($multi) {
            echo "</optgroup>";
         }
      }
      echo "</select>";
   }
}

if (isset($_POST["comment"]) && $_POST["comment"]) {
   $paramscomment=array('value' => '__VALUE__',
                        'table' => $table);
   ajaxUpdateItemOnSelectEvent("dropdown_".$_POST["myname"].$_POST["rand"],"comment_".
                               $_POST["myname"].$_POST["rand"],
                               $CFG_GLPI["root_doc"]."/ajax/comments.php",$paramscomment,false);
}

if (isset($_POST["update_item"])
    && (is_array($_POST["update_item"]) || strlen($_POST["update_item"])>0)) {

   if (!is_array($_POST["update_item"])) {
      $data=unserialize(stripslashes($_POST["update_item"]));
   } else {
      $data=$_POST["update_item"];
   }
   if (is_array($data) && count($data)) {
      $paramsupdate=array();
      if (isset($data['value_fieldname'])) {
         $paramsupdate=array($data['value_fieldname']=>'__VALUE__');
      }
      if (isset($data["moreparams"]) && is_array($data["moreparams"]) && count($data["moreparams"])) {
         foreach ($data["moreparams"] as $key => $val) {
            $paramsupdate[$key]=$val;
         }
      }
      ajaxUpdateItemOnSelectEvent("dropdown_".$_POST["myname"].$_POST["rand"],$data['to_update'],
                                  $data['url'],$paramsupdate,false);
   }
}

?>
