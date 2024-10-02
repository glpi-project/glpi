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

/**
 * NotificationTargetCronTask Class
 **/
class NotificationTargetCronTask extends NotificationTarget
{
    public function getEvents()
    {
        return ['alert' => __('Monitoring of automatic actions')];
    }

    #[Override()]
    public function getEventsToSendImmediately(): array
    {
        return [
            'alert',
        ];
    }

    public function addDataForTemplate($event, $options = [])
    {

        $events                             = $this->getAllEvents();
        $this->data['##crontask.action##'] = $events[$event];

        $cron                               = new CronTask();
        foreach ($options['items'] as $id => $crontask) {
            $tmp                      = [];
            $tmp['##crontask.name##'] = '';

            if ($isplug = isPluginItemType($crontask["itemtype"])) {
                $tmp['##crontask.name##'] = $isplug["plugin"] . " - ";
            }

            $tmp['##crontask.name##']       .= $crontask['name'];
            $tmp['##crontask.description##'] = $cron->getDescription($id);
            $tmp['##crontask.url##']         = $this->formatURL(
                $options['additionnaloption']['usertype'],
                "CronTask_" . $id
            );
            $this->data['crontasks'][] = $tmp;
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

        $tags = ['crontask.action'      => __('Monitoring of automatic actions'),
            'crontask.url'         => __('URL'),
            'crontask.name'        => __('Name'),
            'crontask.description' => __('Description'),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true,
            ]);
        }

        $this->addTagToList(['tag'     => 'crontasks',
            'label'   => __('Automatic actions list'),
            'value'   => false,
            'foreach' => true,
        ]);

        //Tags with just lang
        $tags = ['crontask.warning'
                     => __('The following automatic actions are in error. They require intervention.'),
        ];
        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => false,
                'lang'  => true,
            ]);
        }
        asort($this->tag_descriptions);
    }
}
