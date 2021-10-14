<?php

namespace app\admin\validate;

use think\Validate;

class CategoryModelFieldEdit extends Validate
{
    protected $rule = [
        //'model' => 'require',
        'title' => 'require',
    ];

    protected $message = [
        'model.require' => '模型不能为空',
        'title.require' => '标题不能为空',
    ];
}
