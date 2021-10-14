<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\CashNumberLog;
use app\common\model\SellList;
use app\common\model\SellListRate;
use app\common\model\User;
use app\common\model\UserAuth;
use think\facade\View;

class Carduse extends AdminBase{
    public function index($limit=15){
        if($this->request->isAjax()){
            $da=input();
            $da['STime']=(isset($da['STime']) && !empty($da['STime']))?$da['STime']:date("Y-m-d 00:00:00");
            $da['ETime']=(isset($da['ETime']) && !empty($da['ETime']))?$da['ETime']:date("Y-m-d 23:59:59");
            $map[]=['id','>',0];
            $uidd="";
            if(isset($da['uid']) && !empty($da['uid'])){
                $uidd=User::where(['shopid'=>$da['uid']])->value('id');
            }
            if(isset($da['name']) && !empty($da['name'])){
                $uidd=User::where(['username|mobile'=>$da['name']])->value('id');
            }
            $group="type";
            if(isset($da['onclass']) && $da['onclass']=="on"){
                if($da['classa']!=0){
                    $sell=SellList::field('geway')->where('id','in',$da['classa'])->select();
                    $str='';
                    foreach($sell as $item){
                        $str.=$item['geway'].",";
                    }
                    $map[]=['type','in',$str];
                }
            }
            $field="sum(state=1) as ids,money,sum(if(state=1 and price>0 and price<money,money,0)) as smallmoney,sum(if(state=1 and price>money,money,0)) as bigmoney,sum(if(state=1,money,0)) as amoney,sum(if(state=1,reamount,0)) as amount,sum(if(state=1,cardmount,0)) as settl,type,carduid,uid,sum(feeamount) as dai";
            $with[]='gettitle';
            if(isset($da['ontime']) && $da['ontime']=="on"){
                $group="FROM_UNIXTIME(create_time,'%Y-%m-%d')";
                $field.=",FROM_UNIXTIME(create_time, '%Y-%m-%d') as day";
            }
            if(isset($da['onclass']) && $da['onclass']=="on"){
                $group.=",type";
            }

            if(isset($da['oncard']) && $da['oncard']=="on"){
                $group.=",carduid";
                $with[]='beouid';
                if($uidd)$map[]=['carduid','=',$uidd];
            }
            if(isset($da['onrechang']) && $da['onrechang']=='on'){
                $group.=",uid";
                $with[]='beuid';
               if($uidd)$map[]=['uid','=',$uidd];
            }
            if(isset($da['onmoney']) && $da['onmoney']=='on'){
                if($da['money']>0){
                    $map[]=['money','=',$da['money']];
                }
                $group.=",money";
            }
            $list=CashNumberLog::with($with)->field($field)->alias('a')
                ->where($map)
                ->where(function ($query) {
                    $query->where('feeamount', '>',0)
                        ->whereOr('state', '=', 1);
                })
                ->whereTime('create_time','between',[$da['STime'],$da['ETime']])
                ->group($group)
                ->order('id desc')
                ->paginate($limit,false,['query' => request()->param()]);
            foreach($list as $k=>$v){
                $urel=UserAuth::where(['uid'=>$v['carduid']])->value('name');
                $list[$k]['username']=$urel?:$v['ouid'];
                $list[$k]['company']=$v['company']?:$v['auid'];
            }
            $this->result($list);
        }
        $list=SellListRate::with('bindTitle')->field("bindid,group_concat(listid) ids")->group('bindid')->select();
        View::assign('classlist',$list);
        $mianzhi=SellList::field('distinct mianzhi')->select()->toArray();
        sort($mianzhi);
        View::assign("mianzhi",$mianzhi);
        return view();
    }

}
