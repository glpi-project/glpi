<?php

namespace Glpi\Debug;

class Profile
{
    private string $id;

    private ?string $parent_id;

    private string $start_time;

    /**
     * @var bool If true, the profile is loaded from an old request so current debug info should not be accessible from this profile
     */
    private bool $is_readonly = false;

    /**
     * @var array|null Debug info for this profile. This is only set for loaded/readonly profiles.
     */
    private ?array $debug_info = null;

    private static ?self $current = null;

    /**
     * @var bool If true, top-level profiles will be saved to disk.
     * This is false by default since these are usually initial pages loads and the data is directly used, so saving it is not useful.
     */
    private const SAVE_TOP_LEVEL_PROFILES = false;

    /**
     * The threshold for the size of the debug info before it is saved to disk rather than sent directly to the browser.
     */
    private const DEBUG_INFO_HEADER_THRESHOLD = 4096;

    public function __construct(string $id, ?string $parent_id)
    {
        $this->id = $id;
        $this->parent_id = $parent_id;
        $this->start_time = $_SESSION['glpi_currenttime'];
    }

    public static function getCurrent(): self
    {
        if (self::$current === null) {
            $id = $_SERVER['HTTP_X_GLPI_AJAX_ID'] ?? bin2hex(random_bytes(8));
            $parent_id = $_SERVER['HTTP_X_GLPI_AJAX_PARENT_ID'] ?? null;
            self::$current = new self($id, $parent_id);

            // If this is a sub-request, we can send the $_SERVER global data back in a header now to avoid having to save it later.
            if ($parent_id !== null) {
                $debug_info_header = json_encode([
                    'globals' => [
                        'server' => $_SERVER ?? [],
                    ]
                ], JSON_THROW_ON_ERROR);
                $header_len = strlen($debug_info_header);
                if ($header_len < self::DEBUG_INFO_HEADER_THRESHOLD) {
                    header('X-GLPI-Debug-Server-Global: ' . $debug_info_header);
                }
            }

            // Register a shutdown function to save the profile
            register_shutdown_function(static function () {
                self::getCurrent()->save();
            });
        }
        return self::$current;
    }

    public static function load(string $id, bool $delete = true): ?self
    {
        $profile_location = GLPI_TMP_DIR . '/debug/profiles/';
        $profile_name = $id . '.json.gz';

        if (!file_exists($profile_location . $profile_name)) {
            return null;
        }

        try {
            $profile_data = json_decode(gzdecode(file_get_contents($profile_location . $profile_name)), true, 512, JSON_THROW_ON_ERROR);
            $profile = new self($profile_data['id'], $profile_data['parent_id']);
            $profile->is_readonly = true;
            $profile->start_time = $profile_data['start_time'];
            $profile->debug_info = $profile_data;

            if ($delete) {
                unlink($profile_location . $profile_name);
            }
            return $profile;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getID(): string
    {
        return $this->id;
    }

    public function getParentID(): ?string
    {
        return $this->parent_id;
    }

    public function getDebugInfo()
    {
        if ($this->is_readonly) {
            return $this->debug_info;
        }
        global $CFG_GLPI, $DEBUG_SQL, $SQL_TOTAL_REQUEST, $TIMER_DEBUG;

        $queries_duration = $CFG_GLPI["debug_sql"] ? array_sum($DEBUG_SQL['times']) : 0;
        $execution_time = $TIMER_DEBUG->getTime();

        /**
         * Each top-level key corresponds to a debug toolbar widget id
         */
        $debug_info = [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'server_performance' => [
                'execution_time' => (float) $execution_time,
                'memory_usage' => memory_get_usage(),
                'memory_peak' => memory_get_peak_usage(),
                'memory_limit' => \Toolbox::getMemoryLimit(),
            ],
            'sql' => [
                'total_requests' => $CFG_GLPI["debug_sql"] ? $SQL_TOTAL_REQUEST : 0,
                'total_duration' => $CFG_GLPI["debug_sql"] ? sprintf(_n('%s second', '%s seconds', $queries_duration), $queries_duration) : 0,
                'queries' => [],
            ],
            'globals' => []
        ];

        if ($this->parent_id === null) {
            // We only need these for top-level requests. For AJAX, this data is already known by the client.
            $debug_info['globals']['get'] = $_GET ?? [];
            $debug_info['globals']['post'] = $_POST ?? [];
            // We don't worry about session data for AJAX given the size and need to save the profile to the disk
            $debug_info['globals']['session'] = $_SESSION ?? [];
            $debug_info['globals']['server'] = $_SERVER ?? [];
        }

        foreach ($DEBUG_SQL['queries'] as $num => $query) {
            $info = [
                'num' => $num,
                'query' => $query,
                'time' => $DEBUG_SQL['times'][$num] ?? '',
                'rows' => $DEBUG_SQL['rows'][$num] ?? 0,
                'errors' => $DEBUG_SQL['errors'][$num] ?? '',
                'warnings' => '',
            ];
            if (isset($DEBUG_SQL['warnings'][$num])) {
                foreach ($DEBUG_SQL['warnings'][$num] as $warning) {
                    $info['warnings'] .= sprintf('%s: %s', $warning['Code'], $warning['Message']) . "\n";
                }
            }
            $debug_info['sql']['queries'][] = $info;
        }

        return $debug_info;
    }

    public function save(): void
    {
        if ($this->is_readonly) {
            return;
        }
        if (!self::SAVE_TOP_LEVEL_PROFILES && $this->parent_id === null) {
            return;
        }
        $profile_location = GLPI_TMP_DIR . '/debug/profiles/';
        $profile_name = $this->id . '.json.gz';

        // create missing directory
        if (!is_dir($profile_location)) {
            if (!mkdir($created_dir = $profile_location, 0770, true) && !is_dir($created_dir)) {
                throw new \RuntimeException(sprintf('Failed to create debug profile directory %s', $created_dir));
            }
        }

        $info = $this->getDebugInfo();
        $info['start_time'] = $this->start_time;

        $json = json_encode($info);
        $gz = gzencode($json, 9);
        file_put_contents($profile_location . $profile_name, $gz);
    }
}
