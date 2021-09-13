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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class Telemetry extends CommonGLPI {

   static function getTypeName($nb = 0) {
      return __('Telemetry');
   }

   /**
    * Grab telemetry information
    *
    * @return array
    */
   static public function getTelemetryInfos() {
      $data = [
         'glpi'   => self::grabGlpiInfos(),
         'system' => [
            'db'           => self::grabDbInfos(),
            'web_server'   => self::grabWebserverInfos(),
            'php'          => self::grabPhpInfos(),
            'os'           => self::grabOsInfos()
         ]
      ];

      return $data;
   }

   /**
    * Grab GLPI part information
    *
    * @return array
    */
   static public function grabGlpiInfos() {
      global $CFG_GLPI;

      $glpi = [
         'uuid'               => self::getInstanceUuid(),
         'version'            => GLPI_VERSION,
         'plugins'            => [],
         'default_language'   => $CFG_GLPI['language'],
         'install_mode'       => GLPI_INSTALL_MODE,
         'usage'              => [
            'avg_entities'          => self::getAverage('Entity'),
            'avg_computers'         => self::getAverage('Computer'),
            'avg_networkequipments' => self::getAverage('NetworkEquipment'),
            'avg_tickets'           => self::getAverage('Ticket'),
            'avg_problems'          => self::getAverage('Problem'),
            'avg_changes'           => self::getAverage('Change'),
            'avg_projects'          => self::getAverage('Project'),
            'avg_users'             => self::getAverage('User'),
            'avg_groups'            => self::getAverage('Group'),
            'ldap_enabled'          => AuthLDAP::useAuthLdap(),
            'mailcollector_enabled' => (MailCollector::countActiveCollectors() > 0),
            'notifications_modes'   => []
         ]
      ];

      $plugins = new Plugin();
      foreach ($plugins->getList(['directory', 'version']) as $plugin) {
         $glpi['plugins'][] = [
            'key'       => $plugin['directory'],
            'version'   => $plugin['version']
         ];
      }

      if ($CFG_GLPI['use_notifications']) {
         foreach (array_keys(\Notification_NotificationTemplate::getModes()) as $mode) {
            if ($CFG_GLPI['notifications_' . $mode]) {
               $glpi['usage']['notifications'][] = $mode;
            }
         }
      }

      return $glpi;
   }

   /**
    * Grab DB part information
    *
    * @return array
    */
   static public function grabDbInfos() {
      global $DB;

      $dbinfos = $DB->getInfo();

      $size_res = $DB->request([
         'SELECT' => new \QueryExpression("ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS dbsize"),
         'FROM'   => 'information_schema.tables',
         'WHERE'  => ['table_schema' => $DB->dbdefault]
      ])->next();

      $db = [
         'engine'    => $dbinfos['Server Software'],
         'version'   => $dbinfos['Server Version'],
         'size'      => $size_res['dbsize'],
         'log_size'  => '',
         'sql_mode'  => $dbinfos['Server SQL Mode']
      ];

      return $db;
   }

   /**
    * Grab web server part information
    *
    * @return array
    */
   static public function grabWebserverInfos() {
      global $CFG_GLPI;

      $headers = false;
      $engine  = '';
      $version = '';

      // check if host is present (do no throw php warning in contrary of get_headers)
      if (filter_var(gethostbyname(parse_url($CFG_GLPI['url_base'], PHP_URL_HOST)),
          FILTER_VALIDATE_IP)) {

          // Issue #3180 - disable SSL certificate validation (wildcard, self-signed)
          stream_context_set_default([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                ]
            ]);

         $headers = get_headers($CFG_GLPI['url_base']);
      }

      if (is_array($headers)) {
         //BEGIN EXTRACTING SERVER DETAILS
         $pattern = '#^Server:*#i';
         $matches = preg_grep($pattern, $headers);

         if (count($matches)) {
            $infos = current($matches);
            $pattern = '#Server: ([^ ]+)/([^ ]+)#i';
            preg_match($pattern, $infos, $srv_infos);
            if (count($srv_infos) == 3) {
               $engine  = $srv_infos[1];
               $version = $srv_infos[2];
            }
         }
      }

      $server = [
         'engine'    => $engine,
         'version'   => $version
      ];

      return $server;
   }

   /**
    * Grab PHP part information
    *
    * @return array
    */
   static public function grabPhpInfos() {
      $php = [
         'version'   => str_replace(PHP_EXTRA_VERSION, '', PHP_VERSION),
         'modules'   => get_loaded_extensions(),
         'setup'     => [
            'max_execution_time'    => ini_get('max_execution_time'),
            'memory_limit'          => ini_get('memory_limit'),
            'post_max_size'         => ini_get('post_max_size'),
            'safe_mode'             => ini_get('safe_mode'),
            'session'               => ini_get('session.save_handler'),
            'upload_max_filesize'   => ini_get('upload_max_filesize')
         ]
      ];

      return $php;
   }

   /**
    * Grab OS part information
    *
    * @return array
    */
   static public function grabOsInfos() {
      $distro = false;
      if (file_exists('/etc/redhat-release')) {
         $distro = preg_replace('/\s+$/S', '', file_get_contents('/etc/redhat-release'));
      }
      $os = [
         'family'       => php_uname('s'),
         'distribution' => ($distro ?: ''),
         'version'      => php_uname('r')
      ];
      return $os;
   }


   /**
    * Calculate average for itemtype
    *
    * @param string $itemtype Item type
    *
    * @return string
    */
   public static function getAverage($itemtype) {
      $count = (int)countElementsInTable(getTableForItemType($itemtype));

      if ($count <= 500) {
         return '0-500';
      } else if ($count <= 1000) {
         return '500-1000';
      } else if ($count <= 2500) {
         return '1000-2500';
      } else if ($count <= 5000) {
         return '2500-5000';
      } else if ($count <= 10000) {
         return '5000-10000';
      } else if ($count <= 50000) {
         return '10000-50000';
      } else if ($count <= 100000) {
         return '50000-100000';
      } else if ($count <= 500000) {
         return '100000-500000';
      }
      return '500000+';
   }

   static function cronInfo($name) {
      switch ($name) {
         case 'telemetry' :
            return ['description' => __('Send telemetry information')];
      }
      return [];
   }

   /**
    * Send telemetry information
    *
    * @param CronTask $task CronTask instance
    *
    * @return void
    */
   static public function cronTelemetry($task) {
      $data = self::getTelemetryInfos();
      $infos = json_encode(['data' => $data]);

      $url = GLPI_TELEMETRY_URI . '/telemetry';
      $opts = [
         CURLOPT_POSTFIELDS      => $infos,
         CURLOPT_HTTPHEADER      => ['Content-Type:application/json']
      ];

      $errstr = null;
      $content = json_decode(Toolbox::callCurl($url, $opts, $errstr));

      if ($content && property_exists($content, 'message')) {
         //all is OK!
         return 1;
      } else {
         $message = 'Something went wrong sending telemetry information';
         if ($errstr != '') {
            $message .= ": $errstr";
         }
         Toolbox::logError($message);
         return null; // null = Action aborted
      }
   }

   /**
    * Get instance UUID
    *
    * @return string
    */
   public static final function getInstanceUuid() {
      return Config::getUuid('instance');
   }

   /**
    * Get registration UUID
    *
    * @return string
    */
   public static final function getRegistrationUuid() {
      return Config::getUuid('registration');
   }

   /**
    * Generates an unique identifier for current instance and store it
    *
    * @return string
    */
   public static final function generateInstanceUuid() {
      return Config::generateUuid('instance');
   }

   /**
    * Generates an unique identifier for current instance and store it
    *
    * @return string
    */
   public static final function generateRegistrationUuid() {
      return Config::generateUuid('registration');
   }


   /**
    * Get view data link along with popup script
    *
    * @return string
    */
   public static function getViewLink() {
      global $CFG_GLPI;

      $out = "<a id='view_telemetry' href='{$CFG_GLPI['root_doc']}/ajax/telemetry.php' class='btn btn-sm btn-info mt-2'>
         ".__('See what would be sent...')."
      </a>";
      $out .= Html::scriptBlock("
         $('#view_telemetry').on('click', function(e) {
            e.preventDefault();

            glpi_ajax_dialog({
               title: __('Telemetry data'),
               url: $('#view_telemetry').attr('href'),
               dialogclass: 'modal-lg'
            });
         });");
      return $out;
   }

   /**
    * Enable telemetry
    *
    * @return void
    */
   public static function enable() {
      global $DB;
      $DB->update(
         'glpi_crontasks',
         ['state' => 1],
         ['name' => 'telemetry']
      );
   }

   /**
    * Is telemetry currently enabled
    *
    * @return boolean
    */
   public static function isEnabled() {
      global $DB;
      $iterator = $DB->request([
         'SELECT' => ['state'],
         'FROM'   => 'glpi_crontasks',
         'WHERE'  => [
            'name'   => 'telemetry',
            'state' => 1
         ]

      ]);
      return count($iterator) > 0;
   }


   /**
    * Display telemetry information
    *
    * @return string
    */
   public static function showTelemetry() {
      $out = "<div class='form-check'>
         <input type='checkbox' class='form-check-input' checked='checked' value='1' name='send_stats' id='send_stats'/>
         <label for='send_stats' class='form-check-label'>
            ".__('Send "usage statistics"')."
         </label>
      </div>";
      $out .= "<strong>" . __("We need your help to improve GLPI and the plugins ecosystem!") ."</strong><br><br>";
      $out .= __("Since GLPI 9.2, we’ve introduced a new statistics feature called “Telemetry”, that anonymously with your permission, sends data to our telemetry website.")."<br>";
      $out .= __("Once sent, usage statistics are aggregated and made available to a broad range of GLPI developers.") . "<br><br>";
      $out .= __("Let us know your usage to improve future versions of GLPI and its plugins!")."<br>";

      $out .= self::getViewLink();
      return $out;
   }

   /**
    * Display reference information
    *
    * @return string
    */
   public static function showReference() {
      $out = "<h3>" . __('Reference your GLPI') . "</h3>";
      $out .= sprintf(
         __("Besides, if you appreciate GLPI and its community, ".
         "please take a minute to reference your organization by filling %1\$s"),
         sprintf(
            "<a href='" . GLPI_TELEMETRY_URI . "/reference?showmodal&uuid=" .
            self::getRegistrationUuid() . "' class='btn btn-sm btn-info' target='_blank'>
               <i class='fas fa-pen-alt me-1'></i>
               %1\$s
            </a>",
            __('the registration form')
         )
      );
      return $out;
   }
}
