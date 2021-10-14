<?php
namespace app\admin\controller;

use app\api\lib\Recharge;
use app\common\controller\AdminBase;
use app\common\model\CashOrder as cs;
use app\common\model\SellList;
use app\common\model\CashNumberLog;
use app\common\model\User;
use think\facade\View;
use app\api\lib\ActionQueue;

class CashOrder extends AdminBase{
    public function index($limit=15)
    {
        $da=input();
        if ($this->request->isAjax()){
            $limit=$da['limit']?:$limit;
            $where[]=['id','<>',0];
            $map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
            if(isset($da['st']) && isset($da['se']) && !empty($da['st']) && !empty($da['se'])){
                $map=[$da['st'], $da['se']];
            }
            if(!empty($da['name']) && !empty($da['kk'])){
                  if($da['kk']=='shopid') {
                    $uid=User::where(['shopid'=>$da['name']])->value('id');
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
            $list=cs::with(['numbers','binduname','bindmsg'])->where($where)->whereTime('create_time', 'between',$map)->order('id desc')->paginate($limit);
            foreach($list as $k=>$v){
                $list[$k]['remarks']=$v['remarks'];
                $list[$k]['uname']=!empty($v['username'])?$v['username']:$v['mobile'];
                $list[$k]['title']=$v['title']."【".$v['mianzhi']."】";
                $list[$k]['atime']=date("Y-m-d",strtotime($v['create_time']))."</br>".date("H:i:s",strtotime($v['create_time']));
                $rt=0;
                if(count($v['numbers'])>0){
                    foreach($v['numbers'] as $item=>$val){
                        $list[$k]['amount'].=$val['cardno']."</br>";
                        if($val['state']==0)$rt=1;
                    }
                }else{
                    $list[$k]['amount']="未取卡";
                }
                $list[$k]['isok']=$rt;
                $list[$k]['states']=$v['state'];
                $list[$k]['cashnum']=count($v['numbers']);
                $list[$k]['state']=orderType($v['state']);
                $timenum=(int)strtotime($v['update_time'])-(int)strtotime($v['create_time']);
                $list[$k]['overtime']=($timenum>0)? feng($timenum):'--';
            }
            $this->result($list);
        }
        $li=SellList::select();
        if(isset($da['orderno'])){
		    $da['tc']=1;
		    $da['str']=$da['orderno'];
		}
		View::assign('da',$da);
        return $this->fetch('index',['li'=>$li]);
    }

    public function shenhe(){
        $da=input();
        $res=cs::find($da['id']);
        $res['shop']=SellList::where(['geway'=>$res['type']])->find();
        View::assign("res",$res);
        return view();
    }
    
    public function findOrder(){
        if($this->request->isAjax()){
            $id=input('id');
            $res=cs::field('orderno,money,tmporder')->find($id);
            if($res){
                $is=CashNumberLog::where(['state'=>1,'tmporder'=>$res['orderno']])->find();
                if($is){
                    if($is['money']<$is['price']){
                    $parm=['orderno'=>$is['tmporder'],'cardorder'=>$is['cardorder'],'data'=>['msg'=>$is['remarks'],'voucher'=>$is['voucher'],'ext'=>$is['ext']],'money'=>$is['money'],'moneyb'=>$is['price'],'uid'=>$is['carduid'],'type'=>1];
                        $oka=(new ActionQueue)->cashBack($parm);
                    }elseif($is['money']==$is['price']){
                        $parm=['orderno'=>$is['tmporder'],'cardorder'=>$is['cardorder'],'data'=>['msg'=>$is['remarks'],'voucher'=>$is['voucher'],'ext'=>$is['ext']],'money'=>$is['money'],'moneyb'=>$is['money'],'uid'=>$is['carduid'],'type'=>0];
                        $oka=(new ActionQueue)->cashBack($parm);
                    }else{
                        $ok=successful($is['cardorder'],8,0,0,0,'实际金额小于申明金额，实际金额['.$is['price'] .']');
                    }
                }else{
                    $isload=CashNumberLog::where([['state','in','0,3'],['tmporder','=',$res['orderno']]])->count();
                    $iserr=CashNumberLog::where(['state'=>2,'tmporder'=>$res['orderno']])->select();
                    if($isload==0){
                        (new cs)->failed($res['tmporder'],['msg'=>'重试失败']);
                        foreach($iserr as $item){
                            $oka=successful($item['cardorder'],3,0,0,0,$item['msg']);
                        }
                    }
                }
            }
            $this->success("查询完成");
        }
    }

    public function setRest(){
        if($this->request->isAjax()){
            $da=input();
            $map[]=['id','=',$da['id']];
            $map[]=['state','in','1,4'];
            $res=cs::where($map)->find();
            if(!$res){
                $this->error("标单失败，订单状态不允许该操作");
            }
            switch($da['type']){
                case 'ok':
                        (new cs)->successOrder($res['orderno'],['msg'=>'['.session('admin_auth.username').']手动成功']);
                        $this->success("成功，已回掉");
                    break;
                case 'err':
                       (new cs)->failed($res['orderno'],['msg'=>'['.session('admin_auth.username').']手动失败']);
                        $this->success("成功,已失败订单并回掉");
                   
                    break;
            }
        }
    }

    public function del(){
        if($this->request->isAjax()){
            $id=input('id');
            cs::destroy($id,true);
            $this->success("删除成功");
        }
    }
   
}