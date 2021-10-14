<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\facade\View;
use think\facade\Db;
use app\common\model\Order as Orders;
use app\common\model\Admin;
use app\common\model\CardList;
use app\common\model\User;
use app\common\model\CardOperator;
use app\common\model\Newapi;
use app\common\model\CashNumberLog;


class Apiorder extends AdminBase
{

    public function index($limit=15)
    {
        $da=input();
		if ($this->request->isAjax()){
			$where[]=['tmporder','<>','0'];
			$map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
			if(isset($da['st']) && isset($da['se']) && !empty($da['st']) && !empty($da['se'])){
				$map=[$da['st'], $da['se']];
			}
			if(!empty($da['name']) && !empty($da['kk'])){
			   if($da['kk']=="card_no" || $da['kk']=="card_key"){
			       $value=Orders::enCardno($da['name']);
				   $where[]=[$da['kk'],'=',$value];
				}elseif($da['kk']=='shopid') {
                    $uid=User::where(['shopid'=>$da['name']])->find()['id'];
                    $where[]=['uid','=',$uid];
                }else{
                    $where[]=[$da['kk'],'=',$da['name']];
                }
			}
			if(!empty($da['state'])){
    			   switch($da['state']){
    			        case 1:
    			            $where[]=['state','=',0];
    			            break;
    			        case 2:
    			            $where[]=['state','in','1'];
    			            break;
    			         case 3:
    			            $where[]=['state','in','2,8'];
    			            break;
    			         case 4:
    			            $where[]=['state','in','3,7'];
    			            break;
    			          case 5:
    			            $where[]=['state','=','4'];
    			            break;
    			          case 6:
    			            $where[]=['ispei','>',0];
    			            break;
    			    }
			}
			if(!empty($da['classa'])){
				$where[]=['class','=',$da['classa']];
			}
            if(isset($da['timetype']) && $da['timetype']=="off"){
                $timename="chulitime";
                $orderby="chulitime desc";
            }else{
                $timename="create_time";
                $orderby="id desc";
            }
            $list=Orders::with(['bsLei','uname','opername'])->where($where)->whereTime($timename, 'between',$map)->order($orderby)->paginate($limit);
			foreach($list as $k=>$v){
				$list[$k]['remarks']=$v['remarks'];
                $list[$k]['uname']=$v['username']?:$v['shopid'];
				$list[$k]['atime']=date("Y-m-d",strtotime($v['create_time']))."</br>".date("H:i:s",strtotime($v['create_time']));
                $list[$k]['oper']= $list[$k]['oper']?:($v['type']==0?'站内消耗':"通道已删除");
                $list[$k]['getstate']=$v->getData('state');
			}
			$this->result($list);
		}
		$li=CardList::select();
		 if(isset($da['batchno'])){
            $da['tc']=1;
            $da['str']=$da['batchno'];
            $da['field']="batchno";
         }
         View::assign('da',$da);
        return $this->fetch('index',['id'=>session('admin_auth.admin_id'),'admin'=>Admin::where('id','>',1)->where(['status'=>1])->select(),'li'=>$li]);
    }
	public function qiang(){
		if ($this->request->isPost()) {
			$da=input("id");
			$ok=Orders::find()['qiang'];
			if($ok>0){
				$this->error('该单已经被抢，请重新选择');
			}else{
				(new Orders)->where(['id'=>$da])->update(['qiang'=>session('admin_auth.admin_id')]);
				$this->success('抢单成功，请操作',url('/Apiorder/index'));
			}
		}
	}
	
	public function setStatus(){
    	if ($this->request->isPost()){
    			$da=input();
    			$order=Orders::where(['id'=>$da['id']])->find();
    			if(!$order)return json(['code'=>-1,'msg'=>'当前订单不可操作']);
    			$type=isset($da['type'])?$da['type']:'';
    			$state=$order->getData('state');
    			try{
    			   switch($type){
                    case 'error':
                        $ok=successful($da['id'],3);
                        break;
                    case 'success':
                        $ok=successful($da['id']);
                        break;
                    default:
                        $ok=false;
                        if($order['amount']+$da['xitmoney']>$da['mianzi'])return json(['code'=>-1,'msg'=>'赔付金额+已结算金额不能大于成功面值']);
                        $order->settle_amt=$da['mianzi'];
                        $order->amount+=$da['xitmoney'];
                        $order->ispei=2;
                        $order->state=2;
                        $isok=$order->save();
                        if($isok){
                            $ok=User::where(['id'=>$order['uid']])->inc('money',(float)$da['xitmoney'])->update();
                            addlog($order['uid'],$da['xitmoney'],4,$order['orderno'],"[订单赔付]{$da['xitmoney']}");
                        }else{
                            return json(['code'=>-1,'msg'=>'更新数据失败']);
                        }
                      }
    				  $this->addNumberLog($order['orderno']);
    			  }catch (\Exception $e) {
    				  $this->error($e->getMessage());
    			  }
    			if($ok==true){
    				$this->success('赔付订单状态成功',url('/Apiorder/index'));
    			}else{
    				return json(['code'=>-1,'msg'=>'操作失败,错误原因:'.$ok]);
    			}
    		}
	}

