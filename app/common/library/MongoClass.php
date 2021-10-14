<?php
namespace app\common\library;

use think\facade\Db;

class MongoClass {
    public static function  addBatchno($batchno,$id,$uid,$class){
        if(Db::connect('mong')->table('order.orderbatchno')->where(['batchno'=>$batchno])->find())return true;
         return  Db::connect('mong')
            ->table('order.orderbatchno')->insert(['batchno'=>$batchno,'id'=>$id,'uid'=>$uid,'class'=>$class,'create_time'=>time()]);
    }

    public static function addAllBatchno($data){
        return Db::connect('mong')->table('order.orderbatchno')->insertAll($data);
    }

    public static function delAll(){
        return Db::connect('mong')->table('order.orderbatchno')->delete(true);
    }

    public static  function getAllData($where,$map,$limit){
        $total=Db::connect('mong')->table('order.orderbatchno')->where($where)->where('create_time', 'between',[strtotime($map[0]),strtotime($map[1])])
            ->select()->count();
        $data=Db::connect('mong')->table('order.orderbatchno')->where($where)->where('create_time', 'between',[strtotime($map[0]),strtotime($map[1])])
            ->order('create_time')->paginate($limit,$total);
        return $data;

    }

}
