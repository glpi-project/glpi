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
     * @param CommonGLPI $item            CommonGLPI object
     * @param integer $tabnum          (default 1)
     * @param integer $withtemplate    (default 0)
     **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if (DropdownTranslation::canBeTranslated($item)) {
            DropdownTranslation::showTranslations($item);
        }
        return true;
    }


    public function prepareInputForAdd($input)
    {

        if ($this->checkBeforeAddorUpdate($input, true)) {
            return $input;
        }
        Session::addMessageAfterRedirect(
            __("There's already a translation for this field in this language"),
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
            __("There's already a translation for this field in this language"),
            true,
            ERROR
        );
        return false;
    }


    public function post_purgeItem()
    {
        if ($this->fields['field'] == 'name') {
            $translation = new self();
           //If last translated field is deleted, then delete also completename record
            if (
                $this->getNumberOfTranslations(
                    $this->fields['itemtype'],
                    $this->fields['items_id'],
                    $this->fields['field'],
                    $this->fields['language']
                ) == 0
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
           // If only completename for sons : drop
           // foreach (getSonsOf(getTableForItemType($this->fields['itemtype']),
           //                                        $this->fields['items_id']) as $son) {

           //    if ($this->getNumberOfTranslations($this->fields['itemtype'], $son,
           //                                      'name', $this->fields['language']) == 0) {

           //       $completenames_id = self::getTranslationID($son, $this->fields['itemtype'],
           //                                                      'completename',
           //                                                      $this->fields['language']);
           //       if ($completenames_id) {
           //          $translation = new self();
           //          $translation->delete(array('id' => $completenames_id));
           //       }
           //    }
           // }
           // Then update all sons records
            if (!isset($this->input['_no_completename'])) {
                $translation->generateCompletename($this->fields, false);
            }
        }
        return true;
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
     * @param int $items_id
     * @param string $field
     * @param string $language
     *
     * @return integer the number of translations for this field
     **/
    public static function getNumberOfTranslations($itemtype, $items_id, $field, $language)
    {

        return countElementsInTable(
            getTableForItemType(__CLASS__),
            ['itemtype' => $itemtype,
                'items_id' => $items_id,
                'field'    => $field,
                'language' => $language
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
        /** @var \DBmysql $DB */
        global $DB;

        return countElementsInTable(
            getTableForItemType(__CLASS__),
            ['itemtype' => $DB->escape($item->getType()),
                'items_id' => $item->getID(),
                'NOT'      => ['field' => 'completename' ]
            ]
        );
    }


    /**
     * Check if a field's translation can be added or updated
     *
     * @param bool $input          translation's fields
     * @param bool $add    boolean true if a transaltion must be added, false if updated (true by default)
     *
     * @return boolean true if translation can be added/update, false otherwise
     **/
    public function checkBeforeAddorUpdate($input, $add = true)
    {
        $number = $this->getNumberOfTranslations(
            $input['itemtype'],
            $input['items_id'],
            $input['field'],
            $input['language']
        );
        if ($add) {
            return ($number == 0);
        }
        return ($number > 0);
    }


    /**
     * Generate completename associated with a tree dropdown
     *
     * @param array $input     array of user values
     * @param bool $add   boolean  true if translation is added, false if update (tgrue by default)
     *
     * @return void
     **/
    public function generateCompletename($input, $add = true)
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!is_a($input['itemtype'], CommonTreeDropdown::class, true)) {
            return; // `completename` is used only for tree dropdowns
        }
        $itemtype = $input['itemtype'];

        //If there's already a completename for this language, get it's ID, otherwise 0
        $completenames_id = self::getTranslationID(
            $input['items_id'],
            $itemtype,
            'completename',
            $input['language']
        );
        $item = new $itemtype();
        $item->getFromDB($input['items_id']);
        $foreignKey = $item->getForeignKeyField();

        $completename_parts  = [];
        $completename = "";

        if ($item->fields[$foreignKey] != 0) {
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
        $tmp['value']             = addslashes($completename);
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
            if ($completename != $item->fields['completename']) {
                 $translation->add($tmp);
            }
        }

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => $item->getTable(),
            'WHERE'  => [
                $foreignKey => $item->getID()
            ]
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
     *
     * @return true;
     **/
    public static function showTranslations(CommonDropdown $item)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $rand    = mt_rand();
        $canedit = $item->can($item->getID(), UPDATE);

       //Remove namespace separators
        $normalized_itemtype = str_replace('\\', '', $item->getType());
        if ($canedit) {
            echo "<div id='viewtranslation" . $normalized_itemtype . $item->getID() . "$rand'></div>\n";

            echo "<script type='text/javascript' >\n";
            echo "function addTranslation" . $normalized_itemtype . $item->getID() . "$rand() {\n";
            $params = ['type'                       => __CLASS__,
                'parenttype'                 => get_class($item),
                $item->getForeignKeyField()  => $item->getID(),
                'id'                         => -1
            ];
            Ajax::updateItemJsCode(
                "viewtranslation" . $normalized_itemtype . $item->getID() . "$rand",
                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                $params
            );
            echo "};";
            echo "</script>\n";
            echo "<div class='center'>" .
              "<a class='btn btn-primary' href='javascript:addTranslation" .
              $normalized_itemtype . $item->getID() . "$rand();'>" . __('Add a new translation') .
              "</a></div><br>";
        }

        $iterator = $DB->request([
            'FROM'   => getTableForItemType(__CLASS__),
            'WHERE'  => [
                'itemtype'  => $DB->escape($item->getType()),
                'items_id'  => $item->getID(),
                'field'     => ['<>', 'completename']
            ],
            'ORDER'  => ['language ASC']
        ]);
        if (count($iterator)) {
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
                Html::showMassiveActions($massiveactionparams);
            }
            echo "<div class='center'>";
            echo "<table class='tab_cadre_fixehov'><tr class='tab_bg_2'>";
            echo "<th colspan='4'>" . __("List of translations") . "</th></tr><tr>";
            if ($canedit) {
                echo "<th width='10'>";
                echo Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                echo "</th>";
            }
            echo "<th>" . __("Language") . "</th>";
            echo "<th>" . _n('Field', 'Fields', 1) . "</th>";
            echo "<th>" . __("Value") . "</th></tr>";
            foreach ($iterator as $data) {
                $onhover = '';
                if ($canedit) {
                    $onhover = "style='cursor:pointer'
                           onClick=\"viewEditTranslation" . $normalized_itemtype . $data['id'] . "$rand();\"";
                }
                echo "<tr class='tab_bg_1'>";
                if ($canedit) {
                    echo "<td class='center'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                    echo "</td>";
                }

                echo "<td $onhover>";
                if ($canedit) {
                    echo "\n<script type='text/javascript' >\n";
                    echo "function viewEditTranslation" . $normalized_itemtype . $data['id'] . "$rand() {\n";
                    $params = ['type'                     => __CLASS__,
                        'parenttype'                => get_class($item),
                        $item->getForeignKeyField() => $item->getID(),
                        'id'                        => $data["id"]
                    ];
                    Ajax::updateItemJsCode(
                        "viewtranslation" . $normalized_itemtype . $item->getID() . "$rand",
                        $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                        $params
                    );
                    echo "};";
                    echo "</script>\n";
                }
                echo Dropdown::getLanguageName($data['language']);
                echo "</td><td $onhover>";
                $searchOption = $item->getSearchOptionByField('field', $data['field']);
                echo $searchOption['name'] . "</td>";
                echo "<td $onhover>";
                $matching_field = $item->getAdditionalField($data['field']);
                if (($matching_field['type'] ?? null) === 'tinymce') {
                    echo '<div class="rich_text_container">' . RichText::getSafeHtml($data['value']) . '</div>';
                } else {
                    echo $data['value'];
                }
                echo "</td>";
                echo "</tr>";
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
     * @param integer $ID       field (default -1)
     * @param array   $options
     */
    public function showForm($ID = -1, array $options = [])
    {
        if (!isset($options['parent']) || !($options['parent'] instanceof CommonDBTM)) {
            // parent is mandatory
            trigger_error('Parent item must be defined in `$options["parent"]`.', E_USER_WARNING);
            return false;
        }

        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $item = $options['parent'];

        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            $options['itemtype'] = get_class($item);
            $options['items_id'] = $item->getID();

           // Create item
            $this->check(-1, CREATE, $options);
        }
        $rand = mt_rand();
        $this->showFormHeader($options);
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Language') . "</td>";
        echo "<td>";
        echo "<input type='hidden' name='items_id' value='" . $item->getID() . "'>";
        echo "<input type='hidden' name='itemtype' value='" . get_class($item) . "'>";
        if ($ID > 0) {
            echo "<input type='hidden' name='language' value='" . $this->fields['language'] . "'>";
            echo Dropdown::getLanguageName($this->fields['language']);
        } else {
            $rand   = Dropdown::showLanguages(
                "language",
                ['display_none' => false,
                    'value'        => $_SESSION['glpilanguage']
                ]
            );
            $params = ['language' => '__VALUE__',
                'itemtype' => get_class($item),
                'items_id' => $item->getID()
            ];
            Ajax::updateItemOnSelectEvent(
                "dropdown_language$rand",
                "span_fields",
                $CFG_GLPI["root_doc"] . "/ajax/updateTranslationFields.php",
                $params
            );
        }
        echo "</td><td colspan='2'>&nbsp;</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . _n('Field', 'Fields', 1) . "</td>";
        echo "<td>";
        if ($ID > 0) {
            echo "<input type='hidden' name='field' value='" . $this->fields['field'] . "'>";
            $searchOption = $item->getSearchOptionByField('field', $this->fields['field']);
            echo $searchOption['name'];
        } else {
            echo "<span id='span_fields' name='span_fields'>";
            $rand = self::dropdownFields($item, $_SESSION['glpilanguage']);
            echo "</span>";
            $params = [
                'field'    => '__VALUE__',
                'itemtype' => get_class($item),
                'items_id' => $item->getID(),
            ];
            Ajax::updateItemOnSelectEvent(
                "dropdown_field$rand",
                "span_value",
                $CFG_GLPI["root_doc"] . "/ajax/updateTranslationValue.php",
                $params
            );
            echo Html::scriptBlock(<<<JAVASCRIPT
                $(
                    function() {
                        $("#dropdown_field$rand").trigger("change");
                    }
                );
JAVASCRIPT
            );
        }
        echo "</td>";
        echo "<td>" . __('Value') . "</td>";
        echo "<td>";
        echo "<span id='span_value'>";
        if (
            ($ID > 0)
            && ($item instanceof CommonDropdown)
        ) {
            $matching_field = $item->getAdditionalField($this->fields['field']);
            if (($matching_field['type'] ?? null) === 'tinymce') {
                Html::textarea([
                    'name'              => 'value',
                    'value'             => RichText::getSafeHtml($this->fields["value"], true),
                    'enable_richtext'   => true,
                    'enable_images'     => false,
                    'enable_fileupload' => false,
                ]);
            } else {
                echo "<input type='text' name='value' value=\"" . $this->fields['value'] . "\" size='50'>";
            }
        }
        echo "</span>";
        echo "</td>";
        echo "</tr>\n";
        $this->showFormButtons($options);
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
        /** @var \DBmysql $DB */
        global $DB;

        $options = [];
        foreach (Search::getOptions(get_class($item)) as $id => $field) {
           //Can only translate name, and fields whose datatype is text or string
            if (
                isset($field['field'])
                && ($field['field'] == 'name')
                && ($field['table'] == getTableForItemType(get_class($item)))
                || (isset($field['datatype'])
                 && in_array($field['datatype'], ['text', 'string']))
            ) {
                $options[$field['field']] = $field['name'];
            }
        }

        $used = [];
        if (!empty($options)) {
            $iterator = $DB->request([
                'SELECT' => 'field',
                'FROM'   => self::getTable(),
                'WHERE'  => [
                    'itemtype'  => $DB->escape($item->getType()),
                    'items_id'  => $item->getID(),
                    'language'  => $language
                ]
            ]);
            if (count($iterator) > 0) {
                foreach ($iterator as $data) {
                    $used[$data['field']] = $data['field'];
                }
            }
        }
       //$used = array();
        return Dropdown::showFromArray('field', $options, ['value' => $value,
            'used'  => $used
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
        /** @var \DBmysql $DB */
        global $DB;

        if ($language == '') {
            $language = $_SESSION['glpilanguage'];
        }

        $translated_fields = $language === $_SESSION['glpilanguage'] && isset($_SESSION['glpi_dropdowntranslations'])
            ? $_SESSION['glpi_dropdowntranslations']
            : DropdownTranslation::getAvailableTranslations($language);

       //If dropdown translation is globally off, or if this itemtype cannot be translated,
       //then original value should be returned
        if (
            !$ID
            || !isset($translated_fields[$itemtype][$field])
        ) {
            return $value;
        }
       //ID > 0 : dropdown item might be translated !
        if ($ID > 0) {
           //There's at least one translation for this itemtype
            if (self::hasItemtypeATranslation($itemtype)) {
                $iterator = $DB->request([
                    'SELECT' => ['value'],
                    'FROM'   => self::getTable(),
                    'WHERE'  => [
                        'itemtype'  => $itemtype,
                        'items_id'  => $ID,
                        'field'     => $field,
                        'language'  => $language
                    ]
                ]);
               //The field is already translated in this language
                if (count($iterator)) {
                     $current = $iterator->current();
                     return $current['value'];
                }
            }
           //Get the value coming from the dropdown table
            $iterator = $DB->request([
                'SELECT' => $field,
                'FROM'   => getTableForItemType($itemtype),
                'WHERE'  => ['id' => $ID]
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
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'items_id'  => $ID,
                'language'  => $language,
                'field'     => $field
            ]
        ]);
        if (count($iterator)) {
            $current = $iterator->current();
            return $current['id'];
        }
        return 0;
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

        return (self::isDropdownTranslationActive()
              && (($item instanceof CommonDropdown)
                  && $item->maybeTranslated()));
    }


    /**
     * Is dropdown item translation functionality active
     *
     * @return boolean true if active, false if not
     **/
    public static function isDropdownTranslationActive()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return $CFG_GLPI['translate_dropdowns'];
    }


    /**
     * Get a translation for a value
     *
     * @param string $itemtype  itemtype
     * @param string $field     field to query
     * @param string $value     value to translate
     *
     * @return string the value translated if a translation is available, or the same value if not
     **/
    public static function getTranslationByName($itemtype, $field, $value)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => getTableForItemType($itemtype),
            'WHERE'  => [
                $field   => Toolbox::addslashes_deep($value)
            ]
        ]);
        if (count($iterator) > 0) {
            $current = $iterator->current();
            return self::getTranslatedValue(
                $current['id'],
                $itemtype,
                $field,
                $_SESSION['glpilanguage'],
                $value
            );
        }
        return $value;
    }

    /**
     * Get translations for an item
     *
     * @param string  $itemtype  itemtype
     * @param integer $items_id  item ID
     * @param string  $field     the field for which the translation is needed
     *
     * @return string the value translated if a translation is available, or the same value if not
     **/
    public static function getTranslationsForAnItem($itemtype, $items_id, $field)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'items_id'  => $items_id,
                'field'     => $field
            ]
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
        return countElementsInTable(self::getTable(), ['itemtype' => $itemtype ]);
    }


    /**
     * Get available translations for a language
     *
     * @param string $language language
     *
     * @return array of table / field translated item
     **/
    public static function getAvailableTranslations($language)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $tab = [];
        if (self::isDropdownTranslationActive()) {
            $iterator = $DB->request([
                'SELECT'          => [
                    'itemtype',
                    'field'
                ],
                'DISTINCT'        => true,
                'FROM'            => self::getTable(),
                'WHERE'           => ['language' => $language]
            ]);
            foreach ($iterator as $data) {
                 $tab[$data['itemtype']][$data['field']] = $data['field'];
            }
        }
        return $tab;
    }
}
