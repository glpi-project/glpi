<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Event;
use Glpi\RichText\RichText;

/**
 * KnowbaseItemTranslation Class
 *
 * @since 0.85
 **/
class KnowbaseItemTranslation extends CommonDBChild
{
    public static $itemtype = 'KnowbaseItem';
    public static $items_id = 'knowbaseitems_id';
    public $dohistory       = true;
    public static $logs_for_parent = false;

    public static $rightname       = 'knowbase';



    public static function getTypeName($nb = 0)
    {
        return _n('Translation', 'Translations', $nb);
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab('Log', $ong, $options);
        $this->addStandardTab('KnowbaseItem_Revision', $ong, $options);
        $this->addStandardTab('KnowbaseItem_Comment', $ong, $options);

        return $ong;
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (!$withtemplate) {
            $nb = 0;
            switch ($item->getType()) {
                case __CLASS__:
                    $ong[1] = $this->getTypeName(1);
                    if ($item->canUpdateItem()) {
                        $ong[3] = __('Edit');
                    }
                    return $ong;
            }
        }

        if (self::canBeTranslated($item)) {
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
     * @param $tabnum          (default 1)
     * @param $withtemplate    (default 0)
     **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() == __CLASS__) {
            switch ($tabnum) {
                case 1:
                    $item->showFull();
                    break;

                case 2:
                    $item->showVisibility();
                    break;

                case 3:
                    $item->showForm($item->getID(), ['parent' => $item]);
                    break;
            }
        } else if (self::canBeTranslated($item)) {
            self::showTranslations($item);
        }
        return true;
    }

    /**
     * Print out (html) show item : question and answer
     *
     * @param $options      array of options
     *
     * @return void
     **/
    public function showFull($options = [])
    {
        global $CFG_GLPI;

        if (!$this->can($this->fields['id'], READ)) {
            return false;
        }

        $linkusers_id = true;
       // show item : question and answer
        if (
            ((Session::getLoginUserID() === false) && $CFG_GLPI["use_public_faq"])
            || (Session::getCurrentInterface() == "helpdesk")
            || !User::canView()
        ) {
            $linkusers_id = false;
        }

        echo "<table class='tab_cadre_fixe'>";

        echo "<tr><td class='left' colspan='4'><h2>" . __('Subject') . "</h2>";
        echo $this->fields["name"];

        echo "</td></tr>";
        echo "<tr><td class='left' colspan='4'><h2>" . __('Content') . "</h2>\n";

        echo "<div class='rich_text_container' id='kbanswer'>";
        echo RichText::getEnhancedHtml($this->fields['answer']);
        echo "</div>";
        echo "</td></tr>";
        echo "</table>";

        return true;
    }

