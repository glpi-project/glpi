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

namespace Glpi\System\Status;

use AuthLDAP;
use CronTask;
use DBConnection;
use Glpi\Plugin\Hooks;
use GLPIKey;
use MailCollector;
use Plugin;
use RuntimeException;
use Throwable;
use Toolbox;

use function Safe\fclose;

/**
 * @since 9.5.0
 */
final class StatusChecker
{
    /**
     * The plugin or service is working as expected.
     */
    public const STATUS_OK = 'OK';

    /**
     * The plugin or service is working but may have some issues
     */
    public const STATUS_WARNING = 'WARNING';

    /**
     * The plugin or service is reachable but not working as expected.
     */
    public const STATUS_PROBLEM = 'PROBLEM';

    /**
     * Unable to get the status of a plugin or service.
     * This is likely due to a prerequisite plugin or service being unavailable or the plugin not implementing the status hook.
     * For example, some checks require the DB to be accessible.
     */
    public const STATUS_NO_DATA = 'NO_DATA';

    private static array $cached_status = [];

    /**
     * Get all registered services
     * @return array Array of services keyed by name.
     *    The value for each service is expected to be an array containing a class name and a method name relating to the method that will do the check.
     * @since 10.0.0
     */
    public static function getServices(): array
    {
        return [
            'db'              => [self::class, 'getDBStatus'],
            'cas'             => [self::class, 'getCASStatus'],
            'ldap'            => [self::class, 'getLDAPStatus'],
            'imap'            => [self::class, 'getIMAPStatus'],
            'mail_collectors' => [self::class, 'getMailCollectorStatus'],
            'crontasks'       => [self::class, 'getCronTaskStatus'],
            'filesystem'      => [self::class, 'getFilesystemStatus'],
            'plugins'         => [self::class, 'getPluginsStatus'],
        ];
    }

    /**
     * Calculate the overall GLPI status or the overall service status based on all child status checks
     * @param array $status The status array for all services or a specific service check.
     * @return string The calculated status.
     *    One of {@link STATUS_NO_DATA}, {@link STATUS_OK}, {@link STATUS_WARNING}, or {@link STATUS_PROBLEM}.
     * @since 10.0.0
     */
    public static function calculateGlobalStatus(array $status)
    {
        $statuses = array_column($status, 'status');
        $global_status = self::STATUS_OK;
        if (in_array(self::STATUS_PROBLEM, $statuses, true)) {
            $global_status = self::STATUS_PROBLEM;
        } elseif (in_array(self::STATUS_WARNING, $statuses, true)) {
            $global_status = self::STATUS_WARNING;
        }
        return $global_status;
    }

    /**
     * Get a service's status
     *
     * @param string|null $service The name of the service or if null/'all' all services will be checked
     * @param bool $public_only True if only public information should be available in the status check.
     *    If true, assume the data is being viewed by an anonymous user.
     * @return array An array with the status information
     * @since 10.0.0
     */
    public static function getServiceStatus(?string $service, $public_only = true): array
    {
        $services = self::getServices();
        if ($service === 'all' || $service === null) {
            $status = [
                'glpi'   => [
                    'status' => self::STATUS_OK,
                ],
            ];
            foreach (array_keys($services) as $name) {
                $service_status = self::getServiceStatus($name, $public_only);
                $status[$name] = $service_status;
            }

            $status['glpi']['status'] = self::calculateGlobalStatus($status);

            return $status;
        }

        if (!array_key_exists($service, $services)) {
            return [];
        }
        $service_check_method = $services[$service];
        if (method_exists($service_check_method[0], $service_check_method[1])) {
            return $service_check_method($public_only);
        }
        return [];
    }

