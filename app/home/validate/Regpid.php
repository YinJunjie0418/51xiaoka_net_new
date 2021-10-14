<?php
namespace app\home\validate;

use think\Validate;
use app\common\model\User;


class Regpid extends Validate
{
	protected $regex = ['un' => '/^[A-Za-z0-9_-]{6,20}$/','pass' => '/^[A-Za-z0-9_-~!@#$%^&amp;*()\[\]_+-={}?,.\/]{6,20}$/'];
	
    protected $rule = [
        'username' => 'require|isName:username|regex:un|token',
        'newpsw' => 'require|length:6,20|alphaDash|regex:pass',
        'verifypsw' => 'require|confirm:newpsw',
		'codeno'=>'require|iscode:codeno',
		'refer'=>'require|isok:refer'
    ];

    protected $message = [
        'username.require' => ['code'=>'#username','msg'=>'请输入账号'],
		'username.regex' => ['code'=>'#username','msg'=>'用户名格式错误'],
		'username.isName' => ['code'=>'#username','msg'=>'用户名已经被注册'],
		'username.token' => ['code'=>'#signupbtn','msg'=>'超时操作请刷新'],
		'newpsw.require' => ['code'=>'#newpsw','msg'=>'请输入密码'],
		'newpsw.length' => ['code'=>'#newpsw','msg'=>'密码长度6~20为数组字母组合'],
		'newpsw.regex' => ['code'=>'#newpsw','msg'=>'密码长度6~20为数组字母组合'],
        'newpsw.alphaDash' => ['code'=>'#newpsw','msg'=>'密码长度6~20为数组字母组合'],
		'verifypsw.confirm'=>['code'=>'#verifypsw','msg'=>'两次密码不一致'],
		'verifypsw.require' => ['code'=>'#verifypsw','msg'=>'请输入密码'],
		'codeno.require'=>['code'=>'#codeno','msg'=>'请输入验证码'],
		'codeno.iscode'=>['code'=>'#codeno','msg'=>'验证码错误'],
		'refer.require'=>['code'=>'#agreement','msg'=>'参数错误'],
		'refer.isok'=>['code'=>'#agreement','msg'=>'请勾选同意'],
		
    ];
	
	
	
	protected function isName($value){
        if(User::where(['username'=>$value])->find()){
            return false;
        }else{
            return true;
        }
    }
	protected function isok($value){
        return $value?true:false;
    }
	
	protected function iscode($value,$rule,$data){
		$res=session('regcode'.$data['phoneno']);
		if($res){
			if($res['time']>time()){
				if($res['code']==$value){
				   return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
}
