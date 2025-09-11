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

    public static function getIcon()
    {
        return 'ti ti-language';
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (
            $item instanceof Reminder
            && Session::getCurrentInterface() != "helpdesk"
        ) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::getNumberOfTranslationsForItem($item);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Reminder) {
            self::showTranslations($item);
        }
        return true;
    }

    /**
     * Display all translated field for a Reminder
     *
     * @param Reminder $item a Reminder item
     *
     * @return true
     **/
    public static function showTranslations(Reminder $item)
    {
        $canedit = $item->can($item->getID(), UPDATE);
        $rand    = mt_rand();
        if ($canedit) {
            $twig_params = [
                'item' => $item,
                'rand' => $rand,
                'button_msg' => __('Add a new translation'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div class="text-center">
                    <button class="btn btn-primary" onclick="showTranslation{{ item.getID() ~ rand }}(-1)">{{ button_msg }}</button>
                </div>
                <div id="viewtranslation{{ item.getID() ~ rand }}" class="mb-3"></div>
                <script>
                    function showTranslation{{ item.getID() ~ rand }}(translations_id) {
                        $.ajax({
                            url: CFG_GLPI.root_doc + '/ajax/viewsubitem.php',
                            method: 'POST',
                            data: {
                                type: 'ReminderTranslation',
                                parenttype: '{{ item.getType()|e('js') }}',
                                reminders_id: {{ item.getID() }},
                                id: translations_id
                            },
                            success: (data) => {
                                $('#viewtranslation{{ item.getID() ~ rand }}').html(data);
                            }
                        });
                    }
                    $(() => {
                        $('#translationlist{{ rand }} tbody tr').on('click', function() {
                            showTranslation{{ item.getID() ~ rand }}($(this).attr('data-id'));
                        });
                    });
                </script>
TWIG, $twig_params);
        }

        $obj   = new self();
        $found = $obj->find(['reminders_id' => $item->getID()], "language ASC");

        $entries = [];
        foreach ($found as $data) {
            $entry = [
                'itemtype' => self::class,
                'id' => $data['id'],
            ];
            if ($canedit) {
                $entry['row_class'] = 'cursor-pointer';
            }
            $entry['language'] = Dropdown::getLanguageName($data['language']);

            if ($canedit) {
                $entry['subject'] = sprintf(
                    '<a href="%s">%s</a>',
                    htmlescape(self::getFormURLWithID($data['id'])),
                    htmlescape($data['name'])
                );
            } else {
                $entry['subject'] = htmlescape($data['name']);
            }
            if (!empty($data['text'])) {
                $entry['subject'] .= Html::showToolTip(RichText::getEnhancedHtml($data['text']), ['display' => false]);
            }
            $entries[] = $entry;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'datatable_id' => 'translationlist' . $rand,
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'language' => __('Language'),
                'subject' => __('Subject'),
            ],
            'formatters' => [
                'subject' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => min($_SESSION['glpilist_limit'], count($entries)),
                'container'     => 'mass' . static::class . $rand,
                'specific_actions' => ['purge' => _x('button', 'Delete permanently')],
            ],
        ]);

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
        if ($this->getID() > 0) {
            $this->check($ID, READ);
        } else {
            // Create item
            $item                = $options['parent'];
            $options['itemtype'] = get_class($item);
            $options['reminders_id'] = $item->getID();
            $this->check(-1, CREATE, $options);
        }
        $options['canedit'] = $this->can($ID, UPDATE);

        TemplateRenderer::getInstance()->display('pages/tools/reminder_translation.html.twig', [
            'item' => $this,
            'used_langs' => isset($item) ? self::getAlreadyTranslatedForItem($item) : [],
            'params' => $options,
            'no_header' => true,
        ]);
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
     * Return the number of translations for an item
     *
     * @param Reminder $item
     *
     * @return integer  the number of translations for this item
     **/
    public static function getNumberOfTranslationsForItem($item)
    {
        return countElementsInTable(
            getTableForItemType(self::class),
            ['reminders_id' => $item->getID()]
        );
    }

    /**
     * Get already translated languages for item
     *
     * @param Reminder $item
     *
     * @return array of already translated languages
     **/
    public static function getAlreadyTranslatedForItem($item)
    {
        global $DB;

        $tab = [];

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => ['reminders_id' => $item->getID()],
        ]);

        foreach ($iterator as $data) {
            $tab[$data['language']] = $data['language'];
        }
        return $tab;
    }
}
