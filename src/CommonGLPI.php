<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Plugin\Hooks;
use Glpi\Search\FilterableInterface;

/**
 *  Common GLPI object
 **/
class CommonGLPI implements CommonGLPIInterface
{
    /**
     * Show the title of the item in the navigation header ?
     */
    protected static $showTitleInNavigationHeader = false;

   /**
    * Display list on Navigation Header
    *
    * @var boolean
    */
    protected $displaylist          = true;

    /**
     * Show Debug
     *
     * @var boolean
     */
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

    /**
     * List of tabs to add (registered by `self::registerStandardTab()`).
     * Array structure looks like:
     *  [
     *      "Computer" => [ // item on which the tab will be added
     *          "PluginAwesomeItem" // item that will provide the tab
     *              => 100, // weight value used when sorting tabs
     *      ]
     *  ]
     *
     * @var array
     */
    private static $othertabs = [];

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
     * @param int $right Right to check : r / w / recursive / READ / UPDATE / DELETE
     * @param ?array   $input array of input data (used for adding item) (default NULL)
     *
     * @return boolean
     **/
    public function can($ID, int $right, ?array &$input = null): bool
    {
        return match ($right) {
            READ => static::canView(),
            UPDATE => static::canUpdate(),
            DELETE => static::canDelete(),
            PURGE => static::canPurge(),
            CREATE => static::canCreate(),
            default => false,
        };
    }

    /**
     * Have I the global right to "create" the Object
     * May be overloaded if needed (ex KnowbaseItem)
     *
     * @return boolean
     **/
    public static function canCreate(): bool
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
    public static function canView(): bool
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
    public static function canUpdate(): bool
    {
        if (static::$rightname) {
            return Session::haveRight(static::$rightname, UPDATE);
        }
        return false;
    }

