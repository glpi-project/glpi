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

    public static function getIcon()
    {
        return 'ti ti-language';
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addStandardTab(self::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);
        $this->addStandardTab(KnowbaseItem_Revision::class, $ong, $options);
        $this->addStandardTab(KnowbaseItem_Comment::class, $ong, $options);

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
            switch ($item::class) {
                case self::class:
                    $ong[1] = self::createTabEntry(self::getTypeName(1));
                    if ($item->canUpdateItem()) {
                        $ong[3] = self::createTabEntry(__('Edit'), icon: 'ti ti-edit');
                    }
                    return $ong;
            }
        }

        if ($item instanceof KnowbaseItem) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::getNumberOfTranslationsForItem($item);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item::class === self::class) {
            switch ($tabnum) {
                case 1:
                    $item->showFull();
                    break;
                case 3:
                    $item->showForm($item->getID(), ['parent' => $item]);
                    break;
            }
        } elseif ($item instanceof KnowbaseItem) {
            self::showTranslations($item);
        }
        return true;
    }

    /**
     * Print out (html) show item : question and answer
     *
     * @param array $options of options
     *
     * @return void
     **/
    public function showFull($options = [])
    {
        if (!$this->can($this->fields['id'], READ)) {
            return;
        }

        $twig_params = [
            'subject_label' => __('Subject'),
            'content_label' => __('Content'),
            'item'          => $this,
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="d-flex flex-column">
                <h2>{{ subject_label }}</h2>
                <span class="mb-3">{{ item.fields['name'] }}</span>
                <h2>{{ content_label }}</h2>
                <div id="kbanswer" class="rich_text_container">
                    {{ item.fields['answer']|enhanced_html }}
                </div>
            </div>
TWIG, $twig_params);
    }

    /**
     * Display all translated field for an KnowbaseItem
     *
     * @param KnowbaseItem $item
     *
     * @return true
     **/
    public static function showTranslations(KnowbaseItem $item)
    {
        $canedit = $item->can($item->getID(), UPDATE);
        $rand    = mt_rand();
        if ($canedit) {
            $twig_params = [
                'item' => $item,
                'btn_msg' => __('Add a new translation'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% set rand = random() %}
                <div id="viewtranslation{{ item.getID() ~ rand }}"></div>
                <div class="text-center mb-3">
                    <button name="add_translation{{ rand }}" class="btn btn-primary">{{ btn_msg }}</button>
                </div>
                <script>
                    $(() => {
                        function addTranslation{{ item.getID() ~ rand }}() {
                            $('#viewtranslation{{ item.getID() ~ rand }}').load(
                                '/ajax/viewsubitem.php',
                                {
                                    type: 'KnowbaseItemTranslation',
                                    parenttype: '{{ get_class(item)|e('js') }}',
                                    knowbaseitems_id: {{ item.getID() }},
                                    id: -1
                                }
                            );
                        }
                        $('button[name="add_translation{{ rand }}"]').on('click', addTranslation{{ item.getID() ~ rand }});
                    });
                </script>
TWIG, $twig_params);
        }

        $obj   = new self();
        $found = $obj->find(['knowbaseitems_id' => $item->getID()], "language ASC");

        $entries = [];
        foreach ($found as $data) {
            $name = htmlescape($data["name"]);
            if ($canedit) {
                $name = "<a href=\"" . htmlescape(self::getFormURLWithID($data["id"])) . "\">{$name}</a>";
            }
            if (!empty($data['answer'])) {
                $name .= Html::showToolTip(RichText::getEnhancedHtml($data['answer']), [
                    'display' => false,
                ]);
            }

            $entries[] = [
                'itemtype' => self::class,
                'id'       => $data['id'],
                'language' => Dropdown::getLanguageName($data['language']),
                'name'     => $name,
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'language' => __('Language'),
                'name'     => __('Subject'),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
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
        if (($ID <= 0 && !isset($options['parent'])) || !($options['parent'] instanceof CommonDBTM)) {
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

        TemplateRenderer::getInstance()->display('pages/tools/kb/translation.html.twig', [
            'item' => $this,
            'no_header' => true,
            'used_languages' => $ID <= 0 ? self::getAlreadyTranslatedForItem($options['parent']) : [],
        ]);
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
            'language'           => $_SESSION['glpilanguage'],
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
     * Return the number of translations for an item
     *
     * @param KnowbaseItem $item
     *
     * @return integer  the number of translations for this item
     **/
    public static function getNumberOfTranslationsForItem($item)
    {
        return countElementsInTable(
            getTableForItemType(self::class),
            ['knowbaseitems_id' => $item->getID()]
        );
    }

    /**
     * Get already translated languages for item
     *
     * @param KnowbaseItem $item
     *
     * @return array Array of already translated languages
     **/
    public static function getAlreadyTranslatedForItem(KnowbaseItem $item): array
    {
        global $DB;

        $tab = [];

        $iterator = $DB->request([
            'SELECT' => ['language'],
            'FROM'   => self::getTable(),
            'WHERE'  => ['knowbaseitems_id' => $item->getID()],
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
        $revision->createNew($translation);
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
            'answer' => $revision->fields['answer'],
        ];

        if ($this->update($values)) {
            Event::log(
                $this->getID(),
                "knowbaseitemtranslation",
                5,
                "tools",
                //TRANS: %1$s is the user login, %2$s the revision number
                sprintf(__('%1$s reverts item translation to revision %2$s'), $_SESSION["glpiname"], $revision->fields['revision'])
            );
            return true;
        }

        return false;
    }
}
