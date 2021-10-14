<?php
namespace app\command;

use app\common\library\MongData;
use app\common\model\CashOrder;
use think\console\command;
use think\console\Input;
use think\console\Output;
use think\facade\Config;


class Psubscribe extends command{

    protected function configure()
    {
        $this->setName('psubsc')
            ->setDescription('the psubsc command');
    }

    protected function execute(Input $input, Output $output){
        $config=Config::get("queue.connections.redis");
        $redis = new \Redis();
        $redis->connect($config['host'], $config['port']);
        if($config['password'])$redis->auth($config['password']);
        $redis->config('SET','notify-keyspace-events','Ex');
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        $redis->psubscribe(array('__keyevent@0__:expired'), '\app\command\Psubscribe::keyCallback');
        go( function () {
            $this->loadOrder();
        });
    }

    public static function KeyCallback( $redis, $pattern, $chan, $msg ) {
        go( function ()use( $msg ) {
             Psubscribe::endTimeback($msg);
        } );
    }

    public static function endTimeback( $msg ){
        if(strstr($msg, 'C')===false)return false;
        echo "订单超时：".$msg."\n";
        $ok=(new CashOrder)->failed($msg,['msg'=>'超时失败'],5);
        (new MongData)->delCashorder($msg);
    }



}