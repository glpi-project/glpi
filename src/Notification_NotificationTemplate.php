<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

/**
 * Notification_NotificationTemplate Class
 *
 * @since 9.2
 **/
class Notification_NotificationTemplate extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1       = 'Notification';
    public static $items_id_1       = 'notifications_id';
    public static $itemtype_2       = 'NotificationTemplate';
    public static $items_id_2       = 'notificationtemplates_id';
    public static $mustBeAttached_2 = false; // Mandatory to display creation form

    public $no_form_page    = false;
    protected $displaylist  = false;

    public const MODE_MAIL      = 'mailing';
    public const MODE_AJAX      = 'ajax';
    public const MODE_WEBSOCKET = 'websocket';
    public const MODE_SMS       = 'sms';
    public const MODE_XMPP      = 'xmpp';
    public const MODE_IRC       = 'irc';

    public static function getTypeName($nb = 0)
    {
        return _n('Template', 'Templates', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate && Notification::canView()) {
            $nb = 0;
            switch ($item::class) {
                case Notification::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            static::getTable(),
                            ['notifications_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
                case NotificationTemplate::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            static::getTable(),
                            ['notificationtemplates_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(Notification::getTypeName(Session::getPluralNumber()), $nb, $item::class);
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch (get_class($item)) {
            case Notification::class:
                return self::showForNotification($item, $withtemplate);
            case NotificationTemplate::class:
                return self::showForNotificationTemplate($item, $withtemplate);
        }

        return false;
    }

    /**
     * Print the notification templates
     *
     * @param Notification $notif        Notification object
     * @param integer      $withtemplate Template or basic item (default '')
     *
     * @return bool
     **/
    public static function showForNotification(Notification $notif, $withtemplate = 0): bool
    {
        global $DB;

        $ID = $notif->getID();

        if (
            !$notif->getFromDB($ID)
            || !$notif->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $notif->canEdit($ID);

        if (
            $canedit
            && !(!empty($withtemplate) && ((int) $withtemplate === 2))
        ) {
            $twig_params = [
                'add_msg' => __('Add a template'),
                'id' => $ID,
                'withtemplate' => $withtemplate,
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div class="text-center mb-3">
                    <a class="btn btn-primary" role="button"
                       href="{{ 'Notification_NotificationTemplate'|itemtype_form_path }}?notifications_id={{ id }}&amp;withtemplate={{ withtemplate }}">
                        {{ add_msg }}
                    </a>
                </div>
TWIG, $twig_params);
        }

        $iterator = $DB->request([
            'SELECT' => ['id', 'notificationtemplates_id', 'mode'],
            'FROM'   => self::getTable(),
            'WHERE'  => ['notifications_id' => $ID],
        ]);

        $notiftpl = new self();
        $entries = [];
        foreach ($iterator as $data) {
            $notiftpl->getFromDB($data['id']);
            $tpl = new NotificationTemplate();
            $tpl->getFromDB($data['notificationtemplates_id']);

            $tpl_link = $tpl->getLink();
            if (empty($tpl_link)) {
                $tpl_link = "<i class='ti ti-alert-triangle red'></i>
                        <a href='" . htmlescape($notiftpl->getLinkUrl()) . "'>"
                         . __s("No template selected")
                      . "</a>";
            }
            $mode = self::getMode($data['mode']);
            if ($mode === NOT_AVAILABLE) {
                $mode = "{$data['mode']} ($mode)";
            } else {
                $mode = $mode['label'];
            }
            $entries[] = [
                'itemtype' => self::class,
                'id'       => $data['id'],
                'id_link'  => $notiftpl->getLink(),
                'link'     => $tpl_link,
                'mode'     => $mode,
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'id_link' => __('ID'),
                'link' => static::getTypeName(1),
                'mode' => __('Mode'),
            ],
            'formatters' => [
                'id_link' => 'raw_html',
                'link' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
                'specific_actions' => ['purge' => _x('button', 'Delete permanently')],
            ],
        ]);

        return true;
    }

    /**
     * Print associated notifications
     *
     * @param NotificationTemplate $template     Notification template object
     * @param integer              $withtemplate Template or basic item (default '')
     *
     * @return bool
     */
    public static function showForNotificationTemplate(NotificationTemplate $template, $withtemplate = 0): bool
    {
        global $DB;

        $ID = $template->getID();

        if (
            !$template->getFromDB($ID)
            || !$template->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $template->canEdit($ID);

        $iterator = $DB->request([
            'SELECT' => ['id', 'notifications_id', 'mode'],
            'FROM'   => self::getTable(),
            'WHERE'  => ['notificationtemplates_id' => $ID],
        ]);

        $notiftpl = new self();
        $entries = [];
        foreach ($iterator as $data) {
            $notiftpl->getFromDB($data['id']);
            $notification = new Notification();
            $notification->getFromDB($data['notifications_id']);
            $mode = self::getMode($data['mode']);
            if ($mode === NOT_AVAILABLE) {
                $mode = "{$data['mode']} ($mode)";
            } else {
                $mode = $mode['label'];
            }

            $entries[] = [
                'itemtype' => self::class,
                'id' => $data['id'],
                'id_link' => $notiftpl->getLink(),
                'link' => $notification->getLink(),
                'mode' => $mode,
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'id_link' => __('ID'),
                'link' => Notification::getTypeName(1),
                'mode' => __('Mode'),
            ],
            'formatters' => [
                'id_link' => 'raw_html',
                'link' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
                'specific_actions' => ['purge' => _x('button', 'Delete permanently')],
            ],
        ]);

        return true;
    }

    /**
     * Form for Notification on Massive action
     **/
    public static function showFormMassiveAction()
    {
        echo __s('Mode') . "<br>";
        self::dropdownMode(['name' => 'mode']);
        echo "<br><br>";

        echo htmlescape(NotificationTemplate::getTypeName(1)) . "<br>";
        NotificationTemplate::dropdown([
            'name'       => 'notificationtemplates_id',
            'value'     => 0,
            'comment'   => 1,
        ]);
        echo "<br><br>";

        echo Html::submit(_x('button', 'Add'), ['name' => 'massiveaction']);
    }

    public function getName($options = [])
    {
        return (string) $this->getID();
    }

    /**
     * Print the form
     *
     * @param integer $ID      ID of the item
     * @param array   $options array
     *     - target for the Form
     *     - computers_id ID of the computer for add process
     *
     * @return boolean true if displayed  false if item not found or not right to display
     **/
    public function showForm($ID, array $options = [])
    {
        if (!Session::haveRight("notification", UPDATE)) {
            return false;
        }

        $notif = new Notification();
        if ($ID > 0) {
            $this->check($ID, READ);
            $notif->getFromDB($this->fields['notifications_id']);
        } else {
            $this->check(-1, CREATE, $options);
            $notif->getFromDB($options['notifications_id']);
        }

        TemplateRenderer::getInstance()->display('pages/setup/notification/notification_notificationtemplate.html.twig', [
            'item'              => $this,
            'notification'      => $notif,
            'notification_link' => $notif->getLink(),
        ]);

        return true;
    }

    /**
     * Get notification method label
     *
     * @param string $mode the mode to use
     *
     * @return array|string The mode data if found, otherwise {@link NOT_AVAILABLE}.
     **/
    public static function getMode($mode)
    {
        $tab = self::getModes();
        return $tab[$mode] ?? NOT_AVAILABLE;
    }

    /**
     * Register a new notification mode (for plugins)
     *
     * @param string $mode  Mode
     * @param string $label Mode's label
     * @param string $from  Plugin which registers the mode
     *
     * @return void
     */
    public static function registerMode($mode, $label, $from)
    {
        global $CFG_GLPI;

        self::getModes();
        $CFG_GLPI['notifications_modes'][$mode] = [
            'label'  => $label,
            'from'   => $from,
        ];
    }

    /**
     * Get modes
     *
     * @return array
     **/
    public static function getModes()
    {
        global $CFG_GLPI;

        $core_modes = [
            self::MODE_MAIL      => [
                'label'  => _n('Email', 'Emails', 1),
                'from'   => 'core',
            ],
            self::MODE_AJAX      => [
                'label'  => __('Browser'),
                'from'   => 'core',
            ],
            /*self::MODE_WEBSOCKET => [
            'label'  => __('Websocket'),
            'from'   => 'core'
         ],
         self::MODE_SMS       => [
            'label'  => __('SMS'),
            'from'   => 'core'
         ]*/
        ];

        if (!isset($CFG_GLPI['notifications_modes']) || !is_array($CFG_GLPI['notifications_modes'])) {
            $CFG_GLPI['notifications_modes'] = $core_modes;
        } else {
            // check that core modes are part of the config
            foreach ($core_modes as $mode => $conf) {
                if (!isset($CFG_GLPI['notifications_modes'][$mode])) {
                    $CFG_GLPI['notifications_modes'][$mode] = $conf;
                }
            }
        }

        return $CFG_GLPI['notifications_modes'];
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'mode':
                $mode = self::getMode($values[$field]);
                if ($mode === NOT_AVAILABLE) {
                    $mode = "{$values[$field]} ($mode)";
                } else {
                    $mode = $mode['label'];
                }
                return htmlescape($mode);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;

        switch ($field) {
            case 'mode':
                $options['value']    = $values[$field];
                $options['name']     = $name;
                return self::dropdownMode($options);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * Display a dropdown with all the available notification modes
     *
     * @param array $options array of options
     *
     * @return integer|string
     *    integer if option display=true (random part of elements id)
     *    string if option display=false (HTML code)
     */
    public static function dropdownMode($options)
    {
        $p['name']     = 'modes';
        $p['display']  = true;
        $p['value']    = '';

        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }

        $modes = self::getModes();
        foreach ($modes as &$mode) {
            $mode = $mode['label'];
        }

        return Dropdown::showFromArray($p['name'], $modes, $p);
    }

    /**
     * Get class name for specified mode
     *
     * @param string $mode      Requested mode
     * @param 'event'|'setting'|'' $extratype Extra type
     *
     * @return (
     *      $extratype is 'event'
     *          ? class-string<NotificationEventInterface>
     *          : (
     *              $extratype is 'setting'
     *                  ? class-string<NotificationSetting>
     *                  : class-string<NotificationInterface>
     *          )
     *      )
     */
    public static function getModeClass($mode, $extratype = '')
    {
        if ($extratype === 'event') {
            $classname = 'NotificationEvent' . ucfirst($mode);
        } elseif ($extratype === 'setting') {
            $classname = 'Notification' . ucfirst($mode) . 'Setting';
        } else {
            if ($extratype !== '') {
                throw new LogicException(sprintf('Unknown type `%s`.', $extratype));
            }
            $classname = 'Notification' . ucfirst($mode);
        }
        $conf = self::getMode($mode);
        if ($conf['from'] !== 'core') {
            $classname = 'Plugin' . ucfirst($conf['from']) . $classname;
        }


        switch ($extratype) {
            case 'event':
                $expected_class = NotificationEventInterface::class;
                break;
            case 'setting':
                $expected_class = NotificationSetting::class;
                break;
            default:
                $expected_class = NotificationInterface::class;
                break;
        }

        if (!is_a($classname, $expected_class, true)) {
            throw new RuntimeException(
                sprintf('`%s` is not an instance of `%s`.', $classname, $expected_class)
            );
        }

        return $classname;
    }

    /**
     * Check if at least one mode is currently enabled
     *
     * @return boolean
     */
    public static function hasActiveMode()
    {
        global $CFG_GLPI;
        foreach (array_keys(self::getModes()) as $mode) {
            if ($CFG_GLPI['notifications_' . $mode]) {
                return true;
            }
        }
        return false;
    }
}
