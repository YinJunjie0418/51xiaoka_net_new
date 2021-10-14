<?php
namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

class User extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
	protected $dateFormat = 'Y/m/d H:i:s';
	protected $type = [
	        'last_login_time'=>'timestamp'
    ];
    
    public static function onAfterInsert($user){//新增后事件
		UserRate::addRate($user['id']);
		SellUser::addRate($user['id']);
	}
	
	public static function onBeforeInsert($data){//新增前
		$u=self::where(['shopid'=>$data['shopid']])->find();
		if($u){
			$data['shopid']=self::order('id desc')->value('shopid')+1;
		}
	}

	public static function onAfterDelete($da){
        SellUser::where(['uid'=>$da['id']])->delete();
        UserRate::where(['uid'=>$da['id']])->delete();
    }
	
	public static function onBeforeUpdate($user){//更新前事件
		$u=self::find($user['id']);
		if(($user['rategroup']!=$u['rategroup'] || $u['type']!=$user['type']) && $user['type']==0 && $user['rategroup']!=0){
            (new UserRate)->gengxin($user['rategroup']);
        }
		if(($user['rategroup']!=$u['rategroup'] || $u['type']!=$user['type']) && $user['type']==1 && $user['rategroup']!=0){
            (new SellUser)->gengxin($user['rategroup']);
        }

	}


    public function setPasswordAttr($value)
    {
        return md6($value);
    }
	public function setTradepwdAttr($value){
		
		return md6($value);
	}
	
    public function userReal()
    {
        return $this->belongsTo("userAuth", 'id','uid');
    }

    public function realto(){
        return $this->hasOne(UserAuth::class,'uid','id')->bind(['clas','name','company_name']);
    }

    public function getNumAttr($value,$data)
    {
        return UserFeilvModel::where(['id'=>$data['rategroup']])->value('num');
    }

    public function adduser($type='qq',$data){
        try{
            if($type=='wx'){
               $openid=empty($data['unionid'])?$data['openid']:$data['unionid'];
            }else{
                $openid=$data['openid'];
            }
            $user=$this->where(['qqopenid|wxopenid|unionid'=>$openid])->find();
            if(!$user){
                if($type=='qq'){
                    $data['qqopenid']=$data['openid'];
                }else{
                    $data['wxopenid']=isset($data['openid'])?$data['openid']:'';
                    $data['unionid']=isset($data['unionid'])?$data['unionid']:'';
                    $data['wxopenidg']=isset($data['wxopenidg'])?$data['wxopenidg']:'';
                }
                $data['shopid'] = $this->order('id desc')->value('shopid')+1;
                $data['password']=$data['openid'];
                return $this->allowField(['wxopenid','qqopenid','wxopenidg','unionid','shopid','username','password','assets'])->save($data);
            }
        }catch (\Exception $e){
            logt($e->getMessage());
        }

    }

    public function setMoney($uid,$money,$order,$type=7,$price=0,$remarks=''){
        $decok=false;
        $this->startTrans();
        try{
            $bres=self::field('money,xin,yuti')->where(['id'=>$uid])->lock(true)->find();
            if($bres && $bres['money']+$bres['xin']+getsale($bres['yuti'])>=$money){
                if($type!=7 && $bres['money']+getsale($bres['yuti'])<$money){
                    $this->rollback();
                    return false;
                }
                $ok=self::where(['id'=>$uid])->dec('money',$money)->update();
                if(!$ok){
                    $this->rollback();
                    return false;
                }else{
                    $decok=true;
                    $this->commit();
                }
            }else{
                $this->rollback();
                return false;
            }
        }catch(\Exception $e){
            $this->rollback();
            return false;
        }
        if($decok){
            switch($type){
                case 7:
                    addlog($uid,$money,$type,$order,"[充值扣费]{$money}");
                    break;
                case 1:
                    $cha=(int)$money-(int)$price;
                    addlog($uid,-(int)$money,1,$order,"[商户结算]{$cha}");
                    break;
                case 4:
                    addlog($uid,-$money,4,$order,$remarks);
                    break;
            }
        }
        return $decok;
    }


    public function addMoney($uid,$ufeilv,$orderno,$remarks,$type=2){
        $this->startTrans();
        try{
            $bres=self::where(['id'=>$uid])->lock(true)->find();
            if($bres){
                $ok=self::where(['id'=>$uid])->inc('money',(float)$ufeilv)->update();
                if($ok){
                    addlog($uid,$ufeilv,$type,$orderno,$remarks);
                    $this->commit();
                    return true;
                }
            }
            $this->rollback();
            return false;
        }catch (\Exception $e){
            $this->rollback();
            return false;
        }

    }

}