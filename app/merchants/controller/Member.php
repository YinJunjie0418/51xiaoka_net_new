<?php
declare (strict_types = 1);

namespace app\merchants\controller;

use app\common\model\Recharge;
use think\Request;
use think\facade\View;
use app\common\controller\UserBase;
use app\common\model\Article;
use app\common\model\User;
use app\common\model\Userbank;
use think\facade\Config as Con;
use app\common\model\MoneyLog;


class Member extends UserBase
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
		$news=Article::where(['status'=>1,'cid'=>12])->order("id desc")->find();
		View::assign("tix",Recharge::where(['uid'=>session('user_auth.user_id')])->where('status','0')->sum('money'));
		View::assign("new",$news);
		View::assign("zan",cache(session('user_auth.user_id')."money"));
		View::assign("url",(new Wechat())->bindweixin());
		if(request()->isMobile()){
			return view('member/wap/index',['title'=>"用户中心",'xt'=>testagent(),'wx' => Con::load('setting/wxapp','wxapp')]);
		}else{
			return view();
		}
    }

    public function profile(){
		if(request()->isMobile()){
			return view('member/wap/profile',['title'=>"资料中心"]);
		}else{
		    return view();
		}
	}
	
	public function checkQain(){
	    $ulv=User::with('userReal')->where(['id'=>session('user_auth.user_id')])->find();
	    if($ulv['userReal']['evidenceHash']){
	        echo 1;
	    }else{
	        echo 0;
	    }
	}
	
	public function realname(){
		if($this->user['userReal']['retype']<1 && !empty($this->user['mobile'])){
			$this->redirect(url('merchants/Account/index'));
		}
		if(request()->isMobile()){
			return view('member/wap/realname',['title'=>"实名认证"]);
		}else{
		    return view();
		}
	}
	
	public function setcash(){
		View::assign("pei",Con::load('setting/cash','cash'));
		if(request()->isMobile()){
			 View::assign("empty",'<div class="cashcards-empty">暂未添加提现账号</div>');
			return view('member/wap/bank',['title'=>"提现账户管理",'list'=>Userbank::with('img')->where(['uid'=>session('user_auth.user_id')])->where('bankid','>','0')->select()]);
		}else{
		   return view('bank',['list'=>Userbank::with('img')->where(['uid'=>$this->user['id']])->where('bankid','>','0')->select()]);
		}
		
	}
	
	public function alipay(){
		View::assign("pei",Con::load('setting/cash','cash'));
		View::assign("list",Userbank::where(['uid'=>session('user_auth.user_id'),'bankid'=>'-1'])->select());
		if(request()->isMobile()){
			 View::assign("empty",'<div class="cashcards-empty">暂未添加提现账号</div>');
			return view('member/wap/alipay',['title'=>'支付宝账户管理']);
		}else{
		   return view();
		}
	}
	public function weixin(){
		View::assign("pei",Con::load('setting/cash','cash'));
		View::assign("list",Userbank::where(['uid'=>session('user_auth.user_id'),'bankid'=>'-2'])->select());
		if(request()->isMobile()){
			 View::assign("empty",'<div class="cashcards-empty">暂未添加微信账号</div>');
			return view('member/wap/weixin',['title'=>'微信账户管理']);
		}else{
		   return view();
		}
	}
	 public function delqq()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            User::where(['id'=>session('user_auth.user_id')])->update(['qqopenid'=>'']);
            return json(['confirm'=>['name'=> "解绑成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'reload'],'content'=>'解除QQ登陆成功...']);
        }
		return view();
    }
    
    public function delwx()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            User::where(['id'=>session('user_auth.user_id')])->update(['wxopenid'=>'','unionid'=>'','wxopenidg'=>'']);
            return json(['confirm'=>['name'=> "解绑成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'reload'],'content'=>'解除微信绑定成功...']);
        }
		return view();
    }
	public function addqq(){
		if ($this->request->isPost()){
			$qq=input('contact');
			User::where(['id'=>$this->user['id']])->update(['qq'=>$qq]);
			return json(['confirm'=>['name'=> "设置QQ成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'reload'],'content'=>'操作成功....']);
		}
		return view('actqq');
	}
	
	public function password(){
		if ($this->request->isPost()){
			$data=input();
			try{
				$this->validate($data, 'editpass.pass');
            }catch (\Exception $e){
				$str=$e->getMessage();
				$res=getArr($str);
				return json(["tip"=>$res[0],"content"=>$res[1],'token'=>token()]);
            }
			$user=User::where(['id'=>$this->user['id']])->update(['password'=>md6($data['verifypsw'])]);
			return json(['confirm'=>['name'=> "重置成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'/user_index.html'],'content'=>'操作成功....']);	
		}
		if(request()->isMobile()){
			return view('member/wap/password',['title'=>'密码修改']);
		}else{
		  return view();
		}
	}
	
	public function paymentcodepin(Request $request){
		if ($this->request->isPost()){
			$data=input();
			$check = $request->checkToken('__token__');
			if(false === $check) {
				return json(['tip'=>"#codeno", 'content'=>"非法操作",'token'=>token()]);
			}
			$code=session("?tradepwd".$this->user['mobile']);
			if($code){
				$res=session("tradepwd".$this->user['mobile']);
				if($res['time']<time()){
					return json(['tip'=>"#codeno", 'content'=>"验证码过期",'token'=>token()]);
				}
				if($res['code']==$data['codeTran']){
					$user=User::where(['id'=>$this->user['id']])->update(['tradepwd'=>md6($data['tradePwd'])]);
					session('tradepwd'.$this->user['mobile'],null);
					return json(['confirm'=>['name'=> "安全密码设置成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'reload'],'content'=>'操作成功....']);
				}else{
					return json(['tip'=>"#codeno", 'content'=>"验证码错误",'token'=>token()]);
				}
			}else{
				return json(['tip'=>"#codeno", 'content'=>"验证错误，请重新获取验证码",'token'=>token()]);
			}
			
		}
		if(request()->isMobile()){
			return view('member/wap/paymentcodepin',['title'=>'密码修改']);
		}else{
		    return view();
		}
	}
	
	public function photo(){
		if(empty($this->user['mobile'])){
			$this->redirect(url('merchants/Mobile/setphoto'));
		}
		if(request()->isMobile()){
			return view('member/wap/photo',['title'=>'手机设置']);
		}else{
		  return view();
		}
	}
	public function email(){
		if(empty($this->user['email'])){
			$this->redirect(url('merchants/Email/setEmail'));
		}
		if(request()->isMobile()){
			return view('member/wap/email',['title'=>'邮箱设置']);
		}else{
		  return view();
		}
	}
	public function checkPCode(Request $request){
		if ($this->request->isPost()){
			$data=input();
			$check = $request->checkToken('__token__');
			if(false === $check){
				return json(['tip'=>'#mcode', 'content'=>"非法操作",'token'=>token()]);
			}
			if(isset($data['codeno']) && isset($data['type'])){
				$code=session("?tradepwd".$this->user['mobile']);
				if($code){
					$res=session("tradepwd".$this->user['mobile']);
					if($res['time']<time()){
						return json(['tip'=>"#codeno", 'content'=>"验证码过期",'token'=>token()]);
					}
					if($res['code']==$data['codeno']){
						session($data['type'],time()+300);
						session("tradepwd".$this->user['mobile'],null);
						return json(['url'=>'reload']);
					}else{
						return json(['tip'=>"#codeno", 'content'=>"验证码错误",'token'=>token()]);
					}
				}else{
					return json(['tip'=>"#codeno", 'content'=>"手机号被修改或验证码未发送成功",'token'=>token()]);
				}
			}
		}
	}
	public function checkECode(Request $request){
		if ($this->request->isPost()){
			$data=input();
			$check = $request->checkToken('__token__');
			if(false === $check){
				return json(['tip'=>"#emcode", 'content'=>"非法操作",'token'=>token()]);
			}
			if(isset($data['ecodeno']) && isset($data['type'])){
				$code=session("?upemail".$this->user['email']);
				if($code){
					$res=session("upemail".$this->user['email']);
					if($res['time']<time()){
						return json(['tip'=>"#ecodeno", 'content'=>"验证码过期",'token'=>token()]);
					}
					if($res['code']==$data['ecodeno']){
						session($data['type'],time()+300);
						session("upemail".$this->user['email'],null);
						return json(['url'=>'reload']);
					}else{
						return json(['tip'=>"#ecodeno", 'content'=>"验证码错误",'token'=>token()]);
					}
				}else{
					return json(['tip'=>"#ecodeno", 'content'=>"手机号被修改或验证码未发送成功",'token'=>token()]);
				}
			}
		}
	}
	public function checkPass(Request $request){
		if ($this->request->isPost()){
			$data=input();
			$check = $request->checkToken('__token__');
			if(false === $check){
				return json(['tip'=>"#oldpaypass", 'content'=>"非法操作",'token'=>token()]);
			}
			if(isset($data['oldpaypass'])){
				if(md6($data['oldpaypass'],$this->user['tradepwd'])===true){
						session($data['type'],time()+300);
						return json(['url'=>'reload']);
					}else{
						return json(['tip'=>"#oldpaypass", 'content'=>"安全密码错误",'token'=>token()]);
					}
				}else{
					return json(['tip'=>"#ecodeno", 'content'=>"请输入安全密码",'token'=>token()]);
				}
			}
	}
	
	public function memberlog(){
		$list=MoneyLog::where(['uid'=>session('user_auth.user_id')])->order('id desc')->paginate(15,false,['query' => request()->param()]);
		$money=0;
		foreach($list as $k=>$v){
			$list[$k]['type']=moneyType($v['type']);
		}
		View::assign("data",$list);
		if(request()->isMobile()){
		    View::assign("empty",'<div class="messager messager-empty"><div class="messager-icon"><i class="iconfont iconfont-empty"></i></div><div class="messager-text"><h2 class="messager-title">暂无资金记录</h2></div></div>');
			return view('member/wap/memberlog',['title'=>'资金记录']);
		}else{
		  return view();
		}
	}


	

}
