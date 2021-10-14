<?php
namespace app\command;

use app\common\model\CashOrder;
use app\common\model\MoneyLog;
use app\common\model\Order;
use app\common\model\Payment;
use app\common\model\Shopdaily;
use app\common\model\User;
use app\common\model\Withdraw;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Regular extends command{
    protected function configure()
    {
        $this->setName('regularData')
            ->setDescription('the regular user data');
    }

    protected function execute(Input $input, Output $output){
        Regular::Callback();
    }

    public static function Callback() {
        Payment::where('id','>',0)->update(['remamoney'=>0]);
        $isok=Shopdaily::whereDay('create_time')->find();
        if($isok)return true;
        User::chunk(1000, function ($users) {
            go( function ()use( $users ) {
               Regular::generatedata($users);
            });
        });
    }

    public static function  generatedata($users){
        $arr=[];
        $yesterday=date("y-m-d,23:59:59",strtotime('-1 day'));
        foreach($users as $k=>$user){
            $arr[$k]['uid']=$user['id'];
            $arr[$k]['type']=$user['type'];
            $money=MoneyLog::where(['uid'=>$user['id']])->whereTime('addtime','<',$yesterday)->order('id desc')->value('money');
            $arr[$k]['money']=$money?:0;
            if($user['type']==0){
                $arr[$k]['price']=Withdraw::where(['uid'=>$user['id'],'status'=>2])->whereDay('create_time','yesterday')->sum('money');
                $arr[$k]['loadmoney']=Withdraw::where([['uid','=',$user['id']],['status','in','0,1']])->whereDay('create_time','yesterday')->sum('money');
                $qudao=Withdraw::field('type,sum(money) as money')->where(['uid'=>$user['id']])->whereDay('create_time','yesterday')->group('type')->select();
                $str="";
                foreach($qudao as $item){
                    switch($item['type']){
                        case "alitype":
                          $str.="支付宝【{$item['money']}】</p>";
                        break;
                        case "banktype":
                           $str.="银连【{$item['money']}】</p>";
                        break;
                        default:
                           $str.="微信【{$item['money']}】</p>";
                    }
                }
                $arr[$k]['data']=$str;
                $arr[$k]['cardnum']=Order::where('uid',$user['id'])->whereDay('create_time','yesterday')->count();
                $arr[$k]['errcard']=Order::where('uid',$user['id'])->where('state','in','3,7')->whereDay('create_time','yesterday')->count();
            }else{
                $arr[$k]['price']=CashOrder::where(['uid'=>$user['id']])->whereDay('create_time','yesterday')->sum('money');
                $arr[$k]['loadmoney']=CashOrder::where([['uid','=',$user['id']],['state','=','2']])->whereDay('create_time','yesterday')->sum('price');
                $arr[$k]['addmoney']=MoneyLog::where([['uid','=',$user['id']],['type','in','5,8']])->whereDay('addtime','yesterday')->sum('price');
            }
        }
        (new Shopdaily)->saveAll($arr);
    }
}
