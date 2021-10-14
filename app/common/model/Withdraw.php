<?php
namespace app\common\model;
use think\Model;
use think\model\concern\SoftDelete;

class Withdraw extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
	protected $dateFormat = 'Y/m/d H:i:s';
	protected $type = [
	        
    ];
   
    public function preone(){
		
		return $this->hasOne('userbank','id','cid')->bind(['bankname','accounts','user','bankid'])->removeOption('soft_delete');;
	}
}