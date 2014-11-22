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
 *  Common GLPI object
**/
class CommonGLPI {

   /// GLPI Item type cache : set dynamically calling getType

   protected $type        = -1;
   protected $displaylist = true;

   public $showdebug      = false;

   static protected $othertabs = array();


   /**
    * Return the localized name of the current Type
    * Should be overloaded in each new class
    *
    * @return string
   **/
   static function getTypeName($nb=0) {
      return __('General');
   }


   /**
    * Return the type of the object : class name
    *
    * @return string
   **/
   static function getType() {
      return get_called_class();
   }


   /**
    * Register tab on an objet
    *
    * @since version 0.83
    *
    * @param $typeform  string object class name to add tab on form
    * @param $typetab   string object class name which manage the tab
   **/
   static function registerStandardTab($typeform, $typetab) {

      if (isset(self::$othertabs[$typeform])) {
         self::$othertabs[$typeform][] = $typetab;
      } else {
         self::$othertabs[$typeform] = array($typetab);
      }
   }


   /**
    * Get the array of Tab managed by other types
    * Getter for plugin (ex PDF) to access protected property
    *
    * @since version 0.83
    *
    * @param $typeform string object class name to add tab on form
    *
    * @return array of types
   **/
   static function getOtherTabs($typeform) {

      if (isset(self::$othertabs[$typeform])) {
         return self::$othertabs[$typeform];
      }
      return array();
   }


   /**
    * Define tabs to display
    *
    * NB : Only called for existing object
    *
    * @param $options array
    *     - withtemplate is a template view ?
    *
    * @return array containing the onglets
   **/
   function defineTabs($options=array()) {
      return array();
   }


   /**
    * return all the tabs for current object
    *
    * @since version 0.83
    *
    * @param $options array
    *     - withtemplate is a template view ?
    *
    * @return array containing the onglets
   **/
   final function defineAllTabs($options=array()) {

      // Tabs known by the object
      if ($this->isNewItem()) {
         $onglets  = array();
      } else {
         $onglets  = $this->defineTabs($options);
      }

      // Object with class with 'addtabon' attribute
      if (isset(self::$othertabs[$this->getType()])
          && !$this->isNewItem()) {

         foreach (self::$othertabs[$this->getType()] as $typetab) {
            $this->addStandardTab($typetab, $onglets, $options);
         }
      }

      // Single tab
      if (empty($onglets)) {
         $onglets['empty'] = $this->getTypeName(1);
      }

      return $onglets;
   }


   /**
    * Add standard define tab
    *
    * @param $itemtype         itemtype link to the tab
    * @param &$ong       array defined tab array
    * @param $options    array of options (for withtemplate)
    *
    *  @return nothing (set the tab array)
   **/
   function addStandardTab($itemtype, array &$ong, array $options) {

      $withtemplate = 0;
      if (isset($options['withtemplate'])) {
         $withtemplate = $options['withtemplate'];
      }

      switch ($itemtype) {
         case 'Note' :
            if (Session::haveRight("notes","r")) {
               $ong['Note'] = __('Notes');
            }
            break;

         default :
            if (!is_integer($itemtype)
                && ($obj = getItemForItemtype($itemtype))) {
               $titles = $obj->getTabNameForItem($this, $withtemplate);
               if (!is_array($titles)) {
                  $titles = array(1 => $titles);
               }

               foreach ($titles as $key => $val) {
                  if (!empty($val)) {
                     $ong[$itemtype.'$'.$key] = $val;
                  }
               }
            }
            break;
      }
   }


