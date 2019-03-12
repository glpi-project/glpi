<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Twig-View
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */
namespace Glpi\Application\View\TwigExtension;

use Twig_SimpleFunction;
use Glpi\Application\Router;

class RouterExtension extends \Twig\Extension\AbstractExtension
{
    /**
     * @var Router
     */
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function getName()
    {
        return 'router';
    }

    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('path_for', array($this, 'pathFor')),
            new Twig_SimpleFunction('base_path', array($this, 'basePath')),
        ];
    }

    public function pathFor($name, $data = [], $queryParams = [])
    {
        return $this->router->pathFor($name, $data, $queryParams);
    }

    public function basePath()
    {
        return $this->router->getBasePath();
    }
}