    /**
     * Have I the global right to "delete" the Object
     *
     * May be overloaded if needed
     *
     * @return boolean
     **/
    public static function canDelete(): bool
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
    public static function canPurge(): bool
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
     * @param string $typeform  object class name to add tab on form
     * @param string $typetab   object class name which manage the tab
     * @param int $order        Weight value used when sorting tabs. Lower values will be displayed before higher values.
     *
     * @return void
     **/
    public static function registerStandardTab($typeform, $typetab, int $order = 500)
    {

        if (isset(self::$othertabs[$typeform])) {
            self::$othertabs[$typeform][$typetab] = $order;
        } else {
            self::$othertabs[$typeform] = [$typetab => $order];
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
            $othertabs = self::$othertabs[$typeform];
            asort($othertabs);
            return array_keys($othertabs);
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

        if ($this instanceof FilterableInterface) {
            $this->addStandardTab('Glpi\Search\CriteriaFilter', $ong, $options);
        }

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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $onglets = [];
       // Tabs known by the object
        if ($this->isNewItem()) {
            $this->addDefaultFormTab($onglets);
        } else {
            $onglets = $this->defineTabs($options);
        }

       // Object with class with 'addtabon' attribute
        if (!$this->isNewItem()) {
            $othertabs = self::getOtherTabs($this->getType());
            foreach ($othertabs as $typetab) {
                $this->addStandardTab($typetab, $onglets, $options);
            }
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
        /** @var array $CFG_GLPI */
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
        $icon = '';
        if (method_exists(static::class, 'getIcon')) {
            $icon = static::getIcon();
        }
        $icon = $icon ? "<i class='$icon me-2'></i>" : '';
        $ong[static::getType() . '$main'] = '<span>' . $icon . static::getTypeName(1) . '</span>';
        return $this;
    }

    /**
     * get menu content
     *
     * @since 0.85
     *
     * @return false|array array for menu
     **/
    public static function getMenuContent()
    {

        $menu       = [];

        $type       = static::getType();
        $item       = new static();
        $forbidden  = $item->getForbiddenActionsForMenu();

        if ($item instanceof CommonDBTM) {
            if ($item->canView()) {
                $menu['title']           = $item->getMenuName();
                $menu['shortcut']        = $item->getMenuShorcut();
                $menu['page']            = $item->getSearchURL(false);
                $menu['links']['search'] = $item->getSearchURL(false);
                $menu['links']['lists']  = "";
                $menu['lists_itemtype']  = $item->getType();
                $menu['icon']            = $item->getIcon();

                if (
                    !in_array('add', $forbidden)
                    && $item->canCreate()
                ) {
                    if ($item->maybeTemplate()) {
                        $menu['links']['add'] = '/front/setup.templates.php?' . 'itemtype=' . $type .
                                          '&add=1';
                        if (!in_array('template', $forbidden)) {
                              $menu['links']['template'] = '/front/setup.templates.php?' . 'itemtype=' . $type .
                                                  '&add=0';
                        }
                    } else {
                        $menu['links']['add'] = $item->getFormURL(false);
                    }
                }

                $extra_links = $item->getAdditionalMenuLinks();
                if (is_array($extra_links) && count($extra_links)) {
                    $menu['links'] += $extra_links;
                }
            }
        } else {
            if (
                !method_exists($type, 'canView')
                || $item->canView()
            ) {
                $menu['title']           = $item->getMenuName();
                $menu['shortcut']        = $item->getMenuShorcut();
                $menu['page']            = $item->getSearchURL(false);
                $menu['links']['search'] = $item->getSearchURL(false);
                if (method_exists($item, 'getIcon')) {
                    $menu['icon'] = $item->getIcon();
                }
            }
        }
        if ($data = $item->getAdditionalMenuOptions()) {
            $menu['options'] = $data;
        }
        if ($data = $item->getAdditionalMenuContent()) {
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
     * @return false|array array for menu
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
     * @return array|false array of additional options, false if no options
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
     * @return false|array
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
     * @param integer    $withtemplate is a template object ? (default 0)
     *
     *  @return string|string[] The tab name(s).
     *  Must be:
     *  - A string if there is a single tab
     *  - An array of string if there are multiple tabs.
     *  - An an empty string if there is no tabs.
     **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return '';
    }

    public static function getSectorizedDetails(): array
    {
        return [];
    }

    public static function getHeaderParameters(): array
    {
        return [
            static::getTypeName(\Session::getPluralNumber()),
            '',
            ...static::getSectorizedDetails(),
        ];
    }

    /**
     * show Tab content
     *
     * @since 0.83
     *
     * @param CommonGLPI $item         Item on which the tab need to be displayed
     * @param integer    $tabnum       tab number (default 1)
     * @param integer    $withtemplate is a template object ? (default 0)
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
     * @param integer    $withtemplate is a template object ? (default 0)
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
                            echo "<div class='alltab'>" . $val . "</div>";
                            self::displayStandardTab($item, $key, $withtemplate, $options);
                        }
                    }
                }
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
                    /** @var CommonDBTM $item */
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
                    \Glpi\Debug\Profiler::getInstance()->start(get_class($obj) . '::displayTabContentForItem');
                    $ret = $obj->displayTabContentForItem($item, $tabnum, $withtemplate);
                    \Glpi\Debug\Profiler::getInstance()->stop(get_class($obj) . '::displayTabContentForItem');

                    Plugin::doHook(Hooks::POST_SHOW_TAB, ['item' => $item, 'options' => $options]);
                    return $ret;
                }
                break;
        }
        return false;
    }

    /**
     * @param class-string<CommonGLPI>|null $form_itemtype
     * @return string
     */
    private static function getTabIconClass(?string $form_itemtype = null): string
    {
        $default_icon = CommonDBTM::getIcon();
        $icon = $default_icon;
        $tab_itemtype = static::class;
        $itemtype = $tab_itemtype;

        if (is_subclass_of($tab_itemtype, CommonDBRelation::class)) {
            // Get opposite itemtype than this
            $new_itemtype = $tab_itemtype::getOppositeItemtype($form_itemtype);
            if ($new_itemtype !== null) {
                $itemtype = $new_itemtype;
            }
        }
        if ($icon === $default_icon && !class_exists($itemtype)) {
            $itemtype = $tab_itemtype;
        }
        if ($icon === $default_icon && method_exists($itemtype, 'getIcon')) {
            $icon = $itemtype::getIcon();
        }
        return $icon;
    }

