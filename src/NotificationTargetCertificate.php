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

/**
 * @since 9.2
 */



/**
 * NotificationTargetSoftwareLicense Class
 **/
class NotificationTargetCertificate extends NotificationTarget
{
    public function getEvents()
    {
        return ['alert' => __('Alarm on expired certificate')];
    }

    public function addAdditionalTargets($event = '')
    {
        $this->addTarget(
            Notification::ITEM_TECH_IN_CHARGE,
            __('Technician in charge of the certificate')
        );
        $this->addTarget(
            Notification::ITEM_TECH_GROUP_IN_CHARGE,
            __('Group in charge of the certificate')
        );
    }

    public function addDataForTemplate($event, $options = [])
    {

        $events = $this->getAllEvents();
        $certificate = $this->obj;

        if (!isset($options['certificates'])) {
            $options['certificates'] = [];
            if (!$certificate->isNewItem()) {
                $options['certificates'][] = $certificate->fields;// Compatibility with old behaviour
            }
        } else {
            Toolbox::deprecated('Using "certificates" option in NotificationTargetCertificate is deprecated.');
        }
        if (!isset($options['entities_id'])) {
            $options['entities_id'] = $certificate->fields['entities_id'];
        } else {
            Toolbox::deprecated('Using "entities_id" option in NotificationTargetCertificate is deprecated.');
        }

        $this->data['##certificate.action##'] = $events[$event];
        $this->data['##certificate.entity##'] = Dropdown::getDropdownName(
            'glpi_entities',
            $options['entities_id']
        );

        $this->data['##certificate.name##']           = $certificate->fields['name'];
        $this->data['##certificate.serial##']         = $certificate->fields['serial'];
        $this->data['##certificate.type##'] = Dropdown::getDropdownName(
            'glpi_certificatetypes',
            $certificate->fields['certificatetypes_id']
        );
        $this->data['##certificate.expirationdate##'] = Html::convDate($certificate->fields["date_expiration"]);
        $this->data['##certificate.url##']            = $this->formatURL(
            $options['additionnaloption']['usertype'],
            "Certificate_" . $certificate->getID()
        );

        foreach ($options['certificates'] as $id => $certificate_data) {
            // Old behaviour preserved as notifications rewriting in migrations is kind of complicated
            $this->data['certificates'][] = [
                '##certificate.name##'           => $certificate_data['name'],
                '##certificate.serial##'         => $certificate_data['serial'],
                '##certificate.expirationdate##' => Html::convDate($certificate_data["date_expiration"]),
                '##certificate.url##'            => $this->formatURL(
                    $options['additionnaloption']['usertype'],
                    "Certificate_" . $id
                ),
            ];
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

        $tags = ['certificate.expirationdate' => __('Expiration date'),
            'certificate.name'           => __('Name'),
            'certificate.type'         => _n('Type', 'Types', 1),
            'certificate.serial'         => __('Serial number'),
            'certificate.url'            => __('URL'),
            'certificate.entity'         => Entity::getTypeName(1),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true
            ]);
        }

        $this->addTagToList(['tag'     => 'certificates',
            'label'   => __('Certificates list (deprecated; contains only one element)'),
            'value'   => false,
            'foreach' => true
        ]);

        asort($this->tag_descriptions);
    }
}
