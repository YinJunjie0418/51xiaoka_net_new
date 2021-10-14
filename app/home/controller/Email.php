<?php
declare (strict_types = 1);

namespace app\home\controller;

use think\Request;
use think\facade\View;
use app\common\controller\UserBase;
use app\common\model\User;


class Email extends UserBase
{
    public function setEmail(Request $request){
		if ($this->request->isPost()){
			$data=input();
			$check = $request->checkToken('__token__');
			if(false === $check){
				return json(['tip'=>"#ecodeno", 'content'=>"非法操作",'token'=>token()]);
			}
			if(isset($data['emailno']) && isset($data['ecodeno'])){
				$code=session("?setemail".$data['emailno']);
				if($code){
					$res=session("setemail".$data['emailno']);
					if($res['time']<time()){
						return json(['tip'=>"#ecodeno", 'content'=>"验证码过期",'token'=>token()]);
					}
					if($res['code']==$data['ecodeno']){
						$user=User::where(['id'=>$this->user['id']])->update(['email'=>$data['emailno']]);
						session('setemail'.$data['emailno'],null);
						session('upemail',null);
						return json(['confirm'=>['name'=> "设置成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'/user_email.html'],'content'=>'操作成功....']);
					}else{
						return json(['tip'=>"#emcode", 'content'=>"验证码错误",'token'=>token()]);
					}
				}else{
					return json(['tip'=>"#emailno", 'content'=>"邮箱已修改或验证码未发送成功",'token'=>token()]);
				}
				
			}else{
				return json(['tip'=>"#emcode", 'content'=>"参数错误",'token'=>token()]);
			}
		}
		if(request()->isMobile()){
			return view('email/wap/set_email',['title'=>'邮箱设置']);
		}else{
		  return view();
		}
	}
	public function upEmail(Request $request){
		$isup=0;
		if(session('?upemail')){
			if(session('upemail')>time()){
				$isup=1;
			}
		}
		View::assign("isup",$isup);
		if(request()->isMobile()){
			return view('email/wap/up_email',['title'=>'邮箱更改']);
		}else{
		  return view();
		}
	}
	
}
