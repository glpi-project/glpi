<?php
/**
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

/**
 * Provides a selection of static independent methods to provide functionality
 * for file and file system handling.
 *
 * This example shows how to use the findRecursive method:
 * <code>
 * <?php
 * // lists all the files under /etc (including subdirectories) that end in
 * // .conf
 * $confFiles = ezcBaseFile::findRecursive( "/etc", array( '@\.conf$@' ) );
 *
 * // lists all autoload files in the components source tree and excludes the
 * // ones in the autoload subdirectory. Statistics are returned in the $stats
 * // variable which is passed by reference.
 * $files = ezcBaseFile::findRecursive(
 *     "/dat/dev/ezcomponents",
 *     array( '@src/.*_autoload.php$@' ),
 *     array( '@/autoload/@' ),
 *     $stats
 * );
 *
 * // lists all binaries in /bin except the ones starting with a "g"
 * $data = ezcBaseFile::findRecursive( "/bin", array(), array( '@^/bin/g@' ) );
 * ?>
 * </code>
 *
 * @package Base
 * @version //autogentag//
 * @mainclass
 */
class ezcBaseFile
{
    /**
     * This is the callback used by findRecursive to collect data.
     *
     * This callback method works together with walkRecursive() and is called
     * for every file/and or directory. The $context is a callback specific
     * container in which data can be stored and shared between the different
     * calls to the callback function. The walkRecursive() function also passes
     * in the full absolute directory in $sourceDir, the filename in $fileName
     * and file information (such as size, modes, types) as an array as
     * returned by PHP's stat() in the $fileInfo parameter.
     *
     * @param ezcBaseFileFindContext $context
     * @param string $sourceDir
     * @param string $fileName
     * @param array(stat) $fileInfo
     */
    static protected function findRecursiveCallback( ezcBaseFileFindContext $context, $sourceDir, $fileName, $fileInfo )
    {
        // ignore if we have a directory
        if ( $fileInfo['mode'] & 0x4000 )
        {
            return;
        }

        // update the statistics
        $context->elements[] = $sourceDir . DIRECTORY_SEPARATOR . $fileName;
        $context->count++;
        $context->size += $fileInfo['size'];
    }

    /**
     * Walks files and directories recursively on a file system
     *
     * This method walks over a directory and calls a callback from every file
     * and directory it finds. You can use $includeFilters to include only
     * specific files, and $excludeFilters to exclude certain files from being
     * returned. The function will always go into subdirectories even if the
     * entry would not have passed the filters.
     *
     * The callback is passed in the $callback parameter, and the
     * $callbackContext will be send to the callback function/method as
     * parameter so that you can store data in there that persists with all the
     * calls and recursive calls to this method. It's up to the callback method
     * to do something useful with this. The callback function's parameters are
     * in order:
     *
     * <ul>
     * <li>ezcBaseFileFindContext $context</li>
     * <li>string $sourceDir</li>
     * <li>string $fileName</li>
     * <li>array(stat) $fileInfo</li>
     * </ul>
     *
     * See {@see findRecursiveCallback()} for an example of a callback function.
     *
     * Filters are regular expressions and are therefore required to have
     * starting and ending delimiters. The Perl Compatible syntax is used as
     * regular expression language.
     *
     * @param string         $sourceDir
     * @param array(string)  $includeFilters
     * @param array(string)  $excludeFilters
     * @param callback       $callback
     * @param mixed          $callbackContext
     *
     * @throws ezcBaseFileNotFoundException if the $sourceDir directory is not
     *         a directory or does not exist.
     * @throws ezcBaseFilePermissionException if the $sourceDir directory could
     *         not be opened for reading.
     * @return array
     */
    static public function walkRecursive( $sourceDir, array $includeFilters = array(), array $excludeFilters = array(), $callback, &$callbackContext )
    {
        if ( !is_dir( $sourceDir ) )
        {
            throw new ezcBaseFileNotFoundException( $sourceDir, 'directory' );
        }
        $elements = array();
        $d = @dir( $sourceDir );
        if ( !$d )
        {
            throw new ezcBaseFilePermissionException( $sourceDir, ezcBaseFileException::READ );
        }

        while ( ( $entry = $d->read() ) !== false )
        {
            if ( $entry == '.' || $entry == '..' )
            {
                continue;
            }

            $fileInfo = @stat( $sourceDir . DIRECTORY_SEPARATOR . $entry );
            if ( !$fileInfo )
            {
                $fileInfo = array( 'size' => 0, 'mode' => 0 );
            }

            if ( $fileInfo['mode'] & 0x4000 )
            {
                // We need to ignore the Permission exceptions here as it can
                // be normal that a directory can not be accessed. We only need
                // the exception if the top directory could not be read.
                try
                {
                    call_user_func_array( $callback, array( $callbackContext, $sourceDir, $entry, $fileInfo ) );
                    $subList = self::walkRecursive( $sourceDir . DIRECTORY_SEPARATOR . $entry, $includeFilters, $excludeFilters, $callback, $callbackContext );
                    $elements = array_merge( $elements, $subList );
                }
                catch ( ezcBaseFilePermissionException $e )
                {
                }
            }
            else
            {
                // By default a file is included in the return list
                $ok = true;
                // Iterate over the $includeFilters and prohibit the file from
                // being returned when atleast one of them does not match
                foreach ( $includeFilters as $filter )
                {
                    if ( !preg_match( $filter, $sourceDir . DIRECTORY_SEPARATOR . $entry ) )
                    {
                        $ok = false;
                        break;
                    }
                }
                // Iterate over the $excludeFilters and prohibit the file from
                // being returns when atleast one of them matches
                foreach ( $excludeFilters as $filter )
                {
                    if ( preg_match( $filter, $sourceDir . DIRECTORY_SEPARATOR . $entry ) )
                    {
                        $ok = false;
                        break;
                    }
                }

                // If everything's allright, call the callback and add the
                // entry to the elements array
                if ( $ok )
                {
                    call_user_func( $callback, $callbackContext, $sourceDir, $entry, $fileInfo );
                    $elements[] = $sourceDir . DIRECTORY_SEPARATOR . $entry;
                }
            }
        }
        sort( $elements );
        return $elements;
    }

