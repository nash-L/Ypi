<?php

return array(
    'BASE_NAMESPACE' => 'App\\Controllers',
    'MODE' => 'debug', // debug online
    'DOCUMENT' => true,
    'DOCUMENT_DIR' => ROOT . DS . 'doc',
    'MAP_FILE' => ROOT . DS . 'runtime' . DS . 'map.source',
    'I18N_DIR' => ROOT . DS . 'runtime' . DS . 'i18n',

    'DEV_LANG' => 'zh',
    // zh   中文
    // en   英语
    // yue  粤语
    // wyw  文言文
    // jp   日语
    // kor  韩语
    // fra  法语
    // spa  西班牙语
    // th   泰语
    // ara  阿拉伯语
    // ru   俄语
    // pt   葡萄牙语
    // de   德语
    // it   意大利语
    // el   希腊语
    // nl   荷兰语
    // pl   波兰语
    // bul  保加利亚语
    // est  爱沙尼亚语
    // dan  丹麦语
    // fin  芬兰语
    // cs   捷克语
    // rom  罗马尼亚语
    // slo  斯洛文尼亚语
    // swe  瑞典语
    // hu   匈牙利语
    // cht  繁体中文
    // vie  越南语

    'HEADER_KEYS' => array(
        'LANGUAGE' => 'api-language',
        'VERSION' => 'api-version',
        'FORMAT' => 'api-format',
        'STATUS_TYPE' => 'api-status',
    ),

    'DEF_SETTING' => array(
        'LANGUAGE' => 'en',
        'VERSION' => '0.0.1',
        'FORMAT' => 'json',
        'STATUS_TYPE' => 'http', // http code
    ),
);