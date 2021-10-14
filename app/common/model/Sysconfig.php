<?php
namespace app\common\model;
use think\Model;
use think\model\concern\SoftDelete;

class Sysconfig extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    
    protected $type = [

    ];
    
}