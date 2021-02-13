<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\System\Status;

use AuthLDAP;
use CronTask;
use DBConnection;
use DBmysql;
use MailCollector;
use Plugin;
use Toolbox;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5.0
 */
final class StatusChecker {

   /**
    * The plugin or service is working as expected.
    */
   public const STATUS_OK = 'OK';

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

   /**
    * @param bool $public_only True if only public status information should be given.
    * @return array
    */
   public static function getDBStatus($public_only = true): array {
      static $status = null;

      if ($status === null) {
         $status = [
            'status' => self::STATUS_OK,
            'master' => [
               'status' => self::STATUS_OK,
            ],
            'slaves' => [
               'status' => self::STATUS_NO_DATA,
               'servers' => []
            ]
         ];
         // Check slave server connection
         if (DBConnection::isDBSlaveActive()) {
            $DBslave = DBConnection::getDBSlaveConf();
            if (is_array($DBslave->dbhost)) {
               $hosts = $DBslave->dbhost;
            } else {
               $hosts = [$DBslave->dbhost];
            }

            if (count($hosts)) {
               $status['slaves']['status'] = self::STATUS_OK;
            }

            foreach ($hosts as $num => $name) {
               $diff = DBConnection::getReplicateDelay($num);
               if (abs($diff) > 1000000000) {
                  $status['slaves']['servers'][$num] = [
                     'status'             => self::STATUS_PROBLEM,
                     'replication_delay'  => '-1'
                  ];
                  $status['slaves']['status'] = self::STATUS_PROBLEM;
                  $status['status'] = self::STATUS_PROBLEM;
               } else if (abs($diff) > HOUR_TIMESTAMP) {
                  $status['slaves']['servers'][$num] = [
                     'status'             => self::STATUS_PROBLEM,
                     'replication_delay'  => abs($diff)
                  ];
                  $status['slaves']['status'] = self::STATUS_PROBLEM;
                  $status['status'] = self::STATUS_PROBLEM;
               } else {
                  $status['slaves']['servers'][$num] = [
                     'status'             => self::STATUS_OK,
                     'replication_delay'  => abs($diff)
                  ];
               }
            }
         }

         // Check main server connection
         if (!DBConnection::establishDBConnection(false, true, false)) {
            $status['master'] = [
               'status' => self::STATUS_PROBLEM
            ];
            $status['status'] = self::STATUS_PROBLEM;
         }
      }

      return $status;
   }

   private static function isDBAvailable(): bool {
      static $db_ok = null;

      if ($db_ok === null) {
         $status = self::getDBStatus();
         $db_ok = ($status['master']['status'] === self::STATUS_OK || $status['slaves']['status'] === self::STATUS_OK);
      }

      return $db_ok;
   }

   /**
    * @param bool $public_only True if only public status information should be given.
    * @return array
    */
   public static function getLDAPStatus($public_only = true): array {
      static $status = null;

      if ($status === null) {
         $status = [
            'status' => self::STATUS_NO_DATA,
            'servers' => []
         ];
         if (self::isDBAvailable()) {
            // Check LDAP Auth connections
            $ldap_methods = getAllDataFromTable('glpi_authldaps', ['is_active' => 1]);

            if (count($ldap_methods)) {
               $status['status'] = self::STATUS_OK;
               foreach ($ldap_methods as $method) {
                  try {
                     if (AuthLDAP::tryToConnectToServer($method, $method['rootdn'],
                        Toolbox::sodiumDecrypt($method['rootdn_passwd']))) {
                        $status['servers'][$method['name']] = [
                           'status' => self::STATUS_OK
                        ];
                     } else {
                        $status['servers'][$method['name']] = [
                           'status' => self::STATUS_PROBLEM
                        ];
                        $status['status'] = self::STATUS_PROBLEM;
                     }
                  } catch (\RuntimeException $e) {
                     // May be missing LDAP extension (Probably test environment)
                     $status['servers'][$method['name']] = [
                        'status' => self::STATUS_PROBLEM
                     ];
                     $status['status'] = self::STATUS_PROBLEM;
                  }
               }
            }
         }
      }

      return $status;
   }

   /**
    * @param bool $public_only True if only public status information should be given.
    * @return array
    */
   public static function getIMAPStatus($public_only = true): array {
      static $status = null;

      if ($status === null) {
         $status = [
            'status' => self::STATUS_NO_DATA,
            'servers' => []
         ];
         if (self::isDBAvailable()) {
            // Check IMAP Auth connections
            $imap_methods = getAllDataFromTable('glpi_authmails', ['is_active' => 1]);

            if (count($imap_methods)) {
               $status['status'] = self::STATUS_OK;
               foreach ($imap_methods as $method) {
                  $param = Toolbox::parseMailServerConnectString($method['connect_string'], true);
                  if ($param['ssl'] === true) {
                     $host = 'ssl://'.$param['address'];
                  } else if ($param['tls'] === true) {
                     $host = 'tls://'.$param['address'];
                  } else {
                     $host = $param['address'];
                  }
                  if ($fp = @fsockopen($host, $param['port'], $errno, $errstr, 1)) {
                     $status['servers'][$method['name']] = [
                        'status' => 'OK'
                     ];
                  } else {
                     $status['servers'][$method['name']] = [
                        'status' => self::STATUS_PROBLEM
                     ];
                     $status['status'] = self::STATUS_PROBLEM;
                  }
                  if ($fp !== false) {
                     fclose($fp);
                  }
               }
            }
         }
      }

      return $status;
   }

