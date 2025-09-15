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

namespace Glpi\Dashboard;

use CommonDBTM;
use CommonDevice;
use CommonITILObject;
use Group;
use InvalidArgumentException;
use Session;
use Ticket;
use User;

use function Safe\strtotime;

final class FakeProvider extends Provider
{
    private static function getObscureNumberForString(string $string, int $max = 100): int
    {
        // xxh3 should be the fastest hashing algorithm in PHP as of 8.1
        return (int) abs((int) hexdec(hash('xxh3', $string)) % $max);
    }

    /**
     * @return string[]
     */
    private static function getFakeNames(): array
    {
        $names = [
            ['Mercedes', 'Faulkner'],
            ['German', 'Bond'],
            ['Heidi', 'Bright'],
            ['Theron', 'Mcmillan'],
            ['Shane', 'May'],
            ['Dolores', 'Greene'],
            ['Marcelino', 'Rice'],
            ['Gracie', 'Boyd'],
            ['Zachariah', 'Ellis'],
            ['Rena', 'Velez'],
        ];
        return array_map(static fn($name) => formatUserName(0, '', $name[1], $name[0]), $names);
    }

    /**
     * @param string|null $itemtype
     * @return integer|array|null
     */
    private static function getItemCount(?string $itemtype = null)
    {
        global $CFG_GLPI;

        $values = [
            'Software' => 114700,
            'Computer' => 5400,
            'NetworkEquipment' => 1200,
            'Phone' => 1500,
            'SoftwareLicense' => 130,
            'Monitor' => 3800,
            'Rack' => 12,
            'Printer' => 1350,
            'User' => 4225,
            'Group' => 129,
            'Supplier' => 56,
            'Document' => 37950,
            'Entity' => 67,
            'Profile' => 15,
            'KnowledgebaseItem' => 261,
            'Project' => 7,
            'Manufacturer' => 12,
            'Location' => 12,
        ];

        foreach (CommonDevice::getDeviceTypes() as $device_type) {
            $item_device_type = $device_type::getItem_DeviceType();

            // Generate an obscure, but static number for the items and device types.
            $values[$item_device_type] = self::getObscureNumberForString($item_device_type, 7500);
            $values[$device_type] = self::getObscureNumberForString($device_type, 20);
        }
        foreach ($CFG_GLPI['asset_types'] as $type) {
            if (class_exists($type . 'Model')) {
                $values[$type . 'Model'] = self::getObscureNumberForString($type . 'Model', 25);
            }
            if (class_exists($type . 'Type')) {
                $values[$type . 'Type'] = self::getObscureNumberForString($type . 'Type', 10);
            }
        }

        if ($itemtype === null) {
            return $values;
        }
        return $values[$itemtype] ?? null;
    }

    public static function bigNumberItem(?CommonDBTM $item = null, array $params = []): array
    {
        return [
            'number' => self::getItemCount($item::class) ?? 1500,
            'url'    => '#',
            'label'  => $item::getTypeName(Session::getPluralNumber()),
            'icon'   => $item::getIcon(),
        ];
    }

