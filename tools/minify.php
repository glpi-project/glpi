<?php

/**
 * Require install tools:
 * npm install uglify-js -g
 * npm install -g sprity-cli
 * npm install -g uglifycss
 */

define("GLPI_ROOT", "../");
include('../config/define.php');
include('../inc/html.class.php');

// Get languages
$langjs = array();
foreach ($CFG_GLPI['languages'] as $list) {
    $langjs[$list[2]] = $list[2];
}

// Generate js minified and unified

$js_lib = Html::core_js_lib();

foreach ($langjs as $lang) {
    $args = "-o minify/main.".$lang.".min.js ";
    foreach ($js_lib as $js_file) {
        if (strstr($js_file, '[LANG]')) {
            $js_file = str_replace("[LANG]", $lang, $js_file);
            if (!file_exists("../".$js_file)) {
                $js_file = "";
            }
        }
        if ($js_file != "") {
            $args .= $js_file." ";
        }
    }
    print_r($args);
    exec("cd .. && uglifyjs ".$args);
    echo "\n===========================================\n";
}

// Generate img css-sprite

exec("cd ../minify/ && sprity create ./ ../pics/* --style main.css "
        . "--name main --prefix glpimg --margin 0 --css-path ../minify");


// Generate css minified and unified
$args = "";
$css_files = array(
    'css/styles.css',
    'lib/jquery/css/smoothness/jquery-ui-1.10.4.custom.css',
    'css/jstree/style.css',
    'lib/jqueryplugins/rateit/rateit.css',
    'lib/jqueryplugins/select2/select2.css',
    'lib/jqueryplugins/qtip2/jquery.qtip.css',
    'lib/jqueryplugins/jcrop/jquery.Jcrop.min.css',
    'lib/jqueryplugins/spectrum-colorpicker/spectrum.css',
    'lib/jqueryplugins/jquery-gantt/css/style.css',
    'css/jquery-glpi.css',
    'minify/main.css'
);

foreach ($css_files as $css_file) {
    $args .= "../".$css_file." ";
}
print_r($args);
exec("cd ../minify/ && uglifycss ".$args."> glpi.min.css");
