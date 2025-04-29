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

use Glpi\RichText\RichText;

/**
 * ReminderTranslation Class
 *
 * @since 9.5
 **/
class ReminderTranslation extends CommonDBChild
{
    public static $itemtype = 'Reminder';
    public static $items_id = 'reminders_id';
    public $dohistory       = true;
    public static $logs_for_parent = false;

    public static $rightname       = 'reminder_public';



    public static function getTypeName($nb = 0)
    {
        return _n('Translation', 'Translations', $nb);
    }


    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    /**
     * @param \CommonGLPI $item
     * @param int         $withtemplate
     *
     * @return array|string
     * @see CommonGLPI::getTabNameForItem()
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (
            self::canBeTranslated($item)
            && Session::getCurrentInterface() != "helpdesk"
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::getNumberOfTranslationsForItem($item);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }

        return '';
    }


    /**
     * @param $item            CommonGLPI object
     * @param $tabnum (default 1)
     * @param $withtemplate (default 0)
     **
     *
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if (
            $item->getType() == "Reminder"
            && self::canBeTranslated($item)
        ) {
            self::showTranslations($item);
        }
        return true;
    }


    /**
     * Display all translated field for an Reminder
     *
     * @param $item a Reminder item
     *
     * @return true;
     **/
    public static function showTranslations(Reminder $item)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $canedit = $item->can($item->getID(), UPDATE);
        $rand    = mt_rand();
        if ($canedit) {
            echo "<div id='viewtranslation" . $item->getID() . "$rand'></div>\n";
            echo "<script type='text/javascript' >\n";
            echo "function addTranslation" . $item->getID() . "$rand() {\n";
            $params = ['type'             => __CLASS__,
                'parenttype'       => get_class($item),
                'reminders_id' => $item->fields['id'],
                'id'               => -1,
            ];
            Ajax::updateItemJsCode(
                "viewtranslation" . $item->getID() . "$rand",
                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                $params
            );
            echo "};";
            echo "</script>\n";

            echo "<div class='center'>" .
              "<a class='btn btn-primary' href='javascript:addTranslation" . $item->getID() . "$rand();'>" .
              __('Add a new translation') . "</a></div><br>";
        }

        $obj   = new self();
        $found = $obj->find(['reminders_id' => $item->getID()], "language ASC");

        if (count($found) > 0) {
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
                Html::showMassiveActions($massiveactionparams);
            }

            Session::initNavigateListItems('ReminderTranslation', __('Entry translations list'));

            echo "<div class='center'>";
            echo "<table class='tab_cadre_fixehov'><tr class='tab_bg_2'>";
            echo "<th colspan='4'>" . __("List of translations") . "</th></tr>";
            if ($canedit) {
                echo "<th width='10'>";
                echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                echo "</th>";
            }
            echo "<th>" . __("Language") . "</th>";
            echo "<th>" . __("Subject") . "</th>";
            foreach ($found as $data) {
                echo "<tr class='tab_bg_1'>";
                if ($canedit) {
                    echo "<td class='center'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                    echo "</td>";
                }
                echo "<td>";
                echo Dropdown::getLanguageName($data['language']);
                echo "</td><td>";
                if ($canedit) {
                    echo "<a href=\"" . ReminderTranslation::getFormURLWithID($data["id"]) . "\">{$data['name']}</a>";
                } else {
                    echo  $data["name"];
                }
                if (isset($data['text']) && !empty($data['text'])) {
                    echo "&nbsp;";
                    Html::showToolTip(RichText::getEnhancedHtml($data['text']));
                }
                echo "</td></tr>";
            }
            echo "</table>";
            if ($canedit) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
        } else {
            echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'>";
            echo "<th class='b'>" . __("No translation found") . "</th></tr></table>";
        }

        return true;
    }


    /**
     * Display translation form
     *
     * @param integer $ID
     * @param array   $options
     */
    public function showForm($ID = -1, array $options = [])
    {

        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            // Create item
            $item                = $options['parent'];
            $options['itemtype'] = get_class($item);
            $options['reminders_id'] = $item->getID();
            $this->check(-1, CREATE, $options);
        }
        $this->showFormHeader($options);
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Language') . "&nbsp;:</td>";
        echo "<td>";
        echo "<input type='hidden' name='users_id' value=\"" . Session::getLoginUserID() . "\">";
        echo "<input type='hidden' name='reminders_id' value='" . $this->fields['reminders_id'] . "'>";
        if ($ID > 0) {
            echo Dropdown::getLanguageName($this->fields['language']);
        } else {
            Dropdown::showLanguages(
                "language",
                ['display_none' => false,
                    'value'        => $_SESSION['glpilanguage'],
                    'used'         => self::getAlreadyTranslatedForItem($item),
                ]
            );
        }
        echo "</td><td colspan='2'>&nbsp;</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Name') . "</td>";
        echo "<td colspan='3'>";
        echo Html::input('name', ['value' => $this->fields['name'], 'size' => '80']);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Description') . "</td>";
        echo "<td colspan='3'>";
        Html::textarea(['name'              => 'text',
            'value'             => RichText::getSafeHtml($this->fields["text"], true),
            'enable_richtext'   => true,
            'enable_fileupload' => false,
        ]);
        echo "</td></tr>\n";

        $this->showFormButtons($options);
        return true;
    }


    /**
     * Get a translation for a value
     *
     * @param Reminder $item   item to translate
     * @param string       $field  field to return (default 'name')
     *
     * @return string  the field translated if a translation is available, or the original field if not
     **/
    public static function getTranslatedValue(Reminder $item, $field = "name")
    {
        $obj   = new self();
        $found = $obj->find([
            'reminders_id'   => $item->getID(),
            'language'           => $_SESSION['glpilanguage'],
        ]);

        if (
            (count($found) > 0)
            && in_array($field, ['name', 'text'])
        ) {
            $first = array_shift($found);
            return $first[$field];
        }
        return $item->fields[$field];
    }


    /**
     * Is reminder translation functionality active
     *
     * @return boolean
     **/
    public static function isReminderTranslationActive()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return $CFG_GLPI['translate_reminders'];
    }


    /**
     * Check if an item can be translated
     * It be translated if translation if globally on and item is an instance of CommonDropdown
     * or CommonTreeDropdown and if translation is enabled for this class
     *
     * @param CommonGLPI $item the item to check
     *
     * @return boolean true if item can be translated, false otherwise
     **/
    public static function canBeTranslated(CommonGLPI $item)
    {

        return (self::isReminderTranslationActive()
              && $item instanceof Reminder);
    }


    /**
     * Return the number of translations for an item
     *
     * @param Reminder $item
     *
     * @return integer  the number of translations for this item
     **/
    public static function getNumberOfTranslationsForItem($item)
    {

        return countElementsInTable(
            getTableForItemType(__CLASS__),
            ['reminders_id' => $item->getID()]
        );
    }


    /**
     * Get already translated languages for item
     *
     * @param CommonDBTM $item
     *
     * @return array of already translated languages
     **/
    public static function getAlreadyTranslatedForItem($item)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $tab = [];

        $iterator = $DB->request([
            'FROM'   => getTableForItemType(__CLASS__),
            'WHERE'  => ['reminders_id' => $item->getID()],
        ]);

        foreach ($iterator as $data) {
            $tab[$data['language']] = $data['language'];
        }
        return $tab;
    }
}
