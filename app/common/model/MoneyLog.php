<?php
namespace app\common\model;
use think\Model;


class MoneyLog extends Model
{

    
	protected $dateFormat = 'Y/m/d H:i:s';
	protected $type = [
	        'addtime'=>'timestamp'
    ];
    
   
    
}