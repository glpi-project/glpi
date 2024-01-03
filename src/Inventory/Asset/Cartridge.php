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

namespace Glpi\Inventory\Asset;

use Glpi\Inventory\Conf;
use Glpi\Toolbox\Sanitizer;
use Printer_CartridgeInfo;

class Cartridge extends InventoryAsset
{
    public function knownTags(): array
    {
        $tags = [];

        $aliases = [
            'tonerblack'      => ['tonerblack2'],
            'tonerblackmax'   => ['tonerblack2max'],
            'tonerblackused'  => ['tonerblack2used'],
            'tonerblackremaining'   => ['tonerblack2remaining']
        ];

        $types = [
            'toner'           => __('Toner'),
            'drum'            => __('Drum'),
            'cartridge'       => _n('Cartridge', 'Cartridges', 1),
            'wastetoner'      => __('Waste bin'),
            'maintenancekit'  => __('Maintenance kit'),
            'fuserkit'        => __('Fuser kit'),
            'transferkit'     => __('Transfer kit'),
            'cleaningkit'     => __('Cleaning kit'),
            'developer'       => __('Developer'),
            'photoconductor'  => __('Photoconductor')
        ];

        $colored_types = ['toner', 'drum', 'cartridge', 'photoconductor'];
        $w_extras_types = ['cartridge'];

        $colors = [
            'black'        => __('Black'),
            'cyan'         => __('Cyan'),
            'cyanlight'    => __('Light cyan'),
            'magenta'      => __('Magenta'),
            'magentalight' => __('Light magenta'),
            'yellow'       => __('Yellow'),
            'grey'         => __('Grey'),
            'darkgrey'     => __('Dark grey'),
            'gray'         => __('Grey'),
            'darkgray'     => __('Dark grey')
        ];

        $states = [
            'max'       => __('Max'),
            'used'      => __('Used'),
            'remaining' => __('Remaining')
        ];

        foreach ($types as $type => $label) {
           //not a colored type, add an entry for type only, and type + state
            if (!in_array($type, $colored_types)) {
                $tags[$type] = [
                    'name'   => $label
                ];

                foreach ($states as $state => $slabel) {
                    $tags[$type . $state] = [
                    //TRANS first argument is a type, second a state
                        'name'   => sprintf(
                            '%1$s %2$s',
                            $label,
                            $slabel
                        )
                    ];
                    if (isset($aliases[$type . $state])) {
                        foreach ($aliases[$type . $state] as $alias) {
                            $tags[$alias] = $tags[$type . $state];
                        }
                    }
                }
            } else {
               //types colored: add an entry with type + color and type + color + state
                foreach ($colors as $color => $clabel) {
                    $tags[$type . $color] = [
                    //TRANS first argument is a type, second a color
                        'name'   => sprintf(
                            '%1$s %2$s',
                            $label,
                            $clabel
                        )
                    ];

                    if (isset($aliases[$type . $color])) {
                        foreach ($aliases[$type . $color] as $alias) {
                            $tags[$alias] = $tags[$type . $color];
                        }
                    }

                    foreach ($states as $state => $slabel) {
                        $tags[$type . $color . $state] = [
                        //TRANS first argument is a type, second a color and third a state
                            'name'   => sprintf(
                                '%1$s %2$s %3$s',
                                $label,
                                $clabel,
                                $slabel
                            )
                        ];
                        if (isset($aliases[$type . $color . $state])) {
                            foreach ($aliases[$type . $color . $state] as $alias) {
                                $tags[$alias] = $tags[$type . $color . $state];
                            }
                        }
                    }

                    if (in_array($type, $w_extras_types) && $color == 'black') {
                        $extras = [
                            'photo'  => __('Photo'),
                            'matte'  => __('Matte')
                        ];
                        foreach ($extras as $extra => $elabel) {
                            $tags[$type . $color . $extra] = [
                                'name' => sprintf(
                            //TRANS first argument is a type, second a color and third an extra (matte or photo)
                                    '%1$s %2$s %3$s',
                                    $label,
                                    $clabel,
                                    $elabel
                                )
                            ];
                            if (isset($aliases[$type . $color . $extra])) {
                                foreach ($aliases[$type . $color . $extra] as $alias) {
                                    $tags[$alias] = $tags[$type . $color . $extra];
                                }
                            }

                            $tags[$type . $extra . $color] = [
                                'name' => sprintf(
                           //TRANS first argument is a type, second an extra (matte or photo) and third a color
                                    '%1$s %2$s %3$s',
                                    $label,
                                    $elabel,
                                    $clabel
                                )
                            ];
                            if (isset($aliases[$type . $extra . $color])) {
                                foreach ($aliases[$type . $extra . $color] as $alias) {
                                    $tags[$alias] = $tags[$type . $extra . $color];
                                }
                            }
                        }
                    }
                }
            }
        }

        $tags += [
            'informations' => [
                'name'   => __('Many information grouped')
            ],
            'staples'      => [
                'name'   => __('Staples')
            ]
        ];

        return $tags;
    }

    public function prepare(): array
    {
        return $this->data;
    }

    /**
     * Get existing entries from database
     *
     * @return array
     */
    protected function getExisting(): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $db_existing = [];

        $iterator = $DB->request([
            'FROM'   => Printer_CartridgeInfo::getTable(),
            'WHERE'  => ['printers_id' => $this->item->fields['id']]
        ]);

        foreach ($iterator as $data) {
            $idtmp = $data['id'];
            unset($data['id']);
            $data = array_map('strtolower', $data);
            $db_existing[$idtmp] = $data;
        }

        return $db_existing;
    }

    public function handle()
    {
        $cartinfo = new Printer_CartridgeInfo();
        $db_cartridges = $this->getExisting();

        $value = $this->data[0];
        foreach ($value as $k => $val) {
            foreach ($db_cartridges as $keydb => $arraydb) {
                if ($k == $arraydb['property']) {
                    $input = [
                        'value' => $val,
                        'id' => $keydb
                    ];
                    $cartinfo->update(Sanitizer::sanitize($input), false);
                    unset($value->$k);
                    unset($db_cartridges[$keydb]);
                    break;
                }
            }
        }

        if ((!$this->main_asset || !$this->main_asset->isPartial()) && count($db_cartridges) != 0) {
            foreach ($db_cartridges as $idtmp => $data) {
                $cartinfo->delete(['id' => $idtmp], true);
            }
        }

        foreach ($value as $property => $val) {
            $cartinfo->add(
                Sanitizer::sanitize([
                    'printers_id' => $this->item->fields['id'],
                    'property' => $property,
                    'value' => $val
                ]),
                [],
                false
            );
        }
    }

    public function checkConf(Conf $conf): bool
    {
        return true;
    }

    public function getItemtype(): string
    {
        return \CartridgeItem::class;
    }
}