   /**
    * Get Tab Name used for itemtype
    *
    * NB : Only called for existing object
    *      Must check right on what will be displayed + template
    *
    * @since version 0.83
    *
    * @param $item                     CommonDBTM object for which the tab need to be displayed
    * @param $withtemplate    boolean  is a template object ? (default 0)
    *
    *  @return string tab name
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      return '';
   }


   /**
    * show Tab content
    *
    * @since version 0.83
    *
    * @param $item                  CommonGLPI object for which the tab need to be displayed
    * @param $tabnum       integer  tab number (default 1)
    * @param $withtemplate boolean  is a template object ? (default 0)
    *
    * @return true
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      return false;
   }


   /**
    * display standard tab contents
    *
    * @param $item                  CommonGLPI object for which the tab need to be displayed
    * @param $tab          string   tab name
    * @param $withtemplate boolean  is a template object ? (default 0)
    *
    * @return true
   **/
   static function displayStandardTab(CommonGLPI $item, $tab, $withtemplate=0) {

      switch ($tab) {
         // All tab
         case -1 :
            // get tabs and loop over
            $ong = $item->defineAllTabs(array('withtemplate' => $withtemplate));
            if (count($ong)) {
               foreach ($ong as $key => $val) {
                  if ($key != 'empty') {
                     echo "<div class='alltab'>$val</div>";
                     self::displayStandardTab($item, $key, $withtemplate);
                  }
               }
            }
            return true;

         case 'Note' :
            $item->showNotesForm();
            return true;

         case -2 :
            $item->showDebugInfo();
            return true;

         default :
            $data     = explode('$',$tab);
            $itemtype = $data[0];
            // Default set
            $tabnum   = 1;
            if (isset($data[1])) {
               $tabnum = $data[1];
            }

            if (!is_integer($itemtype) && ($itemtype != 'empty')
                && ($obj = getItemForItemtype($itemtype))) {
               return $obj->displayTabContentForItem($item, $tabnum, $withtemplate);
            }
            break;
      }
      return false;

   }


   /**
    * create tab text entry
    *
    * @param $text   string   text to display
    * @param $nb     integer  number of items (default 0)
    *
    *  @return array containing the onglets
   **/
   static function createTabEntry($text, $nb=0) {

      if ($nb) {
         //TRANS: %1$s is the name of the tab, $2$d is number of items in the tab between ()
         $text = sprintf(__('%1$s %2$s'), $text, "<sup>($nb)</sup>");
      }
      return $text;
   }


   /**
    * Redirect to the list page from which the item was selected
    * Default to the search engine for the type
   **/
   function redirectToList() {
      global $CFG_GLPI;

      if (isset($_GET['withtemplate'])
          && !empty($_GET['withtemplate'])) {
         Html::redirect($CFG_GLPI["root_doc"]."/front/setup.templates.php?add=0&itemtype=".
                        $this->getType());

      } else if (isset($_SESSION['glpilisturl'][$this->getType()])
                 && !empty($_SESSION['glpilisturl'][$this->getType()])) {
         Html::redirect($_SESSION['glpilisturl'][$this->getType()]);

      } else {
         Html::redirect($this->getSearchURL());
      }
   }


   /**
    * is the current object a new  one - Always false here (virtual Objet)
    *
    * @since version 0.83
    *
    * @return boolean
   **/
   function isNewItem() {
      return false;
   }


    /**
    * is the current object a new one - Always true here (virtual Objet)
    *
    * @since version 0.84
    *
    * @return boolean
   **/
   static function isNewID($ID) {
      return true;
   }


