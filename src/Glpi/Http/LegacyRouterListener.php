<?php

namespace Glpi\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class LegacyRouterListener implements EventSubscriberInterface
{
    use LegacyRouterTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 260],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $response = $this->runLegacyRouter($request);

        if ($response) {
            $event->setResponse($response);
        }
    }

    public function runLegacyRouter(Request $request): ?Response
    {
        /**
         * GLPI web router.
         *
         * This router is used to be able to expose only the `/public` directory on the webserver.
         */

        $glpi_root = \dirname(__DIR__, 3);

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

        // Enforce legacy index file for root URL.
        // This prevents Symfony from being called.
        if ($path === '/') {
            $path = '/index.php';
        }

        $response = $this->handleRedirects($path, $uri_prefix);
        if ($response) {
            return $response;
        }

        $target_file = $glpi_root . $path;

        if (
            !$this->isTargetAPhpScript($path)
            || !$this->isPathAllowed($path)
            || !is_file($target_file)
        ) {
            // Let the previous router do the trick, it's fine.
            return null;
        }

        // Ensure `getcwd()` and inclusion path is based on requested file FS location.
        chdir(dirname($target_file));

        // (legacy) Redefine some $_SERVER variables to have same values whenever scripts are called directly
        // or through current router.
        $target_path     = $uri_prefix . $path;
        $target_pathinfo = $this->getTargetPathInfo($glpi_root, $path);
        $_SERVER['PATH_INFO']       = $target_pathinfo;
        $_SERVER['PHP_SELF']        = $target_path;
        $_SERVER['SCRIPT_FILENAME'] = $target_file;
        $_SERVER['SCRIPT_NAME']     = $target_path;

        // New server overrides:
        $request->server->set('PATH_INFO', $target_pathinfo);
        $request->server->set('PHP_SELF', $target_path);
        $request->server->set('SCRIPT_FILENAME', $target_file);
        $request->server->set('SCRIPT_NAME', $target_path);

        \Toolbox::deprecated('Old proxy router is deprecated: you should instead create proper controllers with Route attributes.');

        $baseContent = '';
        ob_start(static function (string $content) use (&$baseContent) {
            $baseContent .= $content;
        });

        // Execute target script.
        $this->requireFile($target_file, $request);

        $requestedFileContent = ob_get_flush();

        // Both have been set by legacy "front" or "ajax" files, usually.
        $headers = $this->buildHeadersList(headers_list());
        $httpCode = http_response_code();

        return new Response($baseContent . $requestedFileContent, $httpCode, $headers);
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

    /**
     * Check whether target is a PHP script.
     *
     * @return bool
     */
    public function isTargetAPhpScript(string $path): bool
    {
        // Check extension on path directly to be able to recognize that target is supposed to be a PHP
        // script even if it not exists. This is usefull to send most appropriate response code (i.e. 403 VS 404).
        if (preg_match('/^php\d*$/', pathinfo($path, PATHINFO_EXTENSION)) === 1) {
            return true;
        }

        return false;
    }

    private function getTargetPathInfo(string $glpi_root, string $uri): ?string
    {
        $uri = preg_replace('/\/{2,}/', '/', $uri); // remove duplicates `/`

        $pathinfo = null;

        // Parse URI to find requested script and PathInfo
        $slash_pos = 0;
        while ($slash_pos !== false && ($dot_pos = strpos($uri, '.', $slash_pos)) !== false) {
            $slash_pos = strpos($uri, '/', $dot_pos);
            $filepath = substr($uri, 0, $slash_pos !== false ? $slash_pos : strlen($uri));
            if (is_file($glpi_root . $filepath)) {
                $path = $filepath;

                $pathinfo = substr($uri, strlen($filepath));
                if ($pathinfo !== '') {
                    // On any regular PHP script that is directly served by Apache, `$_SERVER['PATH_INFO']`
                    // contains decoded URL.
                    // We have to reproduce this decoding operation to prevent issues with endoded chars.
                    $pathinfo = urldecode($pathinfo);
                } else {
                    $pathinfo = null;
                }
                break;
            }
        }

        return $pathinfo;
    }


    /**
     *  Handle well-known URIs as defined in RFC 5785.
     *  https://www.iana.org/assignments/well-known-uris/well-known-uris.xhtml
     */
    public function handleRedirects(string $path, string $uri_prefix): ?Response
    {
        // Handle well-known URIs
        if (preg_match('/^\/\.well-known\//', $path) !== 1) {
            return null;
        }

        // Get the requested URI (the part after .well-known/)
        $requested_uri = explode('/', $path);
        $requested_uri = strtolower(end($requested_uri));

        // Some password managers can use this URI to help with changing passwords
        // Redirect to the change password page
        if ($requested_uri === 'change-password') {
            return new RedirectResponse($uri_prefix . '/front/updatepassword.php', 307);
        }
    }
}