    public function addNumberLog($order){
        $isk=CashNumberLog::where(['cardorder'=>$order])->order('id desc')->find();
        if($isk){
             CashNumberLog::where(['id',$isk['id']])->update(['cardmount'=>$isk['amount']]);
        }
    }

    public function shenhe(){
        $data=input();
        $res=Orders::find($data['id']);
        $res['classa']=CardList::where(['type'=>$res['class']])->value('title');
        $res['atype']=CardOperator::where(['id'=>$res['type']])->value('name');
        View::assign("res",$res);
        return view();
    }

    public function setRest(){
        if($this->request->isAjax()){
            $da=input();
            switch($da['type']){
                case 'ok':
                    $res=Orders::where(['id'=>$da['id'],'state'=>4])->find();
                    if($res){
                        $res->state=1;
                        $res->money=$da['mianzi'];
                        $res->remarks='';
                        $res->save();
                        $this->success("重审成功，卡密进入待冲区");
                    }else{
                        $this->error("重审失败，订单状态不允许该操作");
                    }
                    break;
                case 'err':
                    $res=Orders::where(['id'=>$da['id'],'state'=>4])->find();
                    if($res){
                        $res->state=3;
                        $res->remarks=$da['str'];
                        $res->save();
                        $this->success("重审成功,订单已失败");
                    }else{
                        $this->error("重审失败，订单状态不允许该操作");
                    }
                    break;
            }
        }
    }
	public function peifu(){
		$res=Orders::with('bsLei')->where(['id'=>input('id')])->find();
		$res['atype']=CardOperator::where(['id'=>$res['type']])->value('name');
		$res['classa']=CardList::where(['type'=>$res['class']])->value('title');
		View::assign('res',$res);
		View::assign('op',CardOperator::where(['type'=>0,'status'=>1])->select());
		return view();
	}
	
	public function findorder(){
		if ($this->request->isPost()) {
			$id=input('id');
			$order=Orders::find($id);
			$api=new Newapi($order['type']);
			$ok=$api->blindSearch($order);
			if($ok['code']==1){
				$this->success($ok['msg']);
			}else{
				$this->error($ok['msg']);
			}
		}
	}
    
	public function pic(){
		$id=input('id');
		return view('pic',['img'=>Orders::find($id)['card_no']]);
	}
	
	public function zhipai(){
		 if ($this->request->isPost()) {
			 $da=input();
			 $admin=Admin::find($da['uid']);
			 if($admin){
				 if($admin['status']==1){
					 $order=Orders::where([['id','in',$da['ids']],['qiang','=',0]])->select();
					 $map=[];
					 foreach($order as $k=>$v){
						 $map[$k]['id']=$v['id'];
						 $map[$k]['qiang']=$da['uid'];
					 }
					 (new Orders)->saveAll($map);
					 $this->success('指派成功');
				 }else{
					 $this->error("管理员状态异常");
				 }
			 }else{
				  $this->error("管理员不存在");
			 }
		 }
	}
	public function batchOrder(){
		 if ($this->request->isPost()) {
			 $da=input();
			 if($da['type']=='del'){
				 Db::name('order')->delete($da['ids']);
				 $this->success('删除成功');
			  }elseif($da['type']=="rest"){
				  $order=Orders::where([['id','in',$da['ids']],['state','in',[1,3]]])->select();
				  $count=count($da['ids']);
				  $ok=count($order);
				  foreach($order as $k=>$v){
				   \think\facade\Queue::push("app\home\job\Jobone@sendCard", ['id'=>$v],'sendCardJobQueue');
				  }
				  $this->success("本次共{$count}条订单，状态适合重试的订单{$ok}条，发布重试任务成功");
			  }elseif($da['type']=="success"){
			      $list=Orders::where('id','in',$da['ids'])->select();
			      $count=count($da['ids']);
			      $ok=0;
			      foreach($list as $k=>$v){
			           $state=$v->getData('state');
			           if($state!=2 && $state!=0){
					       successful($v['id']);
					       $ok++;
					  }
			      }
			      $this->success("本次共{$count}条订单，状态适合成功的订单{$ok}条，批量设置成功");
			  }else{
				  $this->error("参数错误");
			  }
		 }
	}

   

