<?php
namespace app\command;


use app\common\model\Order;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Generate extends command{
    protected function configure()
    {
        $this->setName('generateData')
            ->setDescription('the regular generate data 10w');
    }

    protected function execute(Input $input, Output $output){
        $i=0;
        while(true){
            (new Generate)->Callback();
            $i++;
            sleep(1);
            if($i==1000)break;
        }

    }

    public  function Callback() {
        $order=build_order_no('D');
        $num=mt_rand(0,4);
        $arr=[20,30,50,100,200];
        $money=isset($arr[$num])?$arr[$num]:30;
        for($k=0;$k<1000;$k++){
            $map[$k]['uid']=65;
            $map[$k]['type']=0;
            $map[$k]['class']='888';
            $map[$k]['orderno']=build_order_no();
            $map[$k]['batchno']=$order;
            $map[$k]['money']=$money;
            $map[$k]['card_no']=rand_number(15);
            $map[$k]['card_key']=rand_number(19);
            $map[$k]['ip']="127.0.0.3";
            $map[$k]['feilv']=0.985;
            $map[$k]['create_time']=time();
        }
        (new Order)->insertAll($map);

        echo "end:".date("Y-m-d H:i:s").PHP_EOL;

    }




}
