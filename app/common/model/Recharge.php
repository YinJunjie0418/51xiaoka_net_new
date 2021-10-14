<?php
namespace app\common\model;

use think\Model;

class Recharge extends Model{
    protected $autoWriteTimestamp = true;
    protected $dateFormat = 'Y/m/d H:i:s';

    public function preone(){
        return $this->hasOne('payment','id','cid')->bind(['bankName','cs'=>'content']);
    }

    public function shopdata(){
        return $this->hasOne(User::class,'id','uid')->bind(['shopid','company']);
    }




}
