<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// CLASSE knowledgebase

class KnowbaseItem extends CommonDBTM {

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


   function defineTabs($options=array()) {
      global $LANG;

      $ong    = array();
      $ong[1] = $LANG['title'][26];

      return $ong;
   }


   /**
    * Get The Name of the Object
    *
    * @param $with_comment add comments to name (not used for this type)
    *
    * @return String: name of the object in the current language
   **/
   function getName($with_comment=0) {

      if (isset($this->fields["question"]) && !empty($this->fields["question"])) {
         return $this->fields["question"];
      }
      return NOT_AVAILABLE;
   }


   /**
    * Actions done at the end of the getEmpty function
    *
    *@return nothing
   **/
   function post_getEmpty () {

      if (haveRight("faq", "w") && !haveRight("knowbase", "w")) {
         $this->fields["is_faq"] = 1;
      }
   }


   function prepareInputForAdd($input) {
      global $LANG;

      // set new date.
      $input["date"] = $_SESSION["glpi_currenttime"];
      // set users_id

      // set title for question if empty
      if (empty($input["question"])) {
         $input["question"] = $LANG['common'][30];
      }

      if (haveRight("faq", "w") && !haveRight("knowbase", "w")) {
         $input["is_faq"] = 1;
      }
      if (!haveRight("faq", "w") && haveRight("knowbase", "w")) {
         $input["is_faq"] = 0;
      }
      return $input;
   }


   function prepareInputForUpdate($input) {
      global $LANG;

      // set title for question if empty
      if (empty($input["question"])) {
         $input["question"] = $LANG['common'][30];
      }
      return $input;
   }


   /**
    * Print out an HTML "<form>" for knowbase item
    *
    * @param $ID
    * @param $options array
    *     - target for the Form
    *
    * @return nothing (display the form)
   **/
   function showForm($ID, $options=array()) {
      global $LANG;

      // show kb item form
      if (!haveRight("knowbase","w" ) && !haveRight("faq","w")) {
         return false;
      }

      if ($ID >0) {
         $this->check($ID,'r');
      } else {
        $this->check(-1,'w');
      }

      $canedit = $this->can($ID,'w');
      $canrecu = $this->can($ID,'recursive');

      if ($canedit) {
         echo "<div id='contenukb'>";
         initEditorSystem('answer');

         echo "<form method='post' id='form_kb' name='form_kb' action=\"".$this->getFormUrl()."\">";

         if (!empty($ID)) {
            echo "<input type='hidden' name='id' value='$ID'>\n";
         }

         echo "<fieldset>";
         echo "<legend>".$LANG['knowbase'][13]."</legend>";
         echo "<div class='center'>".$LANG['knowbase'][6];
         Dropdown::show('KnowbaseItemCategory',
                        array('value' => $this->fields["knowbaseitemcategories_id"]));
         echo "</div></fieldset>";

         echo "<fieldset>";
         echo "<legend>".$LANG['knowbase'][14]."</legend>";
         echo "<div class='center'>";
         echo "<textarea cols='80' rows='2' name='question'>".$this->fields["question"]."</textarea>";
         echo "</div></fieldset>";

         echo "<fieldset>";
         echo "<legend>".$LANG['knowbase'][15]."</legend>";
         echo "<div class='center'>";
         echo "<textarea cols='80' rows='30' id='answer' name='answer'>".$this->fields["answer"];
         echo "</textarea></div></fieldset>";

         echo "<br>";

         if (!empty($ID)) {
            echo "<fieldset>";
            echo "<legend></legend>";
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
            Dropdown::show('Entity', array('value'    => $this->fields["entities_id"],
                                           'comments' => 0 ));
            echo "&nbsp;&nbsp;".$LANG['entity'][9]."&nbsp;: ";
            if ($canrecu) {
               Dropdown::showYesNo("is_recursive", $this->fields["is_recursive"]);
            } else {
               echo Dropdown::getYesNo($this->fields["is_recursive"]);
            }
         }
         echo "<br><br>" . $LANG['knowbase'][5]."&nbsp;: ";

         if (haveRight("faq","w") && haveRight("knowbase","w")) {
            Dropdown::showYesNo('is_faq', $this->fields["is_faq"]);
         } else {
            echo Dropdown::getYesNo($this->fields["is_faq"]);
         }

         echo "<br><br>";
         if ($ID>0) {
            echo "<input type='submit' class='submit' name='update' value=\"".$LANG['buttons'][7]."\">";
         } else {
            echo "<input type='hidden' name='users_id' value=\"".getLoginUserID()."\">";
            echo "<input type='submit' class='submit' name='add' value=\"".$LANG['buttons'][8]."\">";
         }

         echo "<span class='big_space'>";
         echo "<input type='reset' class='submit' value=\"".$LANG['buttons'][16]."\"></span>";
         echo "</p></form></div>";
         return true;
      }
      //  ELSE Cannot edit
      return false;
   } // function showForm


