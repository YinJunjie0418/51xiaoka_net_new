<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\CashNumberLog;
use app\common\model\User;
use app\common\model\SellList;
use think\facade\View;
use app\common\model\Newapi;
use app\common\model\Order;
use app\api\lib\Recharge;
use app\common\model\CashOrder;


class CashLog extends AdminBase{
    public function index($limit=15){
        if($this->request->isAjax()){
            $da=input();
            $where[]=['id','>',0];
            $map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
            if(isset($da['st']) && isset($da['se']) && !empty($da['st']) && !empty($da['se'])){
                $map=[$da['st'], $da['se']];
            }
            if(!empty($da['name']) && !empty($da['kk'])){
                if($da['kk']=='shopid') {
                    $uid=User::where(['shopid'=>$da['name']])->find()['id'];
                    $where[]=['uid','=',$uid];
                }else{
                    $where[]=[$da['kk'],'like',"%{$da['name']}%"];
                }
            }
            if(!empty($da['state'])){
                $where[]=['state','=',$da['state']-1];
            }
            if(!empty($da['classa'])){
                $where[]=['type','=',$da['classa']];
            }
            $res=CashNumberLog::with(['beuid','beouid','bindoper'])->where($where)->whereTime('create_time', 'between',$map)->order('id desc')->paginate($limit);
            foreach($res as $k=>$v){
                $res[$k]['getstate']=$v['state'];
                $res[$k]['uid']=$v['auid'];
                $res[$k]['ovtime']=($v['state']>0)? feng((int)strtotime($v['update_time'])-(int)strtotime($v['create_time'])):'--';
                $res[$k]['state']=logType($v['state']);
                $res[$k]['voucher']=$v['voucher']?$v['voucher']:'--';
            }
            $this->result($res);
        }
        View::assign('num',CashNumberLog::count());
        View::assign('feeamount',CashNumberLog::sum('feeamount'));
        View::assign('li',SellList::field('geway,title,mianzhi')->select());
        return view();
    }

    public function shenhe(){
        $da=input();
        if($this->request->isAjax()){
            $res=CashNumberLog::where(['id'=>$da['id'],'state'=>3])->find($da['id']);
            $money=$da['money']/1000;
            if($res){
                if($res['money']>$money){
                    $str='['.session('admin_auth.username')."]实际充值".$money .",小于需求充值";
                    if(!empty($da['str']))$str=$da['str'];
                    $arr=['code'=>3,'orderno'=>$res['orderno'],'msg'=>$str,'actualCardPrice'=>$da['money'],'isben'=>1];
                }elseif($res['money']<$money){
                    $str='['.session('admin_auth.username')."]实际充值".$money .",大于需求充值";
                    if(!empty($da['str']))$str=$da['str'];
                    $arr=['code'=>2,'orderno'=>$res['orderno'],'msg'=>$str,'actualCardPrice'=>$da['money'],'isben'=>1];
                }else{
                    $str='['.session('admin_auth.username')."]实际充值".$money ;
                    if(!empty($da['str']))$str=$da['str'];
                    $arr=['code'=>1,'orderno'=>$res['orderno'],'msg'=>$str,'actualCardPrice'=>$da['money'],'isben'=>1];
                }
                $result=(new Recharge)->handleCash($arr);
                if($result===true){
                    $this->success("操作成功");
                }else{
                    $this->error($result);
                }
            }else{
                $this->error("该订单并不允许操作");
            }
        }
        $res=CashNumberLog::with('types')->find($da['id'])->toArray();
        View::assign("res",$res);
        return view();
    }

    public function Failure(){
        $da=input();
        if($this->request->isAjax()){
            $str="";
            $res=CashNumberLog::where(['id'=>$da['id'],'state'=>3])->find();
            if($res){
                switch($da['cardState']){
                    case 1:
                        $map['type']='ok';
                        break;
                    default:
                        $map['type']='err';
                }
                if(empty($da['str']) && $da['cardState']!=1){
                    $da['str']='['.session('admin_auth.username').']销卡失败';
                }
                $map['str']=$da['str'];
                $map['orderno']=$res['cardorder'];
                $str.=(new Order)->setRest($map);
                $CASH=new CashNumberLog();
                if($da['cashState']==1){
                    $CASH->startTrans();
                    try{
                        $oka=(new CashNumberLog)->where('id',$res['id'])->update(['state'=>2,'remarks'=>'['.session('admin_auth.username').']审核失败']);
                        $okb=(new CashOrder)->where(['orderno'=>$res['tmporder']])->update(['state'=>1,'remarks'=>'['.session('admin_auth.username').']订单重充']);
                        if($oka && $okb){
                            $str.=" 充值订单审核成功进入重充";
                            $CASH->commit();
                            (new Recharge)->Reissued($res['tmporder']);
                        }else{
                            $str.=" 更新充值订单失败";
                            $CASH->rollback();
                        }
                    }catch (\Exception $e){
                        $CASH->rollback();
                        $str.=" ".$e->getMessage();
                    }
                }else{
                    $okb=(new CashOrder)->failed($res['tmporder'],['msg'=>$da['str']?$da['str']:'['.session('admin_auth.username').']充值失败']);
                    if($okb){
                        $str.=" 操作充值成功";
                        (new CashNumberLog)->where('id',$res['id'])->update(['state'=>2,'remarks'=>'['.session('admin_auth.username').']审核失败']);
                    }else{
                        $str.=" 操作充值订单失败";
                    }
                }
                $this->success($str);
            }else{
                $this->error("该订单并不允许操作");
            }

        }
        View::assign("res",CashNumberLog::with('types')->find($da['id']));
        return view();
    }


