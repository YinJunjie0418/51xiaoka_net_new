<?php
declare (strict_types = 1);

namespace app\home\controller;

use think\facade\Cache;
use think\facade\View;
use think\facade\Config as Con;
use app\common\controller\UserBase;
use app\common\model\Withdraw;
use app\common\model\User;
use app\common\model\Userbank;

class Cash extends UserBase
{
	private $pei;
	private $umoney;
	
	public function initialize()
     {
        parent::initialize();
		$this->pei=Con::load('setting/cash','cash');
		$money=getsale($this->user['yuti']);
		$this->umoney=$this->user['money'];
		if($this->user['money']+$money<0){
			$this->user['money']=0;
		}else{	
		  $this->user['money']=$this->user['money']+$money;
		}
		View::assign('user',$this->user);
		View::assign("pei",$this->pei);
	 }
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        if($this->pei['alitype']!=1)$this->redirect(url('home/Cash/bank'));
		View::assign("list",Userbank::where(['bankid'=>-1])->where(['uid'=>$this->user['id']])->select());
		View::assign("ok",Userbank::where(['bankid'=>-1])->where(['uid'=>$this->user['id']])->count());
		if(request()->isMobile()){
			return view('cash/wap/index',['title'=>'提现中心']);
		}else{
          return view();
		}
    }
	
	public function bank()
    {
		View::assign("list",Userbank::with('img')->where('bankid','>',0)->where(['uid'=>$this->user['id']])->select());
		View::assign("ok",Userbank::where('bankid','>',0)->where(['uid'=>$this->user['id']])->count());
        if(request()->isMobile()){
			return view('cash/wap/bank',['title'=>'提现中心']);
		}else{
          return view();
		}
    }
	
	public function weixin()
    {
        if($this->pei['wxtype']!=1)$this->redirect(url('home/Cash/bank'));
		View::assign("list",Userbank::where('bankid','=',-2)->where(['uid'=>$this->user['id']])->select());
		View::assign("ok",Userbank::where('bankid','=',-2)->where(['uid'=>$this->user['id']])->count());
        if(request()->isMobile()){
			return view('cash/wap/weixin',['title'=>'提现中心']);
		}else{
          return view();
		}
    }
	
	public function withdraw(){
		if ($this->request->isAjax()){
			$data=input();
			try{
				$this->validate($data, 'cash');
            }catch (\Exception $e){
                $str=$e->getMessage();
                $res=getArr($str);
                if(!isset($res[1])){
                    return json(['tip'=>'#anjian','content'=>'未知错误','token'=>token()]);
                }else{
                    return json(["tip"=>$res[0],"content"=>$res[1],'token'=>token()]);
                }
            }
            if(empty($this->user['userReal']['evidenceHash'])){
               // return json(['tip'=>'#anjian','content'=>'请签署协议后再操作','token'=>token()]);
            }
			$orderid=build_order_no();
			$map['order']=$orderid;
			$map['uid']=session('user_auth.user_id');
			$map['money']=$data['moneyoff'];
			$map['cid']=$data['bank_id'];
			$map['type']=$data['type']=='bank'?'banktype':($data['type']=='alipay'?'alitype':'wxtype');
			$map['price']=charges($data['moneyoff'],$data['type']);
			$map['umoney']=$this->umoney-$data['moneyoff'];
			$map['status']=0;
            $decMoneyIs=(new User)->setMoney(session('user_auth.user_id'),$data['moneyoff'],$orderid,1,$map['price']);
            if(!$decMoneyIs)return json(["tip"=>"#anjian","content"=>"提现失败，请稍后重试1",'token'=>token()]);
            $ok=(new Withdraw)->save($map);
            if($ok && $decMoneyIs){
                if($data['type']=='alipay' && $this->pei['alish']==0){
                       \think\facade\Queue::push("app\home\job\Jobone@tixian", ['orderid'=>$orderid],'Jobtixian');
                    }elseif($map['type']=='wxtype' && $this->pei['wxsh']==0){
                        \think\facade\Queue::push("app\home\job\Jobone@tixian", ['orderid'=>$orderid],'Jobtixian');
                    }elseif(isset($this->pei['banksh']) && $map['type']=="banktype" && $this->pei['banksh']==0){
                        \think\facade\Queue::push("app\home\job\Jobone@tixian", ['orderid'=>$orderid],'Jobtixian');
                    }else{
                        (new Withdraw)->where(['order'=>$orderid])->update(['status'=>1]);
                    }
                    Cache::inc('cash');
                    return json(['confirm'=>['name'=> "提现申请成功！", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'/act_cashrecords.html'],'content'=>'操作成功....']);
                }else{
                    return json(["tip"=>"#anjian","content"=>"提现失败，请稍后重试",'token'=>token()]);
                }
		}
	}
	   
	public function cashrecords(){
		$da=input();
		$map=["2020-1-1", date('Y-m-d').' 23:59:59'];
		if(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && !empty($da['endtime'])){
			$map=[$da['starttime']." 00:00:00", $da['endtime']." 23:59:59"];
		}elseif(isset($da['day']) && !empty($da['day'])){
            $starttime=date('Y-m-d',strtotime("-{$da['day']}day"));
            $map=[$starttime.' 00:00:00', date('Y-m-d 23:59:59')];
		}
		$list=Withdraw::with('preone')->where(['uid'=>session('user_auth.user_id')])->whereTime('create_time', 'between',$map)->order('id desc')->paginate(10,$this->pagingState,['query' => request()->param()]);
		$money=0;$success=0;$fail=0;
		foreach($list as $k=>$v){
			$list[$k]['type']=$v['type']=='alitype'?"支付宝提现":($v['type']=='wxtype'?"微信提现":"银行卡提现");
			if($v->getData('status')<2)$money+=$v['money'];
			if($v->getData('status')==2)$success+=$v['money'];
			if($v->getData('status')==3)$fail+=$v['money'];
			$list[$k]['status']=txType($v['status']);
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
}
