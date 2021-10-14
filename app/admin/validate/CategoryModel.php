<?php

namespace app\admin\validate;

use think\Validate;

class CategoryModel extends Validate
{
    protected $rule = [
        'name' => 'require',
        'model' => 'require|alphaDash|unique:category_model',
    ];

    protected $message = [
        'name.require' => '名称不能为空',
        'model.require' => '模型不能为空',
        'model.alphaDash' => '模型只能为字母或者数字',
        'model.unique' => '模型名称重复'
    ];
}
