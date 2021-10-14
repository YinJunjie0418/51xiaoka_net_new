<?php
namespace app\common\model;
use think\Model;

class BankPrcptcd extends Model{
    protected $autoWriteTimestamp = true;
    protected $dateFormat = 'Y/m/d H:i:s';

    public function protle()
    {
        return $this->hasOne(Banks::class, 'procode', 'bank_code')->bind(['bankName']);
    }
}
