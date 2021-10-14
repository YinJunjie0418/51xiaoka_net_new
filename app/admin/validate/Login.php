<?php

namespace app\admin\validate;

use think\Validate;
use app\common\model\Admin;

class Login extends Validate
{
    protected $rule = [
        'username' => 'require|max:30|isadmin:username|alphaNum|token',
        'password' => 'require|max:30|login:password',
        'code'  => 'require|length:6|number|isok:code',
    ];

    protected $message = [
        'username.require' => '请输入账号',
		'username.max' => '账号不符合规则',
		'username.alphaNum' => '账号不符合规则',
		'username.token' => '操作超时',
		'username.isadmin'=>'管理员不存在',
		
        'password.require' => '请输入密码',
		'password.max' => '密码不符合规则',
		'password.login' => '密码错误',
		
        'code.require'  => '请输入验证码',
		'code.length'  => '验证码错误',
		'code.number'  => '验证码错误',
        'code.isok'  => '验证码错误',
    ];
	
	public function sceneLogin(){
		return $this->only(['username','password']);
	}
	
	public function sceneLogincode(){
		return $this->only(['username','password','code'])
		            ->remove('password', 'login:password');
	}
	
	public function sceneMsg(){
		return $this->only(['username','password'])
		            ->remove('password', 'login:password')
					->append('password', 'ismsg:password');
	}
	
	protected function ismsg($value,$rule,$data=[]){
		$admin = Admin::where(['username' => $data['username']])->find();
		if($admin && md6($data['password'],$admin['password'])){
			if($admin['status'] != 1)return false;
			$type=isset($data['type'])?$data['type']:"email";
			switch($data['type']){
				case "email":
				  session("faurl",$admin['email']);
				break;
				default:
				 session("faurl",$admin['mobile']);
			}
			return true;
		}else{
			return false;
		}
	}
	
	protected function isadmin($value,$rule,$data=[]){
		$admin = Admin::where(['username' => $value])->find();
		if($admin)return true;
		return false;
	}
	
	protected function isok($value,$rule,$data=[]){
		$res=session("login");
		if($res['time']<time() || $res['code']!=$value){
			return false;
		}else{
		    session("safecode",$res['code']);
			session("login",null);
			$admin = Admin::where(['username' => $data['username']])->find();
            if($admin && md6($data['password'],$admin['password'])){
                if($admin['status'] != 1)return false;
                $token=makeToken();
                $ip=request()->ip();
                $auth = [
                    'admin_id' => $admin['id'],
                    'username' => $admin['username'],
                    'ip'=>$ip,
                    'token'=>$token
                ];
                session('admin_auth', $auth);
                session('admin_auth_sign', data_auth_sign($auth));
                Admin::update([
                    'last_login_time' => time(),
                    'last_login_ip'   => $ip,
                    'login_count'     => $admin['login_count'] + 1,
                    'token'=>$token,
                    'timeout'=>strtotime("+1 days")
                ],['id' => $admin['id']]);
                insert_admin_log('登录了后台系统');
			     return true;
			}else{
				return false;
			}
		}
	}
	
	protected function login($value,$rule,$data=[]){
		$admin = Admin::where(['username' => $data['username']])->find();
		if($admin && md6($data['password'],$admin['password'])){
			if($admin['status'] != 1)return false;
			$token=makeToken();
			$ip=request()->ip();
			$auth = [
				'admin_id' => $admin['id'],
				'username' => $admin['username'],
                'ip'=>$ip,
				'token'=>$token
			];
			session('admin_auth', $auth);
			session('admin_auth_sign', data_auth_sign($auth));
			Admin::update([
				'last_login_time' => time(),
				'last_login_ip'   => $ip,
				'login_count'     => $admin['login_count'] + 1,
				'token'=>$token,
				'timeout'=>strtotime("+1 days")
			],['id' => $admin['id']]);
			insert_admin_log('登录了后台系统');
			return true;
		}else{
			return false;
		}
	}
	
}
