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

namespace Glpi\Api\HL\Search;

use Change;
use CommonITILObject;
use ITILTemplate;
use ITILTemplateField;
use Problem;
use Session;
use Ticket;

/**
 * Translates the search option numbers stored by ITIL template fields (hidden, mandatory, readonly and predefined)
 * to API-friendly field names and back.
 *
 * {@link ITILTemplate::getAllowedFields()} remains the source of truth for which numbers can be used in a template.
 * Names are resolved in this order:
 *  1. The readable name of a template pseudo-field (fields that are not properties of the ITIL schema, like the requester).
 *  2. The schema property mapped to the search option (see {@link SearchOptionMapper}), reduced to its parent object
 *     when it targets a sub-property (e.g. "category.id" becomes "category") as templates apply to the whole field.
 *  3. The legacy field name used by the template.
 */
final class TemplateFieldMapper
{
    public const KINDS = ['Hidden', 'Mandatory', 'Readonly', 'Predefined'];

    /**
     * Readable names of the template pseudo-fields, indexed by the legacy name.
     */
    private const PSEUDO_FIELDS = [
        '_users_id_requester'  => 'requester_user',
        '_groups_id_requester' => 'requester_group',
        '_users_id_assign'     => 'assigned_user',
        '_groups_id_assign'    => 'assigned_group',
        '_suppliers_id_assign' => 'assigned_supplier',
        '_users_id_observer'   => 'observer_user',
        '_groups_id_observer'  => 'observer_group',
        '_add_validation'      => 'approval_request',
        '_documents_id'        => 'document',
        '_tasktemplates_id'    => 'task_template',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private static array $cache = [];

    /**
     * @param class-string<CommonITILObject> $type
     * @param string $kind One of {@link self::KINDS}
     * @return class-string<ITILTemplateField>|null
     */
    public static function getFieldClass(string $type, string $kind): ?string
    {
        if (!is_subclass_of($type, CommonITILObject::class) || !in_array($kind, self::KINDS, true)) {
            return null;
        }
        /** @var class-string<ITILTemplateField> $class */
        $class = $type . 'Template' . $kind . 'Field';
        if (!is_subclass_of($class, ITILTemplateField::class)) {
            return null;
        }
        return $class;
    }

    /**
     * @param class-string<CommonITILObject> $type
     * @return class-string<ITILTemplate>|null The template class or null if the ITIL type is invalid
     */
    public static function getTemplateClass(string $type): ?string
    {
        if (!is_subclass_of($type, CommonITILObject::class)) {
            return null;
        }
        /** @var class-string<ITILTemplate> $class */
        $class = $type . 'Template';
        if (!is_subclass_of($class, ITILTemplate::class)) {
            return null;
        }
        return $class;
    }

    /**
     * @param class-string<CommonITILObject> $type
     * @param string $kind One of {@link self::KINDS}
     * @return array<int, string> API field names indexed by search option number for the fields that can be used for this kind of template field.
     */
    public static function getAllowedFields(string $type, string $kind): array
    {
        $template_class = self::getTemplateClass($type);
        $field_class = self::getFieldClass($type, $kind);
        if ($template_class === null || $field_class === null) {
            return [];
        }

        $cache_key = $type . '|' . $kind . '|' . (Session::getLoginUserID() ?: 0);
        if (!isset(self::$cache[$cache_key])) {
            $excluded = $field_class::getExcludedFields();
            $names = [];
            foreach ($template_class::getAllowedFields(true, true) as $num => $legacy_name) {
                if ($num === -1 || isset($excluded[$num])) {
                    continue;
                }
                $names[(int) $num] = self::resolveName($type, (int) $num, (string) $legacy_name);
            }
            self::$cache[$cache_key] = $names;
        }
        return self::$cache[$cache_key];
    }

    /**
     * @param class-string<CommonITILObject> $type
     * @param string $kind One of {@link self::KINDS}
     * @param int $num The search option number
     * @return string|null The API field name or null if the number is not usable for this kind of template field.
     */
    public static function getNameForNum(string $type, string $kind, int $num): ?string
    {
        return self::getAllowedFields($type, $kind)[$num] ?? null;
    }

    /**
     * @param class-string<CommonITILObject> $type
     * @param string $kind One of {@link self::KINDS}
     * @param string $name The API field name
     * @return int|null The search option number or null if the name is not usable for this kind of template field.
     */
    public static function getNumForName(string $type, string $kind, string $name): ?int
    {
        $num = array_search($name, self::getAllowedFields($type, $kind), true);
        return $num === false ? null : (int) $num;
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }

    private static function resolveName(string $type, int $num, string $legacy_name): string
    {
        if (isset(self::PSEUDO_FIELDS[$legacy_name])) {
            return self::PSEUDO_FIELDS[$legacy_name];
        }
        $path = SearchOptionMapper::getPropertyForOption($type, $num);
        if ($path !== null) {
            $last_dot = strrpos($path, '.');
            return $last_dot === false ? $path : substr($path, 0, $last_dot);
        }
        return $legacy_name;
    }
}
