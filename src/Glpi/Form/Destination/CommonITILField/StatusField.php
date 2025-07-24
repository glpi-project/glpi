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

namespace Glpi\Form\Destination\CommonITILField;

use CommonITILObject;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Form;
use InvalidArgumentException;
use Override;

final class StatusField extends AbstractConfigField
{
    public const DEFAULT_STATUS = 'default_status';

    #[Override]
    public function getLabel(): string
    {
        return __("Status");
    }

    #[Override]
    public function getConfigClass(): string
    {
        return SimpleValueConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof SimpleValueConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $possible_values = [
            self::DEFAULT_STATUS     => __("Default"),
            CommonITILObject::CLOSED => __("Closed"),
        ];

        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.dropdownArrayField(
                input_name,
                value,
                possible_values,
                '',
                options|merge({
                    'field_class'      : '',
                    'no_label'         : true,
                    'mb'               : '',
                })
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'form_id'         => $form->fields['id'],
            'value'           => $config->getValue(),
            'possible_values' => $possible_values,
            'input_name'      => $input_name . "[" . SimpleValueConfig::VALUE . "]",
            'options'         => $display_options,
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof SimpleValueConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        if ($config->getValue() != self::DEFAULT_STATUS) {
            $input['status'] = $config->getValue();
        }

        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): SimpleValueConfig
    {
        return new SimpleValueConfig(self::DEFAULT_STATUS);
    }

    #[Override]
    public function getWeight(): int
    {
        return 50;
    }

    #[Override]
    public function getCategory(): Category
    {
        return Category::PROPERTIES;
    }
}