   /**
    * @param bool $public_only True if only public status information should be given.
    * @return array
    */
   public static function getCASStatus($public_only = true): array {
      global $CFG_GLPI;

      static $status = null;

      if ($status === null) {
         $status['status'] = self::STATUS_NO_DATA;
         if (!empty($CFG_GLPI['cas_host'])) {
            $url = $CFG_GLPI['cas_host'];
            if (!empty($CFG_GLPI['cas_port'])) {
               $url .= ':'. (int)$CFG_GLPI['cas_port'];
            }
            $url .= '/'.$CFG_GLPI['cas_uri'];
            $data = Toolbox::getURLContent($url);
            if (!empty($data)) {
               $status['status'] = self::STATUS_OK;
            } else {
               $status['status'] = self::STATUS_PROBLEM;
            }
         }
      }

      return $status;
   }

   /**
    * @param bool $public_only True if only public status information should be given.
    * @return array
    */
   public static function getMailCollectorStatus($public_only = true): array {
      static $status = null;

      if ($status === null) {
         $status = [
            'status' => self::STATUS_NO_DATA,
            'servers' => []
         ];
         if (self::isDBAvailable()) {
            $mailcollectors = getAllDataFromTable('glpi_mailcollectors', ['is_active' => 1]);
            if (count($mailcollectors)) {
               $status['status'] = self::STATUS_OK;
               $mailcol = new MailCollector();
               foreach ($mailcollectors as $mc) {
                  if ($mailcol->getFromDB($mc['id'])) {
                     try {
                        $mailcol->connect();
                        $status['servers'][$mc['name']] = [
                           'status' => 'OK'
                        ];
                     } catch (\Exception $e) {
                        $status['servers'][$mc['name']] = [
                           'status'       => self::STATUS_PROBLEM,
                           'error_code'   => $e->getCode()
                        ];
                        $status['status'] = self::STATUS_PROBLEM;
                     }
                  }
               }
            }
         }
      }

      return $status;
   }

   /**
    * @param bool $public_only True if only public status information should be given.
    * @return array
    */
   public static function getCronTaskStatus($public_only = true): array {
      static $status = null;

      if ($status === null) {
         $status = [
            'status' => self::STATUS_NO_DATA,
            'stuck' => []
         ];
         if (self::isDBAvailable()) {
            $stuck_crontasks = getAllDataFromTable(
               'glpi_crontasks', [
                  'state'  => CronTask::STATE_RUNNING,
                  'OR'     => [
                     new \QueryExpression(
                        '(unix_timestamp(' . DBmysql::quoteName('lastrun') . ') + 2 * '.
                        DBmysql::quoteName('frequency') .' < unix_timestamp(now()))'
                     ),
                     new \QueryExpression(
                        '(unix_timestamp(' . DBmysql::quoteName('lastrun') . ') + 2 * '.
                        HOUR_TIMESTAMP . ' < unix_timestamp(now()))'
                     )
                  ]
               ]
            );
            foreach ($stuck_crontasks as $ct) {
               $status['stuck'][] = $ct['name'];
            }
            $status['status'] = count($status['stuck']) ? self::STATUS_PROBLEM : self::STATUS_OK;
         }
      }

      return $status;
   }

   /**
    * @param bool $public_only True if only public status information should be given.
    * @return array
    */
   public static function getFilesystemStatus($public_only = true): array {
      static $status = null;

      if ($status === null) {
         $status = [
            'status' => self::STATUS_OK,
            'session_dir' => [
               'status' => self::STATUS_OK
            ]
         ];
         // Check session dir (useful when NFS mounted))
         if (!is_dir(GLPI_SESSION_DIR)) {
            $status['session_dir'] = [
               'status' => self::STATUS_PROBLEM,
               'status_msg'   => 'GLPI_SESSION_DIR variable is not a directory'
            ];
            $status['status'] = self::STATUS_PROBLEM;
         } else if (!is_writable(GLPI_SESSION_DIR)) {
            $status['session_dir'] = [
               'status' => self::STATUS_PROBLEM,
               'status_msg'   => 'GLPI_SESSION_DIR is not writeable'
            ];
            $status['status'] = self::STATUS_PROBLEM;
         }
      }

      return $status;
   }