    /**
     * @param bool $public_only True if only public status information should be given.
     * @return array
     */
    public static function getDBStatus(bool $public_only = true): array
    {
        if (!isset(self::$cached_status['db'])) {
            $status = [
                'status' => self::STATUS_OK,
                'main' => [
                    'status' => self::STATUS_OK,
                ],
                'replicas' => [
                    'status' => self::STATUS_NO_DATA,
                    'servers' => [],
                ],
            ];
            // Check replica SQL server connection
            if (DBConnection::isDBSlaveActive()) {
                $DBslave = DBConnection::getDBSlaveConf();
                if (is_array($DBslave->dbhost)) {
                    $hosts = $DBslave->dbhost;
                } else {
                    $hosts = [$DBslave->dbhost];
                }

                if (count($hosts)) {
                    $status['replicas']['status'] = self::STATUS_OK;
                }

                foreach (array_keys($hosts) as $num) {
                    $diff = DBConnection::getReplicateDelay($num);
                    if (abs($diff) > 1000000000) {
                        $status['replicas']['servers'][$num] = [
                            'status'             => self::STATUS_PROBLEM,
                            'replication_delay'  => '-1',
                            'status_msg'           => _x('glpi_status', 'Replication delay is too high'),
                        ];
                        $status['replicas']['status'] = self::STATUS_PROBLEM;
                        $status['status'] = self::STATUS_PROBLEM;
                    } elseif (abs($diff) > HOUR_TIMESTAMP) {
                        $status['replicas']['servers'][$num] = [
                            'status'             => self::STATUS_PROBLEM,
                            'replication_delay'  => abs($diff),
                            'status_msg'           => _x('glpi_status', 'Replication delay is too high'),
                        ];
                        $status['replicas']['status'] = self::STATUS_PROBLEM;
                        $status['status'] = self::STATUS_PROBLEM;
                    } else {
                        $status['replicas']['servers'][$num] = [
                            'status'             => self::STATUS_OK,
                            'replication_delay'  => abs($diff),
                        ];
                    }
                }
            }

            // Check main server connection
            if (!@DBConnection::establishDBConnection(false, true)) {
                $status['main'] = [
                    'status' => self::STATUS_PROBLEM,
                    'status_msg' => _x('glpi_status', 'Unable to connect to the main database'),
                ];
                $status['status'] = self::STATUS_PROBLEM;
            }
            self::$cached_status['db'] = $status;
        }

        return self::$cached_status['db'];
    }

    private static function isDBAvailable(): bool
    {
        $status = self::getDBStatus();
        return ($status['main']['status'] === self::STATUS_OK || $status['replicas']['status'] === self::STATUS_OK);
    }

    /**
     * @param bool $public_only True if only public status information should be given.
     * @return array
     */
    public static function getLDAPStatus(bool $public_only = true): array
    {
        if (!isset(self::$cached_status['ldap'])) {
            $status = [
                'status' => self::STATUS_NO_DATA,
                'servers' => [],
            ];
            if (self::isDBAvailable()) {
                // Check LDAP Auth connections
                $ldap_methods = getAllDataFromTable('glpi_authldaps', ['is_active' => 1]);

                $total_servers = count($ldap_methods);
                $total_error = 0;
                $global_status = self::STATUS_NO_DATA;
                $message = null;
                if ($total_servers > 0) {
                    $global_status = self::STATUS_OK;
                    foreach ($ldap_methods as $method) {
                        $ldap = null;
                        $display_name = $public_only ? 'GLPI_LDAP_' . $method['id'] : $method['name'];
                        try {
                            if (
                                @AuthLDAP::tryToConnectToServer(
                                    $method,
                                    $method['rootdn'],
                                    (new GLPIKey())->decrypt($method['rootdn_passwd'])
                                )
                            ) {
                                $status['servers'][$display_name] = [
                                    'status' => self::STATUS_OK,
                                ];
                            } else {
                                $status['servers'][$display_name] = [
                                    'status' => self::STATUS_PROBLEM,
                                    'status_msg' => _x('glpi_status', 'Unable to connect to the LDAP server'),
                                ];
                                $total_error++;
                                $global_status = self::STATUS_PROBLEM;
                            }
                        } catch (RuntimeException $e) {
                            // May be missing LDAP extension (Probably test environment)
                            $status['servers'][$method['name']] = [
                                'status' => self::STATUS_PROBLEM,
                            ];
                            $total_error++;
                            $global_status = self::STATUS_PROBLEM;
                        }
                    }

                    if ($global_status !== self::STATUS_OK) {
                        $message = sprintf(_x('glpi_status', 'OK: %d, WARNING: %d, PROBLEM: %d, TOTAL: %d'), $total_servers - $total_error, 0, $total_error, $total_servers);
                    }
                }
                $status['status'] = $global_status;
                if ($message !== null) {
                    $status['status_msg'] = $message;
                }
            }
            self::$cached_status['ldap'] = $status;
        }

        return self::$cached_status['ldap'];
    }

