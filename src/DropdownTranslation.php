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
 * DropdownTranslation Class
 *
 *@since 0.85
 **/
class DropdownTranslation extends CommonDBChild
{
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';
    public $dohistory       = true;
    public static $rightname       = 'dropdown';


    public static function getTypeName($nb = 0)
    {
        return _n('Translation', 'Translations', $nb);
    }

    public static function getIcon()
    {
        return 'ti ti-language';
    }

    /**
     * Forbidden massives actions
     **/
    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof CommonDropdown && $item->maybeTranslated()) {
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
        if ($item instanceof CommonDropdown && $item->maybeTranslated()) {
            self::showTranslations($item);
        }
        return true;
    }

    public function prepareInputForAdd($input)
    {
        if ($this->checkBeforeAddorUpdate($input, true)) {
            return $input;
        }
        Session::addMessageAfterRedirect(
            __s("There's already a translation for this field in this language"),
            true,
            ERROR
        );
        return false;
    }

    public function prepareInputForUpdate($input)
    {
        if ($this->checkBeforeAddorUpdate($input, false)) {
            return $input;
        }
        Session::addMessageAfterRedirect(
            __s("There's already a translation for this field in this language"),
            true,
            ERROR
        );
        return false;
    }

    public function post_purgeItem()
    {
        if ($this->fields['field'] === 'name') {
            $translation = new self();
            // If last translated field is deleted, then delete also completename record
            if (
                self::getNumberOfTranslations(
                    $this->fields['itemtype'],
                    $this->fields['items_id'],
                    $this->fields['field'],
                    $this->fields['language']
                ) === 0
            ) {
                if (
                    $completenames_id = self::getTranslationID(
                        $this->fields['items_id'],
                        $this->fields['itemtype'],
                        'completename',
                        $this->fields['language']
                    )
                ) {
                    $translation->delete(['id' => $completenames_id]);
                }
            }

            if (!isset($this->input['_no_completename'])) {
                $translation->generateCompletename($this->fields, false);
            }
        }
    }

    public function post_updateItem($history = true)
    {
        if (!isset($this->input['_no_completename'])) {
            $translation = new self();
            $translation->generateCompletename($this->fields, false);
        }
    }

    public function post_addItem()
    {
        // Add to session
        $_SESSION['glpi_dropdowntranslations'][$this->fields['itemtype']][$this->fields['field']]
            = $this->fields['field'];

        if (!isset($this->input['_no_completename'])) {
            $translation = new self();
            $translation->generateCompletename($this->fields, true);
        }
    }

    /**
     * Return the number of translations for a field in a language
     *
     * @param string $itemtype
     * @param integer $items_id
     * @param string $field
     * @param string $language
     *
     * @return integer the number of translations for this field
     **/
    public static function getNumberOfTranslations($itemtype, $items_id, $field, $language): int
    {
        return countElementsInTable(
            getTableForItemType(self::class),
            [
                'itemtype' => $itemtype,
                'items_id' => $items_id,
                'field'    => $field,
                'language' => $language,
            ]
        );
    }

    /**
     * Return the number of translations for an item
     *
     * @param CommonDBTM $item
     *
     * @return integer the number of translations for this item
     **/
    public static function getNumberOfTranslationsForItem($item)
    {
        return countElementsInTable(
            getTableForItemType(self::class),
            [
                'itemtype' => $item->getType(),
                'items_id' => $item->getID(),
                'NOT'      => ['field' => 'completename' ],
            ]
        );
    }

    /**
     * Check if a field's translation can be added or updated
     *
     * @param array $input          translation's fields
     * @param boolean $add true if a transaltion must be added, false if updated (true by default)
     *
     * @return boolean true if translation can be added/update, false otherwise
     **/
    public function checkBeforeAddorUpdate($input, $add = true)
    {
        $number = self::getNumberOfTranslations(
            $input['itemtype'],
            $input['items_id'],
            $input['field'],
            $input['language']
        );
        if ($add) {
            return ($number === 0);
        }
        return ($number > 0);
    }

    /**
     * Generate completename associated with a tree dropdown
     *
     * @param array $input Array of user values
     * @param boolean $add True if translation is added, false if update (tgrue by default)
     *
     * @return void
     **/
    public function generateCompletename($input, $add = true)
    {
        global $DB;

        $itemtype = $input['itemtype'];

        if (!is_a($itemtype, CommonTreeDropdown::class, true)) {
            return; // `completename` is used only for tree dropdowns
        }

        //If there's already a completename for this language, get it's ID, otherwise 0
        $completenames_id = self::getTranslationID(
            $input['items_id'],
            $itemtype,
            'completename',
            $input['language']
        );
        $item = new $itemtype();
        $item->getFromDB($input['items_id']);
        $foreignKey = $item::getForeignKeyField();

        $completename_parts  = [];
        $completename = "";

        if ((int) $item->fields[$foreignKey] !== 0) {
            // Get translated complename of parent item
            $tranlated_parent_completename = self::getTranslatedValue(
                $item->fields[$foreignKey],
                $itemtype,
                'completename',
                $input['language']
            );
            if ($tranlated_parent_completename !== '') {
                $completename_parts[] = $tranlated_parent_completename;
            } elseif ($parent = $itemtype::getById($item->fields[$foreignKey])) {
                // Fallback to untranslated completename of parent item
                $completename_parts[] = $parent->fields['completename'];
            }
        }

        // Append translated name of item
        $tranlated_name = self::getTranslatedValue(
            $item->getID(),
            $itemtype,
            'name',
            $input['language']
        );
        if ($tranlated_name !== '') {
            $completename_parts[] = $tranlated_name;
        } else {
            $completename_parts[] = $item->fields['name'];
        }

        $completename = implode(' > ', $completename_parts);

        // Add or update completename for this language
        $translation              = new self();
        $tmp                      = [];
        $tmp['items_id']          = $input['items_id'];
        $tmp['itemtype']          = $input['itemtype'];
        $tmp['field']             = 'completename';
        $tmp['value']             = $completename;
        $tmp['language']          = $input['language'];
        $tmp['_no_completename']  = true;
        if ($completenames_id) {
            $tmp['id']    = $completenames_id;
            if ($completename === $item->fields['completename']) {
                $translation->delete(['id' => $completenames_id]);
            } else {
                $translation->update($tmp);
            }
        } else {
            if ($completename !== $item->fields['completename']) {
                $translation->add($tmp);
            }
        }

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => $item::getTable(),
            'WHERE'  => [
                $foreignKey => $item->getID(),
            ],
        ]);

        foreach ($iterator as $tmp) {
            $input2 = $input;
            $input2['items_id'] = $tmp['id'];
            $this->generateCompletename($input2, $add);
        }
    }

    /**
     * Display all translated field for a dropdown
     *
     * @param CommonDropdown $item  A Dropdown item
     **/
    public static function showTranslations(CommonDropdown $item)
    {
        global $DB;

        $rand    = mt_rand();
        $canedit = $item->can($item->getID(), UPDATE);

        if ($canedit) {
            $twig_params = [
                'itemtype' => $item::class,
                'items_id' => $item->getID(),
                'item_fk' => $item->getForeignKeyField(),
                'rand' => $rand,
                'btn_msg' => __('Add a new translation'),
            ];
            // language=twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div id="viewtranslation{{ rand }}"></div>
                <script>
                    function viewEditTranslation{{ rand }}(translations_id = -1) {
                        $('button[name="new_translation"]').toggleClass('d-none', translations_id <= 0);
                        $('#viewtranslation{{ rand }}').load(
                            CFG_GLPI['root_doc'] + '/ajax/viewsubitem.php',
                            {
                                type: 'DropdownTranslation',
                                parenttype: '{{ itemtype|e('js') }}',
                                {{ item_fk }}: {{ items_id }},
                                id: translations_id
                            }
                        );
                    }
                    $(() => {
                        $('#datatable_translations{{ rand }}').on('click', 'tr.cursor-pointer', function() {
                            viewEditTranslation{{ rand }}($(this).data('id'));
                        });
                    });
                </script>
                <div class="text-center mb-3">
                    <button name="new_translation" class="btn btn-primary" type="button" onclick="viewEditTranslation{{ rand }}()">
                        {{ btn_msg }}
                    </button>
                </div>
TWIG, $twig_params);
        }

        $iterator = $DB->request([
            'FROM'   => getTableForItemType(self::class),
            'WHERE'  => [
                'itemtype'  => $item->getType(),
                'items_id'  => $item->getID(),
                'field'     => ['<>', 'completename'],
            ],
            'ORDER'  => ['language ASC'],
        ]);

        $entries = [];
        foreach ($iterator as $data) {
            $searchOption = $item->getSearchOptionByField('field', $data['field']);
            $matching_field = $item->getAdditionalField($data['field']);
            $entry = [
                'itemtype' => self::class,
                'id'       => $data['id'],
                'row_class' => $canedit ? 'cursor-pointer' : '',
                'language' => Dropdown::getLanguageName($data['language']),
                'field'    => $searchOption['name'],
            ];
            if (($matching_field['type'] ?? null) === 'tinymce') {
                $entry['value'] = '<div class="rich_text_container">' . RichText::getSafeHtml($data['value']) . '</div>';
            } else {
                $entry['value'] = htmlescape($data['value']);
            }
            $entries[] = $entry;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'datatable_id' => 'datatable_translations' . $rand,
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'language' => __('Language'),
                'field'    => _n('Field', 'Fields', 1),
                'value'    => __('Value'),
            ],
            'formatters' => [
                'value' => 'raw_html',
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
    }

    /**
     * Display translation form
     *
     * @param integer $ID       field (default -1)
     * @param array   $options
     */
    public function showForm($ID = -1, array $options = [])
    {
        if (!isset($options['parent']) || !($options['parent'] instanceof CommonDropdown)) {
            // parent is mandatory
            trigger_error('Parent item must be defined in `$options["parent"]`.', E_USER_WARNING);
            return false;
        }
        $item = $options['parent'];

        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            $options['itemtype'] = get_class($item);
            $options['items_id'] = $item->getID();

            $this->check(-1, CREATE, $options);
        }

        TemplateRenderer::getInstance()->display('pages/setup/dropdowntranslation.html.twig', [
            'parent_item' => $item,
            'item' => $this,
            'search_option' => !$item->isNewItem() ? $item->getSearchOptionByField('field', $this->fields['field']) : [],
            'matching_field' => $item->getAdditionalField($this->fields['field']),
            'no_header' => true,
        ]);
        return true;
    }

    /**
     * Display a dropdown with fields that can be translated for an itemtype
     *
     * @param CommonDBTM $item      a Dropdown item
     * @param string     $language  language to look for translations (default '')
     * @param string     $value     field which must be selected by default (default '')
     *
     * @return integer the dropdown's random identifier
     **/
    public static function dropdownFields(CommonDBTM $item, $language = '', $value = '')
    {
        global $DB;

        $options = [];
        foreach (Search::getOptions(get_class($item)) as $id => $opt) {
            //Can only translate name, and fields whose datatype is text or string and only fields directly for this itemtype
            $field = $opt['field'] ?? null;
            $type  = $opt['datatype'] ?? '';
            if (
                $field !== null
                && ($field === 'name' || in_array($type, ['text', 'string']))
                && $opt['table'] === getTableForItemType(get_class($item))
            ) {
                $options[$field] = $opt['name'];
            }
        }

        $used = [];
        if ($options !== []) {
            $iterator = $DB->request([
                'SELECT' => ['field'],
                'FROM'   => self::getTable(),
                'WHERE'  => [
                    'itemtype'  => $item::class,
                    'items_id'  => $item->getID(),
                    'language'  => $language,
                ],
            ]);
            foreach ($iterator as $data) {
                $used[$data['field']] = $data['field'];
            }
        }
        return Dropdown::showFromArray('field', $options, ['value' => $value,
            'used'  => $used,
        ]);
    }

    /**
     * Get translated value for a field in a particular language
     *
     * @param integer $ID        dropdown item's id
     * @param string  $itemtype  dropdown itemtype
     * @param string  $field     the field to look for (default 'name')
     * @param string  $language  get translation for this language
     * @param string  $value     default value for the field (default '')
     *
     * @return string the translated value of the value in the default language
     **/
    public static function getTranslatedValue($ID, $itemtype, $field = 'name', $language = '', $value = '')
    {
        global $DB;

        if (!is_a($itemtype, CommonDropdown::class, true)) {
            return $value;
        }

        if ($language === '') {
            $language = $_SESSION['glpilanguage'];
        }

        $translated_fields = $language === $_SESSION['glpilanguage'] && isset($_SESSION['glpi_dropdowntranslations'])
            ? $_SESSION['glpi_dropdowntranslations']
            : self::getAvailableTranslations($language);

        // If dropdown translation is globally off, or if this itemtype cannot be translated,
        // then original value should be returned
        if (
            !$ID
            || !isset($translated_fields[$itemtype][$field])
        ) {
            return $value;
        }
        // ID > 0 : dropdown item might be translated !
        if ($ID > 0) {
            $item = new $itemtype();
            $item->getFromDB($ID);
            if (!$item->maybeTranslated()) {
                return $value;
            }

            // There's at least one translation for this itemtype
            if (self::hasItemtypeATranslation($itemtype)) {
                $iterator = $DB->request([
                    'SELECT' => ['value'],
                    'FROM'   => self::getTable(),
                    'WHERE'  => [
                        'itemtype'  => $itemtype,
                        'items_id'  => $ID,
                        'field'     => $field,
                        'language'  => $language,
                    ],
                ]);
                // The field is already translated in this language
                if (count($iterator)) {
                    $current = $iterator->current();
                    return $current['value'];
                }
            }
            // Get the value coming from the dropdown table
            $iterator = $DB->request([
                'SELECT' => [$field],
                'FROM'   => getTableForItemType($itemtype),
                'WHERE'  => ['id' => $ID],
            ]);
            if (count($iterator)) {
                $current = $iterator->current();
                return $current[$field];
            }
        }

        return "";
    }

    /**
     * Get the id of a translated string
     *
     * @param integer $ID          item id
     * @param string  $itemtype    item type
     * @param string  $field       the field for which the translation is needed
     * @param string  $language    the target language
     *
     * @return integer the row id or 0 if not translation found
     **/
    public static function getTranslationID($ID, $itemtype, $field, $language)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'items_id'  => $ID,
                'language'  => $language,
                'field'     => $field,
            ],
            'LIMIT'  => 1,
        ]);
        return count($iterator) ? $iterator->current()['id'] : 0;
    }

    /**
     * Get translations for an item
     *
     * @param string  $itemtype  itemtype
     * @param integer $items_id  item ID
     * @param string  $field     the field for which the translation is needed
     *
     * @return array
     **/
    public static function getTranslationsForAnItem($itemtype, $items_id, $field)
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'items_id'  => $items_id,
                'field'     => $field,
            ],
        ]);
        $data = [];
        foreach ($iterator as $tmp) {
            $data[$tmp['id']] = $tmp;
        }

        return $data;
    }

    /**
     * Regenerate all completename translations for an item
     *
     * @param string  $itemtype    itemtype
     * @param integer $items_id    item ID
     *
     * @return void
     **/
    public static function regenerateAllCompletenameTranslationsFor($itemtype, $items_id)
    {
        foreach (self::getTranslationsForAnItem($itemtype, $items_id, 'completename') as $data) {
            $dt = new DropdownTranslation();
            $dt->generateCompletename($data, false);
        }
    }

    /**
     * Check if there's at least one translation for this itemtype
     *
     * @param string $itemtype itemtype to check
     *
     * @return boolean true if there's at least one translation, otherwise false
     **/
    public static function hasItemtypeATranslation($itemtype)
    {
        return countElementsInTable(self::getTable(), ['itemtype' => $itemtype ]) > 0;
    }

    /**
     * Get available translations for a language
     *
     * @param string $language language
     *
     * @return array Array of table / field translated items
     **/
    public static function getAvailableTranslations($language)
    {
        global $DB;

        $tab = [];
        $iterator = $DB->request([
            'SELECT'          => [
                'itemtype',
                'field',
            ],
            'DISTINCT'        => true,
            'FROM'            => self::getTable(),
            'WHERE'           => ['language' => $language],
        ]);
        foreach ($iterator as $data) {
            $tab[$data['itemtype']][$data['field']] = $data['field'];
        }
        return $tab;
    }
}
