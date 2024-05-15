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

namespace Glpi\Form\Tag;

final class FormTagsManager
{
    public function getTags(): array
    {
        return [
            new Tag(label: "Exemple tag 1", value: "exemple-tag-1"),
            new Tag(label: "Exemple tag 2", value: "exemple-tag-2"),
            new Tag(label: "Exemple tag 3", value: "exemple-tag-3"),
        ];
    }

    public function insertTagsContent(string $content): string
    {
        return preg_replace_callback(
            '/<span[\S\s]*?data-form-tag[\S\s]*?>[\S\s]*?<\/span>/m',
            function ($match) {
                $tag = $match[0];

                // Extract  value.
                preg_match('/data-form-tag-value="([^"]+)"/', $tag, $value_match);
                if (empty($value_match)) {
                    return "";
                }

                // For now, we return the raw value.
                // In futures improvements, the value will be an id and we will
                // need to call some kind of object here to transform the id into
                // the right string value (for exemple, transforming a question
                // id into its name).
                return $value_match[1];
            },
            $content
        );
    }
}
