<?php
declare (strict_types = 1);

namespace app\api\model;

use app\common\library\MongData;
use app\common\library\MongoClass;
use app\common\model\CashOrderErr;
use app\common\model\Order;
use app\common\model\CardList;
use app\common\model\SellList;
use app\common\model\Totaldata;
use app\common\model\User;
use app\common\model\Card;
use think\facade\Cache;
use think\facade\Log;

class ApiBase{

    public static $param;
    public static $user;
    public static $sellList;
    public static $cardList;
    private static $bill=32;//话费类关联数据库
    private static $fuel=36;//加油充值类关联数据库
    private static $game=34;//游戏充值类关联数据库
    private static $video=37;//视频卡充值类关联数据库



    public function __construct()
    {
        self::$param=request()->param();
        if(request()->action()!='callback' &&  request()->action()!='webback'){
           try{
			   if(isset(self::$param['checkItemFacePrice']))self::$param['checkItemFacePrice']=(int)self::$param['checkItemFacePrice'];
                $Contenttype = request()->header('Content-Type');
                if(strstr($Contenttype,"application/x-www-form-urlencoded")===false) return ApiBase::show(99,'不支持的数据类型');
                validate(\app\api\validate\ApiPubCheck::class)->scene('pubcheck')->check(input());
                self::$user=User::where(['shopid'=>self::$param['customerId']])->find();
                if(!self::$user){
                    throw new \think\exception\HttpException(500, '商户不存在，获取商户密钥失败');
                }
               if(isset(self::$param['number'])){
                   $haoma=(new MongData)->getInterceptTel(self::$param['number'],self::$param['itemId']);
                   if(!$haoma){
                       throw new \think\exception\HttpException(500, '错误次数过多');
                   }
               }
                $ecsign=$this->CheckSign();
                if($ecsign!==true){
                     throw new \think\exception\HttpException(500, $ecsign);
                 }
            }catch (\Exception $e){
                $map['code']=-1;
                $map['message']=$e->getMessage();
                $map['data']=[];
                self::$param['remarks']=$e->getMessage();
                (new CashOrderErr)->addOrder(self::$param);
                Log::info("**************ApiBaseExit**************".$e->getMessage());
                echo json_encode(['code'=>-1,'msg'=>$e->getMessage(),'data'=>[]]);
                exit;
            }
        }
    }

    public static function show(int $code,string $msg,array $data=[]){
        $map['code']=$code;
        $map['message']=$msg;
        $map['data']=$data;
        //if(request()->middleware('isSync')){
            return json($map,200);
        //}else{
           // return xml($map,200);
        //}
    }

    public function CheckSign(){
        $ok=md5Verify(self::$param,self::$user['apikey'],self::$param['sign']);
        if(!$ok)return "SIGN错误";
        return true;
    }

    public static function getUser(string $field){
        return isset(self::$user[$field])?self::$user[$field]:false;
    }

    public  function payOrder(){
        try{
            $key=self::getUser('apides');
            $uid=self::getUser('id');
            $userfeilv=getUserFeilv($uid,self::$param['productCode'],self::$param['amount']);
            $da['custom']=isset(self::$param['custom'])?self::$param['custom']:"";
            $da['type']=CardList::where(['type'=>self::$param['productCode']])->value("tid");
            $da['class']=self::$param['productCode'];
            $da['source']=request()->url(true);
            $da['orderno']=build_order_no('F');
            $da['batchno']=self::$param['batchno'];
            $da['tmporder']=self::$param['orderId'];
            $da['mcode']=isset(self::$param['mcode'])?self::$param['mcode']:0;
            $da['uid']=$uid;
            $da['money']=self::$param['amount'];
            $da['ip']=request()->ip();
            $da['card_no']=$this->isdecode($key,self::$param['cardNumber']);
            $da['card_key']=$this->isdecode($key,self::$param['cardPassword']);
            $da['feilv']=$userfeilv;
            $da['notify']=self::$param['notify_url'];
            $addok=Order::create($da);
            Cache::inc('apiorder',1);
            MongoClass::addBatchno($da['batchno'],2,$uid,$da['class']);
            (new Totaldata)->addData(['apicount'=>['inc',1],'money'=>['inc',$da['money']]]);
            return ['code'=>1,'msg'=>'ok','data'=>$da];
        }catch (\think\Exception $e){
            trace($e->getMessage(), 'error');
            return ['code'=>7,'msg'=>'系统内部错误'];
        }
        
    }

    public function isdecode(string $key,string $str){
        $Card=New Card(bin2hex($key));
        $datano=$Card->decrypt($str);
        return $datano;
    }

    public function setSell($geway){
        self::$sellList=SellList::with('prolie')->where(['geway'=>$geway])->find();
        if(self::$sellList){
            self::$cardList=CardList::where(['id'=>self::$sellList['bindid']])->find();
        }
    }

    public function getSellType($geway,$field=''){
        if(!self::$sellList)return false;
        if($field)return self::$sellList[$field];
        return self::$sellList;
    }

    public function getScene(){
        $type=$this->getSellType(self::$param['itemId'],'cid');
        switch($type){
            case self::$bill:
                $scene='bill';
                break;
            case self::$fuel:
                $scene='fuel';
                break;
            case self::$game:
                $scene='game';
                break;
            case self::$video:
                $scene="video";
                break;
            default:
                return false;
        }
        return $scene;
    }

    public function getStatus(int $status){
        $str="appeal";
        switch($status){
            case 0:
            case 1:
                $str='wait';
                break;
            case 2:
                $str="success";
                break;
            case 3:
            case 5:
                $str="failed";
                break;
        }
        return $str;
    }

}
