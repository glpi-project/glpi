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

namespace Glpi\Dashboard;

use Config;
use DateInterval;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Plugin\Hooks;
use Html;
use Plugin;
use Ramsey\Uuid\Uuid;
use Reminder;
use Session;
use ShareDashboardDropdown;
use Telemetry;
use Ticket;
use Toolbox;

class Grid
{
    protected $cell_margin     = 6;
    protected $grid_cols       = 26;
    protected $grid_rows       = 24;
    protected $current         = "";
    protected $dashboard       = null;
    protected $items           = [];
    protected $context            = '';

    public static $embed              = false;
    public static $all_dashboards     = [];

    public function __construct(string $dashboard_key = "central", int $grid_cols = 26, int $grid_rows = 24, string $context = 'core')
    {

        $this->current   = $dashboard_key;
        $this->grid_cols = $grid_cols;
        $this->grid_rows = $grid_rows;

        $this->dashboard = new Dashboard($dashboard_key);
        $this->context   = $context;
    }


    /**
     * Return the instance of current dasbhoard
     *
     * @return Dashboard
     */
    public function getDashboard()
    {
        return $this->dashboard;
    }

    /**
     * Return the context used for this Grid instance
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }


    /**
     * load all existing dashboards from DB into a static property for caching data
     *
     * @param bool $force, if false, don't use cache
     *
     * @return bool
     */
    public static function loadAllDashboards(bool $force = true): bool
    {
        if (
            !is_array(self::$all_dashboards)
            || count(self::$all_dashboards) === 0
            || $force
        ) {
            self::$all_dashboards = Dashboard::getAll($force, !self::$embed, '');
        }

        return is_array(self::$all_dashboards);
    }


    /**
     * Init dashboards cards
     * A define.php constant (GLPI_AJAX_DASHBOARD) exists to control how the cards should be loaded
     *  - if true: load all cards in seperate ajax request
     *    pros: slow cards wont impact the others
     * - else: load all cards in a single ajax request
     *    pros: less strain for the server
     *
     * @return void
     */
    public function getCards()
    {
        self::loadAllDashboards();

        if (
            !isset(self::$all_dashboards[$this->current])
            || !isset(self::$all_dashboards[$this->current]['items'])
        ) {
            self::$all_dashboards[$this->current] = [
                'items' => []
            ];
        }

        foreach (self::$all_dashboards[$this->current]['items'] as $specs) {
            $card_id      = $specs['card_id'] ?? $specs['gridstack_id'] ?? $specs['id'];
            $gridstack_id = $specs['gridstack_id']   ?? $specs['id'];
            $card_options = ($specs['card_options'] ?? []) + [
                'card_id' => $card_id
            ];

            $card_html    = <<<HTML
            <div class="loading-card">
               <i class="fas fa-spinner fa-spin fa-3x"></i>
            </div>
HTML;
            $this->addGridItem(
                $card_html,
                $gridstack_id,
                $specs['x'] ?? -1,
                $specs['y'] ?? -1,
                $specs['width'] ?? 2,
                $specs['height'] ?? 2,
                $card_options
            );
        }
    }


    /**
     * Do we have the right to view at least one dashboard int the current collection
     *
     * @return bool
     */
    public function canViewCurrent(): bool
    {
       // check global (admin) right
        if (Dashboard::canView()) {
            return true;
        }

        return $this->dashboard->canViewCurrent();
    }


    /**
     * Do we have the right to view at least one dashboard?
     *
     * This can be optionally restricted to a specific context.
     * @return bool
     */
    public static function canViewOneDashboard($context = null): bool
    {
        if (Dashboard::canView()) {
            return true;
        }

        self::loadAllDashboards();

        $viewable = self::$all_dashboards;
        if ($context) {
            $viewable = array_filter(self::$all_dashboards, function ($dashboard) use ($context) {
                return $dashboard['context'] === $context;
            });
        }
        return (count($viewable) > 0);
    }


    /**
     * Do we have the right to view the specified dashboard int the current collection
     *
     * @param string $key the dashboard to check
     * @param bool   $canViewAll Right to view all dashboards
     *
     * @return bool
     */
    public static function canViewSpecificicDashboard($key, $canViewAll = false): bool
    {
        self::loadAllDashboards();

        $dashboard = new Dashboard($key);
        $dashboard->load();
       // check global (admin) right
        if (Dashboard::canView() && !$dashboard->isPrivate()) {
            return true;
        }

        return isset(self::$all_dashboards[$key]);
    }


