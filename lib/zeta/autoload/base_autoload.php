<?php
/**
 * Autoloader definition for the Base component.
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @version //autogentag//
 * @filesource
 * @package Base
 */

return array(
    'ezcBaseException'                            => 'Base/exceptions/exception.php',
    'ezcBaseFileException'                        => 'Base/exceptions/file_exception.php',
    'ezcBaseAutoloadException'                    => 'Base/exceptions/autoload.php',
    'ezcBaseDoubleClassRepositoryPrefixException' => 'Base/exceptions/double_class_repository_prefix.php',
    'ezcBaseExtensionNotFoundException'           => 'Base/exceptions/extension_not_found.php',
    'ezcBaseFileIoException'                      => 'Base/exceptions/file_io.php',
    'ezcBaseFileNotFoundException'                => 'Base/exceptions/file_not_found.php',
    'ezcBaseFilePermissionException'              => 'Base/exceptions/file_permission.php',
    'ezcBaseFunctionalityNotSupportedException'   => 'Base/exceptions/functionality_not_supported.php',
    'ezcBaseInitCallbackConfiguredException'      => 'Base/exceptions/init_callback_configured.php',
    'ezcBaseInitInvalidCallbackClassException'    => 'Base/exceptions/invalid_callback_class.php',
    'ezcBaseInvalidParentClassException'          => 'Base/exceptions/invalid_parent_class.php',
    'ezcBasePropertyNotFoundException'            => 'Base/exceptions/property_not_found.php',
    'ezcBasePropertyPermissionException'          => 'Base/exceptions/property_permission.php',
    'ezcBaseSettingNotFoundException'             => 'Base/exceptions/setting_not_found.php',
    'ezcBaseSettingValueException'                => 'Base/exceptions/setting_value.php',
    'ezcBaseValueException'                       => 'Base/exceptions/value.php',
    'ezcBaseWhateverException'                    => 'Base/exceptions/whatever.php',
    'ezcBaseOptions'                              => 'Base/options.php',
    'ezcBaseStruct'                               => 'Base/struct.php',
    'ezcBase'                                     => 'Base/base.php',
    'ezcBaseAutoloadOptions'                      => 'Base/options/autoload.php',
    'ezcBaseConfigurationInitializer'             => 'Base/interfaces/configuration_initializer.php',
    'ezcBaseExportable'                           => 'Base/interfaces/exportable.php',
    'ezcBaseFeatures'                             => 'Base/features.php',
    'ezcBaseFile'                                 => 'Base/file.php',
    'ezcBaseFileFindContext'                      => 'Base/structs/file_find_context.php',
    'ezcBaseInit'                                 => 'Base/init.php',
    'ezcBaseMetaData'                             => 'Base/metadata.php',
    'ezcBaseMetaDataPearReader'                   => 'Base/metadata/pear.php',
    'ezcBaseMetaDataTarballReader'                => 'Base/metadata/tarball.php',
    'ezcBasePersistable'                          => 'Base/interfaces/persistable.php',
    'ezcBaseRepositoryDirectory'                  => 'Base/structs/repository_directory.php',
);
?>
