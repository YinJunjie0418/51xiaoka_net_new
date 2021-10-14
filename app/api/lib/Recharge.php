<?php
namespace app\api\lib;

use app\api\model\ApiBase;
use app\common\library\MongData;
use app\common\model\CashNumberLog;
use app\common\model\CashOrder as CASH;
use app\common\model\Neworder;
use app\common\model\Order;
use app\common\model\SellList;
use app\common\model\SellUser;
use app\common\model\Totaldata;
use app\common\model\User;
use think\facade\Cache;
use app\api\lib\ActionQueue;

class Recharge {
    public function handleCash($data){
        try{
            trace($data,'log');
            $map=['orderno'=>$data['orderno'],'state'=>0];
            if(isset($data['isben']))$map=['orderno'=>$data['orderno'],'state'=>3];
            $id=CashNumberLog::where($map)->value("id");
        }catch (\Exception $e){
            trace($e->getMessage(),'error');
            return false;
        }
        $CASHLOG=new CashNumberLog();
        $CASHLOG->startTrans();
        try{
            $res=$CASHLOG->where(['id'=>$id])->lock(true)->find();
            if($res){
                switch ($data['code']){
                    case 0:
                         $ok=CASH::update(['state'=>4,'remarks'=>$data['msg']],['orderno'=>$res['tmporder']]);
                         $oka=Order::update(['state'=>4,'remarks'=>'审核中'],['orderno'=>$res['cardorder']]);
                         $okb=CashNumberLog::update(['state'=>3,'remarks'=>$data['msg']],['id'=>$res['id']]);
						 if($ok && $oka && $okb){
							 $CASHLOG->commit();
                              return true;
						 }else{
							 $CASHLOG->rollback();
                              return false;
						 }
                        break;
                    case 1:
                        $money=$data['actualCardPrice']/1000;
                        $res->state=1;
                        $res->price=$money;
                        $res->remarks=$data['msg'];
                        $res->voucher=isset($data['voucher'])?$data['voucher']:'';
                        $res->ext=isset($data['ext'])?$data['ext']:'';
                        $res->feeamount=isset($data['feeAmount'])?$data['feeAmount']:0;
                        $ok=$res->save();
                        $parm=['orderno'=>$res['tmporder'],'cardorder'=>$res['cardorder'],'data'=>$data,'money'=>$res['money'],'moneyb'=>$res['money'],'uid'=>$res['carduid'],'type'=>0];
                        $oka=(new ActionQueue)->cashBack($parm);
                        if($ok && $oka){
                            $CASHLOG->commit();
                            return true;
                        }else{
                            $CASHLOG->rollback();
                            return false;
                        }
                        break;
                    case 2:
                        $money=$data['actualCardPrice']/1000;
                        $res->state=1;
                        $res->remarks=$data['msg'];
                        $res->price=$money;
                        $res->voucher=isset($data['voucher'])?$data['voucher']:'';
                        $res->ext=isset($data['ext'])?$data['ext']:'';
                        $res->feeamount=isset($data['feeAmount'])?$data['feeAmount']:0;
                        $ok=$res->save();
                        $parm=['orderno'=>$res['tmporder'],'cardorder'=>$res['cardorder'],'data'=>$data,'money'=>$res['money'],'moneyb'=>$money,'uid'=>$res['carduid'],'type'=>1];
                        $oka=(new ActionQueue)->cashBack($parm);
                        $okb=(new MongData)->stopData($res['cardorder']);
                        cache("bigcard",1,20);
                        if($ok && $oka && $okb){
                            $CASHLOG->commit();
                            return true;
                        }else{
                            $CASHLOG->rollback();
                            return false;
                        }
                        break;
                    case 3:
                        $res->state=1;
                        $res->remarks=$data['msg'];
                        $res->price=$data['actualCardPrice']/1000;
                        $res->feeamount=isset($data['feeAmount'])?$data['feeAmount']:0;
                        $res->save();
                        $ok=successful($res['cardorder'],8,0,0,0,'实际金额小于申明金额，实际金额['.$data['actualCardPrice']/1000 .']');
                        $oka=(New MongData)->stopData($res['cardorder']);
                        cache("bigcard",2,20);
                        $okb=\think\facade\Queue::push("app\home\job\Jobone@editCashOrder", $res,'editCashOrderQueue');
                        if($ok && $oka && $okb){
                            $CASHLOG->commit();
                            return true;
                        }else{
                            $CASHLOG->rollback();
                            return false;
                        }
                        break;
                    default:
                        if($data['code']!= -3){
                            $res->state=2;
                            $res->remarks=$data['msg'];
                            $res->feeamount=isset($data['feeAmount'])?$data['feeAmount']:0;
                            $ok=$res->save();
                            if(isset($data['mcode'])){
                                switch($data['mcode']){
                                    case 80://充值号码错误
                                        $oka=(new CASH)->failed($res['tmporder'],$data);
                                        $okb=(new Order)->huifu($res['cardorder'],$res['tmporder']);
                                        //$okb=Order::update(['state'=>0],['orderno'=>$res['cardorder']]);
                                        break;
                                    case 81://当日禁止提交
                                        $oka=CASH::update(['state'=>4,'remarks'=>'充值失败,提交次数过多'],['orderno'=>$res['tmporder']]);
                                        $okb=(new Order)->huifu($res['cardorder'],$res['tmporder']);
                                       // $okb=Order::update(['state'=>0],['orderno'=>$res['cardorder']]);
                                        break;
                                    case 82:
                                    case 83://卡密错误
                                        $oka=successful($res['cardorder'],3,0,0,0,$data['msg']);
                                        $okb=\think\facade\Queue::push("app\home\job\Jobone@editCashOrder", $res,'editCashOrderQueue');
                                        //$okb=$this->Reissued($res['tmporder']);
                                        break;
                                    case 84:
                                    case 87://可以再次提交
                                        $oka=(new Order)->huifu($res['cardorder'],$res['tmporder']);
                                        $okb=\think\facade\Queue::push("app\home\job\Jobone@editCashOrder", $res,'editCashOrderQueue');
                                      //  $okb=$this->Reissued($res['tmporder']);
                                        break;
                                }
                                if($ok && $oka && $okb){
                                    $CASHLOG->commit();
                                    return true;
                                }else{
									trace("记录操作失败".$ok.">>".$oka.">>".$okb,'error');
                                    $CASHLOG->rollback();
                                    return false;
                                }
                            }else{
                                $oka=(new CASH)->failed($res['tmporder'],$data);
                                $okb=(new Order)->huifu($res['cardorder'],$res['tmporder']);
                                if($ok && $oka && $okb){
                                    $CASHLOG->commit();
                                    return true;
                                }else{
                                    $CASHLOG->rollback();
                                    return false;
                                }
                            }
                        }
                }
            }
            $CASHLOG->commit();
            return false;
        }catch(\Exception $e){
            $CASHLOG->rollback();
            trace($e->getMessage(),'error');
            return false;
        }
    }

