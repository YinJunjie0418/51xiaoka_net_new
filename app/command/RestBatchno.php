<?php
namespace app\command;

use app\common\library\MongoClass;
use app\common\model\Order as Orders;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class RestBatchno extends command{
    protected function configure()
    {
        $this->setName('restaddbatchno')
            ->setDescription('the rest add batchno');
    }

    protected function execute(Input $input, Output $output){
        RestBatchno::Callback();
    }

    public static function Callback() {
        $num=0;
        $ok=Orders::field('batchno,create_time,id,uid,class')->group('batchno')->chunk(1000, function($list) use ($num) {
            $arr=[];
            foreach($list as $k=>$v){
                if(Db::connect('mong')->table('order.orderbatchno')->where('batchno',$v['batchno'])->find())continue;
                $arr[$k]['batchno']=$v['batchno'];
                $arr[$k]['id']=$v['id'];
                $arr[$k]['uid']=$v['uid'];
                $arr[$k]['class']=$v['class'];
                $arr[$k]['create_time']=$v->getData('create_time');
            }
            $num+=count($arr);
            RestBatchno::restdata($arr);
        });
        echo "本次写入".$num;
    }

    public static function restdata($list){
        try{
            MongoClass::addAllBatchno($list);
        }catch (\Exception $e){
            echo $e->getMessage();
        }
    }
}