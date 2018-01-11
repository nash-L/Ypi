<?php
namespace Ypi\Core;

use Ypi\Core\RouterNode;
use Ypi\Rest;

use Exception;
use Closure;

use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;

class Router {
    private $_dispatcher, $_md;
    public function __construct($base_namespace, $mode, $map_file, $doc_dir)
    {
        if ($mode === 'online' && is_file($map_file)) {
            $dispatcher = unserialize(file_get_contents($map_file));
            $this->_dispatcher = $dispatcher['dispatcher'];
            $this->_md = $dispatcher['doc_md'];
        } else {
            $controllers = self::_getControllers($base_namespace);
            $nodes = RouterNode::getControllerNodes($controllers);
            $mds = array();
            if (is_dir($doc_dir)) {
                $mdfiles = scandir($doc_dir);
                foreach ($mdfiles as $k => $v) {
                    if ($v[0] !== '.' && is_file($doc_dir . DS . $v)) {
                        $v_name = $v;
                        $v_encode = mb_detect_encoding($v, array('ASCII','GB2312','GBK','UTF-8'), true);
                        if ($v_encode !== 'UTF-8') {
                            $v_name = iconv($v_encode, 'UTF-8', $v_name);
                        }
                        $mds[$v_name] = file_get_contents($doc_dir . DS . $v);
                    }
                }
            }
            $this->_md = $mds;
            $this->_dispatcher = simpleDispatcher(function ($r) use (&$nodes, &$mds) {
                foreach ($nodes as $node) {
                    $r->addRoute($node->getMethod(), $node->getPath(), $node);
                }
                $r->addRoute('GET', '/-document', array(array(Router::class, 'showDocument'), array('nodes' => $nodes, 'mds' => $mds)));
            });
            if ($mode === 'online') {
                file_put_contents($map_file, serialize(array('dispatcher' => $this->_dispatcher, 'doc_md' => $this->_md)));
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

    public static function showDocument($nodes, $mds)
    {
        if (App::config()->get('DOCUMENT')) {
            Rest::changeMode('document');
            header('Content-Type: text/html;charset=utf-8');
            $cover_info = App::config()->get('DOCUMENT_COVER');
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
