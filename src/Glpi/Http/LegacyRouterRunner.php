<?php

namespace Glpi\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class LegacyRouterRunner
{
    public function run(Request $request): ?Response
    {
        /**
         * GLPI web router.
         *
         * This router is used to be able to expose only the `/public` directory on the webserver.
         */

        $glpi_root = realpath(dirname(__DIR__, 3));

        if (
            $request->server->get('SCRIPT_NAME') === '/public/index.php'
            && preg_match('/^\/public/', $request->server->get('REQUEST_URI')) !== 1
        ) {
            // When requested URI does not start with '/public' but `$request->server->get('SCRIPT_NAME')` is '/public/index.php',
            // it means that document root is the GLPI root directory, but a rewrite rule redirects the request to the PHP router.
            // This case happen when redirection to PHP router is made by an `.htaccess` file placed in the GLPI root directory,
            // and has to be handled to support shared hosting where it is not possible to change the web server root directory.
            $uri_prefix = '';
        } else {
            // `$request->server->get('SCRIPT_NAME')` corresponds to the script path relative to server document root.
            // -> if server document root is `/public`, then `$request->server->get('SCRIPT_NAME')` will be equal to `/index.php`
            // -> if script is located into a `/glpi-alias` alias directory, then `$request->server->get('SCRIPT_NAME')` will be equal to `/glpi-alias/index.php`
            $uri_prefix = rtrim(str_replace('\\', '/', dirname($request->server->get('SCRIPT_NAME'))), '/');
        }

        // Get URI path relative to GLPI (i.e. without alias directory prefix).
        $path = preg_replace(
            '/^' . preg_quote($uri_prefix, '/') . '/',
            '',
            parse_url($request->server->get('REQUEST_URI') ?? '/', PHP_URL_PATH)
        );

        $proxy = new \Glpi\Http\ProxyRouter($glpi_root, $path);
        $proxy->handleRedirects($uri_prefix);

        if (
            !$proxy->isTargetAPhpScript()
            || !$proxy->isPathAllowed()
            || ($target_file = $proxy->getTargetFile()) === null
        ) {
            // Let the previous router do the trick, it's fine.
            return null;
        }

        // Ensure `getcwd()` and inclusion path is based on requested file FS location.
        chdir(dirname($target_file));

        // (legacy) Redefine some $_SERVER variables to have same values whenever scripts are called directly
        // or through current router.
        $target_path     = $uri_prefix . $proxy->getTargetPath();
        $target_pathinfo = $proxy->getTargetPathInfo();
        $_SERVER['PATH_INFO']       = $target_pathinfo;
        $_SERVER['PHP_SELF']        = $target_path;
        $_SERVER['SCRIPT_FILENAME'] = $target_file;
        $_SERVER['SCRIPT_NAME']     = $target_path;

        // New server overrides:
        $request->server->set('PATH_INFO', $target_pathinfo);
        $request->server->set('PHP_SELF', $target_path);
        $request->server->set('SCRIPT_FILENAME', $target_file);
        $request->server->set('SCRIPT_NAME', $target_path);

        // Execute target script.
        trigger_deprecation('glpi/glpi', '11.0.0', 'Old proxy router is deprecated: you should instead create proper controllers with Route attributes.');

        $baseContent = '';
        ob_start(static function (string $content) use (&$baseContent) {
            $baseContent .= $content;
        });
        $this->requireFile($target_file, $request);
        $requestedFileContent = ob_get_flush();

        // Both have been set by legacy "front" or "ajax" files, usually.
        $headers = $this->buildHeadersList(headers_list());
        $httpCode = http_response_code();

        return new Response($baseContent.$requestedFileContent, $httpCode, $headers);
    }

    /**
     * The goal of this wrapper is to make sure to remove *all* context variables,
     * except the $target_file (which can't be removed) and the HTTP request (for smoother upgrades)
     */
    private function requireFile(string $target_file, Request $request): void
    {
        require($target_file);
    }

    /**
     * @param array<string> $headersAsList
     *
     * @return array<string, string>
     */
    private function buildHeadersList(array $headersAsList): array
    {
        $headers = [];

        foreach ($headersAsList as $value) {
            [$key, $value] = array_map('trim', explode(':', $value, 2));
            $headers[$key] = $value;
        }

        return $headers;
    }
}
