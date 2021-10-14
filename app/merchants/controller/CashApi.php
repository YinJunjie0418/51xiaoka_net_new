<?php
namespace app\merchants\controller;

use app\common\controller\UserBase;
use app\common\model\CashOrder;
use app\common\model\SellList;
use app\common\model\SellUser;
use app\common\model\User;
use think\facade\View;


class CashApi extends UserBase{

    public function index($limit=15){
        $list=SellUser::with('prolie')->where(['uid'=>session("user_auth.user_id")])->paginate($limit);
        foreach ($list as $k=>$v){
            $list[$k]['title']=$v['title']."【".$v['mianzhi']."】";
            $list[$k]['daima']=$v['geway'];
        }
        View::assign('data',$list);
        return view();
    }

    public function geteditip(){
        if ($this->request->isPost()){
            $da=input('ip');
            if($da){
                $user=User::find(session('user_auth.user_id'));
                $user->ip=$da;
                $ok=$user->save();
                if($ok){
                    return json(['confirm'=>['name'=> "修改成功", 'width'=>400, 'prompt'=> "success",'time'=>1,'url'=>'reload'],'content'=>'设置成功！']);
                }else{
                    return json(["tip"=>"#ip","content"=>"保存失败"]);
                }
            }else{
                return json(["tip"=>"#ip","content"=>"请输入IP"]);
            }

        }
        return view('actmi');
    }

    public function setStatus(){
        if($this->request->isPost()){
            $da=input();
            if(SellUser::find($da['id'])['status']==2)return json(['code'=>-1,'msg'=>tips('接口禁用中...')]);
            SellUser::update($da);
            return json(['code'=>1,'msg'=>'操作成功']);
        }
    }