    /**
     * Display grid for the current dashboard
     *
     * @return void display html of the grid
     */
    public function show(bool $mini = false, ?string $token = null)
    {
        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;

        $rand = mt_rand();

        if (!self::$embed && !$this->dashboard->canViewCurrent()) {
            return;
        }

        self::loadAllDashboards();

        $this->restoreLastDashboard();

        if ($mini) {
            $this->cell_margin = 3;
        }

        $embed_class   = self::$embed ? "embed" : "";
        $mini_class    = $mini ? "mini" : "";

        $nb_dashboards = count(self::$all_dashboards);

        $can_view_all  = Session::haveRight('dashboard', READ) || self::$embed;
        $can_create    = Session::haveRight('dashboard', CREATE);
        $can_edit      = Session::haveRight('dashboard', UPDATE) && $nb_dashboards;
        $can_purge     = Session::haveRight('dashboard', PURGE) && $nb_dashboards;
        $can_clone     = $can_create && $nb_dashboards;

       // prepare html for add controls
        $add_controls = "";
        for ($y = 0; $y < $this->grid_rows; $y++) {
            for ($x = 0; $x < $this->grid_cols; $x++) {
                $add_controls .= "<div class='cell-add' data-x='$x' data-y='$y'>&nbsp;</div>";
            }
        }

        // Force clear the cards cache in debug mode
        if ($_SESSION['glpi_use_mode'] === Session::DEBUG_MODE) {
            $GLPI_CACHE->delete(self::getAllDashboardCardsCacheKey());
        }

        // prepare all available cards
        $cards = $this->getAllDasboardCards();

       // prepare all available widgets
        $all_widgets = Widget::getAllTypes();

       // prepare labels
        $embed_label      = __("Share or embed this dashboard");
        $delete_label     = __("Delete this dashboard");
        $history_label    = __("Toggle auto-refresh");
        $night_label      = __("Toggle night mode");
        $fs_label         = __("Toggle fullscreen");
        $clone_label      = __("Clone this dashboard");
        $edit_label       = __("Toggle edit mode");
        $add_filter_lbl   = __("Add filter");
        $add_dash_label   = __("Add a new dashboard");
        $save_label       = _x('button', "Save");

        $gridstack_items = $this->getGridItemsHtml(!$mini);

        $dropdown_dashboards = "";
        if ($nb_dashboards) {
            $dropdown_dashboards = self::dropdownDashboard("", [
                'value'        => $this->current,
                'display'      => false,
                'class'        => 'dashboard_select form-select',
                'can_view_all' => $can_view_all,
                'noselect2'    => true,
                'context'      => $this->context
            ]);
        }

        $dashboard_title = $this->dashboard->getTitle();

        $l_tb_icons   = "";
        $r_tb_icons   = "";
        $rename       = "";
        $left_toolbar = "";
        $grid_guide   = "";

        if (!self::$embed) {
            if (!$mini && $can_create) {
                $l_tb_icons .= "<i class='btn btn-outline-secondary fas fa-plus fs-toggle add-dashboard' title='$add_dash_label'></i>";
            }
            if (!$mini && $can_clone) {
                $r_tb_icons .= "<i class='btn btn-outline-secondary ti ti-copy fs-toggle clone-dashboard' title='$clone_label'></i>";
            }
            if (!$mini && $can_edit) {
                $r_tb_icons .= "<i class='btn btn-outline-secondary ti ti-share fs-toggle open-embed' title='$embed_label'></i>";
                $rename = "<div class='edit-dashboard-properties'>
               <input type='text' class='dashboard-name form-control' value='{$dashboard_title}' size='1'>
               <i class='btn btn-outline-secondary far fa-save save-dashboard-name' title='{$save_label}'></i>
               <span class='display-message'></span>
            </div>";
            }
            if (!$mini && $can_purge) {
                $r_tb_icons .= "<i class='btn btn-outline-secondary ti ti-trash fs-toggle delete-dashboard' title='$delete_label'></i>";
            }
            if ($can_edit) {
                $r_tb_icons .= "<i class='btn btn-outline-secondary ti ti-edit fs-toggle edit-dashboard' title='$edit_label'></i>";
            }

            if (!$mini) {
                $r_tb_icons .= "<i class='btn btn-outline-secondary ti ti-maximize toggle-fullscreen' title='$fs_label'></i>";
            }

            if (!$mini) {
                $left_toolbar = <<<HTML
               <span class="toolbar left-toolbar">
                  <div class="change-dashboard d-flex">
                     $dropdown_dashboards
                     $l_tb_icons
                  </div>
                  $rename
               </span>
HTML;
            }

            $grid_guide = <<<HTML
            <div class="grid-guide">
               $add_controls
            </div>
HTML;
        }

        $toolbars = <<<HTML
         $left_toolbar
         <span class="toolbar">
            <i class="btn btn-outline-secondary fas fa-history auto-refresh" title="$history_label"></i>
            <i class="btn btn-outline-secondary fas fa-moon night-mode" title="$night_label"></i>
            $r_tb_icons
         </span>
HTML;

        $filters = "";
        if (!$mini) {
            $filters = <<<HTML
         <div class='filters_toolbar'>
            <span class='filters'></span>
            <span class='filters-control'>
               <i class="btn btn-sm btn-icon btn-ghost-secondary fas fa-plus plus-sign add-filter">
                  <span class='add-filter-lbl'>{$add_filter_lbl}</span>
               </i>
            </span>
         </div>
HTML;
        }

       // display the grid
        $html = <<<HTML
      <div class="dashboard {$embed_class} {$mini_class}" id="dashboard-{$rand}">
         <span class='glpi_logo'></span>
         $toolbars
         $filters
         $grid_guide
         <div class="grid-stack grid-stack-{$this->grid_cols}"
            id="grid-stack-$rand"
            gs-column="{$this->grid_cols}"
            gs-min-row="{$this->grid_rows}"
            style="width: 100%">
            $gridstack_items
         </div>
      </div>
HTML;

        if ($mini) {
            $html = "<div class='card mb-4 d-none d-md-block dashboard-card'>
            <div class='card-body p-2'>
               $html
            </div>
         </div>";
        }

        $ajax_cards = GLPI_AJAX_DASHBOARD;
        $cache_key  = sha1($_SESSION['glpiactiveentities_string'] ?? "");

        $js_params = json_encode([
            'current'       => $this->current,
            'cols'          => $this->grid_cols,
            'rows'          => $this->grid_rows,
            'cell_margin'   => $this->cell_margin,
            'rand'          => $rand,
            'ajax_cards'    => $ajax_cards,
            'all_cards'     => $cards,
            'all_widgets'   => $all_widgets,
            'context'       => $this->context,
            'cache_key'     => $cache_key,
            'embed'         => self::$embed,
            'token'         => $token,
            'entities_id'   => $_SESSION['glpiactive_entity'],
            'is_recursive'  => $_SESSION['glpiactive_entity_recursive'] ? 1 : 0
        ]);
        $js = <<<JAVASCRIPT
      $(function () {
         new GLPIDashboard({$js_params})
      });
JAVASCRIPT;
        $js = Html::scriptBlock($js);

        echo $html . $js;
    }


    public function showDefault()
    {
        echo "<div class='card p-3'>";
        $this->show();
        echo "</div>";
    }


