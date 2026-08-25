<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
 * Tag class
 */
class Tag extends CommonDropdown
{
    public static string $rightname = 'tag';

    public static function getTypeName($nb = 0): string
    {
        return _n('Tag', 'Tags', $nb);
    }

    /**
     * Get itemtypes that can be tagged with this tag.
     *
     * @param bool $all_if_empty If true and the tag has no restriction in DB, return all taggable itemtypes instead of an empty list.
     *
     * @return list<class-string<CommonDBTM>>
     */
    public function getItemtypes($all_if_empty = true): array
    {
        return Tag_Itemtype::getItemtypesByTag($this, $all_if_empty);
    }

    /**
     * Get the background color of the tag.
     *
     * @return string
     */
    public function getBackgroundColor(): string
    {
        if (Toolbox::isValidHexColor($this->fields['bg_color'])) {
            return $this->fields['bg_color'];
        }
        return $this->generateBackgroundColor($this->fields['name']);
    }

    /**
     * Get the text color of the tag.
     *
     * @return string
     */
    public function getTextColor(): string
    {
        if (Toolbox::isValidHexColor($this->fields['color'])) {
            return $this->fields['color'];
        }
        return $this->generateTextColor($this->getBackgroundColor());
    }

    /**
     * Format and check itemtypes input for add and update
     *
     * @param array<string, mixed> $input Input data
     * @return array<string, mixed> Formatted input
     */
    private function prepareItemtypes(array $input): array
    {
        global $CFG_GLPI;

        if (!isset($input['_itemtypes'])) {
            return $input;
        }

        if (!is_array($input['_itemtypes'])) {
            $input['_itemtypes'] = [];
        }

        $input['_itemtypes'] = array_unique($input['_itemtypes']);

        // Remove itemtypes which are not taggable
        foreach ($input['_itemtypes'] as $key => $itemtype) {
            if (!in_array($itemtype, $CFG_GLPI['taggable_types'])) {
                unset($input['_itemtypes'][$key]);
            }
        }

        return $input;
    }

    /**
     * Prepare colors input for add and update, generate them if not set or invalid
     *
     * @param array<string, mixed> $input Input data
     * @return array<string, mixed> Formatted input
     */
    private function prepareColorsInput(array $input): array
    {
        // If bg_color is not set or invalid, generate a background color based on the tag name
        if (isset($input['bg_color']) && (empty($input['bg_color']) || !Toolbox::isValidHexColor($input['bg_color']))) {
            $input['bg_color'] = $this->generateBackgroundColor($input['name'] ?? $this->fields['name'] ?? '');
        }

        // If color is not set or invalid, generate a text color based on the background color
        $effective_bg = $input['bg_color'] ?? $this->fields['bg_color'] ?? null;
        if (!Toolbox::isValidHexColor($effective_bg)) {
            $effective_bg = $this->generateBackgroundColor($input['name'] ?? $this->fields['name'] ?? '');
        }

        // If color is not set or invalid, generate a text color based on the background color
        if (isset($input['color']) && (empty($input['color']) || !Toolbox::isValidHexColor($input['color']))) {
            $input['color'] = $this->generateTextColor($effective_bg);
        }
        return $input;
    }

    /**
     * Generate a background color based on the tag name.
     *
     * @param string $seed The seed to generate the color
     * @return string The generated background color in hex format
     */
    public function generateBackgroundColor(string $seed): string
    {
        return Toolbox::getColorForString($seed);
    }

    /**
     * Generate a text color based on the background color.
     *
     * @param string $bg_color The background color in hex format
     * @return string The generated text color in hex format
     */
    public function generateTextColor(string $bg_color): string
    {
        return Html::getInvertedColor($bg_color, true, false);
    }

    /**
     * Check that no tag with the same name exists, regardless of entity.
     *
     * @param array<string, mixed> $input
     * @return bool
     */
    private function isUnique(array $input): bool
    {
        $criteria = [
            'name' => $input['name'],
            ['id'  => ['<>', $this->getID()]],
        ];
        $tags = $this->find($criteria);

        return count($tags) === 0;
    }

    /**
     * Get the tag with the given name.
     *
     * @param string $name
     * @return Tag|null
     */
    public function getTagByName(string $name): ?Tag
    {
        $tag = new self();
        $tag_found = $tag->getFromDBByCrit([
            'name' => $name,
        ]);
        if (!$tag_found) {
            return null;
        }
        return $tag;
    }