    /**
     * Create tab text entry.
     *
     * This should be called on the itemtype whose form is being displayed and not on the tab itemtype for the correct
     * icon to be displayed, unless you manually specify the icon.
     *
     * @param string  $text text to display
     * @param integer $nb   number of items (default 0)
     * @param class-string<CommonGLPI>|null $form_itemtype
     * @param string $icon
     *
     *  @return string The tab text (including icon and counter if applicable)
     **/
    public static function createTabEntry($text, $nb = 0, ?string $form_itemtype = null, string $icon = '')
    {
        if ($icon === '') {
            $icon = static::getTabIconClass($form_itemtype);
        }
        if (str_contains($icon, 'fa-empty-icon')) {
            $icon = '';
        }

        $icon_html = $icon !== '' ? sprintf('<i class="%s me-2"></i>', htmlescape($icon)) : '';
        $counter_html = $nb !== 0 ? sprintf(' <span class="badge glpi-badge">%d</span>', (int)$nb) : '';

        return sprintf(
            '<span class="d-flex align-items-center">%s%s%s</span>',
            $icon_html,
            htmlescape($text),
            $counter_html
        );
    }

    /**
     * Redirect to the list page from which the item was selected
     * Default to the search engine for the type
     *
     * @return void
     **/
    public function redirectToList(): void
    {
        Html::redirect($this->getRedirectToListUrl());
    }

