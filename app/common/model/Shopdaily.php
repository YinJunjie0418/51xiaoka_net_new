<?php
namespace app\common\model;

use think\Model;


class Shopdaily extends Model
{
    protected $autoWriteTimestamp = true;
    protected $dateFormat = 'Y/m/d H:i:s';

    public function uname(){
        return $this->hasOne(User::class,'id','uid')->bind(['username','shopid','mobile','company']);
    }


}