<?php
namespace app\common\model;
use think\Model;
use think\model\concern\SoftDelete;

class UserAuth extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
   
	
	public function uname()
    {
        return $this->hasOne("user", 'id','uid')->bind(['username','shopid']);
    }
}