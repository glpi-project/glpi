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

namespace Glpi\Form\QuestionType;

use CommonItilObject_Item;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Question;
use Override;
use Session;

final class QuestionTypeUserDevice extends AbstractQuestionType
{
    #[Override]
    public function validateExtraDataInput(array $input): bool
    {
        // Only one key is allowed and optional: 'is_multiple_devices'.
        // This key must be a valid boolean.
        return (
            isset($input['is_multiple_devices'])
            && count($input) === 1
            && filter_var($input['is_multiple_devices'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null
        ) || empty($input);
    }

    /**
     * Check if the question allows multiple devices
     *
     * @param ?Question $question
     * @return bool
     */
    public function isMultipleDevices(?Question $question): bool
    {
        /** @var ?QuestionTypeUserDevicesConfig $config */
        $config = $this->getConfig($question);
        if ($config === null) {
            return false;
        }

        return $config->isMultipleDevices();
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question): string
    {
        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.dropdownArrayField(
                'default_value',
                '',
                [],
                '',
                {
                    'init'               : init,
                    'no_label'           : true,
                    'field_class'        : [
                        'col-12',
                        'devices-dropdown',
                        is_multiple_devices ? '' : 'd-none'
                    ]|join(' '),
                    'multiple'           : true,
                    'disabled'           : true,
                    'aria_label'         : aria_label_multiple_devices,
                }
            ) }}

            {{ fields.dropdownArrayField(
                'default_value',
                '',
                [],
                '',
                {
                    'init'               : init,
                    'no_label'           : true,
                    'field_class'        : [
                        'col-12',
                        'devices-dropdown',
                        is_multiple_devices ? 'd-none' : ''
                    ]|join(' '),
                    'display_emptychoice': true,
                    'disabled'           : true,
                    'aria_label'         : aria_label_single_device,
                }
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'init'                => $question !== null,
            'is_multiple_devices' => $this->isMultipleDevices($question),
            'aria_label_multiple_devices' => _n('Select device...', 'Select devices...', 2),
            'aria_label_single_device' => _n('Select device...', 'Select devices...', 1)
        ]);
    }

    #[Override]
    public function renderAdministrationOptionsTemplate(?Question $question): string
    {
        $template = <<<TWIG
            {% set rand = random() %}

            <div id="is_multiple_devices_{{ rand }}" class="d-flex gap-2">
                <label class="form-check form-switch mb-0">
                    <input type="hidden" name="is_multiple_devices" value="0"
                    data-glpi-form-editor-specific-question-extra-data>
                    <input class="form-check-input" type="checkbox" name="is_multiple_devices"
                        value="1" {{ is_multiple_devices ? 'checked' : '' }}
                        onchange="handleMultipleDevicesCheckbox_{{ rand }}(this)"
                        data-glpi-form-editor-specific-question-extra-data>
                    <span class="form-check-label">{{ is_multiple_devices_label }}</span>
                </label>
            </div>

            <script>
                function handleMultipleDevicesCheckbox_{{ rand }}(input) {
                    const is_checked = $(input).is(':checked');
                    const selects = $(input).closest('section[data-glpi-form-editor-question]')
                        .find('div .devices-dropdown');

                    {# Toggle all selects visibility #}
                    selects.toggleClass('d-none');

                    {# Handle hidden input for multiple devices #}
                    selects.find('input[type="hidden"]').prop('disabled', !is_checked);
                }
            </script>
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'is_multiple_devices' => $this->isMultipleDevices($question),
            'is_multiple_devices_label' => __('Allow multiple devices')
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(Question $question): string
    {
        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.dropdownArrayField(
                question.getEndUserInputName(),
                '',
                items,
                '',
                {
                    'no_label'           : true,
                    'field_class'        : 'col-12',
                    'display_emptychoice': true,
                    'multiple'           : is_multiple_devices,
                    'aria_label'         : aria_label,
                }
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question' => $question,
            'items'    => CommonItilObject_Item::getMyDevices(Session::getLoginUserID(), Session::getActiveEntities()),
            'is_multiple_devices' => $this->isMultipleDevices($question),
            'aria_label' => _n('Select device...', 'Select devices...', $this->isMultipleDevices($question) ? 2 : 1),
        ]);
    }

    #[Override]
    public function prepareEndUserAnswer(Question $question, mixed $answer): mixed
    {
        if (!is_array($answer)) {
            $answer = [$answer];
        }

        $devices = [];
        if (is_array($answer)) {
            foreach ($answer as $device) {
                $device_parts = [];
                if (
                    preg_match('/^(?<itemtype>.+)_(?<id>\d+)$/', $device, $device_parts) !== 1
                    || !is_a($device_parts['itemtype'], \CommonDBTM::class, true)
                    || $device_parts['itemtype']::getById($device_parts['id']) === false
                ) {
                    continue;
                }

                $devices[] = [
                    'itemtype' => $device_parts['itemtype'],
                    'items_id' => $device_parts['id']
                ];
            }
        }

        return $devices;
    }

    #[Override]
    public function formatRawAnswer(mixed $answer): string
    {
        if (is_string($answer)) {
            $answer = [$answer];
        }

        $formatted_devices = [];
        foreach ($answer as $device) {
            $item = $device['itemtype']::getById($device['items_id']);
            if ($item !== false) {
                $formatted_devices[] = $item->getName();
            }
        }

        return implode(', ', $formatted_devices);
    }

    #[Override]
    public function getName(): string
    {
        return _n('User Device', 'User Devices', Session::getPluralNumber());
    }

    #[Override]
    public function getIcon(): string
    {
        return 'ti ti-devices';
    }

    #[Override]
    public function getCategory(): QuestionTypeCategory
    {
        return QuestionTypeCategory::ITEM;
    }

    #[Override]
    public function isAllowedForUnauthenticatedAccess(): bool
    {
        return false;
    }

    #[Override]
    public function getConfigClass(): ?string
    {
        return QuestionTypeUserDevicesConfig::class;
    }
}