    /**
     * Show an embeded dashboard.
     * We must check token validity to avoid displaying dashboard to invalid users
     *
     * @param array $params contains theses keys:
     * - dashboard: the dashboard system name
     * - entities_id: entity to init in session
     * - is_recursive: do we need to display sub entities
     * - token: the token to check
     *
     * @return void (display)
     */
    public function embed(array $params = [])
    {
        $defaults = [
            'dashboard'    => '',
            'entities_id'  => 0,
            'is_recursive' => 0,
            'token'        => ''
        ];
        $params = array_merge($defaults, $params);

        if (!self::checkToken($params)) {
            Html::displayRightError();
            exit;
        }

        self::$embed = true;

       // load minimal session
        $_SESSION["glpiactive_entity"]           = $params['entities_id'];
        $_SESSION["glpiactive_entity_recursive"] = $params['is_recursive'];
        $_SESSION["glpiname"]                    = 'embed_dashboard';
        $_SESSION["glpigroups"]                  = [];
        if ($params['is_recursive']) {
            $entities = getSonsOf("glpi_entities", $params['entities_id']);
        } else {
            $entities = [$params['entities_id']];
        }
        $_SESSION['glpiactiveentities']        = $entities;
        $_SESSION['glpiactiveentities_string'] = "'" . implode("', '", $entities) . "'";

       // show embeded dashboard
        $this->show(true, $params['token']);
    }

    public static function getToken(string $dasboard = "", int $entities_id = 0, int $is_recursive = 0): string
    {
        $seed         = $dasboard . $entities_id . $is_recursive . Telemetry::getInstanceUuid();
        $uuid         = Uuid::uuid5(Uuid::NAMESPACE_OID, $seed);
        $token        = $uuid->toString();

        return $token;
    }

    /**
     * Check token variables (compare it to `dashboard`, `entities_id` and `is_recursive` paramater)
     *
     * @param array $params contains theses keys:
     * - dashboard: the dashboard system name
     * - entities_id: entity to init in session
     * - is_recursive: do we need to display sub entities
     * - token: the token to check
     *
     * @return bool
     */
    public static function checkToken(array $params = []): bool
    {
        $defaults = [
            'dashboard'    => '',
            'entities_id'  => 0,
            'is_recursive' => 0,
            'token'        => ''
        ];
        $params = array_merge($defaults, $params);

        $token = self::getToken(
            $params['dashboard'],
            $params['entities_id'],
            $params['is_recursive']
        );

        if ($token !== $params['token']) {
            return false;
        }

        return true;
    }


    /**
     * Return the html for all items for the current dashboard
     *
     * @param bool $with_lock if true, return also a locked bottom item (to fix grid height)
     *
     * @return string html of the grid items
     */
    public function getGridItemsHtml(bool $with_lock = true, bool $embed = false): string
    {
        if ($embed) {
            self::$embed = true;
        }

        $this->getCards();

        if ($with_lock) {
            $this->items[] = <<<HTML
         <div class="grid-stack-item lock-bottom"
            gs-no-resize="true"
            gs-no-move="true"
            gs-h="1"
            gs-w="{$this->grid_cols}"
            gs-x="0"
            gs-y="{$this->grid_rows}"></div>
HTML;
        }

       // append all elements to insert them in html
        return implode("", $this->items);
    }


    /**
     * Add a new grid item
     *
     * @param string $html content of the card
     * @param string $gridstack_id unique id identifying the card (used in gridstack)
     * @param int $x position in the grid
     * @param int $y position in the grid
     * @param int $width size in the grid
     * @param int $height size in the grid
     * @param array $data_option aditional options passed to the widget, contains at least thses keys:
     *                             - string 'color'
     * @return void
     */
    public function addGridItem(
        string $html = "",
        string $gridstack_id = "",
        int $x = -1,
        int $y = -1,
        int $width = 2,
        int $height = 2,
        array $data_option = []
    ) {

       // let grid-stack to autoposition item
        $autoposition = 'gs-auto-position="true"';
        $coordinates  = '';
        if ((int) $x >= 0 && (int) $y >= 0) {
            $autoposition = "";
            $coordinates  = "gs-x='$x' gs-y='$y'";
        }

        $color    = $data_option['color'] ?? "#FFFFFF";
        $fg_color = Toolbox::getFgColor($color, 100, true);

       // add card options in data attribute
        $data_option_attr = "";
        if (count($data_option)) {
            $data_option_attr = "data-card-options='" . json_encode($data_option, JSON_HEX_APOS) . "'";
        }

        $refresh_label = __("Refresh this card");
        $edit_label    = __("Edit this card");
        $delete_label  = __("Delete this card");

        $gridstack_id = htmlspecialchars($gridstack_id);

        $this->items[] = <<<HTML
         <div class="grid-stack-item"
               gs-id="{$gridstack_id}"
               gs-w="{$width}"
               gs-h="{$height}"
               {$coordinates}
               {$autoposition}
               {$data_option_attr}
               style="color: {$fg_color}">
            <span class="controls">
               <i class="refresh-item ti ti-refresh" title="{$refresh_label}"></i>
               <i class="edit-item ti ti-edit" title="{$edit_label}"></i>
               <i class="delete-item ti ti-x" title="{$delete_label}"></i>
            </span>
            <div class="grid-stack-item-content">{$html}</div>
         </div>
HTML;
    }


    /**
     * Display a mini form fo adding a new dashboard
     *
     * @return void (display)
     */
    public function displayAddDashboardForm()
    {
        $rand = mt_rand();

        echo "<form class='no-shadow display-add-dashboard-form'>";

        echo "<div class='mb-3'>";
        echo "<label for='title_$rand'>" . __("Title") . "</label>";
        echo "<div>";
        echo Html::input('title', ['id' => "title_$rand"]);
        echo "</div>";
        echo "</div>"; // .field

        echo Html::submit(_x('button', "Add"), [
            'icon'  => 'fas fa-plus',
            'class' => 'btn btn-primary submit-new-dashboard'
        ]);

        echo "</form>"; // .card.display-widget-form
    }


