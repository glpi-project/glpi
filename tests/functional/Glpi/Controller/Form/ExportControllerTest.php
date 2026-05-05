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

namespace tests\units\Glpi\Controller\Form;

use Glpi\Controller\Form\ExportController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Form\Form;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormTesterTrait;
use Symfony\Component\HttpFoundation\Request;

final class ExportControllerTest extends DbTestCase
{
    use FormTesterTrait;

    public function testGlobalAccessDeniedWithoutFormReadRight(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['form'] = 0;

        $request = Request::create('/Form/Export', 'GET', ['ids' => []]);
        $controller = new ExportController();

        $this->expectException(AccessDeniedHttpException::class);
        $controller->__invoke($request);
    }

    public function testAllAccessibleFormsAreExported(): void
    {
        $this->login();
        $entity_id = $this->getTestRootEntity(only_id: true);

        $form_a = $this->createItem(Form::class, [
            'name'         => 'Accessible Form A',
            'entities_id'  => $entity_id,
            'is_recursive' => 0,
        ]);
        $form_b = $this->createItem(Form::class, [
            'name'         => 'Accessible Form B',
            'entities_id'  => $entity_id,
            'is_recursive' => 0,
        ]);

        $request = Request::create('/Form/Export', 'GET', [
            'ids' => [$form_a->getID(), $form_b->getID()],
        ]);
        $controller = new ExportController();
        $response = $controller->__invoke($request);

        $exported_names = $this->getExportedFormNames($response->getContent());
        $this->assertContains('Accessible Form A', $exported_names);
        $this->assertContains('Accessible Form B', $exported_names);
        $this->assertCount(2, $exported_names);
    }

    public function testFormsFromInaccessibleEntityAreFiltered(): void
    {
        $this->login();

        $child_1_id = getItemByTypeName('Entity', '_test_child_1', true);
        $child_2_id = getItemByTypeName('Entity', '_test_child_2', true);

        $visible_form = $this->createItem(Form::class, [
            'name'         => 'Visible Form',
            'entities_id'  => $child_1_id,
            'is_recursive' => 0,
        ]);
        $hidden_form = $this->createItem(Form::class, [
            'name'         => 'Hidden Form',
            'entities_id'  => $child_2_id,
            'is_recursive' => 0,
        ]);

        // Restrict the active session to child_1 only (no subtree)
        $this->setEntity('_test_child_1', false);

        $request = Request::create('/Form/Export', 'GET', [
            'ids' => [$visible_form->getID(), $hidden_form->getID()],
        ]);
        $controller = new ExportController();
        $response = $controller->__invoke($request);

        $exported_names = $this->getExportedFormNames($response->getContent());
        $this->assertContains('Visible Form', $exported_names);
        $this->assertNotContains('Hidden Form', $exported_names);
        $this->assertCount(1, $exported_names);
    }

    public function testNoFormsExportedWhenNoneAreAccessible(): void
    {
        $this->login();

        $child_2_id = getItemByTypeName('Entity', '_test_child_2', true);

        $hidden_form = $this->createItem(Form::class, [
            'name'         => 'Out Of Scope Form',
            'entities_id'  => $child_2_id,
            'is_recursive' => 0,
        ]);

        // Restrict the active session to child_1 only (no subtree); child_2 form is out of scope
        $this->setEntity('_test_child_1', false);

        $request = Request::create('/Form/Export', 'GET', [
            'ids' => [$hidden_form->getID()],
        ]);
        $controller = new ExportController();
        $response = $controller->__invoke($request);

        $exported_names = $this->getExportedFormNames($response->getContent());
        $this->assertEmpty($exported_names);
    }

    public function testEmptyIdsProducesEmptyExport(): void
    {
        $this->login();

        $request = Request::create('/Form/Export', 'GET', ['ids' => []]);
        $controller = new ExportController();
        $response = $controller->__invoke($request);

        $exported_names = $this->getExportedFormNames($response->getContent());
        $this->assertEmpty($exported_names);
    }

    /** @return string[] */
    private function getExportedFormNames(string $json_content): array
    {
        $data = json_decode($json_content, associative: true);
        return array_column($data['forms'] ?? [], 'name');
    }
}