   /**
    * Show onglets
    *
    * @param $options array of parameters to add to URLs and ajax
    *     - withtemplate is a template view ?
    *
    * @return Nothing ()
   **/
   function showTabs($options=array()) {
      global $CFG_GLPI;

      // for objects not in table like central
      if (isset($this->fields['id'])) {
         $ID = $this->fields['id'];
      } else {
         $ID = 0;
      }
      $target         = $_SERVER['PHP_SELF'];
      $extraparamhtml = "";
      $extraparam     = "";
      $withtemplate   = "";

      if (is_array($options) && count($options)) {
         if (isset($options['withtemplate'])) {
            $withtemplate = $options['withtemplate'];
         }
         foreach ($options as $key => $val) {
            // Do not include id options
            if (($key[0] == '_') || ($key == 'id')) {
               unset($options[$key]);
            }
         }

         $extraparamhtml = "&amp;".Toolbox::append_params($options,'&amp;');
         $extraparam     = "&".Toolbox::append_params($options);

//          foreach ($options as $key => $val) {
//             // Do not include id options
//             if (($key[0] != '_') && ($key != 'id')) {
//                $extraparamhtml .= "&amp;$key=$val";
//                $extraparam     .= "&$key=$val";
//             }
//          }
      }

      if (empty($withtemplate)
          && !$this->isNewID($ID)
          && $this->getType()
          && $this->displaylist) {

         $glpilistitems =& $_SESSION['glpilistitems'][$this->getType()];
         $glpilisttitle =& $_SESSION['glpilisttitle'][$this->getType()];
         $glpilisturl   =& $_SESSION['glpilisturl'][$this->getType()];

         if (empty($glpilisturl)) {
            $glpilisturl = $this->getSearchURL();
         }

         echo "<div id='menu_navigate'>";

         $next = $prev = $first = $last = -1;
         $current = false;
         if (is_array($glpilistitems)) {
            $current = array_search($ID,$glpilistitems);
            if ($current !== false) {

               if (isset($glpilistitems[$current+1])) {
                  $next = $glpilistitems[$current+1];
               }

               if (isset($glpilistitems[$current-1])) {
                  $prev = $glpilistitems[$current-1];
               }

               $first = $glpilistitems[0];
               if ($first == $ID) {
                  $first = -1;
               }

               $last = $glpilistitems[count($glpilistitems)-1];
               if ($last == $ID) {
                  $last = -1;
               }

            }
         }
         $cleantarget = HTML::cleanParametersURL($target);
         echo "<ul>";
//          echo "<li><a href=\"javascript:showHideDiv('tabsbody','tabsbodyimg','".$CFG_GLPI["root_doc"].
//                     "/pics/deplier_down.png','".$CFG_GLPI["root_doc"]."/pics/deplier_up.png')\">";
//          echo "<img alt='' name='tabsbodyimg' src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\">";
//          echo "</a></li>";
         echo "<li><a href=\"javascript:toggleTableDisplay('mainformtable','tabsbodyimg','".$CFG_GLPI["root_doc"].
                     "/pics/deplier_down.png','".$CFG_GLPI["root_doc"]."/pics/deplier_up.png')\">";
         echo "<img alt='' name='tabsbodyimg' src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\">";
         echo "</a></li>";

         echo "<li><a href=\"".$glpilisturl."\">";

         if ($glpilisttitle) {
            if (Toolbox::strlen($glpilisttitle) > $_SESSION['glpidropdown_chars_limit']) {
               $glpilisttitle = Toolbox::substr($glpilisttitle, 0,
                                                $_SESSION['glpidropdown_chars_limit'])
                                . "&hellip;";
            }
            echo $glpilisttitle;

         } else {
            _e('List');
         }
         echo "</a></li>";

         if ($first >= 0) {
            echo "<li><a href='$cleantarget?id=$first$extraparamhtml'><img src='".
                       $CFG_GLPI["root_doc"]."/pics/first.png' alt=\"".__s('First').
                       "\" title=\"".__s('First')."\"></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/first_off.png' alt=\"".
                       __s('First')."\" title=\"".__s('First')."\"></li>";
         }

         if ($prev >= 0) {
            echo "<li><a href='$cleantarget?id=$prev$extraparamhtml'><img src='".
                       $CFG_GLPI["root_doc"]."/pics/left.png' alt=\"".__s('Previous').
                       "\" title=\"".__s('Previous')."\"></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/left_off.png' alt=\"".
                       __s('Previous')."\" title=\"".__s('Previous')."\"></li>";
         }

         if ($current !== false) {
            echo "<li>".($current+1) . "/" . count($glpilistitems)."</li>";
         }

         if ($next >= 0) {
            echo "<li><a href='$cleantarget?id=$next$extraparamhtml'><img src='".
                       $CFG_GLPI["root_doc"]."/pics/right.png' alt=\"".__s('Next').
                       "\" title=\"".__s('Next')."\"></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/right_off.png' alt=\"".
                       __s('Next')."\" title=\"".__s('Next')."\"></li>";
         }

         if ($last >= 0) {
            echo "<li><a href='$cleantarget?id=$last$extraparamhtml'><img src=\"".
                       $CFG_GLPI["root_doc"]."/pics/last.png\" alt=\"".__s('Last').
                       "\" title=\"".__s('Last')."\"></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/last_off.png' alt=\"".
                       __s('Last')."\" title=\"".__s('Last')."\"></li>";
         }
         echo "</ul></div>";
         echo "<div class='sep'></div>";
      }
      echo "<div id='tabspanel' class='center-h'></div>";

      $onglets     = $this->defineAllTabs($options);

      $display_all = true;
      if (isset($onglets['no_all_tab'])) {
         $display_all = false;
         unset($onglets['no_all_tab']);
      }

      $class = $this->getType();
      if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
          && (($ID > 0) || $this->showdebug)
          && (method_exists($class, 'showDebug')
              || in_array($class, $CFG_GLPI["infocom_types"])
              || in_array($class, $CFG_GLPI["reservation_types"]))) {

            $onglets[-2] = __('Debug');
      }
      if (count($onglets)) {
         $tabpage = $this->getTabsURL();
         $tabs    = array();

         foreach ($onglets as $key => $val ) {
            $tabs[$key] = array('title'  => $val,
                                'url'    => $tabpage,
                                'params' => "target=$target&itemtype=".$this->getType().
                                            "&glpi_tab=$key&id=$ID$extraparam");
         }

         // Not all tab for templates and if only 1 tab
         if ($display_all && empty($withtemplate) && count($tabs)>1) {
            $tabs[-1] = array('title'  => __('All'),
                              'url'    => $tabpage,
                              'params' => "target=$target&itemtype=".$this->getType().
                                          "&glpi_tab=-1&id=$ID$extraparam");
         }
         Ajax::createTabs('tabspanel', 'tabcontent', $tabs, $this->getType());
      }
   }


   /**
    * Get the search page URL for the current classe
    *
    * @param $full path or relative one (true by default)
   **/
   static function getTabsURL($full=true) {
      return Toolbox::getItemTypeTabsURL(get_called_class(), $full);
   }


   /**
    * Get the search page URL for the current classe
    *
    * @param $full path or relative one (true by default)
   **/
   static function getSearchURL($full=true) {
      return Toolbox::getItemTypeSearchURL(get_called_class(), $full);
   }


   /**
    * Get the search page URL for the current classe
    *
    * @param $full path or relative one (true by default)
   **/
   static function getFormURL($full=true) {
      return Toolbox::getItemTypeFormURL(get_called_class(), $full);
   }


   /**
    * Add div to display form's tabs
   **/
   function addDivForTabs() {

      echo "<div id='tabcontent'>&nbsp;</div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";
   }


   /**
    * @param $options   array
   **/
   function show($options=array()) {

      $this->showTabs($options);
      $this->addDivForTabs();
   }


   /**
    * to list infos in debug tab
   **/
   function showDebugInfo() {
      global $CFG_GLPI;

      $class = $this->getType();

      if (method_exists($class, 'showDebug')) {
         $this->showDebug();
      }

      if (in_array($class, $CFG_GLPI["infocom_types"])) {
         $infocom = new Infocom();
         if ($infocom->getFromDBforDevice($class, $this->fields['id'])) {
            $infocom->showDebug();
         }
      }

      if (in_array($class, $CFG_GLPI["reservation_types"])) {
         $resitem = new ReservationItem();
         if ($resitem->getFromDBbyItem($class, $this->fields['id'])) {
            $resitem->showDebugResa();
         }
      }
   }


   /**
    * Update $_SESSION to set the display options.
    *
    * @since version 0.84
    *
    * @param $input         array   of data to update
    * @param $sub_itemtype  string  sub itemtype if needed (default '')
    *
    * @return nothing
   **/
   static function updateDisplayOptions($input=array(), $sub_itemtype='') {

      $options = static::getAvailableDisplayOptions();
      if (count($options)) {
         if (empty($sub_itemtype)) {
            $display_options = &$_SESSION['glpi_display_options'][self::getType()];
         } else {
            $display_options = &$_SESSION['glpi_display_options'][self::getType()][$sub_itemtype];
         }
         // reset
         if (isset($input['reset'])) {
            foreach ($options as $option_group_name => $option_group) {
               foreach ($option_group as $option_name => $attributs) {
                  $display_options[$option_name] = $attributs['default'];
               }
            }
         } else {
            foreach ($options as $option_group_name => $option_group) {
               foreach ($option_group as $option_name => $attributs) {
                  if (isset($input[$option_name]) && ($_GET[$option_name] == 'on')) {
                     $display_options[$option_name] = true;
                  } else {
                     $display_options[$option_name] = false;
                  }
               }
            }
         }
         // Store new display options for user
         if ($uid = Session::getLoginUserID()) {
            $user = new User();
            if ($user->getFromDB($uid)) {
               $user->update(array('id' => $uid,
                                   'display_options'
                                        => exportArrayToDB($_SESSION['glpi_display_options'])));
            }
         }

         echo "<script type='text/javascript' >\n";
         echo "window.opener.location.reload();";
         echo "</script>";

      }
   }


   /**
    * Load display options to $_SESSION
    *
    * @since version 0.84
    *
    * @param $sub_itemtype  string   sub itemtype if needed
    *
    * @return nothing
   **/
   static function getDisplayOptions($sub_itemtype='') {

      if (!isset($_SESSION['glpi_display_options'])) {
         // Load display_options from user table
         $_SESSION['glpi_display_options'] = array();
         if ($uid = Session::getLoginUserID()) {
            $user = new User();
            if ($user->getFromDB($uid)) {
               $_SESSION['glpi_display_options'] = importArrayFromDB($user->fields['display_options']);
            }
         }
      }
      if (!isset($_SESSION['glpi_display_options'][self::getType()])) {
         $_SESSION['glpi_display_options'][self::getType()] = array();
      }

      if (!empty($sub_itemtype)) {
         if (!isset($_SESSION['glpi_display_options'][self::getType()][$sub_itemtype])) {
            $_SESSION['glpi_display_options'][self::getType()][$sub_itemtype] = array();
         }
         $display_options = &$_SESSION['glpi_display_options'][self::getType()][$sub_itemtype];
      } else {
         $display_options = &$_SESSION['glpi_display_options'][self::getType()];
      }
      // Load default values if not set
      $options = static::getAvailableDisplayOptions();
      if (count($options)) {
         foreach ($options as $option_group_name => $option_group) {
            foreach ($option_group as $option_name => $attributs) {
               if (!isset($display_options[$option_name])) {
                  $display_options[$option_name] = $attributs['default'];
               }
            }
         }
      }
      return $display_options;
   }



   /**
    * @since version 0.84
    *
    * @param $sub_itemtype string sub_itemtype if needed (default '')
   **/
   static function showDislayOptions($sub_itemtype='') {
      global $CFG_GLPI;

      $options      = static::getAvailableDisplayOptions($sub_itemtype);
      if (count($options)) {
         if (empty($sub_itemtype)) {
            $display_options = $_SESSION['glpi_display_options'][self::getType()];
         } else {
            $display_options = $_SESSION['glpi_display_options'][self::getType()][$sub_itemtype];
         }
         echo "<div class='center'>";
         echo "\n<form method='get' action='".$CFG_GLPI['root_doc']."/front/popup.php'>\n";
         echo "<input type='hidden' name='itemtype' value='NetworkPort'>\n";
         echo "<input type='hidden' name='sub_itemtype' value='$sub_itemtype'>\n";
         echo "<table class='tab_cadre'>";
         echo "<tr><th colspan='2'>".__s('Display options')."</th></tr>\n";
         echo "<tr><td colspan='2'>";
         echo "<input type='submit' class='submit' name='reset' value=\"".
                __('Reset display options')."\">";
         echo "</td></tr>\n";

         foreach ($options as $option_group_name => $option_group) {
            if (count($option_group) > 0) {
               echo "<tr><th colspan='2'>$option_group_name</th></tr>\n";
               foreach ($option_group as $option_name => $attributs) {
                  echo "<tr>";
                  echo "<td>";
                  echo "<input type='checkbox' name='$option_name' ".
                        ($display_options[$option_name]?'checked':'').">";
                  echo "</td>";
                  echo "<td>".$attributs['name']."</td>";
                  echo "</tr>\n";
               }
            }
         }
         echo "<tr><td colspan='2' class='center'>";
         echo "<input type='submit' class='submit' name='update' value=\""._sx('button','Save')."\">";
         echo "</td></tr>\n";
         echo "</table>";
         echo "</form>";

         echo "</div>";
      }
   }


   /**
    * Get available display options array
    *
    * @since version 0.84
    *
    * @return all the options
   **/
   static function getAvailableDisplayOptions() {

      return array();
   }
   /**
    * Get link for display options
    *
    * @since version 0.84
    * @param $sub_itemtype string sub itemtype if needed for display options
    * @return link
   **/
   static function getDisplayOptionsLink($sub_itemtype = '') {
      global $CFG_GLPI;

      $link ="<img alt=\"".__s('Display options')."\" title=\"";
      $link .= __s('Display options')."\" src='";
      $link .= $CFG_GLPI["root_doc"]."/pics/options_search.png' ";
      $link .= " class='pointer' onClick=\"var w = window.open('".$CFG_GLPI["root_doc"];
      $link .= "/front/popup.php?popup=display_options&amp;itemtype=".static::getType()."&amp;";
      $link .= "sub_itemtype=$sub_itemtype' ,'glpipopup', 'height=500, width=600, top=100,";
      $link .= "left=100, scrollbars=yes'); w.focus();\">";

      return $link;
   }
}
?>