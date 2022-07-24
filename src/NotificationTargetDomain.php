<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

class NotificationTargetDomain extends NotificationTarget
{
    public function getEvents()
    {
        return [
            'ExpiredDomains'     => __('Expired domains'),
            'DomainsWhichExpire' => __('Expiring domains')
        ];
    }

    public function addAdditionalTargets($event = '')
    {
        $this->addTarget(
            Notification::ITEM_TECH_IN_CHARGE,
            __('Technician in charge of the domain')
        );
        $this->addTarget(
            Notification::ITEM_TECH_GROUP_IN_CHARGE,
            __('Group in charge of the domain')
        );
    }

    public function addDataForTemplate($event, $options = [])
    {
        $domain = $this->obj;

        if (!isset($options['domains'])) {
            $options['domains'] = [];
            if (!$domain->isNewItem()) {
                $options['domains'][] = $domain->fields;// Compatibility with old behaviour
            }
        } else {
            Toolbox::deprecated('Using "domains" option in NotificationTargetDomain is deprecated.');
        }
        if (!isset($options['entities_id'])) {
            $options['entities_id'] = $domain->fields['entities_id'];
        } else {
            Toolbox::deprecated('Using "entities_id" option in NotificationTargetDomain is deprecated.');
        }

        $this->data['##domain.entity##']      = Dropdown::getDropdownName('glpi_entities', $options['entities_id']);
        $this->data['##lang.domain.entity##'] = Entity::getTypeName(1);
        $this->data['##domain.action##']      = ($event == "ExpiredDomains" ? __('Expired domains') : __('Expiring domains'));
        $this->data['##lang.domain.name##']           = __('Name');
        $this->data['##lang.domain.dateexpiration##'] = __('Expiration date');

        $this->data['##domain.name##']           = $domain->fields['name'];
        $this->data['##domain.dateexpiration##'] = Html::convDate($domain->fields['date_expiration']);

        foreach ($options['domains'] as $domain_data) {
            // Old behaviour preserved as notifications rewriting in migrations is kind of complicated
            $this->data['domains'][] = [
                '##domain.name##'             => $domain_data['name'],
                '##domain.dateexpiration##'   => Html::convDate($domain_data['date_expiration'])
            ];
        }
    }

    public function getTags()
    {
        $tags = [
            'domain.name'           => __('Name'),
            'domain.dateexpiration' => __('Expiration date')
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList([
                'tag'   => $tag,
                'label' => $label,
                'value' => true
            ]);
        }

        $this->addTagToList([
            'tag'     => 'domains',
            'label'   => __('Expired or expiring domains (deprecated; contains only one element)'),
            'value'   => false,
            'foreach' => true,
            'events'  => ['DomainsWhichExpire', 'ExpiredDomains']
        ]);

        asort($this->tag_descriptions);
    }
}
