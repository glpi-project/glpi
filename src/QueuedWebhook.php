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

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

use function Safe\json_decode;
use function Safe\strtotime;

class QueuedWebhook extends CommonDBChild
{
    public static $rightname = 'config';

    public static $itemtype = Webhook::class;
    public static $items_id = 'webhooks_id';

    public static function getTypeName($nb = 0)
    {
        return __('Webhook queue');
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', Webhook::class];
    }

    public static function canCreate(): bool
    {
        // Everybody can create : human and cron
        return Session::getLoginUserID(false);
    }

    public static function canDelete(): bool
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function canUpdate(): bool
    {
        // No standard update is allowed
        return false;
    }

    public static function getForbiddenActionsForMenu()
    {
        return ['add'];
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public function getSpecificMassiveActions($checkitem = null, $is_deleted = false)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin && !$is_deleted) {
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'send'] = _sx('button', 'Send');
        }

        return $actions;
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        /** @var QueuedWebhook $item */
        switch ($ma->getAction()) {
            case 'send':
                foreach ($ids as $id) {
                    if ($item->canEdit($id)) {
                        if ($item::sendById($id)) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    public function prepareInputForAdd($input)
    {
        global $DB;

        if (!isset($input['create_time']) || empty($input['create_time'])) {
            $input['create_time'] = $_SESSION["glpi_currenttime"];
        }
        if (!isset($input['send_time']) || empty($input['send_time'])) {
            $toadd = 0;
            if (isset($input['entities_id'])) {
                $toadd = Entity::getUsedConfig('delay_send_emails', $input['entities_id']);
            }
            if ($toadd > 0) {
                $input['send_time'] = date(
                    "Y-m-d H:i:s",
                    strtotime($_SESSION["glpi_currenttime"])
                    + $toadd * MINUTE_TIMESTAMP
                );
            } else {
                $input['send_time'] = $_SESSION["glpi_currenttime"];
            }
        }
        $input['sent_try'] = 0;

        return $input;
    }

    /**
     * Send webhook in queue
     *
     * @param integer $ID Id
     *
     * @return boolean
     */
    public static function sendById(int $ID): bool
    {
        global $CFG_GLPI;

        $queued_webhook = new self();
        if (!$queued_webhook->getFromDB($ID)) {
            return false;
        }

        $guzzle_options = [
            'timeout' => 5,
        ];

        $webhook = new Webhook();
        if (!$webhook->getFromDB($queued_webhook->fields['webhooks_id'])) {
            return false;
        }

        if (GLPI_WEBHOOK_CRA_MANDATORY || $webhook->fields['use_cra_challenge']) {
            // Send CRA challenge
            $result = $webhook::validateCRAChallenge($queued_webhook->fields['url'], 'validate_cra_challenge', $webhook->fields['secret']);
            if ($result['status'] !== true) {
                Toolbox::logInFile('webhook', "CRA challenge failed for webhook {$webhook->fields['name']} ({$webhook->getID()})");
                return false;
            }
        }

        $bearer_token = null;
        if ($webhook->fields['use_oauth']) {
            // Send OAuth Client Credentials
            $client = Toolbox::getGuzzleClient($guzzle_options);
            try {
                $response = $client->request('POST', $webhook->fields['oauth_url'], [
                    RequestOptions::FORM_PARAMS => [
                        'grant_type' => 'client_credentials',
                        'client_id' => $webhook->fields['clientid'],
                        'client_secret' => $webhook->fields['clientsecret'],
                        'scope' => '',
                    ],
                ]);
                $response = json_decode((string) $response->getBody(), true);
                if (isset($response['access_token'])) {
                    $bearer_token = $response['access_token'];
                }
            } catch (GuzzleException $e) {
                Toolbox::logInFile(
                    "webhook",
                    "OAuth authentication error for webhook {$webhook->fields['name']} ({$webhook->getID()}): " . $e->getMessage()
                );
            }
        }

        $client = Toolbox::getGuzzleClient($guzzle_options);
        $headers = json_decode($queued_webhook->fields['headers'], true);
        // Remove headers with empty values
        $headers = array_filter($headers, static fn($value) => !empty($value));
        if ($bearer_token !== null) {
            $headers['Authorization'] = 'Bearer ' . $bearer_token;
        }

        try {
            $response = $client->request($queued_webhook->fields['http_method'], $queued_webhook->fields['url'], [
                RequestOptions::HEADERS => $headers,
                RequestOptions::BODY => $queued_webhook->fields['body'],
            ]);
        } catch (GuzzleException $e) {
            Toolbox::logInFile(
                "webhook",
                "Error sending webhook {$webhook->fields['name']} ({$webhook->getID()}): " . $e->getMessage()
            );
            if ($e instanceof RequestException) {
                $response = $e->getResponse();
            } else {
                $response = null;
            }
        }
        $input = [
            'id' => $ID,
            'sent_try' => $queued_webhook->fields['sent_try'] + 1,
            'sent_time' => $_SESSION["glpi_currenttime"],
        ];
        if ($response !== null) {
            $input['last_status_code'] = $response->getStatusCode();
            if (GLPI_WEBHOOK_ALLOW_RESPONSE_SAVING && $queued_webhook->fields['save_response_body']) {
                $input['response_body'] = (string) $response->getBody();
            } else {
                // Save to property that won't be saved in DB, but can still be available to plugins
                $input['_response_body'] = (string) $response->getBody();
            }

            if ($webhook->fields['log_in_item_history']) {
                /** @var class-string<CommonDBTM> $itemtype */
                $itemtype = $queued_webhook->fields['itemtype'];
                $item = getItemForItemtype($itemtype);
                $item->getFromDB($queued_webhook->fields['items_id']);

                if ($item->dohistory) {
                    Log::history($queued_webhook->fields['items_id'], $queued_webhook->fields['itemtype'], [
                        30, $queued_webhook->fields['last_status_code'], $response->getStatusCode(),
                    ], $queued_webhook->fields['id'], Log::HISTORY_SEND_WEBHOOK);
                }
            }
        }

        return $queued_webhook->update($input) && $response !== null;
    }

    public static function getIcon()
    {
        return "ti ti-notification";
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => self::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'itemlink',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => self::getTable(),
            'field'              => 'create_time',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => self::getTable(),
            'field'              => 'send_time',
            'name'               => __('Expected send date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => self::getTable(),
            'field'              => 'sent_time',
            'name'               => __('Send date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => self::getTable(),
            'field'              => 'url',
            'name'               => __('URL'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => self::getTable(),
            'field'              => 'headers',
            'name'               => __('Headers'),
            'datatype'           => 'specific',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => self::getTable(),
            'field'              => 'body',
            'name'               => _n('Payload', 'Payloads', 1),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'htmltext'           => true,
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => self::getTable(),
            'field'              => 'sent_try',
            'name'               => __('Number of tries of sent'),
            'datatype'           => 'integer',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '20',
            'table'              => self::getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'itemtypename',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => self::getTable(),
            'field'              => 'items_id',
            'name'               => __('Associated item ID'),
            'massiveaction'      => false,
            'datatype'           => 'integer',
        ];

        $tab[] = [
            'id'                 => '22',
            'table'              => 'glpi_webhooks',
            'field'              => 'name',
            'name'               => Webhook::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                => 30,
            'table'             => self::getTable(),
            'field'             => 'last_status_code',
            'name'              => __('Last status code'),
            'massiveaction'     => false,
            'datatype'          => 'specific',
            'additionalfields'  => ['id'],
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id' => 31,
            'table' => self::getTable(),
            'field' => 'http_method',
            'name' => __('HTTP method'),
            'massiveaction' => false,
            'datatype' => 'specific',
        ];

        return $tab;
    }

    public static function getStatusCodeBadge($value, ?int $id = null): string
    {
        $display_value = (int) $value;
        $badge_class = 'badge bg-orange';
        if (empty($display_value)) {
            $display_value = __s('Not sent/no response');
        } elseif ($display_value < 300) {
            $badge_class = 'badge bg-green';
        } else {
            $badge_class = 'badge bg-red';
        }
        $badge = '<div class="' . $badge_class . '">' . $display_value . '</div>';

        if ($id === null || (is_numeric($display_value) && (int) $display_value < 300)) {
            return $badge;
        }
        // Add a button to resend the webhook via ajax
        $btn_id = "resend-webhook-{$id}";
        $badge .= "<button id='{$btn_id}' type='button' class='btn btn-outline-secondary btn-sm ms-1' data-id='{$id}'><i class='ti ti-send'></i>" . __s('Send') . "</button>";
        $badge .= Html::scriptBlock(<<<JS
            $("#{$btn_id}").click(function() {
                var id = $(this).data('id');
                $.ajax({
                    url: '/ajax/webhook.php',
                    type: 'POST',
                    data: {
                        'action': 'resend',
                        'id': id
                    },
                    beforeSend: () => {
                        $("#{$btn_id}").prop('disabled', true);
                    },
                    success: () => {
                        glpi_toast_info(__('Retried to send webhook'));
                    },
                    error: () => {
                        glpi_toast_error(__('Failed to send webhook'));
                    },
                    complete: () => {
                        $("#{$btn_id}").prop('disabled', false);
                        const search_class = $('table.search-results').closest('div.ajax-container.search-display-data').data('js_class');
                        if (search_class !== undefined) {
                            search_class.view.refreshResults();
                        }
                    }
                });
            });
JS);
        return $badge;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        // For last_status_code field, we want to display a badge element
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'last_status_code':
                return self::getStatusCodeBadge($values[$field], $values['id'] ?? null);
            case 'http_method':
                return htmlescape(Webhook::getHttpMethod()[$values[$field]] ?? $values[$field]);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public function showForm($ID, array $options = [])
    {
        $webhook = new Webhook();
        $webhook->getFromDB($this->fields['webhooks_id']);
        TemplateRenderer::getInstance()->display('pages/setup/webhook/queuedwebhook.html.twig', [
            'item' => $this,
            'webhook' => $webhook,
            'headers' => json_decode($this->fields['headers'], true),
            'params' => [
                'canedit' => true,
                'candel' => $this->canDeleteItem(),
            ],
        ]);
        return true;
    }

    /**
     * Get pending webhooks in queue
     *
     * @param string  $send_time   Maximum sent_time
     * @param integer $limit       Query limit clause
     * @param array   $extra_where Extra params to add to the where clause
     *
     * @return array Array of IDs of pending webhooks
     */
    public static function getPendings($send_time = null, $limit = 20, $extra_where = [])
    {
        global $DB;

        if ($send_time === null) {
            $send_time = date('Y-m-d H:i:s');
        }

        $pendings = [];
        $queued_table = self::getTable();
        $webhook_table = Webhook::getTable();

        $iterator = $DB->request([
            'SELECT' => [$queued_table . '.id'],
            'FROM'   => $queued_table,
            'LEFT JOIN' => [
                $webhook_table => [
                    'FKEY' => [
                        $webhook_table => 'id',
                        $queued_table => 'webhooks_id',
                    ],
                ],
            ],
            'WHERE'  => [
                'is_deleted'   => 0,
                [
                    'OR' => [
                        "$queued_table.sent_try" => null,
                        new QueryExpression($DB::quoteName($queued_table . '.sent_try') . ' <= ' . $DB::quoteName($webhook_table . '.sent_try')),
                    ],
                ],
                'send_time'    => ['<', $send_time],
                [
                    'OR' => [
                        // We will retry sending webhooks that never got a response or got any error status code (4xx or 5xx)
                        ['last_status_code' => null],
                        ['last_status_code' => ['>=', 400]],
                    ],
                ],
            ] +  $extra_where,
            'ORDER'  => 'send_time ASC',
            'START'  => 0,
            'LIMIT'  => $limit,
        ]);
        if ($iterator->numRows() > 0) {
            foreach ($iterator as $row) {
                $pendings[] = $row;
            }
        }

        return $pendings;
    }

    /**
     * Cron action on webhook queue: send webhooks in queue
     *
     * @param CronTask|null $task for log (default NULL)
     *
     * @return integer either 0 or 1
     **/
    public static function cronQueuedWebhook(?CronTask $task = null)
    {
        $cron_status = 0;

        // Send webhooks at least 1 minute after adding in queue to be sure that process on it is finished
        $send_time = date("Y-m-d H:i:s", strtotime("+1 minutes"));

        $limit = $task !== null ? $task->fields['param'] : 50;

        $pendings = self::getPendings($send_time, $limit);

        foreach ($pendings as $data) {
            self::sendById($data['id']);
        }

        return $cron_status;
    }


    /**
     * Cron action on webhook queue: clean webhook queue
     *
     * @param CronTask|null $task for log (default NULL)
     *
     * @return integer either 0 or 1
     **/
    public static function cronQueuedWebhookClean(?CronTask $task = null)
    {
        global $DB;

        $vol = 0;

        $expiration = $task !== null ? $task->fields['param'] : 30;

        $queued_table = self::getTable();
        $webhook_table = Webhook::getTable();
        if ($expiration > 0) {
            $secs      = $expiration * DAY_TIMESTAMP;
            $send_time = date("U") - $secs;
            $DB->delete(
                $queued_table,
                [
                    'OR' => [
                        new QueryExpression(QueryFunction::unixTimestamp('send_time') . ' < ' . $DB::quoteValue($send_time)),
                        new QueryExpression($DB::quoteName($queued_table . '.sent_try') . ' >= ' . $DB::quoteName($webhook_table . '.sent_try')),
                    ],
                ],
                [
                    'LEFT JOIN' => [
                        $webhook_table => [
                            'ON' => [
                                $queued_table => 'webhooks_id',
                                $webhook_table => 'id',
                            ],
                        ],
                    ],
                ]
            );
            $vol = $DB->affectedRows();
        }

        $task->setVolume($vol);
        return ($vol > 0 ? 1 : 0);
    }

    public function post_getFromDB()
    {
        parent::post_getFromDB();

        if (!GLPI_WEBHOOK_ALLOW_RESPONSE_SAVING) {
            // Block viewing response body if saving is disabled by config
            unset($this->fields['response_body']);
        }
    }
}
