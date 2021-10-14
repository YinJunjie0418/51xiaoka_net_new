<?php
declare (strict_types = 1);

namespace app\home\controller;

use think\Request;
use think\facade\View;
use app\common\controller\UserBase;
use app\common\model\User;


class Mobile extends UserBase
{
    public function setphoto(Request $request){
		if ($this->request->isPost()){
			$data=input();
			$check = $request->checkToken('__token__');
			if(false === $check) {
			//	return json(['tip'=>"#codeno", 'content'=>"非法操作",'token'=>token()]);
			}
			if(isset($data['phoneno']) && isset($data['codeno'])){
				$code=session("?setphoto".$data['phoneno']);
				if($code){
					$res=session("setphoto".$data['phoneno']);
					if($res['time']<time()){
						return json(['tip'=>"#codeno", 'content'=>"验证码过期",'token'=>token()]);
					}
					if($res['code']==$data['codeno']){
						$user=User::where(['id'=>$this->user['id']])->update(['mobile'=>$data['phoneno']]);
						session('setphoto'.$data['phoneno'],null);
						session('upphoto',null);
						return json(['confirm'=>['name'=> "设置成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'/user_photo.html'],'content'=>'操作成功....']);
					}else{
						return json(['tip'=>"#codeno", 'content'=>"验证码错误",'token'=>token()]);
					}
				}else{
					return json(['tip'=>"#phoneno", 'content'=>"手机号被修改或验证码未发送成功",'token'=>token()]);
				}
				
			}
		}
		if(request()->isMobile()){
			return view('mobile/wap/setphoto',['title'=>'绑定手机']);
		}else{
		  return view();
		}
	}
	public function upphoto(Request $request){
		$isup=0;
		if(session('?upphoto')){
			if(session('upphoto')>time()){
				$isup=1;
			}
		}
		View::assign("isup",$isup);
		if(request()->isMobile()){
			return view('mobile/wap/upphoto',['title'=>'修改绑定手机']);
		}else{
		  return view();
		}
	}
	
}
