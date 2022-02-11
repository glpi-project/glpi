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

namespace tests\units;

use DbTestCase;

abstract class LevelAgreement extends DbTestCase
{

    protected $levelagreement_class = null;

    public function __construct(string $levelagreement_class)
    {
        $this->levelagreement_class = $levelagreement_class;
    }

    protected function getLevelAgreementInstance(): \LevelAgreement
    {
        return new $this->levelagreement_class();
    }

    protected function getActiveTimeBetweeenProvider()
    {
        return [
            [
                'start'         => '2019-01-01 07:00:00',
                'end'           => '2019-01-01 09:00:00',
                'value'         => HOUR_TIMESTAMP,
                'calendars_id'  => 1
            ], [
                'start'         => '2019-01-01 06:00:00',
                'end'           => '2019-01-01 07:00:00',
                'value'         => 0,
                'calendars_id'  => 1
            ], [
                'start'         => '2019-01-01 00:00:00',
                'end'           => '2019-01-08 00:00:00',
                'value'         => 12 * HOUR_TIMESTAMP * 5,
                'calendars_id'  => 1
            ], [
                'start'         => '2019-01-08 00:00:00',
                'end'           => '2019-01-01 00:00:00',
                'value'         => 0,
                'calendars_id'  => 1
            ], [
                'start'         => '2019-01-01 07:00:00',
                'end'           => '2019-01-01 09:00:00',
                'value'         => HOUR_TIMESTAMP * 2,
                'calendars_id'  => -1
            ], [
                'start'         => '2019-01-01 00:00:00',
                'end'           => '2019-01-08 00:00:00',
                'value'         => WEEK_TIMESTAMP,
                'calendars_id'  => -1
            ]
        ];
    }

    public function testGetActiveTimeBetween($start, $end, $expected, $calendars_id)
    {
        $level_agreement = $this->getLevelAgreementInstance();
        $level_agreement->fields['calendars_id'] = $calendars_id;
        $this->boolean($level_agreement->getActiveTimeBetween($start, $end))->isIdenticalTo($expected);
    }
}