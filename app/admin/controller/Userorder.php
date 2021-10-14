<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\library\MongData;
use app\common\library\MongoClass;
use app\common\model\Neworder;
use app\common\model\SellList;
use app\common\model\SellListRate;
use app\common\model\Order as Orders;
use app\common\model\CardList;
use app\common\model\User;
use app\common\model\CardOperator;


class Userorder extends AdminBase
{
    public function index($limit=15)
    {
		if ($this->request->isAjax()){
			$da=input();
            $where[]=['id','>',0];
            $mong=true;
			$map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
			if(isset($da['st']) && isset($da['se']) && !empty($da['st']) && !empty($da['se'])){
				$map=[$da['st'], $da['se']];
			}
			if(!empty($da['name']) && !empty($da['kk'])){
			    if($da['kk']=='batchno') $mong=false;
				if($da['kk']=="card_no" || $da['kk']=="card_key"){
                        $mong=false;
                        $da['name']=Orders::enCardno($da['name']);
						$where[]=[$da['kk'],'=',$da['name']];
				}elseif($da['kk']=='shopid') {
				    $uid=User::where(['shopid'=>$da['name']])->value('id');
                    $where[]=['uid','=',$uid];
                }else{
                     $where[]=[$da['kk'],'=',$da['name']];
                 }
			}
			if(!empty($da['classa'])){
				$where[]=['class','=',$da['classa']];
			}
			
			if(!$mong){
			    $map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
                $list=Orders::with(['bsLei','uname','opername'])->field('id,tmporder,feilv,type,uid,class,priority,batchno,money,create_time,sum(state>1) as sunum,sum(state=3) as errnum,count(id) as ids')->where($where)->whereTime('create_time', 'between',$map)->group('batchno')->order('id desc')->paginate($limit);
                foreach($list as $k=>$v){
                    $list[$k]['jindu']=sprintf("%.4f",(int)$v['sunum']/$v['ids'])*100 ."%";
                    $list[$k]['cuo']=sprintf("%.4f",(int)$v['errnum']/$v['ids'])*100 ."%";
                    $list[$k]['shopid']=$v['shopid'];
                    $list[$k]['progress']=(int)$v['sunum']!=$v['ids']?0:1;
                    $list[$k]['price']='--';
                    $list[$k]['sus']=cache($v['batchno'])?1:0;
                    $list[$k]['ids']=$v['ids'];
                    $list[$k]['tmporder']=$v['tmporder'];
                    if($v['type']==0){
                        $list[$k]['oper']="站内消耗";
                    }else{
                        $list[$k]['oper']=$v['oper']?:"通道已删除";
                    }
                }
                $this->result($list);
            }else{
			    $lists=MongoClass::getAllData($where,$map,$limit);
			    $list=$lists->toArray();
			    $map=[];
			    foreach ($list['data'] as $k=>$v){
			        $order=Orders::with(['bsLei','uname','opername'])->field('id,tmporder,feilv,type,uid,class,priority,batchno,money,create_time,sum(state>1) as sunum,sum(state=3) as errnum,count(id) as ids')
                        ->where('batchno','=',$v['batchno'])->group('batchno')->select()->toArray();
                    $orderlist=$order[0];
                    $map[$k]['feilv']=$orderlist['feilv'];
                    $map[$k]['batchno']=$v['batchno'];
                    $map[$k]['create_time']=$orderlist['create_time'];
                    $map[$k]['title']=$orderlist['title'];
                    $map[$k]['class']=$orderlist['class'];
                    $map[$k]['priority']=$orderlist['priority'];
                    $map[$k]['money']=$orderlist['money'];
                    $map[$k]['jindu']=sprintf("%.4f",$orderlist['sunum']/$orderlist['ids'])*100 ."%";
                    $map[$k]['cuo']=sprintf("%.4f",$orderlist['errnum']/$orderlist['ids'])*100 ."%";
                    $map[$k]['shopid']=$orderlist['shopid'];
                    $map[$k]['progress']=(int)$orderlist['sunum']!=$orderlist['ids']?0:1;
                    $map[$k]['price']='--';
                    $map[$k]['tmporder']=$orderlist['tmporder'];
                    $map[$k]['sus']=cache($v['batchno'])?1:0;
                    $map[$k]['ids']=$orderlist['ids'];
                    $map[$k]['type']=$orderlist['type'];
                    if($orderlist['type']==0){
                        $map[$k]['oper']="站内消耗";
                    }else{
                        $map[$k]['oper']=$orderlist['oper']?:"通道已删除";
                    }
                }
                $list['data']=$map;
                $this->result($list);
            }

		}
		$li=CardList::select();
        return $this->fetch('index',['li'=>$li,'operlist'=>CardOperator::where(['type'=>0,'status'=>1])->select()]);
    }

