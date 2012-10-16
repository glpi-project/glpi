#!/usr/bin/env php
<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

use Zend\Console;
use Zend\File\ClassFileLocator;
use Zend\Loader\StandardAutoloader;

/**
 * Generate class maps for use with autoloading.
 *
 * Usage:
 * --help|-h                    Get usage message
 * --library|-l [ <string> ]    Library to parse; if none provided, assumes
 *                              current directory
 * --output|-o [ <string> ]     Where to write autoload file; if not provided,
 *                              assumes "autoload_classmap.php" in library directory
 * --append|-a                  Append to autoload file if it exists
 * --overwrite|-w               Whether or not to overwrite existing autoload
 *                              file
 */

$zfLibraryPath = getenv('LIB_PATH') ? getenv('LIB_PATH') : __DIR__ . '/../library';
if (is_dir($zfLibraryPath)) {
    // Try to load StandardAutoloader from library
    if (false === include($zfLibraryPath . '/Zend/Loader/StandardAutoloader.php')) {
        echo 'Unable to locate autoloader via library; aborting' . PHP_EOL;
        exit(2);
    }
} else {
    // Try to load StandardAutoloader from include_path
    if (false === include('Zend/Loader/StandardAutoloader.php')) {
        echo 'Unable to locate autoloader via include_path; aborting' . PHP_EOL;
        exit(2);
    }
}

$libraryPath = getcwd();

// Setup autoloading
$loader = new StandardAutoloader(array('autoregister_zf' => true));
$loader->register();

$rules = array(
    'help|h'      => 'Get usage message',
    'library|l-s' => 'Library to parse; if none provided, assumes current directory',
    'output|o-s'  => 'Where to write autoload file; if not provided, assumes "autoload_classmap.php" in library directory',
    'append|a'    => 'Append to autoload file if it exists',
    'overwrite|w' => 'Whether or not to overwrite existing autoload file',
);

try {
    $opts = new Console\Getopt($rules);
    $opts->parse();
} catch (Console\Exception\RuntimeException $e) {
    echo $e->getUsageMessage();
    exit(2);
}

if ($opts->getOption('h')) {
    echo $opts->getUsageMessage();
    exit(0);
}

$relativePathForClassmap = '';
if (isset($opts->l)) {
    if (!is_dir($opts->l)) {
        echo 'Invalid library directory provided' . PHP_EOL
            . PHP_EOL;
        echo $opts->getUsageMessage();
        exit(2);
    }
    $libraryPath = $opts->l;
}
$libraryPath = str_replace(DIRECTORY_SEPARATOR, '/', realpath($libraryPath));

$usingStdout = false;
$appending = $opts->getOption('a');
$output = $libraryPath . '/autoload_classmap.php';
if (isset($opts->o)) {
    $output = $opts->o;
    if ('-' == $output) {
        $output = STDOUT;
        $usingStdout = true;
    } elseif (is_dir($output)) {
        echo 'Invalid output file provided' . PHP_EOL
            . PHP_EOL;
        echo $opts->getUsageMessage();
        exit(2);
    } elseif (!is_writeable(dirname($output))) {
        echo "Cannot write to '$output'; aborting." . PHP_EOL
            . PHP_EOL
            . $opts->getUsageMessage();
        exit(2);
    } elseif (file_exists($output) && !$opts->getOption('w') && !$appending) {
        echo "Autoload file already exists at '$output'," . PHP_EOL
            . "but 'overwrite' or 'appending' flag was not specified; aborting." . PHP_EOL
            . PHP_EOL
            . $opts->getUsageMessage();
        exit(2);
    } else {
        // We need to add the $libraryPath into the relative path that is created in the classmap file.
        $classmapPath = str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname($output)));

        // Simple case: $libraryPathCompare is in $classmapPathCompare
        if (strpos($libraryPath, $classmapPath) === 0) {
            $relativePathForClassmap = substr($libraryPath, strlen($classmapPath) + 1) . '/';
        } else {
            $libraryPathParts  = explode('/', $libraryPath);
            $classmapPathParts = explode('/', $classmapPath);

            // Find the common part
            $count = count($classmapPathParts);
            for ($i = 0; $i < $count; $i++) {
                if (!isset($libraryPathParts[$i]) || $libraryPathParts[$i] != $classmapPathParts[$i]) {
                    // Common part end
                    break;
                }
            }

            // Add parent dirs for the subdirs of classmap
            $relativePathForClassmap = str_repeat('../', $count - $i);

            // Add library subdirs
            $count = count($libraryPathParts);
            for (; $i < $count; $i++) {
                $relativePathForClassmap .= $libraryPathParts[$i] . '/';
            }
        }
    }
}

if (!$usingStdout) {
    if ($appending) {
        echo "Appending to class file map '$output' for library in '$libraryPath'..." . PHP_EOL;
    } else {
        echo "Creating class file map for library in '$libraryPath'..." . PHP_EOL;
    }
}

// Get the ClassFileLocator, and pass it the library path
$l = new ClassFileLocator($libraryPath);

// Iterate over each element in the path, and create a map of
// classname => filename, where the filename is relative to the library path
$map = new stdClass;
foreach ($l as $file) {
    $filename  = str_replace($libraryPath . '/', '', str_replace(DIRECTORY_SEPARATOR, '/', $file->getPath()) . '/' . $file->getFilename());

    // Add in relative path to library
    $filename  = $relativePathForClassmap . $filename;

    foreach ($file->getClasses() as $class) {
        $map->{$class} = $filename;
    }
}

if ($appending) {
    $content = var_export((array) $map, true) . ';';

    // Prefix with __DIR__; modify the generated content
    $content = preg_replace("#(=> ')#", "=> __DIR__ . '/", $content);

    // Fix \' strings from injected DIRECTORY_SEPARATOR usage in iterator_apply op
    $content = str_replace("\\'", "'", $content);

    // Convert to an array and remove the first "array("
    $content = explode(PHP_EOL, $content);
    array_shift($content);

    // Load existing class map file and remove the closing "bracket ");" from it
    $existing = file($output, FILE_IGNORE_NEW_LINES);
    array_pop($existing);

    // Merge
    $content = implode(PHP_EOL, array_merge($existing, $content));
} else {
    // Create a file with the class/file map.
    // Stupid syntax highlighters make separating < from PHP declaration necessary
    $content = '<' . "?php\n"
             . "// Generated by ZF2's ./bin/classmap_generator.php\n"
             . 'return ' . var_export((array) $map, true) . ';';

    // Prefix with __DIR__; modify the generated content
    $content = preg_replace("#(=> ')#", "=> __DIR__ . '/", $content);

    // Fix \' strings from injected DIRECTORY_SEPARATOR usage in iterator_apply op
    $content = str_replace("\\'", "'", $content);
}

// Remove unnecessary double-backslashes
$content = str_replace('\\\\', '\\', $content);

// Exchange "array (" width "array("
$content = str_replace('array (', 'array(', $content);

// Align "=>" operators to match coding standard
preg_match_all('(\n\s+([^=]+)=>)', $content, $matches, PREG_SET_ORDER);
$maxWidth = 0;

foreach ($matches as $match) {
    $maxWidth = max($maxWidth, strlen($match[1]));
}

$content = preg_replace('(\n\s+([^=]+)=>)e', "'\n    \\1' . str_repeat(' ', " . $maxWidth . " - strlen('\\1')) . '=>'", $content);

// Write the contents to disk
file_put_contents($output, $content);

if (!$usingStdout) {
    echo "Wrote classmap file to '" . realpath($output) . "'" . PHP_EOL;
}
