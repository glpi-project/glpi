<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use Glpi\Application\View\TemplateRenderer;
use Glpi\Plugin\Hooks;
use Glpi\Toolbox\Sanitizer;

/**
 *  Common GLPI object
 **/
class CommonGLPI implements CommonGLPIInterface
{
   /// GLPI Item type cache : set dynamically calling getType
    protected $type                 = -1;

   /// Display list on Navigation Header
    protected $displaylist          = true;

   /// Show Debug
    public $showdebug               = false;

    /**
     * Tab orientation : horizontal or vertical.
     *
     * @var string
     */
    public $taborientation          = 'horizontal';

    /**
     * Rightname used to check rights to do actions on item.
     *
     * @var string
     */
    public static $rightname = '';

    /**
     * Need to get item to show tab
     *
     * @var boolean
     */
    public $get_item_to_display_tab = false;
    protected static $othertabs     = [];


    public function __construct()
    {
    }

    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return __('General');
    }


    /**
     * Return the simplified localized label of the current Type in the context of a form.
     * Avoid to recall the type in the label (Computer status -> Status)
     *
     * Should be overloaded in each new class
     *
     * @return string
     **/
    public static function getFieldLabel()
    {
        return static::getTypeName();
    }


    /**
     * Return the type of the object : class name
     *
     * @return string
     **/
    public static function getType()
    {
        return get_called_class();
    }

    /**
     * Check rights on CommonGLPI Object (without corresponding table)
     * Same signature as CommonDBTM::can but in case of this class, we don't check instance rights
     * so, id and input parameters are unused.
     *
     * @param integer $ID    ID of the item (-1 if new item)
     * @param mixed   $right Right to check : r / w / recursive / READ / UPDATE / DELETE
     * @param array   $input array of input data (used for adding item) (default NULL)
     *
     * @return boolean
     **/
    public function can($ID, $right, array &$input = null)
    {
        switch ($right) {
            case READ:
                return static::canView();

            case UPDATE:
                return static::canUpdate();

            case DELETE:
                return static::canDelete();

            case PURGE:
                return static::canPurge();

            case CREATE:
                return static::canCreate();
        }
        return false;
    }


    /**
     * Have I the global right to "create" the Object
     * May be overloaded if needed (ex KnowbaseItem)
     *
     * @return boolean
     **/
    public static function canCreate()
    {
        if (static::$rightname) {
            return Session::haveRight(static::$rightname, CREATE);
        }
        return false;
    }


    /**
     * Have I the global right to "view" the Object
     *
     * Default is true and check entity if the objet is entity assign
     *
     * May be overloaded if needed
     *
     * @return boolean
     **/
    public static function canView()
    {
        if (static::$rightname) {
            return Session::haveRight(static::$rightname, READ);
        }
        return false;
    }


    /**
     * Have I the global right to "update" the Object
     *
     * Default is calling canCreate
     * May be overloaded if needed
     *
     * @return boolean
     **/
    public static function canUpdate()
    {
        if (static::$rightname) {
            return Session::haveRight(static::$rightname, UPDATE);
        }
    }


    /**
     * Have I the global right to "delete" the Object
     *
     * May be overloaded if needed
     *
     * @return boolean
     **/
    public static function canDelete()
    {
        if (static::$rightname) {
            return Session::haveRight(static::$rightname, DELETE);
        }
        return false;
    }


    /**
     * Have I the global right to "purge" the Object
     *
     * May be overloaded if needed
     *
     * @return boolean
     **/
    public static function canPurge()
    {
        if (static::$rightname) {
            return Session::haveRight(static::$rightname, PURGE);
        }
        return false;
    }


    /**
     * Register tab on an objet
     *
     * @since 0.83
     *
     * @param string $typeform object class name to add tab on form
     * @param string $typetab  object class name which manage the tab
     *
     * @return void
     **/
    public static function registerStandardTab($typeform, $typetab)
    {

        if (isset(self::$othertabs[$typeform])) {
            self::$othertabs[$typeform][] = $typetab;
        } else {
            self::$othertabs[$typeform] = [$typetab];
        }
    }


    /**
     * Get the array of Tab managed by other types
     * Getter for plugin (ex PDF) to access protected property
     *
     * @since 0.83
     *
     * @param string $typeform object class name to add tab on form
     *
     * @return array array of types
     **/
    public static function getOtherTabs($typeform)
    {

        if (isset(self::$othertabs[$typeform])) {
            return self::$othertabs[$typeform];
        }
        return [];
    }


    /**
     * Define tabs to display
     *
     * NB : Only called for existing object
     *
     * @param array $options Options
     *     - withtemplate is a template view ?
     *
     * @return array array containing the tabs
     **/
    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);

        return $ong;
    }


    /**
     * return all the tabs for current object
     *
     * @since 0.83
     *
     * @param array $options Options
     *     - withtemplate is a template view ?
     *
     * @return array array containing the tabs
     **/
    final public function defineAllTabs($options = [])
    {
        global $CFG_GLPI;

        $onglets = [];
       // Tabs known by the object
        if ($this->isNewItem()) {
            $this->addDefaultFormTab($onglets);
        } else {
            $onglets = $this->defineTabs($options);
        }

       // Object with class with 'addtabon' attribute
        if (
            isset(self::$othertabs[$this->getType()])
            && !$this->isNewItem()
        ) {
            foreach (self::$othertabs[$this->getType()] as $typetab) {
                $this->addStandardTab($typetab, $onglets, $options);
            }
        }

        $class = $this->getType();
        if (
            ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
            && (!$this->isNewItem() || $this->showdebug)
            && (method_exists($class, 'showDebug')
              || Infocom::canApplyOn($class)
              || in_array($class, $CFG_GLPI["reservation_types"]))
        ) {
            $onglets[-2] = __('Debug');
        }
        return $onglets;
    }


    /**
     * Add standard define tab
     *
     * @param string $itemtype itemtype link to the tab
     * @param array  $ong      defined tabs
     * @param array  $options  options (for withtemplate)
     *
     * @return CommonGLPI
     **/
    public function addStandardTab($itemtype, array &$ong, array $options)
    {

        $withtemplate = 0;
        if (isset($options['withtemplate'])) {
            $withtemplate = $options['withtemplate'];
        }

        switch ($itemtype) {
            default:
                if (
                    !is_integer($itemtype)
                    && ($obj = getItemForItemtype($itemtype))
                ) {
                    $titles = $obj->getTabNameForItem($this, $withtemplate);
                    if (!is_array($titles)) {
                        $titles = [1 => $titles];
                    }

                    foreach ($titles as $key => $val) {
                        if (!empty($val)) {
                            $ong[$itemtype . '$' . $key] = $val;
                        }
                    }
                }
                break;
        }
        return $this;
    }

    /**
     * Add the impact tab if enabled for this item type
     *
     * @param array  $ong      defined tabs
     * @param array  $options  options (for withtemplate)
     *
     * @return CommonGLPI
     **/
    public function addImpactTab(array &$ong, array $options)
    {
        global $CFG_GLPI;

       // Check if impact analysis is enabled for this item type
        if (Impact::isEnabled(static::class)) {
            $this->addStandardTab('Impact', $ong, $options);
        }

        return $this;
    }

    /**
     * Add default tab for form
     *
     * @since 0.85
     *
     * @param array $ong Tabs
     *
     * @return CommonGLPI
     **/
    public function addDefaultFormTab(array &$ong)
    {

        $ong[$this->getType() . '$main'] = $this->getTypeName(1);
        return $this;
    }


    /**
     * get menu content
     *
     * @since 0.85
     *
     * @return array array for menu
     **/
    public static function getMenuContent()
    {

        $menu       = [];

        $type       = static::getType();
        $item       = new $type();
        $forbidden  = $type::getForbiddenActionsForMenu();

        if ($item instanceof CommonDBTM) {
            if ($type::canView()) {
                $menu['title']           = static::getMenuName();
                $menu['shortcut']        = static::getMenuShorcut();
                $menu['page']            = static::getSearchURL(false);
                $menu['links']['search'] = static::getSearchURL(false);
                $menu['links']['lists']  = "";
                $menu['icon']            = static::getIcon();

                if (
                    !in_array('add', $forbidden)
                    && $type::canCreate()
                ) {
                    if ($item->maybeTemplate()) {
                        $menu['links']['add'] = '/front/setup.templates.php?' . 'itemtype=' . $type .
                                          '&add=1';
                        if (!in_array('template', $forbidden)) {
                              $menu['links']['template'] = '/front/setup.templates.php?' . 'itemtype=' . $type .
                                                  '&add=0';
                        }
                    } else {
                        $menu['links']['add'] = static::getFormURL(false);
                    }
                }

                $extra_links = static::getAdditionalMenuLinks();
                if (is_array($extra_links) && count($extra_links)) {
                    $menu['links'] += $extra_links;
                }
            }
        } else {
            if (
                !method_exists($type, 'canView')
                || $item->canView()
            ) {
                $menu['title']           = static::getMenuName();
                $menu['shortcut']        = static::getMenuShorcut();
                $menu['page']            = static::getSearchURL(false);
                $menu['links']['search'] = static::getSearchURL(false);
                if (method_exists($item, 'getIcon')) {
                    $menu['icon'] = static::getIcon();
                }
            }
        }
        if ($data = static::getAdditionalMenuOptions()) {
            $menu['options'] = $data;
        }
        if ($data = static::getAdditionalMenuContent()) {
            $newmenu = [
                strtolower($type) => $menu,
            ];
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
     * @since 0.85
     *
     * @return array array for menu
     **/
    public static function getAdditionalMenuContent()
    {
        return false;
    }


    /**
     * Get forbidden actions for menu : may be add / template
     *
     * @since 0.85
     *
     * @return array array of forbidden actions
     **/
    public static function getForbiddenActionsForMenu()
    {
        return [];
    }


    /**
     * Get additional menu options
     *
     * @since 0.85
     *
     * @return array array of additional options
     **/
    public static function getAdditionalMenuOptions()
    {
        return false;
    }


    /**
     * Get additional menu links
     *
     * @since 0.85
     *
     * @return array array of additional options
     **/
    public static function getAdditionalMenuLinks()
    {
        return false;
    }


    /**
     * Get menu shortcut
     *
     * @since 0.85
     *
     * @return string character menu shortcut key
     **/
    public static function getMenuShorcut()
    {
        return '';
    }


    /**
     * Get menu name
     *
     * @since 0.85
     *
     * @return string character menu shortcut key
     **/
    public static function getMenuName()
    {
        return static::getTypeName(Session::getPluralNumber());
    }


    /**
     * Get Tab Name used for itemtype
     *
     * NB : Only called for existing object
     *      Must check right on what will be displayed + template
     *
     * @since 0.83
     *
     * @param CommonGLPI $item         Item on which the tab need to be displayed
     * @param boolean    $withtemplate is a template object ? (default 0)
     *
     *  @return string tab name
     **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return '';
    }


    /**
     * show Tab content
     *
     * @since 0.83
     *
     * @param CommonGLPI $item         Item on which the tab need to be displayed
     * @param integer    $tabnum       tab number (default 1)
     * @param boolean    $withtemplate is a template object ? (default 0)
     *
     * @return boolean
     **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        return false;
    }


    /**
     * display standard tab contents
     *
     * @param CommonGLPI $item         Item on which the tab need to be displayed
     * @param string     $tab          tab name
     * @param boolean    $withtemplate is a template object ? (default 0)
     * @param array      $options      additional options to pass
     *
     * @return boolean true
     **/
    public static function displayStandardTab(CommonGLPI $item, $tab, $withtemplate = 0, $options = [])
    {
        switch ($tab) {
           // All tab
            case -1:
                // get tabs and loop over
                $ong = $item->defineAllTabs(['withtemplate' => $withtemplate]);

                if (count($ong)) {
                    foreach ($ong as $key => $val) {
                        if ($key != 'empty') {
                            echo "<div class='alltab'>$val</div>";
                            self::displayStandardTab($item, $key, $withtemplate, $options);
                        }
                    }
                }
                return true;

            case -2:
                $item->showDebugInfo();
                return true;

            default:
                $data     = explode('$', $tab);
                $itemtype = $data[0];
                // Default set
                $tabnum   = 1;
                if (isset($data[1])) {
                    $tabnum = $data[1];
                }

                $options['withtemplate'] = $withtemplate;

                if ($tabnum == 'main') {
                    Plugin::doHook(Hooks::PRE_SHOW_ITEM, ['item' => $item, 'options' => &$options]);
                    $ret = $item->showForm($item->getID(), $options);

                    Plugin::doHook(Hooks::POST_SHOW_ITEM, ['item' => $item, 'options' => $options]);
                    return $ret;
                }

                if (
                    !is_integer($itemtype) && ($itemtype != 'empty')
                    && ($obj = getItemForItemtype($itemtype))
                ) {
                    $options['tabnum'] = $tabnum;
                    $options['itemtype'] = $itemtype;
                    Plugin::doHook(Hooks::PRE_SHOW_TAB, [ 'item' => $item, 'options' => &$options]);
                    $ret = $obj->displayTabContentForItem($item, $tabnum, $withtemplate);

                    Plugin::doHook(Hooks::POST_SHOW_TAB, ['item' => $item, 'options' => $options]);
                    return $ret;
                }
                break;
        }
        return false;
    }


    /**
     * create tab text entry
     *
     * @param string  $text text to display
     * @param integer $nb   number of items (default 0)
     *
     *  @return array array containing the onglets
     **/
    public static function createTabEntry($text, $nb = 0)
    {

        if ($nb) {
           //TRANS: %1$s is the name of the tab, $2$d is number of items in the tab between ()
            $text = sprintf(__('%1$s %2$s'), $text, "<span class='badge'>$nb</span>");
        }
        return $text;
    }


    /**
     * Redirect to the list page from which the item was selected
     * Default to the search engine for the type
     *
     * @return void
     **/
    public function redirectToList()
    {
        global $CFG_GLPI;

        if (
            isset($_GET['withtemplate'])
            && !empty($_GET['withtemplate'])
        ) {
            Html::redirect($CFG_GLPI["root_doc"] . "/front/setup.templates.php?add=0&itemtype=" .
                        $this->getType());
        } else if (
            isset($_SESSION['glpilisturl'][$this->getType()])
                 && !empty($_SESSION['glpilisturl'][$this->getType()])
        ) {
            Html::redirect($_SESSION['glpilisturl'][$this->getType()]);
        } else {
            Html::redirect($this->getSearchURL());
        }
    }


    /**
     * is the current object a new  one - Always false here (virtual Objet)
     *
     * @since 0.83
     *
     * @return boolean
     **/
    public function isNewItem()
    {
        return false;
    }


    /**
     * is the current object a new one - Always true here (virtual Objet)
     *
     * @since 0.84
     *
     * @param integer $ID Id to check
     *
     * @return boolean
     **/
    public static function isNewID($ID)
    {
        return true;
    }


    /**
     * Get the search page URL for the current classe
     *
     * @param boolean $full path or relative one (true by default)
     *
     * @return string
     **/
    public static function getTabsURL($full = true)
    {
        return Toolbox::getItemTypeTabsURL(get_called_class(), $full);
    }


    /**
     * Get the search page URL for the current class
     *
     * @param boolean $full path or relative one (true by default)
     *
     * @return string
     **/
    public static function getSearchURL($full = true)
    {
        return Toolbox::getItemTypeSearchURL(get_called_class(), $full);
    }


    /**
     * Get the form page URL for the current class
     *
     * @param boolean $full path or relative one (true by default)
     *
     * @return string
     **/
    public static function getFormURL($full = true)
    {
        return Toolbox::getItemTypeFormURL(get_called_class(), $full);
    }


    /**
     * Get the form page URL for the current class and point to a specific ID
     *
     * @since 0.90
     *
     * @param integer $id   Id (default 0)
     * @param boolean $full Full path or relative one (true by default)
     *
     * @return string
     **/
    public static function getFormURLWithID($id = 0, $full = true)
    {

        $itemtype = get_called_class();
        $link     = $itemtype::getFormURL($full);
        $link    .= (strpos($link, '?') ? '&' : '?') . 'id=' . $id;
        return $link;
    }


    /**
     * Show tabs content
     *
     * @since 0.85
     *
     * @param array $options parameters to add to URLs and ajax
     *     - withtemplate is a template view ?
     *
     * @return void
     **/
    public function showTabsContent($options = [])
    {

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
            if ($this instanceof CommonITILObject && $this->isNewItem()) {
                $this->input = $cleaned_options;
                $this->saveInput();
               // $extraparamhtml can be too long in case of ticket with content
               // (passed in GET in ajax request)
                unset($cleaned_options['content']);
            }

            // prevent double sanitize, because the includes.php sanitize all data
            $cleaned_options = Sanitizer::unsanitize($cleaned_options);

            $extraparamhtml = "&amp;" . Toolbox::append_params($cleaned_options, '&amp;');
        }

        $onglets     = $this->defineAllTabs($options);
        $display_all = true;
        if (isset($onglets['no_all_tab'])) {
            $display_all = false;
            unset($onglets['no_all_tab']);
        }

        if (count($onglets)) {
            $tabpage = $this->getTabsURL();
            $tabs    = [];

            foreach ($onglets as $key => $val) {
                $tabs[$key] = ['title'  => $val,
                    'url'    => $tabpage,
                    'params' => "_target=$target&amp;_itemtype=" . $this->getType() .
                                            "&amp;_glpi_tab=$key&amp;id=$ID$extraparamhtml"
                ];
            }

            // Not all tab for templates and if only 1 tab
            if (
                $display_all
                && empty($withtemplate)
                && (count($tabs) > 1)
            ) {
                $tabs[-1] = ['title'  => __('All'),
                    'url'    => $tabpage,
                    'params' => "_target=$target&amp;_itemtype=" . $this->getType() .
                                          "&amp;_glpi_tab=-1&amp;id=$ID$extraparamhtml"
                ];
            }

            Ajax::createTabs(
                'tabspanel',
                'tabcontent',
                $tabs,
                $this->getType(),
                $ID,
                $this->taborientation,
                $options
            );
        }
    }


    /**
     * Show tabs
     *
     * @param array $options parameters to add to URLs and ajax
     *     - withtemplate is a template view ?
     *
     * @return void
     **/
    public function showNavigationHeader($options = [])
    {
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
        $withtemplate   = "";

        if (is_array($options) && count($options)) {
            $cleanoptions = $options;
            if (isset($options['withtemplate'])) {
                $withtemplate = $options['withtemplate'];
                unset($cleanoptions['withtemplate']);
            }
            foreach (array_keys($cleanoptions) as $key) {
               // Do not include id options
                if (($key[0] == '_') || ($key == 'id')) {
                    unset($cleanoptions[$key]);
                }
            }
            $extraparamhtml = "&amp;" . Toolbox::append_params($cleanoptions, '&amp;');
        }

        if (
            empty($withtemplate)
            && !$this->isNewID($ID)
            && $this->getType()
            && $this->displaylist
        ) {
            $glpilistitems = & $_SESSION['glpilistitems'][$this->getType()];
            $glpilisttitle = & $_SESSION['glpilisttitle'][$this->getType()];
            $glpilisturl   = & $_SESSION['glpilisturl'][$this->getType()];

            if (empty($glpilisturl)) {
                $glpilisturl = $this->getSearchURL();
            }

           // echo "<div id='menu_navigate'>";

            $next = $prev = $first = $last = -1;
            $current = false;
            if (is_array($glpilistitems)) {
                $current = array_search($ID, $glpilistitems);
                if ($current !== false) {
                    if (isset($glpilistitems[$current + 1])) {
                        $next = $glpilistitems[$current + 1];
                    }

                    if (isset($glpilistitems[$current - 1])) {
                        $prev = $glpilistitems[$current - 1];
                    }

                    $first = $glpilistitems[0];
                    if ($first == $ID) {
                        $first = -1;
                    }

                    $last = $glpilistitems[count($glpilistitems) - 1];
                    if ($last == $ID) {
                        $last = -1;
                    }
                }
            }
            $cleantarget = Html::cleanParametersURL($target);
            echo "<div class='navigationheader justify-content-sm-between'>";

            // First set of header pagination actions, displayed on the left side of the page
            echo "<div>";

            if ($first >= 0) {
                echo "<a href='$cleantarget?id=$first$extraparamhtml'
                     class='btn btn-sm btn-icon btn-ghost-secondary' title=\"" . __s('First') . "\"
                     data-bs-toggle='tooltip' data-bs-placement='bottom'>
                     <i class='fa-lg ti ti-chevrons-left'></i>
                  </a>";
            }

            if ($prev >= 0) {
                echo "<a href='$cleantarget?id=$prev$extraparamhtml'
                     id='previouspage'
                     class='btn btn-sm btn-icon btn-ghost-secondary' title=\"" . __s('Previous') . "\"
                     data-bs-toggle='tooltip' data-bs-placement='bottom'>
                     <i class='fa-lg ti ti-chevron-left'></i>
                  </a>";
                $js = '$("body").keydown(function(e) {
                       if ($("input, textarea").is(":focus") === false) {
                          if(e.keyCode == 37 && e.ctrlKey) {
                            window.location = $("#previouspage").attr("href");
                          }
                       }
                  });';
                echo Html::scriptBlock($js);
            }

            if (!$glpilisttitle) {
                $glpilisttitle = __s('List');
            }
            echo "<a href='$glpilisturl' title=\"$glpilisttitle\"
                  class='btn btn-sm btn-icon btn-ghost-secondary'
                  data-bs-toggle='tooltip' data-bs-placement='bottom'>
                  <i class='far fa-lg fa-list-alt'></i>
               </a>";

            echo "</div>";

            if ($this instanceof CommonITILObject) {
                echo "<h3 class='navigationheader-title strong d-flex align-items-center'>";
                echo "<i class='" . $this->getIcon() . " me-1'></i>";
                echo $this->getNameID();
                echo "</h3>";
            }

            // Second set of header pagination actions, displayed on the right side of the page
            echo "<div>";

            if ($current !== false) {
                echo "<span class='m-1 ms-3'>" . ($current + 1) . "/" . count($glpilistitems) . "</span>";
            }

            if ($next >= 0) {
                echo "<a href='$cleantarget?id=$next$extraparamhtml'
                     id='nextpage'
                     class='btn btn-sm btn-icon btn-ghost-secondary'
                     title=\"" . __s('Next') . "\"
                     data-bs-toggle='tooltip' data-bs-placement='bottom'>" .
                "<i class='fa-lg ti ti-chevron-right'></i>
                    </a>";
                $js = '$("body").keydown(function(e) {
                       if ($("input, textarea").is(":focus") === false) {
                          if(e.keyCode == 39 && e.ctrlKey) {
                            window.location = $("#nextpage").attr("href");
                          }
                       }
                  });';
                echo Html::scriptBlock($js);
            }

            if ($last >= 0) {
                echo "<a href='$cleantarget?id=$last $extraparamhtml'
                     class='btn btn-sm btn-icon btn-ghost-secondary'
                     title=\"" . __s('Last') . "\"
                     data-bs-toggle='tooltip' data-bs-placement='bottom'>" .
                "<i class='fa-lg ti ti-chevrons-right'></i></a>";
            }

            echo "</div>";

            echo "</div>"; // .navigationheader
        }
    }

    /**
     * Compute the name to be used in the main header of this item
     *
     * @return string
     */
    public function getHeaderName(): string
    {
        $name = '';
        if (isset($this->fields['id']) && ($this instanceof CommonDBTM)) {
            $name = $this->getName();
            if ($_SESSION['glpiis_ids_visible'] || empty($name)) {
                $name = sprintf(__('%1$s - ID %2$d'), $name, $this->fields['id']);
            }
        }

        return Sanitizer::unsanitize($name);
    }

    /**
     * Display item with tabs
     *
     * @since 0.85
     *
     * @param array $options Options
     *
     * @return void
     */
    public function display($options = [])
    {
        // Item might already be loaded, skip load and rights checks
        $item_loaded = $options['loaded'] ?? false;
        unset($options['loaded']);
        if (!$item_loaded) {
            if (
                isset($options['id'])
                && !$this->isNewID($options['id'])
            ) {
                if (!$this->getFromDB($options['id'])) {
                    Html::displayNotFoundError();
                }
            }
            // in case of lefttab layout, we couldn't see "right error" message
            if (
                $this->get_item_to_display_tab
                && isset($_GET["id"])
                && $_GET["id"]
                && !$this->can($_GET["id"], READ)
            ) {
                // This triggers from a profile switch.
                // If we don't have right, redirect instead to central page
                Toolbox::handleProfileChangeRedirect();
                Html::displayRightError();
            }
        }

       // try to lock object
       // $options must contains the id of the object, and if locked by manageObjectLock will contains 'locked' => 1
        ObjectLock::manageObjectLock(get_class($this), $options);

       // manage custom options passed to tabs
        if (isset($_REQUEST['tab_params']) && is_array($_REQUEST['tab_params'])) {
            $options += $_REQUEST['tab_params'];
        }

        echo "<div class='d-flex flex-column'>";
        echo "<div class='row'>";
        if ($this instanceof CommonDBTM) {
            TemplateRenderer::getInstance()->display('layout/parts/saved_searches.html.twig', [
                'itemtype' => $this->getType(),
            ]);
        }
        echo "<div class='col'>";
        $this->showNavigationHeader($options);
        $this->showTabsContent($options);
        echo "</div>";
        echo "</div>";
    }


    /**
     * List infos in debug tab
     *
     * @return void
     **/
    public function showDebugInfo()
    {
        global $CFG_GLPI;

        if (method_exists($this, 'showDebug')) {
            $this->showDebug();
        }

        $class = $this->getType();

        if (Infocom::canApplyOn($class)) {
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
     * @since 0.84
     *
     * @param array  $input        data to update
     * @param string $sub_itemtype sub itemtype if needed (default '')
     *
     * @return void
     **/
    public static function updateDisplayOptions($input = [], $sub_itemtype = '')
    {

        $options = static::getAvailableDisplayOptions();
        if (count($options)) {
            if (empty($sub_itemtype)) {
                $display_options = &$_SESSION['glpi_display_options'][self::getType()];
            } else {
                $display_options = &$_SESSION['glpi_display_options'][self::getType()][$sub_itemtype];
            }
           // reset
            if (isset($input['reset'])) {
                foreach ($options as $option_group) {
                    foreach ($option_group as $option_name => $attributs) {
                        $display_options[$option_name] = $attributs['default'];
                    }
                }
            } else {
                foreach ($options as $option_group) {
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
                    $user->update(['id' => $uid,
                        'display_options'
                                        => exportArrayToDB($_SESSION['glpi_display_options'])
                    ]);
                }
            }
        }
    }


    /**
     * Load display options to $_SESSION
     *
     * @since 0.84
     *
     * @param string $sub_itemtype sub itemtype if needed (default '')
     *
     * @return void
     **/
    public static function getDisplayOptions($sub_itemtype = '')
    {

        if (!isset($_SESSION['glpi_display_options'])) {
           // Load display_options from user table
            $_SESSION['glpi_display_options'] = [];
            if ($uid = Session::getLoginUserID()) {
                $user = new User();
                if ($user->getFromDB($uid)) {
                    $_SESSION['glpi_display_options'] = importArrayFromDB($user->fields['display_options']);
                }
            }
        }
        if (!isset($_SESSION['glpi_display_options'][self::getType()])) {
            $_SESSION['glpi_display_options'][self::getType()] = [];
        }

        if (!empty($sub_itemtype)) {
            if (!isset($_SESSION['glpi_display_options'][self::getType()][$sub_itemtype])) {
                $_SESSION['glpi_display_options'][self::getType()][$sub_itemtype] = [];
            }
            $display_options = &$_SESSION['glpi_display_options'][self::getType()][$sub_itemtype];
        } else {
            $display_options = &$_SESSION['glpi_display_options'][self::getType()];
        }

       // Load default values if not set
        $options = static::getAvailableDisplayOptions();
        if (count($options)) {
            foreach ($options as $option_group) {
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
     * Show display options
     *
     * @since 0.84
     *
     * @param string $sub_itemtype sub_itemtype if needed (default '')
     *
     * @return void
     **/
    public static function showDislayOptions($sub_itemtype = '')
    {
        global $CFG_GLPI;

        $options      = static::getAvailableDisplayOptions($sub_itemtype);

        if (count($options)) {
            if (empty($sub_itemtype)) {
                $display_options = $_SESSION['glpi_display_options'][self::getType()];
            } else {
                $display_options = $_SESSION['glpi_display_options'][self::getType()][$sub_itemtype];
            }
            echo "<div class='center'>";
            echo "\n<form method='get' action='" . $CFG_GLPI['root_doc'] . "/front/display.options.php'>\n";
            echo "<input type='hidden' name='itemtype' value='NetworkPort'>\n";
            echo "<input type='hidden' name='sub_itemtype' value='$sub_itemtype'>\n";
            echo "<table class='tab_cadre'>";
            echo "<tr><th colspan='2'>" . __s('Display options') . "</th></tr>\n";
            echo "<tr><td colspan='2'>";
            echo "<input type='submit' class='btn btn-primary' name='reset' value=\"" .
                __('Reset display options') . "\">";
            echo "</td></tr>\n";

            foreach ($options as $option_group_name => $option_group) {
                if (count($option_group) > 0) {
                    echo "<tr><th colspan='2'>$option_group_name</th></tr>\n";
                    foreach ($option_group as $option_name => $attributs) {
                        echo "<tr>";
                        echo "<td>";
                        echo "<input type='checkbox' name='$option_name' " .
                        ($display_options[$option_name] ? 'checked' : '') . ">";
                        echo "</td>";
                        echo "<td>" . $attributs['name'] . "</td>";
                        echo "</tr>\n";
                    }
                }
            }
            echo "<tr><td colspan='2' class='center'>";
            echo "<input type='submit' class='btn btn-primary' name='update' value=\"" . _sx('button', 'Save') . "\">";
            echo "</td></tr>\n";
            echo "</table>";
            echo "</form>";

            echo "</div>";
        }
    }


    /**
     * Get available display options array
     *
     * @since 0.84
     *
     * @return array all the options
     **/
    public static function getAvailableDisplayOptions()
    {
        return [];
    }


    /**
     * Get link for display options
     *
     * @since 0.84
     *
     * @param string $sub_itemtype sub itemtype if needed for display options
     *
     * @return string
     **/
    public static function getDisplayOptionsLink($sub_itemtype = '')
    {
        global $CFG_GLPI;

        $rand = mt_rand();

        $link = "<span class='fa fa-wrench pointer' title=\"";
        $link .= __s('Display options') . "\" ";
        $link .= " data-bs-toggle='modal' data-bs-target='#displayoptions$rand'";
        $link .= "><span class='sr-only'>" . __s('Display options') . "</span></span>";
        $link .= Ajax::createIframeModalWindow(
            "displayoptions" . $rand,
            $CFG_GLPI['root_doc'] .
                                                "/front/display.options.php?itemtype=" .
                                                static::getType() . "&sub_itemtype=$sub_itemtype",
            ['display'       => false,
                'width'         => 600,
                'height'        => 500,
                'reloadonclose' => true
            ]
        );

        return $link;
    }


    /**
     * Get error message for item
     *
     * @since 0.85
     *
     * @param integer $error  error type see define.php for ERROR_*
     * @param string  $object string to use instead of item link (default '')
     *
     * @return string
     **/
    public function getErrorMessage($error, $object = '')
    {

        if (empty($object)) {
            $object = $this->getLink();
        }
        switch ($error) {
            case ERROR_NOT_FOUND:
                return sprintf(__('%1$s: %2$s'), $object, __('Unable to get item'));

            case ERROR_RIGHT:
                return sprintf(__('%1$s: %2$s'), $object, __('Authorization error'));

            case ERROR_COMPAT:
                return sprintf(__('%1$s: %2$s'), $object, __('Incompatible items'));

            case ERROR_ON_ACTION:
                return sprintf(__('%1$s: %2$s'), $object, __('Error on executing the action'));

            case ERROR_ALREADY_DEFINED:
                return sprintf(__('%1$s: %2$s'), $object, __('Item already defined'));
        }
    }

    /**
     * Get links to Faq
     **/
    public function getKBLinks()
    {
        global $CFG_GLPI, $DB;

        $ret = '';
        $title = __s('FAQ');
        if (Session::getCurrentInterface() == 'central') {
            $title = __s('Knowledge base');
        }

        $iterator = $DB->request([
            'SELECT' => [KnowbaseItem::getTable() . '.*'],
            'FROM'   => KnowbaseItem::getTable(),
            'WHERE'  => [
                KnowbaseItem_Item::getTable() . '.items_id'  => $this->fields['id'],
                KnowbaseItem_Item::getTable() . '.itemtype'  => $this->getType(),
            ],
            'INNER JOIN'   => [
                KnowbaseItem_Item::getTable() => [
                    'ON'  => [
                        KnowbaseItem_Item::getTable() => KnowbaseItem::getForeignKeyField(),
                        KnowbaseItem::getTable()      => 'id'
                    ]
                ]
            ],
            'ORDER' => [
                KnowbaseItem::getTable()      => 'name'
            ]
        ]);

        $found_kbitem = [];
        foreach ($iterator as $line) {
            $found_kbitem[$line['id']] = $line;
            $kbitem_ids[$line['id']] = $line['id'];
        }

        if (count($found_kbitem)) {
            $rand = mt_rand();
            $kbitem = new KnowbaseItem();
            $kbitem->getFromDB(reset($found_kbitem)['id']);
            $ret .= "<div class='faqadd_block'>";
            $ret .= "<label for='display_faq_chkbox$rand'>";
            $ret .= "<img src='" . $CFG_GLPI["root_doc"] . "/pics/faqadd.png' class='middle pointer'
                    alt=\"$title\" title=\"$title\">";
            $ret .= "</label>";
            $ret .= "<input type='checkbox'  class='display_faq_chkbox' id='display_faq_chkbox$rand'>";
            $ret .= "<div class='faqadd_entries' style='position:relative;'>";
            if (count($found_kbitem) == 1) {
                $ret .= "<div class='faqadd_block_content' id='faqadd_block_content$rand'>";
                $ret .= $kbitem->showFull(['display' => false]);
                $ret .= "</div>"; // .faqadd_block_content
            } else {
                $ret .= Html::scriptBlock("
                var getKnowbaseItemAnswer$rand = function() {
                    var knowbaseitems_id = $('#dropdown_knowbaseitems_id$rand').val();
                    $('#faqadd_block_content$rand').load(
                        '" . $CFG_GLPI['root_doc'] . "/ajax/getKnowbaseItemAnswer.php',
                        {
                            'knowbaseitems_id': knowbaseitems_id
                        }
                    );
                };
                ");
                $ret .= "<label for='dropdown_knowbaseitems_id$rand'>" .
                    KnowbaseItem::getTypeName() . "</label>&nbsp;";
                $ret .= KnowbaseItem::dropdown([
                    'value'     => reset($found_kbitem)['id'],
                    'display'   => false,
                    'rand'      => $rand,
                    'condition' => [
                        KnowbaseItem::getTable() . '.id' => $kbitem_ids
                    ],
                    'on_change' => "getKnowbaseItemAnswer$rand()"
                ]);
                $ret .= "<div class='faqadd_block_content' id='faqadd_block_content$rand'>";
                $ret .= $kbitem->showFull(['display' => false]);
                $ret .= "</div>"; // .faqadd_block_content
            }
            $ret .= "</div>"; // .faqadd_entries
            $ret .= "</div>"; // .faqadd_block
        }
        return $ret;
    }
}
