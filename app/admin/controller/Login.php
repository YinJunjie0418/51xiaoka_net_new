<?php
namespace app\admin\controller;

use app\common\controller\Base;
use app\common\model\MoneyLog;
use app\common\model\Sysconfig;
use think\facade\View;
use think\facade\Request;
use app\common\model\Security;

class Login extends Base
{
     public function initialize()
    {
        parent::initialize();
        $arr=[
            'ip'          => request()->ip(),
            'url'         => request()->url(true),
            'method'      => request()->method(),
            'type'        => request()->type(),
            'param'       => request()->param(),
            'create_time' => date("Y-m-d H:i:s")
        ];
        if(!strstr($arr['url'], 'ssedistill')) adminlog(json_encode($arr));
        $res=Sysconfig::select()->toArray();
        $list=[];
        foreach($res as $k=>$v){
            $list[$v['name']]=$v['value'];
        }
        View::assign('title',$list['sitename']);
        View::assign('url',$list['domain']);
    }
    
    public function index()
    {
		$safe=Security::order("id desc")->find();
        if ($this->request->isPost()) {
            $param = $this->request->param();
			try{
				if($safe['isyan']!=0){
					$this->validate($param, 'login.logincode');
				}else{
					$this->validate($param, 'login.login');
				}
			}catch (\Exception $e){
                   return json(['code'=>-1,'msg'=>$e->getMessage(),'token'=>token()]);
            }
			return json(['code'=>1,'msg'=>"登录成功",'url'=>url('/index/index')->build()]);
        }

		View::assign("safe",$safe);
        return View::fetch('login');
    }
   
	public function sendmsg(){
		$data=input();
		try{
			$this->validate($data, 'login.msg');
		}catch (\Exception $e){
				return json(['code'=>-1,'msg'=>$e->getMessage(),'token'=>token()]);
		}
		$type=$data['type'];
		$code=mt_rand(100000,999999);
		$da=['code'=>$code,'time'=>time()+300];
		session("login",$da);
		trace($da);
		if(!session("?faurl"))return json(['code'=>-1,'msg'=>"非法操作",'token'=>token()]);
		if($type=="email"){
			if(empty(session("faurl")))return json(['code'=>-1,'msg'=>"请设置管理员邮箱",'token'=>token()]);
			$res=sendEmail(session("faurl"),$code,"","",'login');
			if($res['code']==1){
				session("faurl",null);
				return json(['code'=>1,'msg'=>"请接收邮件验证码",'token'=>token()]);
			}else{
				return json(['code'=>-1,'msg'=>$res['msg'],'token'=>token()]);
			}
		}else{
			if(empty(session("faurl")))return json(['code'=>-1,'msg'=>"请设置管理员手机号",'token'=>token()]);
			$res=sendMsg(session("faurl"),"tMsg",$code);
			if($res['code']==1){
				session("faurl",null);
				return json(['code'=>1,'msg'=>"请接收手机验证码",'token'=>token()]);
			}else{
				return json(['code'=>-1,'msg'=>$res['msg'],'token'=>token()]);
			}
		}
	}
	// 退出登录
    public function logout()
    {
        insert_admin_log('退出了后台系统');
        session('admin_auth', null);
        session('admin_auth_sign', null);
        session("safecode",null);
        return $this->redirect(url("/Login/index"));
    }
}