    /**
     * Finds files recursively on a file system
     *
     * With this method you can scan the file system for files. You can use
     * $includeFilters to include only specific files, and $excludeFilters to
     * exclude certain files from being returned. The function will always go
     * into subdirectories even if the entry would not have passed the filters.
     * It uses the {@see walkRecursive()} method to do the actually recursion.
     *
     * Filters are regular expressions and are therefore required to have
     * starting and ending delimiters. The Perl Compatible syntax is used as
     * regular expression language.
     *
     * If you pass an empty array to the $statistics argument, the function
     * will in details about the number of files found into the 'count' array
     * element, and the total filesize in the 'size' array element. Because this
     * argument is passed by reference, you *have* to pass a variable and you
     * can not pass a constant value such as "array()".
     *
     * @param string         $sourceDir
     * @param array(string)  $includeFilters
     * @param array(string)  $excludeFilters
     * @param array()        $statistics
     *
     * @throws ezcBaseFileNotFoundException if the $sourceDir directory is not
     *         a directory or does not exist.
     * @throws ezcBaseFilePermissionException if the $sourceDir directory could
     *         not be opened for reading.
     * @return array
     */
    static public function findRecursive( $sourceDir, array $includeFilters = array(), array $excludeFilters = array(), &$statistics = null )
    {
        // init statistics array
        if ( !is_array( $statistics ) || !array_key_exists( 'size', $statistics ) || !array_key_exists( 'count', $statistics ) )
        {
            $statistics['size']  = 0;
            $statistics['count'] = 0;
        }

        // create the context, and then start walking over the array
        $context = new ezcBaseFileFindContext;
        self::walkRecursive( $sourceDir, $includeFilters, $excludeFilters, array( 'ezcBaseFile', 'findRecursiveCallback' ), $context );

        // collect the statistics
        $statistics['size'] = $context->size;
        $statistics['count'] = $context->count;

        // return the found and pattern-matched files
        sort( $context->elements );
        return $context->elements;
    }


    /**
     * Removes files and directories recursively from a file system
     *
     * This method recursively removes the $directory and all its contents.
     * You should be <b>extremely</b> careful with this method as it has the
     * potential to erase everything that the current user has access to.
     *
     * @param string $directory
     */
    static public function removeRecursive( $directory )
    {
        $sourceDir = realpath( $directory );
        if ( !$sourceDir )
        {
            throw new ezcBaseFileNotFoundException( $directory, 'directory' );
        }
        $d = @dir( $sourceDir );
        if ( !$d )
        {
            throw new ezcBaseFilePermissionException( $directory, ezcBaseFileException::READ );
        }
        // check if we can remove the dir
        $parentDir = realpath( $directory . DIRECTORY_SEPARATOR . '..' );
        if ( !is_writable( $parentDir ) )
        {
            throw new ezcBaseFilePermissionException( $parentDir, ezcBaseFileException::WRITE );
        }
        // loop over contents
        while ( ( $entry = $d->read() ) !== false )
        {
            if ( $entry == '.' || $entry == '..' )
            {
                continue;
            }

            if ( is_dir( $sourceDir . DIRECTORY_SEPARATOR . $entry ) )
            {
                self::removeRecursive( $sourceDir . DIRECTORY_SEPARATOR . $entry );
            }
            else
            {
                if ( @unlink( $sourceDir . DIRECTORY_SEPARATOR . $entry ) === false )
                {
                    throw new ezcBaseFilePermissionException( $directory . DIRECTORY_SEPARATOR . $entry, ezcBaseFileException::REMOVE );
                }
            }
        }
        $d->close();
        rmdir( $sourceDir );
    }

