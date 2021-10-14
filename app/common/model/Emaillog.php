<?php
namespace app\common\model;

use think\Model;

class Emaillog extends Model{
    protected $autoWriteTimestamp = true;
    protected $dateFormat = 'Y/m/d H:i:s';
    protected $type = [
        'update_time'=>'timestamp',
        'shoplist'=>'array',
        'selllist'=>'array',
        'errmsg'=>'array'
    ];
}
