<?php
namespace app\common\model;

use think\Model;


class CashNumberLog extends Model
{
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
    protected $dateFormat = 'Y/m/d H:i:s';
    protected $type = [
        'update_time' => 'timestamp',
    ];
    
     public function types()
    {
        return $this->hasOne(SellList::class, 'geway', 'type');
    }

    public function beuid(){
         return $this->hasOne(User::class,'id','uid')->bind(['auid'=>'shopid','company']);
    }
    public function beouid(){
        return $this->hasOne(User::class,'id','carduid')->bind(['ouid'=>'shopid','username']);
    }

    public function bindoper(){
        return $this->hasOne(CardOperator::Class,'id','operid')->bind(['oper'=>'name']);
    }

    public function gettitle()
    {
        return $this->hasOne(SellList::class, 'geway', 'type')->bind(['title']);
    }

    public function getfeilv(){
        return $this->hasOne(Order::class,'orderno','cardorder')->bind(['feilv']);
    }

    
}
