<?php
declare (strict_types = 1);
namespace app\merchants\controller;

use think\Request;
use app\common\model\Article;
use think\facade\View;
use think\facade\Db;
use app\common\controller\IndexBase;
use app\common\model\User;
use app\common\model\CardList;
use app\common\model\Opinion;
use app\common\model\Category;
use app\common\model\Order;
use app\common\model\Withdraw;


class Index extends IndexBase
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
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
        is_user_login() && $this->redirect(url('merchants/Member/index')); // 登录直接跳转
        if(in_wechat() && $this->iswx){
            $this->redirect(url('merchants/Gongzhaohao/index'));
        }

        if(request()->isMobile()){
            return view('login/wap/index',['title'=>"用户登陆"]);
        }else{
            return view('login',['title'=>"用户登陆"]);
        }
    }




	

	
	public function feedback(){
		if (request()->isPost()) {
			$param=input();
			try{
				$this->validate($param, "opinion");
            }catch (\Exception $e){
				$str=$e->getMessage();
				$res=getArr($str);
				return json(["tip"=>$res[0],"content"=>$res[1],'token'=>token()]);
            }
			if(session("?user_auth"))$param['shopid']=session("user_auth.shop_id");
			Opinion::create($param);
			return json(['confirm'=>['name'=> "提交意见成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'/'],'content'=>'操作成功....']);
		}
		$list=CardList::select();
		$res=Db::name("Sysconfig")->select();
		$lists=[];
		foreach($res as $k=>$v){
			$lists[$v['name']]=$v['value'];
		}
		$lists['title']='意见建议反馈';
		View::assign('res',$lists);
		$rt=Category::field("id")->where('pid','>',0)->select();
		 $map=[];
		 foreach($rt as $k=>$v){
			 $map[$k]=Article::field("id,title,url")->where(['cid'=>$v['id'],'status'=>1,'is_hot'=>1])->select()->toArray();
		 }
		 View::assign("fg",$map);
		View::assign('list',$list);
		View::assign('res',$lists);
		View::assign('id','');
		if(request()->isMobile()){
			return view('index/wap/feedback',['title'=>'企业合作']);
		}else{
			return view();
		}
	}
	
	public function company(){
		if(request()->isMobile()){
			return view('index/wap/company',['title'=>'企业合作']);
		}
	}
	
	public function getName(){
		$type=input('type');
		if($type!='weixin' && $type){
			echo $this->user['shopid'];
		}elseif($type && $type=='weixin'){
			$user=User::where(['id'=>session('user_auth.user_id')])->find();
			if($user['status']!=1 && session("?user_auth")){
				session('user_auth', null);
                session('user_auth_sign', null);
				echo -1;
			}else{
				echo !empty($this->user['username'])?$this->user['username']:$this->user['mobile'];
			}
		}else{
		    echo !empty($this->user['username'])?$this->user['username']:$this->user['mobile'];
		}
	}
	
	public function accets(){
		return view();
	}
		
	public function miss(){
		if(request()->isMobile()){
			return view('index/wap/miss',['title'=>'迷路了']);
		}else{
		  return view();
		}
	}
	
	public function loginErr(){
		if(request()->isMobile()){
			return view('index/wap/loginerr',['title'=>'登陆失败']);
		}else{
		  return view();
		}
	}
	
	public function app(){
		return view();
	}
}
