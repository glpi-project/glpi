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

use Glpi\PHPUnit\Tests\CommonITILValidationTest;
use PHPUnit\Framework\Attributes\DataProvider;

class ChangeValidationTest extends CommonITILValidationTest
{
    public function testGlobalValidationUpdate(): void
    {
        $this->login();
        $uid1 = getItemByTypeName('User', 'glpi', true);

        // --- single ACCEPTED validation & 100% required -> \ChangeValidation::computeValidationStatus($change) returns ACCEPTED
        $change = $this->createItem('Change', [
            'name' => 'Global_Validation_Update',
            'content' => 'Global_Validation_Update',
        ]);

        $validation_1 = $this->createItem('ChangeValidation', [
            'changes_id'        => $change->getID(),
            'itemtype_target'   => \User::class,
            'items_id_target'   => $uid1,
        ]);
        $this->updateITIL_ValidationStepOfItil($validation_1, 100); // 100% required is default, added to be explicit

        $this->updateItem('ChangeValidation', $validation_1->getID(), [
            'status'  => \CommonITILValidation::ACCEPTED,
        ]);

        // --- 0% required -> \ChangeValidation::computeValidationStatus($change) returns ACCEPTED
        $this->updateITIL_ValidationStepOfItil($validation_1, 0);
        $this->assertValidationStatusEquals(\CommonITILValidation::ACCEPTED, \ChangeValidation::computeValidationStatus($change));

        // ---- add a second WAITING validation & 50% required -> \ChangeValidation::computeValidationStatus($change) returns WAITING
        // 1 ACCEPTED validation + 1 WAITING validation
        $this->updateITIL_ValidationStepOfItil($validation_1, 50);

        $validation_2 = $this->createItem('ChangeValidation', [
            'changes_id'        => $change->getID(),
            'itemtype_target'   => \User::class,
            'items_id_target'   => $uid1,
        ]);
        $this->updateItem('ChangeValidation', $validation_2->getID(), [
            'status'  => \CommonITILValidation::WAITING,
        ]);

        $this->assertValidationStatusEquals(\CommonITILValidation::ACCEPTED, \ChangeValidation::computeValidationStatus($change));

        // ---- 100% required -> \ChangeValidation::computeValidationStatus($change) returns WAITING
        // unchanged : 1 ACCEPTED validation + 1 WAITING validation
        $this->updateITIL_ValidationStepOfItil($validation_1, 100);
        $this->assertValidationStatusEquals(\CommonITILValidation::WAITING, \ChangeValidation::computeValidationStatus($change));

        // --- add a third validation & update itils_validationstep to 100% required -> \ChangeValidation::computeValidationStatus($change) returns WAITING
        // 1 ACCEPTED validation + 1 WAITING validation + 1 REFUSED validation
        $this->updateITIL_ValidationStepOfItil($validation_1, 0);

        $v3_id = $this->createItem('ChangeValidation', [
            'changes_id'        => $change->getID(),
            'itemtype_target'   => \User::class,
            'items_id_target'   => $uid1,
        ]);

        $this->updateItem('ChangeValidation', $v3_id->getID(), [
            'status'  => \CommonITILValidation::REFUSED,
        ]);

        $this->assertValidationStatusEquals(\CommonITILValidation::ACCEPTED, \ChangeValidation::computeValidationStatus($change));

        // ---- 100% required -> \ChangeValidation::computeValidationStatus($change) returns REFUSED
        // 1 ACCEPTED validation + 1 WAITING validation + 1 REFUSED validation (unchanged)
        $this->updateITIL_ValidationStepOfItil($validation_1, 100);
        $this->assertValidationStatusEquals(\CommonITILValidation::REFUSED, \ChangeValidation::computeValidationStatus($change));

        // ---- 50% required -> \ChangeValidation::computeValidationStatus($change) returns WAITING
        // 1 ACCEPTED validation + 1 WAITING validation + 1 REFUSED validation (unchanged)
        $this->updateITIL_ValidationStepOfItil($validation_1, 50);
        $this->assertValidationStatusEquals(\CommonITILValidation::WAITING, \ChangeValidation::computeValidationStatus($change));

        // ---- 33% required -> \ChangeValidation::computeValidationStatus($change) returns WAITING
        // 1 ACCEPTED validation + 1 WAITING validation + 1 REFUSED validation (unchanged)
        $this->updateITIL_ValidationStepOfItil($validation_1, 33);
        $this->assertValidationStatusEquals(\CommonITILValidation::ACCEPTED, \ChangeValidation::computeValidationStatus($change));
    }
}