    /**
    * Recursively copy a file or directory.
    *
    * Recursively copy a file or directory in $source to the given
    * destination. If a depth is given, the operation will stop, if the given
    * recursion depth is reached. A depth of -1 means no limit, while a depth
    * of 0 means, that only the current file or directory will be copied,
    * without any recursion.
    *
    * You may optionally define modes used to create files and directories.
    *
    * @throws ezcBaseFileNotFoundException
    *      If the $sourceDir directory is not a directory or does not exist.
    * @throws ezcBaseFilePermissionException
    *      If the $sourceDir directory could not be opened for reading, or the
    *      destination is not writeable.
    *
    * @param string $source
    * @param string $destination
    * @param int $depth
    * @param int $dirMode
    * @param int $fileMode
    * @return void
    */
    static public function copyRecursive( $source, $destination, $depth = -1, $dirMode = 0775, $fileMode = 0664 )
    {
        // Check if source file exists at all.
        if ( !is_file( $source ) && !is_dir( $source ) )
        {
            throw new ezcBaseFileNotFoundException( $source );
        }

        // Destination file should NOT exist
        if ( is_file( $destination ) || is_dir( $destination ) )
        {
            throw new ezcBaseFilePermissionException( $destination, ezcBaseFileException::WRITE );
        }

        // Skip non readable files in source directory
        if ( !is_readable( $source ) )
        {
            return;
        }

        // Copy
        if ( is_dir( $source ) )
        {
            mkdir( $destination );
            // To ignore umask, umask() should not be changed with
            // multithreaded servers...
            chmod( $destination, $dirMode );
        }
        elseif ( is_file( $source ) )
        {
            copy( $source, $destination );
            chmod( $destination, $fileMode );
        }

        if ( ( $depth === 0 ) ||
            ( !is_dir( $source ) ) )
        {
            // Do not recurse (any more)
            return;
        }

        // Recurse
        $dh = opendir( $source );
        while ( ( $file = readdir( $dh ) ) !== false )
        {
            if ( ( $file === '.' ) ||
                ( $file === '..' ) )
            {
                continue;
            }

            self::copyRecursive(
                $source . '/' . $file,
                $destination . '/' . $file,
                $depth - 1, $dirMode, $fileMode
            );
        }
    }

    /**
     * Calculates the relative path of the file/directory '$path' to a given
     * $base path.
     *
     * $path and $base should be fully absolute paths. This function returns the
     * answer of "How do I go from $base to $path". If the $path and $base are
     * the same path, the function returns '.'. This method does not touch the
     * filesystem.
     *
     * @param string $path
     * @param string $base
     * @return string
     */
    static public function calculateRelativePath( $path, $base )
    {
        // Sanitize the paths to use the correct directory separator for the platform
        $path = strtr( $path, '\\/', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR );
        $base = strtr( $base, '\\/', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR );

        $base = explode( DIRECTORY_SEPARATOR, $base );
        $path = explode( DIRECTORY_SEPARATOR, $path );

        // If the paths are the same we return
        if ( $base === $path )
        {
            return '.';
        }

        $result = '';

        $pathPart = array_shift( $path );
        $basePart = array_shift( $base );
        while ( $pathPart == $basePart )
        {
            $pathPart = array_shift( $path );
            $basePart = array_shift( $base );
        }

        if ( $pathPart != null )
        {
            array_unshift( $path, $pathPart );
        }
        if ( $basePart != null )
        {
            array_unshift( $base, $basePart );
        }

        $result = str_repeat( '..' . DIRECTORY_SEPARATOR, count( $base ) );
        // prevent a trailing DIRECTORY_SEPARATOR in case there is only a ..
        if ( count( $path ) == 0 )
        {
            $result = substr( $result, 0, -strlen( DIRECTORY_SEPARATOR ) );
        }
        $result .= join( DIRECTORY_SEPARATOR, $path );

        return $result;
    }

    /**
     * Returns whether the passed $path is an absolute path, giving the current $os.
     *
     * With the $os parameter you can tell this function to use the semantics
     * for a different operating system to determine whether a path is
     * absolute. The $os argument defaults to the OS that the script is running
     * on.
     *
     * @param string $path
     * @param string $os
     * @return bool
     */
    public static function isAbsolutePath( $path, $os = null )
    {
        if ( $os === null )
        {
            $os = ezcBaseFeatures::os();
        }

        // Stream wrapper like phar can also be considered absolute paths
        if ( preg_match( '(^[a-z]{3,}://)S', $path ) )
        {
            return true;
        }

        switch ( $os )
        {
            case 'Windows':
                // Sanitize the paths to use the correct directory separator for the platform
                $path = strtr( $path, '\\/', '\\\\' );

                // Absolute paths with drive letter: X:\
                if ( preg_match( '@^[A-Z]:\\\\@i', $path ) )
                {
                    return true;
                }

                // Absolute paths with network paths: \\server\share\
                if ( preg_match( '@^\\\\\\\\[A-Z]+\\\\[^\\\\]@i', $path ) )
                {
                    return true;
                }
                break;
            case 'Mac':
            case 'Linux':
            case 'FreeBSD':
            default:
                // Sanitize the paths to use the correct directory separator for the platform
                $path = strtr( $path, '\\/', '//' );

                if ( $path[0] == '/' )
                {
                    return true;
                }
        }
        return false;
    }
}
?>
