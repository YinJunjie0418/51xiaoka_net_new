<?php

namespace app\admin\validate;

use think\Validate;

class CategoryModelField extends Validate
{
    protected $rule = [
        'model' => 'require',
        'title' => 'require',
        'name' => 'require',
        'type' => 'require',
    ];

    protected $message = [
        'model.require' => '模型不能为空',
        'title.require' => '标题不能为空',
        'model.require' => '标识不能为空',
        'type.require' => '类型不能为空',
    ];
}
