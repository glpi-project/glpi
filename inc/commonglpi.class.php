<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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
   die("Sorry. You can't access this file directly");
}

/**
 *  Common GLPI object
**/
class CommonGLPI {

   /// GLPI Item type cache : set dynamically calling getType
   protected $type                 = -1;

   /// Display list on Navigation Header
   protected $displaylist          = true;

   /// Show Debug
   public $showdebug               = false;

   /// Tab orientation : horizontal or vertical
   public $taborientation          = 'horizontal';

   /// Need to get item to show tab
   public $get_item_to_display_tab = false;
   static protected $othertabs     = array();


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

      $ong = array();
      $this->addDefaultFormTab($ong);
      return $ong;
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
      global $CFG_GLPI;

      $onglets = array();
      // Tabs known by the object
      if ($this->isNewItem()) {
         $this->addDefaultFormTab($onglets);
      } else {
         $onglets = $this->defineTabs($options);
      }

      // Object with class with 'addtabon' attribute
      if (isset(self::$othertabs[$this->getType()])
          && !$this->isNewItem()) {

         foreach (self::$othertabs[$this->getType()] as $typetab) {
            $this->addStandardTab($typetab, $onglets, $options);
         }
      }

      $class = $this->getType();
      if (($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
          && (!$this->isNewItem() || $this->showdebug)
          && (method_exists($class, 'showDebug')
              || InfoCom::canApplyOn($class)
              || in_array($class, $CFG_GLPI["reservation_types"]))) {

            $onglets[-2] = __('Debug');
      }
//       // Single tab
//       if (empty($onglets)) {
//          $onglets['empty'] = $this->getTypeName(1);
//       }

      return $onglets;
   }


