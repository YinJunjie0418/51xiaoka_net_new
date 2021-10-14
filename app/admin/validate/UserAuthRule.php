<?php

namespace app\admin\validate;

use think\Validate;

class UserAuthRule extends Validate
{
    protected $rule = [
        'name'       => 'require',
        'sort_order' => 'number',
    ];

    protected $message = [
        'name.require'      => '名称不能为空',
        'sort_order.number' => '排序必须是数字',
    ];
}