    public function cancel(){
        $number = 0;
        if(input('order')){
            $batch=input("order");
			$order=Orders::field('uid,type')->where('batchno','=',$batch)->find();
			$umoney=User::where(['id'=>$order['uid']])->value("money");
			if($umoney<0){
				$this->error('该用户预提金额未补齐,取消失败');
			} 
			if($order['type']!=0){
				 if(!Orders::checkApi($order['type'],'revokeData'))$this->error('该接口不支持取消功能');
                 $ok=\think\facade\Queue::push("app\home\job\Jobone@getAdress", ['id'=>'cancelOrder','batchno'=>$batch,'type'=>$order['type']],'ipJobQueue');
            }else{
                 $ok = (new MongData)->stopData($batch, 1);
            }
            if($ok){
                if($order['type']!=0){
                    $number=Orders::where('batchno','=',$batch)->where('state','in','0,1')->count();
                }else {
                    $number = Orders::where('batchno', '=', $batch)->where('state', '=', '0')->update(['state' => 3, 'chulitime' => time(), 'remarks' => '[' . session('admin_auth.username') . ']取消订单']);
                }
                $this->success('预计取消: '.$number.'笔订单，暂时只支持本站消耗取消订单');
            }else{
                $this->error('取消订单失败，联系技术看看什么问题');
            }

        }else if(input("ids")){
            $ids=input("ids");
            $list = Orders::where('id','in',$ids)->where('state','in','0,1')->field('orderno,uid,type')->select();
            $oknumber=0;
            foreach($list as $item){
				$number++;
				$umoney=User::where(['id'=>$item['uid']])->value("money");
				if($umoney>=0) {
                    if ($item['type'] != 0) {
                        if(Orders::checkApi($item['type'], 'revokeData')){
                            $ok = \think\facade\Queue::push("app\home\job\Jobone@getAdress", ['id' => 'cancelOrder', 'orderno' => $item['orderno'], 'type' =>$item['type']], 'ipJobQueue');
                        }else{
                            $ok=false;
                        }
                    } else {
                        if($item['state']==0){
                            $ok = (new MongData)->stopSingleData($item['orderno']);
                        }else{
                            $ok=false;
                        }
                    }
					if($ok)$oknumber++;
				}
            }
            $this->success('预计取消: '.$number.'笔订单;成功发送:'.$oknumber.'笔');
        }
    }

    public function editpriority(){
        if($this->request->isAjax()){
            $order=input('order');
            $num=input('priority');
            if(is_numeric($num) && $num>=0 && $num<101){
               // try{
                  //  $norder=new Neworder();
                  //  $norder->startTrans();
                    $res=Neworder::where(['batchno'=>$order])->update(['priority'=>$num]);
                    $resok=Orders::where(['batchno'=>$order])->update(['priority'=>$num]);
                    if($res && $resok){
                      //  $norder->commit();
                        $this->success("设置成功，影响{$res}条数据");
                    }else{
                      //  $norder->rollback();
                        $this->error("设置失败,只能影响站内消耗卡的优先级");
                    }
               // }catch (\Exception $e){
                  //  $norder->rollback();
                  //  $this->error("设置失败".$e->getMessage());
                //}
            }else{
                $this->error("请填写0-100的数字");
            }

        }
    }

    public function restmongo(){
        if($this->request->isAjax()){
            MongoClass::delAll();
            $ok=\think\facade\Queue::push("app\home\job\Jobone@getAdress", ['id'=>'batchno'],'ipJobQueue');
           if($ok){
               $this->error("正在执行批次号重建，请稍后刷新");
            }else{
                $this->error("重建失败");
            }
        }
    }
    
