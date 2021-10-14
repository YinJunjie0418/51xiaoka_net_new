<?php
namespace app\common\model;

use app\api\lib\ActionQueue;
use app\api\lib\Recharge;
use app\common\library\MongData;
use think\facade\Log;
use think\Model;
use GuzzleHttp\Client;


class CashOrder extends Model
{

    protected $autoWriteTimestamp = true;
    protected $dateFormat = 'Y/m/d H:i:s';

    public function binduname(){
        return $this->hasOne(User::class,"id",'uid')->bind(['username','mobile','shopid']);
    }

    public function bindmsg(){
        return $this->hasOne(SellList::class,"geway",'type')->bind(['title','mianzhi']);
    }

    public function getNotifymsgAttr($value){
        return htmlspecialchars($value);
    }
    
    public function getRemarksAttr($value){
        return htmlspecialchars($value);
    }



    public function addOrder($data)
    {
       try{
                $res = SellList::with('prolie')->where(['geway' => $data['itemId']])->find();
                $class = CardList::where(['id' => $res['bindid']])->value('type');
                $map['uid'] = $data['customerId'];
                $map['tmporder'] = $data['orderno'];
                $map['orderno'] = $data['sysorder'];
                $map['price']=$data['price'];
                $map['type'] = $data['itemId'];
                $map['class'] = $class;
                $map['money'] = $data['checkItemFacePrice'];
                $map['number'] = $data['number'];
                $map['state'] = isset($data['state']) ? $data['state'] : '0';
                $map['feilv'] = $data['feilv'];
                $map['remarks'] = isset($data['remarks']) ? $data['remarks'] : "";
                $map['ip'] = $data['ip'];
                $map['notify_url'] = $data['notify_url'];
                $map['overtime'] = $data['overtime'] ? time() + $data['overtime'] : 0;
                $map['ext1'] = isset($data['ext1']) ? $data['ext1'] : '0';
                $map['ext2'] = isset($data['ext2']) ? $data['ext2'] : '0';
                $map['ext3'] = isset($data['ext3']) ? $data['ext3'] : '0';
                return $this->create($map);
        }catch (\Exception $e){
           trace("入库话单失败".$e->getMessage(),'error');
           return false;
        }
    }


     public function updateOrder($data)
    {
         try{
            return $this->failed($data['orderno'],$data,$data['state']);
         }catch (\Exception $e){
             trace($e->getMessage(),'error');
             return true;
        }
    }


    public function successOrder($order,$data)
    {
        $id=self::where([['orderno','=',$order],['state','in','1,4']])->value('id');
        $this->startTrans();
        try{
            $res=self::where(['id'=>$id])->lock(true)->find();
            if($res){
                $user=User::field('shopid,apikey,id')->find($res['uid']);
                $okaa=User::where(['id'=>$res['uid']])->dec('money',$res['price'])->update();
                $log=CashNumberLog::with('getfeilv')->field('cardorder,cardno')->where(['tmporder'=>$order,'state'=>1])->find();
                $ordeRate=$log['feilv'];
                $card=$log['cardno'];
                $profit=sprintf('%.2f',$res['money']*($res['feilv']-$ordeRate));
                $res->notifyok=1;
                $res->state=2;
                $res->remarks=isset($data['msg'])?$data['msg']:"";
                $res->profit=$profit;
                $ok=$res->save();
                $data['url']=$res['notify_url'];
                $data['data']=[
                    'customerId'=>$user['shopid'],
                    'orderno'=>$order,
                    'tmporder'=>$res['tmporder'],
                    'number'=>$res['number'],
                    'code'=>1,
                    'cardno'=>$card,
                    'voucher'=>isset($data['voucher'])?$data['voucher']:'',
                    'ext'=>isset($data['ext'])?$data['ext']:'',
                    'money'=>$res['money'],
                    'message'=>"处理成功",
                    'amount'=>sprintf('%.2f',$res['money']*$res['feilv'])];
                $data['key']=$user['apikey'];
                $oku=(new ActionQueue)->cashorderback($data);
                $okf=CashNumberLog::where(['tmporder'=>$order])->update(['reamount'=>$res['price'],'cardmount'=>sprintf('%.2f',$res['money']*$ordeRate)]);
                if($ok && $oku && $okaa && $okf){
					(new MongData)->setCache($res['uid'],$res['price'],'dec');
                    addlog($user['id'],$res['price'],7,$res['orderno'],"[充值扣费]{$res['price']}");
                    $this->commit();
                    return $res['price'];
                }else{
                    $this->rollback();
                    return false;
                }
            }else{
                $this->rollback();
                return false;
            }
        }catch (\Exception $e){
            $this->rollback();
            trace($e->getMessage(),'error');
            return false;
        }finally{
            (new Totaldata)->addData(['cashprofit'=>['inc',$profit],'cashoknum'=>['inc',1],'cashokmoney'=>['inc',$res['money']]]);
        }
    }


