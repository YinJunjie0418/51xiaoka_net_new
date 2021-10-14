<?php

namespace app\admin\validate;

use think\Validate;

class Card extends Validate
{
    protected $rule = [
        'title' => 'require',
        'image' => 'require',
    ];

    protected $message = [
        'title.require' => '分类名称不能为空',
        'image.require' => '图标不能为空',
    ];
}