    public static function getTicketSummary(array $params = [])
    {
        return [
            'data'  => [
                [
                    'number' => 240,
                    'label'  => __("New"),
                    'url'    => '#',
                    'color'  => '#3bc519',
                ], [
                    'number' => 308,
                    'label'  => __("Assigned"),
                    'url'    => '#',
                    'color'  => '#f1cd29',
                ], [
                    'number' => 67,
                    'label'  => __("Pending"),
                    'url'    => '#',
                    'color'  => '#f1a129',
                ], [
                    'number' => 31,
                    'label'  => __("To approve"),
                    'url'    => '#',
                    'color'  => '#266ae9',
                ], [
                    'number' => 78,
                    'label'  => __("Solved"),
                    'url'    => '#',
                    'color'  => '#edc949',
                ], [
                    'number' => 14550,
                    'label'  => __("Closed"),
                    'url'    => '#',
                    'color'  => '#555555',
                ],
            ],
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }

    public static function nbTicketsGeneric(string $case = "", array $params = []): array
    {
        $number = match ($case) {
            'notold' => 646,
            'late' => 2,
            'waiting_validation' => 31,
            'incoming' => 240,
            'waiting' => 67,
            'assigned' => 308,
            'planned' => 4,
            'solved' => 78,
            'closed' => 14550,
            default => throw new InvalidArgumentException("Invalid case: $case"),
        };

        $label = match ($case) {
            'notold' => _x('status', 'Not solved'),
            'late' => __("Late tickets"),
            'waiting_validation' => __("Tickets waiting for approval"),
            'incoming' => __("Incoming tickets"),
            'waiting' => __("Pending tickets"),
            'assigned' => __("Assigned tickets"),
            'planned' => __("Planned tickets"),
            'solved' => __("Solved tickets"),
            'closed' => __("Closed tickets"),
            default => throw new InvalidArgumentException("Invalid case: $case"),
        };

        $icon = Ticket::getStatusIcon($case);

        return [
            'number'     => $number,
            'url'        => '#',
            'label'      => $label,
            'icon'       => $icon,
            's_criteria' => [],
            'itemtype'   => 'Ticket',
        ];
    }

    public static function nbTicketsByAgreementStatusAndTechnician(array $params = []): array
    {
        return [
            'label' => __('Tickets by SLA status and by technician'),
            'data' => [
                'series' => [
                    [
                        'name' => __('Late own and resolve'),
                        'data' => [1, 6, 0, 0, 2, 3, 0, 0, 0, 0],
                    ],
                    [
                        'name' => __('Late resolve'),
                        'data' => [3, 6, 0, 0, 3, 3, 0, 0, 0, 0],
                    ],
                    [
                        'name' => __('Late own'),
                        'data' => [1, 6, 0, 0, 2, 3, 0, 0, 0, 0],
                    ],
                    [
                        'name' => __('On time'), //406 not new, solved or closed
                        'data' => [42, 44, 21, 37, 47, 42, 43, 46, 42, 41],
                    ],
                ],
                'labels' => self::getFakeNames(),
            ],
            'icon' => 'ti ti-stopwatch',
        ];
    }

    public static function nbTicketsByAgreementStatusAndTechnicianGroup(array $params = []): array
    {
        return [
            'label' => __('Tickets by SLA status and by technician group'),
            'data' => [
                'series' => [
                    [
                        'name' => __('Late own and resolve'),
                        'data' => [0, 1, 3, 2, 5],
                    ],
                    [
                        'name' => __('Late resolve'),
                        'data' => [0, 1, 3, 2, 5],
                    ],
                    [
                        'name' => __('Late own'),
                        'data' => [0, 1, 3, 2, 5],
                    ],
                    [
                        'name' => __('On time'),
                        'data' => [12, 22, 34, 65, 102],
                    ],
                ],
                'labels' => [
                    _x('fake_data', 'Security team'),
                    _x('fake_data', 'Network team'),
                    _x('fake_data', 'Software team'),
                    _x('fake_data', 'Hardware team'),
                    _x('fake_data', 'Helpdesk team'),
                ],
            ],
            'icon' => 'ti ti-stopwatch',
        ];
    }

    public static function nbItemByFk(?CommonDBTM $item = null, ?CommonDBTM $fk_item = null, array $params = []): array
    {
        $item_counts = self::getItemCount();
        $number_fk = $item_counts[$fk_item::class] ?? 20;
        $number = $item_counts[$item::class] ?? 1500;

        $fk_item_index = array_keys($item_counts, $fk_item::class)[0] ?? 1;

        $data = [];
        for ($i = 0; $i < $number_fk; $i++) {
            $nb = self::getObscureNumberForString($number . $fk_item_index . $i, $number);
            // Reduce number of items so the total won't exceed the total number of items
            $number -= $nb;

            $data[] = [
                'number' => $nb,
                'label'  => '',
                'url'    => '#',
            ];
        }

        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => $fk_item::getIcon(),
        ];
    }

    public static function articleListItem(?CommonDBTM $item = null, array $params = []): array
    {
        $data = [];
        for ($i = 0; $i < 5; $i++) {
            $days_ago = self::getObscureNumberForString($i . $item::class, 30);
            $date = date("Y-m-d", strtotime("-$days_ago days"));
            $data[] = [
                'date' => $date,
                'label' => 'Lorem ipsum',
                'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed non risus. Suspendisse lectus tortor, dignissim sit amet, adipiscing nec, ultricies sed, dolor.',
                'author' => self::getFakeNames()[$i],
                'url' => '#',
            ];
        }
        // sort by date so newest is first
        usort($data, static fn($a, $b) => $b['date'] <=> $a['date']);
        return [
            'data'   => $data,
            'number' => 5,
            'url'    => $item::getSearchURL(),
            'label'  => sprintf(__('List of %s'), $item::getTypeName(Session::getPluralNumber())),
            'icon'   => $item::getIcon(),
        ];
    }

    public static function ticketsOpened(array $params = []): array
    {
        $data = [];
        // generate data for the last 12 months
        for ($i = 0; $i < 12; $i++) {
            $date = date("Y-m", strtotime("-$i months"));
            $data[] = [
                'number' => self::getObscureNumberForString($date . 'ticketsOpened', 2500),
                'label' => $date,
                'url' => '#',
            ];
        }

        return [
            'data'        => array_reverse($data),
            'distributed' => false,
            'label'       => $params['label'],
            'icon'        => Ticket::getIcon(),
        ];
    }

