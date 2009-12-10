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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// CLASSE knowledgebase

class KnowbaseItem extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_knowbaseitems';
   public $type = KNOWBASE_TYPE;
   public $may_be_recursive=true;
   public $entity_assign=true;

   static function getTypeName() {
      global $LANG;

      return $LANG['title'][5];
   }

   /**
    * Get The Name of the Object
    *
    * @param $with_comment add comments to name (not used for this type)
    * @return String: name of the object in the current language
    */
   function getName($with_comment=0) {
      if (isset($this->fields["question"])
          && !empty($this->fields["question"])) {
         return $this->fields["question"];
      }
      return NOT_AVAILABLE;
   }

   /**
    * Actions done at the end of the getEmpty function
    *
    *@return nothing
    *
    **/
   function post_getEmpty () {

      if (haveRight("faq","w") && !haveRight("knowbase","w")) {
         $this->fields["is_faq"]=1;
      }
   }

   /**
    * Have I the right to "create" the Object
    *
    * overloaded function of CommonDBTM
    *
    * @return booleen
    **/
   function canCreate () {
      return (haveRight("faq", "w") || haveRight("knowbase", "w"));
   }

   function prepareInputForAdd($input) {
      global $LANG;

      // set new date.
      $input["date"] = $_SESSION["glpi_currenttime"];
      // set users_id

      // set title for question if empty
      if(empty($input["question"])) {
         $input["question"]=$LANG['common'][30];
      }

      if (haveRight("faq","w") && !haveRight("knowbase","w")) {
         $input["is_faq"]=1;
      }
      if (!haveRight("faq","w") && haveRight("knowbase","w")) {
         $input["is_faq"]=0;
      }
      return $input;
   }

   function prepareInputForUpdate($input) {
      global $LANG;

      // set title for question if empty
      if (empty($input["question"])) {
         $input["question"]=$LANG['common'][30];
      }
      return $input;
   }

   /**
   * Print out an HTML "<form>" for knowbase item
   *
   * @param $target
   * @param $ID
   * @return nothing (display the form)
   **/
   function showForm($target,$ID) {
      global $LANG, $CFG_GLPI;

      // show kb item form
      if (!haveRight("knowbase","w" ) && !haveRight("faq","w")) {
         return false;
      }
      if ($ID >0) {
         $this->check($ID,'r');
      } else {
        $this->check(-1,'w');
        $this->getEmpty();
      }

      $canedit=$this->can($ID,'w');
      $canrecu=$this->can($ID,'recursive');

      if($canedit) {
         echo "<div id='contenukb'>";
         echo "<script type='text/javascript' src='".$CFG_GLPI["root_doc"].
               "/lib/tiny_mce/tiny_mce.js'></script>";
         echo "<script language='javascript' type='text/javascript''>";
         echo "tinyMCE.init({
            language : '".$CFG_GLPI["languages"][$_SESSION['glpilanguage']][3]."',
            mode : 'exact',
            elements: 'answer',
            plugins : 'table,directionality,paste,safari,searchreplace',
            theme : 'advanced',
            entity_encoding : 'numeric', ";
            // directionality + search replace plugin
         echo "theme_advanced_buttons1_add : 'ltr,rtl,search,replace',";
         echo "theme_advanced_toolbar_location : 'top',
            theme_advanced_toolbar_align : 'left',
            theme_advanced_buttons1 : 'bold,italic,underline,strikethrough,fontsizeselect,formatselect,separator,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,outdent,indent',
            theme_advanced_buttons2 : 'forecolor,backcolor,separator,hr,separator,link,unlink,anchor,separator,tablecontrols,undo,redo,cleanup,code,separator',
            theme_advanced_buttons3 : ''});";
         echo "</script>";

         echo "<form method='post' id='form_kb' name='form_kb' action=\"$target\">";

         if (!empty($ID)) {
            echo "<input type='hidden' name='id' value=\"$ID\">\n";
         }

         echo "<fieldset>";
         echo "<legend>".$LANG['knowbase'][13]."</legend>";
         echo "<div class='center'>".$LANG['knowbase'][6];
         Dropdown::dropdownValue("glpi_knowbaseitemcategories","knowbaseitemcategories_id",
                       $this->fields["knowbaseitemcategories_id"]);
         echo "</div></fieldset>";

         echo "<fieldset>";
         echo "<legend>".$LANG['knowbase'][14]."</legend>";
         echo "<div class='center'><textarea cols='80' rows='2' name='question' >".
                  $this->fields["question"]."</textarea></div>";
         echo "</fieldset>";

         echo "<fieldset>";
         echo "<legend>".$LANG['knowbase'][15]."</legend>";
         echo "<div class='center'><textarea cols='80' rows='30' id='answer' name='answer' >".
                  $this->fields["answer"]."</textarea></div>";
         echo "</fieldset>";

         echo "<br>";

         if (!empty($ID)) {
            echo "<fieldset>";
            echo "<div class='baskb'>";
            if ($this->fields["users_id"]) {
               echo $LANG['common'][37]."&nbsp;: ".getUserName($this->fields["users_id"],"1")."      ";
            }

            echo "<span class='baskb_right'>";
            if ($this->fields["date_mod"]) {
               echo $LANG['common'][26]."&nbsp;: ".convDateTime($this->fields["date_mod"])."     ";
            }
            echo "</span><br>";

            if ($this->fields["date"]) {
               echo $LANG['common'][27]."&nbsp;: ". convDateTime($this->fields["date"]);
            }

            echo "<span class='baskb_right'>";
            echo $LANG['knowbase'][26]."&nbsp;: ".$this->fields["view"]."</span></div>";

            echo "</fieldset>";
         }
         echo "<p class='center'>";

         if (isMultiEntitiesMode()) {
            echo $LANG['entity'][0]."&nbsp;: ";
            Dropdown::dropdownValue("glpi_entities", "entities_id", $this->fields["entities_id"],0);
            echo "&nbsp;&nbsp;".$LANG['entity'][9]."&nbsp;: ";
            if ($canrecu) {
               dropdownYesNo("is_recursive",$this->fields["is_recursive"]);
            } else {
               echo getYesNo($this->fields["is_recursive"]);
            }
         }
         echo "<br><br>" . $LANG['knowbase'][5]."&nbsp;: ";
         if (haveRight("faq","w") && haveRight("knowbase","w")) {
            dropdownYesNo('is_faq',$this->fields["is_faq"]);
         } else {
            echo getYesNo($this->fields["is_faq"]);
         }
         echo "<br><br>";
         if ($ID>0) {
            echo "<input type='submit' class='submit' name='update' value=\"".$LANG['buttons'][7]."\">";
            echo " <input type='reset' class='submit' value=\"".
                  $LANG['buttons'][16]."\">";
         } else {
            echo "<input type='hidden' name='users_id' value=\"".$_SESSION['glpiID']."\">";
            echo "<input type='submit' class='submit' name='add' value=\"".$LANG['buttons'][8]."\">";
            echo " <input type='reset' class='submit' value=\"".$LANG['buttons'][16]."\">";
         }
         echo "</p></form></div>";
         return true;
      } else { // Cannot edit
         return false;
      }
   } // function showForm
}

?>
