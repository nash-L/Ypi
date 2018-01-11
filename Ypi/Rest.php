<?php
namespace Ypi;

use Exception;

use Ypi\Core\Config;
use Ypi\Core\App;
use Ypi\Core\Router;
use Ypi\Core\I18n;

use SimpleXMLElement;

class Rest {
    private static $mode = 'api'; // document

    public function run(array $conf = array('mode' => 'debug', 'base_namespace' => 'App\\Controllers'))
    {
        try {
            App::service('config', function () use (&$conf) {
                return new Config($conf);
            });

            App::service('router', function () {
                return new Router(App::config()->base_namespace, App::config()->mode, App::config()->map_file, App::config()->document_dir);
            });

            App::service('inter', function () {
                return new I18n(App::config()->i18n_dir, App::config()->dev_lang);
            });

            App::server('i18n', function ($str) {
                return App::inter()->trans($str, App::config()->getHeader('language'));
            });

            $result = App::router()->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

            if (self::$mode === 'api') {
                $this->_reback(array('code' => 200, 'data' => $result, 'msg' => App::i18n('请求成功')));
            }
        } catch (Exception $e) {
            $this->_reback(array('code' => $e->getCode(), 'data' => null, 'msg' => App::i18n($e->getMessage())));
        }
    }

    public static function changeMode($mode) {
        self::$mode = $mode;
    }

    private function _reback($data)
    {
        switch (App::config()->getHeader('status_type')) {
            case 'code': header('HTTP/1.1 200 OK');
            case 'http': header("HTTP/1.1 {$data['code']} {$data['msg']}");
        }
        echo $this->_formatData($data, App::config()->getHeader('format'));
    }


    private function _formatData(array $data, $format, SimpleXMLElement &$xmlDom = null)
    {
        switch ($format) {
            case 'json':
                header('Content-Type: application/json;charset=utf-8');
                return json_encode($data);
            case 'xml': 
                header('Content-Type: text/xml;charset=utf-8');
                if (is_null($xmlDom)) {
                    $xmlDom = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?><document></document>');
                }
                foreach ($data as $k => $v) {
                    $key = $k;
                    if (is_numeric($k)) {
                        $key = 'array-item';
                    }
                    if (is_array($v)) {
                        $newDom = $xmlDom->addChild($key);
                        self::_formatData($v, $format, $newDom);
                    } else {
                        $newDom = $xmlDom->addChild($key, $v);
                    }
                }
                return $xmlDom->asXML();
        }
    }
}