    public function suspend(){
        if($this->request->isAjax()){
            $da=input('order');
            $class=input("class");
            $money=input('money');
            if(cache($da)){
                cache($da,null);
                $sellid=CardList::where(['type'=>$class])->value('id');
                $bind=SellListRate::field('listid')->where(['bindid'=>$sellid])->select();
                $binds=[];
                foreach($bind as $item){
                   $binds[]=$item['listid'];
                }
                $name=SellList::where([['id','in',$binds],['mianzhi','=',$money]])->value('geway');
                $orderlist=Orders::where(['batchno'=>$da,'state'=>0])->select();
                $num=0;
                $i=0;
                foreach ($orderlist as $k=>$v){
                    if($v['type']==0){
                        $map['card_key']=Orders::getCardno($v->getData('card_key'));
                        $map['card_no']=Orders::getCardno($v->getData('card_no'));
                        $map['uid']=$v['uid'];
                        $map['orderno']=$v['orderno'];
                        $map['feilv']=$v['feilv'];
                        $map['batchno']=$da;
                        $map['money']=$v['money'];
                        $ok=(new MongData)->addOrderData($map,$name);
                        $num++;
                    }else{
                        if($i%3==0){
                            \think\facade\Queue::push("app\home\job\Jobone@sendCard", $v,'sendCardbJobQueue');
                        }elseif($i%2==0) {
                            \think\facade\Queue::push("app\home\job\Jobone@sendCard", $v,'sendCardcJobQueue');
                        }else{
                            \think\facade\Queue::push("app\home\job\Jobone@sendCard", $v,'sendCardJobQueue');
                        }
                        $i++;
                    }
                }
			  $this->success("重启任务成功");
            }else{
                cache($da,12);
                (new MongData)->stopData($da,1);
                $this->success("暂停任务成功");
            }
            
        }
    }
    
    public function editfeilv(){
        if($this->request->isAjax()){
            $da=input();
            $num=Orders::where(['batchno'=>$da['order']])->update(['feilv'=>$da['feilv']]);
            $this->success("成功设置{$num}笔订单");
        }
    }
    
    public function switchChannel(){
        if($this->request->isAjax()){
            $da=input('idOrder');
            $data=explode("|",$da);
            if(count($data)<2)$this->error("参数错误");
            $channel=$data[0];
            $order=$data[1];
            $class=$data[2];
            $result=Orders::where(['batchno'=>$order])->where('state','in','0,1,3,7')->select();
            $num=0;
            if($channel==0){
                $sellid=CardList::where(['type'=>$class])->value('id');
                $bind=SellListRate::field('listid')->where(['bindid'=>$sellid])->select();
                $binds=[];
                foreach($bind as $item){
                   $binds[]=$item['listid'];
                }
                foreach ($result as $k=>$v){
                     $name=SellList::where([['id','in',$binds],['mianzhi','=',$v['money']]])->value('geway');
                    $map[$k]['uuid'] = $v['uid'];
                    $map[$k]['cardno'] = $v->getData('card_no');
                    $map[$k]['cardkey'] =$v->getData('card_key');
                    $map[$k]['orderno'] = $v['orderno'];
                    $map[$k]['feilv']=(float)$v['feilv'];
                    $map[$k]['fields']=$name.$v['money'];
                    $map[$k]['time']=time();
                    $map[$k]['batchno']=$v['batchno'];
                    $num++;
                }
                 $ok = (new Neworder)->saveAll($map);
                 if($ok){
                     Orders::where(['batchno'=>$order])->where('state','in','0,1,3,7')->update(['type'=>0,'state'=>0]);
                 }
            }else{
                 foreach($result as $k=>$v){
                    $map=$v->toarray();
                    $map['card_no']=$v->getData('card_no');
                    $map['card_key']=$v->getData('card_key');
                    \think\facade\Queue::push("app\home\job\Jobone@sendCard", $map,'sendCardJobQueue');
                    $num++;
                }
                Orders::where(['batchno'=>$order])->where('state','in','0,3,7')->update(['type'=>$channel,'state'=>1]);
                Neworder::where(['batchno'=>$order])->delete();
            }
            $this->success("成功切换{$num}笔订单");
        }
    }
    
}
