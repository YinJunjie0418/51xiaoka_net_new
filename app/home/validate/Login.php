<?php
namespace app\home\validate;

use think\Validate;
use app\common\model\User;


class Login extends Validate
{
    protected $rule = [
        'username' => 'require|isName:username',
        'password' => 'require|isPass:password',
		'phoneno'=>'require|mobile',
		'codeno'=>'require|length:6|isCode:codeno',
    ];

    protected $message = [
        'username.require' => '请输入账号',
		'phoneno.require' => '请输入手机号',
		'username.token' => '超时操作请刷新',
		'phoneno.token' => '超时操作请刷新',
		'phoneno.mobile' => '手机号错误',
        'password.require' => '请输入密码',
    ];
	//定义验证场景
    protected $scene = [
        'name' => ['username','password'],
		'code'=>['phoneno','codeno'],		
    ];
	
	protected function isCode($value,$rule,$data=[]){
		$code=session('?login'.$data['phoneno']);
        if($code){
			$code=session('login'.$data['phoneno']);
			if($code['time']<time()){
				session('login'.$data['phoneno'],null);
				return json_encode(['tip'=>"#codeno", 'content'=>"验证码已经过期",'token'=>token()]);
			}elseif($value!=$code['code']){
				return json_encode(['tip'=>"#codeno", 'content'=>"验证码错误",'token'=>token()]);
			}else{
				session('login'.$data['phoneno'],null);
				$admin = User::where(['mobile' => $data['phoneno']])->find();
				if($admin['status'] != 1){
					return json_encode(['tip'=>"#phoneno", 'content'=>"帐号被禁用，请联系管理员"]);
				}elseif($admin['type']!=0) {
                    return json_encode(['tip'=>"#phoneno", 'content'=>"用户不存在"]);
                }else{
				    if(session("?wxbind")){
    				    $admin->wxopenidg=session("wxbind.openid");
        		        $admin->unionid=session("?wxbind.unionid")?session("wxbind.unionid"):'';
    				}
					$token=makeToken();
					$admin->token=$token;
					$admin->timeout=strtotime("+1 days");
					$admin->last_login_ip=$this->request->ip();
					$auth = [
						'user_id' => $admin['id'],
						'shop_id' => $admin['shopid'],
						'mobile' => $admin['mobile'],
						'token'=>$token
					];
					$admin->save();
					session('user_auth', $auth);
					session('user_auth_sign', data_auth_sign($auth));
					session('login'.$data['phoneno'],null);
					return true;
				}
			}
        }else{
            return json_encode(["tip"=>"#codeno","content"=>"参数错误",'token'=>token()]);
        }
    }
	protected function isName($value){
        if(User::where(['username|mobile|email'=>$value])->find()){
            return true;
        }else{
            return json_encode(["tip"=>"#username","content"=>"账号或密码错误！",'token'=>token()]);
        }
    }
	
	protected function isPass($value,$rule,$data=[]){
		$admin = User::where(['username|mobile|email' => $data['username']])->find();
		$dengl=cache($admin['id']);
		if(isset($dengl['ci']) && $dengl['ci']<=1){
			return json_encode(['id'=>"#sign-error", 'content'=>"登陆错误次数太多请5分钟后再试！",'token'=>token()]);
		}
		if($admin && md6($value,$admin['password'])===true){
			if($admin['status'] != 1){
				return json_encode(['id'=>"#sign-error", 'content'=>"帐号被禁用，请联系管理员"]);
			}elseif($admin['type']!=0) {
                return json_encode(['id'=>"#sign-error", 'content'=>"用户不存在"]);
            }else{
			    if(session('?qq_userinfo')){
					$admin->qqopenid=session('qq_userinfo.openid');
				}
				if(session('?userwxauth')){
				    $admin->wxopenid=session("userwxauth.openid");
				    $admin->unionid=session("userwxauth.unionid");
				}
				if(session("?wxbind")){
				    $admin->wxopenidg=session("wxbind.openid");
    		        $admin->unionid=session("?wxbind.unionid")?session("wxbind.unionid"):'';
				}
				$token=makeToken();
				$admin->token=$token;
				$admin->timeout=strtotime("+1 days");
				$admin->last_login_ip=$this->request->ip();
                $auth = [
                    'user_id' => $admin['id'],
                    'shop_id' => $admin['shopid'],
                    'mobile' => $admin['mobile'],
					'token'=>$token
                ];
				$admin->save();
                session('user_auth', $auth);
                session('user_auth_sign', data_auth_sign($auth));
				return true;
			}
		}else{
			$dengl=cache($admin['id']);
			$ci=6;
			if(isset($dengl['ci'])){
				$dengl['ci']--;
				$ci=$dengl['ci'];
				cache($admin['id'],$dengl,300);
			}else{
				$ci--;
				cache($admin['id'],['ci'=>5],300);
			}
			return json_encode(['id'=>"#sign-error", 'content'=>"帐号或密码错误，您还可以尝试{$ci}次！",'token'=>token()]);
		}
	}
	
}

