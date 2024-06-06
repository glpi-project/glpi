<?php

namespace Glpi\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class LegacyAssetsListener implements EventSubscriberInterface
{
    use LegacyRouterTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 250],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $response = $this->serveLegacyAssets($request);

        if ($response) {
            $event->setResponse($response);
        }
    }

    private function serveLegacyAssets(Request $request): ?Response
    {
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

        $path = preg_replace(
            '/^' . preg_quote($uri_prefix, '/') . '/',
            '',
            parse_url($request->server->get('REQUEST_URI') ?? '/', PHP_URL_PATH)
        );

        $uri = preg_replace('/\/{2,}/', '/', $path); // remove duplicates `/`

        $path     = null;
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

        if ($path === null) {
            // Fallback to requested URI
            $path = $uri;

            // Clean trailing `/`.
            $path = rtrim($path, '/');

            // If URI matches a directory path, consider `index.php` is the requested script.
            if (is_dir($glpi_root . $path) && is_file($glpi_root . $path . '/index.php')) {
                $path .= '/index.php';
            }
        }



        if ($this->isPathAllowed($path) === false) {
            return null;
        }

        $target_file = $glpi_root . $path;

        if (!is_file($target_file)) {
            return null;
        }

        if ($this->isTargetAPhpScript($path)) {
            return null;
        }

        // Serve static files if web server is not configured to do it directly.
        $response = new BinaryFileResponse($target_file);
        $response->setMaxAge(2_592_000);
        $response->mustRevalidate();

        // Automatically modifies the response to 304 if HTTP Cache headers match
        $response->isNotModified($request);

        return $response;
    }
}
