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

namespace Glpi\Marketplace\Api;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Response;
use \GuzzleHttp\Client as Guzzle_Client;
use \GLPINetwork;
use \Toolbox;
use \Session;

class Plugins {
   protected $httpClient  = null;
   protected $last_error  = "";

   public    const COL_PAGE = 200;
   protected const TIMEOUT  = 5;

   static $plugins = [];

   function __construct(bool $connect = false) {
      global $CFG_GLPI;

      $options = [
         'base_uri'        => GLPI_MARKETPLACE_PLUGINS_API_URI,
         'connect_timeout' => self::TIMEOUT,
      ];

      // add proxy string if configured in glpi
      if (!empty($CFG_GLPI["proxy_name"])) {
         $proxy_creds      = !empty($CFG_GLPI["proxy_user"])
            ? $CFG_GLPI["proxy_user"].":".Toolbox::sodiumDecrypt($CFG_GLPI["proxy_passwd"])."@"
            : "";
         $proxy_string     = "http://{$proxy_creds}".$CFG_GLPI['proxy_name'].":".$CFG_GLPI['proxy_port'];
         $options['proxy'] = $proxy_string;
      }

      // init guzzle client with base options
      $this->httpClient = new Guzzle_Client($options);
   }


   /**
    * Send a http request to services api
    * using the base url set in constructor and the current endpoint
    *
    * @param string $endpoint which resource whe need to query
    * @param array $options array of options for guzzle lib
    * @param string $method GET/POST, etc
    *
    * @return Psr\Http\Message\ResponseInterface|false
    */
   private function request(
      string $endpoint = '',
      array $options = [],
      string $method = 'GET'
   ) {
      if (!GLPINetwork::isRegistered()) {
         // Simulate empty response if registration key is not valid
         return new Response(200, [], '[]');
      }

      $options['headers'] = array_merge_recursive(
         [
            'Accept'             => 'application/json',
            'User-Agent'         => GLPINetwork::getGlpiUserAgent(),
            'X-Registration-Key' => GLPINetwork::getRegistrationKey(),
            'X-Glpi-Network-Uid' => GLPINetwork::getGlpiNetworkUid(),
         ],
         $options['headers'] ?? []
      );

      try {
         $response = $this->httpClient->request($method, $endpoint, $options);

      } catch (RequestException $e) {
         $this->last_error = [
            'title'     => "Plugins API error",
            'exception' => $e->getMessage(),
            'request'   => Psr7\str($e->getRequest()),
         ];
         if ($e->hasResponse()) {
             $this->last_error['response'] = Psr7\str($e->getResponse());
         }

         if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            Toolbox::logDebug($this->last_error);
         }
         return false;
      }

