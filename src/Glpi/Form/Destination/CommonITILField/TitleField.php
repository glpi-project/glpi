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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Form;
use InvalidArgumentException;
use Glpi\Form\Tag\FormTagProvider;
use Glpi\Form\Tag\FormTagsManager;
use Override;

class TitleField extends AbstractConfigField
{
    #[Override]
    public function getLabel(): string
    {
        return __("Title");
    }

    #[Override]
    public function getConfigClass(): string
    {
        return SimpleValueConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof SimpleValueConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // language=Twig
        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.textareaField(
                input_name,
                value,
                '',
                options|merge({
                    'field_class'      : '',
                    'no_label'         : true,
                    'enable_richtext'  : true,
                    'enable_images'    : false,
                    'enable_form_tags' : true,
                    'form_tags_form_id': form_id,
                    'toolbar'          : false,
                    'editor_height'    : 0,
                    'statusbar'        : false,
                    'rand'             : rand,
                    'single_line'      : true,
                })
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'form_id'    => $form->fields['id'],
            'value'      => $config->getValue(),
            'input_name' => $input_name . "[" . SimpleValueConfig::VALUE . "]",
            'options'    => $display_options,
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

        $tag_manager = new FormTagsManager();
        $input['name'] = $tag_manager->insertTagsContent(
            $config->getValue(),
            $answers_set
        );

        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): SimpleValueConfig
    {
        return new SimpleValueConfig((new FormTagProvider())->getTagForForm($form)->html);
    }

    #[Override]
    public function prepareInput(array $input): array
    {
        if (isset($input[$this->getKey()]) && isset($input[$this->getKey()]['value'])) {
            // Remove HTML tags except span with data-form-tag attribute
            $input[$this->getKey()]['value'] = strip_tags($input[$this->getKey()]['value'], '<span>');
        }

        return $input;
    }

    #[Override]
    public function getWeight(): int
    {
        return 10;
    }
}
