<?php
/**
 * Include file that can be used for a quick setup of the eZ Components.
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
 * @access private
 */
$dir = dirname( __FILE__ );
$dirParts = explode( DIRECTORY_SEPARATOR, $dir );

if ( $dirParts[count( $dirParts ) - 1] === 'src' )
{
    $baseDir = join( DIRECTORY_SEPARATOR, array_slice( $dirParts, 0, -2 ) );
    require $baseDir . '/Base/src/base.php'; // svn, bundle
}
else if ( $dirParts[count( $dirParts ) - 2] === 'ezc' )
{
    $baseDir = join( DIRECTORY_SEPARATOR, array_slice( $dirParts, 0, -2 ) );
    require $baseDir . '/ezc/Base/base.php'; // pear
}
else
{
    die( "Your environment isn't properly set-up. Please refer to the eZ components documentation at http://components.ez.no/doc ." );
}

/**
 * Implements the __autoload mechanism for PHP - which can only be done once
 * per request.
 *
 * @param string $className  The name of the class that should be loaded.
 */
function __autoload( $className )
{
	ezcBase::autoload( $className );
}
?>
