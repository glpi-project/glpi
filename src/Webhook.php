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

use Glpi\Api\HL\Controller\AdministrationController;
use Glpi\Api\HL\Controller\AssetController;
use Glpi\Api\HL\Controller\CoreController;
use Glpi\Api\HL\Controller\ITILController;
use Glpi\Api\HL\Controller\ManagementController;
use Glpi\Api\HL\Router;
use Glpi\Http\Request;
use Glpi\Application\View\TemplateRenderer;
use GuzzleHttp\Client as Guzzle_Client;

class Webhook extends CommonDBTM
{
    use Glpi\Features\Clonable;


    public static $rightname         = 'config';


    // From CommonDBTM
    public $dohistory                = true;


    public static $undisclosedFields = [
        'secret'
    ];


    public function getCloneRelations(): array
    {
        return [
            Notepad::class,
        ];
    }


    public static function getTypeName($nb = 0)
    {
        return _n('Webhook', 'Webhooks', $nb);
    }


    public static function canCreate()
    {
        return static::canUpdate();
    }


    public static function canPurge()
    {
        return static::canUpdate();
    }

    public static function canDelete()
    {
        return static::canUpdate();
    }


    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong)
         ->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public function rawSearchOptions()
    {

        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'searchtype'         => [
                'equals',
                'notequals'
            ]
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'event',
            'name'               => _n('Event', 'Events', 1),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'additionalfields'   => [
                'itemtype'
            ],
            'searchtype'         => [
                'equals',
                'notequals'
            ]
        ];

        return $tab;
    }


    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'itemtype':
                if (isset($values[$field]) && class_exists($values[$field])) {
                    return $values[$field]::getTypeName(0);
                }
                break;
            case 'event':
                if (isset($values['itemtype']) && !empty($values['itemtype'])) {
                    $label = NotificationEvent::getEventName($values['itemtype'], $values[$field]);
                    if ($label == NOT_AVAILABLE) {
                        return self::getDefaultEventsListLabel($values[$field]);
                    } else {
                        return $label;
                    }
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }


    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'itemtype':
                return Dropdown::showFromArray(
                    $name,
                    self::getGlpiItemtypes(),
                    [
                        'display'             => false,
                        'display_emptychoice' => true,
                        'value'               => $values[$field],
                    ]
                );
            case 'event':
                $recursive_search = function ($list_itemtype) use (&$recursive_search) {
                    $events = [];
                    foreach ($list_itemtype as $itemtype => $itemtype_label) {
                        if (is_array($itemtype_label)) {
                            $events += $recursive_search($itemtype_label);
                        } else {
                            if (isset($itemtype) && class_exists($itemtype)) {
                                $target = NotificationTarget::getInstanceByType($itemtype);
                                if ($target) {
                                    $events[$itemtype::getTypeName(0)] = $target->getAllEvents();
                                } else {
                                    //return standard CRUD
                                    $events[$itemtype::getTypeName(0)] = self::getDefaultEventsList();
                                }
                            }
                        }
                    }
                    return $events;
                };

                $events = $recursive_search(self::getGlpiItemtypes());
                return Dropdown::showFromArray(
                    $name,
                    $events,
                    [
                        'display'             => false,
                        'display_emptychoice' => true,
                        'value'               => $values[$field],
                    ]
                );
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
    * Return a list of GLPI events switch itemtype.
    *
    * @return array
    */
    public static function getGlpiEventsList(mixed $itemtype): array
    {
        if (isset($itemtype) && class_exists($itemtype)) {
            return self::getDefaultEventsList();
        } else {
            return [];
        }
    }

    /**
    * Return a list of default events.
    *
    * @return array
    */
    public static function getDefaultEventsList(): array
    {
        return [
            'new' => __("New"),
            'update' => __('Update'),
            'delete' => __("Delete"),
        ];
    }

    /**
    * Return default event name.
    *
    * @return string
    */
    public static function getDefaultEventsListLabel($event_name): string
    {
        $events = [
            'new' => __("New"),
            'update' => __('Update'),
            'delete' => __("Delete"),
        ];

        if (isset($events[$event_name])) {
            return $events[$event_name];
        } else {
            return NOT_AVAILABLE;
        }
    }


    /**
    * Return a list of default events.
    *
    * @return array
    */
    public static function getHttpMethod(): array
    {
        return [
            'post'      => 'POST',
            'get'       => 'GET',
            'update'    => 'UPDATE',
            'patch'     => 'PATCH',
        ];
    }

    /**
    * Return status icon
    *
    * @return string
    */
    public static function getStatusIcon($status): string
    {
        if ($status) {
            return '<i class="fa-solid fa-triangle-exclamation fa-beat fa-lg" style="color: #ff0000;"></i>';
        } else {
            return '<i class="fa-solid fa-circle-check fa-beat fa-lg" style="color: #36d601;"></i>';
        }
    }

    /**
    * Return a list of GLPI itemtypes availabel through HL API.
    *
    * @return array
    */
    public static function getGlpiItemtypes(): array
    {
        $assets_itemtypes = [];
        $router = Router::getInstance();
        foreach ($router->getControllers() as $controller_class) {
            if (get_class($controller_class) == CoreController::class) {
                continue;
            }

            switch (get_class($controller_class)) {
                case AssetController::class:
                    $familly = _n('Asset', 'Assets', Session::getPluralNumber());
                    break;
                case ITILController::class:
                    $familly = __('Assistance');
                    break;
                case AdministrationController::class:
                    $familly = __('Administration');
                    break;
                case ManagementController::class:
                    $familly = __('Management');
                    break;
            }

            foreach ($controller_class::getKnownSchemas() as $available_itemtype => $value) {
                //manage namespaced itemtype ex: Glpi\Socket
                $itemtype = $available_itemtype;
                if (isset($value['x-itemtype'])) {
                    $itemtype = $value['x-itemtype'];
                }

                if (class_exists($itemtype)) {
                    $assets_itemtypes[$familly][$itemtype] = $itemtype::getTypeName(0);
                }
            }

            //add subItem Task / Followup / Solution / Document / Validation / Log for CommonITILObject
            if (get_class($controller_class) == ITILController::class) {
                $assets_itemtypes[__('Sub item assistance')] = self::getSubItemForAssistance();
            }
        }
        return $assets_itemtypes;
    }

    public static function getSubItemForAssistance(): array
    {
        $sub_item = [
            'ITILFollowup' => ITILFollowup::getTypeName(0),
            'Document_Item' => Document_Item::getTypeName(0),
            'ITILSolution' => ITILSolution::getTypeName(0),
        ];

        $itil_types = [Ticket::class, Change::class, Problem::class];
        foreach ($itil_types as $itil_type) {
            if ($itil_type::getValidationClassInstance() !== null) {
                $sub_item[$itil_type::getValidationClassInstance()::class] = $itil_type::getValidationClassInstance()::class::getTypeName(0);
            }

            if ($itil_type::getTaskClass() !== null) {
                $sub_item[$itil_type::getTaskClass()] = $itil_type::getTaskClass()::getTypeName(0);
            }
        }
        return $sub_item;
    }

    public function callAPI(string $path, string $event, string $itemtype)
    {
        if ($path != '/') {
            $router = Router::getInstance();
            $path = rtrim($path, '/');
            $request = new Request('GET', $path);
            $request = $request->withHeader('Glpi-Session-Token', $_SESSION['valid_id']);
            $response = $router->handleRequest($request);
            $itemtype_data = json_decode($response->getBody()->getContents(), true);
            $data = [
                'event' => $event,
                strtolower($itemtype) => $itemtype_data
            ];
            echo json_encode($data, JSON_PRETTY_PRINT);
        } else {
            echo __('No route found for this itemtype');
        }
    }

    public function getPathByItem(CommonDBTM $item): string
    {

        $is_timeline_item = false;
        $foreign_key = null;
        //object from timeline need to be computed
        //see ITILController->getSubitemFriendlyType
        switch ($item::getType()) {
            case 'ITILFollowup':
                $itemtype = 'Followup';
                $is_timeline_item = true;
                break;
            case 'Document_Item':
                $itemtype = 'Document';
                $is_timeline_item = true;
                break;
            case 'ITILSolution':
                $itemtype = 'Solution';
                $is_timeline_item = true;
                break;
            case 'TicketTask':
                $foreign_key = "tickets_id";
                $itemtype = 'Task';
                $is_timeline_item = true;
                break;
            case 'ChangeTask':
                $foreign_key = "changes_task";
                $itemtype = 'Task';
                $is_timeline_item = true;
                break;
            case 'ProblemTask':
                $foreign_key = "problems_task";
                $itemtype = 'Task';
                $is_timeline_item = true;
                break;
            case 'ITILValidation':
                $itemtype = 'Validation';
                $is_timeline_item = true;
                break;
            default:
                $itemtype = $item::getType();
                break;
        }

        $path = '/';
        $router = Router::getInstance();

        //use right function to compute path if timeline item or not
        $function = $is_timeline_item ? 'getTimelineItem' : 'search';

        //prepare arg to compute API path
        //default arg ex: Computer
        // - itemtype => computer
        // - id       => Computer ID
        $arg = [
            'itemtype' => $itemtype,
            'id' => $item->getID()
        ];

        if ($is_timeline_item) {
            //for timeline item ex : Followup
            // - itemtype     => Ticket
            // - id           => Ticket ID
            // - subitem_type => Followup
            // - subitem_id   => followup ID
            $arg = [
                'subitem_type'  => $itemtype ,
                'subitem_id'    => $item->getID()
            ];

            //handle CommonITILTask case with foreign key column (ex: tickets_id for TicketTask)
            //instead of tems_id / itemtype columns for ITILFollowup / ITILValidation / Document_Item / ITILSolution
            if ($foreign_key != null) {
                $arg['itemtype'] = getItemtypeForForeignKeyField($foreign_key);
                $arg['id']       = $item->fields[$foreign_key];
            } else {
                $arg['itemtype'] = $item->fields['itemtype'];
                $arg['id']       = $item->fields['items_id'];
            }
        }

        //go through the controllers to find the one corresponding to the itemtype
        foreach ($router->getControllers() as $controller_class) {
            //itemtype can be found from standard key or from subkey (x-itemtype)
            // standard key
            //[Computer] => Array
            //(
            //    [type] => object
            //)

            // subkey
            //[RecurringTicket] => Array
            //(
            //    [x-itemtype] => TicketRecurrent
            //    [type] => object
            //)

            foreach ($controller_class::getKnownSchemas() as $key => $value) {
                $doIt = false;
                if ($key == $itemtype) {
                    $doIt = true;
                } elseif (isset($value['x-itemtype']) && $value['x-itemtype'] == $itemtype) {
                    //replace by internal API key (ex TicketRecurrent : => RecurringTicket)
                    $arg['itemtype'] = $key;
                    $doIt = true;
                }

                //compute path with arg
                if ($doIt) {
                    Toolbox::logDebug($arg);
                    $path = $controller_class::getAPIPathForRouteFunction(
                        get_class($controller_class),
                        $function,
                        $arg
                    );
                    if ($path != '/') {
                        return $path;
                    }
                }
            }
        }
        return $path;
    }

    public function showForm($id, array $options = [])
    {
        if (!empty($id)) {
            $this->getFromDB($id);

            //validate CRA if needed
            if (isset($this->fields['use_cra_challenge']) && $this->fields['use_cra_challenge']) {
                $response = self::validateCRAChallenge($this->fields['url'], 'validate_cra_challenge', $this->fields['secret']);
                if (!$response['status']) {
                    $this->fields['is_cra_challenge_valid'] = false;
                    $this->update($this->fields);
                }
            }
        } else {
            $this->getEmpty();
        }
        $this->initForm($id, $options);

        TemplateRenderer::getInstance()->display('pages/setup/webhook/webhook.html.twig', [
            'item' => $this,
            'secret_already_used' => $this->getWebhookWithSameSecret()
        ]);
        return true;
    }


    /**
     * Check if secret is already use dby another webhook
     * @return array of webhook using same secret
     */
    public function getWebhookWithSameSecret()
    {

        if ($this->isNewID($this->fields['id'])) {
            return [];
        } else {
            //check if secret is already use by another webhook
            $webhook = new self();
            $data = $webhook->find([
                'secret' => $this->fields['secret'],
                'NOT' => [
                    'id' => $this->fields['id']
                ],
            ]);

            $already_use = [];
            foreach ($data as $webhook_value) {
                $webhook->getFromDB($webhook_value['id']);
                $already_use[$webhook_value['id']] = [
                    'link' => $webhook->getLink()
                ];
            }
            return $already_use;
        }
    }


    public static function signWebhookRequest($body, $secret)
    {
        $body = json_encode($body);
        $timestamp = time();
        $signature = self::getSignature($body, $secret);
        header("X-GLPI-signature: " . $signature);
        header("X-GLPI-timestamp: " . $timestamp);
        echo $body;
    }

    public static function getSignature($data, $secret)
    {
        $signature = hash_hmac('sha256', $data, $secret);
        return $signature;
    }

    /**
     * Validate Challenge Response Answer
     *
     * @param string $url
     * @param string $body
     * @param string $secret
     *
     * @return boolean
     */
    public static function validateCRAChallenge($url, $body, $secret): array
    {
        global $CFG_GLPI;

        $challenge_response = [];
        $options = [
            'base_uri'        => $url,
            'connect_timeout' => 1,
        ];

        // add proxy string if configured in glpi
        if (!empty($CFG_GLPI["proxy_name"])) {
            $proxy_creds      = !empty($CFG_GLPI["proxy_user"])
            ? $CFG_GLPI["proxy_user"] . ":" . (new GLPIKey())->decrypt($CFG_GLPI["proxy_passwd"]) . "@"
            : "";
            $proxy_string     = "http://{$proxy_creds}" . $CFG_GLPI['proxy_name'] . ":" . $CFG_GLPI['proxy_port'];
            $options['proxy'] = $proxy_string;
        }

        // init guzzle client with base options
        $httpClient = new Guzzle_Client($options);
        try {
            //prepare query / body
            $response = $httpClient->request('GET', '', [
                'query' => ['crc_token' => self::getSignature($body, $secret)],
            ]);

            if ($response->getStatusCode() == 200 && $response->getBody()) {
                $response_challenge = $response->getBody()->getContents();
                //check response
                if ($response_challenge == hash_hmac('sha256', self::getSignature($body, $secret), $secret)) {
                    $challenge_response = [
                        'status' => true,
                        'message' => __('Challenge–response authentication validated'),
                    ];
                } else {
                    $challenge_response = [
                        'status' => false,
                        'message' => __('Challenge–response authentication failed, the answer returned by target is different')
                    ];
                }
            } else {
                $challenge_response = [
                    'status' => false,
                    'message' => $response->getReasonPhrase()
                ];
            }
        } catch (\GuzzleHttp\Exception\ClientException | \GuzzleHttp\Exception\RequestException $e) {
            $challenge_response['status'] = false;
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $challenge_response['message'] = $response->getReasonPhrase();
                $challenge_response['status_code'] = $response->getStatusCode();
            } else {
                $challenge_response['message'] = $e->getMessage();
                $challenge_response['status_code'] = 503;
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $challenge_response['status'] = false;
            $challenge_response['message'] = $e->getMessage();
        }

        return $challenge_response;
    }


    public static function send(array $data)
    {
        global $CFG_GLPI, $DB;

        $processed = [];
        foreach ($data as $row) {
        }
    }

    public function prepareInputForAdd($input)
    {
        return $this->handleInput($input);
    }


    public function prepareInputForUpdate($input)
    {
        return $this->handleInput($input);
    }

    public static function generateRandomSecret()
    {
        return Toolbox::getRandomString(40);
    }


    public function handleInput($input)
    {
        //empty choice (0) update to empty ('')
        if (isset($input["itemtype"]) && !$input["itemtype"]) {
            $input["itemtype"] = '';
        }

        if (isset($input["event"]) && !$input["event"]) {
            $input["event"] = '';
        }

        if (empty($input['secret']) || isset($input['_regenerate_secret'])) {
            //generate random secret if needed or if empty
            $input['secret'] = self::generateRandomSecret();
        }

        if (isset($input['use_cra_challenge'])) {
            $input['use_cra_challenge'] = (int)$input['use_cra_challenge'];
        }

        return $input;
    }


    public function post_getEmpty()
    {
        $this->fields['is_cra_challenge_valid']                        = 0;
    }

    public static function getMenuContent()
    {
        $menu = [];
        if (Webhook::canView()) {
            $menu = [
                'title'    => _n('Webhook', 'Webhooks', Session::getPluralNumber()),
                'page'     => '/front/webhook.php',
                'icon'     => static::getIcon(),
            ];
            $menu['links']['search'] = '/front/webhook.php';
            $menu['links']['add'] = '/front/webhook.form.php';

            $mp_icon     = WebhookTest::getIcon();
            $mp_title    = WebhookTest::getTypeName();
            $webhook_test = "<i class='$mp_icon pointer' title='$mp_title'></i><span class='d-none d-xxl-block'>$mp_title</span>";
            $menu['links'][$webhook_test] = '/front/webhooktest.php';

            $mp_icon     = QueuedWebhook::getIcon();
            $mp_title    = QueuedWebhook::getTypeName();
            $queuedwebhook = "<i class='$mp_icon pointer' title='$mp_title'></i><span class='d-none d-xxl-block'>$mp_title</span>";
            $menu['links'][$queuedwebhook] = '/front/queuedwebhook.php';
        }
        if (count($menu)) {
            return $menu;
        }
        return false;
    }


    public static function getIcon()
    {
        return "ti ti-webhook";
    }
}
