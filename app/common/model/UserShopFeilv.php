<?php
namespace app\common\model;
use think\Model;

class UserShopFeilv extends Model{



    public static function onAfterInsert($data){
        $list=CardChannel::select();
        $map=[];
        foreach($list as $k=>$v){
           $map[$k]['bid']=$data['id'];
           $map[$k]['listid']=$v['listid'];
           $map[$k]['mianzhi']=$v['mianzhi'];
           $map[$k]['feilv']=$v['feilv'];
        }
        (new UserShopList)->saveAll($map);
    }

    public static function onAfterDelete($data){
        UserShopList::where(['bid'=>$data['id']])->delete();
    }
}
