<?php
namespace app\command;


use app\common\model\BankPrcptcd;
use app\common\model\Order;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class GetAllBank extends command{
    protected function configure()
    {
        $this->setName('GetAllBank')
            ->addArgument('bankcode', Argument::OPTIONAL, "")
            ->setDescription('the regular generate data 10w');
    }

    protected function execute(Input $input, Output $output){
        $name = trim($input->getArgument('bankcode'));
        if($name){
            GetAllBank::sendPostData($name);
        }else{
            $bankl=openJson('bank');
            foreach ($bankl as $item){
                go(function()use($item){
                    GetAllBank::sendPostData($item['node']['bank_code']);
                });
            }
        }

    }

    public static function sendPostData($id) {
        $bankl=openJson('citylist');
        foreach($bankl as $item){
            echo "采集".$item['region']."地区\n";
            foreach ($item['regionEntitys'] as $v){
                go(function()use($v,$id,$item) {
                    $newBank = new \api\LianBank(['code' => $id, 'citycode' => $v['code']]);
                    $res = $newBank->getAllBankCode();
                    if ($res['ret_code'] == '0000') {
                        $parm = [];
                        foreach ($res['card_list'] as $k => $vv) {
                            $parm[$k]['bank_code'] = $id;
                            $parm[$k]['province']=$item['code'];
                            $parm[$k]['bankname'] = $vv['brabank_name'];
                            $parm[$k]['prcptcd'] = $vv['prcptcd'];
                            $parm[$k]['city_code'] = $v['code'];
                            $idd=BankPrcptcd::where(['prcptcd'=>$vv['prcptcd'],'city_code'=>$v['code']])->value('id');
                            if($idd){
                                $parm[$k]['id']=$idd;
                            }
                        }
                        (new BankPrcptcd)->saveAll($parm);
                    }else{
                        print_r($res);
                    }
                });
            }
        }
    }

}
