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

namespace Glpi\Application\View\Extension;

use CommonDBTM;
use CommonDropdown;
use CommonGLPI;
use Dropdown;
use Glpi\Toolbox\Sanitizer;
use Group;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use User;

/**
 * @since 10.0.0
 */
class ItemtypeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('itemtype_class', [$this, 'getItemtypeClass']),
            new TwigFilter('itemtype_dropdown', [$this, 'getItemtypeDropdown'], ['is_safe' => ['html']]),
            new TwigFilter('itemtype_form_path', [$this, 'getItemtypeFormPath']),
            new TwigFilter('itemtype_icon', [$this, 'getItemtypeIcon']),
            new TwigFilter('itemtype_name', [$this, 'getItemtypeName']),
            new TwigFilter('itemtype_search_path', [$this, 'getItemtypeSearchPath']),
            new TwigFilter('itemtype_foreign_key', [$this, 'getItemtypeForeignKey']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_item', [$this, 'getItem']),
            new TwigFunction('get_item_comment', [$this, 'getItemComment']),
            new TwigFunction('get_item_link', [$this, 'getItemLink'], ['is_safe' => ['html']]),
            new TwigFunction('get_item_name', [$this, 'getItemName']),
        ];
    }

    /**
     * Returns class instance of given itemtype.
     *
     * @param string $itemtype
     *
     * @return CommonGLPI|null
     */
    public function getItemtypeClass(string $itemtype): ?CommonGLPI
    {
        return is_a($itemtype, CommonGLPI::class, true) ? new $itemtype() : null;
    }

    /**
     * Returns dropdwon HTML code for given itemtype.
     *
     * @param string $itemtype
     *
     * @return string|null
     */
    public function getItemtypeDropdown($itemtype, array $options = []): ?string
    {
        $options['display'] = false;
        return is_a($itemtype, CommonDBTM::class, true) ? $itemtype::dropdown($options) : null;
    }

    /**
     * Returns domain relative path of given itemtype form.
     *
     * @param string $itemtype
     * @param null|int $id
     *
     * @return string|null
     */
    public function getItemtypeFormPath(string $itemtype, ?int $id = null): ?string
    {
        if (!is_a($itemtype, CommonGLPI::class, true)) {
            return null;
        }

        return $id !== null ? $itemtype::getFormURLWithID($id) : $itemtype::getFormURL();
    }

    /**
     * Returns typename of given itemtype.
     *
     * @param string $itemtype
     *
     * @return string|null
     */
    public function getItemtypeIcon(string $itemtype): ?string
    {
        return is_a($itemtype, CommonDBTM::class, true) ? $itemtype::getIcon() : null;
    }

    /**
     * Returns typename of given itemtype.
     *
     * @param string $itemtype
     * @param number $count
     *
     * @return string|null
     */
    public function getItemtypeName(string $itemtype, $count = 1): ?string
    {
        return is_a($itemtype, CommonGLPI::class, true) ? $itemtype::getTypeName($count) : null;
    }

    /**
     * Returns domain relative path of given itemtype search.
     *
     * @param string $itemtype
     *
     * @return string|null
     */
    public function getItemtypeSearchPath(string $itemtype): ?string
    {
        return is_a($itemtype, CommonGLPI::class, true) ? $itemtype::getSearchURL() : null;
    }

    /**
     * Returns item from given itemtype having given ID.
     *
     * @param string  $itemtype Itemtype of the item.
     * @param int     $id       ID of the item.
     *
     * @return CommonDBTM|null
     */
    public function getItem($itemtype, int $id): ?CommonDBTM
    {
        if (is_a($itemtype, CommonDBTM::class, true) && ($item = $itemtype::getById($id)) !== false) {
            return $item;
        }
        return null;
    }

    /**
     * Returns name of the given item.
     * In case of a dropdown, it returns the translated name, otherwise, it returns the friendly name.
     *
     * @param CommonDBTM|string $item   Item instance of itemtype of the item.
     * @param int|null $id              ID of the item, useless first argument is an already loaded item instance.
     *
     * @return string|null
     */
    public function getItemName($item, ?int $id = null): ?string
    {
        if (is_a($item, CommonDropdown::class, true)) {
            $items_id = $item instanceof CommonDBTM ? $item->fields[$item->getIndexName()] : $id;
            $name = Dropdown::getDropdownName($item::getTable(), $items_id, false, true, false, '');
            return Sanitizer::getVerbatimValue($name);
        }

        if (($instance = $this->getItemInstance($item, $id)) === null) {
            return null;
        }

        return Sanitizer::getVerbatimValue($instance->getFriendlyName());
    }

    /**
     * Returns comment of the given item.
     * In case of a dropdown, it returns the translated comment.
     *
     * @param CommonDBTM|string $item   Item instance of itemtype of the item.
     * @param int|null $id              ID of the item, useless first argument is an already loaded item instance.
     *
     * @return string|null
     */
    public function getItemComment($item, ?int $id = null): ?string
    {
        if (is_a($item, CommonDropdown::class, true)) {
            $items_id = $item instanceof CommonDBTM ? $item->fields[$item->getIndexName()] : $id;
            $texts = Dropdown::getDropdownName($item::getTable(), $items_id, true, true, false, '');
            return Sanitizer::getVerbatimValue($texts['comment']);
        }

        if (($instance = $this->getItemInstance($item, $id)) === null) {
            return null;
        }

        $comment = $instance->isField('comment') ? $instance->fields['comment'] : null;

        return $comment !== null ? Sanitizer::getVerbatimValue($comment) : null;
    }

    /**
     * Returns link of the given item.
     *
     * @param CommonDBTM|string   $item    Item instance of itemtype of the item.
     * @param int|null            $id      ID of the item, useless first argument is an already loaded item instance.
     * @param array               $options Available options:
     *    - enable_anonymization (bool) Enable anonymization of helpdesk actors;
     *
     * @return string|null
     */
    public function getItemLink($item, ?int $id = null, array $options = []): ?string
    {
        if (($instance = $this->getItemInstance($item, $id)) === null) {
            return null;
        }

        if ($instance instanceof Group) {
            return $instance->getGroupLink(true);
        }
        if ($instance instanceof User) {
            return $instance->getUserLink(true);
        }

        return $instance->getLink();
    }

    /**
     * Returns instance of item with given ID.
     *
     * @param CommonDBTM|string $item   Item instance of itemtype of the item.
     * @param int|null $id              ID of the item, useless first argument is an already loaded item instance.
     *
     * @return CommonDBTM|null
     */
    private function getItemInstance($item, ?int $id = null): ?CommonDBTM
    {
        if (!is_a($item, CommonDBTM::class, true)) {
            return null;
        }

        if ($item instanceof CommonDBTM && ($id === null || $item->fields[$item->getIndexName()] === $id)) {
            return $item;
        }

        $instance = $id !== null ? $item::getById($id) : null;
        return $instance ?: null;
    }

    public function getItemtypeForeignKey(string $itemtype): ?string
    {
        if (is_a($itemtype, CommonDBTM::class, true)) {
            return $itemtype::getForeignKeyField();
        }
        return null;
    }
}
