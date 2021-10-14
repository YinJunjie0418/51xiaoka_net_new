<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'psubsc'=>'app\command\Psubscribe',
        'regdata'=>'app\command\Regular',
        'restbatchno'=>'app\command\RestBatchno',
        'adddata'=>'app\command\Generate',
        'addbank'=>'app\command\GetAllBank',
    ],
];
