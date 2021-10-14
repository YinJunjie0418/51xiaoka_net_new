<?php
declare (strict_types = 1);

namespace app\home\controller;

use think\Request;
use think\facade\View;
use app\common\controller\UserBase;
use app\common\model\User;


class Editpass extends UserBase
{
    public function index(Request $request){
		if ($this->request->isPost()){
			$data=input();
			try{
				$this->validate($data, 'editpass.epass');
            }catch (\Exception $e){
				$str=$e->getMessage();
				$res=getArr($str);
				return json(["tip"=>$res[0],"content"=>$res[1],'token'=>token()]);
            }
			$user=User::where(['id'=>$this->user['id']])->update(['tradepwd'=>md6($data['verifypsw'])]);
			session('uppass',null);
			return json(['confirm'=>['name'=> "重置成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'/user_paymentcodepin.html'],'content'=>'操作成功....']);	
		}
		$isup=0;
		if(session('?uppass')){
			if(session('uppass')>time()){
				$isup=1;
			}
		}
		
		View::assign("isup",$isup);
		if(request()->isMobile()){
			return view("editpass/wap/index",['title'=>'密码修改']);
		}else{
		   return view();
		}
	}
	
}