     public function Reissued($order,$cards=''){
        try{
            $result=CASH::withCount('numbers')->where(['orderno'=>$order])->find();
            $user=User::where(['id'=>$result['uid']])->find();
            $unum=isset($user->num) ? $user->num : 0;
            if($result->numbers_count<$unum){
                $map['itemId']=$result['type'];
                $map['overtime']=$result['overtime']>0?$result['overtime']-time():0;
                $map['customerId']=$result['uid'];
                $map['number']=$result['number'];
                $map['checkItemFacePrice']=$result['money'];
                if(!$cards){
                    $cardok=(new MongData)->findCard($result['type'],$result['money'],$order);
                    if(!$cardok){
                        $request['code']=-1;
                        $request['msg']="重试无匹配卡密";
                        $request['state']=3;
                        $request['orderno']=$order;
                        $ok=(new CASH)->updateOrder($request);
                        if(!$ok){
                            trace("重试更新订单失败",'error');
                        }
                        return true;
                    }
                    $cards=(new Neworder)->where(['tmporder'=>$order])->find();
                    (new Order)->where(['orderno' => $cards['orderno']])->update(['state' => 1]);
                }
                $map['sysorder']=$order;
                $map['state']=1;
                $map['useorder']=$cards['orderno'];
                $map['cardno']=Order::getCardno($cards['cardno']);
                $map['cardkey']=Order::getCardno($cards['cardkey']);
                $map['carduid']=$cards['uuid'];
                $map['feilv']=$result['feilv'];
                $map['price']=$result['price'];
                $dai=new \app\api\lib\Cashorder($map);
                $ok=$dai->firestCash();
                if($ok!==true){
                    if($ok['code']==2){
                        Order::update(['state'=>3,'remarks'=>$ok['msg']],['orderno'=>$map['useorder']]);
                        (new Recharge())->Reissued($order);
                    }else{
                        (new order)->huifu($map['useorder'],$order);
                    }
                }
            }else{
                $request['code']=-1;
                $request['msg']="重试". $unum ."次失败";
                $request['state']=3;
                $request['orderno']=$order;
                $ok=(new CASH)->updateOrder($request);
                if(!$ok){
                    trace("重试更新订单失败2",'warning');
                }
            }
            return true;
        }catch (\Exception $e){
            trace("重试更新订单失败".$e->getMessage(),'error');
            return false;
        }
    }
    
     public function psendData($data){
        try{
            $map = $data;
            $map['state'] = 1;
            $dai = new \app\api\lib\Cashorder($map);
            $ok = $dai->firestCash();
            trace($ok);
        }catch (\Exception $e){
            trace($e->getMessage(),'error');
            return false;
        }
    }

