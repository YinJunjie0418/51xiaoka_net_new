<?php
namespace app\common\model;

use think\Model;

class Totaldata extends Model{

    protected $autoWriteTimestamp = true;
    protected $dateFormat = 'Y/m/d H:i:s';
    protected $updateTime = false;
    protected $type = [

    ];

    public function addData($data){
        $isExis=self::whereDay('create_time')->find();
        if($isExis){
            return self::update($data,['id'=>$isExis['id']]);
        }else{
            return $this->save($data);
        }
    }
}