      return $response;
   }


   /**
    * Send an http request on an endpoint accepting paginated queries
    *
    * @param string $endpoint which resource whe need to query
    * @param array $options array of options for guzzle lib
    * @param string $method GET/POST, etc
    *
    * @return array full collection
    */
   private function getPaginatedCollection(
      string $endpoint = '',
      array $options = [],
      string $method = 'GET'
   ): array {
      $collection = [];
      $i    = 0;
      do {
         $request_options = array_merge_recursive([
            'headers' => [
               'X-Range' => ($i * self::COL_PAGE)."-".(($i + 1) * self::COL_PAGE - 1),
            ],
         ], $options);
         $response = $this->request($endpoint, $request_options, $method);

         if ($current = ($response !== false ? json_decode($response->getBody(), true) : false)) {
            $collection = array_merge($collection, $current);
         }

         $i++;
      } while ($current !== false && count($current));

      return $collection;
   }


   /**
    * Return the full list of avaibles plugins on services API
    *
    * @param bool   $force_refresh if false, we will return results stored in local cache
    * @param string $tag_filter filter the plugin list by given tag
    * @param string $string_filter filter the plugin list by given string
    * @param string $sort sort-alpha-asc|sort-alpha-desc|sort-dl|sort-update|sort-added|sort-note
    *
    * @return array collection of plugins
    */
   function getAllPlugins(
      bool $force_refresh = false,
      string $tag_filter = "",
      string $string_filter = "",
      string $sort = 'sort-alpha-asc'
   ) {
      global $GLPI_CACHE;

      $plugins_colct = !$force_refresh
         ? $GLPI_CACHE->get('marketplace_all_plugins', null)
         : null;

      if ($plugins_colct === null) {
         $plugins = $this->getPaginatedCollection('plugins');

         // replace keys indexes by system names
         $plugins_keys  = array_column($plugins, 'key');
         $plugins_colct = array_combine($plugins_keys, $plugins);

         foreach ($plugins_colct as &$plugin) {
            usort(
               $plugin['versions'],
               function ($a, $b) {
                  return version_compare($a['num'], $b['num']);
               }
            );
         }

         $GLPI_CACHE->set('marketplace_all_plugins', $plugins_colct, HOUR_TIMESTAMP);
      }

      // Filter versions.
      // Done after caching process to be able to handle change of "GLPI_MARKETPLACE_PRERELEASES"
      // without having to purge the cache manually.
      foreach ($plugins_colct as &$plugin) {
         if (!GLPI_MARKETPLACE_PRERELEASES) {
            $plugin['versions'] = array_filter($plugin['versions'], function($version) {
               return !isset($version['stability']) || $version['stability'] === "stable";
            });
         }

         if (count($plugin['versions']) === 0) {
            continue;
         }

         $higher_version = end($plugin['versions']);
         if (is_array($higher_version)) {
            $plugin['installation_url'] = $higher_version['download_url'];
            $plugin['version'] = $higher_version['num'];
         }
      }
      self::$plugins = $plugins_colct;

      if (strlen($tag_filter) > 0) {
         $tagged_plugins = array_column($this->getPluginsForTag($tag_filter), 'key');
         $plugins_colct  = array_intersect_key($plugins_colct, array_flip($tagged_plugins));
      }

      if (strlen($string_filter) > 0) {
         $plugins_colct = array_filter($plugins_colct, function($plugin) use ($string_filter) {
            return strpos(strtolower(json_encode($plugin)), strtolower($string_filter)) !== false;
         });
      }

      // manage sorting of collection
      uasort($plugins_colct, function($plugin1, $plugin2) use ($sort) {
         switch ($sort) {
            case "sort-alpha-asc":
               return strnatcasecmp($plugin1['name'], $plugin2['name']);
            case "sort-alpha-desc":
               return strnatcasecmp($plugin2['name'], $plugin1['name']);
            case "sort-dl":
               return strnatcmp($plugin2['download_count'], $plugin1['download_count']);
            case "sort-update":
               return strnatcmp($plugin2['date_updated'], $plugin1['date_updated']);
            case "sort-added":
               return strnatcmp($plugin2['date_added'], $plugin1['date_added']);
            case "sort-note":
               return strnatcmp($plugin2['note'], $plugin1['note']);
         }
      });

      return $plugins_colct;
   }


   /**
    * Return plugins list for the given page
    *
    * @param bool   $force_refresh if false, we will return results stored in local cache
    * @param string $tag_filter filter the plugin list by given tag
    * @param string $string_filter filter the plugin list by given string
    * @param int    $page which page to query
    * @param int    $nb_per_page how manyu per page we want
    * @param string $sort sort-alpha-asc|sort-alpha-desc|sort-dl|sort-update|sort-added|sort-note
    *
    * @return array full collection
    */
   function getPaginatedPlugins(
      bool $force_refresh = false,
      string $tag_filter = "",
      string $string_filter = "",
      int $page = 1,
      int $nb_per_page = 15,
      string $sort = 'sort-alpha-asc'
   ) {
      $plugins = $this->getAllPlugins($force_refresh, $tag_filter, $string_filter, $sort);

      $plugins_page = array_splice($plugins, max($page - 1, 0) * $nb_per_page, $nb_per_page);
      return $plugins_page;
   }


   /**
    * return the number of available plugins in distant API
    *
    * @param string $tag_filter filter the plugin list by given tag
    *
    * @return int number of plugins
    */
   function getNbPlugins(string $tag_filter = "") {
      $plugins = $this->getAllPlugins(false, $tag_filter);

      return count($plugins);
   }


   /**
    * get top 10 plugins sorted by trending (most downloaded in the last month) criterion
    *
    * @return array collection of plugins
    */
   function getTrendingPlugins() {
      return $this->getTopPlugins("trending");
   }


   /**
    * get top 10 plugins sorted by popular (most downloaded all time) criterion
    *
    * @return array collection of plugins
    */
   function getPopularPlugins() {
      return $this->getTopPlugins("popular");
   }

   /**
    * get top 10 plugins sorted by their submition date (DESC sort) criterion
    *
    * @return array collection of plugins
    */
   function getNewPlugins() {
      return $this->getTopPlugins("new");
   }

   /**
    * get top 10 plugins sorted by their update date (DESC sort) criterion
    *
    * @return array collection of plugins
    */
   function getUpdatedPlugins() {
      return $this->getTopPlugins("updated");
   }

   /**
    * get top 10 plugins sorted by given criterion (see other getTopXXX methods)
    *
    * @param string $endpoint criterion to filter plugsin
    *
    * @return array collection of plugins
    */
   function getTopPlugins(string $endpoint = "") {
      $response = $this->request("plugin/{$endpoint}");

      if ($response === false) {
         return [];
      }

      $top      = json_decode($response->getBody(), true);
      $key_list = array_column($top, 'key', 'key');
      $plugins  = $this->getAllPlugins();

      $top_plugins = array_filter($plugins, function($plugin) use($key_list) {
         return in_array($plugin['key'], $key_list);
      });

      return $top_plugins;
   }


   /**
    * Get a single plugin array
    *
    * @param string $key plugin system name
    * @param bool $force_refresh if false, we will return results stored in local cache
    *
    * @return array plugin data
    */
   public function getPlugin(string $key = "", bool $force_refresh = false): array {
      $plugins_list = [];
      if ($force_refresh || !count(self::$plugins)) {
         $plugins_list = $this->getAllPlugins($force_refresh);
      } else {
         $plugins_list = self::$plugins;
      }

      return $plugins_list[$key] ?? [];
   }


   /**
    * Inform plugins API that a plugin (by its key) has been downloaded
    * and the download counter must be incremented
    *
    * @param string $key      plugin system key
    * @param string $version  plugin version
    *
    * @return void we don't wait for a response, this a fire and forget request
    */
   public function incrementPluginDownload(string $key, string $version) {
      $this->request(
         "plugin/{$key}/download/{$version}",
         [
            'allow_redirects' => false, // Prevent follow redirects to download page sent by Plugins API
         ]
      );
   }


   /**
    * Get top list of tags for current session language
    *
    * @return array top tags
    */
   public function getTopTags(): array {
      global $CFG_GLPI;

      $response  = $this->request('tags/top', [
         'headers' => [
            'X-Lang' => $CFG_GLPI['languages'][$_SESSION['glpilanguage']][2]
         ]
      ]);

      if ($response === false) {
         return [];
      }

      $toptags   = json_decode($response->getBody(), true);

      return $toptags;
   }


   /**
    * get a plugins collection for the givent tag
    *
    * @param string $tag to filter plugins
    * @param bool $force_refresh if false, we will return results stored in local cache
    *
    * @return array filtered plugin collection
    */
   public function getPluginsForTag(string $tag = "", bool $force_refresh = false): array {
      global $GLPI_CACHE;

      $plugins_colct = [];
      if (!$force_refresh && $GLPI_CACHE->has("marketplace_tag_$tag")) {
         $plugins_colct = $GLPI_CACHE->get("marketplace_tag_$tag");
      }

      if (!count($plugins_colct)) {
         $plugins_colct = $this->getPaginatedCollection("tags/{$tag}/plugin");
         $GLPI_CACHE->set("marketplace_tag_$tag", $plugins_colct, HOUR_TIMESTAMP);
      }

      return $plugins_colct;
   }


   /**
    * Download plugin archive and follow progress with a session var `marketplace_dl_progress`
    *
    * @param string $url where is the plugin
    * @param string $dest  where we store it it
    * @param string $plugin_key plugin system name
    *
    * @return bool
    */
   public function downloadArchive(string $url, string $dest, string $plugin_key, bool $track_progress = true): bool {
      if ($track_progress) {
         if (!isset($_SESSION['marketplace_dl_progress'])) {
            $_SESSION['marketplace_dl_progress'] = [];
         }
         $_SESSION['marketplace_dl_progress'][$plugin_key] = 0;
      }

      // close session to permits polling of progress by frontend
      session_write_close();

      $options = [
         'headers'  => [
            'Accept' => '*/*',
         ],
         'sink'     => $dest,
      ];
      if ($track_progress) {
         // track download progress
         $options['progress'] = function($downloadTotal, $downloadedBytes) use ($plugin_key) {
            // Prevent "net::ERR_RESPONSE_HEADERS_TOO_BIG" error
            // Each time Session::start() is called, PHP add a 'Set-Cookie' header,
            // so if a plugin takes more than a few seconds to be downloaded, PHP will set too many
            // 'Set-Cookie' headers and response will not be accepted by browser.
            // We can remove the 'Set-Cookie' here as it will be put back on next instruction (Session::start()).
            header_remove('Set-Cookie');

            // restart session to store percentage of download for this plugin
            Session::start();

            // calculate percent based on the size and store it in session
            $percent = 0;
            if ($downloadTotal > 0) {
               $percent = round($downloadedBytes * 100 / $downloadTotal);
            }
            $_SESSION['marketplace_dl_progress'][$plugin_key] = $percent;

            // reclose session to avoid blocking ajax requests
            session_write_close();
         };
      }

      $response = $this->request($url, $options);

      // restart session to permits write of vars
      // (later, we also may have some addMessageAfterRedirect to provider errors to user)
      Session::start();

      if ($track_progress) {
         // force finish of download (to avoid keeping js loop in case of errors)
         $_SESSION['marketplace_dl_progress'][$plugin_key] = 100;
      }

      return $response !== false && $response->getStatusCode() === 200;
   }
}