    /**
     * @param array<string, mixed> $input
     * @return false|array<string, mixed>
     */
    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);

        if (!is_array($input)) {
            return false;
        }

        if (!isset($input['entities_id'])) {
            $input['entities_id'] = Session::getActiveEntity();
        }

        if (empty($input['name'])) {
            Session::addMessageAfterRedirect(
                __s('The tag name cannot be empty!'),
                false,
                ERROR
            );
            return false;
        }

        if (!$this->isUnique($input)) {
            $conflicting_tag = $this->getTagByName($input['name']);
            if ($conflicting_tag !== null) {
                Session::addMessageAfterRedirect(
                    htmlescape(sprintf(
                        __('A tag with this name already exists in entity "%s"! Transfer the tag to another entity or change its name.'),
                        Dropdown::getDropdownName(Entity::getTable(), $conflicting_tag->fields['entities_id'])
                    )),
                    false,
                    ERROR
                );
            }
            return false;
        }

        $input = $this->prepareItemtypes($input);
        $input = $this->prepareColorsInput($input);
        return $input;
    }

    /**
     * @param array<string, mixed> $input
     * @return false|array<string, mixed>
     */
    public function prepareInputForUpdate($input)
    {
        $input = parent::prepareInputForUpdate($input);

        if (!is_array($input)) {
            return false;
        }

        if (!isset($input['entities_id'])) {
            $input['entities_id'] = $this->fields['entities_id'];
        }

        if (empty($input['name'])) {
            $input['name'] = $this->fields['name'];
        }

        if (!$this->isUnique($input)) {
            $conflicting_tag = $this->getTagByName($input['name']);
            if ($conflicting_tag !== null) {
                Session::addMessageAfterRedirect(
                    htmlescape(sprintf(
                        __('A tag with this name already exists in entity "%s"! Transfer the tag to another entity or change its name.'),
                        Dropdown::getDropdownName(Entity::getTable(), $conflicting_tag->fields['entities_id'])
                    )),
                    false,
                    ERROR
                );
            }
            return false;
        }
        $input = $this->prepareItemtypes($input);
        $input = $this->prepareColorsInput($input);
        return $input;
    }

    /**
     * Clean all related data in database when purging a tag
     */
    public function cleanDBonPurge(): void
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Tag_Itemtype::class,
                Tag_Item::class,
            ]
        );
    }

    public function post_addItem(): void
    {
        parent::post_addItem();

        if (!isset($this->input['_itemtypes'])) {
            return;
        }

        $tag_itemtype = new Tag_Itemtype();
        foreach ($this->input['_itemtypes'] as $itemtype) {
            $tag_itemtype->add([
                'tags_id' => $this->getID(),
                'itemtype' => $itemtype,
            ]);
        }
    }

    public function post_updateItem($history = true): void
    {
        parent::post_updateItem($history);

        if (!isset($this->input['_itemtypes'])) {
            return;
        }

        $old_itemtypes = $this->getItemtypes(false);
        $tag_itemtype = new Tag_Itemtype();
        foreach ($old_itemtypes as $itemtype) {
            if (!in_array($itemtype, $this->input['_itemtypes'], true)) {
                $tag_itemtype->deleteByCriteria([
                    'tags_id' => $this->getID(),
                    'itemtype' => $itemtype,
                ]);
            }
        }
        foreach ($this->input['_itemtypes'] as $itemtype) {
            if (!in_array($itemtype, $old_itemtypes, true)) {
                $tag_itemtype->add([
                    'tags_id' => $this->getID(),
                    'itemtype' => $itemtype,
                ]);
            }
        }
    }

    /**
     * @param int $ID
     * @param array<string, mixed> $options
     */
    public function showForm($ID, array $options = []): bool
    {
        if ($this->isNewID($ID)) {
            $options += $this->restoreInput();
        }
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/setup/tag.html.twig', [
            'item' => $this,
            'params' => $options,
        ]);
        return true;
    }

    public function rawSearchOptions(): array
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '11',
            'table'              => static::getTable(),
            'field'              => 'color',
            'name'               => __('Color'),
            'datatype'           => 'color',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => static::getTable(),
            'field'              => 'bg_color',
            'name'               => __('Background color'),
            'datatype'           => 'color',
        ];

        return $tab;
    }

    /**
     * @return array
     */
    public static function rawSearchOptionsToAdd(string $itemtype): array
    {
        if ($itemtype !== AllAssets::class && !Tag_Itemtype::isTaggableItemtype($itemtype)) {
            return [];
        }

        $tab = [];
        $name = _n('Tag', 'Tags', Session::getPluralNumber());

        $tab[] = [
            'id'                 => '490',
            'table'              => self::getTable(),
            'field'              => 'name',
            'name'               => $name,
            'datatype'           => 'specific',
            'forcegroupby'       => true,
            'aggregate'          => true,
            'massiveaction'      => false,
            'itemtype_for_tags'  => $itemtype !== AllAssets::class ? $itemtype : null,
            'joinparams'         => [
                'beforejoin' => [
                    'table'      => Tag_Item::getTable(),
                    'joinparams' => [
                        'jointype' => 'itemtype_item',
                    ],
                ],
            ],
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        // If the field is "name" and the values contain an array of tag IDs, render them as badges
        if ($field === 'name' && isset($values['values']) && is_array($values['values'])) {
            $tags_ids = array_column($values['values'], 'id');

            $badges = [];
            if (!empty($tags_ids)) {
                foreach ((new self())->find(['id' => $tags_ids]) as $tag_data) {
                    $tag = new self();
                    $tag->getFromResultSet($tag_data);
                    $badges[] = $tag->getBadgeHtml();
                }
            }

            if (empty($badges)) {
                return '';
            }

            return '<div class="d-flex flex-wrap gap-1">' . implode('', $badges) . '</div>';
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if ($field === 'name') {
            if (!is_array($values)) {
                $values = [$field => $values];
            }

            return self::dropdown([
                'name'      => $name,
                'value'     => $values[$field] ?? '',
                'display'   => false,
                '_itemtype' => $options['searchopt']['itemtype_for_tags'] ?? null,
            ]);
        }

        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * Render a tag's name as a colored badge.
     *
     * @param int    $tags_id
     * @param string $name
     *
     * @return string
     */
    public function getBadgeHtml(): string
    {
        return sprintf(
            '<span class="badge" style="background-color:%s;color:%s">%s</span>',
            htmlescape($this->getBackgroundColor()),
            htmlescape($this->getTextColor()),
            htmlescape($this->fields['name'])
        );
    }

    /**
     * Get the data needed to render a tags dropdown.
     *
     * @param string|array|null $itemtype Restrict the tags to those allowed for the given itemtype(s).
     *                                    Use null to get every active tag, regardless of itemtype (e.g. for the "All assets" search).
     *
     * @return array{tag_names: array<int, string>, bg_colors: array<int, string>, text_colors: array<int, string>}
     */
    public static function getTagsDropdownData(string|array|null $itemtype): array
    {
        $tag_names = [];
        $bg_colors = [];
        $text_colors = [];

        // Get tags allowed to be attached to items of the given itemtype(s)
        if ($itemtype === null) {
            $tags = [];
        } elseif (is_array($itemtype)) {
            $tags = Tag_Itemtype::getTagsByItemtypes($itemtype);
        } else {
            $tags = Tag_Itemtype::getTagsByItemtype($itemtype);
        }

        foreach ($tags as $tag) {
            $tag_names[$tag->getID()] = $tag->fields['name'];
            $bg_colors[$tag->getID()] = $tag->getBackgroundColor();
            $text_colors[$tag->getID()] = $tag->getTextColor();
        }

        return ['tag_names' => $tag_names, 'bg_colors' => $bg_colors, 'text_colors' => $text_colors];
    }

    /**
     * Dropdown for tags
     *
     * @param array $options Display options
     *
     * @return string|null
     */
    public static function dropdown($options = [])
    {
        $default_options = [
            'rand'      => mt_rand(),
            'display'   => true,
            'name'      => self::getForeignKeyField(),
            '_item'     => null,
            '_itemtype' => [],
        ];
        $options = array_merge($default_options, $options);

        $item = $options['_item'];
        $itemtype = $options['_itemtype'];

        if ($item instanceof CommonDBTM) {
            $itemtype = get_class($item);
            $options['multiple'] = true;
            $options['values'] = Tag_Item::getTagsForItem($item);
        } else {
            $options['display_emptychoice'] = true;
        }

        $data = self::getTagsDropdownData($itemtype);

        $add_option_attributes = [];
        foreach ($data['bg_colors'] as $tag_id => $bg_color) {
            $add_option_attributes[$tag_id] = [
                'bg-color'   => $bg_color,
                'text-color' => $data['text_colors'][$tag_id] ?? '',
            ];
        }
        $options['add_option_attributes'] = $add_option_attributes;
        $options['templateResult']        = 'templateTagResult';
        $options['templateSelection']     = 'templateTagSelection';

        $twig_params = [
            'data'          => $data,
            'options'       => $options,
            'can_create'    => self::canCreate() && !isset($_REQUEST['_in_modal']),
            'field_id'      => Html::cleanId('dropdown_' . $options['name'] . $options['rand']),
        ];

        if (!$options['display']) {
            return TemplateRenderer::getInstance()->render('components/form/tag_dropdown.html.twig', $twig_params);
        }

        TemplateRenderer::getInstance()->display('components/form/tag_dropdown.html.twig', $twig_params);
    }

    /**
     * Add the "add a tag" / "remove a tag" massive actions to the given itemtype's action list.
     *
     * @param array<string, string> $actions
     * @param class-string<CommonDBTM> $itemtype
     * @param bool $is_deleted
     * @param CommonDBTM|null $checkitem
     *
     * @return void
     */
    public static function getMassiveActionsForItemtype(
        array &$actions,
        $itemtype,
        $is_deleted = false,
        ?CommonDBTM $checkitem = null
    ): void {
        if (!Tag_Itemtype::isTaggableItemtype($itemtype) || !self::canView()) {
            return;
        }

        $action_prefix = Tag_Item::class . MassiveAction::CLASS_ACTION_SEPARATOR;

        $actions[$action_prefix . 'add']    = "<i class='ti ti-tag-plus'></i>" . _sx('button', 'Add a tag');
        $actions[$action_prefix . 'remove'] = "<i class='ti ti-tag-minus'></i>" . _sx('button', 'Remove a tag');
    }
}
