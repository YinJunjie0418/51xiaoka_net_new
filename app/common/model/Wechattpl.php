<?php
namespace app\common\model;
use think\Model;
use think\model\concern\SoftDelete;

class Wechattpl extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $autoWriteTimestamp = true;
	protected $dateFormat = 'Y/m/d H:i:s';

   
}