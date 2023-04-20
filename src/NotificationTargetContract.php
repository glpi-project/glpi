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
 * NotificationTargetContract Class
 **/
class NotificationTargetContract extends NotificationTarget
{
    public function getEvents()
    {

        return ['end'               => __('End of contract'),
            'notice'            => __('Notice'),
            'periodicity'       => __('Periodicity'),
            'periodicitynotice' => __('Periodicity notice')
        ];
    }


    public function addDataForTemplate($event, $options = [])
    {
        $this->data['##contract.entity##'] = Dropdown::getDropdownName(
            'glpi_entities',
            $options['entities_id']
        );
        $events                             = $this->getEvents();
        $this->data['##contract.action##'] = sprintf(
            __('%1$s - %2$s'),
            __('Contracts alarm'),
            $events[$event]
        );

        foreach ($options['items'] as $id => $contract) {
            $tmp                        = [];
            $tmp['##contract.name##']   = $contract['name'];
            $tmp['##contract.number##'] = $contract['num'];
            $tmp['##contract.comment##'] = $contract['comment'];
            $tmp['##contract.account##'] = $contract['accounting_number'];

            if ($contract['contracttypes_id']) {
                $tmp['##contract.type##'] = Dropdown::getDropdownName(
                    'glpi_contracttypes',
                    $contract['contracttypes_id']
                );
            } else {
                $tmp['##contract.type##'] = "";
            }

            if ($contract['states_id']) {
                $tmp['##contract.states##'] = Dropdown::getDropdownName(
                    'glpi_states',
                    $contract['states_id']
                );
            } else {
                $tmp['##contract.states##'] = "";
            }

            switch ($event) {
                case 'end':
                    $tmp['##contract.time##'] = Infocom::getWarrantyExpir(
                        $contract["begin_date"],
                        $contract["duration"]
                    );
                    break;

                case 'notice':
                    $tmp['##contract.time##'] = Infocom::getWarrantyExpir(
                        $contract["begin_date"],
                        $contract["duration"],
                        $contract["notice"]
                    );
                    break;

                case 'periodicity':
                case 'periodicitynotice':
                    if (isset($contract["alert_date"])) {
                         $tmp['##contract.time##'] =  Html::convDate($contract["alert_date"]);
                    } else if (isset($options['_debug'])) {
                          $tmp['##contract.time##'] =  Html::convDate($_SESSION['glpi_currenttime']);
                    }
                    break;
            }

            $tmp['##contract.url##']          = $this->formatURL(
                $options['additionnaloption']['usertype'],
                "Contract_" . $id
            );
            $tmp['##contract.items.number##'] = 0;
            $tmp['##contract.items##']        = '';
            if (isset($contract['items']) && count($contract['items'])) {
                 $toadd = [];
                foreach ($contract['items'] as $itemtype => $item) {
                    if ($type = getItemForItemtype($itemtype)) {
                         $typename = $type->getTypeName();
                        foreach ($item as $item_data) {
                            $toadd[] = sprintf(__('%1$s - %2$s'), $typename, $item_data['name']);
                            $tmp['##contract.items.number##']++;
                        }
                    }
                }
                if (count($toadd)) {
                    $tmp["##contract.items##"] = implode(', ', $toadd);
                }
            }

            $this->data['contracts'][] = $tmp;
        }

        switch ($event) {
            case 'end':
                $this->data['##lang.contract.time##'] = __('Contract expired since the');
                break;

            case 'notice':
                $this->data['##lang.contract.time##'] =  __('Contract with notice since the');
                break;

            case 'periodicity':
                $this->data['##lang.contract.time##']
                        =  __('Contract reached the end of a period since the');
                break;

            case 'periodicitynotice':
                $this->data['##lang.contract.time##']
                        =  __('Contract with notice for the current period since the');
                break;
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

        $tags = ['contract.action'       => _n('Event', 'Events', 1),
            'contract.name'         => __('Name'),
            'contract.number'       => _x('phone', 'Number'),
            'contract.comment'      => __('Comments'),
            'contract.account'      => __('Account number'),
            'contract.items.number' => _x('quantity', 'Number of items'),
            'contract.items'        => __('Device list'),
            'contract.type'         => _n('Type', 'Types', 1),
            'contract.entity'       => Entity::getTypeName(1),
            'contract.states'       => __('Status'),
            'contract.time'         => sprintf(
                __('%1$s / %2$s'),
                __('Contract expired since the'),
                __('Contract with notice since the')
            )
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'   => $tag,
                'label' => $label,
                'value' => true
            ]);
        }

       //Tags without lang
        $tags = ['contract.url' => sprintf(
            __('%1$s: %2$s'),
            _n('Contract', 'Contracts', 1),
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
        $tags = ['contracts' => _n('Contract', 'Contracts', Session::getPluralNumber())];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(['tag'     => $tag,
                'label'   => $label,
                'value'   => false,
                'foreach' => true
            ]);
        }

        asort($this->tag_descriptions);
    }
}
