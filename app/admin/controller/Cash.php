<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\facade\Cache;
use think\facade\View;
use think\facade\Config;
use think\facade\Db;
use app\common\model\User;
use app\common\model\Withdraw;
use app\common\model\Wechat;
use app\common\model\Userbank;
use app\common\model\UserAuth;
use app\common\model\CardOperator;


class Cash extends AdminBase
{
    protected $noAuth = ['export'];
    public function index($limit=15)
    {
		if ($this->request->isAjax()) {
			$da= input();
			$map[]=['id','>',0];
			if(isset($da['state']) && $da['state']!=0){
                    $map[]=['status','=',$da['state']-1];
                }
			if(isset($da['keys']) && isset($da['shop_id']) && !empty($da['keys']) && !empty($da['shop_id'])){
			    if($da['keys']=='shopid'){
			        $uid=User::where(['shopid'=>$da['shop_id']])->value('id');
			        $map[]=['uid','=',$uid];
                }
                if($da['keys']=='name'){
                    $uid=UserAuth::where('name','like',"%".$da['shop_id']."%")->select();
                    $str="";
                    foreach ($uid as $ka=>$vv){
                        $str.=$vv['uid'].",";
                    }
                    $map[]=['uid','in',$str];
                }
                if($da['keys']=='orderno')$map[]=['order','=',$da['shop_id']];
                if($da['keys']=='money')$map[]=['money','=',$da['shop_id']];
			}
			   $list = Withdraw::with('preone')->where($map)->order('id desc')->paginate($limit);
			foreach($list as $k=>$v){
				$list[$k]['username']=User::where(['id'=>$v['uid']])->value('username');
                $list[$k]['shopid']=User::where(['id'=>$v['uid']])->value('shopid');
				$list[$k]['type']=$v['type']=='alitype'?"支付宝提现":($v['type']=='wxtype'?"微信提现":"银行卡提现");
                $list[$k]['getstatus']=$v['status'];
				$list[$k]['status']=txType($v['status']);
                $ovtime=strtotime($v['update_time'])-strtotime($v['create_time']);
				$list[$k]['ovtime']=$ovtime>0?feng($ovtime):"--";
			}
			$this->result($list);
		}
		View::assign("money",$this->getMoney());
        return $this->fetch('index');
    }
    
    public function getMoney(){
        $config=Config::load('setting/cash','cash');
        $className="\api\\".$config['txclass'];
        $isdisplay=1;
        $bankname="";
        $lianres='';
        if($config['banktype']==1){
            $bankname=CardOperator::where(['class'=>$config['txclass']])->value('name');
            if(class_exists($className) && method_exists($className,'getMoney')){
                $lian=new $className([]);
                $lianres=$lian->getMoney();
            }elseif(!class_exists($className)){
                $lianres="接口文件不存在";
            }else{
                $lianres="没有查询余额功能";
            }
        }else{
            $isdisplay=0;
        }

        if($config['alitype']==1){
            $isalipay=1;
            $alipay=(new \app\common\alipay\Cashwith)->getMoney();
        }else{
            $alipay=0;
            $isalipay=0;
        }

        return ['lian'=>$lianres,'bankname'=>$bankname,'alipay'=>$alipay,'isdisplay'=>$isdisplay,'isalipay'=>$isalipay];
    }

    
    public function edit()
    {
        if ($this->request->isAjax()){
            $param = $this->request->param();
            if(!isset($param['type']) && empty($param['type']))$this->error('请选择操作状态');
            if(isset($param['type']) && isset($param['id'])){
                Cache::dec("cash");
				$result=Withdraw::find($param['id']);
				switch($param['type']){
					case "shou":
					 if($result['status']==1){
                         $str=isset($param['content'])?$param['content']:"[手动转账]";
					     $ok=Withdraw::where(['id'=>$param['id']])->update(['status'=>2,'wtype'=>1,'content'=>'['.session('admin_auth.username').']'.$str]);
					     if($ok && $result['type']=='wxtype'){
					        	$ubank=Userbank::where(['id'=>$result['cid']])->find();
					        	if($ubank){
					        	   $cha=(float)$result['money']-(float)$result['price'];
                                   $strxin=$result['shopid']."[".$ubank['user']." ".$ubank['bankname']."]金额:".$result['money']."元,手续费:".$result['price']."元,共合计:".$cha."元";
					        	  $ms=(new Wechat)->wxCash($ubank['accounts'],$strxin,$cha);
					        	  if($ms['errcode']!='0'){
					        	       $this->error("订单操作成功,微信通知：".$ms['errmsg']);
					        	  }
					        	}
					    }
					 }else{
						 $this->error("该订单当前状态不可操作");
					 }
					break;
					case "auto":
					  if($result['status']==1){
						  Withdraw::where(['id'=>$param['id']])->update(['status'=>0]);
						 $ok=\think\facade\Queue::push("app\home\job\Jobone@tixian", ['orderid'=>$result['order']],'Jobtixian');
					  }else{
						  $this->error("该订单当前状态不可操作");
					  }
					break;
					default:
					 if($result['status']==1){
						 Db::startTrans();
						 try {
							 $str=isset($param['content'])?$param['content']:"[失败退回]";
					         Withdraw::where(['id'=>$param['id']])->update(['status'=>3,'content'=>'['.session('admin_auth.username').']'.$str]);
							 Db::name('user')->where(['id'=>$result['uid']])->inc('money',$result['money'])->update();
							 addlog($result['uid'],$result['money'],3,$result['order'],"[提现退票]{$result['money']}");
							 Db::commit();
							 $ok=true;
						}catch (\Exception $e) {
							Db::rollback();
							$this->error($e->getMessage());
						};
						
					 }else{
						 $this->error("该订单当前状态不可操作");
					 }
				}
				if($ok == true) {
					$this->success('操作成功',url('/Cash/index'));
				} else {
					$this->error($this->errorMsg);
				}
			}
            
        }
        $data = Withdraw::with('preone')->where('id', input('id'))->find();
        $isdisplay=1;
        $config=Config::load('setting/cash','cash');
        if($data['type']=="alitype"){
            if($config['alitype']==1){
                $money=(new \app\common\alipay\Cashwith)->getMoney();
            }else{
                $isdisplay=0;
            }

        }else{
            $className="\api\\".$config['txclass'];
            if(class_exists($className) && method_exists($className,'getMoney')){
                $money=(new $className([]))->getMoney();
            }else{
                $isdisplay=0;
                $money="接口文件错误或没有余额查询方式";
            }
        }
        return $this->fetch('save', [
            'data' => $data,'shopid'=>User::find($data['uid'])['shopid'],"money"=>$money,'isdisplay'=>$isdisplay]);
    }

