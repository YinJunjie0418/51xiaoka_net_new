<?php
namespace app\api\controller;

use app\api\lib\ActionQueue;
use app\api\lib\Recharge;
use app\api\model\ApiBase;
use app\common\library\MongData;
use app\common\model\CashOrder;
use app\common\model\CashOrderErr;
use app\common\model\CardOperator;
use app\common\model\User;


class UnicomMy extends ApiBase{


    public function queryBalance(){//查询余额
        $money=self::getUser('money');
        return ApiBase::show(1,'ok',['shopid'=>self::$param['customerId'],'money'=>$money,'statuc'=>'success']);
    }

    public function queryBizOrder(){//订单查询
        self::$param['ip']=request()->ip();
        trace(self::$param);
        try{
            validate(\app\api\validate\Unicommy::class)->scene('bizorder')->check(self::$param);
        }catch (\Exception $e){
            return self::show(-1,$e->getMessage());
        }
        $uid=User::where(['shopid'=>self::$param['customerId']])->value('id');
        $orderData=CashOrder::where(['tmporder'=>self::$param['orderno'],'uid'=>$uid])->find();
        if($orderData){
            $this->setSell($orderData['type']);
            $sellres=$this->getSellType(1);
            if(!$sellres)return self::show(-1,'产品错误');
            $parm['systemOrderId']=$orderData['orderno'];
            $parm['OrderId']=$orderData['tmporder'];
            $parm['number']=$orderData['number'];
            $parm['itemName']=$sellres['title']."[".$sellres['mianzhi']."]";
            $parm['itemid']=$orderData['type'];
            $parm['itemFacePrice']=$orderData['money'];
            if($orderData['state']==2){
                $parm['amount']=$orderData['price'];
                $parm['status']=$this->getStatus(2);
            }else{
                $parm['status']=$this->getStatus($orderData['state']);
                $parm['amount']=0;
            }
            return self::show(1,'ok',$parm);
        }else{
            return self::show(-1,'没有该订单');
        }
    }

   public function queryBuy(){//充值
        $params = self::$param;
        $params['ip']=request()->ip();
        //trace($params);
        try{
            $this->setSell($params['itemId']);
            $scene=$this->getScene();
            if($scene===false){
                throw new \think\exception\HttpException(500, "暂不支持该类型的充值");
            }
            validate(\app\api\validate\Unicommy::class)->scene($scene)->check($params);
        }catch (\Exception $e){
            return self::show(-1,$e->getMessage());
        }
        $params['customerId']=self::getUser('id');
        $mcash=(new MongData)->findCard($params['itemId'],$params['checkItemFacePrice'],$params['orderno']);
        if(!$mcash && $params['overtime']==0){
            $oper=Self::$sellList['operid'];
            $type=(new CardOperator)->where(['id'=>$oper])->value('type');
             if($type==5){
                $params['checkItemFacePrice']=$params['checkItemFacePrice']*$params['amt'];
                $params['state']=1;
                $oka=(new Recharge)->addentry($params);
                if($oka['code']==1){
                    $params['sysorder']=$oka['msg'];
                    $ok=\think\facade\Queue::push("app\home\job\Jobone@pCashOrder", $params,'pCashOrderQueue');
                    if($ok)return self::show(1,"接收成功");
                      return self::show(-1,"接收失败");
                }else{
                    $params['remarks']=$oka['msg'];
                    (new CashOrderErr)->addOrder($params);
                    return self::show(-1,$oka['msg']);
                }
            }else{
                 $params['remarks']='充值失败：暂无匹配卡密';
                (new CashOrderErr)->addOrder($params);
                return self::show(-1, '充值失败：暂无匹配卡密');
            }
        }elseif(!$mcash && $params['overtime']>0){
            $params['state']=6;
            $oka=(new Recharge)->addentry($params);
            if($oka['code']==1){
                cache($oka['msg'],'12',$params['overtime']);
                (new MongData)->addCashData(['id'=>$oka['id'],'orderno'=>$oka['msg'],'overtime'=>$params['overtime'],'cm'=>$params['itemId'].$params['checkItemFacePrice']]);
                return self::show(1,"接收成功");
            }else{
                $params['remarks']=$oka['msg'];
                (new CashOrderErr)->addOrder($params);
                return self::show(-1,$oka['msg']);
            }
        }else{
            $params['state']=1;
            $oka=(new Recharge)->addentry($params);
            if($oka['code']==1){
                $params['sysorder']=$oka['msg'];
                $ok=(new ActionQueue)->cashSend($params);
                if($ok)return self::show(1,"接收成功");
                return self::show(-1,"接收失败");
            }else{
                $params['remarks']=$oka['msg'];
                (new CashOrderErr)->addOrder($params);
                return self::show(-1,$oka['msg']);
            }
        }
    }

}