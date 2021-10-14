<?php
namespace app\merchants\validate;

use think\Validate;
use app\common\model\User;


class Forgetpassword extends Validate
{
	protected $regex = ['un' => '/^[A-Za-z0-9_-]{6,20}$/'];
	
    protected $rule = [
        'username' => 'require|regex:un|token',
		'codeno'=>'require|captcha|isName:username',
    ];

    protected $message = [
        'username.require' => ['code'=>'#username','msg'=>'请输入账号'],
		'username.regex' => ['code'=>'#username','msg'=>'用户名格式错误'],
		'codeno.isName' => ['code'=>'#username','msg'=>'账号不存在'],
		'username.token' => ['code'=>'#signupbtn','msg'=>'超时操作请刷新'],
		'codeno.require'=>['code'=>'#codeno','msg'=>'请输入验证码'],
		'codeno.captcha'=>['code'=>'#codeno','msg'=>'验证码错误']
		
    ];
	
	
	
	protected function isName($value,$rule,$data){
        if(User::where(['username|mobile|email'=>$data['username']])->find()){
			return true;
        }else{
            return false;
        }
    }

}
