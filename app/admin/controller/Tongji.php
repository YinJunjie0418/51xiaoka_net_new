<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Order;
use app\common\model\User;
use app\common\model\Withdraw;

class Tongji extends AdminBase
{
    

    public function index($limit=15)
    {
		if ($this->request->isAjax()){
			$da=input();
			$da['STime']=isset($da['STime'])?(!empty($da['STime'])?$da['STime']:"2010-01-12 00:00:00"):"2010-01-12 00:00:00";
			$da['ETime']=isset($da['ETime'])?(!empty($da['ETime'])?$da['ETime']:date("Y-m-d 23:59:59")):date("Y-m-d 23:59:59");
			$map[]=['tmporder','=','0'];
            if(isset($da['Uid']) && !empty($da['Uid'])){
                $shopid=User::where(['shopid'=>$da['Uid']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            if(isset($da['Name']) && !empty($da['Name'])){
                $shopid=User::where(['username|mobile'=>$da['Name']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
			$list=Order::with('calssname')
                ->field("class,count(id) as cid,sum(money) as money,sum(if(`state`=2 or `state`=8,money,0)) as amt,sum(if(`state`=2 or `state`=8,amount, 0) ) as su,sum( if(`state`=2 or `state`=8, xitmoney, 0)) as supr,sum(if(type!=0,profit,0)) as profit,sum(`state`=0) as loadid,sum(`state`=1 or `state`=4) as paying,sum(`state`=3 or `state`=7) as failed")
                ->where($map)->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])
                ->group("class")
                ->order('id desc')
                ->paginate($limit,false,['query' => request()->param()]);
			foreach($list as $k=>$v){
				$list[$k]['loadid']=$v['loadid']."/笔";
				$list[$k]['paying']=$v['paying']."/笔";
				$list[$k]['failed']=$v['failed']."/笔";
				$list[$k]['spr']= $v['profit'];
				$list[$k]['class']=$v['title'];
			}
			$this->result($list);
		}
        return $this->fetch('index');
    }
	
	public function user($limit=15)
    {
		if ($this->request->isAjax()){
			$da=input();
			$da['STime']=isset($da['STime'])?(!empty($da['STime'])?$da['STime']:"2010-01-12 00:00:00"):"2010-01-12 00:00:00";
			$da['ETime']=isset($da['ETime'])?(!empty($da['ETime'])?$da['ETime']:date("Y-m-d 23:59:59")):date("Y-m-d 23:59:59");
			$map[]=['tmporder','=','0'];
            if(isset($da['Uid']) && !empty($da['Uid'])){
                $shopid=User::where(['shopid'=>$da['Uid']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            if(isset($da['Name']) && !empty($da['Name'])){
                $shopid=User::where(['username|mobile'=>$da['Name']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
			$list=Order::with('uname')
                ->field("uid,count(id) as cid,sum(money) as money,sum(if(`state`=2 or `state`=8,money,0)) as amt,sum(if(`state`=2 or `state`=8,amount, 0) ) as su,sum( if(`state`=2 or `state`=8, xitmoney, 0)) as supr,sum(`state`=0) as loadid,sum(if(type!=0,profit,0)) as profit,sum(`state`=1 or `state`=4) as paying,sum(`state`=3 or `state`=7) as failed")
                ->where($map)
                ->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])
                ->group("uid")
                ->order('id desc')->paginate($limit,false,['query' => request()->param()]);
			foreach($list as $k=>$v){
                $list[$k]['loadid']=$v['loadid']."/笔";
                $list[$k]['paying']=$v['paying']."/笔";
                $list[$k]['failed']=$v['failed']."/笔";
                $list[$k]['spr']= $v['profit'];
				$list[$k]['shopid']=empty($v['username'])?$v['shopid']:$v['username'];
			}
			$this->result($list);
		}
        return view();
    }
	
	public function todata($limit=15)
    {
		if ($this->request->isAjax()){
			$da=input();
			$da['STime']=isset($da['STime'])?(!empty($da['STime'])?$da['STime']:"2010-01-12 00:00:00"):"2010-01-12 00:00:00";
			$da['ETime']=isset($da['ETime'])?(!empty($da['ETime'])?$da['ETime']:date("Y-m-d 23:59:59")):date("Y-m-d 23:59:59");
			$map[]=['tmporder','=','0'];
            if(isset($da['Uid']) && !empty($da['Uid'])){
                $shopid=User::where(['shopid'=>$da['Uid']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            if(isset($da['Name']) && !empty($da['Name'])){
                $shopid=User::where(['username|mobile'=>$da['Name']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
			$list=(new Order)
                ->field("FROM_UNIXTIME(create_time, '%Y-%m-%d') as day,count(distinct batchno) as baci,count(id) as cid,sum(money) as money,sum(if(`state`=2 or `state`=8,money,0)) as amt,sum(if(`state`=2 or `state`=8,amount, 0) ) as su,sum(if(type!=0,profit,0)) as profit,sum( if(`state`=2 or `state`=8, xitmoney, 0)) as supr,sum(`state`=0 or `state`=1) as loadid,sum(`state`=3 or `state`=7) as failed")
                ->where($map)->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])
                ->group("FROM_UNIXTIME(create_time,'%Y-%m-%d')")
                ->order('id desc')->paginate($limit,false,['query' => request()->param()]);
			foreach($list as $k=>$v){
                $list[$k]['paying']=sprintf("%.4f",1-(int)$v['loadid']/(int)$v['cid'])*100 ."%";
                $list[$k]['err']=sprintf("%.4f",(int)$v['failed']/(int)$v['cid'])*100 ."%";
                $list[$k]['loadid']=$v['loadid']."/笔";
                $list[$k]['failed']=$v['failed']."/笔";
                $list[$k]['spr']= $v['profit'];
			}
			$this->result($list);
		}
        return view();
    }

    public function comtime($limit=15)
    {
        if ($this->request->isAjax()){
            $da=input();
            $da['STime']=isset($da['STime'])?(!empty($da['STime'])?$da['STime']:"2010-01-12 00:00:00"):"2010-01-12 00:00:00";
            $da['ETime']=isset($da['ETime'])?(!empty($da['ETime'])?$da['ETime']:date("Y-m-d 23:59:59")):date("Y-m-d 23:59:59");
            $map[]=['tmporder','=','0'];
            if(isset($da['Uid']) && !empty($da['Uid'])){
                $shopid=User::where(['shopid'=>$da['Uid']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            if(isset($da['Name']) && !empty($da['Name'])){
                $shopid=User::where(['username|mobile'=>$da['Name']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            $list=(new Order)
                ->field("FROM_UNIXTIME(chulitime, '%Y-%m-%d') as day,sum(state=2 or state=8) as cid,sum(if(state=2 or state=8,money,0)) as money,sum(amount) as amount,sum(if(type!=0,profit,0)) as profit,sum(if(state=8 and ispei=1,settle_amt,0)) as settleamt,sum(if(state=8 and ispei=2,settle_amt,0)) as amt")
                ->where($map)->where('state','in','2,8')->whereTime('chulitime', 'between',[$da['STime'],$da['ETime']])
                ->group("FROM_UNIXTIME(chulitime,'%Y-%m-%d')")
                ->order('chulitime desc')->paginate($limit,false,['query' => request()->param()]);
				 foreach($list as $k=>$v){
					$list[$k]['xitmoney']=sprintf("%.4f",$v['amount']+$v['profit']);
				}
            $this->result($list);
        }
        return view();
    }
	
	public function chanel($limit=15){
		if ($this->request->isAjax()){
			$da=input();
			$da['STime']=isset($da['STime'])?(!empty($da['STime'])?$da['STime']:"2010-01-12 00:00:00"):"2010-01-12 00:00:00";
			$da['ETime']=isset($da['ETime'])?(!empty($da['ETime'])?$da['ETime']:date("Y-m-d 23:59:59")):date("Y-m-d 23:59:59");
			$map[]=['tmporder','=','0'];
            if(isset($da['Uid']) && !empty($da['Uid'])){
                $shopid=User::where(['shopid'=>$da['Uid']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            if(isset($da['Name']) && !empty($da['Name'])){
                $shopid=User::where(['username|mobile'=>$da['Name']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
			$list=Order::with('opername')
                ->field("type,FROM_UNIXTIME(chulitime, '%Y-%m-%d') as day,sum(`state`=2 or `state`=8) as cid,sum(if(`state`=2 or `state`=8,money,0)) as money,sum(amount) as amount,sum( if(`state`=2 or `state`=8, xitmoney, 0)) as xitmoney,sum(if(type!=0,profit,0)) as profit,sum(if(`state`=3 or `state`=7,money,0)) as failedmoney,sum(`state`=3 or `state`=7) as failed")
                ->where($map)->whereTime('chulitime', 'between',[$da['STime'],$da['ETime']])
                ->group('type,FROM_UNIXTIME(chulitime, "%Y-%m-%d")')->order('id desc')->paginate($limit,false,['query' => request()->param()]);
			foreach($list as $k=>$v){
                if($v['type']==0){
                    $list[$k]['name']="本站消耗";
                }else{
                    $list[$k]['name']=$v['oper']?:"通道已删除";
                }
                $list[$k]['cid']=$v['cid']."/笔";
                $list[$k]['failed']=$v['failed']."/笔";

			}
			$this->result($list);
		}
        return view();
	}
	
	public function jiesuan($limit=15){
		if ($this->request->isAjax()){
			$da=input();
			$da['STime']=isset($da['STime'])?(!empty($da['STime'])?$da['STime']:"2010-01-12 00:00:00"):"2010-01-12 00:00:00";
			$da['ETime']=isset($da['ETime'])?(!empty($da['ETime'])?$da['ETime']:date("Y-m-d 23:59:59")):date("Y-m-d 23:59:59");
			$map[]=['id','>',0];
            if(isset($da['Uid']) && !empty($da['Uid'])){
                $shopid=User::where(['shopid'=>$da['Uid']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            if(isset($da['Name']) && !empty($da['Name'])){
                $shopid=User::where(['username|mobile'=>$da['Name']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
			$list=(new Withdraw)
                ->field("FROM_UNIXTIME(create_time, '%Y-%m-%d') as day,sum(`status`=2) as cid,sum(if(`status`=2,money,0)) as money,sum(`status`=2 and `type`='alitype') as alinum,sum(if(`status`=2 and `type`='alitype',money,0)) as alimoney,sum(`status`=2 and `type`='banktype') as banknum,sum(if(`status`=2 and `type`='banktype',money,0)) as bankmoney,sum(if(`status`=2,price,0)) as price,sum(wtype=1 and status=2) as wnum,sum(if(`wtype`=1 and `status`=2,money,0)) as wmoney")
                ->where($map)
                ->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])
                ->group('FROM_UNIXTIME(create_time, "%Y-%m-%d")')
                ->order('id desc')->paginate($limit,false,['query' => request()->param()]);
			$this->result($list);
		}
        return view();
	}

    
    
}
