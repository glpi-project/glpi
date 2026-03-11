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

namespace Glpi\Api\HL\Controller;

use Appliance;
use Budget;
use CartridgeItem;
use Certificate;
use Change;
use Cluster;
use CommonDBTM;
use Computer;
use ConsumableItem;
use Contact;
use Database;
use DatabaseInstance;
use DCRoom;
use Domain;
use DomainRecord;
use Enclosure;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Group;
use Line;
use Monitor;
use NetworkEquipment;
use Notepad;
use Peripheral;
use Phone;
use Printer;
use Problem;
use Project;
use ProjectTask;
use Rack;
use Software;
use SoftwareLicense;
use Supplier;
use User;

final class NotepadController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        return [
            'Note' => [
                'type' => Doc\Schema::TYPE_OBJECT,
                'x-itemtype' => Notepad::class,
                'x-version-introduced' => '2.2.0',
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
                    'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                    'user' => self::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                    'user_editor' => self::getDropdownTypeSchema(class: User::class, field: 'users_id_lastupdater', full_schema: 'User'),
                    'content' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_HTML],
                    'visible_from_ticket' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'date_creation' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'date_mod' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                ],
            ],
        ];
    }

    /**
     * @return list<class-string<CommonDBTM>>
     */
    public static function getNotepadTypes(): array
    {
        return [
            Entity::class, Line::class, Peripheral::class, Cluster::class, Project::class,
            DomainRecord::class, Enclosure::class, DCRoom::class, Computer::class, Printer::class,
            Supplier::class, SoftwareLicense::class, Certificate::class, ConsumableItem::class,
            Budget::class, CartridgeItem::class, Rack::class, Phone::class, Change::class,
            NetworkEquipment::class, Appliance::class, Problem::class, Database::class,
            Contact::class, Domain::class, Software::class, ProjectTask::class, DatabaseInstance::class,
            Group::class, Monitor::class,
        ];
    }

    #[Route(path: '/{itemtype}/{items_id}/Note', methods: ['POST'], requirements: [
        'itemtype' => [self::class, 'getNotepadTypes'],
        'items_id' => '\d+',
    ], tags: ['Notes'])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\CreateRoute(schema_name: 'Note')]
    public function createNote(Request $request): Response
    {
        $request->setParameter('itemtype', $request->getAttribute('itemtype'));
        $request->setParameter('items_id', $request->getAttribute('items_id'));
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema('Note', $this->getAPIVersion($request)),
            $request->getParameters(),
            [self::class, 'getNote'],
            [
                'mapped' => [
                    'itemtype' => $request->getAttribute('itemtype'),
                    'items_id' => $request->getAttribute('items_id'),
                ],
            ]
        );
    }

    #[Route(path: '/{itemtype}/{items_id}/Note', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getNotepadTypes'],
        'items_id' => '\d+',
    ], tags: ['Notes'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\SearchRoute(schema_name: 'Note')]
    public function searchNote(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';itemtype==' . $request->getAttribute('itemtype') . ';items_id==' . $request->getAttribute('items_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Note', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/{itemtype}/{items_id}/Note/{id}', methods: ['GET'], requirements: [
        'itemtype' => [self::class, 'getNotepadTypes'],
        'items_id' => '\d+',
        'id' => '\d+',
    ], tags: ['Notes'])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\GetRoute(schema_name: 'Note')]
    public function getNote(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';itemtype==' . $request->getAttribute('itemtype') . ';items_id==' . $request->getAttribute('items_id');
        $request->setParameter('filter', $filters);
        return ResourceAccessor::getOneBySchema(
            $this->getKnownSchema('Note', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters()
        );
    }

    #[Route(path: '/{itemtype}/{items_id}/Note/{id}', methods: ['PATCH'], requirements: [
        'itemtype' => [self::class, 'getNotepadTypes'],
        'items_id' => '\d+',
        'id' => '\d+',
    ], tags: ['Notes'])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\UpdateRoute(schema_name: 'Note')]
    public function updateNote(Request $request): Response
    {
        return ResourceAccessor::updateBySchema(
            $this->getKnownSchema('Note', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters()
        );
    }

    #[Route(path: '/{itemtype}/{items_id}/Note/{id}', methods: ['DELETE'], requirements: [
        'itemtype' => [self::class, 'getNotepadTypes'],
        'items_id' => '\d+',
        'id' => '\d+',
    ], tags: ['Notes'])]
    #[RouteVersion(introduced: '2.2')]
    #[Doc\DeleteRoute(schema_name: 'Note')]
    public function deleteNote(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema(
            $this->getKnownSchema('Note', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters()
        );
    }
}
