<?php
namespace app\admin\controller;
use app\common\controller\AdminBase;
use think\facade\View;
use think\facade\Request;
use think\facade\Db;
use app\common\model\Backdb;
use app\common\model\Config;

class Upsystem extends AdminBase
{
	protected $url="https://www.368ys.cn/";
	
    protected function _initialize()
    {
        parent::_initialize();
    }
    
    //广告首页
    public function index()
    {
        if($this->request->isAjax()){
    		$res=vpost($this->url .'edition.html');
			
    		$res=json_decode($res,true);
    		if($res['data']){
             	foreach($res['data'] as $k=>$v){
             	    $ok=Config::where(['value'=>$v['version']])->find();
             	    if($ok){
             	        $res['data'][$k]['state']=1;
						if(isset($ok['sqlok']) && $ok['sqlok']==1){
						  $res['data'][$k]['sqlok']=2;
						}
             	    }else{
             	        $res['data'][$k]['state']=0;
             	    }
             	}
				return json($res);
    		}
    		
        }
        return $this->fetch('index');
    }
	
	public function upsql(){
		if($this->request->isAjax()){
			$id=input('id');
    		$res=vpost($this->url .'edition.html',['id'=>$id]);
    		$res=json_decode($res,true);
			$ss=true;
			$msg='数据库更新成功';
    		if($res['data']){
				$str=explode(";",$res['data']);
             	foreach($str as $k=>$v){
					if(empty($v))continue;
					try{
				    	$ok=Db::execute($v);
					}catch (\Exception $e){

                    }
				}
    		}
    		return json(['code'=>$ss,'msg'=>$msg]);
        }
	}
   
	
	public function plugunit(){
	    if($this->request->isAjax()){
    		$res=vpost($this->url .'editionapi.html');
    		$res=json_decode($res,true);
    		if($res['data']){
             	foreach($res['data'] as $k=>$v){
             	    $ok=Config::where(['value'=>$v['version']])->find();
             	    if($ok){
             	        $res['data'][$k]['state']=1;
             	    }else{
             	        $res['data'][$k]['state']=0;
             	    }
             	}
				
    		}
			return json($res);
        }
		return view();
	}
	
	public function sqlall(){
		if($this->request->isAjax()){
			$list=(new Backdb)->dataList();
			foreach($list as $k=>$v){
				$list[$k]['data_length']=format_bytes($v['data_length']);
			}
			return $this->result($list);
		}
		return view();
	}
	public function import(){
		 if(request()->isPost()){
			$name=input('time');
			  $back=new Backdb([],['name'=>date('Ymd-His',$name),'part'=>1]);
			  $res=$back->import(0);
			  $this->success('备份成功');
		  }
		$list=(new Backdb)->fileList();
		View::assign('list',$list);
		return view();
	}
	
	public function backup(){//备份
	  if(request()->isPost()){
		  $res=(new Backdb)->backall();
		  $this->success('备份成功');
	  }
	}
	public function optimize(){
		if(request()->isPost()){
			$tab=input('table');
		  $res=(new Backdb)->optimize($tab);
		  if($res){
		    $this->success('优化成功');
		  }else{
			  $this->error($res);
		  }
	  }
	}
	
	public function download(){
		$da=input('time');
		(new Backdb)->downloadFile($da);
	}
	
	public function del(){
		if(request()->isPost()){
			$tab=input('time');
		    $res=(new Backdb)->delFile($tab);
		  if($res){
		    $this->success('删除成功');
		  }else{
			  $this->error($res);
		  }
	  }
		
	}
	
	public function repair(){
		if(request()->isPost()){
			$tab=input('table');
		  $res=(new Backdb)->repair($tab);
		  if($res){
		    $this->success('修复成功');
		  }else{
			  $this->error($res);
		  }
	  }
	}
    
}
