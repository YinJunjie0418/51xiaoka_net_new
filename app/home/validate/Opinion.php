<?php
namespace app\home\validate;

use think\Validate;


class Opinion extends Validate
{
	
    protected $rule = [
	    'type'=>'require|token',
        'contact' => 'require|number',
		'title' => 'require|length:6,50',
        'content' => 'require',
    ];

    protected $message = [
        'type.require' => ['code'=>'#card_type"','msg'=>'非法参数1'],
		'type.token' => ['code'=>'#anjian','msg'=>'超时操作请刷新'],
		
		'title.require' => ['code'=>'#title','msg'=>'标题必填'],
		'title.length' => ['code'=>'#title','msg'=>'标题字符请控制在6-50字以内'],
		
		'content.require' => ['code'=>'#content','msg'=>'请输入内容'],
		

		'contact.require'=>['code'=>'#contact','msg'=>'请输入联系方式'],
		'contact.number'=>['code'=>'#contact','msg'=>'联系方式只能是手机或QQ']
		
    ];

	
}
