<?php

namespace app\admin\validate;

use think\Validate;

class User extends Validate
{
    protected $rule = [
        'mobile'   => 'require|mobile|unique:user',
        'password' => 'min:6|max:16',
    ];

    protected $message = [
        'mobile.require'   => '手机号不能为空',
        'mobile.mobile'    => '手机号码格式不正确',
		'mobile.unique'    => '手机号码已经存在',
        'password.min'     => '密码长度不能小于6位',
        'password.max'     => '密码长度不能大于16位',
    ];
	
	
}
