<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Order;
use app\common\model\User;
use app\common\model\CardList;
use app\common\model\CardOperator;

class Apitongji extends AdminBase
{
    

    public function index($limit=15)
    {
		if ($this->request->isAjax()){
			$da=input();
			$da['STime']=isset($da['STime'])?(!empty($da['STime'])?$da['STime']:"2010-01-12 00:00:00"):"2010-01-12 00:00:00";
			$da['ETime']=isset($da['ETime'])?(!empty($da['ETime'])?$da['ETime']:date("Y-m-d 23:59:59")):date("Y-m-d 23:59:59");
            $map[]=['tmporder','<>','0'];
            if(isset($da['Uid']) && !empty($da['Uid'])){
                $shopid=User::where(['shopid'=>$da['Uid']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            if(isset($da['Name']) && !empty($da['Name'])){
                $shopid=User::where(['username|mobile'=>$da['Name']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
			$list=(new Order)->field("class,count(id) as cid,sum(money) as money, GROUP_CONCAT(id) as fg")->where($map)->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])->group("class")->order('id desc')->paginate($limit,false,['query' => request()->param()]);
			foreach($list as $k=>$v){
			     $amt=0;$su=0;$supr=0;$spr=0;$loadid=0;$paying=0;$failed=0;
			      $result=Order::where([['class','=',$v['class']],['tmporder','<>',0]])->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])->select();
			      foreach($result as $kk=>$vk){
			          if($vk->getData('state')==2 || $vk->getData('state')==8 )$amt+=$vk['money'];
			          if($vk->getData('state')==2 || $vk->getData('state')==8)$su+=$vk['amount'];
			          if($vk->getData('state')==2 || $vk->getData('state')==8)$supr+=$vk['xitmoney'];
			          if($vk->getData('state')==0)$loadid++;
			          if($vk->getData('state')==1 || $vk->getData('state')==4)$paying++;
			          if($vk->getData('state')==3 || $vk->getData('state')==7)$failed++;
			      }
				$list[$k]['amt']=$amt;
				$list[$k]['su']=$su;
				$list[$k]['supr']=$supr;
				$list[$k]['loadid']=$loadid."/笔";
				$list[$k]['paying']=$paying."/笔";
				$list[$k]['failed']=$failed."/笔";
				$list[$k]['spr']= sprintf("%.2f",$supr-$su);
				$list[$k]['class']=CardList::where(['type'=>$v['class']])->value('title');
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
            $map[]=['tmporder','<>','0'];
			if(isset($da['Uid']) && !empty($da['Uid'])){
                $shopid=User::where(['shopid'=>$da['Uid']])->find();
				$map[]=['uid','=',$shopid['id']];
			}
			if(isset($da['Name']) && !empty($da['Name'])){
				$shopid=User::where(['username|mobile'=>$da['Name']])->find();
				$map[]=['uid','=',$shopid['id']];
			}
			$list=(new Order)->field("uid,count(id) as cid,sum(money) as money, GROUP_CONCAT(id) as fg")->where($map)->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])->group("uid")->order('id desc')->paginate($limit,false,['query' => request()->param()]);
			foreach($list as $k=>$v){
			    $amt=0;$su=0;$supr=0;$spr=0;$loadid=0;$paying=0;$failed=0;
			      $result=Order::where([['uid','=',$v['uid']],['tmporder','<>',0]])->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])->select();
			      foreach($result as $kk=>$vk){
			          if($vk->getData('state')==2 || $vk->getData('state')==8 )$amt+=$vk['money'];
			          if($vk->getData('state')==2 || $vk->getData('state')==8)$su+=$vk['amount'];
			          if($vk->getData('state')==2 || $vk->getData('state')==8)$supr+=$vk['xitmoney'];
			          if($vk->getData('state')==0)$loadid++;
			          if($vk->getData('state')==1 || $vk->getData('state')==4)$paying++;
			          if($vk->getData('state')==3 || $vk->getData('state')==7)$failed++;
			      }
				$list[$k]['amt']=$amt;
				$list[$k]['su']=$su;
				$list[$k]['supr']=$supr;
				$list[$k]['loadid']=$loadid."/笔";
				$list[$k]['paying']=$paying."/笔";
				$list[$k]['failed']=$failed."/笔";
				$list[$k]['spr']= sprintf("%.2f",$supr-$su);
				$name=User::find($v['uid']);
				$list[$k]['shopid']=empty($name['username'])?$name['mobile']:$name['username'];
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
            $map[]=['tmporder','<>','0'];
			if(isset($da['Uid']) && !empty($da['Uid'])){
                $shopid=User::where(['shopid'=>$da['Uid']])->find();
                $map[]=['uid','=',$shopid['id']];
			}
			if(isset($da['Name']) && !empty($da['Name'])){
				$shopid=User::where(['username|mobile'=>$da['Name']])->find();
				$map[]=['uid','=',$shopid['id']];
			}
			$list=(new Order)->field("FROM_UNIXTIME(create_time, '%Y-%m-%d') as day,count(id) as cid,sum(money) as money, GROUP_CONCAT(id) as fg")->where($map)->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])->group('FROM_UNIXTIME(create_time,"%Y-%m-%d")')->order('id desc')->paginate($limit,false,['query' => request()->param()]);
			foreach($list as $k=>$v){
			    $amt=0;$su=0;$supr=0;$spr=0;$loadid=0;$paying=0;$failed=0;
			      $result=Order::where([['tmporder','<>',0]])->whereDay('create_time',$v['day'])->select();
			      foreach($result as $kk=>$vk){
			          if($vk->getData('state')==2 || $vk->getData('state')==8 )$amt+=$vk['money'];
			          if($vk->getData('state')==2 || $vk->getData('state')==8)$su+=$vk['amount'];
			          if($vk->getData('state')==2 || $vk->getData('state')==8)$supr+=$vk['xitmoney'];
			          if($vk->getData('state')==0)$loadid++;
			          if($vk->getData('state')==1 || $vk->getData('state')==4)$paying++;
			          if($vk->getData('state')==3 || $vk->getData('state')==7)$failed++;
			      }
				$list[$k]['amt']=$amt;
				$list[$k]['su']=$su;
				$list[$k]['supr']=$supr;
				$list[$k]['loadid']=$loadid."/笔";
				$list[$k]['paying']=$paying."/笔";
				$list[$k]['failed']=$failed."/笔";
				$list[$k]['spr']= sprintf("%.2f",$supr-$su);
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
            $map[]=['tmporder','<>','0'];
			if(isset($da['Uid']) && !empty($da['Uid'])){
                $shopid=User::where(['shopid'=>$da['Uid']])->find();
                $map[]=['uid','=',$shopid['id']];
			}
			if(isset($da['Name']) && !empty($da['Name'])){
				$shopid=User::where(['username|mobile'=>$da['Name']])->find();
				$map[]=['uid','=',$shopid['id']];
			}
			$list=(new Order)->field("type,count(id) as cid,sum(money) as money, GROUP_CONCAT(id) as fg")->where($map)->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])->group('type')->order('id desc')->paginate($limit,false,['query' => request()->param()]);
			foreach($list as $k=>$v){
			     $name=CardOperator::where(['id'=>$v['type']])->value('name');
				$list[$k]['name']=$name?$name:"通道已删除";
			     $amt=0;$su=0;$supr=0;$spr=0;$loadid=0;$paying=0;$failed=0;
			      $result=Order::where([['type','=',$v['type']],['tmporder','<>',0]])->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])->select();
			      foreach($result as $kk=>$vk){
			          if($vk->getData('state')==2 || $vk->getData('state')==8 )$amt+=$vk['money'];
			          if($vk->getData('state')==2 || $vk->getData('state')==8)$su+=$vk['amount'];
			          if($vk->getData('state')==2 || $vk->getData('state')==8)$supr+=$vk['xitmoney'];
			          if($vk->getData('state')==0)$loadid++;
			          if($vk->getData('state')==1 || $vk->getData('state')==4)$paying++;
			          if($vk->getData('state')==3 || $vk->getData('state')==7)$failed++;
			      }
				$list[$k]['amt']=$amt;
				$list[$k]['su']=$su;
				$list[$k]['supr']=$supr;
				$list[$k]['loadid']=$loadid."/笔";
				$list[$k]['paying']=$paying."/笔";
				$list[$k]['failed']=$failed."/笔";
				$list[$k]['spr']= sprintf("%.2f",$supr-$su);

			}
			$this->result($list);
		}
        return view();
	}
    
}
