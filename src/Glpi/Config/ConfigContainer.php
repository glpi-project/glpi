<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Config;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Toolbox;
use Traversable;

/**
 * Array-like container backing the global `$CFG_GLPI` configuration.
 *
 * It behaves like the plain array it replaces (offset access, iteration, count,
 * JSON serialization) but lets us flag individual keys as deprecated: reading a
 * deprecated key emits an `E_USER_DEPRECATED` notice (once per key and request)
 * through {@see Toolbox::deprecated()}, while still returning the real value so
 * that legacy/plugin code keeps working unchanged.
 *
 * {@see self::offsetGet()} returns values **by reference** on purpose: this is
 * what allows nested writes and appends on array values to keep working, e.g.
 * `$CFG_GLPI['asset_types'][] = MyAsset::class;`.
 *
 * @implements ArrayAccess<array-key, mixed>
 * @implements IteratorAggregate<array-key, mixed>
 */
final class ConfigContainer implements ArrayAccess, IteratorAggregate, Countable, JsonSerializable
{
    /**
     * Config keys that are additionally hidden by {@see self::getSafeConfig()}
     * when the `$safer` flag is set (e.g. to avoid disclosing emails).
     */
    private const SAFER_UNDISCLOSED_FIELDS = ['admin_email', 'replyto_email'];

    /**
     * Deprecation definitions, indexed by config key.
     *
     * @var array<string, array{message: string, version: ?string}>
     */
    private array $deprecated = [];

    /**
     * Keys for which the deprecation notice has already been emitted during the
     * current request (used to avoid flooding the logs).
     *
     * @var array<string, true>
     */
    private array $warned = [];

    /**
     * @param array<array-key, mixed> $config
     */
    public function __construct(private array $config) {}

    /**
     * Flag a configuration key as deprecated.
     *
     * @param string      $key     Configuration key to deprecate.
     * @param string      $message Message to display when the key is read.
     * @param string|null $version Version the deprecation starts from (see {@see Toolbox::deprecated()}).
     */
    public function deprecateKey(string $key, string $message, ?string $version = null): void
    {
        $this->deprecated[$key] = ['message' => $message, 'version' => $version];
    }

    /**
     * @param string $offset
     *
     * ⚠️ Returns by reference so that nested writes/appends keep working, exactly
     * like on a plain array (e.g. `$CFG_GLPI['asset_types'][] = ...`, or
     * auto-vivification `$CFG_GLPI['new_registry'][$key] = ...`). To support
     * writes to a not-yet-existing key, a missing offset is materialized as
     * `null` before its reference is returned — mirroring PHP array behavior.
     */
    public function &offsetGet(mixed $offset): mixed
    {
        if (isset($this->deprecated[$offset]) && !isset($this->warned[$offset])) {
            $this->warned[$offset] = true;
            Toolbox::deprecated(
                $this->deprecated[$offset]['message'],
                true,
                $this->deprecated[$offset]['version'],
            );
        }

        if (!array_key_exists($offset, $this->config)) {
            $this->config[$offset] = null;
        }

        return $this->config[$offset];
    }

    /**
     * @param string|null $offset
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->config[] = $value;
        } else {
            $this->config[$offset] = $value;
        }
    }

    /**
     * @param string $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->config[$offset]);
    }

    /**
     * @param string $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->config[$offset]);
    }

    /**
     * @return ArrayIterator<array-key, mixed>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->config);
    }

    public function count(): int
    {
        return count($this->config);
    }

    public function jsonSerialize(): mixed
    {
        return $this->config;
    }

    /**
     * Return a plain-array copy of the whole configuration.
     *
     * Escape hatch for the few core code paths that need to run native array
     * functions (`array_keys()`, `array_diff_key()`, ...) over the full config.
     *
     * @return array<array-key, mixed>
     */
    public function getArrayCopy(): array
    {
        return $this->config;
    }

    /**
     * Return the configuration without the keys that must not be disclosed
     * (passwords, tokens, ...), with values overridden by the current session
     * preferences when available.
     *
     * @param bool $safer Also hide "safer" undisclosed fields (e.g. emails).
     *
     * @return array<array-key, mixed>
     */
    public function getSafeConfig(bool $safer = false): array
    {
        $safe_config = array_diff_key($this->config, array_flip(\Config::$undisclosedFields));

        if ($safer) {
            $safe_config = array_diff_key($safe_config, array_flip(self::SAFER_UNDISCLOSED_FIELDS));
        }

        // override with session values
        foreach ($safe_config as $key => &$value) {
            $value = $_SESSION['glpi' . $key] ?? $value;
        }

        return $safe_config;
    }
}
