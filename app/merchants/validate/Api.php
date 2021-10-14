<?php
namespace app\merchants\validate;

use think\Validate;
use app\common\model\User;


class Api extends Validate
{
    protected $rule = [
	    'phoneno' => 'requirea:phoneno|isOkphoto:phoneno|isMobile:phoneno|noMobile:phoneno|okMobile:phoneno',
		'emailno'=>'emaila|isEmail:emailno',
		'regcode'=>'require|length:5|captcha'
		
    ];

    protected $message = [
		'username.token' => '非法操作',
        'password.require' => '请输入密码',
		'emailno.email' => '邮箱格式错误',
		'regcode.require'=>['code'=>'#regcode','msg'=>'请输入验证码'],
		'regcode.length'=>['code'=>'#regcode','msg'=>'验证码为5位字符'],
		'regcode.captcha'=>['code'=>'#regcode','msg'=>'验证码错误']
		
    ];
	
	
	public function sceneRegcode(){
		return $this->only(['phoneno','regcode'])
		        ->remove('phoneno', ['isMobile','okMobile']);
	}

    public function sceneCash(){
        return $this->only(['phoneno'])
            ->remove('phoneno', ['isOkphoto','noMobile','okMobile']);
    }
	
	public function sceneUpemail(){
		return $this->only(['emailno'])
		        ->remove('emailno', ['isEmail']);
	}
	
	public function sceneSetemail(){
		return $this->only(['emailno']);
	}
	
	public function sceneTradepwd(){
		return $this->only(['phoneno'])
		        ->remove('phoneno', ['noMobile','isMobile','okMobile:phoneno']);
	}
	
	public function sceneLogin(){
		return $this->only(['phoneno'])
		        ->remove('phoneno', ['noMobile','okMobile']);
	}
	
	public function sceneSetphoto(){
		return $this->only(['phoneno'])
		        ->remove('phoneno', ['isMobile','okMobile']);
	}
	protected function emaila($value){
		if(check_email($value)){
			return true;
		}else{
			return json_encode(["tip"=>"#emailno","content"=>"邮箱格式错误"]);
		}
	}
	protected function requirea($value,$rule,$data){
		if($value){
			$time=cookie($data['scene']."time");
			if($time>time()){
				$rt=$time-time();
				return json_encode(["eval"=>"codetime(".$rt.")"]);
			}else{
			  return true;
			}
		}else{
		  return json_encode(["tip"=>"#phoneno","content"=>"手机号不能为空"]);
		}
	}
	protected function isOkphoto($value){
		if(preg_match("/^1\d{10}$/", $value)){
			return true;
		}else{
			return json_encode(["tip"=>"#phoneno","content"=>"手机号格式错误"]);
		}
	}
	
	protected function isMobile($value){
		$user=User::where(['mobile'=>$value])->findOrEmpty();
        if(!$user->isEmpty()){
            return true;
        }else{
            return json_encode(["confirm"=>["name"=>"此手机号暂未与任何账户关联！","width"=>400,"prompt"=>"info"]]);
        }
    }
	
	protected function noMobile($value){
		$user=User::where(['mobile'=>$value])->findOrEmpty();
        if(!$user->isEmpty()){
            return json_encode(["tip"=>"#phoneno","content"=>"手机号已存在"]);
        }else{
            return true;
        }
    }
	
	protected function okMobile($value){
		$user=User::where(['mobile'=>$value,'id'=>session('user_auth.user_id')])->findOrEmpty();
        if(!$user->isEmpty()){
            return true;
        }else{
            return json_encode(["eval"=>"","tip"=>"#mcode","type"=>2,"content"=>"发现异常，手机号与登陆账号不是绑定关系"]);
        }
    }
	
	protected function isEmail($value){
		$user=User::where(['email'=>$value])->findOrEmpty();
        if(!$user->isEmpty()){
            return json_encode(["tip"=>"#emailno","content"=>"邮箱已经存在"]);;
        }else{
            return true;
        }
    }
	
	
}
