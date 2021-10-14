<?php
namespace app\common\model;
use think\facade\Config;
use think\Model;
use think\model\concern\SoftDelete;

class Userbank extends Model
{
	use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
	protected $dateFormat = 'Y/m/d H:i:s';
	protected $type = [
	        'delete_time'=>'timestamp'
    ];
    public function img(){
		return $this->hasOne('banks','id','bankid')->bind(['logo','logo1']);
	}
	
	public function userto(){
		return $this->hasOne('user','id','uid')->bind(['username','mobile','shopid']);
	}
}