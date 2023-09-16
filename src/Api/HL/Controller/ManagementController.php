<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\Api\HL\Controller;

use Appliance;
use ApplianceEnvironment;
use ApplianceType;
use AutoUpdateSystem;
use Budget;
use BudgetType;
use Certificate;
use CertificateType;
use Cluster;
use Contact;
use Contract;
use Database;
use Datacenter;
use Document;
use Document_Item;
use Domain;
use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\Search;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Group;
use Line;
use Location;
use Manufacturer;
use Network;
use SoftwareLicense;
use State;
use Supplier;
use User;

#[Route(path: '/Management', tags: ['Management'])]
final class ManagementController extends AbstractController
{
    use CRUDControllerTrait;

    protected static function getRawKnownSchemas(): array
    {
        global $CFG_GLPI;
        $schemas = [];

        $management_types = [
            Appliance::class => 'Appliance',
            Budget::class => 'Budget',
            Certificate::class => 'Certificate',
            Cluster::class => 'Cluster',
            Contact::class => 'Contact',
            Contract::class => 'Contract',
            Database::class => 'Database',
            Datacenter::class => 'DataCenter',
            Document::class => 'Document',
            Domain::class => 'Domain',
            SoftwareLicense::class => 'License',
            Line::class => 'Line',
            Supplier::class => 'Supplier',
        ];

        foreach ($management_types as $m_class => $m_name) {
            $schemas[$m_name] = [
                'x-itemtype' => $m_class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'x-readonly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                ]
            ];

            // Need instance since some fields are not static even if they aren't related to instances
            $item = new $m_class();

            if ($item->isField('comment')) {
                $schemas[$m_name]['properties']['comment'] = ['type' => Doc\Schema::TYPE_STRING];
            }

            if (in_array($m_class, $CFG_GLPI['state_types'], true)) {
                $schemas[$m_name]['properties']['status'] = self::getDropdownTypeSchema(class: State::class, full_schema: 'State');
            }

            if (in_array($m_class, $CFG_GLPI['location_types'], true)) {
                $schemas[$m_name]['properties']['location'] = self::getDropdownTypeSchema(class: Location::class, full_schema: 'Location');
            }

            if ($item->isEntityAssign()) {
                $schemas[$m_name]['properties']['entity'] = self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity');
                // Add completename field
                $schemas[$m_name]['properties']['entity']['properties']['completename'] = ['type' => Doc\Schema::TYPE_STRING];
                $schemas[$m_name]['properties']['is_recursive'] = ['type' => Doc\Schema::TYPE_BOOLEAN];
            }
            $schemas[$m_name]['properties']['date_creation'] = ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME];
            $schemas[$m_name]['properties']['date_mod'] = ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME];

            $type_class = $item->getTypeClass();
            if ($type_class !== null) {
                $schemas[$m_name]['properties']['type'] = self::getDropdownTypeSchema($type_class);
            }
            if ($item->isField('manufacturers_id')) {
                $schemas[$m_name]['properties']['manufacturer'] = self::getDropdownTypeSchema(class: Manufacturer::class, full_schema: 'Manufacturer');
            }
            $model_class = $item->getModelClass();
            if ($model_class !== null) {
                $schemas[$m_name]['properties']['model'] = self::getDropdownTypeSchema($model_class);
            }
            $env_class = $m_class . 'Environment';
            if (class_exists($env_class)) {
                $schemas[$m_name]['properties']['environment'] = self::getDropdownTypeSchema($env_class);
            }