   /**
    * Add kb item to the public FAQ
    *
    * @return nothing
   **/
   function addToFaq() {
      global $DB;

      $DB->query("UPDATE `".$this->getTable()."`
                  SET `is_faq` = '1'
                  WHERE `id` = '".$this->fields['id']."'");
   }


   /**
    * Remove kb item from the public FAQ
    *
    * @return nothing
   **/
   function removeFromFaq() {
      global $DB;

      $DB->query("UPDATE `".$this->getTable()."`
                  SET `is_faq` = '0'
                  WHERE `id` = '".$this->fields['id']."'");
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

      $edit    = $this->can($ID, 'w');
      $isFAQ   = $this->fields["is_faq"];
      $editFAQ = haveRight("faq", "w");

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
                     <img src=\"".$CFG_GLPI["root_doc"]."/pics/faqremove.png\" alt=\"".
                        $LANG['knowbase'][7]."\" title=\"".$LANG['knowbase'][7]."\"></a></td>\n";
            } else {
               echo "<td class='center' width='33%'><a  class='icon_nav_move' href=\"".
                     $CFG_GLPI["root_doc"]."/front/knowbaseitem.form.php?id=$ID&amp;addtofaq=yes\">
                     <img src=\"".$CFG_GLPI["root_doc"]."/pics/faqadd.png\" alt=\"".
                        $LANG['knowbase'][5]."\" title=\"".$LANG['knowbase'][5]."\"></a></td>\n";
            }
         }
         echo "<td class='center' width='34%'><a class='icon_nav_move' href=\"".
               $CFG_GLPI["root_doc"]."/front/knowbaseitem.form.php?id=$ID&amp;modify=yes\">";
         echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/faqedit.png\" alt=\"".$LANG['knowbase'][8].
               "\" title=\"".$LANG['knowbase'][8]."\"></a></td>\n";
         echo "<td class='center' width='33%'>";
         echo "<a class='icon_nav_move' href=\"javascript:confirmAction('".
                addslashes($LANG['common'][55])."','".$CFG_GLPI["root_doc"].
                "/front/knowbaseitem.form.php?id=$ID&amp;delete=yes')\">";
         echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/faqdelete.png\" alt=\"".$LANG['knowbase'][9].
               "\" title=\"".$LANG['knowbase'][9]."\"></a></td>";
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
   function showFull($linkusers_id=true, $options=array()) {
      global $DB, $LANG, $CFG_GLPI;

      // show item : question and answer
      if (!haveRight("user","r")) {
         $linkusers_id = false;
      }

      //update counter view
      $query = "UPDATE `glpi_knowbaseitems`
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
      $fullcategoryname = getTreeValueCompleteName("glpi_knowbaseitemcategories",
                                                   $knowbaseitemcategories_id);

      $this->showTabs($options);
      $options['colspan'] = 2;
      $options['canedit'] = 0; // Hide the buttons
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_3'><th colspan='4'>".$LANG['common'][36]."&nbsp;:&nbsp;";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/".
            (isset($_SESSION['glpiactiveprofile'])
             && $_SESSION['glpiactiveprofile']['interface']=="central"
                  ?"knowbaseitem.php"
                  :"helpdesk.faq.php")."?knowbaseitemcategories_id=$knowbaseitemcategories_id'>".
            $fullcategoryname."</a>";
      echo "</th></tr>";

      echo "<tr class='tab_bg_3'><td class='left' colspan='4'><h2>".$LANG['knowbase'][14]."</h2>";
      echo $this->fields["question"];

      echo "</td></tr>";
      echo "<tr class='tab_bg_3'><td class='left' colspan='4'><h2>".$LANG['knowbase'][15]."</h2>\n";

      $answer = unclean_cross_side_scripting_deep($this->fields["answer"]);

      echo "<div id='kbanswer'>".$answer."</div>";
      echo "</td></tr>";

      echo "<tr><th class='tdkb'  colspan='2'>";
      if ($this->fields["users_id"]) {
         echo $LANG['common'][37]."&nbsp;: ";

         // Integer because true may be 2 and getUserName return array
         if ($linkusers_id) {
            $linkusers_id = 1;
         } else {
            $linkusers_id = 0;
         }

         echo getUserName($this->fields["users_id"], $linkusers_id);
         echo "<br>";
      }

      if ($this->fields["date"]) {
         echo $LANG['knowbase'][27]."&nbsp;: ". convDateTime($this->fields["date"]);
      }

      echo "</th>";
      echo "<th class='tdkb' colspan='2'>";

      if ($this->fields["date_mod"]) {
         echo $LANG['common'][26]."&nbsp;: ".convDateTime($this->fields["date_mod"]).
              "<br>";
      }
      echo $LANG['knowbase'][26]."&nbsp;: ".$this->fields["view"]."</th></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   /**
    * Print out an HTML "<form>" for Search knowbase item
    *
    * @param $options : $_GET
    * @param $faq display on faq ?
    *
    * @return nothing (display the form)
   **/
   static function searchForm($options, $faq=0) {
      global $LANG, $CFG_GLPI;

      // Default values of parameters
      $params["knowbaseitemcategories_id"] = "0";
      $params["contains"]                  = "";
      $params["target"]                    = $_SERVER['PHP_SELF'];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      if (!$CFG_GLPI["use_public_faq"] && !haveRight("knowbase","r") && !haveRight("faq","r")) {
         return false;
      }

      echo "<div><table class='center-h'><tr><td>";

      echo "<form method=get action='".$params["target"]."'><table border='0' class='tab_cadre'>";
      echo "<tr><th colspan='2'>".$LANG['search'][0]."&nbsp;:</th></tr>";
      echo "<tr class='tab_bg_2 center'><td>";
      echo "<input type='text' size='30' name='contains' value=\"".
             stripslashes(cleanInputText($params["contains"]))."\"></td>";
      echo "<td><input type='submit' value=\"".$LANG['buttons'][0]."\" class='submit' ></td></tr>";
      echo "</table></form>";

      echo "</td>";

      // Category select not for anonymous FAQ
      if (getLoginUserID() && !$faq) {
         echo "<td><form method=get action='".$params["target"]."'><table border='0' class='tab_cadre'>";
         echo "<tr><th colspan='2'>".$LANG['buttons'][43]."&nbsp;:</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>".$LANG['common'][36]."&nbsp;:&nbsp;";
         Dropdown::show('KnowbaseItemCategory',
                        array('value' => '$params["knowbaseitemcategories_id)"]'));
         echo "</td><td><input type='submit' value=\"".$LANG['buttons'][2]."\" class='submit'></td>";
         echo "</tr></table></form></td>";
      }
      echo "</tr></table></div>";
   }


   /**
    *Print out list kb item
    *
    * @param $options : $_GET
    * @param $faq display on faq ?
   **/
   static function showList($options, $faq=0) {
      global $DB, $LANG;

      // Default values of parameters
      $params["start"]                     = "0";
      $params["knowbaseitemcategories_id"] = "0";
      $params["contains"]                  = "";
      $params["target"]                    = $_SERVER['PHP_SELF'];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      // Lists kb Items
      $where = "";
      $order = "";
      $score = "";

      // Build query
      if (getLoginUserID()) {
         $where = getEntitiesRestrictRequest("", "glpi_knowbaseitems", "", "", true) . " AND ";
      } else {
         // Anonymous access
         if (isMultiEntitiesMode()) {
            $where = " (`glpi_knowbaseitems`.`entities_id` = '0'
                        AND `glpi_knowbaseitems`.`is_recursive` = '1')
                        AND ";
         }
      }

      if ($faq) { // helpdesk
         $where .= " (`glpi_knowbaseitems`.`is_faq` = '1')
                      AND ";
      }

      // a search with $contains
      if (strlen($params["contains"])>0) {
         $search  = unclean_cross_side_scripting_deep($params["contains"]);

         $score   = " ,MATCH(glpi_knowbaseitems.question, glpi_knowbaseitems.answer)
                     AGAINST('$search' IN BOOLEAN MODE) AS SCORE ";

         $where_1 = $where." MATCH(glpi_knowbaseitems.question, glpi_knowbaseitems.answer)
                    AGAINST('$search' IN BOOLEAN MODE) ";

         $order   = "ORDER BY `SCORE` DESC";

         // preliminar query to allow alternate search if no result with fulltext
         $query_1 = "SELECT COUNT(`id`)
                     FROM `glpi_knowbaseitems`
                     WHERE $where_1";
         $result_1 = $DB->query($query_1);
         $numrows_1 = $DB->result($result_1,0,0);

         if ($numrows_1<= 0) {// not result this fulltext try with alternate search
            $search1 = array(/* 1 */   '/\\\"/',
                             /* 2 */   "/\+/",
                             /* 3 */   "/\*/",
                             /* 4 */   "/~/",
                             /* 5 */   "/</",
                             /* 6 */   "/>/",
                             /* 7 */   "/\(/",
                             /* 8 */   "/\)/",
                             /* 9 */   "/\-/");
            $contains = preg_replace($search1,"", $params["contains"]);
            $where .= " (`glpi_knowbaseitems`.`question` ".makeTextSearch($contains)."
                         OR `glpi_knowbaseitems`.`answer` ".makeTextSearch($contains).")";
         } else {
            $where = $where_1;
         }

      } else { // no search -> browse by category
         $where .= " (`glpi_knowbaseitems`.`knowbaseitemcategories_id`
                        = '".$params["knowbaseitemcategories_id"]."')";
         $order  = " ORDER BY `glpi_knowbaseitems`.`question` ASC";
      }

