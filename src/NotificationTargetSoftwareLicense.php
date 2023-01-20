<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

/**
 * NotificationTargetSoftwareLicense Class
 **/
class NotificationTargetSoftwareLicense extends NotificationTarget
{
    public function getEvents()
    {
        return ['alert' => __('Alarm on expired license')];
    }


    public function addDataForTemplate($event, $options = [])
    {

        $events                            = $this->getAllEvents();
        $license = $this->obj;

        if (!isset($options['licenses'])) {
            $options['licenses'] = [];
            if (!$license->isNewItem()) {
                $options['licenses'][] = $license->fields;// Compatibility with old behaviour
            }
        } else {
            Toolbox::deprecated('Using "licenses" option in NotificationTargetSoftwareLicense is deprecated.');
        }
        if (!isset($options['entities_id'])) {
            $options['entities_id'] = $license->fields['entities_id'];
        } else {
            Toolbox::deprecated('Using "entities_id" option in NotificationTargetSoftwareLicense is deprecated.');
        }

        $this->data['##license.action##'] = $events[$event];

        $this->data['##license.entity##'] = Dropdown::getDropdownName(
            'glpi_entities',
            $options['entities_id']
        );
        $tmp                       = [];
        $tmp['##license.item##']   = $license->fields['softname'];
        $tmp['##license.name##']   = $license->fields['name'];
        $tmp['##license.serial##'] = $license->fields['serial'];
        $tmp['##license.expirationdate##']
            = Html::convDate($license->fields["expire"]);
        $tmp['##license.url##']    = $this->formatURL(
            $options['additionnaloption']['usertype'],
            "SoftwareLicense_" . $license->getID()
        );
        $tmp['##license.entity##'] = Dropdown::getDropdownName(
            'glpi_entities',
            $license->fields['entities_id']
        );
        $this->data['licenses'][] = $tmp;

        foreach ($options['licenses'] as $id => $license) {
            $tmp                       = [];
            $tmp['##license.item##']   = $license['softname'];
            $tmp['##license.name##']   = $license['name'];
            $tmp['##license.serial##'] = $license['serial'];
            $tmp['##license.expirationdate##']
                                    = Html::convDate($license["expire"]);
            $tmp['##license.url##']    = $this->formatURL(
                $options['additionnaloption']['usertype'],
                "SoftwareLicense_" . $id
            );
            $tmp['##license.entity##'] = Dropdown::getDropdownName(
                'glpi_entities',
                $license['entities_id']
            );
            $this->data['licenses'][] = $tmp;
        }

        $this->getTags();
        foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
            if (!isset($this->data[$tag])) {
                $this->data[$tag] = $values['label'];
            }
        }
    }


    public function getTags()
    {

        $tags = ['license.expirationdate' => __('Expiration date'),
            'license.item'           => _n('Software', 'Software', 1),
            'license.name'           => __('Name'),
            'license.serial'         => __('Serial number'),
            'license.entity'         => Entity::getTypeName(1),
            'license.url'            => __('URL'),
            'license.action'         => _n('Event', 'Events', 1)
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true
            ]);
        }

        $this->addTagToList(['tag'     => 'licenses',
            'label'   => __('Licenses list (deprecated; contains only one element)'),
            'value'   => false,
            'foreach' => true
        ]);

        asort($this->tag_descriptions);
    }

    public function addAdditionalTargets($event = '')
    {
        $this->addTarget(
            Notification::ITEM_TECH_IN_CHARGE,
            __('Technician in charge of the software license')
        );
        $this->addTarget(
            Notification::ITEM_TECH_GROUP_IN_CHARGE,
            __('Group in charge of the software license')
        );
    }
}
