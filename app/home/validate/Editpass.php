<?php
namespace app\home\validate;

use think\Validate;
use app\common\model\User;


class Editpass extends Validate
{
    protected $rule = [
        'newpsw' => 'require|length:6,20|alphaDash|token',
        'verifypsw' => 'require|confirm:newpsw',
		'verifycode'=>'require|length:5|captcha',
		'oldpsw'=>'require|isPass:oldpsw',
		
    ];

    protected $message = [
        'newpsw.require' => ['code'=>'#newpsw','msg'=>'请输入密码'],
		'verifypsw.require' => ['code'=>'#verifypsw','msg'=>'请输入密码'],
		'verifycode.require' => ['code'=>'#verifycode','msg'=>'请输入验证码'],
		'newpsw.length' => ['code'=>'#newpsw','msg'=>'密码长度6~20位数组字母组合'],
        'newpsw.alphaDash' => ['code'=>'#newpsw','msg'=>'密码长度6~20位数组字母组合'],
		'newpsw.token' => ['code'=>'#newpsw','msg'=>'数据过期请重新提交'],
		'verifypsw.confirm'=>['code'=>'#verifypsw','msg'=>'两次密码不一致'],
		'verifycode.length'=>['code'=>'#verifycode','msg'=>'验证码为5位字符'],
		'verifycode.captcha'=>['code'=>'#verifycode','msg'=>'验证码错误'],
		'oldpsw.require'=>['code'=>'#oldpsw','msg'=>'请输入原登陆密码'],
		'oldpsw.isPass'=>['code'=>'#oldpsw','msg'=>'原登陆密码错误']
    ];
	
	//定义验证场景
    protected $scene = [
        'pass' => ['oldpsw','newpsw','verifypsw','verifycode'],
		'epass' => ['newpsw','verifypsw','verifycode']
		
    ];
	
	protected function isPass($value){
		$pass=User::where(['id'=>session('user_auth.user_id')])->value('password');
		if(md6($value,$pass)){
			return true;
		}else{
			return false;
		}
	}

	
	
}