    public function sendData($data){
        try{
            $cards = (new Neworder)->where(['tmporder' => $data['orderno']])->find();
            (new Order)->where(['orderno' => $cards['orderno']])->update(['state' => 1]);
            $map = $data;
            $map['state'] = 1;
            $map['useorder'] = $cards['orderno'];
            $map['cardno'] = Order::getCardno($cards['cardno']);
            $map['cardkey'] = Order::getCardno($cards['cardkey']);
            $map['carduid'] = $cards['uuid'];
            $dai = new \app\api\lib\Cashorder($map);
            $ok = $dai->firestCash();
            if ($ok !== true) {
                if ($ok['code'] == 2) {
                    $okd = (new Order)->where(['orderno' => $map['useorder']])->update(['state' => 3, 'remarks' => $ok['msg']]);
					(new ActionQueue)->backorder($map['useorder']);
                    (new Neworder)->where(['id' => $cards['id']])->delete();
                    if (!$okd) {
                        trace("操作订单" . $map['useorder'] . "失败" . json_encode($ok),'warning');
                    }
                    (new Recharge())->Reissued($data['orderno']);
                } else {
                    (new Order)->huifu($map['useorder'], $cards['id']);
                }
            }
        }catch (\Exception $e){
            trace($e->getMessage(),'error');
            return false;
        }
    }

    public function addentry($data){
        $feilv=$this->getFeilv($data['itemId'],$data['customerId']);
        $orderid=build_order_no('C');
        $decmoney=sprintf('%.4f',$data['checkItemFacePrice']*$feilv);
        trace($data['customerId'].">>".$decmoney);
        if(isset($data['decimal'])){
            if($data['decimal']!=$decmoney){
                (new Neworder)->where(['tmporder'=>$data['orderno']])->update(['tmporder'=>0]);
                $res=['code'=>-1,'msg'=>'销售价格错误'];
                return $res;
           }
        }
        $USER=new User();
        $USER->startTrans();
        try{
            $bres=(new User)->field('money,xin')->where(['id'=>$data['customerId']])->lock(true)->find();
            if($bres){
                $occupymoney=$this->occupymoney($data['customerId']);
                $umoney=$bres['money']+$bres['xin']-$occupymoney;
                trace($data['customerId'].">>".$umoney);
                if($umoney<$decmoney){
                    if((new Neworder)->where(['tmporder'=>$data['orderno']])->find()){
                        $oka=(new Neworder)->where(['tmporder'=>$data['orderno']])->update(['tmporder'=>0]);
                        if(!$oka){
                            trace("订单释放失败：".$data['orderno'],'warning');
                            $USER->rollback();
                            $res=['code'=>-1,'msg'=>'系统错误'];
                            return $res;
                        }
                    }
                    $res=['code'=>-1,'msg'=>'余额不足'];
                    $USER->commit();
                    return $res;
                }else{
                    (new MongData)->setCache($data['customerId'],$decmoney);
                    $USER->commit();
                }
            }else{
                trace("查找用户失败：".$data['orderno'],'log');
                $res=['code'=>-1,'msg'=>'余额不足'];
                $USER->rollback();
                return $res;
            }
        }catch (\Exception $e){
            $USER->rollback();
            trace($e->getMessage(),'error');
            return $res=['code'=>-1,'msg'=>'系统错误'];
        }
        $map=$data;
        $map['sysorder']=$orderid;
        $map['feilv']=$feilv;
        $map['price']=$decmoney;
        $ok=(new CASH)->addOrder($map);
        if(!$ok){
            (new Neworder)->where(['tmporder'=>$data['orderno']])->update(['tmporder'=>0]);
            (new MongData)->setCache($data['customerId'],$decmoney,'dec');
            $res=['code'=>-1,'msg'=>'入库失败'];
            return $res;
        }
        (new Totaldata)->addData(['cashnum'=>['inc',1],'cashmoney'=>['inc',$data['checkItemFacePrice']]]);
        return ['code'=>1,'msg'=>$orderid,'id'=>$ok->id];
    }

    private function occupymoney($uid){
        $isok=Cache::has($uid."money");
        if($isok){
            return Cache::get($uid."money");
        }else{
            $money=CASH::where('state','in','0,1,4,6')->where('uid',$uid)->sum('price');
            Cache::set($uid."money",$money);
            return $money;
        }
    }

    public function getFeilv(string $sellida,int $uid){
        $sellid=ApiBase::$sellList['id'];
        if(!$sellid)$sellid=SellList::where(['geway'=>$sellida])->value('id');
        $rate=SellUser::where(['uid'=>$uid,'sellid'=>$sellid])->value('rate');
        return $rate;
    }
}