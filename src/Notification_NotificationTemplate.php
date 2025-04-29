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
            switch (get_class($item)) {
                case Notification::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            $this->getTable(),
                            ['notifications_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
                case NotificationTemplate::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            $this->getTable(),
                            ['notificationtemplates_id' => $item->getID()]
                        );
                    }
                    return self::createTabEntry(Notification::getTypeName(Session::getPluralNumber()), $nb);
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch (get_class($item)) {
            case Notification::class:
                self::showForNotification($item, $withtemplate);
                break;
            case NotificationTemplate::class:
                self::showForNotificationTemplate($item, $withtemplate);
                break;
        }

        return true;
    }


    /**
     * Print the notification templates
     *
     * @param Notification $notif        Notification object
     * @param integer      $withtemplate Template or basic item (default '')
     *
     * @return void
     **/
    public static function showForNotification(Notification $notif, $withtemplate = 0)
    {
        /** @var \DBmysql $DB */
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
            && !(!empty($withtemplate) && ($withtemplate == 2))
        ) {
            echo "<div class='center firstbloc'>" .
               "<a class='btn btn-primary' href='" . self::getFormURL() . "?notifications_id=$ID&amp;withtemplate=" .
                  $withtemplate . "'>";
            echo __('Add a template');
            echo "</a></div>\n";
        }

        echo "<div class='center'>";

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => ['notifications_id' => $ID],
        ]);

        echo "<table class='tab_cadre_fixehov'>";
        $colspan = 2;

        if ($iterator->numrows()) {
            $header = "<tr>";
            $header .= "<th>" . __('ID') . "</th>";
            $header .= "<th>" . static::getTypeName(1) . "</th>";
            $header .= "<th>" . __('Mode') . "</th>";
            $header .= "</tr>";
            echo $header;

            Session::initNavigateListItems(
                __CLASS__,
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    Notification::getTypeName(1),
                    $notif->getName()
                )
            );

            $notiftpl = new self();
            foreach ($iterator as $data) {
                $notiftpl->getFromDB($data['id']);
                $tpl = new NotificationTemplate();
                $tpl->getFromDB($data['notificationtemplates_id']);

                $tpl_link = $tpl->getLink();
                if (empty($tpl_link)) {
                    $tpl_link = "<i class='fa fa-exclamation-triangle red'></i>&nbsp;
                            <a href='" . $notiftpl->getLinkUrl() . "'>" .
                             __("No template selected") .
                          "</a>";
                }

                echo "<tr class='tab_bg_2'>";
                echo "<td>" . $notiftpl->getLink() . "</td>";
                echo "<td>$tpl_link</td>";
                $mode = self::getMode($data['mode']);
                if ($mode === NOT_AVAILABLE) {
                    $mode = "{$data['mode']} ($mode)";
                } else {
                    $mode = $mode['label'];
                }
                echo "<td>$mode</td>";
                echo "</tr>";
                Session::addToNavigateListItems(__CLASS__, $data['id']);
            }
            echo $header;
        } else {
            echo "<tr class='tab_bg_2'><th colspan='$colspan'>" . __('No item found') . "</th></tr>";
        }

        echo "</table>";
        echo "</div>";
    }


    /**
     * Print associated notifications
     *
     * @param NotificationTemplate $template     Notification template object
     * @param integer              $withtemplate Template or basic item (default '')
     *
     * @return void
     */
    public static function showForNotificationTemplate(NotificationTemplate $template, $withtemplate = 0)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ID = $template->getID();

        if (
            !$template->getFromDB($ID)
            || !$template->can($ID, READ)
        ) {
            return false;
        }

        echo "<div class='center'>";

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => ['notificationtemplates_id' => $ID],
        ]);

        echo "<table class='tab_cadre_fixehov'>";
        $colspan = 2;

        if ($iterator->numrows()) {
            $header = "<tr>";
            $header .= "<th>" . __('ID') . "</th>";
            $header .= "<th>" . _n('Notification', 'Notifications', 1) . "</th>";
            $header .= "<th>" . __('Mode') . "</th>";
            $header .= "</tr>";
            echo $header;

            Session::initNavigateListItems(
                __CLASS__,
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    Notification::getTypeName(1),
                    $template->getName()
                )
            );

            foreach ($iterator as $data) {
                $notification = new Notification();
                $notification->getFromDB($data['notifications_id']);

                echo "<tr class='tab_bg_2'>";
                echo "<td>" . $data['id'] . "</td>";
                echo "<td>" . $notification->getLink() . "</td>";
                $mode = self::getMode($data['mode']);
                if ($mode === NOT_AVAILABLE) {
                    $mode = "{$data['mode']} ($mode)";
                } else {
                    $mode = $mode['label'];
                }
                echo "<td>$mode</td>";
                echo "</tr>";
                Session::addToNavigateListItems(__CLASS__, $data['id']);
            }
            echo $header;
        } else {
            echo "<tr class='tab_bg_2'><th colspan='$colspan'>" . __('No item found') . "</th></tr>";
        }

        echo "</table>";
        echo "</div>";
    }


    /**
     * Form for Notification on Massive action
     **/
    public static function showFormMassiveAction()
    {

        echo __('Mode') . "<br>";
        self::dropdownMode(['name' => 'mode']);
        echo "<br><br>";

        echo NotificationTemplate::getTypeName(1) . "<br>";
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
        return $this->getID();
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

        $this->showFormHeader($options);

        if ($this->isNewID($ID)) {
            echo "<input type='hidden' name='notifications_id' value='" . $options['notifications_id'] . "'>";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Notification', 'Notifications', 1) . "</td>";
        echo "<td>" . $notif->getLink() . "</td>";
        echo "<td colspan='2'>&nbsp;</td>";
        echo "</tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Mode') . "</td>";
        echo "<td>";
        self::dropdownMode(['name' => 'mode', 'value' => $this->getField('mode')]);
        echo "</td>";

        echo "<td>" . NotificationTemplate::getTypeName(1) . "</td>";
        echo "<td><span id='show_templates'>";
        NotificationTemplate::dropdownTemplates(
            'notificationtemplates_id',
            $notif->fields['itemtype'],
            $this->fields['notificationtemplates_id']
        );
        echo "</span></td></tr>";

        $this->showFormButtons($options);

        return true;
    }


    /**
     * Get notification method label
     *
     * @param string $mode the mode to use
     *
     * @return string
     **/
    public static function getMode($mode)
    {
        $tab = self::getModes();
        if (isset($tab[$mode])) {
            return $tab[$mode];
        }
        return NOT_AVAILABLE;
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
        /** @var array $CFG_GLPI */
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
        /** @var array $CFG_GLPI */
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
            //check that core modes are part of the config
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
                return $mode;
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
     * @param string $extratype Extra type (either 'event' or 'setting')
     *
     * @return string
     */
    public static function getModeClass($mode, $extratype = '')
    {
        if ($extratype == 'event') {
            $classname = 'NotificationEvent' . ucfirst($mode);
        } elseif ($extratype == 'setting') {
            $classname = 'Notification' . ucfirst($mode) . 'Setting';
        } else {
            if ($extratype != '') {
                throw new \LogicException(sprintf('Unknown type `%s`.', $extratype));
            }
            $classname = 'Notification' . ucfirst($mode);
        }
        $conf = self::getMode($mode);
        if ($conf['from'] != 'core') {
            $classname = 'Plugin' . ucfirst($conf['from']) . $classname;
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;
        foreach (array_keys(self::getModes()) as $mode) {
            if ($CFG_GLPI['notifications_' . $mode]) {
                return true;
            }
        }
        return false;
    }
}
