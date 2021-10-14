<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\CashNumberLog;
use app\common\model\SellList;
use app\common\model\User;
use app\common\model\CashOrder;

class CashCount extends AdminBase{

    public function index($limit=15){
        if ($this->request->isAjax()){
            $da=input();
            $da['STime']=isset($da['STime'])?(!empty($da['STime'])?$da['STime']:"2010-01-12 00:00:00"):"2010-01-12 00:00:00";
            $da['ETime']=isset($da['ETime'])?(!empty($da['ETime'])?$da['ETime']:date("Y-m-d 23:59:59")):date("Y-m-d 23:59:59");
            $map[]=['id','>',0];
            if(isset($da['name']) && !empty($da['name'])){
                $shopid=User::where(['username|mobile|shopid'=>$da['name']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            $list=CashOrder::with('bindmsg')
                ->field("uid,type,money,count(id) as cid,sum(money) as amoney,sum(if(`state`=2,money,0)) as amt,sum(if(`state`=2,price, 0) ) as su,sum( if(`state`=2, profit, 0)) as spr,sum(`state`=3 or `state`=5) as supr,sum(`state`=1 or `state`=4 or `state`=6) as paying,sum(`state`=0) as loadid")
                ->where($map)->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])->group("type")
                ->order('id desc')->paginate($limit,false,['query' => request()->param()]);
            foreach($list as $k=>$v){
                $fee=CashNumberLog::where(['type'=>$v['type'],'money'=>$v['money']])->where($map)->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])->sum('feeamount');
                $list[$k]['feeamount']=sprintf("%.3f",$fee);
                $list[$k]['spr']=sprintf("%.3f",$v['spr']-$fee);
                $list[$k]['type']=$v['title']."【".$v['money']."】";
            }
            $this->result($list);
        }
        return view();
    }

    public function usersum($limit=15){
        if ($this->request->isAjax()){
            $da=input();
            $da['STime']=isset($da['STime'])?(!empty($da['STime'])?$da['STime']:"2010-01-12 00:00:00"):"2010-01-12 00:00:00";
            $da['ETime']=isset($da['ETime'])?(!empty($da['ETime'])?$da['ETime']:date("Y-m-d 23:59:59")):date("Y-m-d 23:59:59");
            $map[]=['id','>',0];
            if(isset($da['name']) && !empty($da['name'])){
                $shopid=User::where(['username|mobile|shopid'=>$da['name']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            $list=CashOrder::with('binduname')
                ->field("uid,type,money,count(id) as cid,sum(money) as bmoney,sum(if(`state`=2,money,0)) as amt,sum(if(`state`=2,price, 0) ) as su,sum( if(`state`=2, profit, 0)) as spr,sum(`state`=3 or `state`=5) as supr,sum(`state`=1 or `state`=4 or `state`=6) as paying,sum(`state`=0) as loadid")
                ->where($map)->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])
                ->group("uid")->order('id desc')->paginate($limit,false,['query' => request()->param()]);
            foreach($list as $k=>$v){
                $fee=CashNumberLog::where(['uid'=>$v['uid']])->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])->sum('feeamount');
                $list[$k]['uid']=$v['shopid'];
                $list[$k]['feeamount']=sprintf("%.3f",$fee);
                $list[$k]['spr']=sprintf("%.3f",$v['spr']-$fee);
            }
            $this->result($list);
        }
        return view();
    }

    public function todata($limit=15){
        if ($this->request->isAjax()){
            $da=input();
            $da['STime']=isset($da['STime'])?(!empty($da['STime'])?$da['STime']:"2010-01-12 00:00:00"):"2010-01-12 00:00:00";
            $da['ETime']=isset($da['ETime'])?(!empty($da['ETime'])?$da['ETime']:date("Y-m-d 23:59:59")):date("Y-m-d 23:59:59");
            $map[]=['id','>',0];
            if(isset($da['name']) && !empty($da['name'])){
                $shopid=User::where(['username|mobile|shopid'=>$da['name']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            $list=(new CashOrder)
                ->field("FROM_UNIXTIME(create_time, '%Y-%m-%d') as day,money,count(id) as cid,sum(money) as money,sum(if(`state`=2,money,0)) as amt,sum(if(`state`=2,price, 0) ) as su,sum( if(`state`=2, profit, 0)) as spr,sum(`state`=3 or `state`=5) as supr,sum(`state`=1 or `state`=4 or `state`=6) as paying,sum(`state`=0) as loadid")
                ->where($map)->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])
                ->group('FROM_UNIXTIME(create_time,"%Y-%m-%d")')->order('id desc')->paginate($limit,false,['query' => request()->param()]);
            foreach ($list as $k=>$v){
                $fee=CashNumberLog::whereDay('create_time',$v['day'])->sum('feeamount');
                $list[$k]['feeamount']=$fee;
                $list[$k]['spr']=sprintf("%.2f",$v['spr']-$fee);
            }
            $this->result($list);
        }
        return view();
    }
}
