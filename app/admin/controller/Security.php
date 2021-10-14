<?php
namespace app\admin\controller;
use app\common\controller\AdminBase;
use think\facade\View;
use think\facade\Request;
use app\common\model\Config;
use app\common\model\Admin;
use app\common\model\Security as SE;

class Security extends AdminBase
{
    
    public function index()
    {
		!$this->checkto() && $this->redirect(url('/Security/login'));
		if($this->request->isPost()) {
			$param  = $this->request->param();
			$param['datatype']=(isset($param['datatype']) && $param['datatype']=="on")?1:0;
			if($param['datatype']==1){
				if(empty($param['datakey'])){
					$this->error("加密密钥不能为空");
				}
				if(strlen($param['datakey'])>24){
					$this->error("加密密钥不能大于24位");
				}
			}
			if(empty($param['id'])){
				(new SE)->save($param);
			}else{
				SE::update($param,['id'=>$param['id']]);
			}
			$this->success("配置成功");
		}
        View::assign("data",SE::order('id desc')->find());
        return view();
    }
	
		
	
	public function checkto(){
		$sid=session("admin_auth.admin_id");
		if($sid!=1){
			return false;
		}
		if(!session("?Security")){
			return false;
		}else{
			if(session("Security.time")<time()){
				return false;
			}
			if(!session("Security.isok")){
				return false;
			}
		}
		return true;
	}
	
	public function login(){
		if($this->request->isPost()) {
            $code  = input('code');
			$url="";
			try{
				$yan=session("Securityyan");
				if($yan['time']<time()){
					$msg="验证码过期";
				}elseif($yan['code']!=$code){
					$msg="验证码错误";
				}else{
					$da=['isok'=>true,'time'=>time()+300];
					session("Security",$da);
					session("Securityyan",null);
					$msg="验证成功";
					$url=url('/Security/index');
				}
			}catch (\Exception $e){
				$this->error($e->getMessage());
			}
			if(empty($url)){
				$this->error($msg);
			}else{
				$this->success($msg,$url);
			}
		}
		return view();
	}
	
	public function sendmsg(){
		if(session('admin_auth.admin_id')!=1){
			$this->error("本功能只有超级管理员可以操作");
		}
		$admin=Admin::find(session('admin_auth.admin_id'));
		$type=input('type');
		$code=generate_password(4);
		$da=['code'=>$code,'time'=>time()+300];
		session("Securityyan",$da);
		if($type=="email"){
			if(empty($admin['email']))$this->error("请设置管理员邮箱");
			$res=sendEmail($admin['email'],$code);
			if($res['code']==1){
				$this->success("请接收邮件验证码");
			}else{
				$this->error($res['msg']);
			}
		}else{
			if(empty($admin['mobile']))$this->error("请设置管理员手机号");
			$res=sendMsg($admin['mobile'],"tMsg",$code);
			if($res['code']==1){
				$this->success("请接收手机验证码");
			}else{
				$this->error($res['msg'].$code);
			}
		}
	}
   
	
	
    
}