      if (!$params["start"]) {
         $params["start"] = 0;
      }

      $query = "SELECT * $score
                FROM `glpi_knowbaseitems`
                WHERE $where
                $order";

      // Get it from database
      if ($result=$DB->query($query)) {
         $KbCategory = new KnowbaseItemCategory();
         $title = "";
         if ($KbCategory->getFromDB($params["knowbaseitemcategories_id"])) {
            $title = $LANG['common'][36]." = ".(empty($KbCategory->fields['name'])
                                                ?"(".$params['knowbaseitemcategories_id'].")"
                                                : $KbCategory->fields['name']);
         }

         initNavigateListItems('KnowbaseItem', $title);

         $numrows    = $DB->numrows($result);
         $list_limit = $_SESSION['glpilist_limit'];

         // Limit the result, if no limit applies, use prior result
         if ($numrows>$list_limit && !isset($_GET['export_all'])) {
            $query_limit   = $query ." LIMIT ".intval($params["start"]).", ".intval($list_limit)." ";
            $result_limit  = $DB->query($query_limit);
            $numrows_limit = $DB->numrows($result_limit);

         } else {
            $numrows_limit = $numrows;
            $result_limit  = $result;
         }

         if ($numrows_limit>0) {
            // Set display type for export if define
            $output_type = HTML_OUTPUT;

            if (isset($_GET["display_type"])) {
               $output_type = $_GET["display_type"];
            }

            // Pager
            $parameters = "start=".$params["start"]."&amp;knowbaseitemcategories_id=".
                          $params['knowbaseitemcategories_id']."&amp;contains=".
                          $params["contains"]."&amp;is_faq=$faq";

            if ($output_type==HTML_OUTPUT) {
               printPager($params['start'], $numrows, getItemTypeSearchURL('KnowbaseItem'),
                          $parameters, 'KnowbaseItem');
            }

            $nbcols = 1;
            // Display List Header
            echo Search::showHeader($output_type, $numrows_limit+1, $nbcols);

            if ($output_type!=HTML_OUTPUT) {
               $header_num = 1;
               echo Search::showHeaderItem($output_type, $LANG['knowbase'][14], $header_num);
               echo Search::showHeaderItem($output_type, $LANG['knowbase'][15], $header_num);
            }

            // Num of the row (1=header_line)
            $row_num = 1;
            for ($i=0 ; $i<$numrows_limit ; $i++) {
               $data = $DB->fetch_array($result_limit);

               addToNavigateListItems('KnowbaseItem', $data["id"]);
               // Column num
               $item_num = 1;
               $row_num++;
               echo Search::showNewLine($output_type, $i%2);

               if ($output_type==HTML_OUTPUT) {
                  echo Search::showItem($output_type,
                                        "<div class='kb'><a ".
                                          ($data['is_faq']?" class='pubfaq' ":" class='knowbase' ").
                                          " href=\"".$params['target']."?id=".$data["id"]."\">".
                                          resume_text($data["question"], 80)."</a></div>
                                          <div class='kb_resume'>".
                                          resume_text(html_clean(unclean_cross_side_scripting_deep($data["answer"])),
                                                      600)."</div>",
                                        $item_num, $row_num);
               } else {
                  echo Search::showItem($output_type, $data["question"], $item_num, $row_num);
                  echo Search::showItem($output_type,
                     html_clean(unclean_cross_side_scripting_deep(html_entity_decode($data["answer"],
                                                                                     ENT_QUOTES,
                                                                                     "UTF-8"))),
                                        $item_num, $row_num);
               }

               // End Line
               echo Search::showEndLine($output_type);
            }

            // Display footer
            if ($output_type==PDF_OUTPUT_LANDSCAPE || $output_type==PDF_OUTPUT_PORTRAIT) {
               echo Search::showFooter($output_type,
                                       Dropdown::getDropdownName("glpi_knowbaseitemcategories",
                                                                 $params['knowbaseitemcategories_id']));
            } else {
               echo Search::showFooter($output_type);
            }
            echo "<br>";
            if ($output_type==HTML_OUTPUT) {
               printPager($params['start'], $numrows, getItemTypeSearchURL('KnowbaseItem'),
                          $parameters, 'KnowbaseItem');
            }

         } else {
            if ($params["knowbaseitemcategories_id"] !=0) {
               echo "<div class='center b'>".$LANG['search'][15]."</div>";
            }
         }
      }
   }


   /**
    * Print out list recent or popular kb/faq
    *
    * @param $target where to go on action
    * @param $type type : recent / popular
    * @param $faq display only faq
    *
    * @return nothing (display table)
   **/
   static function showRecentPopular($target, $type, $faq=0) {
      global $DB, $LANG;

      if ($type=="recent") {
         $orderby = "ORDER BY `date` DESC";
         $title   = $LANG['knowbase'][29];

      } else {
         $orderby = "ORDER BY `view` DESC";
         $title   = $LANG['knowbase'][30];
      }

      $faq_limit = "";
      if (getLoginUserID()) {
         $faq_limit .= getEntitiesRestrictRequest(" WHERE ", "glpi_knowbaseitems", "", "", true);

      } else {
         // Anonymous access
         if (isMultiEntitiesMode()) {
            $faq_limit .= " WHERE (`glpi_knowbaseitems`.`entities_id` = '0'
                                   AND `glpi_knowbaseitems`.`is_recursive` = '1')";
         } else {
            $faq_limit .= " WHERE 1";
         }
      }

      if ($faq) { // FAQ
         $faq_limit .= " AND (`glpi_knowbaseitems`.`is_faq` = '1')";
      }

      $query = "SELECT *
                FROM `glpi_knowbaseitems`
                $faq_limit
                $orderby
                LIMIT 10";
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if ($number > 0) {
         echo "<table class='tab_cadrehov'>";
         echo "<tr><th>".$title."</th></tr>";
         while ($data=$DB->fetch_array($result)) {
            echo "<tr class='tab_bg_2'><td class='left'>";
            echo "<a ".($data['is_faq']?" class='pubfaq' ":" class='knowbase' ")." href=\"".
                  $target."?id=".$data["id"]."\">".resume_text($data["question"],80)."</a></td></tr>";
         }
         echo "</table>";
      }
   }


   /**
    * Print out lists of recent and popular kb/faq
    *
    * @param $target where to go on action
    * @param $faq display only faq
    *
    * @return nothing (display table)
   **/
   static function showViewGlobal($target, $faq=0) {

      echo "<div><table class='center-h' width='950px'><tr><td class='center middle'>";
      self::showRecentPopular($target, "recent", $faq);
      echo "</td><td class='center middle'>";
      self::showRecentPopular($target, "popular", $faq);
      echo "</td></tr>";
      echo "</table></div>";
}


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         =  'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[4]['table'] = 'glpi_knowbaseitemcategories';
      $tab[4]['field'] = 'name';
      $tab[4]['name']  = $LANG['common'][36];

      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'date';
      $tab[5]['name']          = $LANG['common'][27];
      $tab[5]['datatype']      = 'datetime';
      $tab[5]['massiveaction'] = false;

      $tab[6]['table']     = $this->getTable();
      $tab[6]['field']     = 'question';
      $tab[6]['name']      = $LANG['knowbase'][14];
      $tab[6]['datatype']  = 'text';

      $tab[7]['table']     = $this->getTable();
      $tab[7]['field']     = 'answer';
      $tab[7]['name']      = $LANG['knowbase'][15];
      $tab[7]['datatype']  = 'text';

      $tab[8]['table']    = $this->getTable();
      $tab[8]['field']    = 'is_faq';
      $tab[8]['name']     = $LANG['knowbase'][5];
      $tab[8]['datatype'] = 'bool';

      $tab[9]['table']         = $this->getTable();
      $tab[9]['field']         = 'view';
      $tab[9]['name']          = $LANG['knowbase'][26];
      $tab[9]['datatype']      = 'integer';
      $tab[9]['massiveaction'] = false;

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'date_mod';
      $tab[19]['name']          = $LANG['common'][26];
      $tab[19]['datatype']      = 'datetime';
      $tab[19]['massiveaction'] = false;

      $tab[70]['table']         = 'glpi_users';
      $tab[70]['field']         = 'name';
      $tab[70]['name']          = $LANG['common'][34];
      $tab[70]['massiveaction'] = false;

      $tab[80]['table']         = 'glpi_entities';
      $tab[80]['field']         = 'completename';
      $tab[80]['name']          = $LANG['entity'][0];
      $tab[80]['massiveaction'] = false;

      $tab[86]['table']    = $this->getTable();
      $tab[86]['field']    = 'is_recursive';
      $tab[86]['name']     = $LANG['entity'][9];
      $tab[86]['datatype'] = 'bool';

      return $tab;
   }

}

?>
