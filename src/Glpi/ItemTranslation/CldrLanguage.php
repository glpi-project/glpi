<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\ItemTranslation;

use Gettext\Languages\Language;
use LogicException;

final class CldrLanguage
{
    private Language $language;

    public function __construct(string $lang_identifier)
    {
        $language = Language::getById($lang_identifier);
        if ($language === null) {
            throw new LogicException();
        }

        $this->language = $language;
    }

    /**
     * Get the plural key corresponding to the given number.
     *
     * @param int $number
     * @return string
     */
    final public function getPluralKey(int $number): string
    {
        $formula_to_compute = str_replace('n', (string) $number, $this->language->formula);
        $category_index_number = eval("return $formula_to_compute;");

        if (!\array_key_exists($category_index_number, $this->language->categories)) {
            throw new LogicException();
        }

        return $this->language->categories[$category_index_number]->id;
    }
}
