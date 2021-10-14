<?php
declare (strict_types = 1);

namespace app\merchants\controller;


use think\facade\Cache;
use think\facade\View;
use app\common\controller\UserBase;
use app\common\model\Payment;
use app\common\model\Recharge;


class Cash extends UserBase
{

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if ($this->request->isAjax()){
            $id=input('id');
            $list=Payment::where('id',$id)->find();
            return json($list);
        }
        $banklist=Payment::where(['state'=>1])->select()->toArray();
		View::assign("list",$banklist);
		if(request()->isMobile()){
			return view('cash/wap/index',['title'=>'加款中心']);
		}else{
          return view();
		}

    }

	public function withdraw(){
		if ($this->request->isAjax()){
			$data=input();
			try{
				$this->validate($data, 'recharge');
            }catch (\Exception $e){
				$str=$e->getMessage();
				$res=getArr($str);
				return json(["tip"=>$res[0],"content"=>$res[1],'token'=>token()]);
            }
			$orderid=build_order_no("R");
			$map['order']=$orderid;
			$map['uid']=session('user_auth.user_id');
			$map['money']=$data['moneyoff'];
			$map['cid']=$data['bankid'];
			$map['content']=$data['rechpic'];
			$map['umoney']=$this->user['money'];
    		$ok=(new Recharge)->save($map);
    		if($ok){
                Cache::inc('recharge');
                return json(['confirm'=>['name'=> "加款申请成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>url('merchants/Cash/cashrecords')->build()],'content'=>'操作成功....']);
            }else{
                return json(["tip"=>"#anjian","content"=>"加款失败，请稍后重试",'token'=>token()]);
            }
		}
	}
	   
	public function cashrecords(){
		$da=input();
		$map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
		if(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && !empty($da['endtime'])){
			$map=[$da['starttime']." 00:00:00", $da['endtime']." 23:59:59"];
		}elseif(isset($da['day']) && !empty($da['day'])){
			$starttime=date('Y-m-d',strtotime("-".$da['day']."day"));
			$map=[$starttime." 00:00:00", date('Y-m-d 23:59:59')];
		}
		$list=Recharge::with('preone')->where(['uid'=>session('user_auth.user_id')])->whereTime('create_time', 'between',$map)->order('id desc')->paginate(10,$this->pagingState,['query' => request()->param()]);
		$money=0;$success=0;$fail=0;
		foreach($list as $k=>$v){
			if($v->getData('status')==0)$money+=$v['money'];
			if($v->getData('status')==1)$success+=$v['money'];
			if($v->getData('status')==2)$fail+=$v['money'];
			$list[$k]['status']=caType($v['status']);
			$list[$k]['content']=str_replace("\\","/",$v['content']);
		}
		View::assign("money",$money);
		View::assign("success",$success);
		View::assign("fail",$fail);
		View::assign("list",$list);
		View::assign("day",isset($da['day'])?$da['day']:0);
		View::assign("starttime",isset($da['starttime'])?$da['starttime']:'');
		View::assign("endtime",isset($da['endtime'])?$da['endtime']:'');
		if(request()->isMobile()){
			View::assign("empty",'<div class="messager messager-empty"><div class="messager-icon"><i class="iconfont iconfont-empty"></i></div><div class="messager-text"><h2 class="messager-title">暂无提现记录</h2></div></div>');
			return view('cash/wap/cashrecords',['title'=>'提现记录']);
		}else{
            $em='<div class="box"><div class="nodata"><i class="iconfont icon-nodata"></i><br>抱歉，没有找到任何相关的数据！</div></div>';
            View::assign("empty",$em);
		  return view();
		}
	}

	public function lookpic(){
        $url=input("url");
        View::assign("url",$url);
        return view();
    }
}