    public function getRedirectToListUrl(): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!empty($_GET['withtemplate'])) {
            return $CFG_GLPI["root_doc"] . "/front/setup.templates.php?add=0&itemtype=" . static::getType();
        }

        if (
            isset($_SESSION['glpilisturl'][static::getType()])
            && !empty($_SESSION['glpilisturl'][static::getType()])
        ) {
            return $_SESSION['glpilisturl'][static::getType()];
        }

        return static::getSearchURL();
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
        $link     = static::getFormURL($full);
        $link    .= (strpos($link, '?') ? '&' : '?') . 'id=' . ((int) $id);
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

        $cleaned_options = $options;
        unset($cleaned_options['id'], $cleaned_options['stock_image']);

        $target         = $_SERVER['PHP_SELF'];
        $withtemplate   = "";

        // TODO - There should be a better option than checking whether or not
        // $options is empty.
        // See:
        //  - https://github.com/glpi-project/glpi/pull/12929
        //  - https://github.com/glpi-project/glpi/pull/13101
        //  - https://github.com/glpi-project/glpi/commit/1d27bf14d4527f748876dcd556f2b995a0bf7684
        if (is_array($cleaned_options) && count($cleaned_options)) {
            if (isset($options['withtemplate'])) {
                $withtemplate = $options['withtemplate'];
            }

            if ($this instanceof CommonITILObject && $this->isNewItem()) {
                $this->input = $cleaned_options;
                $this->saveInput();
               // $extraparamhtml can be too long in case of ticket with content
               // (passed in GET in ajax request)
                unset($cleaned_options['content']);
            }
        }

        $onglets     = $this->defineAllTabs($options);
        $display_all = true;
        if (isset($onglets['no_all_tab'])) {
            $display_all = false;
            unset($onglets['no_all_tab']);
        }

        if (count($onglets)) {
            $tabs_url   = $this->getTabsURL();
            $parsed_url = parse_url($tabs_url);
            $tab_path   = $parsed_url['path'];
            $tab_params = [];
            if (array_key_exists('query', $parsed_url)) {
                parse_str($parsed_url['query'], $tab_params);
            }

            $tab_params = array_merge($cleaned_options, $tab_params);

            $tab_params = array_merge(
                $tab_params,
                [
                    '_target' => $target,
                    '_itemtype' => $this->getType(),
                    'id' => $ID,
                ]
            );

            $tabs = [];
            foreach ($onglets as $key => $val) {
                if ($val === null) {
                    // This is a placeholder tab
                    continue;
                }
                $tabs[$key] = ['title'  => $val,
                    'url'    => $tab_path,
                    'params' => Toolbox::append_params(['_glpi_tab' => $key] + $tab_params, '&amp;'),
                ];
            }

            // Not all tab for templates and if only 1 tab
            if (
                $display_all
                && empty($withtemplate)
                && (count($tabs) > 1)
            ) {
                $tabs[-1] = ['title'  => static::createTabEntry(__('All'), 0, null, 'ti ti-layout-list'),
                    'url'    => $tab_path,
                    'params' => Toolbox::append_params(['_glpi_tab' => '-1'] + $tab_params, '&amp;'),
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
        /** @var array $CFG_GLPI */
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

        if (is_array($options) && count($options)) {
            $cleanoptions = $options;
            if (isset($options['withtemplate'])) {
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
            !$this->isNewID($ID)
            && $this->getType()
            && $this->displaylist
        ) {
            $glpilistitems = & $_SESSION['glpilistitems'][$this->getType()];
            $glpilisttitle = & $_SESSION['glpilisttitle'][$this->getType()];
            $glpilisturl   = & $_SESSION['glpilisturl'][$this->getType()];
            if ($this instanceof CommonDBChild && $parent = $this->getItem(true, false)) {
                $glpilisturl = $parent::getFormURLWithID($parent->fields['id'], true);
            }
            if (empty($glpilisturl)) {
                $glpilisturl = $this->getSearchURL();
            }

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
            $is_deleted = $this instanceof CommonDBTM && $this->isField('is_deleted') && $this->fields['is_deleted'];
            echo "<div id='navigationheader' class='navigationheader justify-content-sm-between " . ($is_deleted ? 'asset-deleted' : '') . "'>";

            // First set of header pagination actions, displayed on the left side of the page
            echo "<div class='left-icons'>";

            if (!$glpilisttitle) {
                $glpilisttitle = __('List');
            }
            $list = "<a href='$glpilisturl' title=\"" . htmlescape($glpilisttitle) . "\"
                  class='btn btn-sm btn-icon btn-ghost-secondary me-2'
                  data-bs-toggle='tooltip' data-bs-placement='bottom'>
                  <i class='ti ti-list-search fa-lg'></i>
               </a>";
            $list_shown = false;

            if ($first < 0) {
                // Show list icon before the placeholder space for the first pagination button
                echo $list;
                $list_shown = true;
            }
            echo "<a href='$cleantarget?id=$first$extraparamhtml'
                 class='btn btn-sm btn-icon btn-ghost-secondary me-2 " . ($first >= 0 ? '' : 'bs-invisible') . "' title=\"" . __s('First') . "\"
                 data-bs-toggle='tooltip' data-bs-placement='bottom'>
                 <i class='fa-lg ti ti-chevrons-left'></i>
              </a>";

            if (!$list_shown && $prev < 0) {
                // Show list icon before the placeholder space for the "prev" pagination button
                echo $list;
                $list_shown = true;
            }
            echo "<a href='$cleantarget?id=$prev$extraparamhtml'
                 id='previouspage'
                 class='btn btn-sm btn-icon btn-ghost-secondary me-2 " . ($prev >= 0 ? '' : 'bs-invisible') . "' title=\"" . __s('Previous') . "\"
                 data-bs-toggle='tooltip' data-bs-placement='bottom'>
                 <i class='fa-lg ti ti-chevron-left'></i>
              </a>";
            if ($prev >= 0) {
                $js = '$("body").keydown(function(e) {
                       if ($("input, textarea").is(":focus") === false) {
                          if(e.keyCode == 37 && e.ctrlKey) {
                            window.location = $("#previouspage").attr("href");
                          }
                       }
                  });';
                echo Html::scriptBlock($js);
            }

            // If both first and prev buttons shown, the list should be added now
            if (!$list_shown) {
                echo $list;
            }

            echo "</div>";

            if (static::$showTitleInNavigationHeader && $this instanceof CommonDBTM) {
                echo "<h3 class='navigationheader-title strong d-flex align-items-center'>";
                if (method_exists($this, 'getStatusIcon') && $this->isField('status')) {
                    echo "<span class='me-1'>" . $this->getStatusIcon($this->fields['status']) . '</span>';
                }
                echo htmlescape($this->getNameID([
                    'forceid' => $this instanceof CommonITILObject
                ]));
                if ($this->isField('is_deleted') && $this->fields['is_deleted']) {
                    $title = $this->isField('date_mod')
                                ? sprintf(__s('Item has been deleted on %s'), Html::convDateTime($this->fields['date_mod']))
                                : __s('Deleted');
                    echo "<span class='mx-2 status rounded-1' title=\"" . $title . "\"
                        data-bs-toggle='tooltip'>
                        <i class='ti ti-trash'></i>";
                        echo __s('Deleted');
                    echo "</span>";
                }
                echo "</h3>";
            } else {
                echo TemplateRenderer::getInstance()->render('components/form/header_content.html.twig', [
                    'item'          => $this,
                    'params'        => $options,
                    'in_navheader'  => true,
                    'header_toolbar' => $this->getFormHeaderToolbar(),
                ]);
            }

            // Second set of header pagination actions, displayed on the right side of the page
            echo "<div class='right-icons>";

            echo "<span class='py-1 px-3 " . ($current !== false ? '' : 'bs-invisible') . "'>" . ($current + 1) . "/" . count($glpilistitems ?? []) . "</span>";

            echo "<a href='$cleantarget?id=$next$extraparamhtml'
                 id='nextpage'
                 class='btn btn-sm btn-icon btn-ghost-secondary ms-2 " . ($next >= 0 ? '' : 'bs-invisible') . "'
                 title=\"" . __s('Next') . "\"
                 data-bs-toggle='tooltip' data-bs-placement='bottom'>" .
            "<i class='fa-lg ti ti-chevron-right'></i>
                </a>";
            if ($next >= 0) {
                $js = '$("body").keydown(function(e) {
                       if ($("input, textarea").is(":focus") === false) {
                          if(e.keyCode == 39 && e.ctrlKey) {
                            window.location = $("#nextpage").attr("href");
                          }
                       }
                  });';
                echo Html::scriptBlock($js);
            }

            echo "<a href='$cleantarget?id=$last $extraparamhtml'
                 class='btn btn-sm btn-icon btn-ghost-secondary ms-2 " . ($last >= 0 ? '' : 'bs-invisible') . "'
                 title=\"" . __s('Last') . "\"
                 data-bs-toggle='tooltip' data-bs-placement='bottom'>" .
            "<i class='fa-lg ti ti-chevrons-right'></i></a>";

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

        return $name;
    }

    /**
     * Display item with tabs
     *
     * @since 0.85
     *
     * @param array $options Options
     *                       show_nav_header (default true): show navigation header (link to list of items)
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
                $this instanceof CommonDBTM
                && isset($options['id'])
                && !$this->isNewID($options['id'])
            ) {
                if (!$this->getFromDB($options['id'])) {
                    throw new NotFoundHttpException();
                }
            }
            // in case of lefttab layout, we couldn't see "right error" message
            if (
                $this->get_item_to_display_tab
                && isset($_GET["id"])
                && $_GET["id"]
                && !$this->can($_GET["id"], READ)
            ) {
                throw new AccessDeniedHttpException();
            }
        }

       // try to lock object
       // $options must contain the id of the object, and if locked by manageObjectLock will contain 'locked' => 1
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
        if (($options['show_nav_header'] ?? true)) {
            $this->showNavigationHeader($options);
        }
        $this->showTabsContent($options);
        echo "</div>";
        echo "</div>";
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
        if (empty($object) && $this instanceof CommonDBTM) {
            $object = $this->getLink();
        }
        return match ($error) {
            ERROR_NOT_FOUND => sprintf(__('%1$s: %2$s'), $object, __('Unable to get item')),
            ERROR_RIGHT => sprintf(__('%1$s: %2$s'), $object, __('Authorization error')),
            ERROR_COMPAT => sprintf(__('%1$s: %2$s'), $object, __('Incompatible items')),
            ERROR_ON_ACTION => sprintf(__('%1$s: %2$s'), $object, __('Error on executing the action')),
            ERROR_ALREADY_DEFINED => sprintf(__('%1$s: %2$s'), $object, __('Item already defined')),
            default => '',
        };
    }

    /**
     * Get links to Faq
     **/
    public function getKBLinks()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        if (!($this instanceof CommonDBTM)) {
            return '';
        }

        $ret = '';
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
            $ret .= "<i class='ti ti-zoom-question'></i>";
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

    /**
     * Get array of extra form header toolbar buttons
     * @return array Array of HTML elements
     */
    protected function getFormHeaderToolbar(): array
    {
        return [];
    }
}
