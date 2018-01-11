<?php
namespace Ypi\Core;

use ReflectionClass;
use ReflectionMethod;
use Exception;

use Ypi\Core\App;

class RouterNode {
    private $_methodDoc, $_method, $_path;
    public $method, $docs, $path, $title;

    private function __construct($title, $method, $path, $methodDoc)
    {
        krsort($methodDoc);
        $this->_methodDoc = $methodDoc;
        $this->_method = $method;
        $this->_path = $path;
        $this->docs = $methodDoc;
        $this->method = $method;
        $this->path = $path;
        $this->title = $title;
    }

    public function getMethod()
    {
        return $this->_method;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function callController($def_param)
    {
        $version = App::config()->getHeader('VERSION');
        $callback = [];
        foreach ($this->_methodDoc as $ver => $callback) {
            if (version_compare($ver, $version, '<=')) {
                break;
            }
        }
        $data = array();
        if ($this->_method === 'GET') {
            $data = $_GET;
        } elseif ($input = file_get_contents('php://input')) {
            $data = json_decode($input, true);
        } else {
            $data = $_POST;
        }
        $initparam = $this->getParam($callback['init_param'], $data, $def_param);
        $param = $this->getParam($callback['param'], $data, $def_param);
        $ctl = App::make($callback['class_name'], $initparam);
        $return = array('data' => App::execute(array($ctl, $callback['method_name']), $param));
        $return_param = array('data' => $callback['data']);
        $re = $this->getParam($return_param, $return);
        return $re['data'];
    }

    private function getParam(array $param_infos, array &$data, array $return = array())
    {
        foreach ($param_infos as $param_info) {
            if (isset($data[$param_info['name']])) {
                if ($param_info['type'] === 'array' && is_array($data[$param_info['name']])) {
                    $return[$param_info['name']] = array();
                    foreach ($data[$param_info['name']] as $val) {
                        $return[$param_info['name']][] = $this->formatData($param_info['sub_param'], $val, $param_info['name']);
                    }
                } elseif ($param_info['type'] === 'array[object]' && is_array($data[$param_info['name']])) {
                    $return[$param_info['name']] = array();
                    foreach ($data[$param_info['name']] as $val) {
                        $return[$param_info['name']][] = $this->getParam($param_info['sub_param'], $val);
                    }
                } elseif ($param_info['type'] === 'object' && is_array($data[$param_info['name']])) {
                    $return[$param_info['name']] = $this->getParam($param_info['sub_param'], $data[$param_info['name']]);
                } elseif (!is_array($data[$param_info['name']])) {
                    $return[$param_info['name']] = $this->formatData($param_info['type'], $data[$param_info['name']], $param_info['name']);
                    if (in_array($param_info['type'], array('int', 'float', 'string')) && isset($param_info['sub_param'][0]) && !in_array($return[$param_info['name']], $param_info['sub_param'])) {
                        throw new Exception('参数错误，参数 ' . $param_info['name'] . ' 需提供 ' . implode(',', $param_info['sub_param']) . ' 之中的参数', 400);
                    }
                }
            } elseif ($param_info['require']) {
                throw new Exception('无法找到必传参数：' . $param_info['name'], 400);
            }
        }
        return $return;
    }

    private function formatData($type, $data, $name)
    {
        $value = false;
        switch ($type) {
            case 'array': $value = $data; break;
            case 'int': $value = filter_var($data, FILTER_VALIDATE_INT); break;
            case 'raw': $value = filter_var($data, FILTER_DEFAULT); break;
            case 'string': $value = filter_var($data, FILTER_SANITIZE_STRING); break;
            case 'float': $value = filter_var($data, FILTER_VALIDATE_FLOAT); break;
            case 'ip': $value = filter_var($data, FILTER_VALIDATE_IP); break;
            case 'mac': $value = filter_var($data, FILTER_VALIDATE_MAC); break;
            case 'url': $value = filter_var($data, FILTER_VALIDATE_URL); break;
            case 'email': $value = filter_var($data, FILTER_VALIDATE_EMAIL); break;
            case 'bool': $value = filter_var($data, FILTER_VALIDATE_BOOLEAN); break;
            case 'date': $value = filter_var($data, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^((\d{3}[1-9]|\d{2}[1-9]\d|\d[1-9]\d{2}|[1-9]\d{3})-((0?[13578]|1[02])-(0?[1-9]|[1-2]\d|3[01])|(0?[469]|11)-(0?[1-9]|[1-2]\d|30)|0?2-(0?[1-9]|1\d|2[0-8]))|(\d{2}([2468][048]|[13579][26]|0[48])|([2468][048]|[13579][26]|0[48])00)-0?2-29)$/']]); break;
            case 'time': $value = filter_var($data, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^(((AM|PM|am|pm) )?(0?[1-9]|1[012])|(0?0|1[3-9]|2[0-3])):([0-5]?\d)(:([0-5]?\d))?$/']]); break;
            case 'datetime': $value = filter_var($data, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^((\d{3}[1-9]|\d{2}[1-9]\d|\d[1-9]\d{2}|[1-9]\d{3})-((0?[13578]|1[02])-(0?[1-9]|[1-2]\d|3[01])|(0?[469]|11)-(0?[1-9]|[1-2]\d|30)|0?2-(0?[1-9]|1\d|2[0-8]))|(\d{2}([2468][048]|[13579][26]|0[48])|([2468][048]|[13579][26]|0[48])00)-0?2-29) (((AM|PM|am|pm) )?(0?[1-9]|1[012])|(0?0|1[3-9]|2[0-3])):([0-5]?\d)(:([0-5]?\d))?$/']]); break;
        }
        if ($value === false && $type !== 'bool') {
            throw new Exception("参数错误，参数 {$name} 不匹配", 400);
        }
        return $value;
    }

    public static function getControllerNodes($classNames, &$docs = array())
    {
        if ($className = array_shift($classNames)) {
            if (class_exists($className)) {
                self::_reflectController($className, $docs);
            }
            return self::getControllerNodes($classNames, $docs);
        }
        $return = array();
        foreach ($docs as $method => $val) {
            foreach ($val as $path => $nodeArr) {
                $max_version = $nodeArr['_MAX_VERSION_'];
                $title = $nodeArr['_TITLE_'];
                unset($nodeArr['_MAX_VERSION_'], $nodeArr['_TITLE_']);
                // var_dump($title);die;
                $return[] = new RouterNode($title, $method, $path, $nodeArr);
            }
        }
        return $return;
    }

    private static function _reflectController($className, &$docs)
    {
        $ref = new ReflectionClass($className);
        if ($doc = $ref->getDocComment()) {
            $doc = self::_getDoc($doc, 0);
            $doc['class_name'] = $className;
            if (($initMethod = $ref->getConstructor()) && $initDoc = $initMethod->getDocComment()) {
                $doc = self::_getDoc($initDoc, 0, $doc);
            }
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if (!$method->isConstructor()) {
                    self::_reflecMethod($method, $doc, $docs);
                }
            }
        }
    }

    private static function _reflecMethod(ReflectionMethod &$method, &$doc, &$docs)
    {
        if ($methodDoc = $method->getDocComment()) {
            $methodDoc = self::_getDoc($methodDoc, 1, $doc);
            $methodDoc['method_name'] = $method->getName();
            if (isset($methodDoc['url'])) {
                foreach ($methodDoc['url'] as $urls) {
                    foreach ($urls['methods'] as $http_method) {
                        empty($docs[$http_method]) && $docs[$http_method] = array();
                        empty($docs[$http_method][$urls['path']]) && $docs[$http_method][$urls['path']] = array();
                        if (empty($docs[$http_method][$urls['path']][$methodDoc['version']])) {
                            if (!isset($docs[$http_method][$urls['path']]['_MAX_VERSION_']) || version_compare($methodDoc['version'], $docs[$http_method][$urls['path']]['_MAX_VERSION_'], '>=')) {
                                $docs[$http_method][$urls['path']]['_MAX_VERSION_'] = $methodDoc['version'];
                                if (!isset($docs[$http_method][$urls['path']]['_TITLE_'])) {
                                    $docs[$http_method][$urls['path']]['_TITLE_'] = '';
                                }
                                isset($methodDoc['title']) && $docs[$http_method][$urls['path']]['_TITLE_'] = $methodDoc['title'];
                            }
                            $docs[$http_method][$urls['path']][$methodDoc['version']] = $methodDoc;
                        } else {
                            throw new Exception("定义了重复版本的路由服务 {$http_method} {$urls['path']} {$methodDoc['version']}", 500);
                        }
                    }
                }
            }
        }
    }

    private static function _getDoc($doc, $type, $doc_arr = null)
    {
        if (is_null($doc_arr)) {
            $doc_arr = array('param' => array(), 'init_param' => array(), 'group' => '', 'version' => App::config()->getHeaderDef('VERSION'));
        }
        $doc_str = preg_replace('/\r?\n[\t ]*\*\/[\t ]*|[\t ]*(\/\*\*[\t ]*\r?\n|\*[\t ]*)/', '', $doc);
        self::_getPublicDoc($doc_str, $doc_arr, $type);
        self::_getParamDoc($doc_str, $doc_arr, $type);
        self::_getUrlDoc($doc_str, $doc_arr, $type);
        self::_getReturnDoc($doc_str, $doc_arr, $type);
        return $doc_arr;
    }

    private static function _getPublicDoc($doc_str, &$doc_arr, $type)
    {
        if (preg_match_all('/@(date|author|phone|version|title)[\t ]+(\S+)[\t ]*($|\r?\n)/', $doc_str, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $matche) {
                $doc_arr[$matche[1]] = $matche[2];
            }
        }
    }

    private static function _getParamDoc($doc_str, &$doc_arr, $type)
    {
        if (preg_match_all('/@param[\t ]+(\S+)[\t ]+(float\??\<[^@]+\>|int\??\<[^@]+\>|string\??\<[^@]+\>|array\??\{[^@]+\}|array\??\[[^@]*\]|[a-z]+\??)[\t ]*(\S*)[\t ]*($|\r?\n)/', $doc_str, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $matche) {
                $key = $type === 0 ? 'init_param' : 'param';
                $doc_arr[$key][$matche[1]] = self::_getParamAttr($matche[1], $matche[2], $matche[3]);
            }
        }
    }

    private static function _getUrlDoc($doc_str, &$doc_arr, $type)
    {
        if ($type === 0 && preg_match('/@url[\t ]+(\S+)[\t ]*($|\r?\n)/', $doc_str, $matche)) {
            $doc_arr['group'] = $matche[1];
        } elseif ($type === 1 && preg_match_all('/@url[\t ]+([GET|POST|PUT|DELETE|PATCH|HEAD][\/(GET|POST|PUT|DELETE|PATCH|HEAD)]*)[\t ]+(\S+)[\t ]*($|\r?\n)/', $doc_str, $matches, PREG_SET_ORDER)) {
            $doc_arr['url'] = array();
            foreach ($matches as $matche) {
                $doc_arr['url'][] = array('methods' => explode('|', $matche[1]), 'path' => $doc_arr['group'] . $matche[2]);
            }
        }
    }

    private static function _getReturnDoc($doc_str, &$doc_arr, $type)
    {
        if ($type === 1 && preg_match('/@return[\t ]+(float\<[^@]+\>|int\<[^@]+\>|string\<[^@]+\>|array\{[^@]+\}|array\[[^@]*\]|[a-z]+)[\t ]*(\S*)($|\r?\n)/', $doc_str, $matche)) {
            $doc_arr['data'] = self::_getParamAttr('data', $matche[1], $matche[2]);
        }
    }

    private static function _getParamAttr($name, $type, $remark)
    {
        $type = strtr($type, array("\r\n" => '', "\n" => '', ' ' => '', "\t" => ''));
        $attr = array('name' => $name, 'remark' => $remark, 'sub_param' => array());
        if (preg_match('/^([a-z]+)(\??)([\S]*)/', $type, $matche)) {
            $attr['type'] = $matche[1];
            $attr['require'] = $matche[2] === '';
        }
        if ($attr['type'] === 'array') {
            if (preg_match('/^\[([a-z]*)\]$/', $matche[3], $_matche)) {
                $attr['sub_param'] = $_matche[1];
            } elseif (preg_match('/^\[[ \t\n]*array\{([\s\S]+)\}[ \t\n]*\]$/', $matche[3], $_matche)) {
                if (preg_match_all('/([a-zA-Z_][a-zA-Z0-9_]*):(float\??\<[^\>]+\>|int\??\<[^\>]+\>|string\??\<[^\>]+\>|array\??\{[\s\S]+\}|array\??\[[\s\S]*\]|[a-z]+\??)[\t ]*([^,]*)/', $_matche[1], $__matches, PREG_SET_ORDER)) {
                    foreach ($__matches as $__matche) {
                        $attr['sub_param'][$__matche[1]] = self::_getParamAttr($__matche[1], $__matche[2], $__matche[3]);
                    }
                    $attr['type'] = 'array[object]';
                }
            } elseif (preg_match('/^\{([\s\S]+)\}$/', $matche[3], $_matche)) {
                if (preg_match_all('/([a-zA-Z_][a-zA-Z0-9_]*):(float\??\<[^\>]+\>|int\??\<[^\>]+\>|string\??\<[^\>]+\>|array\??\{[\s\S]+\}|array\??\[[\s\S]*\]|[a-z]+\??)[\t ]*([^,]*)/', $_matche[1], $__matches, PREG_SET_ORDER)) {
                    foreach ($__matches as $__matche) {
                        $attr['sub_param'][$__matche[1]] = self::_getParamAttr($__matche[1], $__matche[2], $__matche[3]);
                    }
                    $attr['type'] = 'object';
                }
            }
        }
        if ($attr['type'] === 'int' || $attr['type'] === 'float' || $attr['type'] === 'string') {
            if (preg_match('/^\<([\s\S]+)\>$/', $matche[3], $_matche)) {
                $attr['sub_param'] = explode(',', $_matche[1]);
                foreach ($attr['sub_param'] as $k => $v) {
                    if ($attr['type'] === 'int') {
                        $attr['sub_param'][$k] = intval($v);
                    } elseif ($attr['type'] === 'float') {
                        $attr['sub_param'][$k] = floatval($v);
                    } elseif ($attr['type'] === 'string') {
                        $attr['sub_param'][$k] = strval($v);
                    }
                }
            }
        }
        return $attr;
    }
}