    public function lookup()
    {
        if ($this->request->isPost()) {
            $id=input('id');
            $cash=Withdraw::field('id,order,uid,money')->where(['id'=>$id])->find();
            if($cash){
                $config=Config::load('setting/cash','cash');
                $className="\api\\".$config['txclass'];
                if(class_exists($className) && method_exists($className,'blindSearch')){
                    $BANK=new $className($cash);
                    $res=$BANK->blindSearch();
                    return json($res);
                }else{
                    $this->error("接口错误或不存在查询方法，请联系管理员");
                }
            }else{
                $this->error("参数错误");
            }
        }
    }
    
        public function export()
    {
            $da=input();
            $map[]=['id','>',0];
			if(isset($da['state']) && $da['state']!=0){
                    $map[]=['status','=',$da['state']-1];
                }
			if(isset($da['keys']) && isset($da['shop_id']) && !empty($da['keys']) && !empty($da['shop_id'])){
			    if($da['keys']=='shopid'){
			        $uid=User::where(['shopid'=>$da['shop_id']])->value('id');
			        $map[]=['uid','=',$uid];
                }
                if($da['keys']=='name'){
                    $uid=UserAuth::where('name','like',"%".$da['shop_id']."%")->select();
                    $str="";
                    foreach ($uid as $ka=>$vv){
                        $str.=$vv['uid'].",";
                    }
                    $map[]=['uid','in',$str];
                }
                if($da['keys']=='orderno')$map[]=['order','=',$da['shop_id']];
                if($da['keys']=='money')$map[]=['money','=',$da['shop_id']];
			}
			$listtotal = Withdraw::where($map)->count();
            $title=['订单号','会员ID','真实姓名','提现类型','提现账户', '提现账号', '提现金额','实际到账','手续费','状态','备注','时间'];
            articleAccessLog($title,"提现数据".date('Y-m-d'),$listtotal,$this,$map,$map);
    }

    public function getArticleAccessLog($where,$mapp,$page,$limit)
    {
        $list = Withdraw::with('preone')->where($where)->order('id desc')->page($page,$limit)->select();
        $map=[];
        foreach($list as $k=>$v){
            $map[$k][]=$v['order'];
            $map[$k][]=User::where(['id'=>$v['uid']])->value('shopid');
            $map[$k][]=$v['user']."\t";
            $map[$k][]=$v['type']=='alitype'?"支付宝提现":($v['type']=='wxtype'?"微信提现":"银行卡提现");
            $map[$k][]=$v['bankname']."\t";
            $map[$k][]=$v['accounts']."\t";
            $map[$k][]=$v['money'];
            $map[$k][]=$v['money']-$v['price'];
            $map[$k][]=$v['price'];
            $map[$k][]=ttxType($v['status']);
            $map[$k][]=$v['content']?$v['content']:'--';
            $map[$k][]=$v['create_time'];
        }
        return $map;
    }

}