            if (in_array($m_class, $CFG_GLPI['linkuser_tech_types'], true)) {
                $schemas[$m_name]['properties']['user_tech'] = self::getDropdownTypeSchema(class: User::class, field: 'users_id_tech', full_schema: 'User');
            }
            if (in_array($m_class, $CFG_GLPI['linkgroup_tech_types'], true)) {
                $schemas[$m_name]['properties']['group_tech'] = self::getDropdownTypeSchema(class: Group::class, field: 'groups_id_tech', full_schema: 'Group');
            }
            if (in_array($m_class, $CFG_GLPI['linkuser_types'], true)) {
                $schemas[$m_name]['properties']['user'] = self::getDropdownTypeSchema(class: User::class, full_schema: 'User');
            }
            if (in_array($m_class, $CFG_GLPI['linkgroup_types'], true)) {
                $schemas[$m_name]['properties']['group'] = self::getDropdownTypeSchema(class: Group::class, full_schema: 'Group');
            }

            if ($item->isField('contact')) {
                $schemas[$m_name]['properties']['contact'] = ['type' => Doc\Schema::TYPE_STRING];
            }
            if ($item->isField('contact_num')) {
                $schemas[$m_name]['properties']['contact_num'] = ['type' => Doc\Schema::TYPE_STRING];
            }
            if ($item->isField('serial')) {
                $schemas[$m_name]['properties']['serial'] = ['type' => Doc\Schema::TYPE_STRING];
            }
            if ($item->isField('otherserial')) {
                $schemas[$m_name]['properties']['otherserial'] = ['type' => Doc\Schema::TYPE_STRING];
            }
            if ($item->isField('networks_id')) {
                $schemas[$m_name]['properties']['network'] = self::getDropdownTypeSchema(Network::class);
            }

            if ($item->isField('uuid')) {
                $schemas[$m_name]['properties']['uuid'] = ['type' => Doc\Schema::TYPE_STRING];
            }
            if ($item->isField('autoupdatesystems_id')) {
                $schemas[$m_name]['properties']['autoupdatesystem'] = self::getDropdownTypeSchema(AutoUpdateSystem::class);
            }

            if ($item->maybeDeleted()) {
                $schemas[$m_name]['properties']['is_deleted'] = ['type' => Doc\Schema::TYPE_BOOLEAN];
            }

