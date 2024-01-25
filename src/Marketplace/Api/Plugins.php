<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Marketplace\Api;

use GLPINetwork;
use GuzzleHttp\Client as Guzzle_Client;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Session;
use Toolbox;

class Plugins
{
    protected $httpClient  = null;
    protected $last_error  = null;

    public const COL_PAGE    = 200;
    protected const TIMEOUT     = 5;

    /**
     * Max request attemps on READ operations.
     *
     * @var integer
     */
    protected const MAX_REQUEST_ATTEMPTS = 3;

    /**
     * Flag that indicates that plugin list is truncated (due to an errored response from marketplace API).
     *
     * @var boolean
     */
    protected $is_list_truncated = false;

    public static $plugins = null;

    public function __construct()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $options = [
            'base_uri'        => GLPI_MARKETPLACE_PLUGINS_API_URI,
            'connect_timeout' => self::TIMEOUT,
        ];

        // add proxy string if configured in glpi
        if (!empty($CFG_GLPI["proxy_name"])) {
            $proxy_creds      = !empty($CFG_GLPI["proxy_user"])
                ? $CFG_GLPI["proxy_user"] . ":" . (new \GLPIKey())->decrypt($CFG_GLPI["proxy_passwd"]) . "@"
                : "";
            $proxy_string     = "http://{$proxy_creds}" . $CFG_GLPI['proxy_name'] . ":" . $CFG_GLPI['proxy_port'];
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
     * @return \Psr\Http\Message\ResponseInterface|false
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
            $this->last_error = null; // Reset error buffer
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->last_error = [
                'title'     => "Plugins API error",
                'exception' => $e->getMessage(),
                'request'   => Message::toString($e->getRequest()),
            ];
            if ($e->hasResponse()) {
                $this->last_error['response'] = Message::toString($e->getResponse());
            }

            Toolbox::logDebug($this->last_error);
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

        $i          = 0;
        $attempt_no = 1;

        do {
            $request_options = array_merge_recursive([
                'headers' => [
                    'X-Range' => ($i * self::COL_PAGE) . "-" . (($i + 1) * self::COL_PAGE - 1),
                ],
            ], $options);
            $response = $this->request($endpoint, $request_options, $method);

            if ($response === false || !is_array($current = json_decode($response->getBody(), true))) {
                 // retry on error or unexpected response
                 $attempt_no++;
                 continue;
            }

            if (count($current) === 0) {
                break; // Last page reached
            }

            $collection = array_merge($collection, $current);
            $i++;
            $attempt_no = 1;
        } while ($attempt_no <= self::MAX_REQUEST_ATTEMPTS);

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
    public function getAllPlugins(
        bool $force_refresh = false,
        string $tag_filter = "",
        string $string_filter = "",
        string $sort = 'sort-alpha-asc'
    ) {
        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;

        $cache_key = self::getCacheKey('marketplace_all_plugins');

        if (self::$plugins === null) {
            $plugins_colct = !$force_refresh
                ? $GLPI_CACHE->get($cache_key, null)
                : null;

            if ($plugins_colct === null) {
                $plugins = $this->getPaginatedCollection('plugins');
                $this->is_list_truncated = $this->last_error !== null;

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

                if ($this->last_error === null) {
                    // Cache result only if self::getPaginatedCollection() did not returned an incomplete result due to an error
                    $GLPI_CACHE->set($cache_key, $plugins_colct, HOUR_TIMESTAMP);
                }
            }

            // Filter versions.
            // Done after caching process to be able to handle change of "GLPI_MARKETPLACE_PRERELEASES"
            // without having to purge the cache manually.
            foreach ($plugins_colct as &$plugin) {
                if (!GLPI_MARKETPLACE_PRERELEASES) {
                    $plugin['versions'] = array_filter($plugin['versions'], function ($version) {
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
        } else {
            $plugins_colct = self::$plugins;
        }

        if (strlen($tag_filter) > 0) {
            $tagged_plugins = array_column($this->getPluginsForTag($tag_filter), 'key');
            if ($this->last_error !== null) {
                $this->is_list_truncated = true;
            }
            $plugins_colct  = array_intersect_key($plugins_colct, array_flip($tagged_plugins));
        }

        if (strlen($string_filter) > 0) {
            $plugins_colct = array_filter($plugins_colct, function ($plugin) use ($string_filter) {
                return strpos(strtolower(json_encode($plugin)), strtolower($string_filter)) !== false;
            });
        }

        // manage sorting of collection
        uasort($plugins_colct, function ($plugin1, $plugin2) use ($sort) {
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
     * @param int    $total Total count of plugin found with given filters
     *
     * @return array full collection
     */
    public function getPaginatedPlugins(
        bool $force_refresh = false,
        string $tag_filter = "",
        string $string_filter = "",
        int $page = 1,
        int $nb_per_page = 15,
        string $sort = 'sort-alpha-asc',
        int &$total = 0
    ) {
        $plugins = $this->getAllPlugins($force_refresh, $tag_filter, $string_filter, $sort);

        $total = count($plugins);

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
    public function getNbPlugins(string $tag_filter = "")
    {
        $plugins = $this->getAllPlugins(false, $tag_filter);

        return count($plugins);
    }


    /**
     * Get a single plugin array
     *
     * @param string $key plugin system name
     * @param bool $force_refresh if false, we will return results stored in local cache
     *
     * @return array plugin data
     */
    public function getPlugin(string $key = "", bool $force_refresh = false): array
    {
        $plugins_list = $this->getAllPlugins($force_refresh);

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
    public function incrementPluginDownload(string $key, string $version)
    {
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
    public function getTopTags(): array
    {
        /** @var array $CFG_GLPI */
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
    public function getPluginsForTag(string $tag = "", bool $force_refresh = false): array
    {
        /** @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE */
        global $GLPI_CACHE;

        $cache_key = self::getCacheKey("marketplace_tag_$tag");

        $plugins_colct = !$force_refresh ? $GLPI_CACHE->get($cache_key, []) : [];

        if (!count($plugins_colct)) {
            $plugins_colct = $this->getPaginatedCollection("tags/{$tag}/plugin");

            if ($this->last_error === null) {
                // Cache result only if self::getPaginatedCollection() did not returned an incomplete result due to an error
                $GLPI_CACHE->set($cache_key, $plugins_colct, HOUR_TIMESTAMP);
            }
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
    public function downloadArchive(string $url, string $dest, string $plugin_key, bool $track_progress = true): bool
    {
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
            $options['progress'] = function ($downloadTotal, $downloadedBytes) use ($plugin_key) {
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

    /**
     * Indicates whether the plugin list is truncated, mostly due to a marketplace API server unavailability.
     *
     * @return bool
     */
    public function isListTruncated(): bool
    {
        return $this->is_list_truncated;
    }

    private static function getCacheKey(string $item_key): string
    {
        return $item_key . '_' . GLPINetwork::getRegistrationKey();
    }
}
