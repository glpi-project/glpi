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

namespace Glpi\Form\Condition\ConditionHandler;

use Glpi\Form\Condition\ValueOperator;
use Override;

class StringConditionHandler implements ConditionHandlerInterface
{
    #[Override]
    public function getSupportedValueOperators(): array
    {
        return [
            ValueOperator::EQUALS,
            ValueOperator::NOT_EQUALS,
            ValueOperator::CONTAINS,
            ValueOperator::NOT_CONTAINS,
        ];
    }

    #[Override]
    public function applyValueOperator(
        mixed $a,
        ValueOperator $operator,
        mixed $b,
    ): bool {
        // Normalize strings.
        $a = strtolower(strval($a));
        $b = strtolower(strval($b));

        return match ($operator) {
            ValueOperator::EQUALS       => $a === $b,
            ValueOperator::NOT_EQUALS   => $a !== $b,
            ValueOperator::CONTAINS     => str_contains($b, $a),
            ValueOperator::NOT_CONTAINS => !str_contains($b, $a),

            // Unsupported operators
            default => false,
        };
    }

    /**
     * Render the default input template based on the template key.
     * This provides a fallback implementation.
     *
     * @param string $name Input name
     * @param mixed $value Current value
     * @param array $options Additional options for the template
     * @return string HTML content
     */
    #[Override]
    public function renderInputTemplate(string $name, mixed $value, array $options = []): string
    {
        $placeholder        = $options['placeholder'] ?? __("Enter a value...");
        $label              = $options['label'] ?? __("Value");
        $additional_classes = $options['class'] ?? "";

        $html_attributes = array_merge([
            'type'                              => $this->getInputType(),
            'class'                             => "me-2 form-control value-selector flex-grow-1 {$additional_classes}",
            'value'                             => $value,
            'name'                              => $name,
            'placeholder'                       => $placeholder,
            'aria-label'                        => $label,
            'data-glpi-conditions-editor-value' => '',
        ], $this->getInputAdditionalHTMLAttributes());

        // Generate HTML attributes string
        $attributes_str = '';
        foreach ($html_attributes as $attr => $attr_value) {
            $attributes_str .= ' ' . $attr . '="' . htmlspecialchars($attr_value) . '"';
        }

        return "<input{$attributes_str} />";
    }

    public function getInputType(): string
    {
        return "text";
    }

    /**
     * Get additional HTML attributes for the input field.
     *
     * @return array<string, string> Additional HTML attributes
     */
    public function getInputAdditionalHTMLAttributes(): array
    {
        return [];
    }
}
