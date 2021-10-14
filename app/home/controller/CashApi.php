<?php
namespace app\home\controller;

use app\common\controller\UserBase;
use app\common\model\CashOrder;
use app\common\model\SellList;
use app\common\model\SellUser;
use app\common\model\User;
use think\facade\View;


class CashApi extends UserBase{

    public function index($limit=15){
        $list=SellUser::where(['uid'=>session("user_auth.user_id")])->paginate($limit);
        foreach ($list as $k=>$v){
            $res=SellList::where(['id'=>$v['sellid']])->find();
            $list[$k]['title']=$res['title']."【".$res['mianzhi']."】";
            $list[$k]['daima']=$res['geway'];
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
        $map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
        if(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && !empty($da['endtime'])){
            $map=[$da['starttime']." 00:00:00", $da['endtime']." 23:59:59"];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && empty($da['starttime']) && !empty($da['endtime'])){
            $map=["2020-1-1 00:00:00", $da['endtime']." 23:59:59"];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && empty($da['endtime'])){
            $map=[$da['starttime']." 00:00:00", date("Y-m-d  H:i:s")];
        }elseif(isset($da['day']) && !empty($da['day'])){
            $starttime=date('Y-m-d',strtotime("-{$da['day']}day"));
            $map=[$starttime." 23:59:59", date('Y-m-d 23:59:59')];
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

        $list = CashOrder::where($where)->whereTime('create_time', 'between', $map)->order('id desc')->paginate(15, $this->pagingState, ['query' => request()->param()]);
        foreach($list as $k=>$v){
            $res=SellList::where(['geway'=>$v['type']])->find();
            $list[$k]['title']=$res['title']."【".$res['mianzhi']."】";
            $miao=empty($v->getData('update_time'))?'--':$v->getData('update_time')-$v->getData('create_time');
            $list[$k]['haos']=feng($miao);
        }
        View::assign("list",$list);
        View::assign('setype',input('setype'));
        View::assign('cardype',input('cardype'));
        View::assign('status',input('status'));
        View::assign('clist',SellList::select());
        View::assign("day",isset($da['day'])?$da['day']:0);
        View::assign("starttime",isset($da['starttime'])?$da['starttime']:'');
        View::assign("endtime",isset($da['endtime'])?$da['endtime']:'');
        if(request()->isMobile()){
            View::assign("empty",'<div class="messager messager-empty"><div class="messager-icon"><i class="iconfont iconfont-empty"></i></div><div class="messager-text"><h2 class="messager-title">暂无记录</h2></div></div>');
            return view('cash_api/wap/consign',['title'=>'充值记录']);
        }else{
            return view();
        }
    }
    public function statistics(){
        $da=input();
        $map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
        if(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && !empty($da['endtime'])){
            $map=[$da['starttime']." 00:00:00", $da['endtime']." 23:59:59"];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && empty($da['starttime']) && !empty($da['endtime'])){
            $map=["2020-1-1 00:00:00", $da['endtime']." 23:59:59"];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && empty($da['endtime'])){
            $map=[$da['starttime']." 00:00:00", date("Y-m-d  H:i:s")];
        }elseif(isset($da['day']) && !empty($da['day'])){
            $starttime=date('Y-m-d',strtotime("-{$da['day']}day"));
            $map=[$starttime." 23:59:59", date('Y-m-d 23:59:59')];
        }
        $where['uid']=session('user_auth.user_id');
        $list=CashOrder::field("FROM_UNIXTIME(create_time, '%Y-%m-%d') as datetime")->where($where)->whereTime('create_time','between',$map)->group('FROM_UNIXTIME(create_time,"%Y-%m-%d")')->paginate(15,$this->pagingState,['query' => request()->param()]);
        foreach($list as $k=>$v){
            $list[$k]['id']=CashOrder::where('uid','=',session('user_auth.user_id'))->whereDay('create_time',$v['datetime'])->count();
            $list[$k]['summoney']=CashOrder::where('uid','=',session('user_auth.user_id'))->whereDay('create_time',$v['datetime'])->sum("money");
            $list[$k]['okcount']=CashOrder::where([['state','=','2'],['uid','=',session('user_auth.user_id')]])->whereDay('create_time',$v['datetime'])->count();
            $list[$k]['okmian']=CashOrder::where([['state','=','2'],['uid','=',session('user_auth.user_id')]])->whereDay('create_time',$v['datetime'])->sum("money");
            $list[$k]['okmoney']=CashOrder::where([['state','=','2'],['uid','=',session('user_auth.user_id')]])->whereDay('create_time',$v['datetime'])->sum("price");
            $list[$k]['failcount']=CashOrder::where([['state','=','3'],['uid','=',session('user_auth.user_id')]])->whereDay('create_time',$v['datetime'])->count();
            $list[$k]['failmian']=CashOrder::where([['state','=','3'],['uid','=',session('user_auth.user_id')]])->whereDay('create_time',$v['datetime'])->sum("money");
            $list[$k]['loadcount']=CashOrder::where([['state','in','0,1'],['uid','=',session('user_auth.user_id')]])->whereDay('create_time',$v['datetime'])->count();
            $list[$k]['loadmian']=CashOrder::where([['state','in','0,1'],['uid','=',session('user_auth.user_id')]])->whereDay('create_time',$v['datetime'])->sum("money");
        }
        View::assign('cardype',input('cardype'));
        View::assign("list",$list);
        View::assign("day",isset($da['day'])?$da['day']:0);
        View::assign("starttime",isset($da['starttime'])?$da['starttime']:'');
        View::assign("endtime",isset($da['endtime'])?$da['endtime']:'');
        if(request()->isMobile()){
            View::assign("empty",'<div class="messager messager-empty"><div class="messager-icon"><i class="iconfont iconfont-empty"></i></div><div class="messager-text"><h2 class="messager-title">暂无记录</h2></div></div>');
            return view('cash_api/wap/statistics',['title'=>'充值统计']);
        }else{
            return view();
        }
    }

    public function export()
    {
        $da=input();
        $map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
        if(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && !empty($da['endtime'])){
            $map=[$da['starttime']." 00:00:00", $da['endtime']." 23:59:59"];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && empty($da['starttime']) && !empty($da['endtime'])){
            $map=["2020-1-1 00:00:00", $da['endtime']." 23:59:59"];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && empty($da['endtime'])){
            $map=[$da['starttime']." 00:00:00", date("Y-m-d  H:i:s")];
        }elseif(isset($da['day']) && !empty($da['day'])){
            $starttime=date('Y-m-d',strtotime("-{$da['day']}day"));
            $map=[$starttime." 23:59:59", date('Y-m-d 23:59:59')];
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
        $list=CashOrder::where($where)->whereTime('create_time', 'between',$map)->order('id desc')->select();
        $map=[];
        foreach($list as $k=>$v){
            $map[$k]['orderno']='&nbsp;'.$v['orderno'];
            $map[$k]['batchno']='&nbsp;'.$v['tmporder'];
            $res=SellList::where(['geway'=>$v['type']])->find();
            $map[$k]['title']=$res['title']."【".$res['mianzhi']."】";
            $map[$k]['number']='&nbsp;'.$v['number'];
            $map[$k]['money']=$v['money'];
            $map[$k]['state']=orderType($v['state']);
            $map[$k]['remarks']=$v['remarks']?$v['remarks']:'--';
            $map[$k]['create_time']=$v['create_time'];
            $map[$k]['update_time']=$v['update_time'];
        }
        if(empty($map))exit("记录为空");
        $title=['系统订单号','商户订单号','产品类型', '充值号码', '充值金额','状态','备注','提交时间','处理时间'];
        export_excel($map, $title,"导出充值记录".date('YmdHis'));
    }
}
