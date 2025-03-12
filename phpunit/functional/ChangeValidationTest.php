<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units;

/* Test for src/ChangeValidation.php */

use Glpi\PHPUnit\Tests\CommonITILValidation;
use PHPUnit\Framework\Attributes\DataProvider;

class ChangeValidationTest extends CommonITILValidation
{
    #[DataProvider('testComputeValidationProvider')]
    public function testComputeValidation(
        int $accepted,
        int $refused,
        int $validation_percent,
        int $result
    ): void {
        $test_result = \ChangeValidation::computeValidation(
            $accepted,
            $refused,
            $validation_percent
        );

        $this->assertEquals($result, $test_result);
    }

    public static function testComputeValidationProvider(): array
    {
        return [
            // 100% validation required
            [
                'accepted'           => 0,
                'refused'            => 0,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 10,
                'refused'            => 0,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 90,
                'refused'            => 0,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 100,
                'refused'            => 0,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::ACCEPTED,
            ],
            [
                'accepted'           => 0,
                'refused'            => 10,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::REFUSED,
            ],
            // 50% validation required
            [
                'accepted'           => 0,
                'refused'            => 0,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 10,
                'refused'            => 0,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 50,
                'refused'            => 0,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::ACCEPTED,
            ],
            [
                'accepted'           => 0,
                'refused'            => 10,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 0,
                'refused'            => 50,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 0,
                'refused'            => 60,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::REFUSED,
            ],
            // 0% validation required
            [
                'accepted'           => 0,
                'refused'            => 0,
                'validation_percent' => 0,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 10,
                'refused'            => 0,
                'validation_percent' => 0,
                'result'             => \CommonITILValidation::ACCEPTED,
            ],
            [
                'accepted'           => 0,
                'refused'            => 10,
                'validation_percent' => 0,
                'result'             => \CommonITILValidation::REFUSED,
            ],
        ];
    }


    public function testGlobalValidationUpdate(): void
    {
        $this->login();
        $uid1 = getItemByTypeName('User', 'glpi', true);

        $change = $this->createItem('Change', [
            'name' => 'Global_Validation_Update',
            'content' => 'Global_Validation_Update',
            'validation_percent' => 100,
        ]);

        $v1_id = $this->createItem('ChangeValidation', [
            'changes_id'        => $change->getID(),
            'itemtype_target'   => \User::class,
            'items_id_target'   => $uid1,
//            'validationsteps_id' => $this->getInitialDefaultValidationStep()->getID(),
        ]);

        $this->updateItem('ChangeValidation', $v1_id->getID(), [
            'status'  => \CommonITILValidation::ACCEPTED,
        ]);

        $this->updateItem('Change', $change->getID(), [
            'validation_percent' => 0,
        ]);

        $this->assertEquals(\CommonITILValidation::ACCEPTED, \ChangeValidation::computeValidationStatus($change));

        $this->updateItem('Change', $change->getID(), [
            'validation_percent' => 50,
        ]);

        $v2_id = $this->createItem('ChangeValidation', [
            'changes_id'        => $change->getID(),
            'itemtype_target'   => \User::class,
            'items_id_target'   => $uid1,
//            'validationsteps_id' => $this->getInitialDefaultValidationStep()->getID(),
        ]);

        $this->updateItem('ChangeValidation', $v2_id->getID(), [
            'status'  => \CommonITILValidation::WAITING,
        ]);

        $this->assertEquals(\CommonITILValidation::WAITING, \ChangeValidation::computeValidationStatus($change));

        $this->updateItem('Change', $change->getID(), [
            'validation_percent' => 100,
        ]);

        $v3_id = $this->createItem('ChangeValidation', [
            'changes_id'        => $change->getID(),
            'itemtype_target'   => \User::class,
            'items_id_target'   => $uid1,
        ]);

        $this->updateItem('ChangeValidation', $v3_id->getID(), [
            'status'  => \CommonITILValidation::REFUSED,
        ]);


        $this->assertEquals(\CommonITILValidation::REFUSED, \ChangeValidation::computeValidationStatus($change));
    }

}