    public function consign(){
        $da=input();
        $map=[date('Y-m-d H:i',strtotime("-1hour")), date('Y-m-d 23:59:59')];
        if(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && !empty($da['endtime'])){
            $map=[$da['starttime'], $da['endtime']];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && empty($da['starttime']) && !empty($da['endtime'])){
            $map=[$map[0], $da['endtime']];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && empty($da['endtime'])){
            $map=[$da['starttime'], date("Y-m-d  H:i:s")];
        }elseif(isset($da['day']) && !empty($da['day'])){
            $starttime=date('Y-m-d 23:59:59',strtotime("-{$da['day']}day"));
            $map=[$starttime, date('Y-m-d 23:59:59')];
        }
        $where[]=['uid','=',session('user_auth.user_id')];
        if(isset($da['rekey']) && !empty($da['rekey'])){
            $where[]=[$da['setype'],'=',$da['rekey']];
        }
        if(isset($da['cardype']) && !empty($da['cardype'])){
            $where[]=['type','=',$da['cardype']];
        }
        if(isset($da['status']) && !empty($da['status'])){
            if($da['status']==4){
                $where[]=['state','in',[3,5]];
            }else{
                $where[]=['state','=',$da['status']-1];
            }
        }
        $list = CashOrder::with('bindmsg')->where($where)->whereTime('create_time', 'between', $map)->order('id desc')->paginate(15, $this->pagingState, ['query' => request()->param()]);
        foreach($list as $k=>$v){
            $list[$k]['title']=$v['title']."【".$v['mianzhi']."】";
            if($v->getData('state')>2){
                $miao=empty($v->getData('update_time'))?'--':(int)$v->getData('update_time')-(int)$v->getData('create_time');
                $list[$k]['haos']=feng($miao);
            }else{
                 $list[$k]['haos']="--";
            }
           
        }
        View::assign("list",$list);
        View::assign('setype',input('setype'));
        View::assign('cardype',input('cardype'));
        View::assign('status',input('status'));
        View::assign('clist',SellList::select());
        View::assign("day",isset($da['day'])?$da['day']:0);
        View::assign("starttime",$map[0]);
        View::assign("endtime",$map[1]);
        if(request()->isMobile()){
            View::assign("empty",'<div class="messager messager-empty"><div class="messager-icon"><i class="iconfont iconfont-empty"></i></div><div class="messager-text"><h2 class="messager-title">暂无记录</h2></div></div>');
            return view('cash_api/wap/consign',['title'=>'充值记录']);
        }else{
            $em='<div class="box"><div class="nodata"><i class="iconfont icon-nodata"></i><br>抱歉，没有找到任何相关的数据！</div></div>';
            View::assign("empty",$em);
            return view();
        }
    }
    public function statistics(){
        $da=input();
        $map=[date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')];
        if(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && !empty($da['endtime'])){
            $map=[$da['starttime'], $da['endtime']];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && empty($da['starttime']) && !empty($da['endtime'])){
            $map=[$map[0], $da['endtime']];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && empty($da['endtime'])){
            $map=[$da['starttime'], date("Y-m-d  H:i:s")];
        }elseif(isset($da['day']) && !empty($da['day'])){
            $starttime=date('Y-m-d 23:59:59',strtotime("-{$da['day']}day"));
            $map=[$starttime, date('Y-m-d 23:59:59')];
        }
        $where['uid']=session('user_auth.user_id');
        $list=CashOrder::field("FROM_UNIXTIME(create_time, '%Y-%m-%d') as datetime,count(id) as id,sum(money) as summoney,sum(state=2) as okcount,sum(if(state=2,money,0)) as okmian,sum(if(state=2,price,0)) as okmoney,sum(state=3) as failcount,sum(if(state=3,money,0)) as failmian,sum(state=0 || state=1) as loadcount,sum(if(state=0 || state=1,money,0)) as loadmian")
            ->where($where)->whereTime('create_time','between',$map)
            ->group('FROM_UNIXTIME(create_time,"%Y-%m-%d")')
            ->paginate(15,$this->pagingState,['query' => request()->param()]);
        View::assign('cardype',input('cardype'));
        View::assign("list",$list);
        View::assign("day",isset($da['day'])?$da['day']:0);
        View::assign("starttime",$map[0]);
        View::assign("endtime",$map[1]);
        if(request()->isMobile()){
            View::assign("empty",'<div class="messager messager-empty"><div class="messager-icon"><i class="iconfont iconfont-empty"></i></div><div class="messager-text"><h2 class="messager-title">暂无记录</h2></div></div>');
            return view('cash_api/wap/statistics',['title'=>'充值统计']);
        }else{
            $em='<div class="box"><div class="nodata"><i class="iconfont icon-nodata"></i><br>抱歉，没有找到任何相关的数据！</div></div>';
            View::assign("empty",$em);
            return view();
        }
    }

    public function export()
    {
        $da=input();
        $map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
        if(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && !empty($da['endtime'])){
            $map=[$da['starttime'], $da['endtime']];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && empty($da['starttime']) && !empty($da['endtime'])){
            $map=["2020-1-1 00:00:00", $da['endtime']];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && empty($da['endtime'])){
            $map=[$da['starttime'], date("Y-m-d  H:i:s")];
        }elseif(isset($da['day']) && !empty($da['day'])){
            $starttime=date('Y-m-d',strtotime("-{$da['day']}day"));
            $map=[$starttime, date('Y-m-d 23:59:59')];
        }
        $where['uid']=session('user_auth.user_id');
        if(isset($da['rekey']) && !empty($da['rekey'])){
            $where[$da['setype']]=$da['rekey'];
        }
        if(isset($da['cardype']) && !empty($da['cardype'])){
            $where['type']=$da['cardype'];
        }
        if(isset($da['status']) && !empty($da['status'])){
            $where['state']=$da['status']-1;
        }
        $listtotal=CashOrder::where($where)->whereTime('create_time', 'between',$map)->count();
		if($listtotal==0)exit("记录为空");
        $title=['系统订单号','商户订单号','产品类型', '充值号码', '充值金额','状态','备注','提交时间','处理时间'];
        articleAccessLog($title,"导出充值记录".date('YmdHis'),$listtotal,$this,$where,$map);
    }
	
	public function getArticleAccessLog($where,$map,$page,$limit)
    {
        $list=CashOrder::with('bindmsg')->where($where)->whereTime('create_time', 'between',$map)->order('id desc')->page($page,$limit)->select();
        $map=[];
        foreach($list as $k=>$v){
            $map[$k]['orderno']=$v['orderno']."\t";;
            $map[$k]['batchno']=$v['tmporder']."\t";
            $map[$k]['title']=$v['title']."【".$v['mianzhi']."】";
            $map[$k]['number']=$v['number']."\t";
            $map[$k]['money']=$v['money'];
            $map[$k]['state']=orderTyped($v['state']);
            $map[$k]['remarks']=$v['remarks']?$v['remarks']:'--';
            $map[$k]['create_time']=$v['create_time'];
            $map[$k]['update_time']=$v['update_time'];
        }
        return $map;
    }
    
    public function sellpay(){
        $model=SellList::field('title,id,iconurl')->where(['status'=>1])->group('title')->select();
        View::assign("model",$model);
        View::assign('title',$model[0]['title']);
        return view();
    }
    
    public function sellmianzhi(){
        $title=input('title');
        $model=SellList::field('mianzhi,id,rate')->where(['status'=>1,'title'=>$title])->select();
        foreach($model as $k=>$v){
            $model[$k]['rate']=SellUser::where(['uid'=>session('user_auth.user_id'),'sellid'=>$v['id']])->value('rate');
        }
        $this->result($model);
    }
    
    public function loadmoney(){
        $money=(new \app\common\model\CashOrder)::where('state','in','0,1,4,6')->where('uid',session('user_auth.user_id'))->sum('price');
        Cache(session('user_auth.user_id')."money",$money);
        return json(["state"=>1,"type"=>2,"content"=>"","list"=>[],"confirm"=>["name"=>"刷新占用金额成功","prompt"=>"success","width"=>400,"time"=>5,"url"=>"reload"]]);
    }
}
