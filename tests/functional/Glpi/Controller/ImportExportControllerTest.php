<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Controller;

use Computer;
use Entity;
use Glpi\Controller\ImportExportController;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Form\Export\Serializer\FormSerializer;
use Glpi\Form\Form;
use Glpi\Http\RedirectResponse;
use Glpi\ImportExport\ImportExportManager;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use MassiveAction;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function Safe\json_decode;

final class ImportExportControllerTest extends DbTestCase
{
    use FormTesterTrait;

    private function request(string $itemtype, string $action, string $method, array $parameters = []): Response
    {
        $request = Request::create('/ImportExport/' . $action . '?itemtype=' . urlencode($itemtype), $method, $parameters);
        $controller = new ImportExportController();
        return match ($action) {
            'Export'               => $controller->export($request),
            'Import'               => $controller->index($request),
            'Import/Preview'       => $controller->preview($request),
            'Import/ResolveIssues' => $controller->resolveIssues($request),
            'Import/Execute'       => $controller->execute($request),
        };
    }

    public function testFormerFormUrlsRedirect(): void
    {
        $controller = new ImportExportController();

        $response = $controller->legacyFormImport(Request::create('/Form/Import'));
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/ImportExport/Import?itemtype=' . urlencode(Form::class), $response->getTargetUrl());

        $response = $controller->legacyFormExport(Request::create('/Form/Export', 'GET', ['ids' => [12, 34]]));
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            '/ImportExport/Export?' . http_build_query(['itemtype' => Form::class, 'ids' => [12, 34]]),
            $response->getTargetUrl(),
        );
    }

    public function testUnsupportedItemtype(): void
    {
        $this->login();
        $this->expectException(BadRequestHttpException::class);
        $this->request(Computer::class, 'Import', 'GET');
    }

    public function testOnlyAvailableReplacementsAreUsed(): void
    {
        $this->login();
        $entity = $this->createItem(Entity::class, ['name' => 'Deleted entity', 'entities_id' => $this->getTestRootEntity(true)]);
        $form = $this->createForm((new FormBuilder('Form of a deleted entity'))->setEntitiesId($entity->getID()));
        $json = $this->request(Form::class, 'Export', 'GET', ['ids' => [$form->getID()]])->getContent();
        $this->deleteItem(Entity::class, $entity->getID());
        $this->setEntity('_test_child_1', false);

        $preview = function (int $replacement_id) use ($json): string {
            $replacements = [['itemtype' => Entity::class, 'original_name' => 'Root entity > _test_root_entity > Deleted entity', 'replacement_id' => $replacement_id]];
            return (new Crawler($this->request(Form::class, 'Import/Preview', 'POST', ['json' => $json, 'replacements' => $replacements])->getContent()))
                ->filter('[data-testid="import-preview"] tbody')->text();
        };
        $this->assertStringContainsString("Can't be imported", $preview(999999));
        $this->assertStringContainsString("Can't be imported", $preview($this->getTestRootEntity(true)));
        $this->assertStringContainsString('Ready to be imported', $preview(getItemByTypeName(Entity::class, '_test_child_1', true)));
    }

    public function testFormsUseTheGenericWizard(): void
    {
        $this->login();
        $this->assertInstanceOf(FormSerializer::class, ImportExportManager::getHandler(Form::class));

        $form = $this->createForm(new FormBuilder('Exported form'));
        $response = $this->request(Form::class, 'Export', 'GET', ['ids' => [$form->getID()]]);
        $this->assertSame(['Exported form'], array_column(json_decode($response->getContent(), true)['forms'], 'name'));

        // As tests/e2e/utils/FormImporter.ts does
        $crawler = new Crawler($this->request(Form::class, 'Import/Execute', 'POST', ['json' => $response->getContent()])->getContent());
        $link = $crawler->filter('[data-testid="import-results"] a[href^="/front/form/form.form.php?id="]');
        $this->assertSame('Exported form', $link->text());
        $this->assertSame(
            '/ImportExport/Import?itemtype=' . urlencode(Form::class),
            $crawler->filterXPath('//a[normalize-space()="Import another file"]')->attr('href'),
        );
    }

    public function testMassiveActionAndMenuLink(): void
    {
        $this->login();
        $key = ImportExportManager::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'export';
        $this->assertArrayNotHasKey($key, MassiveAction::getAllMassiveActions(Computer::class));
        $this->assertArrayHasKey($key, MassiveAction::getAllMassiveActions(Form::class));
        $this->assertArrayNotHasKey(Form::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'export', MassiveAction::getAllMassiveActions(Form::class));

        $this->assertArrayNotHasKey('import', Computer::getMenuContent()['links']);

        $form_links = Form::getMenuContent()['links'];
        $this->assertSame('/ImportExport/Import?itemtype=' . urlencode(Form::class), $form_links['import']);
        $this->assertArrayNotHasKey('import_forms', $form_links);
    }
}
