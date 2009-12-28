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
   public $type = 'KnowbaseItem';

   static function getTypeName() {
      global $LANG;

      return $LANG['title'][5];
   }

   function canCreate() {
      return (haveRight('knowbase', 'w') || haveRight('faq', 'w'));
   }

   function canView() {
      return (haveRight('knowbase', 'r') || haveRight('faq', 'r'));
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
         Dropdown::show('KnowbaseItemCategory',
                     array('value' => $this->fields["knowbaseitemcategories_id"]));
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
            Dropdown::show('Entity',
                        array('value'     => $this->fields["entities_id"],
                              'comments'  => 0 ));
            echo "&nbsp;&nbsp;".$LANG['entity'][9]."&nbsp;: ";
            if ($canrecu) {
               Dropdown::showYesNo("is_recursive",$this->fields["is_recursive"]);
            } else {
               echo Dropdown::getYesNo($this->fields["is_recursive"]);
            }
         }
         echo "<br><br>" . $LANG['knowbase'][5]."&nbsp;: ";
         if (haveRight("faq","w") && haveRight("knowbase","w")) {
            Dropdown::showYesNo('is_faq',$this->fields["is_faq"]);
         } else {
            echo Dropdown::getYesNo($this->fields["is_faq"]);
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

   /**
    * Add kb item to the public FAQ
    *
    * @return nothing
    **/
   function addToFaq() {
      global $DB;

      $DB->query("UPDATE
                  `".$this->table."`
                  SET `is_faq`='1'
                  WHERE `id`='".$this->fields['id']."'");
   }

   /**
    * Remove kb item from the public FAQ
    *
    * @return nothing
    **/
   function removeFromFaq() {
      global $DB;

      $DB->query("UPDATE
                  `".$this->table."`
                  SET `is_faq`='0'
                  WHERE `id`='".$this->fields['id']."'");
   }

   /**
    * Print out an HTML Menu for knowbase item
    *
    * @return nothing (display the form)
    **/
   function showMenu() {
      global $LANG, $CFG_GLPI;

      $ID = $this->fields['id'];
      if (!$this->can($ID,'r')) {
         return false;
      }

      $edit=$this->can($ID,'w');
      $isFAQ = $this->fields["is_faq"];
      $editFAQ=haveRight("faq","w");

      echo "<table class='tab_cadre_fixe'><tr><th colspan='3'>";
      if ($isFAQ) {
         echo $LANG['knowbase'][10]."</th></tr>\n";
      } else {
         echo $LANG['knowbase'][11]."</th></tr>\n";
      }

      if ($edit) {
         echo "<tr>";
         if ($editFAQ) {
            if ($isFAQ) {
               echo "<td class='center' width='33%'><a class='icon_nav_move' href=\"".
                     $CFG_GLPI["root_doc"]."/front/knowbaseitem.form.php?id=$ID&amp;removefromfaq=yes\">
                     <img src=\"".$CFG_GLPI["root_doc"]."/pics/faqremove.png\" alt='".
                        $LANG['knowbase'][7]."' title='".$LANG['knowbase'][7]."'></a></td>\n";
            } else {
               echo "<td class='center' width='33%'><a  class='icon_nav_move' href=\"".
                     $CFG_GLPI["root_doc"]."/front/knowbaseitem.form.php?id=$ID&amp;addtofaq=yes\">
                     <img src=\"".$CFG_GLPI["root_doc"]."/pics/faqadd.png\" alt='".
                        $LANG['knowbase'][5]."' title='".$LANG['knowbase'][5]."'></a></td>\n";
            }
         }
         echo "<td class='center' width='34%'><a class='icon_nav_move' href=\"".
               $CFG_GLPI["root_doc"]."/front/knowbaseitem.form.php?id=$ID&amp;modify=yes\">";
         echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/faqedit.png\" alt='".$LANG['knowbase'][8].
               "' title='".$LANG['knowbase'][8]."'></a></td>\n";
         echo "<td class='center' width='33%'>";
         echo "<a class='icon_nav_move' href=\"javascript:confirmAction('".addslashes($LANG['common'][55]).
               "','".$CFG_GLPI["root_doc"]."/front/knowbaseitem.form.php?id=$ID&amp;delete=yes')\">";
         echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/faqdelete.png\" alt='".$LANG['knowbase'][9].
               "' title='".$LANG['knowbase'][9]."'></a></td>";
         echo "</tr>";
      }
      echo "</table><br>";
   }

   /**
    * Print out (html) show item : question and answer
    *
    * @param $linkusers_id display users_id link
    *
    * @return nothing (display item : question and answer)
    **/
   function showFull($linkusers_id=true) {
      global $DB,$LANG,$CFG_GLPI;

      // show item : question and answer
      if (!haveRight("user","r")) {
         $linkusers_id=false;
      }

      //update counter view
      $query="UPDATE `glpi_knowbaseitems`
              SET `view`=view+1
              WHERE `id` = '".$this->fields['id']."'";
      $DB->query($query);

      if ($this->fields["is_faq"]) {
         if (!$CFG_GLPI["use_public_faq"] && !haveRight("faq","r") && !haveRight("knowbase","r")) {
            return false;
         }
      } else if (!haveRight("knowbase","r")) {
         return false;
      }

      $knowbaseitemcategories_id = $this->fields["knowbaseitemcategories_id"];
      $fullcategoryname = getTreeValueCompleteName("glpi_knowbaseitemcategories",$knowbaseitemcategories_id);

      echo "<table class='tab_cadre_fixe'><tr><th colspan='2'>";
      echo $LANG['common'][36]."&nbsp;:&nbsp;";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/".
            (isset($_SESSION['glpiactiveprofile'])
             && $_SESSION['glpiactiveprofile']['interface']=="central"?"knowbaseitem.php":"helpdesk.faq.php").
            "?knowbaseitemcategories_id=$knowbaseitemcategories_id'>".$fullcategoryname."</a>";
      echo "</th></tr>";

      echo "<tr class='tab_bg_3'><td class='left' colspan='2'><h2>";
      echo ($this->fields["is_faq"]) ? "".$LANG['knowbase'][3]."" : "".$LANG['knowbase'][14]."";
      echo "</h2>";
      echo $this->fields["question"];

      echo "</td></tr>";
      echo "<tr class='tab_bg_3'><td class='left' colspan='2'><h2>";
      echo ($this->fields["is_faq"]) ? "".$LANG['knowbase'][4]."" : "".$LANG['knowbase'][15]."";
      echo "</h2>\n";

      $answer = unclean_cross_side_scripting_deep($this->fields["answer"]);

      echo "<div id='kbanswer'>".$answer."</div>";
      echo "</td></tr>";

      echo "<tr><th class='tdkb'>";
      if ($this->fields["users_id"]) {
         echo $LANG['common'][37]."&nbsp;: ";
         // Integer because true may be 2 and getUserName return array
         if ($linkusers_id) {
            $linkusers_id=1;
         } else {
            $linkusers_id=0;
         }

         echo getUserName($this->fields["users_id"],$linkusers_id);
         echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
      }
      if ($this->fields["date"]) {
         echo $LANG['knowbase'][27]."&nbsp;: ". convDateTime($this->fields["date"]);
      }

      echo "</th><th class='tdkb'>";
      if ($this->fields["date_mod"]) {
         echo $LANG['common'][26]."&nbsp;: ".convDateTime($this->fields["date_mod"]).
              "&nbsp;&nbsp;|&nbsp;&nbsp; ";
      }
      echo $LANG['knowbase'][26]."&nbsp;: ".$this->fields["view"]."</th></tr>";
      echo "</table><br>";

      return true;
   }

}

?>