    /**
     * Display mini configuration form to add or edit a widget
     *
     * @param array $params with these keys:
     * - int    'gridstack_id': unique identifier of the card
     * - int    'x': position in the grid
     * - int    'y: position in the grid
     * - int    'width': size in the grid
     * - int    'height': size in the grid
     * - string 'rand': unique identifier for the dom
     * - string 'action': [display_add_widget|display_edit_widget] current action for the form
     * - array  'card_options': aditionnal options for the card, contains at least:
     *     - string 'card_id': identifier return by @see self::getAllDasboardCards
     *     - string 'color'
     *
     * @return void
     */
    public function displayWidgetForm(array $params = [])
    {
        $gridstack_id = $params['gridstack_id'] ?? "";
        $old_id       = $gridstack_id;
        $x            = (int) ($params['x'] ?? 0);
        $y            = (int) ($params['y'] ?? 0);
        $width        = (int) ($params['width'] ?? 0);
        $height       = (int) ($params['height'] ?? 0);
        $cardopt      = $params['card_options'] ?? ['color' => "#FAFAFA"];
        $card_id      = $cardopt['card_id'] ?? "";
        $widgettypes  = Widget::getAllTypes();
        $widgettype   = $cardopt['widgettype'] ?? "";
        $widget_def   = $widgettypes[$widgettype] ?? [];
        $use_gradient = $cardopt['use_gradient'] ?? 0;
        $point_labels = $cardopt['point_labels'] ?? 0;
        $limit        = $cardopt['limit'] ?? 7;
        $color        = $cardopt['color'];
        $edit         = $params['action'] === "display_edit_widget";
        $cards        = $this->getAllDasboardCards();
        $card         = $cards[$card_id] ?? [];
       // append card id to options
        if (!isset($cardopt['card_id'])) {
            $cardopt['card_id'] = $card_id;
        }

        $list_cards = [];
        array_walk($cards, function ($data, $index) use (&$list_cards) {
            $group = $data['group'] ?? __("others");
            $list_cards[$group][$index] = $data['label'] ?? $data['itemtype']::getTypeName();
        });

       // manage autoescaping
        if (isset($cardopt['markdown_content'])) {
            $cardopt['markdown_content'] = Html::cleanPostForTextArea($cardopt['markdown_content']);
        }

        TemplateRenderer::getInstance()->display('components/dashboard/widget_form.html.twig', [
            'gridstack_id' => $gridstack_id,
            'old_id'       => $old_id,
            'x'            => $x,
            'y'            => $y,
            'width'        => $width,
            'height'       => $height,
            'edit'         => $edit,
            'card'         => $card,
            'widget_def'   => $widget_def,
            'color'        => $color,
            'card_id'      => $card_id,
            'use_gradient' => $use_gradient,
            'point_labels' => $point_labels,
            'limit'        => $limit,
            'list_cards'   => $list_cards,
            'widget_types' => Widget::getAllTypes(),
            'widgettype'   => $widgettype,
            'card_options' => $cardopt,
        ]);
    }


    /**
     * Display mini form to add filter to the current dashboard
     *
     * @param array $params default values for
     * - 'used' already used filters
     *
     * @return void
     */
    public function displayFilterForm(array $params = [])
    {
        $default_params = [
            'used'  => [],
        ];
        $params = array_merge($default_params, $params);

        $used         = array_flip($params['used']);
        $filters      = Filter::getFilterChoices();
        $list_filters = array_diff_key($filters, $used);

        $rand = mt_rand();
        echo "<form class='display-filter-form'>";

        echo "<div class='field'>";
        echo "<label for='dropdown_card_id$rand'>" . __("Filters") . "</label>";
        echo "<div>";
        Dropdown::showFromArray('filter_id', $list_filters, [
            'display_emptychoice' => true,
            'rand'                => $rand,
        ]);
        echo "</div>";
        echo "</div>"; // .field

        echo Html::submit("<i class='fas fa-plus'></i>&nbsp;" . _x('button', "Add"), [
            'class' => 'btn btn-primary mt-2'
        ]);
        echo "</form>"; // form.card.display-filter-form
    }


    /**
     * Display a mini form for embedding current dashboard in another application.
     * Also, display a select for sharing current dashboard to another users/groups/entities/profiles
     *
     * @return void
     */
    public function displayEmbedForm()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $entities_id  = $_SESSION['glpiactive_entity'];
        $is_recursive = $_SESSION['glpiactive_entity_recursive'];
        $token        = self::getToken($this->current, $entities_id, $is_recursive);

        $embed_url    = $CFG_GLPI['url_base'] .
         "/front/central.php?embed&dashboard=" . $this->current .
         "&entities_id=$entities_id" .
         "&is_recursive=$is_recursive" .
         "&token=$token";

        echo "<label>" . __("Embed in another application") . "</label><br>";
        echo "<fieldset class='embed_block'>";
        echo __("Direct link");
        echo "<div class='copy_to_clipboard_wrapper'>";
        echo Html::input('direct_link', [
            'value' => $embed_url,
        ]);
        echo "</div><br>";

        $iframe = "<iframe src='$embed_url' frameborder='0' width='800' height='600' allowtransparency></iframe>";
        echo __("Iframe");
        echo "<div class='copy_to_clipboard_wrapper'>";
        echo Html::input('iframe_code', [
            'value' => $iframe,
        ]);
        echo "</div>";
        echo "</fieldset><br>";

