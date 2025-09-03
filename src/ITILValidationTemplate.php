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

use Glpi\Features\Clonable;

/**
 * ValidationTemplate Class
 **/
class ITILValidationTemplate extends AbstractITILChildTemplate
{
    use Clonable;

    // From CommonDBTM
    public $dohistory = true;

    public static $rightname = 'itilvalidationtemplate';

    public $can_be_translated = true;

    public static function getTypeName($nb = 0)
    {
        return _n('Approval template', 'Approval templates', $nb);
    }

    public function prepareInputForUpdate($input)
    {
        if (isset($input['validationsteps_id']) && !(new ValidationStep())->getFromDB($input['validationsteps_id'])) {
            Session::addMessageAfterRedirect(
                __s('Invalid approval step'),
                false,
                ERROR
            );
            return [];
        }

        return parent::prepareInputForUpdate($input);
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name' => 'validationsteps_id',
                'label' => ValidationStep::getTypeName(1),
                'type' => 'dropdownValue',
            ],
            [
                'name'  => 'approver',
                'label' => __('Approver'),
                'type'  => '',
                'list'  => true,
            ],
            [
                'name'           => 'content',
                'label'          => __('Content'),
                'type'           => 'tinymce',
                // As content copying from template is not using the image pasting process, these images
                // are not correctly processed. Indeed, document item corresponding to the destination item is not created and
                // image src is not containing the ITIL item foreign key, so image will not be visible for helpdesk profiles.
                // As fixing it is really complex (requires lot of refactoring in images handling, both on JS and PHP side),
                // it is preferable to disable usage of images in templates.
                'disable_images' => true,
            ],
        ];
    }

    /**
     * Display specific "approver" field
     *
     * @param int $id ITILValidationTemplate ID
     */
    private static function displayValidatorField(
        ?int $id,
    ): string {
        $options = [
            'users_id_requester' => Session::getLoginUserID(),
            'itemtype_target'    => null,
            'groups_id'          => null,
            'items_id_target'    => null,
            'right'              => 'validate_request',
            'display'            => false,
        ];

        if ($id > 0) {
            $targets   = ITILValidationTemplate_Target::getTargets($id);
            if (!empty($targets)) {
                $target = current($targets);
                $itemtype = $target['itemtype'];
                $items_ids = array_column($targets, 'items_id');

                $options['itemtype_target'] = $itemtype;
                $options['groups_id'] = $target['groups_id'];
                $options['items_id_target'] = $itemtype == 'Group' && count($items_ids) == 1 ? $items_ids[0] : $items_ids;
            }
        }

        return CommonITILValidation::dropdownValidator($options);
    }

    public function displaySpecificTypeField($id, $field = [], array $options = [])
    {
        if ($field['name'] == 'approver') {
            echo self::displayValidatorField($id);
        }
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '4',
            'name'               => __('Content'),
            'field'              => 'content',
            'table'              => $this->getTable(),
            'datatype'           => 'text',
            'htmltext'           => true,
        ];

        return $tab;
    }

    public static function getIcon()
    {
        return "ti ti-stack-2-filled";
    }

    private function postTargets(): void
    {
        if (isset($this->input['itemtype_target']) && isset($this->input['items_id_target'])) {
            if (!is_array($this->input['items_id_target'])) {
                $this->input['items_id_target'] = [$this->input['items_id_target']];
            }

            $itilValidationTemplatesTarget = new ITILValidationTemplate_Target();
            $itilValidationTemplatesTarget->deleteByCriteria([
                'itilvalidationtemplates_id' => $this->getID(),
            ]);

            foreach ($this->input['items_id_target'] as $user_id) {
                $itilValidationTemplatesTarget->add([
                    'itilvalidationtemplates_id' => $this->getID(),
                    'itemtype'                   => $this->input['itemtype_target'],
                    'items_id'                   => $user_id,
                    'groups_id'                  => $this->input['groups_id'] ?? null,
                ]);
            }
        }
    }

    public function post_addItem($history = 1)
    {
        $this->postTargets();
    }

    public function post_updateItem($history = true)
    {
        $this->postTargets();
    }

    public function getCloneRelations(): array
    {
        return [];
    }
}