    /**
     * @param bool $public_only True if only public status information should be given.
     * @return array
     */
    public static function getIMAPStatus(bool $public_only = true): array
    {
        if (!isset(self::$cached_status['imap'])) {
            $status = [
                'status' => self::STATUS_NO_DATA,
                'servers' => [],
            ];
            if (self::isDBAvailable()) {
                // Check IMAP Auth connections
                $imap_methods = getAllDataFromTable('glpi_authmails', ['is_active' => 1]);

                $total_servers = count($imap_methods);
                $total_error = 0;
                $global_status = self::STATUS_NO_DATA;
                $message = null;
                if ($total_servers > 0) {
                    $global_status = self::STATUS_OK;
                    foreach ($imap_methods as $method) {
                        $param = Toolbox::parseMailServerConnectString($method['connect_string'], true);
                        $display_name = $public_only ? 'GLPI_IMAP_' . $method['id'] : $method['name'];
                        if ($param['ssl'] === true) {
                            $host = 'ssl://' . $param['address'];
                        } elseif ($param['tls'] === true) {
                            $host = 'tls://' . $param['address'];
                        } else {
                            $host = $param['address'];
                        }
                        if ($fp = @fsockopen($host, $param['port'], $errno, $errstr, 1)) { // @phpstan-ignore theCodingMachineSafe.function
                            $status['servers'][$display_name] = [
                                'status' => self::STATUS_OK,
                            ];
                        } else {
                            $status['servers'][$display_name] = [
                                'status' => self::STATUS_PROBLEM,
                                'status_msg' => _x('glpi_status', 'Unable to connect to the IMAP server'),
                            ];
                            $total_error++;
                            $global_status = self::STATUS_PROBLEM;
                        }
                        if ($fp !== false) {
                            fclose($fp);
                        }
                    }
                    if ($global_status !== self::STATUS_OK) {
                        $message = sprintf(_x('glpi_status', 'OK: %d, WARNING: %d, PROBLEM: %d, TOTAL: %d'), $total_servers - $total_error, 0, $total_error, $total_servers);
                    }
                }
                $status['status'] = $global_status;
                if ($message !== null) {
                    $status['status_msg'] = $message;
                }
            }
            self::$cached_status['imap'] = $status;
        }

        return self::$cached_status['imap'];
    }

    /**
     * @param bool $public_only True if only public status information should be given.
     * @return array
     */
    public static function getCASStatus(bool $public_only = true): array
    {
        global $CFG_GLPI;

        if (!isset(self::$cached_status['cas'])) {
            $status = ['status' => self::STATUS_NO_DATA];
            if (!empty($CFG_GLPI['cas_host'])) {
                // Rebuild CAS URL
                // see `CAS_Client::_getServerBaseURL()`
                $url = 'https://' . $CFG_GLPI['cas_host'];
                if (!empty($CFG_GLPI['cas_port'])) {
                    $url .= ':' . (int) $CFG_GLPI['cas_port'];
                }
                $url .= '/' . $CFG_GLPI['cas_uri'];

                if (Toolbox::isUrlSafe($url)) {
                    $data = Toolbox::getURLContent($url);
                    if (!empty($data)) {
                        $status['status'] = self::STATUS_OK;
                    } else {
                        $status['status'] = self::STATUS_PROBLEM;
                    }
                } else {
                    $status['status'] = self::STATUS_NO_DATA;
                    if (!$public_only) {
                        $status['status_msg'] = sprintf(
                            __('URL "%s" is not considered safe and cannot be fetched from GLPI server.'),
                            $url
                        );
                    }
                }
            }
            self::$cached_status['cas'] = $status;
        }

        return self::$cached_status['cas'];
    }

    /**
     * @param bool $public_only True if only public status information should be given.
     * @return array
     */
    public static function getMailCollectorStatus(bool $public_only = true): array
    {
        if (!isset(self::$cached_status['mail_collectors'])) {
            $status = [
                'status' => self::STATUS_NO_DATA,
                'servers' => [],
            ];
            if (self::isDBAvailable()) {
                $mailcollectors = getAllDataFromTable('glpi_mailcollectors', ['is_active' => 1]);

                $total_servers = count($mailcollectors);
                $total_error = 0;
                $global_status = self::STATUS_NO_DATA;
                $message = null;
                if ($total_servers > 0) {
                    $global_status = self::STATUS_OK;
                    $mailcol = new MailCollector();
                    foreach ($mailcollectors as $mc) {
                        if ($mailcol->getFromDB($mc['id'])) {
                            $display_name = $public_only ? 'GLPI_COLLECTOR_' . $mc['id'] : $mc['name'];
                            try {
                                $mailcol->connect();
                                $status['servers'][$display_name] = [
                                    'status' => self::STATUS_OK,
                                ];
                            } catch (Throwable $e) {
                                $status['servers'][$display_name] = [
                                    'status'       => self::STATUS_PROBLEM,
                                    'error_code'   => $e->getCode(),
                                    'status_msg'      => $e->getMessage(),
                                ];
                                $total_error++;
                                $global_status = self::STATUS_PROBLEM;
                            }
                        }
                    }
                    if ($global_status !== self::STATUS_OK) {
                        $message = sprintf(_x('glpi_status', 'OK: %d, WARNING: %d, PROBLEM: %d, TOTAL: %d'), $total_servers - $total_error, 0, $total_error, $total_servers);
                    }
                }
                $status['status'] = $global_status;
                if ($message !== null) {
                    $status['status_msg'] = $message;
                }
            }
            self::$cached_status['mail_collectors'] = $status;
        }

        return self::$cached_status['mail_collectors'];
    }

