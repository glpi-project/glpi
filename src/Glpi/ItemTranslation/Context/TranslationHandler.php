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

namespace Glpi\ItemTranslation\Context;

use CommonDBTM;

/**
 * Handler for a specific translatable field
 */
final class TranslationHandler
{
    /** @var CommonDBTM The item to translate */
    private CommonDBTM $item;

    /** @var string The key of the field to translate */
    private string $key;

    /** @var string The human-readable name of the field */
    private string $name;

    /** @var string The default value (in the default language) */
    private string $value;

    /** @var bool Whether this field contains rich text that should be edited in a rich text editor */
    private bool $is_rich_text;

    /** @var string|null The category name for grouping translations */
    private ?string $category;

    /**
     * @param CommonDBTM $item The item to translate
     * @param string $key The key of the field to translate
     * @param string $name The human-readable name of the field
     * @param string $value The default value (in the default language)
     * @param bool $is_rich_text Whether this field contains rich text
     * @param string|null $category The category name for grouping translations
     */
    public function __construct(
        CommonDBTM $item,
        string $key,
        string $name,
        string $value,
        bool $is_rich_text = false,
        ?string $category = null
    ) {
        $this->item = $item;
        $this->key = $key;
        $this->name = $name;
        $this->value = $value;
        $this->is_rich_text = $is_rich_text;
        $this->category = $category;
    }

    /**
     * Get the item to translate
     *
     * @return CommonDBTM
     */
    public function getItem(): CommonDBTM
    {
        return $this->item;
    }

    /**
     * Get the key of the field to translate
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get the human-readable name of the field
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the default value (in the default language)
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Check if this field contains rich text that should be edited in a rich text editor
     *
     * @return bool
     */
    public function isRichText(): bool
    {
        return $this->is_rich_text;
    }

    /**
     * Get the category name for grouping translations
     *
     * @return string|null
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }
}
