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

/**
 * NotificationTargetMailCollector Class
 *
 * @since 0.85
 **/
class NotificationTargetMailCollector extends NotificationTarget
{
    public function getEvents()
    {
        return ['error' => __('Receiver errors')];
    }


    public function addDataForTemplate($event, $options = [])
    {

        $events                                  = $this->getEvents();
        $this->data['##mailcollector.action##'] = $events[$event];

        foreach ($options['items'] as $id => $mailcollector) {
            $tmp                             = [];
            $tmp['##mailcollector.name##']   = $mailcollector['name'];
            $tmp['##mailcollector.errors##'] = $mailcollector['errors'];
            $tmp['##mailcollector.url##']    = $this->formatURL(
                $options['additionnaloption']['usertype'],
                "MailCollector_" . $id
            );
            $this->data['mailcollectors'][] = $tmp;
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

        $tags = ['mailcollector.action' => _n('Event', 'Events', 1),
            'mailcollector.name'   => __('Name'),
            'mailcollector.errors' => __('Connection errors')
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true
            ]);
        }

        $tags = ['mailcollector.url' => sprintf(
            __('%1$s: %2$s'),
            _n('Receiver', 'Receivers', 1),
            __('URL')
        )
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true,
                'lang'  => false
            ]);
        }

       //Foreach global tags
        $tags = ['mailcollectors' => _n('Receiver', 'Receivers', Session::getPluralNumber())];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'     => $tag,
                'label'   => $label,
                'value'   => false,
                'foreach' => true
            ]);
        }

        asort($this->tag_descriptions);
        return $this->tag_descriptions;
    }
}
