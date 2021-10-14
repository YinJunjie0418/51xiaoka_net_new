<?php
namespace app\home\controller;

use app\common\controller\IndexBase;
use think\facade\View;
use think\facade\Request;
use think\facade\Session;
use app\common\wxfaceld\pcticket;

use app\common\model\User;

class Login extends IndexBase
{
    //登陆
    public function login()
    {
        if ($this->request->isPost()){
            $param = $this->request->param();
			try{
				$scene=$param['type']==0?'login.name':'login.code';
				$this->validate($param, $scene);
            }catch (\Exception $e){
				if(isJsonBool($e->getMessage(),true)!==false){
				   return json(isJsonBool($e->getMessage()));
				}else{
					return json(['id'=>"#sign-error", 'content'=>$e->getMessage(),'token'=>token()]);
				}
             }
             insert_user_log(1,'登录成功');
			 $ms=["content"=>"登录处理中，请稍等......","confirm"=>["name"=>"登录成功","prompt"=>"success","width"=>350,"time"=>1,"callback"=>"","url"=>"/user_index.html"]];
             return json($ms);
	    }
		is_user_login() && $this->redirect(url('home/Member/index')); // 登录直接跳转
		if(in_wechat() && $this->iswx){
            $this->redirect(url('home/Gongzhaohao/index'));
        } 
		if(request()->isMobile()){
			return view('login/wap/index',['title'=>"用户登陆"]);
		}else{
			return view('login',['title'=>"用户登陆"]);
		}
        
    }
    
      //微信绑定登陆
    public function wxbindlogin()
    {
        if ($this->request->isPost()){
            $param = $this->request->param();
			try{
				$scene=$param['type']==0?'login.name':'login.code';
				$this->validate($param, $scene);
            }catch (\Exception $e){
				if(isJsonBool($e->getMessage(),true)!==false){
				   return json(isJsonBool($e->getMessage()));
				}else{
					return json(['id'=>"#sign-error", 'content'=>$e->getMessage(),'token'=>token()]);
				}
             }
             insert_user_log(1,'登录成功');
			 $ms=["content"=>"登录处理中，请稍等......","confirm"=>["name"=>"登录成功","prompt"=>"success","width"=>350,"time"=>1,"callback"=>"","url"=>"/user_index.html"]];
             return json($ms);
	    }
			return view('wxbindlogin',['title'=>"绑定账户",'id'=>input('id')]);
    }
	
	public function forgetpassword(){
		if ($this->request->isPost()) {
			$param = $this->request->param();
			try{
				$this->validate($param, "forgetpassword");
            }catch (\Exception $e){
				$str=$e->getMessage();
				$res=getArr($str);
				return json(["tip"=>$res[0],"content"=>$res[1],'token'=>token()]);
            }
			$pass=generate_password(4);
			$user=User::where(['username|mobile|email'=>$param['username']])->find();
			$isupdate=false;
			$isfa=false;
			if($user){
				if(!empty($user['email'])){
					cookie("name",$user['email'],10);
					$res=sendEmail($user['email'],$pass,"您正在进行邮箱操作，您的验证码为","您正在进行密码重置操作，您的新密码为");
					if($res['code']==1){
						$isupdate=true;
						$isfa=true;
						User::where(['id'=>$user['id']])->update(['password'=>md6($pass)]);
						return json(['confirm'=>['name'=> "重置密码成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'/forgetpassword/type/email.html'],'content'=>'操作成功....']);
					}
				}
				if(!empty($user['mobile']) && $isfa===false){
					cookie("name",$user['mobile'],10);
					$result=sendMsg($user['mobile'],'findMsg',$pass);
					if($result['code']==1){
						$isupdate=true;
						$isfa=true;
						User::where(['id'=>$user['id']])->update(['password'=>md6($pass)]);
					   return json(['confirm'=>['name'=> "重置密码成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'/forgetpassword/type/photo.html'],'content'=>'操作成功....']);
					}else{
						return json(['confirm'=>['name'=> "重置密码失败！", 'width'=>400, 'prompt'=> "warning",'time'=>2,'url'=>'reload'],'content'=>'发送邮件和短信均已失败....']);
					}
				}elseif($isupdate===false){
					return json(['confirm'=>['name'=> "重置密码失败！", 'width'=>400, 'prompt'=> "warning",'time'=>2,'url'=>'reload'],'content'=>'发送邮件失败,该账号未绑定手机....']);
				}
			}
		}
		$name=cookie("name");
		View::assign("name",$name);
		View::assign("type",$name?input('type'):"");
		if(request()->isMobile()){
			return view('login/wap/forgetpassword',['title'=>"用户登陆"]);
		}else{
			return view();
		}
      
	}
	
	public function register(){
		cookie('referee',input('tid'));
		is_user_login() && $this->redirect(url('home/Member/index')); // 登录直接跳转
		if($this->isreg=="off")$this->redirect(url("home/Login/errreg"));
		if ($this->request->isPost()) {
		    if($this->isreg=='off'){
		        return json(['confirm'=>['name'=> "注册暂停请谅解！", 'width'=>400, 'prompt'=> "warning",'time'=>2,'url'=>'reload'],'content'=>'本站目前暂未开放注册，感谢关注！']);
		    }
			$param = $this->request->param();
			try{
				$this->validate($param, "regpid");
            }catch (\Exception $e){
				$str=$e->getMessage();
				$res=getArr($str);
				return json(["tip"=>$res[0],"content"=>$res[1],'token'=>token()]);
             }
             if(session("?wxbind")){
                 $map['wxopenidg']=session("wxbind.openid");
        		 $map['unionid']=session("?wxbind.unionid")?session("wxbind.unionid"):'';
             }
			$map['shopid'] = User::order('id desc')->value('shopid')+1;
			
			$map['username']=$param['username'];
			$map['password']=$param['newpsw'];
			$map['mobile']=$param['phoneno'];
			$map['qq']=$param['qq'];
			$map['assets']=cookie('referee');
			(new User)->save($map);
			$ms=['confirm'=>['name'=> "注册账号成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'/login.html'],'content'=>'请登陆....'];
            return json($ms);
		}
		if(request()->isMobile()){
			return view('login/wap/signup',['title'=>"用户登陆"]);
		}else{
			return view('signup');
		}
        
		
	}
	
	public function errreg(){
	    if(request()->isMobile()){
			    return view('login/wap/loginerr',['title'=>'注册暂停']);
		}else{
		   return view('loginerr');
		}
	}
	public function kuanglogin(){
	   
		if(request()->isMobile()){
		    if(in_wechat()){
		        return json(['url'=>url('home/Gongzhaohao/index')->build()]);
		    }else{
			    return view('login/wap/kuanglogin');
		    }
		}else{
		   return view();
		}
	}
	
    // 退出登录
    public function logout()
    {
        insert_user_log(2,'退出了系统');
        session('user_auth', null);
        session('user_auth_sign', null);
		if(request()->isMobile()){
			return view('login/wap/logout',['title'=>"退出登陆"]);
		}else{
           return view();
		}
    }
    
    public function faceback(){
        $da=input();
        logt(json_encode($da));
        $ok=false;
        if(isset($da['orderNo'])){
            $ok=(new pcticket())->replaceBack($da);
        }
        View::assign("ok",$ok?1:0);
        View::assign('title',"认证返回");
        return view('login/wap/faceback');
    }
}
