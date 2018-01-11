<?php
namespace Ypi\Core;

class I18n {
    private $_i18n_dir, $_dev_lang, $_lang_arr;

    public function __construct($i18n_dir, $dev_lang)
    {
        $this->_i18n_dir = $i18n_dir;
        $this->_dev_lang = $dev_lang;
        $this->_lang_arr = null;
    }

    public function trans($str, $lang)
    {
        if (is_null($this->_lang_arr)) {
            $langfile = $this->_i18n_dir . DS . $lang . '.inc';
            if (is_file($langfile)) {
                $this->_lang_arr = require $langfile;
            } else {
                $this->_lang_arr = array();
            }
        }
        if (isset($this->_lang_arr[$str])) {
            return $this->_lang_arr[$str];
        }
        return $this->transFromRemote($str, $lang);
    }

    private function transFromRemote($str, $lang)
    {
        if ($lang === $this->_dev_lang) {
            $this->saveI18n($str, $lang, $str);
            return $this->_lang_arr[$str];
        }
        $url = 'http://api.fanyi.baidu.com/api/trans/vip/translate';
        $post_data = array ('q' => $str, 'from' => 'auto', 'to' => $lang, 'appid' => '20171222000107514', 'salt' => mt_rand());
        $post_data['sign'] = strtolower(md5("{$post_data['appid']}{$post_data['q']}{$post_data['salt']}mu4YJ4THFrGMvcyOtEwO"));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($output, true);
        foreach ($result['trans_result'] as $res) {
            $this->saveI18n($res['src'], $lang, $res['dst']);
        }
        return $this->_lang_arr[$str];
    }

    private function saveI18n($str, $lang, $trans_lang)
    {
        $langfile = $this->_i18n_dir . DS . $lang . '.inc';
        $content = "<?php\n\nreturn array(\n);\n";
        if (is_file($langfile)) {
            $content = file_get_contents($langfile);
        }
        $content = preg_replace('/\);\n?$/', "    '{$str}' => '{$trans_lang}',\n);\n", $content);
        $this->_lang_arr[$str] = $trans_lang;
        file_put_contents($langfile, $content);
    }
}