   /**
    *
    * @since 9.5.0
    * @param bool $public_only True if only public status information should be given.
    * @return array
    */
   public static function getPluginsStatus($public_only = true): array {
      static $status = null;

      if ($status === null) {
         $plugins = Plugin::getPlugins();
         $status = [];

         foreach ($plugins as $plugin) {
            // Old-style plugin status hook which only modified the global OK status.
            $param = ['ok' => true];
            $plugin_status = Plugin::doOneHook($plugin, 'status', $param);
            if ($plugin_status === null) {
               continue;
            }
            if (isset($plugin_status['ok']) && count(array_keys($plugin_status)) === 1) {
               $status[$plugin] = [
                  'status'    => $plugin_status['ok'] ? self::STATUS_OK : self::STATUS_PROBLEM,
                  'version'   => Plugin::getInfo($plugin)['version']
               ];
            } else {
               $status[$plugin] = $plugin_status;
            }
         }
      }

      if (count($status) === 0) {
         $status['status'] = self::STATUS_NO_DATA;
      } else {
         if ($public_only) {
            // Only show overall plugin status
            // Giving out plugin names and versions to anonymous users could make it easier to target insecure plugins and versions
            $statuses = array_column($status, 'status');
            $all_ok = !in_array(self::STATUS_PROBLEM, $statuses, true);
            return ['status' => $all_ok ? self::STATUS_OK : self::STATUS_PROBLEM];
         }
      }

      return $status;
   }

   /**
    * @param bool $public_only True if only public status information should be given.
    * @param bool $as_array
    * @return array|string
    */
   public static function getFullStatus($public_only = true, $as_array = true) {
      static $status = null;

      if ($status === null) {
         $status = [
            'db'              => self::getDBStatus($public_only),
            'cas'             => self::getCASStatus($public_only),
            'ldap'            => self::getLDAPStatus($public_only),
            'imap'            => self::getIMAPStatus($public_only),
            'mail_collectors' => self::getMailCollectorStatus($public_only),
            'crontasks'       => self::getCronTaskStatus($public_only),
            'filesystem'      => self::getFilesystemStatus($public_only),
            'glpi'            => [
               'status'    => self::STATUS_OK,
            ],
            'plugins'         => self::getPluginsStatus($public_only)
         ];
         // Compute GLPI status from top-level services
         $statuses = array_column($status, 'status');
         $all_ok = !in_array(self::STATUS_PROBLEM, $statuses, true);
         $status['glpi']['status'] = $all_ok ? self::STATUS_OK : self::STATUS_PROBLEM;
      }

      // Only show overall core status for public
      // Giving out the version to anonymous users could make it easier to target insecure versions of GLPI
      if (!$public_only) {
         $status['glpi']['version'] = GLPI_VERSION;
      }

      if ($as_array) {
         return $status;
      }

      $output = '';
      // Plain-text output
      if (count($status['db']['slaves'])) {
         foreach ($status['db']['slaves']['servers'] as $num => $slave_info) {
            $output .= "GLPI_DBSLAVE_{$num}_{$slave_info['status']}\n";
         }
      } else {
         $output .= "No slave DB\n";
      }
      $output .= "GLPI_DB_{$status['db']['master']['status']}\n";
      $output .= "GLPI_SESSION_DIR_{$status['filesystem']['session_dir']['status']}\n";
      if (count($status['ldap']['servers'])) {
         $output .= 'Check LDAP servers:';
         foreach ($status['ldap']['servers'] as $name => $ldap_info) {
            $output .= " {$name}_{$ldap_info['status']}\n";
         }
      } else {
         $output .= "No LDAP server\n";
      }
      if (count($status['imap']['servers'])) {
         $output .= 'Check IMAP servers:';
         foreach ($status['imap']['servers'] as $name => $imap_info) {
            $output .= " {$name}_{$imap_info['status']}\n";
         }
      } else {
         $output .= "No IMAP server\n";
      }
      if (isset($status['cas']['status']) && $status['cas']['status'] !== self::STATUS_NO_DATA) {
         $output .= "CAS_SERVER_{$status['cas']['status']}\n";
      } else {
         $output .= "No CAS server\n";
      }
      if (count($status['mail_collectors']['servers'])) {
         $output .= 'Check mail collectors:';
         foreach ($status['mail_collectors']['servers'] as $name => $collector_info) {
            $output .= " {$name}_{$collector_info['status']}\n";
         }
      } else {
         $output .= "No mail collector\n";
      }
      if (count($status['crontasks']['stuck'])) {
         $output .= 'Check crontasks:';
         foreach ($status['crontasks']['stuck'] as $name) {
            $output .= " {$name}_PROBLEM\n";
         }
      } else {
         $output .= "Crontasks_OK\n";
      }

      // Overall Status
      $output .= "\nGLPI_{$status['glpi']['status']}\n";
      return $output;
   }
}