    /**
     * Display all translated field for an KnowbaseItem
     *
     * @param $item a KnowbaseItem item
     *
     * @return true;
     **/
    public static function showTranslations(KnowbaseItem $item)
    {
        global $CFG_GLPI;

        $canedit = $item->can($item->getID(), UPDATE);
        $rand    = mt_rand();
        if ($canedit) {
            echo "<div id='viewtranslation" . $item->getID() . "$rand'></div>\n";
            echo "<script type='text/javascript' >\n";
            echo "function addTranslation" . $item->getID() . "$rand() {\n";
            $params = ['type'             => __CLASS__,
                'parenttype'       => get_class($item),
                'knowbaseitems_id' => $item->fields['id'],
                'id'               => -1
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
        $found = $obj->find(['knowbaseitems_id' => $item->getID()], "language ASC");

        if (count($found) > 0) {
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
                Html::showMassiveActions($massiveactionparams);
            }

            Session::initNavigateListItems('KnowbaseItemTranslation', __('Entry translations list'));

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
                    echo "<a href=\"" . KnowbaseItemTranslation::getFormURLWithID($data["id"]) . "\">{$data['name']}</a>";
                } else {
                    echo  $data["name"];
                }
                if (isset($data['answer']) && !empty($data['answer'])) {
                    echo "&nbsp;";
                    Html::showToolTip(RichText::getEnhancedHtml($data['answer']));
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
        if (!($ID > 0) && !isset($options['parent']) || !($options['parent'] instanceof CommonDBTM)) {
            // parent is mandatory in new item form
            trigger_error('Parent item must be defined in `$options["parent"]`.', E_USER_WARNING);
            return false;
        }
        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
           // Create item
            $options['itemtype']         = get_class($options['parent']);
            $options['knowbaseitems_id'] = $options['parent']->getID();
            $this->check(-1, CREATE, $options);
        }
        $this->showFormHeader($options);
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Language') . "&nbsp;:</td>";
        echo "<td>";
        echo "<input type='hidden' name='users_id' value=\"" . Session::getLoginUserID() . "\">";
        echo "<input type='hidden' name='knowbaseitems_id' value='" . $this->fields['knowbaseitems_id'] . "'>";
        if ($ID > 0) {
            echo Dropdown::getLanguageName($this->fields['language']);
        } else {
            Dropdown::showLanguages(
                "language",
                ['display_none' => false,
                    'value'        => $_SESSION['glpilanguage'],
                    'used'         => self::getAlreadyTranslatedForItem($options['parent'])
                ]
            );
        }
        echo "</td><td colspan='2'>&nbsp;</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Subject') . "</td>";
        echo "<td colspan='3'>";
        echo "<textarea class='form-control' name='name'>" . $this->fields["name"] . "</textarea>";
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Content') . "</td>";
        echo "<td colspan='3'>";
        Html::textarea(
            [
                'name'              => 'answer',
                'value'             => RichText::getSafeHtml($this->fields['answer'], true),
                'editor_id'         => 'answer',
                'enable_fileupload' => false,
                'enable_richtext'   => true,
                'cols'              => 100,
                'rows'              => 30
            ]
        );
        echo "</td></tr>\n";

        $this->showFormButtons($options);
        return true;
    }


    /**
     * Get a translation for a value
     *
     * @param KnowbaseItem $item   item to translate
     * @param string       $field  field to return (default 'name')
     *
     * @return string  the field translated if a translation is available, or the original field if not
     **/
    public static function getTranslatedValue(KnowbaseItem $item, $field = "name")
    {
        $obj   = new self();
        $found = $obj->find([
            'knowbaseitems_id'   => $item->getID(),
            'language'           => $_SESSION['glpilanguage']
        ]);

        if (
            (count($found) > 0)
            && in_array($field, ['name', 'answer'])
        ) {
            $first = array_shift($found);
            return $first[$field];
        }
        return $item->fields[$field];
    }


    /**
     * Is kb item translation functionality active
     *
     * @return true if active, false if not
     **/
    public static function isKbTranslationActive()
    {
        global $CFG_GLPI;

        return $CFG_GLPI['translate_kb'];
    }


    /**
     * Check if an item can be translated
     * It be translated if translation if globally on and item is an instance of CommonDropdown
     * or CommonTreeDropdown and if translation is enabled for this class
     *
     * @param item the item to check
     *
     * @return true if item can be translated, false otherwise
     **/
    public static function canBeTranslated(CommonGLPI $item)
    {

        return (self::isKbTranslationActive()
              && $item instanceof KnowbaseItem);
    }


    /**
     * Return the number of translations for an item
     *
     * @param KnowbaseItem $item
     *
     * @return integer  the number of translations for this item
     **/
    public static function getNumberOfTranslationsForItem($item)
    {

        return countElementsInTable(
            getTableForItemType(__CLASS__),
            ['knowbaseitems_id' => $item->getID()]
        );
    }


    /**
     * Get already translated languages for item
     *
     * @param item
     *
     * @return array of already translated languages
     **/
    public static function getAlreadyTranslatedForItem($item)
    {
        global $DB;

        $tab = [];

        $iterator = $DB->request([
            'FROM'   => getTableForItemType(__CLASS__),
            'WHERE'  => ['knowbaseitems_id' => $item->getID()]
        ]);

        foreach ($iterator as $data) {
            $tab[$data['language']] = $data['language'];
        }
        return $tab;
    }

    public function pre_updateInDB()
    {
        $revision = new KnowbaseItem_Revision();
        $translation = new KnowbaseItemTranslation();
        $translation->getFromDB($this->getID());
        $revision->createNewTranslated($translation);
    }

    /**
     * Reverts item translation contents to specified revision
     *
     * @param integer $revid Revision ID
     *
     * @return boolean
     */
    public function revertTo($revid)
    {
        $revision = new KnowbaseItem_Revision();
        $revision->getFromDB($revid);

        $values = [
            'id'     => $this->getID(),
            'name'   => $revision->fields['name'],
            'answer' => $revision->fields['answer']
        ];

        if ($this->update($values)) {
            Event::log(
                $this->getID(),
                "knowbaseitemtranslation",
                5,
                "tools",
                //TRANS: %1$s is the user login, %2$s the revision number
                sprintf(__('%1$s reverts item translation to revision %2$s'), $_SESSION["glpiname"], $revision)
            );
            return true;
        } else {
            return false;
        }
    }
}