            if ($m_class === Budget::class) {
                $schemas[$m_name]['properties']['value'] = ['type' => Doc\Schema::TYPE_NUMBER];
                $schemas[$m_name]['properties']['date_begin'] = [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'begin_date',
                ];
                $schemas[$m_name]['properties']['date_end'] = [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'end_date',
                ];
            }
        }

        $schemas['Document']['properties']['filename'] = ['type' => Doc\Schema::TYPE_STRING];
        $schemas['Document']['properties']['filepath'] = [
            'type' => Doc\Schema::TYPE_STRING,
            'x-mapped-from' => 'id',
            'x-mapper' => static function ($v) use ($CFG_GLPI) {
                return $CFG_GLPI["root_doc"] . "/front/document.send.php?docid=" . $v;
            }
        ];
        $schemas['Document']['properties']['mime'] = ['type' => Doc\Schema::TYPE_STRING];
        $schemas['Document']['properties']['sha1sum'] = ['type' => Doc\Schema::TYPE_STRING];
        $schemas['Document_Item'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => Document_Item::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'itemtype' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-readonly' => true,
                ],
                'items_id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'documents_id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'filepath' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'x-mapped-from' => 'documents_id',
                    'x-mapper' => static function ($v) use ($CFG_GLPI) {
                        return $CFG_GLPI["root_doc"] . "/front/document.send.php?docid=" . $v;
                    }
                ]
            ]
        ];

        return $schemas;
    }

    #[Route(path: '/Budget', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search budgets',
        parameters: [self::PARAMETER_RSQL_FILTER],
        responses: [
            ['schema' => 'Budget[]']
        ]
    )]
    public function searchBudgets(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Budget'), $request->getParameters());
    }

    #[Route(path: '/Budget/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a budget by ID',
        responses: [
            ['schema' => 'Budget']
        ]
    )]
    public function getBudget(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('Budget'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Budget', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new budget', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Budget',
        ]
    ])]
    public function createBudget(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('Budget'), $request->getParameters(), [self::class, 'getBudget']);
    }

    #[Route(path: '/Budget/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a budget by ID',
        responses: [
            ['schema' => 'Budget']
        ]
    )]
    public function updateBudget(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('Budget'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Budget/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a budget by ID')]
    public function deleteBudget(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('Budget'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/License', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search licenses',
        parameters: [self::PARAMETER_RSQL_FILTER],
        responses: [
            ['schema' => 'License[]']
        ]
    )]
    public function searchLicenses(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('License'), $request->getParameters());
    }

    #[Route(path: '/License/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a license by ID',
        responses: [
            ['schema' => 'License']
        ]
    )]
    public function getLicense(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('License'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/License', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new license', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'License',
        ]
    ])]
    public function createLicense(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('License'), $request->getParameters(), [self::class, 'getLicense']);
    }

    #[Route(path: '/License/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a license by ID',
        responses: [
            ['schema' => 'License']
        ]
    )]
    public function updateLicense(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('License'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/License/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a license by ID')]
    public function deleteLicense(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('License'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Supplier', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search suppliers',
        parameters: [self::PARAMETER_RSQL_FILTER],
        responses: [
            ['schema' => 'Supplier[]']
        ]
    )]
    public function searchSuppliers(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Supplier'), $request->getParameters());
    }

    #[Route(path: '/Supplier/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a supplier by ID',
        responses: [
            ['schema' => 'Supplier']
        ]
    )]
    public function getSupplier(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('Supplier'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Supplier', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new supplier', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Supplier',
        ]
    ])]
    public function createSupplier(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('Supplier'), $request->getParameters(), [self::class, 'getSupplier']);
    }

    #[Route(path: '/Supplier/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a supplier by ID',
        parameters: [
            [
                'name' => '_',
                'location' => Doc\Parameter::LOCATION_BODY,
                'type' => Doc\Schema::TYPE_OBJECT,
                'schema' => 'Supplier',
            ]
        ],
        responses: [
            ['schema' => 'Supplier']
        ]
    )]
    public function updateSupplier(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('Supplier'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Supplier/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a supplier by ID')]
    public function deleteSupplier(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('Supplier'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Contact', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search contacts',
        parameters: [self::PARAMETER_RSQL_FILTER],
        responses: [
            ['schema' => 'Contact[]']
        ]
    )]
    public function searchContacts(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Contact'), $request->getParameters());
    }

    #[Route(path: '/Contact/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a contact by ID',
        responses: [
            ['schema' => 'Contact']
        ]
    )]
    public function getContact(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('Contact'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Contact', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new contact', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Contact',
        ]
    ])]
    public function createContact(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('Contact'), $request->getParameters(), [self::class, 'getContact']);
    }

    #[Route(path: '/Contact/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a contact by ID',
        responses: [
            ['schema' => 'Contact']
        ]
    )]
    public function updateContact(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('Contact'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Contact/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a contact by ID')]
    public function deleteContact(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('Contact'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Contract', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search contracts',
        parameters: [self::PARAMETER_RSQL_FILTER],
        responses: [
            ['schema' => 'Contract[]']
        ]
    )]
    public function searchContracts(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Contract'), $request->getParameters());
    }

    #[Route(path: '/Contract/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a contract by ID',
        responses: [
            ['schema' => 'Contract']
        ]
    )]
    public function getContract(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('Contract'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Contract', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new contract', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Contract',
        ]
    ])]
    public function createContract(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('Contract'), $request->getParameters(), [self::class, 'getContract']);
    }

    #[Route(path: '/Contract/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a contract by ID',
        responses: [
            ['schema' => 'Contract']
        ]
    )]
    public function updateContract(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('Contract'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Contract/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a contract by ID')]
    public function deleteContract(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('Contract'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Document', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search documents',
        parameters: [self::PARAMETER_RSQL_FILTER],
        responses: [
            ['schema' => 'Document[]']
        ]
    )]
    public function searchDocuments(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Document'), $request->getParameters());
    }

    #[Route(path: '/Document/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a document by ID. If the Accept header is set to application/octet-stream, the file will be returned. Otherwise, the document metadata will be returned.',
        responses: [
            ['schema' => 'Document']
        ]
    )]
    public function getDocument(Request $request): Response
    {
        if ($request->hasHeader('Accept') && $request->getHeaderLine('Accept') === 'application/octet-stream') {
            // User is requesting the actual file
            $document = new Document();
            if ($document->getFromDB($request->getAttribute('id'))) {
                if ($document->canViewFile()) {
                    return $document->send(null, true);
                }
                return self::getAccessDeniedErrorResponse();
            }
            return self::getNotFoundErrorResponse();
        }
        return Search::getOneBySchema($this->getKnownSchema('Document'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Document', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new document', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Document',
        ]
    ])]
    public function createDocument(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('Document'), $request->getParameters(), [self::class, 'getDocument']);
    }

    #[Route(path: '/Document/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a document by ID',
        responses: [
            ['schema' => 'Document']
        ]
    )]
    public function updateDocument(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('Document'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Document/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a document by ID')]
    public function deleteDocument(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('Document'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Line', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search lines',
        parameters: [self::PARAMETER_RSQL_FILTER],
        responses: [
            ['schema' => 'Line[]']
        ]
    )]
    public function searchLines(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Line'), $request->getParameters());
    }

    #[Route(path: '/Line/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a line by ID',
        responses: [
            ['schema' => 'Line']
        ]
    )]
    public function getLine(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('Line'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Line', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new line', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Line',
        ]
    ])]
    public function createLine(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('Line'), $request->getParameters(), [self::class, 'getLine']);
    }

    #[Route(path: '/Line/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a line by ID',
        responses: [
            ['schema' => 'Line']
        ]
    )]
    public function updateLine(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('Line'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Line/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a line by ID')]
    public function deleteLine(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('Line'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Certificate', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search certificates',
        parameters: [self::PARAMETER_RSQL_FILTER],
        responses: [
            ['schema' => 'Certificate[]']
        ]
    )]
    public function searchCertificates(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Certificate'), $request->getParameters());
    }

    #[Route(path: '/Certificate/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a certificate by ID',
        responses: [
            ['schema' => 'Certificate']
        ]
    )]
    public function getCertificate(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('Certificate'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Certificate', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new certificate', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Certificate',
        ]
    ])]
    public function createCertificate(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('Certificate'), $request->getParameters(), [self::class, 'getCertificate']);
    }

    #[Route(path: '/Certificate/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a certificate by ID',
        responses: [
            ['schema' => 'Certificate']
        ]
    )]
    public function updateCertificate(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('Certificate'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Certificate/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a certificate by ID')]
    public function deleteCertificate(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('Certificate'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/DataCenter', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search data centers',
        parameters: [self::PARAMETER_RSQL_FILTER],
        responses: [
            ['schema' => 'DataCenter[]']
        ]
    )]
    public function searchDatacenters(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('DataCenter'), $request->getParameters());
    }

    #[Route(path: '/DataCenter/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a data center by ID',
        responses: [
            ['schema' => 'DataCenter']
        ]
    )]
    public function getDataCenter(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('DataCenter'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/DataCenter', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new data center', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'DataCenter',
        ]
    ])]
    public function createDataCenter(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('DataCenter'), $request->getParameters(), [self::class, 'getDataCenter']);
    }

    #[Route(path: '/DataCenter/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a data center by ID',
        responses: [
            ['schema' => 'DataCenter']
        ]
    )]
    public function updateDataCenter(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('DataCenter'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/DataCenter/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a data center by ID')]
    public function deleteDataCenter(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('DataCenter'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Cluster', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search clusters',
        parameters: [self::PARAMETER_RSQL_FILTER],
        responses: [
            ['schema' => 'Cluster[]']
        ]
    )]
    public function searchClusters(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Cluster'), $request->getParameters());
    }

    #[Route(path: '/Cluster/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a cluster by ID',
        responses: [
            ['schema' => 'Cluster']
        ]
    )]
    public function getCluster(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('Cluster'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Cluster', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new cluster', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Cluster',
        ]
    ])]
    public function createCluster(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('Cluster'), $request->getParameters(), [self::class, 'getCluster']);
    }

    #[Route(path: '/Cluster/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a cluster by ID',
        responses: [
            ['schema' => 'Cluster']
        ]
    )]
    public function updateCluster(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('Cluster'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Cluster/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a cluster by ID')]
    public function deleteCluster(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('Cluster'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Domain', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search domains',
        parameters: [self::PARAMETER_RSQL_FILTER],
        responses: [
            ['schema' => 'Domain[]']
        ]
    )]
    public function searchDomains(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Domain'), $request->getParameters());
    }

    #[Route(path: '/Domain/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a domain by ID',
        responses: [
            ['schema' => 'Domain']
        ]
    )]
    public function getDomain(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('Domain'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Domain', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new domain', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Domain',
        ]
    ])]
    public function createDomain(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('Domain'), $request->getParameters(), [self::class, 'getDomain']);
    }

    #[Route(path: '/Domain/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a domain by ID',
        responses: [
            ['schema' => 'Domain']
        ]
    )]
    public function updateDomain(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('Domain'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Domain/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a domain by ID')]
    public function deleteDomain(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('Domain'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Appliance', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search appliances',
        parameters: [self::PARAMETER_RSQL_FILTER],
        responses: [
            ['schema' => 'Appliance[]']
        ]
    )]
    public function searchAppliances(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Appliance'), $request->getParameters());
    }

    #[Route(path: '/Appliance/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a appliance by ID',
        responses: [
            ['schema' => 'Appliance']
        ]
    )]
    public function getAppliance(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('Appliance'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Appliance', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new appliance', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Appliance',
        ]
    ])]
    public function createAppliance(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('Appliance'), $request->getParameters(), [self::class, 'getAppliance']);
    }

    #[Route(path: '/Appliance/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a appliance by ID',
        responses: [
            ['schema' => 'Appliance']
        ]
    )]
    public function updateAppliance(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('Appliance'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Appliance/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a appliance by ID')]
    public function deleteAppliance(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('Appliance'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Database', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'List or search databases',
        parameters: [self::PARAMETER_RSQL_FILTER],
        responses: [
            ['schema' => 'Database[]']
        ]
    )]
    public function searchDatabases(Request $request): Response
    {
        return Search::searchBySchema($this->getKnownSchema('Database'), $request->getParameters());
    }

    #[Route(path: '/Database/{id}', methods: ['GET'], requirements: ['id' => '\d+'], middlewares: [ResultFormatterMiddleware::class])]
    #[Doc\Route(
        description: 'Get a database by ID',
        responses: [
            ['schema' => 'Database']
        ]
    )]
    public function getDatabase(Request $request): Response
    {
        return Search::getOneBySchema($this->getKnownSchema('Database'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Database', methods: ['POST'])]
    #[Doc\Route(description: 'Create a new database', parameters: [
        [
            'name' => '_',
            'location' => Doc\Parameter::LOCATION_BODY,
            'type' => Doc\Schema::TYPE_OBJECT,
            'schema' => 'Database',
        ]
    ])]
    public function createDatabase(Request $request): Response
    {
        return Search::createBySchema($this->getKnownSchema('Database'), $request->getParameters(), [self::class, 'getDatabase']);
    }

    #[Route(path: '/Database/{id}', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Doc\Route(
        description: 'Update a database by ID',
        responses: [
            ['schema' => 'Database']
        ]
    )]
    public function updateDatabase(Request $request): Response
    {
        return Search::updateBySchema($this->getKnownSchema('Database'), $request->getAttributes(), $request->getParameters());
    }

    #[Route(path: '/Database/{id}', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[Doc\Route(description: 'Delete a database by ID')]
    public function deleteDatabase(Request $request): Response
    {
        return Search::deleteBySchema($this->getKnownSchema('Database'), $request->getAttributes(), $request->getParameters());
    }
}
