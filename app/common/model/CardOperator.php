<?php
namespace app\common\model;
use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;

class CardOperator extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $type = [
	      'fields'=>'array',
		  'content'=>'array'
    ];
   

}