    /**
     * @param bool $public_only True if only public status information should be given.
     * @return array
     */
    public static function getCronTaskStatus(bool $public_only = true): array
    {
        if (!isset(self::$cached_status['crontasks'])) {
            $status = [
                'status' => self::STATUS_NO_DATA,
                'stuck' => [],
            ];
            if (self::isDBAvailable()) {
                global $DB;

                $crontasks = getAllDataFromTable('glpi_crontasks');
                $running = count(array_filter($crontasks, static fn($crontask) => $crontask['state'] === CronTask::STATE_RUNNING));
                $stuck_crontasks = CronTask::getZombieCronTasks();
                foreach ($stuck_crontasks as $ct) {
                    $status['stuck'][] = $ct['name'];
                }
                $status['status'] = count($status['stuck']) ? self::STATUS_PROBLEM : self::STATUS_OK;
                $status['status_msg'] = sprintf(_x('glpi_status', 'RUNNING: %d, STUCK: %d, TOTAL: %d'), $running, count($stuck_crontasks), count($crontasks));
            }
            self::$cached_status['crontasks'] = $status;
        }

        return self::$cached_status['crontasks'];
    }

    /**
     * @param bool $public_only True if only public status information should be given.
     * @return array
     */
    public static function getFilesystemStatus(bool $public_only = true): array
    {
        if (!isset(self::$cached_status['filesystem'])) {
            $status = [
                'status' => self::STATUS_OK,
                'session_dir' => [
                    'status' => self::STATUS_OK,
                ],
            ];
            $session_handler = ini_get('session.save_handler'); // @phpstan-ignore theCodingMachineSafe.function
            if ($session_handler !== false && strtolower($session_handler) === 'files') {
                // Check session dir (useful when NFS mounted))
                if (!is_dir(GLPI_SESSION_DIR)) {
                    $status['session_dir'] = [
                        'status' => self::STATUS_PROBLEM,
                        'status_msg'   => sprintf(_x('glpi_status', '%s variable is not a directory'), 'GLPI_SESSION_DIR'),
                    ];
                    $status['status'] = self::STATUS_PROBLEM;
                } elseif (!is_writable(GLPI_SESSION_DIR)) {
                    $status['session_dir'] = [
                        'status' => self::STATUS_PROBLEM,
                        'status_msg'   => sprintf(_x('glpi_status', '%s variable is not writable'), 'GLPI_SESSION_DIR'),
                    ];
                    $status['status'] = self::STATUS_PROBLEM;
                }
            } else {
                $status['session_dir']['status_msg'] = _x('glpi_status', 'PHP is not configured to use the "files" session save handler');
            }
            self::$cached_status['filesystem'] = $status;
        }

        return self::$cached_status['filesystem'];
    }

    /**
     * @param bool $public_only True if only public status information should be given.
     * @return array
     */
    public static function getPluginsStatus(bool $public_only = true): array
    {
        if (!isset(self::$cached_status['plugins'])) {
            $plugins = Plugin::getPlugins();
            $status = [];

            foreach ($plugins as $plugin) {
                // Old-style plugin status hook which only modified the global OK status.
                $param = [
                    'ok' => true,
                    '_public_only' => $public_only,
                ];
                $plugin_status = Plugin::doOneHook($plugin, Hooks::AUTO_STATUS, $param);
                if ($plugin_status === null) {
                    continue;
                }
                unset($plugin_status['_public_only']);
                if (isset($plugin_status['ok']) && count(array_keys($plugin_status)) === 1) {
                    $status[$plugin] = [
                        'status'    => $plugin_status['ok'] ? self::STATUS_OK : self::STATUS_PROBLEM,
                        'version'   => Plugin::getPluginFilesVersion($plugin),
                    ];
                } else {
                    $status[$plugin] = $plugin_status;
                }
            }
            self::$cached_status['plugins'] = $status;
        }

        if (count(self::$cached_status['plugins']) === 0) {
            self::$cached_status['plugins']['status'] = self::STATUS_NO_DATA;
        } else {
            if ($public_only) {
                // Only show overall plugin status
                // Giving out plugin names and versions to anonymous users could make it easier to target insecure plugins and versions
                $statuses = array_column(self::$cached_status['plugins'], 'status');
                $all_ok = !in_array(self::STATUS_PROBLEM, $statuses, true);
                return ['status' => $all_ok ? self::STATUS_OK : self::STATUS_PROBLEM];
            }
        }

        return self::$cached_status['plugins'];
    }

    public static function resetInstance(): void
    {
        self::$cached_status = [];
    }
}