    public static function getTicketsEvolution(array $params = []): array
    {
        $series = [
            'inter_total' => [
                'name'   => _nx('ticket', 'Opened', 'Opened', Session::getPluralNumber()),
                'search' => [],
            ],
            'inter_solved' => [
                'name'   => _nx('ticket', 'Solved', 'Solved', Session::getPluralNumber()),
                'search' => [],
            ],
            'inter_solved_late' => [
                'name'   => __('Late'),
                'search' => [],
            ],
            'inter_closed' => [
                'name'   => __('Closed'),
                'search' => [],
            ],
        ];

        $date_labels = [];
        // generate data for the last 12 months
        for ($i = 0; $i < 12; $i++) {
            $date = date("Y-m", strtotime("-$i months"));
            $date_labels[] = $date;
            $opened = self::getObscureNumberForString($date . 'inter_total', 1500);
            $solved = $opened - self::getObscureNumberForString($date . 'inter_solved', min(500, $opened));
            $solved_late = $solved - self::getObscureNumberForString($date . 'inter_solved_late', min(20, $solved));
            $closed = $solved - self::getObscureNumberForString($date . 'inter_closed', min(5, $solved));

            $series['inter_total']['data'][] = [
                'value' => $opened,
                'url'   => '#',
            ];
            $series['inter_solved']['data'][] = [
                'value' => $solved,
                'url'   => '#',
            ];
            $series['inter_solved_late']['data'][] = [
                'value' => $solved_late,
                'url'   => '#',
            ];
            $series['inter_closed']['data'][] = [
                'value' => $closed,
                'url'   => '#',
            ];
        }
        $date_labels = array_reverse($date_labels);
        foreach ($series as $serie) {
            $serie['data'] = array_reverse($serie['data']);
        }

        return [
            'data'  => [
                'labels' => $date_labels,
                'series' => array_values($series),
            ],
            'label' => $params['label'],
            'icon'  => Ticket::getIcon(),
        ];
    }

    public static function getTicketsStatus(array $params = []): array
    {
        $statuses = Ticket::getAllStatusArray();
        $date_labels = [];
        $series = [];

        foreach ($statuses as $status_i => $status) {
            $series[$status] = [
                'name'   => $status,
                'search' => [],
            ];
            for ($i = 0; $i < 12; $i++) {
                $date = date("Y-m", strtotime("-$i months"));
                $date_labels[] = $date;

                if ($i >= 4 && $status_i === CommonITILObject::CLOSED) {
                    $num = self::getObscureNumberForString($date . $status, 500) + 1500;
                } elseif ($i >= 8 && $status_i === CommonITILObject::CLOSED) {
                    $num = self::getObscureNumberForString($date . $status, 2500);
                } elseif ($i >= 8) {
                    $num = 0;
                } elseif ($i >= 4) {
                    $num = self::getObscureNumberForString($date . $status, 20);
                } elseif (($i === 3 || $i === 2) && $status_i === CommonITILObject::CLOSED) {
                    $num = self::getObscureNumberForString($date . $status, 500) + 1000;
                } elseif ($i === 0) {
                    // base the max number on how far into the current month we are
                    $num = self::getObscureNumberForString($date . $status, (int) date("d") * 16);
                } else {
                    $num = self::getObscureNumberForString($date . $status, 500);
                }

                $series[$status]['data'][] = [
                    'value' => $num,
                    'url'   => '#',
                ];
            }
        }

        $date_labels = array_reverse($date_labels);
        foreach ($series as $status => $serie) {
            $series[$status]['data'] = array_reverse($serie['data']);
        }

        $data = [
            'labels' => $date_labels,
            'series' => $series,
        ];

        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => Ticket::getIcon(),
        ];
    }

    public static function nbTicketsActor(string $case = "", array $params = []): array
    {
        $default_params = [
            'label'         => "",
            'icon'          => null,
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);

        $fake_names = self::getFakeNames();
        $data = [];
        foreach ($fake_names as $fake_name) {
            $data[] = [
                'number' => self::getObscureNumberForString($fake_name . $case, 500),
                'label'  => $fake_name,
                'url'    => '#',
            ];
        }

        $case_array = explode('_', $case);
        $icon = match ($case_array[0]) {
            'group' => Group::getIcon(),
            default => User::getIcon(),
        };

        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => $icon,
        ];
    }

    public static function averageTicketTimes(array $params = [])
    {
        $data = [
            'labels' => [],
            'series' => [
                [
                    'name' => __("Time to own"),
                    'data' => [],
                ], [
                    'name' => __("Waiting time"),
                    'data' => [],
                ], [
                    'name' => __("Time to resolve"),
                    'data' => [],
                ], [
                    'name' => __("Time to close"),
                    'data' => [],
                ],
            ],
        ];
        for ($i = 0; $i < 12; $i++) {
            $date = date("Y-m", strtotime("-$i months"));
            $data['labels'][] = $date;

            $ttr = round(self::getObscureNumberForString($date . 'time_to_resolve', 5 * DAY_TIMESTAMP) / HOUR_TIMESTAMP, 1);
            $ttc = $ttr + round(self::getObscureNumberForString($date . '}', DAY_TIMESTAMP) / HOUR_TIMESTAMP, 1);
            $data['series'][0]['data'][] = round(self::getObscureNumberForString($date . 'time_to_own', DAY_TIMESTAMP) / HOUR_TIMESTAMP, 1);
            $data['series'][1]['data'][] = round(self::getObscureNumberForString($date . 'waiting_time', DAY_TIMESTAMP) / HOUR_TIMESTAMP, 1);
            $data['series'][2]['data'][] = $ttr;
            $data['series'][3]['data'][] = $ttc;
        }

        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => Ticket::getIcon(),
        ];
    }
}
