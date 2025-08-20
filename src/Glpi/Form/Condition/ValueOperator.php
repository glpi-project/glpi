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

namespace Glpi\Form\Condition;

enum ValueOperator: string
{
    case EQUALS                            = 'equals';
    case NOT_EQUALS                        = 'not_equals';
    case CONTAINS                          = 'contains';
    case NOT_CONTAINS                      = 'not_contains';
    case GREATER_THAN                      = 'greater_than';
    case GREATER_THAN_OR_EQUALS            = 'greater_than_or_equals';
    case LESS_THAN                         = 'less_than';
    case LESS_THAN_OR_EQUALS               = 'less_than_or_equals';
    case IS_ITEMTYPE                       = 'is_itemtype';
    case IS_NOT_ITEMTYPE                   = 'is_not_itemtype';
    case AT_LEAST_ONE_ITEM_OF_ITEMTYPE     = 'at_least_one_item_of_itemtype';
    case ALL_ITEMS_OF_ITEMTYPE             = 'all_items_of_itemtype';
    case MATCH_REGEX = 'match_regex';
    case NOT_MATCH_REGEX = 'not_match_regex';

    case LENGTH_GREATER_THAN               = 'length_greater_than';
    case LENGTH_GREATER_THAN_OR_EQUALS     = 'length_greater_than_or_equals';
    case LENGTH_LESS_THAN                  = 'length_less_than';
    case LENGTH_LESS_THAN_OR_EQUALS        = 'length_less_than_or_equals';

    case VISIBLE = 'visible';
    case NOT_VISIBLE = 'not_visible';

    case EMPTY = 'empty';
    case NOT_EMPTY = 'not_empty';

    public function getLabel(): string
    {
        return match ($this) {
            self::EQUALS                            => __("Is equal to"),
            self::NOT_EQUALS                        => __("Is not equal to"),
            self::CONTAINS                          => __("Contains"),
            self::NOT_CONTAINS                      => __("Do not contains"),
            self::GREATER_THAN                      => __("Is greater than"),
            self::GREATER_THAN_OR_EQUALS            => __("Is greater than or equals to"),
            self::LESS_THAN                         => __("Is less than"),
            self::LESS_THAN_OR_EQUALS               => __("Is less than or equals to"),
            self::IS_ITEMTYPE                       => __("Is of itemtype"),
            self::IS_NOT_ITEMTYPE                   => __("Is not of itemtype"),
            self::AT_LEAST_ONE_ITEM_OF_ITEMTYPE     => __("At least one item of itemtype"),
            self::ALL_ITEMS_OF_ITEMTYPE             => __("All items of itemtype"),
            self::MATCH_REGEX            => __("Match regular expression"),
            self::NOT_MATCH_REGEX        => __("Do not match regular expression"),

            self::LENGTH_GREATER_THAN               => __("Length is greater than"),
            self::LENGTH_GREATER_THAN_OR_EQUALS     => __("Length is greater than or equals to"),
            self::LENGTH_LESS_THAN                  => __("Length is less than"),
            self::LENGTH_LESS_THAN_OR_EQUALS        => __("Length is less than or equals to"),

            self::VISIBLE                => __("Is visible"),
            self::NOT_VISIBLE            => __("Is not visible"),

            self::EMPTY                 => __("Is empty"),
            self::NOT_EMPTY             => __("Is not empty"),
        };
    }

    public function canBeUsedForValidation(): bool
    {
        return match ($this) {
            self::GREATER_THAN,
            self::GREATER_THAN_OR_EQUALS,
            self::LESS_THAN,
            self::LESS_THAN_OR_EQUALS,
            self::MATCH_REGEX,
            self::NOT_MATCH_REGEX,
            self::LENGTH_GREATER_THAN,
            self::LENGTH_GREATER_THAN_OR_EQUALS,
            self::LENGTH_LESS_THAN,
            self::LENGTH_LESS_THAN_OR_EQUALS => true,

            default => false
        };
    }

    public function getErrorMessageForValidation(
        ValidationStrategy $validation_strategy,
        ConditionData $condition_data
    ): string {
        if ($validation_strategy === ValidationStrategy::VALID_IF) {
            return match ($this) {
                self::GREATER_THAN           => sprintf(__("The value must be greater than %s"), $condition_data->getValue()),
                self::GREATER_THAN_OR_EQUALS => sprintf(__("The value must be greater than or equal to %s"), $condition_data->getValue()),
                self::LESS_THAN              => sprintf(__("The value must be less than %s"), $condition_data->getValue()),
                self::LESS_THAN_OR_EQUALS    => sprintf(__("The value must be less than or equal to %s"), $condition_data->getValue()),
                self::MATCH_REGEX            => __("The value must match the requested format"),
                self::NOT_MATCH_REGEX        => __("The value must not match the requested format"),
                self::LENGTH_GREATER_THAN               => sprintf(__("The length must be greater than %s"), $condition_data->getValue()),
                self::LENGTH_GREATER_THAN_OR_EQUALS     => sprintf(__("The length must be greater than or equal to %s"), $condition_data->getValue()),
                self::LENGTH_LESS_THAN                  => sprintf(__("The length must be less than %s"), $condition_data->getValue()),
                self::LENGTH_LESS_THAN_OR_EQUALS        => sprintf(__("The length must be less than or equal to %s"), $condition_data->getValue()),

                default => __("The value is not valid"),
            };
        } elseif ($validation_strategy === ValidationStrategy::INVALID_IF) {
            return match ($this) {
                self::GREATER_THAN           => sprintf(__("The value must not be greater than %s"), $condition_data->getValue()),
                self::GREATER_THAN_OR_EQUALS => sprintf(__("The value must not be greater than or equal to %s"), $condition_data->getValue()),
                self::LESS_THAN              => sprintf(__("The value must not be less than %s"), $condition_data->getValue()),
                self::LESS_THAN_OR_EQUALS    => sprintf(__("The value must not be less than or equal to %s"), $condition_data->getValue()),
                self::MATCH_REGEX            => __("The value must not match the requested format"),
                self::NOT_MATCH_REGEX        => __("The value must match the requested format"),
                self::LENGTH_GREATER_THAN               => sprintf(__("The length must not be greater than %s"), $condition_data->getValue()),
                self::LENGTH_GREATER_THAN_OR_EQUALS     => sprintf(__("The length must not be greater than or equal to %s"), $condition_data->getValue()),
                self::LENGTH_LESS_THAN                  => sprintf(__("The length must not be less than %s"), $condition_data->getValue()),
                self::LENGTH_LESS_THAN_OR_EQUALS        => sprintf(__("The length must not be less than or equal to %s"), $condition_data->getValue()),

                default => __("The value is not valid"),
            };
        }

        return __("The value is not valid");
    }
}
