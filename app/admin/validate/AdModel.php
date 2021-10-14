<?php

namespace app\admin\validate;

use think\Validate;

class AdModel extends Validate
{
    protected $rule = [
        'name' => 'require|unique:ad_model',
    ];

    protected $message = [
        'name.require' => '名称不能为空',
        'name.unique'  => '名称已存在',
    ];
}
