<?php
namespace app\common\model;

use think\Model;

class UserFeilvModel extends Model{


    public static function onAfterInsert($data){
        $list=SellList::select();
        $arr=[];
        foreach ($list as $k=>$v){
            $arr[$k]['bid']=$data['id'];
            $arr[$k]['sellid']=$v['id'];
            $arr[$k]['rate']=$v['rate'];
        }
        (new UserFeilvList)->saveAll($arr);
    }

    public static function onAfterDelete($data){
        UserFeilvList::where(['bid'=>$data['id']])->delete();
    }
}
