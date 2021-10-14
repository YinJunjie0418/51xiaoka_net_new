<?php
declare (strict_types = 1);
namespace app\home\controller;

use think\Request;
use app\common\model\Article;
use think\facade\View;
use think\facade\Db;
use app\common\controller\IndexBase;
use app\common\model\User;
use app\common\model\CardList;
use app\common\model\Opinion;
use app\common\model\Category;


class Index extends IndexBase
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
       
        
		$news=Article::where(['status'=>1,'cid'=>12])->order("id desc")->find();
		View::assign("new",$news);
		if(request()->isMobile()){
			return view('index/wap/index');
		}else{
			return view();
		}
    }




	
	public function feilv(){
		$list=(new CardList)->order('id desc')->paginate(20,false,['query' => request()->param()]);;
		foreach($list as $k=>$v){
			$list[$k]['feilv']=hasTone($v['tid'],$v['type'],false);
		}
		$res=Db::name("Sysconfig")->select();
		$lists=[];
		foreach($res as $k=>$v){
			$lists[$v['name']]=$v['value'];
		}
		$lists['title']='平台费率明细';
		View::assign('res',$lists);
		View::assign("list",$list);
		return view();
	}
	
	public function cooperation(){
		if(request()->isMobile()){
			return view('index/wap/cooperation',['title'=>'招商合作']);
		}
	}
	public function category(){
		if(request()->isMobile()){
			return view('index/wap/category');
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
			    if(session("?wxbind")){
			        echo 5;
			    }else{
				  echo !empty($this->user['username'])?$this->user['username']:$this->user['mobile'];
			    }
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
