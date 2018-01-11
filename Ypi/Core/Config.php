<?php
namespace Ypi\Core;

class Config {
    private $_conf;

    public function __construct(array $conf)
    {
        foreach ($conf as $k => $v) {
            $this->_conf[strtoupper($k)] = $v;
        }
    }

    public function get($key)
    {
        $key = strtoupper($key);
        if (isset($this->_conf[$key])) {
            return $this->_conf[$key];
        }
    }

    public function getHeader($name)
    {
        $name = strtoupper($name);
        if (isset($this->_conf['HEADER_KEYS'][$name])) {
            $key = 'HTTP_' . strtoupper(strtr($this->_conf['HEADER_KEYS'][$name], '-', '_'));
            if (isset($_SERVER[$key])) {
                return $_SERVER[$key];
            } else {
                return $this->_conf['DEF_SETTING'][$name];
            }
        }
        return '';
    }

    public function getHeaderDef($name)
    {
        $name = strtoupper($name);
        if (isset($this->_conf['DEF_SETTING'][$name])) {
            return $this->_conf['DEF_SETTING'][$name];
        }
        return '';
    }

    public function set($key, $val)
    {
        $this->_conf[strtoupper($key)] = $val;
    }

    public function __get($key)
    {
        return $this->get($key);
    }

    public function __set($key, $val)
    {
        $this->set($key, $val);
    }
}
