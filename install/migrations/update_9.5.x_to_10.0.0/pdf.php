<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/**
 * @var DB $DB
 * @var Migration $migration
 */
$fonts_mapping = [
   // Arabic fonts => xbriyaz
    'xbriyaz'      => [
        'aealarabiya',
        'aefurat',
    ],
   // CJK fonts => sun-exta
    'sun-exta'     => [
        'cid0cs',
        'cid0ct',
        'cid0jp',
        'cid0kr',
        'hysmyeongjostdmedium',
        'kozgopromedium',
        'kozminproregular',
        'msungstdlight',
        'stsongstdlight',
    ],
   // Adobe embedded fonts => corresponding TTF font
    'courier'      => ['pdfacourier'],
    'helvetica'    => ['pdfahelvetica'],
    'symbol'       => ['pdfasymbol'],
    'times'        => ['pdfatimes'],
    'zapfdingbats' => ['pdfazapfdingbats'],
   // Other unsupported fonts
    'dejavusans'   => ['dejavusansextralight'],
];

foreach ($fonts_mapping as $new_value => $old_values) {
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_configs',
            [
                'value' => $new_value,
            ],
            [
                'name'  => 'pdffont',
                'value' => $old_values,
            ]
        )
    );
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_users',
            [
                'pdffont' => $new_value,
            ],
            [
                'pdffont' => $old_values,
            ]
        )
    );
}
