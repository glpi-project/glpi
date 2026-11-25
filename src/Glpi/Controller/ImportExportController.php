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

namespace Glpi\Controller;

use CommonDBTM;
use Entity;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Form\Form;
use Glpi\Http\RedirectResponse;
use Glpi\ImportExport\Context\DatabaseMapper;
use Glpi\ImportExport\ImportExportHandlerInterface;
use Glpi\ImportExport\ImportExportManager;
use InvalidArgumentException;
use JsonException;
use Session;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use UnexpectedValueException;

/**
 * Export, and import wizard (upload, preview, resolve issues, execute), for
 * every itemtype supported by {@see ImportExportManager}.
 *
 * The itemtype is given as an `itemtype` query parameter.
 */
final class ImportExportController extends AbstractController
{
    #[Route("/ImportExport/Export", name: "glpi_import_export_export", methods: "GET")]
    public function export(Request $request): Response
    {
        [$itemtype, $handler] = $this->getContext($request, import: false);

        $ids = $request->query->all()['ids'] ?? [];
        $items = array_filter(
            $itemtype::getByIds(is_array($ids) ? $ids : []),
            static fn(CommonDBTM $item) => $item->can($item->getID(), READ),
        );
        $export = $handler->exportItems($items);

        $response = new Response($export->getJsonContent());
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $export->getFileName(),
        ));
        return $response;
    }

    #[Route("/ImportExport/Import", name: "glpi_import_export_import", methods: "GET")]
    public function index(Request $request): Response
    {
        [$itemtype] = $this->getContext($request, import: true);
        return $this->renderStep($itemtype, 'step1_index', __('Import'), []);
    }

    #[Route("/ImportExport/Import/Preview", name: "glpi_import_export_import_preview", methods: "POST")]
    public function preview(Request $request): Response
    {
        [$itemtype, $handler] = $this->getContext($request, import: true);
        $index_url = $request->getBasePath() . ImportExportManager::getImportUrl($itemtype);

        $json = $request->request->has('json') ? $request->request->getString('json') : null;
        $file = $request->files->get('import_file');
        if ($json === null && $file instanceof UploadedFile) {
            $json = $file->getContent();
        }
        try {
            $preview = $handler->previewImport($json ?? '', $this->getMapper($request), $this->getSkippedItems($request));
        } catch (InvalidArgumentException|UnexpectedValueException|JsonException) {
            Session::addMessageAfterRedirect(__s('The file is not a valid export for this type of item.'), false, ERROR);
            return new RedirectResponse($index_url);
        }

        if ($preview->getValidItems() === [] && $preview->getInvalidItems() === [] && $preview->getItemsWithFatalErrors() === []) {
            return new RedirectResponse($index_url);
        }
        return $this->renderStep($itemtype, 'step2_preview', __('Preview import'), [
            'preview'      => $preview,
            'json'         => $json,
            'replacements' => $this->getReplacements($request),
        ]);
    }

    #[Route("/ImportExport/Import/ResolveIssues", name: "glpi_import_export_import_resolve_issues", methods: "POST")]
    public function resolveIssues(Request $request): Response
    {
        [$itemtype, $handler] = $this->getContext($request, import: true);
        $json = $request->request->getString('json');
        $issues = $handler->listIssues($this->getMapper($request), $json)->getIssues();

        return $this->renderStep($itemtype, 'step3_resolve_issues', __('Resolve issues'), [
            'issues'        => $issues[$request->request->getInt('item_id')] ?? [],
            'json'          => $json,
            'replacements'  => $this->getReplacements($request),
            'skipped_items' => $this->getSkippedItems($request),
        ]);
    }

    #[Route("/ImportExport/Import/Execute", name: "glpi_import_export_import_execute", methods: "POST")]
    public function execute(Request $request): Response
    {
        [$itemtype, $handler] = $this->getContext($request, import: true);
        $results = $handler->importItems(
            $request->request->getString('json'),
            $this->getMapper($request),
            $this->getSkippedItems($request),
        );
        return $this->renderStep($itemtype, 'step4_execute', __('Import results'), ['results' => $results]);
    }

    /** @deprecated 11.0.14 Former URL of the forms export, use {@see export()} */
    #[Route("/Form/Export", name: "glpi_form_export")]
    public function legacyFormExport(Request $request): Response
    {
        $ids = $request->query->all()['ids'] ?? [];
        return new RedirectResponse(
            $request->getBasePath() . ImportExportManager::getExportUrl(Form::class, is_array($ids) ? $ids : []),
        );
    }

    /** @deprecated 11.0.14 Former URL of the forms import, use {@see index()} */
    #[Route("/Form/Import", name: "glpi_form_import", methods: "GET")]
    public function legacyFormImport(Request $request): Response
    {
        return new RedirectResponse($request->getBasePath() . ImportExportManager::getImportUrl(Form::class));
    }

    /**
     * @return array{class-string<CommonDBTM>, ImportExportHandlerInterface}
     */
    private function getContext(Request $request, bool $import): array
    {
        $itemtype = $request->query->getString('itemtype');
        $handler = ImportExportManager::getHandler($itemtype);
        if ($handler === null || !is_a($itemtype, CommonDBTM::class, true)) {
            throw new BadRequestHttpException();
        }
        if (!($import ? $itemtype::canCreate() : $itemtype::canView())) {
            throw new AccessDeniedHttpException();
        }
        return [$itemtype, $handler];
    }

    /**
     * @param class-string<CommonDBTM> $itemtype
     * @param array<string, mixed> $parameters
     */
    private function renderStep(string $itemtype, string $step, string $title, array $parameters): Response
    {
        return $this->render("pages/import_export/$step.html.twig", $parameters + [
            'title'    => $title,
            'menu'     => $itemtype::getSectorizedDetails(),
            'itemtype' => $itemtype,
            'urls'     => [
                'index'          => ImportExportManager::getImportUrl($itemtype),
                'preview'        => ImportExportManager::getImportUrl($itemtype, '/Preview'),
                'resolve_issues' => ImportExportManager::getImportUrl($itemtype, '/ResolveIssues'),
                'execute'        => ImportExportManager::getImportUrl($itemtype, '/Execute'),
            ],
        ]);
    }

    private function getMapper(Request $request): DatabaseMapper
    {
        $mapper = new DatabaseMapper(Session::getActiveEntities());
        foreach ($this->getReplacements($request) as $replacement) {
            $mapper->addMappedItem($replacement['itemtype'], $replacement['original_name'], $replacement['replacement_id']);
        }
        return $mapper;
    }

    /**
     * Replacements chosen for missing items. They are posted by the browser,
     * so only those the user could have picked are kept.
     *
     * @return list<array{itemtype: string, original_name: string, replacement_id: int}>
     */
    private function getReplacements(Request $request): array
    {
        $replacements = [];
        foreach ($request->request->all()['replacements'] ?? [] as $replacement) {
            if (
                is_array($replacement)
                && isset($replacement['itemtype'], $replacement['original_name'], $replacement['replacement_id'])
                && $this->isAvailableItem((string) $replacement['itemtype'], (int) $replacement['replacement_id'])
            ) {
                $replacements[] = [
                    'itemtype'       => (string) $replacement['itemtype'],
                    'original_name'  => (string) $replacement['original_name'],
                    'replacement_id' => (int) $replacement['replacement_id'],
                ];
            }
        }
        return $replacements;
    }

    /**
     * Whether the item exists and is visible in the active entities, as the
     * items found by name are.
     */
    private function isAvailableItem(string $itemtype, int $id): bool
    {
        $item = getItemForItemtype($itemtype);
        if (!$item instanceof CommonDBTM || !$item->getFromDB($id)) {
            return false;
        }
        if ($item instanceof Entity) {
            return Session::haveAccessToEntity($id);
        }
        return !$item->isEntityAssign() || Session::haveAccessToEntity($item->getEntityID(), $item->isRecursive());
    }

    /** @return array<int|string> */
    private function getSkippedItems(Request $request): array
    {
        $skipped = $request->request->all()['skipped_items'] ?? [];
        return is_array($skipped) ? $skipped : [];
    }
}
