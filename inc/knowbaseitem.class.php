<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// CLASSE knowledgebase

class KnowbaseItem extends CommonDBTM {

   // For visibility checks
   protected $users     = array();
   protected $groups    = array();
   protected $profiles  = array();
   protected $entities  = array();


   static function getTypeName() {
      global $LANG;

      return $LANG['title'][5];
   }


   function canCreate() {
      return (Session::haveRight('knowbase', 'w') || Session::haveRight('faq', 'w'));
   }


   function canView() {
      global $CFG_GLPI;

      return (Session::haveRight('knowbase', 'r')
              || Session::haveRight('faq', 'r')
              || (Session::getLoginUserID()===false && $CFG_GLPI["use_public_faq"]));
   }


   function canViewItem() {
      global $CFG_GLPI;

      if ($this->fields['users_id'] == Session::getLoginUserID()) {
         return true;
      }
      if (Session::haveRight('knowbase_admin', '1')) {
         return true;
      }

      if ($this->fields["is_faq"]) {
         return (((Session::haveRight('knowbase', 'r') || Session::haveRight('faq', 'r'))
                  && $this->haveVisibilityAccess())
                 || (Session::getLoginUserID() === false && $this->isPubliclyVisible()));
      }
      return (Session::haveRight("knowbase", "r") && $this->haveVisibilityAccess());
   }