    public function del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            Orders::destroy(isset($param['id'])?$param['id']:$param['ids']);
            $this->success('删除成功');
        }
    }
	public function export()
    {
		$data=input();
		$map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
		if(isset($data['st']) && isset($data['se']) && !empty($data['st']) && !empty($data['se'])){
				$map=[$data['st'], $data['se']];
			}

        $where[]=['tmporder','<>','0'];
        if(!empty($da['name']) && !empty($da['kk'])){
            if($da['kk']=="card_no" || $da['kk']=="card_key"){
                $value=Orders::enCardno($da['name']);
                $where[]=[$da['kk'],'=',$value];
            }elseif($da['kk']=='shopid') {
                $uid=User::where(['shopid'=>$da['name']])->find()['id'];
                $where[]=['uid','=',$uid];
            }else{
                $where[]=[$da['kk'],'=',$da['name']];
            }
        }
		if(!empty($data['state'])){
		    switch($data['state']){
			        case 1:
			            $where[]=['state','=',0];
			            break;
			        case 2:
			            $where[]=['state','in','1'];
			            break;
			         case 3:
			            $where[]=['state','in','2,8'];
			            break;
			         case 4:
			            $where[]=['state','in','3,7'];
			            break;
			          case 5:
			            $where[]=['state','=','4'];
			            break;
			    }
		}
		if(!empty($data['classa'])){
			$where[]=['class','=',$data['classa']];
		}
        $listtotal=Orders::where($where)->whereTime('create_time', 'between',$map)->count();
        $title=['会员ID','用户名','系统订单号','卡类', '卡号', '卡密','提交金额','实际面值','结算金额','状态','提交IP','备注','提交时间','处理时间'];
        articleAccessLog($title,"点卡数据".date('Y-m-d'),$listtotal,$this,$where,$map);

    }

    public function getArticleAccessLog($where,$map,$page,$limit)
    {
        $list=Orders::with(['bsLei','uname'])->where($where)->whereTime('create_time', 'between',$map)->order('id desc')->page($page,$limit)->select();
        $map=[];
        foreach($list as $k=>$v){
            $map[$k][]=$v['shopid'];
            $map[$k][]=$v['username'];
            $map[$k][]=$v['orderno']."\t";
            $map[$k][]=$v['title'];
            $map[$k][]=$v['card_no']."\t";
            $map[$k][]=Orders::getCardno($v->getData('card_key'))."\t";
            $map[$k][]=$v['money'];
            $map[$k][]=$v['settle_amt'];
            $map[$k][]=$v['amount'];
            $map[$k][]=orderTyped($v->getData('state'));
            $map[$k][]=$v['ip'];
            $map[$k][]=$v['remarks']?$v['remarks']:'--';
            $map[$k][]=$v['create_time'];
            $map[$k][]=$v['update_time'];
        }
        return $map;
    }


	public function xiafa(){
		if ($this->request->isPost()) {
			$id=input('id');
			if(Orders::find($id)){
				 \think\facade\Queue::push("app\home\job\Jobone@sendPost", ['id'=>$id],'sendPostJobQueue');
				 return json(['code'=>1,'msg'=>'加入回掉队列成功，请等待回掉','url'=>'']);
			}else{
				return json(['code'=>-1,'msg'=>'参数错误']);
			}
		}
	}
	
	public function details(){
		$id=input('id');
		$res=Orders::with('bsLei')->where(['id'=>input('id')])->find();
		$res['atype']=CardOperator::where(['id'=>$res['type']])->value('name');
		$res['classa']=CardList::where(['type'=>$res['class']])->value('title');
		$res['guan']=Admin::where(['id'=>$res['qiang']])->value('username');
		View::assign('res',$res);
		return view();
	}
    
}