   /**
    * Add standard define tab
    *
    * @param $itemtype         itemtype link to the tab
    * @param &$ong       array defined tab array
    * @param $options    array of options (for withtemplate)
    *
    * @return $this
   **/
   function addStandardTab($itemtype, array &$ong, array $options) {

      $withtemplate = 0;
      if (isset($options['withtemplate'])) {
         $withtemplate = $options['withtemplate'];
      }

      switch ($itemtype) {
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
      return $this;
   }


   /**
    * @since version 0.85
    *
    * @param $ong   array
    *
    * @return $this
   **/
   function addDefaultFormTab(array &$ong) {
      global $CFG_GLPI;

      if (self::isLayoutExcludedPage()
          || !self::isLayoutWithMain()
          || !method_exists($this, "showForm")) {
         $ong[$this->getType().'$main'] = $this->getTypeName(1);
      }
      return $this;
   }


   /**
    * get menu content
    *
    * @since version 0.85
    *
    * @return array for menu
   **/
   static function getMenuContent() {

      $menu       = array();

      $type       = static::getType();
      $item       = new $type();
      $forbidden  = $type::getForbiddenActionsForMenu();

      $debug      = false;

      if ($item instanceof CommonDBTM) {
         if ($type::canView()) {
            $menu['title']           = static::getMenuName();
            $menu['shortcut']        = static::getMenuShorcut();
            $menu['page']            = static::getSearchURL(false);
            $menu['links']['search'] = static::getSearchURL(false);


            if (!in_array('add', $forbidden)
                && $type::canCreate()) {

               if ($item->maybeTemplate()) {
                  $menu['links']['add'] = '/front/setup.templates.php?'.'itemtype='.$type.
                                          '&amp;add=1';
                  if (!in_array('template', $forbidden)) {
                     $menu['links']['template'] = '/front/setup.templates.php?'.'itemtype='.$type.
                                                  '&amp;add=0';
                  }
               } else {
                  $menu['links']['add'] = static::getFormURL(false);
               }
            }

            if ($data = static::getAdditionalMenuLinks()) {
               $menu['links'] += $data;
            }

         }
      } else {
         if (!method_exists($type, 'canView')
             || $item->canView()) {
            $menu['title']           = static::getMenuName();
            $menu['shortcut']        = static::getMenuShorcut();
            $menu['page']            = static::getSearchURL(false);
            $menu['links']['search'] = static::getSearchURL(false);
         }
      }
      if ($data = static::getAdditionalMenuOptions()) {
         $menu['options'] = $data;
      }
      if ($data = static::getAdditionalMenuContent()) {
         $newmenu[strtolower($type)]  = $menu;
         // Force overwrite existing menu
         foreach ($data as $key => $val) {
            $newmenu[$key] = $val;
         }
         $newmenu['is_multi_entries'] = true;
         $menu = $newmenu;
      }
      if (count($menu)) {
         return $menu;
      }
      return false;
   }


   /**
    * get additional menu content
    *
    * @since version 0.85
    *
    * @return array for menu
   **/
   static function getAdditionalMenuContent() {
      return false;
   }


   /**
    * Get forbidden actions for menu : may be add / template
    *
    * @since version 0.85
    *
    * @return array of forbidden actions
   **/
   static function getForbiddenActionsForMenu() {
      return array();
   }


   /**
    * Get additional menu options
    *
    * @since version 0.85
    *
    * @return array of additional options
   **/
   static function getAdditionalMenuOptions() {
      return false;
   }


   /**
    * Get additional menu links
    *
    * @since version 0.85
    *
    * @return array of additional options
   **/
   static function getAdditionalMenuLinks() {
      return false;
   }


   /**
    * Get menu shortcut
    *
    * @since version 0.85
    *
    * @return character menu shortcut key
   **/
   static function getMenuShorcut() {
      return '';
   }


   /**
    * Get menu name
    *
    * @since version 0.85
    *
    * @return character menu shortcut key
   **/
   static function getMenuName() {
      return static::getTypeName(Session::getPluralNumber());
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
    * @param $options      array    additional options to pass
    *
    * @return true
   **/
   static function displayStandardTab(CommonGLPI $item, $tab, $withtemplate=0, $options=array()) {

      switch ($tab) {
         // All tab
         case -1 :
            // get tabs and loop over
            $ong = $item->defineAllTabs(array('withtemplate' => $withtemplate));

            if (!self::isLayoutExcludedPage() && self::isLayoutWithMain()) {
               //on classical and vertical split; the main tab is always displayed
               array_shift($ong);
            }

            if (count($ong)) {
               foreach ($ong as $key => $val) {
                  if ($key != 'empty') {
                     echo "<div class='alltab'>$val</div>";
                     self::displayStandardTab($item, $key, $withtemplate, $options);
                  }
               }
            }
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

            $options['withtemplate'] = $withtemplate;

            if ($tabnum == 'main') {
               Plugin::doHook('pre_show_item', array('item' => $item, 'options' => &$options));
               $ret = $item->showForm($item->getID(), $options);
               Plugin::doHook('post_show_item', array('item' => $item, 'options' => $options));
               return $ret;
            }

            if (!is_integer($itemtype) && ($itemtype != 'empty')
                && ($obj = getItemForItemtype($itemtype))) {
               $options['tabnum'] = $tabnum;
               $options['itemtype'] = $itemtype;
               Plugin::doHook('pre_show_tab', array( 'item' => $item, 'options' => &$options));
               $ret = $obj->displayTabContentForItem($item, $tabnum, $withtemplate);
               Plugin::doHook('post_show_tab', array('item' => $item, 'options' => $options));
               return $ret;
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
         $text = sprintf(__('%1$s %2$s'), $text, "<sup class='tab_nb'>$nb</sup>");
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
    * Get the search page URL for the current classe
    *
    * @param $full path or relative one (true by default)
   **/
   static function getTabsURL($full=true) {
      return Toolbox::getItemTypeTabsURL(get_called_class(), $full);
   }


   /**
    * Get the search page URL for the current class
    *
    * @param $full path or relative one (true by default)
   **/
   static function getSearchURL($full=true) {
      return Toolbox::getItemTypeSearchURL(get_called_class(), $full);
   }


   /**
    * Get the form page URL for the current class
    *
    * @param $full path or relative one (true by default)
   **/
   static function getFormURL($full=true) {
      return Toolbox::getItemTypeFormURL(get_called_class(), $full);
   }


   /**
    * Get the form page URL for the current class and point to a specific ID
    *
    * @param $id      (default 0)
    * @param $full    path or relative one (true by default)
    *
    * @since version 0.90
   **/
   static function getFormURLWithID($id=0, $full=true) {

      $itemtype = get_called_class();
      $link     = $itemtype::getFormURL($full);
      $link    .= (strpos($link,'?') ? '&':'?').'id=' . $id;
      return $link;
   }


   /**
    * @since version 0.90
    *
    * @param $options   array
    *
    * @return boolean
   **/
   function showPrimaryForm($options=array()) {

      if (!method_exists($this, "showForm")) {
         return false;
      }

      $ong   = $this->defineAllTabs();
      $class = "main_form";
      if (count($ong) == 0) {
         $class .= " no_tab";
      }
      if (!isset($_GET['id'])
          || (($_GET['id'] <= 0) && !$this instanceof Entity )) {
         $class .= " create_form";
      } else {
         $class .= " modify_form";
      }
      echo "<div class='form_content'>";
      echo "<div class='$class'>";
      Plugin::doHook('pre_show_item', array('item' => $this, 'options' => &$options));
      $this->showForm($options['id'], $options);
      Plugin::doHook('post_show_item', array('item' => $this, 'options' => $options));
      echo "</div>";
      echo "</div>";
   }


   /**
    * Add div to display form's tabs
    *
    * @param $options   array
   **/
   function addDivForTabs($options=array()) {
      $this->showTabsContent($options);
   }


  /**
    * Show header of forms : navigation headers
    *
    * @param $options array of parameters to add to URLs and ajax
    *     - withtemplate is a template view ?
    *
    * @return Nothing ()
   **/
   function showTabs($options=array()) {
      $this->showNavigationHeaderOld($options);
   }


   /**
    * Show tabs content
    *
    * @since version 0.85
    *
    * @param $options array of parameters to add to URLs and ajax
    *     - withtemplate is a template view ?
    *
    * @return Nothing ()
   **/
   function showTabsContent($options=array()) {
      global $CFG_GLPI;

      // for objects not in table like central
      if (isset($this->fields['id'])) {
         $ID = $this->fields['id'];
      } else {
         if (isset($options['id'])) {
            $ID = $options['id'];
         } else {
            $ID = 0;
         }
      }

      $target         = $_SERVER['PHP_SELF'];
      $extraparamhtml = "";
      $extraparam     = "";
      $withtemplate   = "";
      if (is_array($options) && count($options)) {
         if (isset($options['withtemplate'])) {
            $withtemplate = $options['withtemplate'];
         }
         $cleaned_options = $options;
         if (isset($cleaned_options['id'])) {
            unset($cleaned_options['id']);
         }
         if (isset($cleaned_options['stock_image'])) {
            unset($cleaned_options['stock_image']);
         }
         $extraparamhtml = "&amp;".Toolbox::append_params($cleaned_options,'&amp;');
         $extraparam     = "&".Toolbox::append_params($cleaned_options);
      }
      echo "<div class='glpi_tabs ".($this->isNewID($ID)?"new_form_tabs":"")."'>";
      echo "<div id='tabspanel' class='center-h'></div>";
      $current_tab = 0;
      $onglets     = $this->defineAllTabs($options);
      $display_all = true;
      if (isset($onglets['no_all_tab'])) {
         $display_all = false;
         unset($onglets['no_all_tab']);
      }

      if (count($onglets)) {
         $tabpage = $this->getTabsURL();
         $tabs    = array();

         foreach ($onglets as $key => $val ) {
            $tabs[$key] = array('title'  => $val,
                                'url'    => $tabpage,
                                'params' => "_target=$target&amp;_itemtype=".$this->getType().
                                            "&amp;_glpi_tab=$key&amp;id=$ID$extraparamhtml");
         }

         // Not all tab for templates and if only 1 tab
         if ($display_all
             && empty($withtemplate)
             && (count($tabs) > 1)) {
            $tabs[-1] = array('title'  => __('All'),
                              'url'    => $tabpage,
                              'params' => "_target=$target&amp;_itemtype=".$this->getType().
                                          "&amp;_glpi_tab=-1&amp;id=$ID$extraparamhtml");
         }

         Ajax::createTabs('tabspanel', 'tabcontent', $tabs, $this->getType(), $ID,
                          $this->taborientation);
      }
      echo "</div>";
   }


   /**
    * Show tabs
    *
    * @param $options array of parameters to add to URLs and ajax
    *     - withtemplate is a template view ?
    *
    * @return Nothing ()
   **/
   function showNavigationHeader($options=array()) {
      global $CFG_GLPI;

      // for objects not in table like central
      if (isset($this->fields['id'])) {
         $ID = $this->fields['id'];
      } else {
         if (isset($options['id'])) {
            $ID = $options['id'];
         } else {
            $ID = 0;
         }
      }
      $target         = $_SERVER['PHP_SELF'];
      $extraparamhtml = "";
      $extraparam     = "";
      $withtemplate   = "";

      if (is_array($options) && count($options)) {
         $cleanoptions = $options;
         if (isset($options['withtemplate'])) {
            $withtemplate = $options['withtemplate'];
            unset($cleanoptions['withtemplate']);
         }
         foreach ($cleanoptions as $key => $val) {
            // Do not include id options
            if (($key[0] == '_') || ($key == 'id')) {
               unset($cleanoptions[$key]);
            }
         }
         $extraparamhtml = "&amp;".Toolbox::append_params($cleanoptions,'&amp;');
         $extraparam     = "&".Toolbox::append_params($cleanoptions);
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

//          echo "<div id='menu_navigate'>";

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
         echo "<div class='navigationheader'><table class='tab_cadre_pager'>";
         echo "<tr class='tab_bg_2'>";

         if ($first >= 0) {
            echo "<td class='left'><a href='$cleantarget?id=$first$extraparamhtml'>".
                  "<img src='".$CFG_GLPI["root_doc"]."/pics/first.png' alt=\"".__s('First').
                    "\" title=\"".__s('First')."\" class='pointer'></a></td>";
         } else {
            echo "<td class='left'><img src='".$CFG_GLPI["root_doc"]."/pics/first_off.png' alt=\"".
                                    __s('First')."\" title=\"".__s('First')."\"></td>";
         }

         if ($prev >= 0) {
            echo "<td class='left'><a href='$cleantarget?id=$prev$extraparamhtml' id='previouspage'>".
                  "<img src='".$CFG_GLPI["root_doc"]."/pics/left.png' alt=\"".__s('Previous').
                    "\" title=\"".__s('Previous')."\" class='pointer'></a></td>";
            $js = '$("body").keydown(function(e) {
                       if ($("input, textarea").is(":focus") === false) {
                          if(e.keyCode == 37 && e.ctrlKey) {
                            window.location = $("#previouspage").attr("href");
                          }
                       }
                  });';
            echo Html::scriptBlock($js);
         } else {
            echo "<td class='left'><img src='".$CFG_GLPI["root_doc"]."/pics/left_off.png' alt=\"".
                                    __s('Previous')."\" title=\"".__s('Previous')."\"></td>";
         }

         if (!$glpilisttitle) {
            $glpilisttitle = __('List');
         }
         echo "<td><a href=\"".$glpilisturl."\" title='$glpilisttitle'>";
         echo Toolbox::substr($glpilisttitle, 0, 100)."...";
         echo "</a></td>";

         $name = $this->getTypeName(1);
         if (isset($this->fields['id']) && ($this instanceof CommonDBTM)) {
            $name = sprintf(__('%1$s - %2$s'), $name, $this->getName());
            if ($_SESSION['glpiis_ids_visible'] || empty($name)) {
               $name = sprintf(__('%1$s - ID %2$d'), $name, $this->fields['id']);
            }
         }
         if (isset($this->fields["entities_id"])
               && Session::isMultiEntitiesMode()
               && $this->isEntityAssign()) {
            $entname = Dropdown::getDropdownName("glpi_entities", $this->fields["entities_id"]);
            if ($this->isRecursive()) {
               $entname = sprintf(__('%1$s + %2$s'), $entname, __('Child entities'));
            }
            $name = sprintf(__('%1$s (%2$s)'), $name, $entname);

         }
         echo "<td class='b big'>";
         if (!self::isLayoutWithMain() || self::isLayoutExcludedPage()) {
            echo $name;
         }
         echo "</td>";

         if ($current !== false) {
            echo "<td>".($current+1) . "/" . count($glpilistitems)."</td>";
         }

         if ($next >= 0) {
            echo "<td class='right'><a href='$cleantarget?id=$next$extraparamhtml' id='nextpage'>".
                  "<img src='".$CFG_GLPI["root_doc"]."/pics/right.png' alt=\"".__s('Next').
                    "\" title=\"".__s('Next')."\" class='pointer'></a></td>";
            $js = '$("body").keydown(function(e) {
                       if ($("input, textarea").is(":focus") === false) {
                          if(e.keyCode == 39 && e.ctrlKey) {
                            window.location = $("#nextpage").attr("href");
                          }
                       }
                  });';
            echo Html::scriptBlock($js);
         } else {
            echo "<td class='right'><img src='".$CFG_GLPI["root_doc"]."/pics/right_off.png' alt=\"".
                                     __s('Next')."\" title=\"".__s('Next')."\"></td>";
         }

         if ($last >= 0) {
            echo "<td class='right'><a href='$cleantarget?id=$last$extraparamhtml'>".
                  "<img src=\"".$CFG_GLPI["root_doc"]."/pics/last.png\" alt=\"".__s('Last').
                    "\" title=\"".__s('Last')."\" class='pointer'></a></td>";
         } else {
            echo "<td class='right'><img src='".$CFG_GLPI["root_doc"]."/pics/last_off.png' alt=\"".
                                     __s('Last')."\" title=\"".__s('Last')."\"></td>";
         }

//          echo "</ul></div>";
         // End pager
         echo "</tr></table></div>";
//          echo "<div class='sep'></div>";
      }
   }


   /**
    * Show tabs
    *
    * @since version 0.85
    * @param $options array of parameters to add to URLs and ajax
    *     - withtemplate is a template view ?
    * @deprecated  Only for compatibility usage
    * @return Nothing ()
   **/
   function showNavigationHeaderOld($options=array()) {
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
            if (($key[0] != '_') && ($key != 'id')) {
               $extraparamhtml .= "&amp;$key=$val";
               $extraparam     .= "&$key=$val";
            }
         }
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
         echo "<li><a href=\"javascript:toggleTableDisplay('mainformtable','tabsbodyimg','".
                    $CFG_GLPI["root_doc"]."/pics/deplier_down.png','".$CFG_GLPI["root_doc"].
                    "/pics/deplier_up.png')\">";
         echo "<img alt='' name='tabsbodyimg' src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\" class='pointer'>";
         echo "</a></li>";

         echo "<li><a href=\"".$glpilisturl."\">";

         if ($glpilisttitle) {
            echo $glpilisttitle;
         } else {
            _e('List');
         }
         echo "</a></li>";

         if ($first >= 0) {
            echo "<li><a href='$cleantarget?id=$first$extraparamhtml'><img src='".
                       $CFG_GLPI["root_doc"]."/pics/first.png' alt=\"".__s('First').
                       "\" title=\"".__s('First')."\" class='pointer'></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/first_off.png' alt=\"".
                       __s('First')."\" title=\"".__s('First')."\" class='pointer'></li>";
         }

         if ($prev >= 0) {
            echo "<li><a href='$cleantarget?id=$prev$extraparamhtml'><img src='".
                       $CFG_GLPI["root_doc"]."/pics/left.png' alt=\"".__s('Previous').
                       "\" title=\"".__s('Previous')."\" class='pointer'></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/left_off.png' alt=\"".
                       __s('Previous')."\" title=\"".__s('Previous')."\" class='pointer'></li>";
         }

         if ($current !== false) {
            echo "<li>".($current+1) . "/" . count($glpilistitems)."</li>";
         }

         if ($next >= 0) {
            echo "<li><a href='$cleantarget?id=$next$extraparamhtml'><img src='".
                       $CFG_GLPI["root_doc"]."/pics/right.png' alt=\"".__s('Next').
                       "\" title=\"".__s('Next')."\" class='pointer'></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/right_off.png' alt=\"".
                       __s('Next')."\" title=\"".__s('Next')."\" class='pointer'></li>";
         }

         if ($last >= 0) {
            echo "<li><a href='$cleantarget?id=$last$extraparamhtml'><img src=\"".
                       $CFG_GLPI["root_doc"]."/pics/last.png\" alt=\"".__s('Last').
                       "\" title=\"".__s('Last')."\" class='pointer'></a></li>";
         } else {
            echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/last_off.png' alt=\"".
                       __s('Last')."\" title=\"".__s('Last')."\" class='pointer'></li>";
         }
         echo "</ul></div>";
         echo "<div class='sep'></div>";
      }
   }


   /**
    * @param $options   array
   **/
   function show($options=array()) {

      $this->showTabs($options);
      $this->addDivForTabs($options);
   }


   /**
    * check if main is always display in current Layout
    *
    * @since version 0.90
    *
    * @return bool
    */
   public static function isLayoutWithMain() {
      return (isset($_SESSION['glpilayout']) && in_array($_SESSION['glpilayout'], array('classic', 'vsplit')));
   }


   /**
    * check if page is excluded for splitted layouts
    *
    * @since version 0.90
    *
    * @return bool
    */
   public static function isLayoutExcludedPage() {
      global $CFG_GLPI;

      if (basename($_SERVER['SCRIPT_NAME']) == "updatecurrenttab.php") {
         $base_referer = basename($_SERVER['HTTP_REFERER']);
         $base_referer = explode("?", $base_referer);
         $base_referer = $base_referer[0];
         return in_array($base_referer, $CFG_GLPI['layout_excluded_pages']);
      }

      return in_array(basename($_SERVER['SCRIPT_NAME']), $CFG_GLPI['layout_excluded_pages']);
   }


   /** Display item with tabs
    *
    * @since version 0.85
    *
    * @param $options   array
   **/
   function display($options=array()) {
      global $CFG_GLPI;

      if (isset($options['id'])
          && !$this->isNewID($options['id'])) {
         if (!$this->getFromDB($options['id'])) {
            Html::displayNotFoundError();
         }
      }

      // in case of lefttab layout, we couldn't see "right error" message
      if ($this->get_item_to_display_tab) {
         if (isset($_GET["id"]) && $_GET["id"] && !$this->can($_GET["id"], READ)) {
            // This triggers from a profile switch.
            // If we don't have right, redirect instead to central page
            if (isset($_SESSION['_redirected_from_profile_selector'])
                && $_SESSION['_redirected_from_profile_selector']) {
               unset($_SESSION['_redirected_from_profile_selector']);
               Html::redirect($CFG_GLPI['root_doc']."/front/central.php");
            }
            html::displayRightError();
         }
      }

      // try to lock object
      // $options must contains the id of the object, and if locked by manageObjectLock will contains 'locked' => 1
      ObjectLock::manageObjectLock( get_class( $this ), $options ) ;

      $this->showNavigationHeader($options);
      if (!self::isLayoutExcludedPage() && self::isLayoutWithMain()) {

         if (!isset($options['id'])) {
            $options['id'] = 0;
         }
         $this->showPrimaryForm($options);
      }

      $this->showTabsContent($options);
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

      if (InfoCom::canApplyOn($class)) {
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
      }
   }


   /**
    * Load display options to $_SESSION
    *
    * @since version 0.84
    *
    * @param $sub_itemtype  string   sub itemtype if needed (default '')
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
         echo "\n<form method='get' action='".$CFG_GLPI['root_doc']."/front/display.options.php'>\n";
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

      $rand = mt_rand();

      $link ="<img alt=\"".__s('Display options')."\" title=\"";
      $link .= __s('Display options')."\" src='";
      $link .= $CFG_GLPI["root_doc"]."/pics/options_search.png' ";
      $link .= " class='pointer' onClick=\"".Html::jsGetElementbyID("displayoptions".$rand).".dialog('open');\">";
      $link .= Ajax::createIframeModalWindow("displayoptions".$rand,
                                             $CFG_GLPI['root_doc'].
                                                "/front/display.options.php?itemtype=".
                                                static::getType()."&sub_itemtype=$sub_itemtype",
                                             array('display'       => false,
                                                   'width'         => 600,
                                                   'height'        => 500,
                                                   'reloadonclose' => true));

      return $link;
   }


   /**
    * Get error message for item
    *
    * @since version 0.85
    *
    * @param $error             error type see define.php for ERROR_*
    * @param $object    string  string to use instead of item link (default '')
    *
    * @return link
   **/
   function getErrorMessage($error, $object='') {

      if (empty($object)) {
         $object = $this->getLink();
      }
      switch ($error) {
         case ERROR_NOT_FOUND :
            return sprintf(__('%1$s: %2$s'), $object,  __('Unable to get item'));

         case ERROR_RIGHT :
            return sprintf(__('%1$s: %2$s'), $object,  __('Authorization error'));

         case ERROR_COMPAT :
            return sprintf(__('%1$s: %2$s'), $object,  __('Incompatible items'));

         case ERROR_ON_ACTION :
            return sprintf(__('%1$s: %2$s'), $object,  __('Error on executing the action'));

         case ERROR_ALREADY_DEFINED :
            return sprintf(__('%1$s: %2$s'), $object,  __('Item already defined'));
      }
   }

}
?>
