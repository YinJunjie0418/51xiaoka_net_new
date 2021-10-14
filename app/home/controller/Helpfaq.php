<?php
declare (strict_types = 1);

namespace app\home\controller;
use think\facade\View;
use app\common\controller\IndexBase;
use app\common\model\Article;
use app\common\model\Category;
use app\common\model\CardList;

use think\Request;

class Helpfaq extends IndexBase
{
	public function initialize()
    {
        parent::initialize();
		 $rt=Category::field("id")->where('pid','>',0)->select();
		 $map=[];
		 foreach($rt as $k=>$v){
			 $map[$k]=Article::field("id,title,url")->where(['cid'=>$v['id'],'status'=>1,'is_hot'=>1])->select()->toArray();
		 }
		 View::assign("fg",$map);
		 View::assign("id",input('id')?:0);
    }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
		$res=Category::find(12);
		$list=Article::where(['cid'=>12,'status'=>1])->order("update_time desc")->select();
		View::assign("res",$res);
		View::assign("list",$list);
		if(request()->isMobile()){
			return view('helpfaq/wap/index');
		}else{
			return view();
		}
    }
	
	public function zixun(){
		$res=Category::find(24);
		$list=Article::where(['cid'=>24,'status'=>1])->order("update_time desc")->paginate(15,false,['query' => request()->param()]);
		View::assign("res",$res);
		View::assign("list",$list);
		View::assign("id",1);
		return view();
	}
	 public function business()
    {
        return view();
    }
	
	public function helpa(){
		if(request()->isMobile()){
			return view('helpfaq/wap/helpa',['title'=>'帮助中心']);
		}
	}
	public function guide(){
		if(request()->isMobile()){
			return view('helpfaq/wap/guide',['title'=>'回收流程']);
		}
	}
	public function aboutus(){
		if(request()->isMobile()){
			return view('helpfaq/wap/aboutus',['title'=>'关于我们']);
		}
	}
	public function agreement(){
		if(request()->isMobile()){
			return view('helpfaq/wap/agreement',['title'=>'用户服务协议','content'=>Article::find(37)['content']]);
		}
	}
	public function faq(){
		if(request()->isMobile()){
			return view('helpfaq/wap/faq',['title'=>'常见问题']);
		}
	}
	public function privacy(){
		if(request()->isMobile()){
			return view('helpfaq/wap/privacy',['title'=>'隐私政策','content'=>Article::find(38)['content']]);
		}
	}
	public function protocol(){
		if(request()->isMobile()){
			return view('helpfaq/wap/protocol',['title'=>'转让协议']);
		}
	}
	public function adeclare(){
		if(request()->isMobile()){
			return view('helpfaq/wap/adeclare',['title'=>'回收说明']);
		}
	}
	public function api(){
		if(request()->isMobile()){
			return view('helpfaq/wap/api',['title'=>'API接口']);
		}
	}

	public function danye(){
		$id=input("id");
		$res=Article::find($id);
		$resa=str_replace("&nbsp;"," ",$res['content']);
		$resb=str_replace('<pre class="brush:html;toolbar:false">'," ",$resa);
		$resc=str_replace('</pre>'," ",$resb);
		$res['content']=$resc;

        $list=Article::field('id,title,update_time')->where(['cid'=>24,'status'=>1])->limit(15)->order("update_time desc")->select();
		View::assign("res",$res);
		View::assign("list",$list);
		View::assign("preda",Article::field('id,title,update_time')->where(['cid'=>24,'status'=>1])->where('id','>',$id)->order("update_time asc")->find());
        View::assign("nextda",Article::field('id,title,update_time')->where(['cid'=>24,'status'=>1])->where('id','<',$id)->order("update_time desc")->find());
		return view("index/".str_replace(".html","",$res['template']));
	}
}
