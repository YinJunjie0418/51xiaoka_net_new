<?php
namespace app\common\model;

use app\api\lib\ActionQueue;
use app\common\library\MongoClass;
use think\facade\Log;
use think\Model;


class Order extends Model
{

    protected $autoWriteTimestamp = true;
    protected $dateFormat = 'Y/m/d H:i:s';
    protected $type = [
        'chulitime'=>'timestamp'
    ];
    protected $table = 'hq_order';

    public function bsLei()
    {
        return $this->belongsTo(CardList::class,'class','type')->bind(['title','phoneRecycleIcon','tid'=>'id','iscode','isyzm']);
    }


    public function uname(){
        return $this->hasOne(User::class,'id','uid')->bind(['username','shopid','umoney'=>'money','yuti']);
    }

    public function opername(){
        return $this->hasOne(CardOperator::Class,'id','type')->bind(['oper'=>'name']);
    }

    public function calssname(){
        return $this->hasOne(CardList::Class ,'type','class')->bind(['title','fenlei']);
    }

    public function setCardNoAttr($value,$data)
    {
        $value=self::enCardno($value);
        return $value;
    }

    public function getTongzhiAttr($value){
        return htmlspecialchars($value);
    }

    public function setCardKeyAttr($value,$data)
    {
        $value=self::enCardno($value);
        return $value;
    }

    public function getCardNoAttr($v,$data)
    {
        $value=self::getCardno($v);
        if(!$value)$value=$v;
        return $value;
    }



    public function getCardKeyAttr($v,$data)
    {
        $auto=CardList::where(['type'=>$data['class']])->cache("cardlist",50)->find();
        $value=self::getCardno($v);
        if(!$value)$value=$v;
        if($auto['is_auto']!=0){
            $value='*********'.substr($value,-4);
        }
        return $value;
    }

    public function getStateAttr($value){
        return orderType($value);
    }


    public static function getCardno($cardno){//解密
        $card=new Card(env('aes.key'));
        $value=$card->decrypt($cardno);
        if(!$value)$value=$cardno;
        return $value;
    }

    public static function enCardno($cardno){//加密
        $card=new Card(env('aes.key'));
        $value=$card->encrypt($cardno);
        if(!$value)$value=$cardno;
        return $value;
    }

    public function huifu($useorder,$id){
        $order=Order::where(['orderno'=>$useorder])->find();
        if($order){
            $order->state=0;
            $oka=$order->save();
            $ok=(new Neworder)->where(['orderno'=>$useorder])->update(['tmporder'=>0]);
            if($oka && $ok){
                return true;
            }else{
                Log::info("恢复订单缓存失败".$useorder);
                return false;
            }
        }else{
			return true;
		}
    }


    public function setRest($da){
        $res=self::where(['orderno'=>$da['orderno'],'state'=>4])->find();
        switch($da['type']){
            case 'ok':
                if($res){
                    $this->startTrans();
                    try{
                        $isok=self::where('id',$res['id'])->update(['state'=>0]);
                        $ok=(new Neworder)->where(['orderno'=>$da['orderno']])->update(['tmporder'=>0]);
                        if($isok && $ok){
                            $this->commit();
                            return "重审成功，卡密进入待冲区";
                        }else{
                            $this->rollback();
                            return "重审失败，卡密进入待冲区失败";
                        }
                    }catch (\Exception $e){
                        $this->rollback();
                        return "重审失败，卡密进入待冲区失败";
                    }
                }else{
                    return "重审失败，订单状态不允许该操作";
                }
                break;
            case 'err':
                if($res){
                    $this->startTrans();
                    try{
                        $isok=self::where('id',$res['id'])->update(['state'=>3,'remarks'=>$da['str']]);
                        $ok=(new Neworder)->where(['orderno'=>$da['orderno']])->delete();
                        if($isok && $ok){
                            $this->commit();
                            return "重审成功,订单已失败";
                        }else{
                            $this->rollback();
                            return "重审失败，订单未删除";
                        }
                    }catch (\Exception $e){
                        $this->rollback();
                        return "重审失败，删除带充区卡密失败";
                    }
                }else{
                    return "重审失败，订单状态不允许该操作";
                }
                break;
        }
    }

    public static function checkApi($type,$classType){
        $class=CardOperator::where(['id'=>$type])->value('class');
        $className="\api\\".$class;
        if(class_exists($className) && method_exists($className,$classType)){
                return true;
        }else{
            return false;
        }
    }



}