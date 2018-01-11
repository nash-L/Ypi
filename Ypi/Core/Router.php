<?php
namespace Ypi\Core;

use Ypi\Core\RouterNode;
use Ypi\Rest;

use Exception;
use Closure;

use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;

class Router {
    private $_dispatcher;
    public function __construct($base_namespace, $mode, $map_file)
    {
        $controllers = self::_getControllers($base_namespace);
        $nodes = RouterNode::getControllerNodes($controllers);
        if ($mode === 'online' && is_file($map_file)) {
            $this->_dispatcher = unserialize(file_get_contents($map_file));
        } else {
            $this->_dispatcher = simpleDispatcher(function ($r) use (&$nodes) {
                foreach ($nodes as $node) {
                    $r->addRoute($node->getMethod(), $node->getPath(), $node);
                }
                $r->addRoute('GET', '/-document', array(array(Router::class, 'showDocument'), array('nodes' => $nodes)));
            });
            if ($mode === 'online') {
                file_put_contents($map_file, serialize($this->_dispatcher));
            }
        }
    }

    public function dispatch($httpMethod, $url)
    {
        $uri_arr = explode('?', $url);
        $uri = rawurldecode($uri_arr[0]);
        $routeInfo = $this->_dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new Exception('无法找到资源', 404);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new Exception('请求不被允许', 405);
                break;
            case Dispatcher::FOUND:
                if ($routeInfo[1] instanceof RouterNode) {
                    return $routeInfo[1]->callController($routeInfo[2]);
                } elseif (is_callable($routeInfo[1])) {
                    App::execute($routeInfo[1], $routeInfo[2]);
                } elseif (is_array($routeInfo[1]) && isset($routeInfo[1][0]) && is_callable($routeInfo[1][0])) {
                    $args = $routeInfo[2];
                    if (isset($routeInfo[1][1])) {
                        $args = array_merge($args, $routeInfo[1][1]);
                    }
                    App::execute($routeInfo[1][0], $args);
                }
        }
    }

    public static function showDocument($nodes)
    {
        if (App::config()->get('DOCUMENT')) {
            Rest::changeMode('document');
            header('Content-Type: text/html;charset=utf-8');
            require ROOT . DS . 'Ypi' . DS . 'document-html' . DS . 'tpl.phtml';
        } else {
            throw new Exception('无法找到资源', 404);
        }
    }

    // 获取指定控制器目录下的全部控制器类名。
    private static function _getControllers($base_name, array &$controllers = [])
    {
        $dir = realpath(ROOT . DS . strtr($base_name, '\\', DS));
        if (!$dir) {
            throw new Exception('服务器错误，指定服务地址错误', 500);
        }
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file[0] == '.') {
                continue;
            }
            if (($path_name = $base_name . '\\' . $file) && is_dir($new_path = $dir . DS . $file)) {
                self::_getControllers($path_name, $controllers);
            } elseif (is_file($new_path) && ($class_name = strtr($path_name, ['.php' => ''])) && class_exists($class_name)) {
                $controllers[] = $class_name;
            }
        }
        return $controllers;
    }
}
