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
use Glpi\Asset\AssetDefinition;
use Glpi\Controller\ImportExportController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Form\Export\Serializer\FormSerializer;
use Glpi\Form\Form;
use Glpi\Http\RedirectResponse;
use Glpi\ImportExport\ImportExportManager;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use MassiveAction;
use Profile;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function Safe\json_decode;
use function Safe\json_encode;

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

    private function exportDefinition(string $system_name): array
    {
        $definition = $this->initAssetDefinition($system_name);
        $response = $this->request(AssetDefinition::class, 'Export', 'GET', ['ids' => [$definition->getID()]]);
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename=', $response->headers->get('Content-Disposition'));

        $data = json_decode($response->getContent(), true);
        $data['items'][0]['fields']['system_name'] .= 'Copy';
        return [$definition, json_encode($data)];
    }

    public function testRights(): void
    {
        $this->login();
        $_SESSION['glpiactiveprofile']['config'] = READ;
        $this->assertSame(200, $this->request(AssetDefinition::class, 'Export', 'GET', ['ids' => []])->getStatusCode());
        try {
            $this->request(AssetDefinition::class, 'Import', 'GET');
            $this->fail('Import must require the create right');
        } catch (AccessDeniedHttpException) {
        }

        $_SESSION['glpiactiveprofile']['config'] = 0;
        $this->expectException(AccessDeniedHttpException::class);
        $this->request(AssetDefinition::class, 'Export', 'GET', ['ids' => []]);
    }

    public function testUnsupportedItemtype(): void
    {
        $this->login();
        $this->expectException(BadRequestHttpException::class);
        $this->request(Computer::class, 'Import', 'GET');
    }

    public function testWizard(): void
    {
        $this->login();
        [$definition, $json] = $this->exportDefinition('WizardAsset');

        // Step 1: upload form, posted to the itemtype's preview
        $crawler = new Crawler($this->request(AssetDefinition::class, 'Import', 'GET')->getContent());
        $this->assertSame(
            '/ImportExport/Import/Preview?itemtype=' . urlencode(AssetDefinition::class),
            $crawler->filter('form[enctype="multipart/form-data"]')->attr('action'),
        );

        // Step 2: preview, with an unknown profile
        $data = json_decode($json, true);
        $data['items'][0]['fields']['profiles']['Unknown profile'] = READ;
        $json = json_encode($data);
        $crawler = new Crawler($this->request(AssetDefinition::class, 'Import/Preview', 'POST', ['json' => $json])->getContent());
        $this->assertStringContainsString("Can't be imported", $crawler->filter('[data-testid="import-preview"] tbody')->text());
        $resolve = $crawler->filter('button[name="item_id"]');
        $this->assertSame((string) $definition->getID(), $resolve->attr('value'));
        $this->assertStringContainsString('/ImportExport/Import/ResolveIssues?itemtype=', $resolve->attr('formaction'));

        // Step 3: the missing profile is listed
        $crawler = new Crawler($this->request(AssetDefinition::class, 'Import/ResolveIssues', 'POST', ['json' => $json, 'item_id' => $definition->getID()])->getContent());
        $this->assertSame(Profile::class, $crawler->filter('input[name="replacements[0][itemtype]"]')->attr('value'));
        $this->assertSame('Unknown profile', $crawler->filter('input[name="replacements[0][original_name]"]')->attr('value'));

        // Step 2 again, with the replacement
        $replacements = [['itemtype' => Profile::class, 'original_name' => 'Unknown profile', 'replacement_id' => getItemByTypeName(Profile::class, 'Observer', true)]];
        $crawler = new Crawler($this->request(AssetDefinition::class, 'Import/Preview', 'POST', ['json' => $json, 'replacements' => $replacements])->getContent());
        $this->assertStringContainsString('Ready to be imported', $crawler->filter('[data-testid="import-preview"] tbody')->text());
        $this->assertSame('Unknown profile', $crawler->filter('input[name="replacements[0][original_name]"]')->attr('value'));

        // Removing the only item goes back to step 1
        $response = $this->request(AssetDefinition::class, 'Import/Preview', 'POST', ['json' => $json, 'skipped_items' => [$definition->getID()]]);
        $this->assertInstanceOf(RedirectResponse::class, $response);

        // Step 4
        $crawler = new Crawler($this->request(AssetDefinition::class, 'Import/Execute', 'POST', ['json' => $json, 'replacements' => $replacements])->getContent());
        $link = $crawler->filter('[data-testid="import-results"] a');
        $this->assertSame('WizardAssetCopy', AssetDefinition::getById((int) explode('id=', $link->attr('href'))[1])->fields['system_name']);
    }

    public function testInvalidFileGoesBackToUpload(): void
    {
        $this->login();
        $response = $this->request(AssetDefinition::class, 'Import/Preview', 'POST', ['json' => '{"type": "Computer", "version": 1, "items": []}']);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $response = $this->request(AssetDefinition::class, 'Import/Preview', 'POST', ['json' => json_encode(['type' => AssetDefinition::class, 'version' => 2, 'items' => []])]);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $response = $this->request(AssetDefinition::class, 'Import/Preview', 'POST', ['json' => 'not json']);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $message = 'The file is not a valid export for this type of item.';
        $this->hasSessionMessages(ERROR, [$message, $message, $message]);
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
        $this->assertArrayHasKey($key, MassiveAction::getAllMassiveActions(AssetDefinition::class));
        $this->assertArrayNotHasKey($key, MassiveAction::getAllMassiveActions(Computer::class));
        $this->assertArrayHasKey($key, MassiveAction::getAllMassiveActions(Form::class));
        $this->assertArrayNotHasKey(Form::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'export', MassiveAction::getAllMassiveActions(Form::class));

        $this->assertSame(
            '/ImportExport/Import?itemtype=' . urlencode(AssetDefinition::class),
            AssetDefinition::getMenuContent()['links']['import'],
        );
        $this->assertArrayNotHasKey('import', Computer::getMenuContent()['links']);

        $form_links = Form::getMenuContent()['links'];
        $this->assertSame('/ImportExport/Import?itemtype=' . urlencode(Form::class), $form_links['import']);
        $this->assertArrayNotHasKey('import_forms', $form_links);
    }
}