        $this->displayEditRightsForm();
    }


    /**
     * Display a mini form for sharing current dashboard to another users/groups/entities/profiles.
     *
     * @return void
     */
    public function displayEditRightsForm()
    {
        self::loadAllDashboards();
        $rand   = mt_rand();
        $values = [];

        echo "<form class='no-shadow display-rights-form'>";

        echo "<label for='dropdown_rights_id$rand'>" .
           __("Or share the dashboard to these target objects:") .
           "</label><br>";

        $values = [
            'profiles_id' => self::$all_dashboards[$this->current]['rights']['profiles_id'] ?? [],
            'entities_id' => self::$all_dashboards[$this->current]['rights']['entities_id'] ?? [],
            'users_id'    => self::$all_dashboards[$this->current]['rights']['users_id'] ?? [],
            'groups_id'   => self::$all_dashboards[$this->current]['rights']['groups_id'] ?? [],
        ];

        echo ShareDashboardDropdown::show($rand, $values);
        echo "<br>";

        echo "<div class='d-flex align-items-center my-3'>";
        echo __('Personal') . "&nbsp;";
        echo Html::showToolTip(__("A personal dashboard is not visible by other administrators unless you explicitly share the dashboard")) . "&nbsp";
        echo Dropdown::showYesNo(
            'is_private',
            (self::$all_dashboards[$this->current]['users_id'] == '0' ? '0' : '1'),
            -1,
            [
                'display' => false
            ]
        );
        echo "</div>";

        echo "<a href='#' class='btn btn-primary save_rights'>
         <i class='far fa-save'></i>
         <span>" . __("Save") . "</span>
      </a>";

        Html::closeForm(true);
    }


    /**
     * Return the html for the given card_id
     *
     * @param string $card_id identifier return by @see self::getAllDasboardCards
     * @param array $card_options contains these keys:
     * - array 'args':
     *    - string 'gridstack_id' unique identifier of the card in the grid, used to return html by cache
     *    - bool 'force' if true, cache will be bypassed
     *    - bool 'embed' is the dashboard emebeded or not
     *
     * @return string html of the card
     */
    public function getCardHtml(string $card_id = "", array $card_options = []): string
    {
        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;

        $gridstack_id = $card_options['args']['gridstack_id'] ?? $card_id;
        $dashboard    = $card_options['dashboard'] ?? "";

        $force = ($card_options['args']['force'] ?? $card_options['force'] ?? false);

       // retrieve card
        $notfound_html = "<div class='empty-card card-warning '>
         <i class='fas fa-exclamation-triangle'></i>" .
         __('empty card!') . "
      </div>";
        $render_error_html = "<div class='empty-card card-error '>
         <i class='fas fa-exclamation-triangle'></i>" .
         __('Error rendering card!') .
            "</br>" .
            $card_id .
            "</div>";

        $start = microtime(true);
        try {
            $cards = $this->getAllDasboardCards();
            if (!isset($cards[$card_id])) {
                return $notfound_html;
            }
            $card = $cards[$card_id];

            $use_cache = !$force
                && $_SESSION['glpi_use_mode'] != Session::DEBUG_MODE
                && (!isset($card['cache']) || $card['cache'] == true);
            $cache_age = 40;

            if ($use_cache) {
                // browser cache
                header_remove('Pragma');
                header('Cache-Control: public');
                header('Cache-Control: max-age=' . $cache_age);
                header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_age));
            }

            $html = "";

            // call provider to retrieve data
            if (isset($card['provider'])) {
                $provider_args = ($card['args'] ?? []) + [
                    'params' => [
                        'label' => $card['label'] ?? ""
                    ]
                ];
                if (isset($card_options['args']['apply_filters'])) {
                    $provider_args['params']['apply_filters'] = $card_options['args']['apply_filters'];
                }
                $widget_args = call_user_func_array($card['provider'], array_values($provider_args));
            }
            $widget_args = array_merge($widget_args ?? [], $card_options['args'] ?? []);

            // call widget function to construct html
            $all_widgets = Widget::getAllTypes();
            $widgettype = $card_options['args']['widgettype'] ?? "";
            $widgetfct = $all_widgets[$widgettype]['function'] ?? "";
            if (strlen($widgetfct)) {
                // clean urls in embed mode
                if (isset($card_options['embed']) && $card_options['embed']) {
                    unset($widget_args['url']);

                    if (isset($widget_args['data'])) {
                        $unset_url = function (&$array) use (&$unset_url) {
                            unset($array['url']);
                            foreach ($array as &$value) {
                                if (is_array($value)) {
                                    $unset_url($value, 'url');
                                }
                            }
                        };
                        $unset_url($widget_args['data']);
                    }
                }

                if (isset($card['filters'])) {
                    $widget_args['filters'] = $card['filters'];
                }

                // call widget function
                $html = call_user_func($widgetfct, $widget_args);
            }

            // display a warning for empty card
            if (strlen($html) === 0) {
                return $notfound_html;
            }

            $execution_time = round(microtime(true) - $start, 3);
        } catch (\Throwable $e) {
            $html = $render_error_html;
            $execution_time = round(microtime(true) - $start, 3);
            // Log the error message without exiting
            /** @var \GLPI $GLPI */
            global $GLPI;
            $GLPI->getErrorHandler()->handleException($e, true);
        }

        if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            $html .= <<<HTML
         <span class='debug-card'>
            {$execution_time}s
         </span>
