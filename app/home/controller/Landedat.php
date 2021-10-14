<?php
namespace app\home\controller;

use app\common\controller\IndexBase;
use think\facade\View;
use think\facade\Request;
use think\facade\Session;
use think\facade\Config;

use app\common\model\User;

class Landedat extends IndexBase
{
    private $config;
	private $shenhe=false;
    //登陆
    public function qqlogin()
    {
        $this->getConfig('qq');
		$login=\wycto\login\Login::getApp('qq',$this->config);
		$url=$login->login();
		$this->redirect($url);
    }
    
     public function wxlogin()
    {
         session("wxbind",null);
        $this->getConfig('weixin');
		$logina=\wycto\login\Login::getApp('weixin',$this->config);
		$url=$logina->login();
		$this->redirect($url);
    }
    
    public function getConfig($as=''){
        switch($as){
            case 'qq':
				$qq=Config::load('setting/qq','qq');
				$config = array(
					'app_id' => isset($qq['appid'])?$qq['appid']:'',
					'app_key' => isset($qq['appkey'])?$qq['appkey']:'',
					'callback' => "/apiback/type/qq.html",
					'scope' => 'get_user_info',
					'expires_in' => 7775000,
					'framework'=>'tp'
				);
				$this->shenhe=isset($qq['type'])?$qq['type']:false;
				$this->config=$config;
            break;
            case "weixin":
                $weixin=Config::load('setting/wxpay','weixn');
                $config = array(
                 //开发平台获取
                'app_id' => $weixin['faappid'],
                //开发平台获取
                'app_secret' => $weixin['fasecretID'],
                //回掉地址，需要在腾讯开发平台填写
                'callback' => "/apiback/type/wx.html",
                //终端类型
                'terminal' => request()->isMobile()?'wap':"pc",//pc为电脑端扫码登录，否则微信公众号登录
                //手机端回调地址
                'callback_wx' => "/apiback/type/wx.html",
                //订阅号appid
                'app_id_d' =>  $weixin['appid'],
                //订阅号app_secret
                'app_secret_d' => $weixin['secretID']
                );
                	$this->config=$config;
            break;
        }
    }
	
	public function callback(){
	    $action=input('type');
	    if(empty(input('state')))exit("非法数据");
	    switch($action){
	        case 'qq':
	            $this->getConfig('qq');
	            $login = \wycto\login\Login::getApp($action,$this->config);
                $userinfo = $login->getUserInfo();
                $ok=$this->toLogin($userinfo['openid']);
				if($ok){
				    $user=User::where(['id'=>session('user_auth.user_id')])->find();
				    if($user['status']!=1){
    				    session('user_auth',null);
    				    session('user_auth_sign',null);
    					$this->redirect(url("home/Index/loginErr"));
    				}else{
    				   $this->redirect(url("home/Member/index")); 
    				}
			 	   
				}else{
					session("qq_userinfo", $userinfo);
				   if($this->shenhe || session('?user_auth')){
					   $this->reguser();
					   $this->redirect(url("home/Member/index"));
				   }
				   if(request()->isMobile()){
					   View::assign('title','绑定账号');
					   return view('landedat/wap/callback');
				   }else{
                       return view();
				   }
                }
	       break;
	       case 'wx':
	           $this->getConfig('weixin');
	           $login=\wycto\login\Login::getApp('weixin',$this->config);
	           $user=$login->getUserInfo();
	           $ok=$this->toLogin(isset($user['unionid'])?$user['unionid']:$user['openid']);
			   if(!$ok){
			       $id=build_order_no();
			       session("wxbind",$user);
			      // $this->redirect(url("home/Login/wxbindlogin"));
			   }
	       break;
	    }
	}
	private function wxuser($user){
	        $map['shopid'] = User::order('id desc')->value('shopid')+1;
    		$map['username']=filterEmoji($user['nickname']);
    		$map['password']=$user['openid'];
    		$map['wxopenidg']=$user['openid'];
    		$map['unionid']=isset($user['unionid'])?$user['unionid']:'';
    		$map['assets']=cookie('referee');
    		(new User)->save($map);
    		session("wxbind",null);
    		$this->toLogin($map['wxopenidg']);
    		insert_user_log(1,'登录成功');
	}
	
	private function reguser(){
	    if(session("?user_auth")){
	        User::where(['id'=>session('user_auth.user_id')])->update(['qqopenid'=>session('qq_userinfo.openid')]);
	    }else{
	        $map['shopid'] = User::order('id desc')->value('shopid')+1;
    		$map['username']=session('qq_userinfo.nickname');
    		$map['password']=session('qq_userinfo.openid');
    		$map['qqopenid']=session('qq_userinfo.openid');
    		$map['assets']=cookie('referee');
    		(new User)->save($map);
	    }
		$this->toLogin(session('qq_userinfo.openid'));
		
	}
	
	public function qqtologin(){
		if ($this->request->isPost()){
			if(session("?qq_userinfo")){
				$this->reguser();
				$ms=["content"=>"登录处理中，请稍等......","confirm"=>["name"=>"登录成功","prompt"=>"success","width"=>350,"time"=>1,"callback"=>"","url"=>"/user_index.html"]];
                return json($ms);
			}elseif(session("?wxbind")){
			    $this->wxuser(session("wxbind"));
			    $ms=["content"=>"登录处理中，请稍等......","confirm"=>["name"=>"登录成功","prompt"=>"success","width"=>350,"time"=>1,"callback"=>"","url"=>"/user_index.html"]];
                return json($ms);
			}else{
				return json(["tip"=>"#qqlogin","content"=>"非法提交重新登陆！"]);
			}
		}
	}
	
	private function toLogin($openid){
	    if(!empty($openid)){
    		$user = User::where(['qqopenid|unionid|wxopenidg' => $openid])->find();
    		if ($user){
    			$token=makeToken();
    			$user->token=$token;
    			$user->timeout=strtotime("+1 days");
    			$user->last_login_ip=$this->request->ip();
    			$auth = [
    				'user_id' => $user['id'],
    				'shop_id' => $user['shopid'],
    				'mobile' => $user['mobile'],
    				'token'=>$token
    			];
    			$user->save();
    			session('user_auth', $auth);
    			session('user_auth_sign', data_auth_sign($auth));
    			insert_user_log(1,'登录成功');
    			return true;
    		}else{
    			return false;
    		}
	    }else{
	        return false;
	    }
	}
}
