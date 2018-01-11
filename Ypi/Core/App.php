<?php
namespace Ypi\Core;

use Closure;
use Exception;
use ReflectionFunction;

use Auryn\Injector;

class App {
    private static $_servers, $_di, $_funcs;

    /**
     * 初始化
     */
    private static function _init()
    {
        empty(self::$_di) && self::$_di = new Injector;
        empty(self::$_servers) && self::$_servers = array();
        empty(self::$_funcs) && self::$_funcs = array();
    }

    /**
     * 设置与获取服务
     */
    public static function service($server_name, $call)
    {
        self::_init();
        self::$_servers[$server_name] = $call;
    }

    /**
     * 设置与获取服务方法
     */
    public static function server($server_name, $call)
    {
        self::_init();
        self::$_funcs[$server_name] = $call;
    }

    /**
     * 执行方法
     */
    public static function execute($callabled, array $params = array())
    {
        self::_init();
        $params = self::_makeParams($params);
        return self::$_di->execute($callabled, $params);
    }

    /**
     * 制作对象
     */
    public static function make($className, array $params = array())
    {
        self::_init();
        if (count($params)) {
            $params = self::_makeParams($params);
            self::$_di->define($className, $params);
        }
        return self::$_di->make($className);
    }

    /**
     * 制作参数
     */
    private static function _makeParams(array $params)
    {
        foreach ($params as $k => $v) {
            $params[":{$k}"] = $v;
            unset($params[$k]);
        }
        return $params;
    }

    /**
     * 静态调用
     */
    public static function __callStatic($server_name, array $args)
    {
        self::_init();
        if (isset(self::$_servers[$server_name])) {
            if (self::$_servers[$server_name] instanceof Closure) {
                self::$_servers[$server_name] = self::execute(self::$_servers[$server_name]);
            }
            return self::$_servers[$server_name];
        } elseif (isset(self::$_funcs[$server_name])) {
            if (is_callable(self::$_funcs[$server_name])) {
                return call_user_func_array(self::$_funcs[$server_name], $args);
            }
        }
        throw new Exception('找不到服务', 500);
    }
}
