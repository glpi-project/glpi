<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use CommonITILValidation;
use Glpi\Tests\CommonITILValidationTest;
use QueuedNotification;

class TicketValidationTest extends CommonITILValidationTest
{
    public function testValidationAnswerNotification(): void
    {
        global $CFG_GLPI;

        $this->login();
        $itil = $this->createItem($this->getITILClassname(), [
            'name' => 'Test Notification Recipients',
            'content' => 'Test Notification Recipients',
        ]);
        $validation = $this->createItem($this->getValidationClassname(), [
            $itil::getForeignKeyField() => $itil->getID(),
            'itemtype_target' => 'User',
            'items_id_target' => $_SESSION['glpiID'],
        ]);
        $queued_notification = new QueuedNotification();

        $CFG_GLPI["use_notifications"] = true;
        $CFG_GLPI['notifications_mailing'] = true;

        $this->assertEquals(0, countElementsInTable($queued_notification::getTable(), ['event' => 'validation_answer']));

        // Updating the submission comment should not trigger the validation_answer notification
        $validation->update([
            'id' => $validation->getID(),
            'comment_submission' => 'This is a comment.',
        ]);
        $this->assertEquals(0, countElementsInTable($queued_notification::getTable(), ['event' => 'validation_answer']));

        $validation->update([
            'id' => $validation->getID(),
            'status' => CommonITILValidation::ACCEPTED,
        ]);
        // Administrator + Approver
        $this->assertEquals(2, countElementsInTable($queued_notification::getTable(), ['event' => 'validation_answer']));
    }
}