   function canUpdateItem() {

      // Personal knowbase or visibility and write access
      return (Session::haveRight('knowbase_admin', '1')
              || $this->fields['users_id'] == Session::getLoginUserID()
              || ((($this->fields["is_faq"] && Session::haveRight("faq", "w"))
                   || (!$this->fields["is_faq"] && Session::haveRight("knowbase", "w")))
                  && $this->haveVisibilityAccess()));
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Document', $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               $ong[1] = $this->getTypeName(1);
               if ($item->canUpdate()) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $ong[2] = self::createTabEntry($LANG['reminder'][2],
                                                    $item->countVisibilities());
                  } else {
                     $ong[2] = $LANG['reminder'][2];
                  }
               }
               return $ong;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if($item->getType() == __CLASS__) {
         switch($tabnum) {
            case 1 :
               $item->showMenu();
               break;

            case 2 :
               $item->showVisibility();
               break;
         }
      }
      return true;
   }


   /**
    * Actions done at the end of the getEmpty function
    *
    *@return nothing
   **/
   function post_getEmpty() {

      if (Session::haveRight("faq", "w") && !Session::haveRight("knowbase", "w")) {
         $this->fields["is_faq"] = 1;
      }
   }


   /**
    * @since version 0.83
   **/
   function post_getFromDB() {

      // Users
      $this->users    = KnowbaseItem_User::getUsers($this->fields['id']);

      // Entities
      $this->entities = Entity_KnowbaseItem::getEntities($this->fields['id']);

      // Group / entities
      $this->groups   = Group_KnowbaseItem::getGroups($this->fields['id']);

      // Profile / entities
      $this->profiles = KnowbaseItem_Profile::getProfiles($this->fields['id']);
   }


   function cleanDBonPurge() {

      $class = new KnowbaseItem_User();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new Entity_KnowbaseItem();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new Group_KnowbaseItem();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      $class = new KnowbaseItem_Profile();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }


   /**
    * @since version 0.83
    */
   function countVisibilities() {

      return (count($this->entities)
              + count($this->users)
              + count($this->groups)
              + count($this->profiles));
   }


   /**
    * Check is this item if visible to everybody (anonymous users)
    *
    * @since version 0.83
    *
    * @return Boolean
    */
   function isPubliclyVisible() {
      global $CFG_GLPI;

      if (!$CFG_GLPI["use_public_faq"]) {
         return false;
      }
      if (isset($this->entities[0])) { // Browse root entity rights
         foreach ($this->entities[0] as $entity) {
            if ($entity['is_recursive']) {
               return true;
            }
         }
      }
      return false;
   }


   /**
    * Is the login user have access to KnowbaseItem based on visibility configuration
    *
    * @since version 0.83
    *
    * @return boolean
   **/
   function haveVisibilityAccess() {

      // No public knowbaseitem right : no visibility check
      if (!Session::haveRight('faq', 'r') && !Session::haveRight('knowbase', 'r') ) {
         return false;
      }

      // Author
      if ($this->fields['users_id'] == Session::getLoginUserID()) {
         return true;
      }
      // Admin
      if (Session::haveRight('knowbase_admin', '1')) {
         return true;
      }

      // Users
      if (isset($this->users[Session::getLoginUserID()])) {
         return true;
      }

      // Groups
      if (count($this->groups)
          && isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"])) {
         foreach ($this->groups as $key => $data) {
            foreach ($data as $group) {
               if (in_array($group['groups_id'], $_SESSION["glpigroups"])) {
                  // All the group
                  if ($group['entities_id'] < 0) {
                     return true;
                  }
                  // Restrict to entities
                  $entities = array($group['entities_id']);
                  if ($group['is_recursive']) {
                     $entities = getSonsOf('glpi_entities', $group['entities_id']);
                  }
                  if (Session::haveAccessToOneOfEntities($entities, true)) {
                     return true;
                  }
               }
            }
         }
      }

      // Entities
      if (count($this->entities)
          && isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])) {
         foreach ($this->entities as $key => $data) {
            foreach ($data as $entity) {
               $entities = array($entity['entities_id']);
               if ($entity['is_recursive']) {
                  $entities = getSonsOf('glpi_entities', $entity['entities_id']);
               }
               if (Session::haveAccessToOneOfEntities($entities, true)) {
                  return true;
               }
            }
         }
      }

      // Profiles
      if (count($this->profiles)
          && isset($_SESSION["glpiactiveprofile"]) && isset($_SESSION["glpiactiveprofile"]['id'])) {
         if (isset($this->profiles[$_SESSION["glpiactiveprofile"]['id']])) {
            foreach ($this->profiles[$_SESSION["glpiactiveprofile"]['id']] as $profile) {
               // All the profile
               if ($profile['entities_id'] < 0) {
                  return true;
               }
               // Restrict to entities
               $entities = array($profile['entities_id']);
               if ($profile['is_recursive']) {
                  $entities = getSonsOf('glpi_entities',$profile['entities_id']);
               }
               if (Session::haveAccessToOneOfEntities($entities, true)) {
                  return true;
               }
            }
         }
      }

      return false;
   }


   /**
   * Return visibility joins to add to SQL
   *
   * @since version 0.83
   *
   * @param $forceall force all joins
   *
   * @return string joins to add
   **/
   static function addVisibilityJoins($forceall=false) {

      $join = '';
      // Users
      $join .= " LEFT JOIN `glpi_knowbaseitems_users`
                     ON (`glpi_knowbaseitems_users`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) ";

      // Groups
      if ($forceall
          || (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"]))) {
         $join .= " LEFT JOIN `glpi_groups_knowbaseitems`
                        ON (`glpi_groups_knowbaseitems`.`knowbaseitems_id`
                              = `glpi_knowbaseitems`.`id`) ";
      }

      // Profiles
      if ($forceall
          || (isset($_SESSION["glpiactiveprofile"]) && isset($_SESSION["glpiactiveprofile"]['id']))) {
         $join .= " LEFT JOIN `glpi_knowbaseitems_profiles`
                        ON (`glpi_knowbaseitems_profiles`.`knowbaseitems_id`
                              = `glpi_knowbaseitems`.`id`) ";
      }

      // Entities
      if ($forceall
          || !Session::getLoginUserID()
          || (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"]))) {
         $join .= " LEFT JOIN `glpi_entities_knowbaseitems`
                        ON (`glpi_entities_knowbaseitems`.`knowbaseitems_id`
                              = `glpi_knowbaseitems`.`id`) ";
      }

      return $join;
   }


   /**
    * Return visibility SQL restriction to add
    *
    * @since version 0.83
    *
    * @return string restrict to add
   **/
   static function addVisibilityRestrict() {
      $restrict = '';
      if (Session::getLoginUserID() && !Session::haveRight('knowbase_admin', '1')) {

         $restrict = "(`glpi_knowbaseitems`.`users_id` = '".Session::getLoginUserID()."' ";
         // Users
         $restrict .= " OR `glpi_knowbaseitems_users`.`users_id` = '".Session::getLoginUserID()."' ";
         // Groups
         if (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"])) {
            $restrict .= " OR (`glpi_groups_knowbaseitems`.`groups_id`
                                    IN ('".implode("','",$_SESSION["glpigroups"])."')
                              AND (`glpi_groups_knowbaseitems`.`entities_id` < 0
                                    ".getEntitiesRestrictRequest("OR", "glpi_groups_knowbaseitems",
                                                               '', '', true).")) ";
         }

         // Profiles
         if (isset($_SESSION["glpiactiveprofile"]) && isset($_SESSION["glpiactiveprofile"]['id'])) {
            $restrict .= " OR (`glpi_knowbaseitems_profiles`.`profiles_id`
                                    = '".$_SESSION["glpiactiveprofile"]['id']."'
                              AND (`glpi_knowbaseitems_profiles`.`entities_id` < 0
                                    ".getEntitiesRestrictRequest("OR", "glpi_knowbaseitems_profiles",
                                                               '', '', true).")) ";
         }
         // Entities
         if (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])) {
            // Force complete SQL not summary when access to all entities
            $restrict .= getEntitiesRestrictRequest("OR", "glpi_entities_knowbaseitems", '', '', true, true);
         }

         $restrict .= ") ";
      } else {
         $restrict = '1';
      }
      return $restrict;
   }



   function prepareInputForAdd($input) {
      global $LANG;

      // set new date if not exists
      if (!isset($input["date"]) || empty($input["date"])) {
         $input["date"] = $_SESSION["glpi_currenttime"];
      }
      // set users_id

      // set title for question if empty
      if (isset($input["name"]) && empty($input["name"])) {
         $input["name"] = $LANG['common'][30];
      }

      if (Session::haveRight("faq", "w") && !Session::haveRight("knowbase", "w")) {
         $input["is_faq"] = 1;
      }
      if (!Session::haveRight("faq", "w") && Session::haveRight("knowbase", "w")) {
         $input["is_faq"] = 0;
      }
      return $input;
   }


   function prepareInputForUpdate($input) {
      global $LANG;

      // set title for question if empty
      if (isset($input["name"]) && empty($input["name"])) {
         $input["name"] = $LANG['common'][30];
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
      if (!Session::haveRight("knowbase","w" ) && !Session::haveRight("faq","w")) {
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
         // Load ticket solution
         if (empty($ID) && isset($options['itemtype']) && isset($options['items_id'])
            && !empty($options['itemtype']) && !empty($options['items_id'])) {
            $item = new $options['itemtype']();
            if ($item->getFromDB($options['items_id'])) {
               $this->fields['name']   = $item->getField('name');
               $this->fields['answer'] = $item->getField('solution');
               if ($item->isField('itilcategories_id')) {
                  $ic = new ItilCategory();
                  if ($ic->getFromDB($item->getField('itilcategories_id'))) {
                     $this->fields['knowbaseitemcategories_id'] = $ic->getField('knowbaseitemcategories_id');
                  }

               }
            }
         }
         echo "<div id='contenukb'>";
         Html::initEditorSystem('answer');

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
         echo "<textarea cols='80' rows='2' name='name'>".$this->fields["name"]."</textarea>";
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
               echo $LANG['common'][26]."&nbsp;: ".Html::convDateTime($this->fields["date_mod"]).
                    "     ";
            }
            echo "</span><br>";

            if ($this->fields["date"]) {
               echo $LANG['common'][27]."&nbsp;: ". Html::convDateTime($this->fields["date"]);
            }

            echo "<span class='baskb_right'>";
            echo $LANG['knowbase'][26]."&nbsp;: ".$this->fields["view"]."</span></div>";

            echo "</fieldset>";
         }

         echo "<p class='center'>";

//          if (Session::isMultiEntitiesMode()) {
//             echo $LANG['entity'][0]."&nbsp;: ";
//             Dropdown::show('Entity', array('value'    => $this->fields["entities_id"],
//                                            'comments' => 0 ));
//             echo "&nbsp;&nbsp;".$LANG['entity'][9]."&nbsp;: ";
//             if ($canrecu) {
//                Dropdown::showYesNo("is_recursive", $this->fields["is_recursive"]);
//             } else {
//                echo Dropdown::getYesNo($this->fields["is_recursive"]);
//             }
//          }
//          echo "<br><br>" .
         echo $LANG['knowbase'][5]."&nbsp;: ";

         if (Session::haveRight("faq","w") && Session::haveRight("knowbase","w")) {
            Dropdown::showYesNo('is_faq', $this->fields["is_faq"]);
         } else {
            echo Dropdown::getYesNo($this->fields["is_faq"]);
         }

         echo "<br><br>";
         if ($ID>0) {
            echo "<input type='submit' class='submit' name='update' value=\"".$LANG['buttons'][7]."\">";
         } else {
            echo "<input type='hidden' name='users_id' value=\"".Session::getLoginUserID()."\">";
            echo "<input type='submit' class='submit' name='add' value=\"".$LANG['buttons'][8]."\">";
         }

         echo "<span class='big_space'>";
         echo "<input type='reset' class='submit' value=\"".$LANG['buttons'][16]."\"></span>";
         echo "</p>";
         Html::closeForm();
         echo "</div>";
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
      if (isset($_SESSION['glpi_faqcategories'])) {
         unset($_SESSION['glpi_faqcategories']);
      }
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
      if (isset($_SESSION['glpi_faqcategories'])) {
         unset($_SESSION['glpi_faqcategories']);
      }

   }


   /**
    * Print out an HTML Menu for knowbase item
    *
    * @return nothing (display the form)
   **/
   function showMenu() {
      global $LANG, $CFG_GLPI;

      $ID = $this->fields['id'];
      if (!$this->can($ID,'r') || Session::getLoginUserID()===false) {
         return false;
      }

      $edit    = $this->can($ID, 'w');
      $isFAQ   = $this->fields["is_faq"];
      $editFAQ = Session::haveRight("faq", "w");

      echo "<table class='tab_cadre_fixe'><tr><th colspan='3'>";
      if ($isFAQ) {
         echo $LANG['knowbase'][10]."</th></tr>\n";
      } else {
         echo $LANG['knowbase'][11]."</th></tr>\n";
      }

      if ($edit) {
         echo "<tr class='tab_bg_1'>";
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
    * Increase the view counter of the current knowbaseitem
    *
    * @since version 0.83
    */
   function updateCounter() {
      global $DB;

      //update counter view
      $query = "UPDATE `glpi_knowbaseitems`
                SET `view` = `view`+1
                WHERE `id` = '".$this->getID()."'";

      $DB->query($query);
   }


   /**
    * Print out (html) show item : question and answer
    *
    * @param $linkusers_id display users_id link
    * @param $options array of options
    *
    * @return nothing (display item : question and answer)
   **/
   function showFull($linkusers_id=true, $options=array()) {
      global $DB, $LANG, $CFG_GLPI;

      if (!$this->can($this->fields['id'],'r')) {
         return false;
      }

      // show item : question and answer
      if (!Session::haveRight("user", "r")) {
         $linkusers_id = false;
      }

      $inpopup = strpos($_SERVER['PHP_SELF'],"popup.php");

      $this->updateCounter();

      $knowbaseitemcategories_id = $this->fields["knowbaseitemcategories_id"];
      $fullcategoryname = getTreeValueCompleteName("glpi_knowbaseitemcategories",
                                                   $knowbaseitemcategories_id);

      if (!$inpopup) {
         $this->showTabs($options);
      }
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
      echo $this->fields["name"];

      echo "</td></tr>";
      echo "<tr class='tab_bg_3'><td class='left' colspan='4'><h2>".$LANG['knowbase'][15]."</h2>\n";

      echo "<div id='kbanswer'>";
      echo Toolbox::unclean_html_cross_side_scripting_deep($this->fields["answer"]);
      echo "</div>";
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
         echo $LANG['knowbase'][27]."&nbsp;: ". Html::convDateTime($this->fields["date"]);
      }

      if ($this->countVisibilities() == 0) {
         echo "<br><span class='red'>".$LANG['knowbase'][3]."</span>";
      }
      echo "</th>";
      echo "<th class='tdkb' colspan='2'>";

      if ($this->fields["date_mod"]) {
         echo $LANG['common'][26]."&nbsp;: ".Html::convDateTime($this->fields["date_mod"]).
              "<br>";
      }
      echo $LANG['knowbase'][26]."&nbsp;: ".$this->fields["view"]."</th></tr>";

      $this->showFormButtons($options);
      if (!$inpopup) {
         $this->addDivForTabs();
      }

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

      if (!$CFG_GLPI["use_public_faq"]
          && !Session::haveRight("knowbase","r")
          && !Session::haveRight("faq","r")) {
         return false;
      }

      // Default values of parameters
      $params["knowbaseitemcategories_id"] = "0";
      $params["contains"]                  = "";
      $params["target"]                    = $_SERVER['PHP_SELF'];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      echo "<div><table class='center-h'><tr><td>";

      echo "<form method=get action='".$params["target"]."'><table border='0' class='tab_cadre'>";
      echo "<tr><th colspan='2'>".$LANG['search'][0]."&nbsp;:</th></tr>";
      echo "<tr class='tab_bg_2 center'><td>";
      echo "<input type='text' size='30' name='contains' value=\"".
             stripslashes(Html::cleanInputText($params["contains"]))."\"></td>";
      echo "<td><input type='submit' value=\"".$LANG['buttons'][0]."\" class='submit'></td></tr>";
      echo "</table>";
      if (isset($options['itemtype']) && isset($options['items_id'])) {
         echo "<input type='hidden' name='itemtype' value='".$options['itemtype']."'>";
         echo "<input type='hidden' name='items_id' value='".$options['items_id']."'>";
      }
      Html::closeForm();

      echo "</td>";

      // Category select not for anonymous FAQ
      if (Session::getLoginUserID()
          && !$faq
          && (!isset($options['itemtype']) || !isset($options['items_id']))) {

         echo "<td><form method=get action='".$params["target"]."'>";
         echo "<table border='0' class='tab_cadre'>";
         echo "<tr><th colspan='2'>".$LANG['buttons'][43]."&nbsp;:</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>".$LANG['common'][36]."&nbsp;:&nbsp;";
         Dropdown::show('KnowbaseItemCategory',
                        array('value' => '$params["knowbaseitemcategories_id)"]'));
         echo "</td><td><input type='submit' value=\"".$LANG['buttons'][2]."\" class='submit'></td>";
         echo "</tr></table>";
         Html::closeForm();
         echo "</td>";
      }
      echo "</tr></table></div>";
   }


   /**
    * Build request for showList
    *
    * @since version 0.83
    *
    * @param $params Array (contains, knowbaseitemcategories_id, faq)
    *
    * @return String : SQL request
   **/
   static function getListRequest($params) {
      global $DB;

      // Lists kb Items
      $where = "";
      $order = "";
      $score = "";
      $join = self::addVisibilityJoins();

      // Build query
      if (Session::getLoginUserID()) {
         $where = self::addVisibilityRestrict()." AND ";
      } else {
         // Anonymous access
         if (Session::isMultiEntitiesMode()) {
            $where = " (`glpi_entities_knowbaseitems`.`entities_id` = '0'
                        AND `glpi_entities_knowbaseitems`.`is_recursive` = '1')
                      AND ";
         }
      }

      if ($params['faq']) { // helpdesk
         $where .= " (`glpi_knowbaseitems`.`is_faq` = '1')
                      AND ";
      }

      // a search with $contains
      if (strlen($params["contains"])>0) {
         $search  = Toolbox::unclean_cross_side_scripting_deep($params["contains"]);

         $score   = " ,MATCH(`glpi_knowbaseitems`.`name`, `glpi_knowbaseitems`.`answer`)
                     AGAINST('$search' IN BOOLEAN MODE) AS SCORE ";

         $where_1 = $where." MATCH(`glpi_knowbaseitems`.`name`, `glpi_knowbaseitems`.`answer`)
                    AGAINST('$search' IN BOOLEAN MODE) ";

         $order   = "ORDER BY `SCORE` DESC";

         // preliminar query to allow alternate search if no result with fulltext
         $query_1 = "SELECT COUNT(`glpi_knowbaseitems`.`id`)
                     FROM `glpi_knowbaseitems`
                     $join
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
            $where .= " (`glpi_knowbaseitems`.`name` ".Search::makeTextSearch($contains)."
                         OR `glpi_knowbaseitems`.`answer` ".Search::makeTextSearch($contains).")";
         } else {
            $where = $where_1;
         }

      } else { // no search -> browse by category
         $where .= " (`glpi_knowbaseitems`.`knowbaseitemcategories_id`
                        = '".$params["knowbaseitemcategories_id"]."')";
         $order  = " ORDER BY `glpi_knowbaseitems`.`name` ASC";
      }

      $query = "SELECT DISTINCT `glpi_knowbaseitems`.*,
                       `glpi_knowbaseitemcategories`.`completename` AS category
                       $score
                FROM `glpi_knowbaseitems`
                $join
                LEFT JOIN `glpi_knowbaseitemcategories`
                     ON (`glpi_knowbaseitemcategories`.`id`
                           = `glpi_knowbaseitems`.`knowbaseitemcategories_id`)
                WHERE $where
                $order";
      return $query;
   }

   /**
    * Print out list kb item
    *
    * @param $options : $_GET
    * @param $faq display on faq ?
   **/
   static function showList($options, $faq=0) {
      global $DB, $LANG, $CFG_GLPI;

      // Default values of parameters
      $params['faq']                       = $faq;
      $params["start"]                     = "0";
      $params["knowbaseitemcategories_id"] = "0";
      $params["contains"]                  = "";
      $params["target"]                    = $_SERVER['PHP_SELF'];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      if (!$params["start"]) {
         $params["start"] = 0;
      }

      $query = self::getListRequest($params);

      // Get it from database
      if ($result=$DB->query($query)) {
         $KbCategory = new KnowbaseItemCategory();
         $title = "";
         if ($KbCategory->getFromDB($params["knowbaseitemcategories_id"])) {
            $title = $LANG['common'][36]." = ".(empty($KbCategory->fields['name'])
                                                ?"(".$params['knowbaseitemcategories_id'].")"
                                                : $KbCategory->fields['name']);
         }

         Session::initNavigateListItems('KnowbaseItem', $title);

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
            if (isset($options['itemtype']) && isset($options['items_id'])) {
               $parameters .= "&amp;items_id=".$options['items_id']."&amp;itemtype=".$options['itemtype'];
            }

            if ($output_type==HTML_OUTPUT) {
               Html::printPager($params['start'], $numrows,
                                Toolbox::getItemTypeSearchURL('KnowbaseItem'),
                                $parameters, 'KnowbaseItem');
            }

            $nbcols = 1;
            // Display List Header
            echo Search::showHeader($output_type, $numrows_limit+1, $nbcols);

            $header_num = 1;
            echo Search::showHeaderItem($output_type, $LANG['knowbase'][14], $header_num);

            if ($output_type!=HTML_OUTPUT) {
               echo Search::showHeaderItem($output_type, $LANG['knowbase'][15], $header_num);
            }
            echo Search::showHeaderItem($output_type, $LANG['common'][36], $header_num);

            if (isset($options['itemtype'])
                && isset($options['items_id'])
                && $output_type==HTML_OUTPUT) {
               echo Search::showHeaderItem($output_type, '&nbsp;', $header_num);
            }

            // Num of the row (1=header_line)
            $row_num = 1;
            for ($i=0 ; $i<$numrows_limit ; $i++) {
               $data = $DB->fetch_array($result_limit);

               Session::addToNavigateListItems('KnowbaseItem', $data["id"]);
               // Column num
               $item_num = 1;
               $row_num++;
               echo Search::showNewLine($output_type, $i%2);

               if ($output_type==HTML_OUTPUT) {
                  if (isset($options['itemtype']) && isset($options['items_id'])) {
                     $href = " href='#' onClick=\"var w = window.open('".$CFG_GLPI["root_doc"].
                              "/front/popup.php?popup=show_kb&amp;id=".$data['id']."' ,'glpipopup', ".
                              "'height=400, width=1000, top=100, left=100, scrollbars=yes' );w.focus();\"" ;
                  } else {
                     $href = " href=\"".$params['target']."?id=".$data["id"]."\" ";
                  }

                  echo Search::showItem($output_type,
                                        "<div class='kb'><a ".
                                          ($data['is_faq']?" class='pubfaq' ":" class='knowbase' ").
                                          " $href>".Html::resume_text($data["name"], 80)."</a></div>
                                          <div class='kb_resume'>".
                                          Html::resume_text(Html::clean(Toolbox::unclean_cross_side_scripting_deep($data["answer"])),
                                                            600)."</div>",
                                        $item_num, $row_num);
               } else {
                  echo Search::showItem($output_type, $data["name"], $item_num, $row_num);
                  echo Search::showItem($output_type,
                     Html::clean(Toolbox::unclean_cross_side_scripting_deep(html_entity_decode($data["answer"],
                                                                                               ENT_QUOTES,
                                                                                               "UTF-8"))),
                                        $item_num, $row_num);
               }
               echo Search::showItem($output_type, $data["category"], $item_num, $row_num);

               if (isset($options['itemtype'])
                   && isset($options['items_id'])
                   && $output_type==HTML_OUTPUT) {

                  $content = "<a href='".Toolbox::getItemTypeFormURL($options['itemtype']).
                               "?load_kb_sol=".$data['id']."&amp;id=".$options['items_id'].
                               "&amp;forcetab=".$options['itemtype']."$2'>".$LANG['job'][24]."</a>";
                  echo Search::showItem($output_type, $content, $item_num, $row_num);
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
               Html::printPager($params['start'], $numrows,
                                Toolbox::getItemTypeSearchURL('KnowbaseItem'),
                                $parameters, 'KnowbaseItem');
            }

         } else {
            echo "<div class='center b'>".$LANG['search'][15]."</div>";
         }
      }
   }


   /**
    * Print out list recent or popular kb/faq
    *
    * @param $target where to go on action
    * @param $type type : recent / popular / not published
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
      // Force all joins for not published to verify no visibility set
      $join = self::addVisibilityJoins(true);

      if (Session::getLoginUserID()) {
         $faq_limit .= "WHERE ".self::addVisibilityRestrict();
      } else {
         // Anonymous access
         if (Session::isMultiEntitiesMode()) {
            $faq_limit .= " WHERE (`glpi_entities_knowbaseitems`.`entities_id` = '0'
                                   AND `glpi_entities_knowbaseitems`.`is_recursive` = '1')";
         } else {
            $faq_limit .= " WHERE 1";
         }
      }

      if ($type=="notpublished") {
         $title = $LANG['knowbase'][2];
         // not published = no visibility set
         $faq_limit = "WHERE `glpi_entities_knowbaseitems`.`entities_id` IS NULL
                             AND `glpi_knowbaseitems_profiles`.`profiles_id` IS NULL
                             AND `glpi_groups_knowbaseitems`.`groups_id` IS NULL
                             AND `glpi_knowbaseitems_users`.`users_id` IS NULL";

         if (!Session::haveRight('knowbase_admin', '1')) {
            $faq_limit .= " AND `glpi_knowbaseitems`.`users_id` = '".Session::getLoginUserID()."'";
         }
      } else {
         // Only published
         $faq_limit .= " AND (`glpi_entities_knowbaseitems`.`entities_id` IS NOT NULL
                             OR `glpi_knowbaseitems_profiles`.`profiles_id` IS NOT NULL
                             OR `glpi_groups_knowbaseitems`.`groups_id` IS NOT NULL
                             OR `glpi_knowbaseitems_users`.`users_id` IS NOT NULL)";
      }

      if ($faq) { // FAQ
         $faq_limit .= " AND (`glpi_knowbaseitems`.`is_faq` = '1')";
      }

      $query = "SELECT DISTINCT `glpi_knowbaseitems`.*
                FROM `glpi_knowbaseitems`
                $join
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
                  $target."?id=".$data["id"]."\">".Html::resume_text($data["name"],80)."</a></td>".
                  "</tr>";
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

      echo "<div><table class='center-h' width='950px'><tr><td class='center top'>";
      self::showRecentPopular($target, "recent", $faq);
      echo "</td><td class='center top'>";
      self::showRecentPopular($target, "popular", $faq);
      echo "</td><td class='center top'>";
      self::showRecentPopular($target, "notpublished", $faq);
      echo "</td></tr>";
      echo "</table></div>";
}


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

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
      $tab[6]['field']     = 'name';
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

   /**
    * Show visibility config for a knowbaseitem
    *
    * @since version 0.83
    *
   **/
   function showVisibility() {
      global $DB, $CFG_GLPI, $LANG;

      $ID      = $this->fields['id'];
      $canedit = $this->can($ID,'w');

      echo "<div class='center'>";

      $rand = mt_rand();

      if ($canedit) {
         echo "<form name='knowbaseitemvisibility_form$rand' id='knowbaseitemvisibility_form$rand' ";
         echo " method='post' action='".Toolbox::getItemTypeFormURL('KnowbaseItem')."'>";
         echo "<input type='hidden' name='knowbaseitems_id' value='$ID'>";
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='4'>".$LANG['common'][116]."</th></tr>";
         echo "<tr class='tab_bg_1'><td class='tab_bg_2' width='100px'>";

         $types = array( 'Group', 'Profile', 'User', 'Entity');

         $addrand = Dropdown::dropdownTypes('_type', '', $types);
         $params  = array('type'  => '__VALUE__',
                          'right' => ($this->getfield('is_faq') ? 'faq' : 'knowbase'));

         Ajax::updateItemOnSelectEvent("dropdown__type".$addrand,"visibility$rand",
                                    $CFG_GLPI["root_doc"]."/ajax/visibility.php",
                                    $params);

         echo "</td>";
         echo "<td><span id='visibility$rand'></span>";
         echo "</td></tr>";
         echo "</table></div>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";
      if ($canedit) {
         echo "<th>&nbsp;</th>";
      }
      echo "<th>".$LANG['common'][17]."</th>";
      echo "<th>".$LANG['mailing'][121]."</th>";
      echo "</tr>";

      // Users
      if (count($this->users)) {
         foreach ($this->users as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  $sel = "";
                  if (isset($_GET["select"]) && $_GET["select"]=="all") {
                     $sel = "checked";
                  }
                  echo "<input type='checkbox' name='user[".$data["id"]."]' value='1' $sel>";
                  echo "</td>";
               }
               echo "<td>".$LANG['common'][34]."</td>";
               echo "<td>".getUserName($data['users_id'])."</td>";
               echo "</tr>";
            }
         }
      }

      // Groups
      if (count($this->groups)) {
         foreach ($this->groups as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  $sel = "";
                  if (isset($_GET["select"]) && $_GET["select"]=="all") {
                     $sel = "checked";
                  }
                  echo "<input type='checkbox' name='group[".$data["id"]."]' value='1' $sel>";
                  echo "</td>";
               }
               echo "<td>".$LANG['common'][35]."</td>";
               echo "<td>";
               $names = Dropdown::getDropdownName('glpi_groups', $data['groups_id'],1);
               echo $names["name"]." ";
               echo Html::showToolTip($names["comment"]);
               if ($data['entities_id'] >= 0) {
                  echo " / ";
                  echo Dropdown::getDropdownName('glpi_entities',$data['entities_id']);
                  if ($data['is_recursive']) {
                     echo " <span class='b'>&nbsp;(R)</span>";
                  }
               }
               echo "</td>";
               echo "</tr>";
            }
         }
      }

      // Entity
      if (count($this->entities)) {
         foreach ($this->entities as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  $sel = "";
                  if (isset($_GET["select"]) && $_GET["select"]=="all") {
                     $sel = "checked";
                  }
                  echo "<input type='checkbox' name='entity[".$data["id"]."]' value='1' $sel>";
                  echo "</td>";
               }
               echo "<td>".$LANG['entity'][0]."</td>";
               echo "<td>";
               $names = Dropdown::getDropdownName('glpi_entities', $data['entities_id'],1);
               echo $names["name"]." ";
               echo Html::showToolTip($names["comment"]);
               if ($data['is_recursive']) {
                  echo " <span class='b'>&nbsp;(R)</span>";
               }
               echo "</td>";
               echo "</tr>";
            }
         }
      }

      // Profiles
      if (count($this->profiles)) {
         foreach ($this->profiles as $key => $val) {
            foreach ($val as $data) {
               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td>";
                  $sel = "";
                  if (isset($_GET["select"]) && $_GET["select"]=="all") {
                     $sel = "checked";
                  }
                  echo "<input type='checkbox' name='profile[".$data["id"]."]' value='1' $sel>";
                  echo "</td>";
               }
               echo "<td>".$LANG['profiles'][22]."</td>";
               echo "<td>";
               $names = Dropdown::getDropdownName('glpi_profiles', $data['profiles_id'], 1);
               echo $names["name"]." ";
               echo Html::showToolTip($names["comment"]);
               if ($data['entities_id'] >= 0) {
                  echo " / ";
                  echo Dropdown::getDropdownName('glpi_entities',$data['entities_id']);
                  if ($data['is_recursive']) {
                     echo " <span class='b'>&nbsp;(R)</span>";
                  }
               }
               echo "</td>";
               echo "</tr>";
            }
         }
      }

      if ($canedit) {
         echo "<tr class='tab_bg_1'><td colspan='3'></td></tr>";
      }
      echo "</table>";
      if ($canedit) {
         Html::openArrowMassives("knowbaseitemvisibility_form$rand", true);
         $confirm= array();
         if ($this->fields['users_id'] != Session::getLoginUserID()) {
            $confirm = array('deletevisibility' => $LANG['common'][120]);
         }

         Html::closeArrowMassives(array('deletevisibility' => $LANG['buttons'][6]), $confirm);
         Html::closeForm();
      }

      echo "</div>";
      // Add items

      return true;
   }


}
?>