    public function findorder(){
        if ($this->request->isPost()) {
            $id=input('id');
            $order=CashNumberLog::find($id);
            $api=new Newapi($order['operid']);
            $ok=$api->cashSearch($order);
            trace($ok);
            (new Recharge)->handleCash($ok);
            if($ok['code']==1 || $ok['code']==2 || $ok['code']==3){
                $this->success($ok['msg']."实际充值金额".$ok['actualCardPrice']/1000);
            }else{
                $this->error($ok['msg']);
            }
        }
    }

    public function sendPost(){
	    if($this->request->isAjax()){
	        $id=input('order');
	        $res=CashOrder::where(['orderno'=>$id])->find();
	        if($res){
	            $user=User::find($res['uid']);
                $data=[
                    'customerId'=>$user['shopid'],
                    'orderno'=>$res['orderno'],
                    'tmporder'=>$res['tmporder'],
                    'code'=>$res->getData('state')==2?1:(($res->getData('state')==0 || $res->getData('state')==4)?0:-1),
                    'cardno'=>"",
                    'voucher'=>'',
                    'ext'=>'',
                    'number'=>$res['number'],
                    'money'=>$res->getData('state')==2?$res['money']:0,
                    'amount'=>$res['price'],
                    'message'=>'ok'
                ];
                $urlok=(new CashOrder)->sendPostNotify($res['notify_url'],$data,$user['apikey']);
                $res->notifyok=1;
                $res->notifymsg=$urlok;
                $res->save();
                $this->success("回掉成功：".$urlok);
	        }else{
	            $this->error("参数错误，回掉失败");
	        }
	    }
	}
	
    public function export()
    {
        $da=input();
        $where[]=['id','>',0];
        $map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
        if(isset($da['st']) && isset($da['se']) && !empty($da['st']) && !empty($da['se'])){
            $map=[$da['st'], $da['se']];
        }
        if(!empty($da['name']) && !empty($da['kk'])){
            if($da['kk']=='shopid') {
                $uid=User::where(['shopid'=>$da['name']])->find()['id'];
                $where[]=['uid','=',$uid];
            }else{
                $where[]=[$da['kk'],'=',$da['name']];
            }
        }
        if(!empty($da['state'])){
            $where[]=['state','=',$da['state']-1];
        }
        if(!empty($da['classa'])){
            $where[]=['type','=',$da['classa']];
        }

        $listtotal=CashNumberLog::where($where)->whereTime('create_time', 'between',$map)->count();
        $title=['充值通道','产品','订单号/充值凭证','业务号码/流水', '商户信息', '充值卡号/流水','实际面值','实际到账','充值时间','状态','耗时','备注','短信内容'];
        articleAccessLog($title,"充值数据".date('Y-m-d'),$listtotal,$this,$where,$map);
    }

    public function getArticleAccessLog($where,$map,$page,$limit)
    {
        $list=CashNumberLog::with(['beuid','beouid','bindoper','gettitle'])->where($where)->whereTime('create_time', 'between',$map)->order('id desc')->page($page,$limit)->select();
        $map=[];
        foreach($list as $k=>$v){
            $map[$k][]=$v['oper'];
            $map[$k][]=$v['title'];
            $map[$k][]=$v['orderno']."凭:".($v['voucher']?$v['voucher']:'--');
            $map[$k][]="业:".$v['number']."流:".$v['tmporder'];
            $map[$k][]="寄:".$v['ouid']."充:".$v['auid'];
            $map[$k][]="卡:".$v['cardno']."单:".$v['cardorder'];
            $map[$k][]=$v['money'];
            $map[$k][]=$v['price'];
            $map[$k][]=$v['create_time'];
            $map[$k][]=logTyped($v['state']);
            $map[$k][]=($v['state']>0)? feng($v->getData('update_time')-$v->getData('create_time')):'--';
            $map[$k][]=$v['remarks'];
            $map[$k][]=$v['ext'];
        }
        return $map;
    }


}