     public function failed($order,$data,$state=3){
        $id=self::where([['orderno','=',$order],['state','in','0,1,4,6']])->value('id');
        $this->startTrans();
        try{
            $res=self::where(['id'=>$id])->lock(true)->find();
            if($res){
                $user=User::find($res['uid']);
                $incmoney=$res['price'];
                $res->state=$state;
                $res->remarks=$data['msg'];
                $res->price=0;
                $res->notifyok=1;
                $ok=$res->save();
                $data['url']=$res['notify_url'];
                $data['data']=[
                    'customerId'=>$user['shopid'],
                    'orderno'=>$order,
                    'tmporder'=>$res['tmporder'],
                    'code'=>-1,
                    'cardno'=>"",
                    'voucher'=>'',
                    'ext'=>'',
                    'number'=>$res['number'],
                    'money'=>$res['money'],
                    'amount'=>0,
                    'message'=>$data['msg']
                ];
                $data['key']=$user['apikey'];
                (new MongData)->addInterceptTel($res['number']);
                $oku=(new ActionQueue)->cashorderback($data);
                $okb=(new Totaldata)->addData(['casherrnum'=>['inc',1],'casherrmoney'=>['inc',$res['money']]]);
                if($ok && $oku && $okb){
					(new MongData)->setCache($res['uid'],$incmoney,'dec');
                    $this->commit();
                    return true;
                }else{
                    $this->rollback();
                     trace("话单修改失败".$order."state:".$ok."??".$oku."??".$okb,'error');
                    return false;
                }
            }else{
                $this->rollback();
                trace("订单不存在>>>".$order,'error');
                return false;
            }
        }catch (\Exception $e){
            $this->rollback();
            trace($e->getMessage(),'error');
            return false;
        }
    }

    public function sendPostNotify($url,$data,$key){
        try{
            if(empty($url))return true;
            $sign=md5Verify($data,$key);
            $data['sign']=$sign;
            trace($data,'sign');
            $response=(new Client())->request("post",$url,['form_params'=>$data]);
            $msg=$response->getBody()->getContents();
            return empty($msg)?"未知返回":$msg;
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

    public function getCash($data){
        $id = CashOrder::where(['class' => $data['class'], 'money' => $data['money'], 'state' => 6])->order("overtime asc")->value('id');
        $this->startTrans();
        try{
            $res = CashOrder::where(['id'=>$id])->lock(true)->find();
            if($res) {
                $res->state=1;
                $res->save();
                $arr['orderno']=$data['orderno'];
                $arr['card_no']=$data['card_no'];
                $arr['card_key']=$data['card_key'];
                $arr['uid']=$res['uid'];
                (new Recharge)->Reissued($res['orderno'],$arr);
                cache($res['orderno'],null);
                $this->commit();
                return true;
            }else{
                $this->rollback();
                return false;
            }
        }catch (\Exception $e){
            $this->rollback();
            return false;
        }
    }

    public function numbers()
    {
        return $this->hasMany(CashNumberLog::class, 'tmporder', 'orderno');
    }



}