HTML;
        }

        return $html;
    }


    /**
     * Return Html for a provided set of filters
     * @param array $filter_names
     *
     * @return string the html
     */
    public function getFiltersSetHtml(array $filters = []): string
    {
        $html = "";

        foreach ($filters as $filter_id => $filter_values) {
            $html .= $this->getFilterHtml($filter_id, $filter_values);
        }

        return $html;
    }


    /**
     * Return Html for a provided filter name
     *
     * @param string $filter_id the system name of a filter (ex dates)
     * @param string|array $filter_values init the input with these values,
     *                     will be a string if empty values
     *
     * @return string the html
     */
    public function getFilterHtml(string $filter_id = "", $filter_values = ""): string
    {
        foreach (Filter::getRegisteredFilterClasses() as $filter) {
            if ($filter::getId() == $filter_id) {
                return $filter::getHtml($filter_values);
            }
        }

        return "";
    }


    /**
     * Return all itemtypes possible for constructing cards.
     * User in @see self::getAllDasboardCards
     *
     * @return array [itemtype1, itemtype2]
     */
    protected function getMenuItemtypes(): array
    {
        $menu_itemtypes = [];
        $exclude   = [
            'Config',
        ];

        $menu = Html::getMenuInfos();
        array_walk($menu, static function ($firstlvl) use (&$menu_itemtypes) {
            $key = $firstlvl['title'];
            if (isset($firstlvl['types'])) {
                  $menu_itemtypes[$key] = array_merge($menu_itemtypes[$key] ?? [], $firstlvl['types']);
            }
        });

        foreach ($menu_itemtypes as &$firstlvl) {
            $firstlvl = array_filter($firstlvl, static function ($itemtype) use ($exclude) {
                if (
                    in_array($itemtype, $exclude)
                    || !is_subclass_of($itemtype, 'CommonDBTM')
                ) {
                      return false;
                }

                $testClass = new \ReflectionClass($itemtype);
                return !$testClass->isAbstract();
            });
        }

        return $menu_itemtypes;
    }

    /**
     * Get cache key for the "getAllDasboardCards" function data.
     * The data will contain some translated strings and thus must be kept in a
     * separate cache entry for each languages
     *
     * @return string
     */
    public static function getAllDashboardCardsCacheKey(?string $language = null): string
    {
        if ($language === null) {
            $language = Session::getLanguage() ?? '';
        }

        return sprintf(
            'getAllDashboardCards_%s_%s',
            sha1(json_encode(Filter::getRegisteredFilterClasses())),
            $language
        );
    }

    /**
     * Construct catalog of all possible cards addable in a dashboard.
     *
     * @param bool $force Force rebuild the catalog of cards
     *
     * @return array
     */
    public function getAllDasboardCards($force = false): array
    {
        /**
         * @var array $CFG_GLPI
         * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
         */
        global $CFG_GLPI, $GLPI_CACHE;

        $cards = $GLPI_CACHE->get(self::getAllDashboardCardsCacheKey());

        if ($cards === null || $force) {
            $cards = [];
            $menu_itemtypes = $this->getMenuItemtypes();

            foreach ($menu_itemtypes as $firstlvl => $itemtypes) {
                foreach ($itemtypes as $itemtype) {
                    $clean_itemtype = str_replace('\\', '_', $itemtype);
                    $cards["bn_count_$clean_itemtype"] = [
                        'widgettype' => ["bigNumber"],
                        'group'      => $firstlvl,
                        'itemtype'   => "\\$itemtype",
                        'label'      => sprintf(__("Number of %s"), $itemtype::getTypeName()),
                        'provider'   => "Glpi\\Dashboard\\Provider::bigNumber$itemtype",
                        'filters'    => Filter::getAppliableFilters($itemtype::getTable()),
                    ];
                }
            }

            foreach ($CFG_GLPI['itemdevices'] as $itemtype) {
                $fk_itemtype = $itemtype::getDeviceType();
                $label = sprintf(
                    __("Number of %s by type"),
                    $itemtype::getTypeName(Session::getPluralNumber()),
                    $fk_itemtype::getFieldLabel()
                );

                $cards["count_" . $itemtype . "_" . $fk_itemtype] = [
                    'widgettype' => ['summaryNumbers', 'multipleNumber', 'pie', 'donut', 'halfpie', 'halfdonut', 'bar', 'hbar'],
                    'itemtype'   => "\\$itemtype",
                    'group'      =>  _n('Device', 'Devices', 1),
                    'label'      => $label,
                    'provider'   => "Glpi\\Dashboard\\Provider::multipleNumber" . $itemtype . "By" . $fk_itemtype,
                    'filters'    => Filter::getAppliableFilters($itemtype::getTable()),
                ];

                $clean_itemtype = str_replace('\\', '_', $itemtype);
                $cards["bn_count_$clean_itemtype"] = [
                    'widgettype' => ["bigNumber"],
                    'group'      => _n('Device', 'Devices', 1),
                    'itemtype'   => "\\$itemtype",
                    'label'      => sprintf(__("Number of %s"), $itemtype::getTypeName()),
                    'provider'   => "Glpi\\Dashboard\\Provider::bigNumber$itemtype",
                    'filters'    => Filter::getAppliableFilters($itemtype::getTable()),
                ];
            }

            foreach ($CFG_GLPI['device_types'] as $itemtype) {
                $clean_itemtype = str_replace('\\', '_', $itemtype);
                $cards["bn_count_$clean_itemtype"] = [
                    'widgettype' => ["bigNumber"],
                    'group'      => _n('Device', 'Devices', 1),
                    'itemtype'   => "\\$itemtype",
                    'label'      => sprintf(__("Number of type of %s"), $itemtype::getTypeName()),
                    'provider'   => "Glpi\\Dashboard\\Provider::bigNumber$itemtype",
                    'filters'    => Filter::getAppliableFilters($itemtype::getTable()),
                ];
            }

            // add multiple width for Assets itemtypes grouped by their foreign keys
            $assets = array_merge($CFG_GLPI['asset_types'], ['Software']);
            foreach ($assets as $itemtype) {
                $fk_itemtypes = [
                    'State',
                    'Entity',
                    'Manufacturer',
                    'Location',
                ];

                if (class_exists($itemtype . 'Type')) {
                    $fk_itemtypes[] = $itemtype . 'Type';
                }
                if (class_exists($itemtype . 'Model')) {
                    $fk_itemtypes[] = $itemtype . 'Model';
                }

                foreach ($fk_itemtypes as $fk_itemtype) {
                    $label = sprintf(
                        __("%s by %s"),
                        $itemtype::getTypeName(Session::getPluralNumber()),
                        $fk_itemtype::getFieldLabel()
                    );

                    $cards["count_" . $itemtype . "_" . $fk_itemtype] = [
                        'widgettype' => ['summaryNumbers', 'multipleNumber', 'pie', 'donut', 'halfpie', 'halfdonut', 'bar', 'hbar'],
                        'itemtype'   => "\\Computer",
                        'group'      => _n('Asset', 'Assets', Session::getPluralNumber()),
                        'label'      => $label,
                        'provider'   => "Glpi\\Dashboard\\Provider::multipleNumber" . $itemtype . "By" . $fk_itemtype,
                        'filters'    => Filter::getAppliableFilters($itemtype::getTable()),
                    ];
                }
            }

            $tickets_cases = [
                'late'               => __("Late tickets"),
                'waiting_validation' => __("Tickets waiting for your approval"),
                'notold'             => __('Not solved tickets'),
                'incoming'           => __("New tickets"),
                'waiting'            => __('Pending tickets'),
                'assigned'           => __('Assigned tickets'),
                'planned'            => __('Planned tickets'),
                'solved'             => __('Solved tickets'),
                'closed'             => __('Closed tickets'),
            ];
            foreach ($tickets_cases as $case => $label) {
                $cards["bn_count_tickets_$case"] = [
                    'widgettype' => ["bigNumber"],
                    'itemtype'   => "\\Ticket",
                    'group'      => __('Assistance'),
                    'label'      => sprintf(__("Number of %s"), $label),
                    'provider'   => "Glpi\\Dashboard\\Provider::nbTicketsGeneric",
                    'args'       => [
                        'case'   => $case,
                        'params' => [
                            'validation_check_user' => true,
                        ]
                    ],
                    'cache'      => false,
                    'filters'    => Filter::getAppliableFilters($itemtype::getTable()),
                ];

                $cards["table_count_tickets_$case"] = [
                    'widgettype' => ["searchShowList"],
                    'itemtype'   => "\\Ticket",
                    'group'      => __('Assistance'),
                    'label'      => sprintf(__("List of %s"), $label),
                    'provider'   => "Glpi\\Dashboard\\Provider::nbTicketsGeneric",
                    'args'       => [
                        'case'   => $case,
                        'params' => [
                            'validation_check_user' => true,
                        ]
                    ],
                    'filters'    => Filter::getAppliableFilters($itemtype::getTable()),
                ];
            }

            // add specific ticket's cases
            $cards["nb_opened_ticket"] = [
                'widgettype' => ['line', 'area', 'bar'],
                'itemtype'   => "\\Ticket",
                'group'      => __('Assistance'),
                'label'      => __("Number of tickets by month"),
                'provider'   => "Glpi\\Dashboard\\Provider::ticketsOpened",
                'filters'    => Filter::getAppliableFilters(Ticket::getTable()),
            ];

            $cards["ticket_evolution"] = [
                'widgettype' => ['lines', 'areas', 'bars', 'stackedbars'],
                'itemtype'   => "\\Ticket",
                'group'      => __('Assistance'),
                'label'      => __("Evolution of ticket in the past year"),
                'provider'   => "Glpi\\Dashboard\\Provider::getTicketsEvolution",
                'filters'    => Filter::getAppliableFilters(Ticket::getTable()),
            ];

            $cards["ticket_status"] = [
                'widgettype' => ['lines', 'areas', 'bars', 'stackedbars'],
                'itemtype'   => "\\Ticket",
                'group'      => __('Assistance'),
                'label'      => __("Tickets status by month"),
                'provider'   => "Glpi\\Dashboard\\Provider::getTicketsStatus",
                'filters'    => Filter::getAppliableFilters(Ticket::getTable()),
            ];

            $cards["ticket_times"] = [
                'widgettype' => ['lines', 'areas', 'bars', 'stackedbars'],
                'itemtype'   => "\\Ticket",
                'group'      => __('Assistance'),
                'label'      => __("Tickets times (in hours)"),
                'provider'   => "Glpi\\Dashboard\\Provider::averageTicketTimes",
                'filters'    => Filter::getAppliableFilters(Ticket::getTable()),
            ];

            $cards["tickets_summary"] = [
                'widgettype' => ['summaryNumbers', 'multipleNumber', 'bar', 'hbar'],
                'itemtype'   => "\\Ticket",
                'group'      => __('Assistance'),
                'label'      => __("Tickets summary"),
                'provider'   => "Glpi\\Dashboard\\Provider::getTicketSummary",
                'filters'    => Filter::getAppliableFilters(Ticket::getTable()),
            ];

            $case = '';
            $cards["bn_count_tickets_expired_by_tech"] = [
                'widgettype' => ['hBars', 'stackedHBars'],
                'itemtype'   => "\\Ticket",
                'group'      => __('Assistance'),
                'label'      => sprintf(__("Number of tickets by SLA status and technician")),
                'provider'   => "Glpi\\Dashboard\\Provider::nbTicketsByAgreementStatusAndTechnician",
                'filters'    => Filter::getAppliableFilters(Ticket::getTable()),
            ];

            $cards["bn_count_tickets_expired_by_tech_group"] = [
                'widgettype' => ['hBars', 'stackedHBars'],
                'itemtype'   => "\\Ticket",
                'group'      => __('Assistance'),
                'label'      => sprintf(__("Number of tickets by SLA status and technician group")),
                'provider'   => "Glpi\\Dashboard\\Provider::nbTicketsByAgreementStatusAndTechnicianGroup",
                'filters'    => Filter::getAppliableFilters(Ticket::getTable()),
            ];

            foreach (
                [
                    'ITILCategory' => __("Top ticket's categories"),
                    'Entity'       => __("Top ticket's entities"),
                    'RequestType'  => __("Top ticket's request types"),
                    'Location'     => __("Top ticket's locations"),
                ] as $itemtype => $label
            ) {
                $cards["top_ticket_$itemtype"] = [
                    'widgettype' => ['summaryNumbers', 'pie', 'donut', 'halfpie', 'halfdonut', 'multipleNumber', 'bar', 'hbar'],
                    'itemtype'   => "\\Ticket",
                    'group'      => __('Assistance'),
                    'label'      => $label,
                    'provider'   => "Glpi\\Dashboard\\Provider::multipleNumberTicketBy$itemtype",
                    'filters'    => Filter::getAppliableFilters($itemtype::getTable()),
                ];
            }

            foreach (
                [
                    'user_requester'  => __("Top ticket's requesters"),
                    'group_requester' => __("Top ticket's requester groups"),
                    'user_observer'   => __("Top ticket's observers"),
                    'group_observer'  => __("Top ticket's observer groups"),
                    'user_assign'     => __("Top ticket's assignees"),
                    'group_assign'    => __("Top ticket's assignee groups"),
                ] as $type => $label
            ) {
                $cards["top_ticket_$type"] = [
                    'widgettype' => ['pie', 'donut', 'halfpie', 'halfdonut', 'summaryNumbers', 'multipleNumber', 'bar', 'hbar'],
                    'itemtype'   => "\\Ticket",
                    'group'      => __('Assistance'),
                    'label'      => $label,
                    'provider'   => "Glpi\\Dashboard\\Provider::nbTicketsActor",
                    'args'       => [
                        'case' => $type,
                    ],
                    'filters'    => Filter::getAppliableFilters(Ticket::getTable()),
                ];
            }

            $cards["RemindersList"] = [
                'widgettype' => ["articleList"],
                'label'      => __("List of reminders"),
                'group'      => __('Tools'),
                'provider'   => "Glpi\\Dashboard\\Provider::getArticleListReminder",
                'filters'    => Filter::getAppliableFilters(Reminder::getTable()),
            ];

            $cards["markdown_editable"] = [
                'widgettype'   => ["markdown"],
                'label'        => __("Editable markdown card"),
                'group'        => __('Others'),
                'card_options' => [
                    'content' => __("Toggle edit mode to edit content"),
                ]
            ];
            $GLPI_CACHE->set(self::getAllDashboardCardsCacheKey(), $cards);
        }

        $more_cards = Plugin::doHookFunction(Hooks::DASHBOARD_CARDS);
        if (is_array($more_cards)) {
            $cards = array_merge($cards, $more_cards);
        }

        return $cards;
    }


    public function getRights($interface = 'central')
    {
        return [
            READ   => __('Read'),
            UPDATE => __('Update'),
            CREATE => __('Create'),
            PURGE  => [
                'short' => __('Purge'),
                'long'  => _x('button', 'Delete permanently')
            ]
        ];
    }


    /**
     * Save last dashboard viewed
     *
     * @param string $page current page
     * @param string $dashboard current dashboard
     *
     * @return void
     */
    public function setLastDashboard(string $page = "", string $dashboard = "")
    {
        $_SESSION['last_dashboards'][$page] = $dashboard;
    }


    /**
     * Restore last viewed dashboard
     *
     * @return string the dashboard key
     */
    public function restoreLastDashboard(): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
        $new_key = "";
        $target = Toolbox::cleanTarget($_REQUEST['_target'] ?? $_SERVER['REQUEST_URI'] ?? "");
        if (isset($_SESSION['last_dashboards']) && strlen($target) > 0) {
            $target = preg_replace('/^' . preg_quote($CFG_GLPI['root_doc'], '/') . '/', '', $target);
            if (!isset($_SESSION['last_dashboards'][$target])) {
                return "";
            }

            $new_key   = $_SESSION['last_dashboards'][$target];
            $dashboard = new Dashboard($new_key);
            if (!$dashboard->canViewCurrent()) {
                return "";
            }

            $this->current = $new_key;
        }

        return $new_key;
    }


    /**
     * Retrieve the default dashboard for a specific menu entry
     * First try from session
     * then on config
     * And Fallback on the first dashboard found
     *
     * @param string $menu
     * @param bool $strict if true, do not provide a fallback
     *
     * @return string the dashboard key
     */
    public static function getDefaultDashboardForMenu(string $menu = "", bool $strict = false): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $grid = new self();

        if (!$strict) {
            $restored = $grid->restoreLastDashboard();
            if (strlen($restored) > 0) {
                return $restored;
            }
        }

        // Try loading default from user preferences
        $config_key = 'default_dashboard_' . $menu;
        $default    = $_SESSION["glpi$config_key"] ?? "";
        if (strlen($default)) {
            // If default is "disabled", return empty string and skip default value from config
            if ($default == 'disabled') {
                return "";
            }

            $dasboard = new Dashboard($default);

            if ($dasboard->load() && $dasboard->canViewCurrent()) {
                return $default;
            }
        }

        // Try loading default from config
        $default = $CFG_GLPI[$config_key] ?? "";
        if (strlen($default)) {
            $dasboard = new Dashboard($default);

            if ($dasboard->load() && $dasboard->canViewCurrent()) {
                return $default;
            }
        }

        // if default not found, return first dashboard
        if (!$strict) {
            self::loadAllDashboards();
            $first_dashboard = array_shift(self::$all_dashboards);
            if (isset($first_dashboard['key'])) {
                return $first_dashboard['key'];
            }
        }

        return "";
    }


    public static function dropdownDashboard(string $name = "", array $params = [], bool $disabled_option = false): string
    {
        $to_show = Dashboard::getAll(false, true, $params['context'] ?? 'core');
        $can_view_all = $params['can_view_all'] ?? false;

        $options_dashboards = [];
        foreach ($to_show as $key => $dashboard) {
            if (self::canViewSpecificicDashboard($key, $can_view_all)) {
                $options_dashboards[$key] = $dashboard['name'] ?? $key;
            }
        }

        if ($disabled_option) {
            $options_dashboards = ['disabled' => __('Disabled')] + $options_dashboards;
        }

        return Dropdown::showFromArray($name, $options_dashboards, $params);
    }
}
