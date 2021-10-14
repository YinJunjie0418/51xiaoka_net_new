<?php
namespace app\common\library;

use app\common\model\Neworder;
use app\common\model\Order;
use app\common\model\User;
use helper\RedisHelper;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

class MongData{
    public function addOrderData($data,$name){
        $map['uuid'] = $data['uid'];
        $map['cardno'] = Order::enCardno($data['card_no']);
        $map['cardkey'] =Order::enCardno($data['card_key']);
        $map['orderno'] = $data['orderno'];
        $map['feilv']=(float)$data['feilv'];
        $map['fields']=$name.$data['money'];
        $map['time']=time();
        $map['batchno']=$data['batchno'];
        if(isset($data['tmporder']))$map['tmporder']=$data['tmporder'];
        $ok = (new Neworder)->insert($map);
        Cache::inc($name.":".$data['money']);
        return $ok;
    }

    public function addCashData($data){
        $map['cm']=$data['cm'];
        $map['csid']=$data['id'];
        $map['orderno']=$data['orderno'];
        $map['overtime']=time()+$data['overtime'];
        $ok = Db::name('cashdata')->insert($map);
        return $ok;
    }

    public function addInterceptTel($tel,$num=1){
        $list=cache(date("Y-m-d"));
        if($list ){
            if(isset($list[$tel])){
                $list[$tel]+=$num;
            }else{
                $list[$tel]=$num;
            }
        }else{
            $list[$tel]=1;
        }
        cache(date("Y-m-d"),$list,24*60*60);
    }

    public function setCache($uid,$price,$type="inc"){
        $keys=$uid."money";
        if(Cache::has($keys)){
            $REDIS=new RedisHelper();
            while(true){
                if($REDIS->lock('moneylock',1,1)){
                    $hasmoney=Cache::get($keys);
                    switch($type){
                        case 'inc':
                            Cache::set($keys,$hasmoney+$price);
                            break;
                        default:
                            Cache::set($keys,$hasmoney-$price);
                    }
                    break;
                }
            }
			$REDIS->del('moneylock');
			return true;
        }else{
			return true;
		}
    }

    public function getInterceptTel($tel,$geway){
        $list=cache(date("Y-m-d"));
        if($list){
            if(isset($list[$tel])){
                if($list[$tel]>=cache($geway) && cache($geway)>0){
                    return false;
                }else{
                    return true;
                }
            }else{
                return true;
            }
        }else{
            return true;
        }
    }

    public function delCashorder($orderno){
        $isdel=Db::name('cashdata')->where(['orderno'=>$orderno])->delete();
        return $isdel;
    }

    public function findCard($class,$money,$orderno){
        return DB::name('neworder')->where(['fields'=>$class.$money,'tmporder'=>0])->order('priority desc,feilv asc,time asc')
            ->limit(1)->update(['tmporder'=>$orderno]);
    }

    public function stopData($orderno,$type=0){
        if($type==0){
            $batchno=Order::where(['orderno'=>$orderno])->value('batchno');
        }else{
            $batchno=$orderno;
        }
        $ok=(new Neworder)->where(['batchno'=>$batchno,'tmporder'=>0])->delete();
        cache($batchno,12);
        Log::info("暂停".$batchno.">>>".$ok);
        return true;
    }
    public function stopSingleData($orderno){
        $ok=(new Neworder)->where(['orderno'=>$orderno,'tmporder'=>0])->delete();
        Log::info("取消".$orderno.">>>".$ok);
        return true;
    }

    public function stopUserData($orderno,$uid){
        $ok=(new Neworder)->where(['batchno'=>$orderno,'uuid'=>$uid,'tmporder'=>0])->delete();
        Log::info("用户取消".$orderno.">>>".$ok);
        return $ok;
    }
    public function stopUserSingle($orderno,$uid){
        $ok=(new Neworder)->where(['orderno'=>$orderno,'uuid'=>$uid,'tmporder'=>0])->delete();
        Log::info("用户取消".$orderno.">>>".$ok);
        return $ok;
    }
    public function editMoney($da){
        $US=new User();
        $US->startTrans();
        try{
            $user=$US->where(['id'=>$da['id']])->lock(true)->find();
            if($user){
                $str=$da['type']=='money'?"金额":"授信额度";
                $ok=$US->where(['id'=>$da['id']])->inc($da['type'],(float)$da['money'])->update();
                if($ok){
                    addlog($user['id'],$da['money'],isset($da['typea'])?$da['typea']:5,isset($da['order'])?$da['order']:"",$da['money']>0?"增加{$str}{$da['money']}[{$da['remarks']}]":"扣除{$str}{$da['money']}[{$da['remarks']}]");
                    $US->commit();
                    return ['code'=>1,'msg'=>"操作成功"];
                }else{
                    $US->rollback();
                    return ['code'=>-1,'msg'=>"参数错误"];
                }
            }else{
                return ['code'=>-1,'msg'=>"参数错误"];
            }
        }catch (\Exception $e){
            return ['code'=>-1,'msg'=>$e->getMessage()];
        }

    }
}
