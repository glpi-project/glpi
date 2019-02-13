<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

namespace Glpi\Application;

use Glpi\ConfigParams;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Manager of local configuration file used by dependency injection.
 *
 * @since 10.0.0
 */
class LocalConfigurationManager
{
    /**
     * @var string
     */
    private $configDir;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var Yaml
     */
    private $yaml;

    /**
     * @param string           $configDir
     * @param PropertyAccessor $propertyAccessor
     * @param Yaml             $yamlParser
     */
    public function __construct(
        string $configDir,
        PropertyAccessor $propertyAccessor,
        Yaml $yaml
    ) {
        $this->configDir = $configDir;
        $this->propertyAccessor = $propertyAccessor;
        $this->yaml = $yaml;
    }

    /**
     * Set local configuration values from legacy cache configuration found in DB.
     * This method will not overwrite existing parameters in local configuration file.
     *
     * @param ConfigParams $configParams
     * @param string       $cacheDir
     *
     * @return void
     */
    public function setCacheValuesFromLegacyConfig(ConfigParams $configParams, string $cacheDir)
    {
        $localParams = $this->getParametersFromFile();

        if (null === $this->propertyAccessor->getValue($localParams, '[application_cache]')) {
            $cacheDbConfig = $this->convertCacheDbConfigToParameters(
                $configParams,
                'cache_db',
                $cacheDir
            );
            if (!empty($cacheDbConfig)) {
                $this->setParameterValue('[application_cache]', $cacheDbConfig);
            }
        }

        if (null === $this->propertyAccessor->getValue($localParams, '[translation_cache]')) {
            $cacheTransConfig = $this->convertCacheDbConfigToParameters(
                $configParams,
                'cache_trans',
                $cacheDir
            );
            if (!empty($cacheTransConfig)) {
                $this->setParameterValue('[translation_cache]', $cacheTransConfig);
            }
        }
    }

    /**
     * Set a parameter value and saves it into the local configuration file.
     *
     * @param string  $path       Path of the parameter in Symfony Property access format.
     *                            See https://symfony.com/doc/current/components/property_access.html
     * @param mixed   $value      Value to save.
     * @param boolean $overwrite  Allow overriding of already defined parameters.
     *
     * @return void
     */
    public function setParameterValue(string $path, $value, bool $overwrite = true)
    {
        $localParams = $this->getParametersFromFile();

        if (!$overwrite && null !== $this->propertyAccessor->getValue($localParams, $path)) {
            // Do not overwrite existing value if not allowed.
            return;
        }

        $this->propertyAccessor->setValue($localParams, $path, $value);

        $filename = $this->getLocalConfigFilename();

        if ((file_exists($filename) && !is_writable($filename))
            || (!file_exists($filename) && !is_writable(dirname($filename)))) {
            throw new \RuntimeException(
                sprintf('Unable to write local configuration file "%s".', $filename)
            );
        }

        file_put_contents(
            $filename,
            $this->yaml->dump(
                [
                    'parameters' => $localParams
                ],
                999 // No inline for more readability
            )
        );
    }

    /**
     * Return parameters from local config file.
     *
     * @return array
     */
    private function getParametersFromFile(): array
    {
        $filename = $this->getLocalConfigFilename();

        if (!is_file($filename) || !is_readable($filename)) {
            return [];
        }

        $localConfig = $this->yaml->parseFile($this->configDir . '/parameters.yaml');

        return is_array($localConfig) && array_key_exists('parameters', $localConfig) && is_array($localConfig['parameters'])
            ? $localConfig['parameters']
            : [];
    }

    /**
     * Returns local config filename.
     *
     * @return string
     */
    private function getLocalConfigFilename(): string
    {
        return $this->configDir . '/parameters.yaml';
    }

    /**
     * Convert cache configuration from database (GLPI < 10.0) to a format
     * acceptable for local configuration file.
     *
     * @param ConfigParams $configParams
     * @param string       $configKey
     *
     * @return array
     */
    private function convertCacheDbConfigToParameters(
        ConfigParams $configParams,
        string $configKey,
        string $cacheDir
    ): array {
        if (!$configParams->offsetExists($configKey)) {
            return [];
        }

        $dbConfig = json_decode($configParams[$configKey], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($dbConfig)) {
            return [];
        }

        // Make configured path relative to GLPI cache directory
        // This was done by previous cache factory but is not done since 10.0
        // to give more flexibility in configuration.
        if (array_key_exists('adapter', $dbConfig)) {
            if ('dba' === $dbConfig['adapter'] && isset($dbConfig['options']['pathname'])) {
                $dbConfig['options']['pathname'] = $cacheDir . '/' . $dbConfig['options']['pathname'];
            } elseif ('filesystem' === $dbConfig['adapter'] && isset($dbConfig['options']['cache_dir'])) {
                $dbConfig['options']['cache_dir'] = $cacheDir . '/' . $dbConfig['options']['cache_dir'];
            }
        }

        return $dbConfig;
    }
}
