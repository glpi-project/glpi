<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\CookieAuthMiddleware;
use Glpi\Api\HL\OpenAPIGenerator;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\Router;
use Glpi\Api\HL\RouteVersion;
use Glpi\Application\Environment;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Error\ErrorHandler;
use Glpi\Http\JSONResponse;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Glpi\OAuth\Server;
use Glpi\System\Status\StatusChecker;
use Glpi\Toolbox\MarkdownRenderer;
use Html;
use JsonException;
use League\OAuth2\Server\Exception\OAuthServerException;
use Session;
use Throwable;
use Transfer;
use User;

use function Safe\file_get_contents;
use function Safe\preg_replace;
use function Safe\strtotime;

final class CoreController extends AbstractController
{
    public static function getRawKnownSchemas(): array
    {
        return [
            'Session' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'current_time' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_DATE_TIME],
                    'user_id' => ['type' => Doc\Schema::TYPE_INTEGER],
                    'use_mode' => ['type' => Doc\Schema::TYPE_INTEGER],
                    'friendly_name' => ['type' => Doc\Schema::TYPE_STRING],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'real_name' => ['type' => Doc\Schema::TYPE_STRING],
                    'first_name' => ['type' => Doc\Schema::TYPE_STRING],
                    'default_entity' => ['type' => Doc\Schema::TYPE_INTEGER],
                    'profiles' => ['type' => Doc\Schema::TYPE_ARRAY, 'items' => ['type' => Doc\Schema::TYPE_INTEGER]],
                    'active_entities' => ['type' => Doc\Schema::TYPE_ARRAY, 'items' => ['type' => Doc\Schema::TYPE_INTEGER]],
                    'active_profile' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'properties' => [
                            'id' => ['type' => Doc\Schema::TYPE_INTEGER],
                            'name' => ['type' => Doc\Schema::TYPE_STRING],
                            'interface' => ['type' => Doc\Schema::TYPE_STRING],
                        ],
                    ],
                    'active_entity' => [
                        'type' => Doc\Schema::TYPE_OBJECT,
                        'properties' => [
                            'id' => ['type' => Doc\Schema::TYPE_INTEGER],
                            'short_name' => ['type' => Doc\Schema::TYPE_STRING],
                            'complete_name' => ['type' => Doc\Schema::TYPE_STRING],
                            'recursive' => ['type' => Doc\Schema::TYPE_INTEGER],
                        ],
                    ],
                ],
            ],
            'EntityTransferRecord' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'itemtype' => ['type' => Doc\Schema::TYPE_STRING],
                    'items_id' => ['type' => Doc\Schema::TYPE_INTEGER],
                    'entity' => ['type' => Doc\Schema::TYPE_INTEGER],
                    'options' => ['type' => Doc\Schema::TYPE_OBJECT],
                ],
            ],
            'APIInformation' => [
                'x-version-introduced' => '2.0',
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'message' => ['type' => Doc\Schema::TYPE_STRING],
                    'api_versions' => [
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'items' => [
                            'type' => Doc\Schema::TYPE_OBJECT,
                            'properties' => [
                                'api_version' => ['type' => Doc\Schema::TYPE_STRING],
                                'version' => ['type' => Doc\Schema::TYPE_STRING],
                                'endpoint' => ['type' => Doc\Schema::TYPE_STRING],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[Route(path: '/', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [CookieAuthMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'API Homepage. Displays the available API versions and a list of available routes. When logged in, more routes are displayed.',
        responses: [
            new Doc\Response(
                schema: new Doc\SchemaReference('APIInformation'),
                description: 'API information',
            ),
        ]
    )]
    public function index(Request $request): Response
    {
        $data = [
            'message' => 'Welcome to GLPI API',
            'api_versions' => Router::getAPIVersions(),
        ];

        return new JSONResponse($data);
    }

    #[Route(path: '/doc', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [CookieAuthMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'Displays the API documentation as a Swagger UI HTML page.')]
    public function showDocumentation(Request $request): Response
    {
        global $CFG_GLPI;

        $swagger_content = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>GLPI API Documentation</title>';
        $swagger_content .= Html::script('/lib/swagger-ui.js');
        $swagger_content .= Html::css('/lib/swagger-ui.css');
        $favicon = Html::getPrefixedUrl('/pics/favicon.ico');
        $doc_json_path = $CFG_GLPI['root_doc'] . '/api.php/doc.json';
        $swagger_content .= <<<HTML
        <link rel="shortcut icon" type="images/x-icon" href="$favicon" />
        </head>
        <body>
            <div id="swagger-ui"></div>
            <script>
                const ui = window.SwaggerUIBundle({
                    url: '{$doc_json_path}',
                    dom_id: '#swagger-ui',
                    docExpansion: 'none',
                    validatorUrl: 'none',
                    filter: true,
                    showExtensions: true,
                    oauth2RedirectUrl: '{$CFG_GLPI['root_doc']}/api.php/swagger-oauth-redirect',
                    // Sort operations by name and then by method
                    operationsSorter: (a, b) => {
                        const method_order = ['get', 'post', 'put', 'patch', 'delete'];
                        if (a.get('path') === b.get('path')) {
                            return method_order.indexOf(a.get('method')) - method_order.indexOf(b.get('method'));
                        }
                        return a.get('path').localeCompare(b.get('path'));
                    },
                    tagsSorter: (a, b) => a.localeCompare(b),
                });
            </script>
        </body>
HTML;

        // Must allow caching since it is a large script, and the documentation won't update often (possibly when plugins change)
        return new Response(200, [
            'Content-Type' => 'text/html',
            'Cache-Control' => 'public, max-age=86400',
        ], $swagger_content);
    }

    #[Route(path: '/doc.json', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [CookieAuthMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'Get the OpenAPI JSON schema.')]
    public function getOpenAPISchema(Request $request): Response
    {
        $generator = new OpenAPIGenerator(Router::getInstance(), $this->getAPIVersion($request));
        $schema = $generator->getSchema();
        return new JSONResponse($schema);
    }

    #[Route(path: '/getting-started', methods: ['GET'], security_level: Route::SECURITY_NONE, middlewares: [CookieAuthMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'Displays the general API documentation to get started.')]
    public function showGettingStarted(Request $request): Response
    {
        $documentation_file = GLPI_ROOT . '/resources/api_doc.MD';
        $documentation = file_get_contents($documentation_file);

        $md = new MarkdownRenderer();
        $html_docs = $md->render($documentation);

        // Some very basic replacements to make the HTML look better (Use Tabler/Bootstrap classes)
        // Place tables in a flex column where on lg screens, they take 75% of the width and on xl screens, they take 50% of the width
        $html_docs = preg_replace(
            '/<table>(.*?)<\/table>/s',
            '<div class="d-flex flex-column"><div class="col-12 col-md-8 col-xl-6"><table class="table table-bordered">$1</table></div></div>',
            $html_docs
        );

        $content = Html::includeHeader(
            title: __('API Getting Started'),
            display: false,
        );

        $content .= '<body class="documentation-page"><div id="page"><div class="documentation documentation-large">';
        $content .= $html_docs;
        $content .= '</div></div></body>';

        return new Response(200, [
            'Content-Type' => 'text/html',
        ], $content);
    }

    private function getAllowedMethodsForMatchedRoute(Request $request): array
    {
        // Possible methods excluding OPTIONS
        $methods = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'];

        $allowed_methods = [];
        $router = Router::getInstance();
        foreach ($methods as $method) {
            $route = $router->match($request->withMethod($method));
            if ($route !== null) {
                // Filter out this route
                $controller = $route->getController();
                $controller_method = $route->getMethod();
                if ($controller === self::class && $controller_method->getShortName() === 'defaultRoute') {
                    continue;
                }
                $allowed_methods[] = $method;
            }
        }
        sort($allowed_methods);
        return $allowed_methods;
    }

    #[Route('/{req}', ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'], ['req' => '.*'], -1)]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'A fallback for when no other endpoint matches the request. A 404 error will be shown.',
        methods: ['GET', 'POST', 'PATCH', 'PUT', "DELETE"],
        responses: [
            new Doc\Response(
                schema: null,
                description: 'Never returned',
                status_code: 200,
            ),
            new Doc\Response(
                schema: null,
                description: 'No route found for the requested path',
                status_code: 404,
            ),
        ]
    )]
    public function defaultRoute(Request $request): Response
    {
        return new JSONResponse(null, 404);
    }

    #[Route(path: '/{req}', methods: ['OPTIONS'], requirements: ['req' => '.*'], priority: -1, security_level: Route::SECURITY_NONE)]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'A global route that enables the OPTIONS method on all endpoints. This responds with an Accept header indicating which methods are allowed.',
        methods: ['OPTIONS']
    )]
    public function defaultOptionsRoute(Request $request): Response
    {
        $authenticated = Session::getLoginUserID() !== false;
        $allowed_methods = $authenticated ? $this->getAllowedMethodsForMatchedRoute($request) : ['GET', 'POST', 'PATCH', 'PUT', "DELETE"];
        if (count($allowed_methods) === 0) {
            return new JSONResponse(null, 404);
        }
        $response_headers = [];
        if ($authenticated) {
            $response_headers['Allow'] = $allowed_methods;
        }
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
            $response_headers['Access-Control-Allow-Methods'] = $allowed_methods;
        }
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            $response_headers['Access-Control-Allow-Headers'] = [
                'Content-Type', 'Authorization', 'Origin', 'Accept',
                'GLPI-API-Version', 'GLPI-Profile', 'GLPI-Entity', 'GLPI-Entity-Recursive',
                'X-Debug-Mode',
            ];
            if (Environment::get()->shouldEnableExtraDevAndDebugTools()) {
                $response_headers['Access-Control-Allow-Headers'][] = 'XDEBUG_TRIGGER';
            }
        }
        return new JSONResponse(null, 204, $response_headers);
    }

    #[Route(path: '/session', methods: ['GET'], tags: ['Session'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get information about the session',
        responses: [
            new Doc\Response(
                schema: new Doc\SchemaReference('Session'),
                description: 'The session information'
            ),
        ]
    )]
    public function getSession(Request $request): Response
    {
        /** @var array{name: string, default: mixed}[] $allowed_keys_mapping */
        $allowed_keys_mapping = [
            'glpi_currenttime' => [
                'name' => 'current_time',
                'default' => '',
            ],
            'glpiID' => [
                'name' => 'user_id',
                'default' => -1,
            ],
            'glpi_use_mode' => [
                'name' => 'use_mode',
                'default' => Session::NORMAL_MODE,
            ],
            'glpifriendlyname' => [
                'name' => 'friendly_name',
                'default' => '',
            ],
            'glpiname' => [
                'name' => 'name',
                'default' => '',
            ],
            'glpirealname' => [
                'name' => 'real_name',
                'default' => '',
            ],
            'glpifirstname' => [
                'name' => 'first_name',
                'default' => '',
            ],
            'glpidefault_entity' => [
                'name' => 'default_entity',
                'default' => -1,
            ],
            'glpiprofiles' => [
                'name' => 'profiles',
                'default' => [],
            ],
            'glpiactiveentities' => [
                'name' => 'active_entities',
                'default' => [],
            ],
        ];
        $session = [];
        foreach ($allowed_keys_mapping as $key => $new_key) {
            $session[$new_key['name']] = $_SESSION[$key] ?? $new_key['default'];
        }
        // Convert current_time YYYY-MM-DD HH-mm-ss to RFC3339 datetime
        $session['current_time'] = date(DATE_RFC3339, strtotime($session['current_time']));
        $active_profile = $_SESSION['glpiactiveprofile'];
        $session['active_profile'] = [
            'id' => $active_profile['id'],
            'name' => $active_profile['name'],
            'interface' => $active_profile['interface'],
        ];
        $session['active_entity'] = [
            'id' => $_SESSION['glpiactive_entity'],
            'short_name' => $_SESSION['glpiactive_entity_shortname'],
            'complete_name' => $_SESSION['glpiactive_entity_name'],
            'recursive' => $_SESSION['glpiactive_entity_recursive'],
        ];
        return new JSONResponse($session);
    }

    #[Route(path: '/authorize', methods: ['GET', 'POST'], security_level: Route::SECURITY_NONE, tags: ['Session'], middlewares: [CookieAuthMiddleware::class])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'Authorize the API client using the authorization code grant type.')]
    public function authorize(Request $request): Response
    {
        global $CFG_GLPI;
        try {
            $auth_request = Server::getAuthorizationServer()->validateAuthorizationRequest($request);

            $user_id = Session::getLoginUserID();
            if ($user_id === false) {
                // Redirect to login page
                $redirect_params = [
                    'scope'         => implode(' ', array_map(static fn($s) => $s->getIdentifier(), $auth_request->getScopes())),
                    'client_id'     => $auth_request->getClient()->getIdentifier(),
                    'response_type' => 'code',
                    'redirect_uri'  => $auth_request->getRedirectUri(),
                ];
                $redirect_uri = $CFG_GLPI['url_base']
                    . '/api.php/v2'
                    . $this->getAPIPathForRouteFunction(self::class, 'authorize')
                    . '?'
                    . http_build_query($redirect_params);
                return new Response(302, ['Location' => $CFG_GLPI['url_base'] . '/?redirect=' . rawurlencode($redirect_uri)]);
            }
            $user = new \Glpi\OAuth\User();
            $user->setIdentifier($user_id);
            $auth_request->setUser($user);
            if (!$request->hasParameter('accept') && !$request->hasParameter('deny')) {
                // Display the authorization page
                $glpi_user = new User();
                $glpi_user->getFromDB($user_id);
                $authorize_form = TemplateRenderer::getInstance()->render('pages/oauth/authorize.html.twig', [
                    'auth_request' => $auth_request,
                    'scopes' => $auth_request->getScopes(),
                    'client' => $auth_request->getClient(),
                    'user' => $glpi_user,
                ]);
                return new Response(200, ['Content-Type' => 'text/html'], $authorize_form);
            }

            $auth_request->setAuthorizationApproved($request->hasParameter('accept'));
            /** @var Response $response */
            $response = Server::getAuthorizationServer()->completeAuthorizationRequest($auth_request, new Response());
            return $response;
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse(new Response()); // @phpstan-ignore return.type (Response vs ResponseInterface)
        } catch (Throwable $exception) {
            ErrorHandler::logCaughtException($exception);
            return new JSONResponse(null, 500);
        }
    }

    #[Route(path: '/token', methods: ['POST'], security_level: Route::SECURITY_NONE, tags: ['Session'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'Get an OAuth 2.0 token')]
    public function token(Request $request): Response
    {
        try {
            /** @var JSONResponse $response */
            $response = Server::getAuthorizationServer()->respondToAccessTokenRequest($request, new JSONResponse());
            return $response;
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse(new JSONResponse()); // @phpstan-ignore return.type (Response vs ResponseInterface)
        } catch (Throwable $exception) {
            ErrorHandler::logCaughtException($exception);
            return new JSONResponse(null, 500);
        }
    }

    #[Route(path: '/swagger-oauth-redirect', methods: ['GET'], security_level: Route::SECURITY_NONE, tags: ['Session'])]
    #[RouteVersion(introduced: '2.0')]
    public function swaggerOAuthRedirect(Request $request): Response
    {
        $content = file_get_contents(GLPI_ROOT . '/public/lib/swagger-ui-dist/oauth2-redirect.html');
        return new Response(200, ['Content-Type' => 'text/html'], $content);
    }

    #[Route(path: '/status', methods: ['GET'], tags: ['Status'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(description: 'Get a list of all GLPI system status checker services.')]
    public function status(Request $request): Response
    {
        $services = array_keys(StatusChecker::getServices());
        $data = [
            'all' => [
                'href' => '/status/all',
            ],
        ];
        foreach ($services as $service) {
            $data[$service] = [
                'href' => '/status/' . $service,
            ];
        }
        return new JSONResponse($data);
    }

    #[Route(path: '/status/all', methods: ['GET'], security_level: Route::SECURITY_NONE, tags: ['Status'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get the the status of all GLPI system status checker services',
        responses: [
            new Doc\Response(
                schema: new Doc\Schema(
                    type: Doc\Schema::TYPE_ARRAY,
                    items: new Doc\Schema(
                        type: Doc\Schema::TYPE_OBJECT,
                        properties: [
                            'status' => new Doc\Schema(
                                type: Doc\Schema::TYPE_STRING,
                                enum: [StatusChecker::STATUS_OK, StatusChecker::STATUS_WARNING, StatusChecker::STATUS_PROBLEM, StatusChecker::STATUS_NO_DATA],
                            ),
                        ],
                    )
                )
            ),
        ]
    )]
    public function statusAllServices(Request $request): Response
    {
        $show_all = Session::haveRight('config', READ);
        $data = StatusChecker::getServiceStatus(null, !$show_all);
        return new JSONResponse($data);
    }

    #[Route(path: '/status/{service}', methods: ['GET'], requirements: [
        'service' => '[a-zA-Z0-9_]+',
    ], priority: 9, tags: ['Status'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Get the status of a GLPI system status checker service. Use "all" as the service to get the full system status.',
        responses: [
            new Doc\Response(
                schema: new Doc\Schema(
                    type: Doc\Schema::TYPE_OBJECT,
                    properties: [
                        'status' => new Doc\Schema(
                            type: Doc\Schema::TYPE_STRING,
                            enum: [StatusChecker::STATUS_OK, StatusChecker::STATUS_WARNING, StatusChecker::STATUS_PROBLEM, StatusChecker::STATUS_NO_DATA]
                        ),
                    ],
                )
            ),
        ]
    )]
    public function statusByService(Request $request): Response
    {
        $show_all = Session::haveRight('config', READ);
        $service = $request->getAttribute('service');
        $service = strtolower($service);

        $data = StatusChecker::getServiceStatus($service, !$show_all);
        return new JSONResponse($data);
    }

    #[Route(path: '/Transfer', methods: ['POST'])]
    #[RouteVersion(introduced: '2.0')]
    #[Doc\Route(
        description: 'Transfer one or more items to another entity',
        parameters: [
            new Doc\Parameter(
                name: '_',
                schema: new Doc\SchemaReference('EntityTransferRecord[]'),
                location: Doc\Parameter::LOCATION_BODY,
            ),
        ]
    )]
    public function transferEntity(Request $request): Response
    {
        $params = $request->getParameters();
        $transfer = new Transfer();

        $transfer_records = array_filter($params, static fn($param)
            // must have itemtype, items_id and entity keys
            => is_array($param) && isset($param['itemtype'], $param['items_id'], $param['entity']));
        $original_record_count = count($transfer_records);
        // Filter out any records that would transfer to an entity the user doesn't have access to
        $transfer_records = array_filter($transfer_records, static fn($record) => Session::haveAccessToEntity((int) $record['entity']));
        $is_partial_transfer = $original_record_count !== count($transfer_records);

        $controllers = Router::getInstance()->getControllers();
        $schema_mappings = [];
        foreach ($controllers as $controller) {
            $schemas = $controller::getKnownSchemas($this->getAPIVersion($request));
            foreach ($schemas as $schema_name => $schema) {
                if (isset($schema['x-itemtype'])) {
                    $schema_mappings[$schema_name] = $schema['x-itemtype'];
                }
            }
        }

        $transfers_by_entity = [];
        foreach ($transfer_records as $record) {
            $entity = (int) $record['entity'];
            $itemtype = $schema_mappings[$record['itemtype']] ?? $record['itemtype'];
            $items_id = (int) $record['items_id'];
            $options = $record['options'] ?? [];

            try {
                $options_hash = md5(json_encode($options, JSON_THROW_ON_ERROR));
            } catch (JsonException) {
                $options_hash = mt_rand();
            }

            // Group transfers by entity and options hash
            if (!isset($transfers_by_entity[$entity])) {
                $transfers_by_entity[$entity] = [];
            }
            if (!isset($transfers_by_entity[$entity][$options_hash])) {
                $transfers_by_entity[$entity][$options_hash] = [
                    'options' => $options,
                    'items' => [],
                ];
            }
            if (!isset($transfers_by_entity[$entity][$options_hash]['items'][$itemtype])) {
                $transfers_by_entity[$entity][$options_hash]['items'][$itemtype] = [];
            }
            $transfers_by_entity[$entity][$options_hash]['items'][$itemtype][] = $items_id;
        }

        foreach ($transfers_by_entity as $entity => $records) {
            foreach ($records as $opt_group) {
                $transfer->moveItems($opt_group['items'], $entity, $opt_group['options']);
            }
        }

        return new JSONResponse(null, $is_partial_transfer ? 202 : 200);
    }
}
