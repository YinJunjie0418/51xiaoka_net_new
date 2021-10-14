<?php
declare (strict_types = 1);

namespace app\merchants\controller;
use app\common\controller\IndexBase;
use app\common\model\CardList;
use app\common\model\CashOrder;
use app\common\model\SellList;
use app\common\model\SellUser;


class Card extends IndexBase
{

    public function topay(){
        $da=input();
        if(request()->isPost()){
            if(cookie('isan')){
                return json(['confirm'=>['width'=>'350', 'prompt'=> "warning"],'content'=>"请不要频繁操作",'list'=> [],'type'=>"2"]);
            }else{
                cookie('isan',1,1);
            }
        }
        try{
            $order=build_order_no('D');
            $ip=request()->ip();
            $uid=session("user_auth.user_id");
            $res = SellList::with('prolie')->where(['id' => $da['cardid']])->find();
            $moneyArr=SellList::field('mianzhi')->where(['title'=>$da['title']])->select()->toArray();
            $newArr=array_column($moneyArr,'mianzhi');
            $class = CardList::where(['id' => $res['bindid']])->value('type');
            $urate=SellUser::where(['uid'=>$uid,'sellid'=>$da['cardid']])->value('rate');
            if(!$urate){
                return json(['confirm'=>['width'=>'350', 'prompt'=> "warning"],'content'=>"获取用户费率失败，请联系管理员",'list'=> [],'type'=>"2"]);
            }
            switch($da['type']){
                case 0://批量提交
                    $data=str_replace("\r\n",',',$da['cardlist']);
                    $data=str_replace("\n",',',$data);
                    $data=str_replace("\r",',',$data);
                    $list=explode(',',$data);
                    $err=[];
                    $errnum=0;
                    $map=[];
                    foreach($list as $k=>$v){
                        $ok=$this->yanka($v);
                        if(is_array($ok) && is_numeric($ok[1])){
                            $map[$k]['uid']=$uid;
                            $map[$k]['type']=$res['geway'];
                            $map[$k]['class']=$class;
                            $map[$k]['orderno']=build_order_no();
                            $map[$k]['money']=$res['mianzhi']*$ok[1];
                            $map[$k]['price']=$res['mianzhi']*$ok[1]*$urate;
                            $map[$k]['number']=$ok[0];
                            $map[$k]['state']=1;
                            $map[$k]['feilv']=$urate;
                            $map[$k]['ip']=$ip;
                            $map[$k]['overtime']=0;
                            $map[$k]['create_time']=time();
                        }else{
                            $ok="充值金额不在面值范围【".$ok[0]."-".$ok[1]."】";
                            $err[$k]=$ok;
                            $errnum++;
                        }
                    }
                    $count=count($list);
                    if($errnum>0){
                        $msg=['confirm'=> ['width'=>400, 'prompt'=> "info"],'num'=>$count,'content'=>"共提交{$count}张卡卷,有{$errnum}条记录有错误",'list'=>$err,'state'=>2,'type'=>1];
                    }else{
                        (new CashOrder)->insertAll($map);
                        $i=0;
                        foreach($map as $k=>$v){
                            if($i%3==0){
                                \think\facade\Queue::push("app\home\job\Jobone@sendCach", $v,'sendCashJobQueue');
                            }elseif($i%2==0) {
                                \think\facade\Queue::push("app\home\job\Jobone@sendCach", $v,'sendCashJobQueueb');
                            }else{
                                \think\facade\Queue::push("app\home\job\Jobone@sendCach", $v,'sendCashJobQueuec');
                            }
                            $i++;
                        }
                        $msg=["state"=>1,"type"=>2,'num'=>$count,"content"=>"共提交{$count}条充值订单,请在充值记录查看进度！","list"=>$err,"confirm"=>["name"=>"提交成功","prompt"=>"success","width"=>400,"time"=>5,"url"=>"reload"]];
                    }
                    return json($msg);
                    break;
                case 1://文件提交
                    $list=$this->getXls($da['cardlist']);
                    if(!is_array($list))return $list;
                    $str='';
                    $no=0;
                    $map=[];
                    foreach($list as $k=>$v){
                        if(empty($v[0]) || !is_numeric($v[1])){
                            $no++;
                            $str.="[第{$k}条数据账户为空或充值金额不正确]</br>";
                           }else{
                            $map[$k]['uid']=$uid;
                            $map[$k]['type']=$res['geway'];
                            $map[$k]['class']=$class;
                            $map[$k]['orderno']=build_order_no();
                            $map[$k]['money']=$v[1];
                            $map[$k]['price']=$v[1]*$urate;
                            $map[$k]['number']=$v[0];
                            $map[$k]['state']=1;
                            $map[$k]['feilv']=$urate;
                            $map[$k]['ip']=$ip;
                            $map[$k]['overtime']=0;
                            $map[$k]['create_time']=time();  
                           }
                    }
                    (new CashOrder)->insertAll($map);
                        $i=0;
                        foreach($map as $k=>$v){
                            if($i%3==0){
                                \think\facade\Queue::push("app\home\job\Jobone@sendCach", $v,'sendCashJobQueue');
                            }elseif($i%2==0) {
                                \think\facade\Queue::push("app\home\job\Jobone@sendCach", $v,'sendCashJobQueueb');
                            }else{
                                \think\facade\Queue::push("app\home\job\Jobone@sendCach", $v,'sendCashJobQueuec');
                            }
                            $i++;
                        }
                        $num=count($map);
                        if($no>0){
                            return  json(['confirm'=> ['width'=>400, 'prompt'=> "info"],'content'=>"成功提交{$num}条充值订单</br>".$str,'list'=>[],'state'=>2,'type'=>1]);
                        }else{
                            $msg=["state"=>1,"type"=>2,"content"=>"成功提交{$num}条充值订单,请在充值记录查看进度！","list"=>[],"confirm"=>["name"=>$str,"prompt"=>"success","width"=>400,"time"=>5,"url"=>"reload"]];
                            return json($msg);
                        }
                break;
                default:
                    return json(['code'=>-1,'msg'=>'参数错误']);
            }
        }catch (\Exception $e){
            return json(['code'=>-1,'msg'=>$e->getMessage()]);
        }
    }

    public function yanka($str){
        $data=str_replace(" ",',',$str);
        $str=explode(",",$data);
        $spr=array_values(array_filter($str));
        if(count($spr)==1){
            $spr[1]=1;
        }
        return $spr;
    }
    
    public function getXls($filename){
        $fileName=root_path('public').$filename;
        if (!file_exists($fileName)) {
             return json(['confirm'=> ['width'=>400, 'prompt'=> "info"],'content'=>"参数错误，请联系管理员",'list'=>[],'state'=>2,'type'=>1]);
        }
        try{
            $objPHPxls=\PhpOffice\PhpSpreadsheet\IOFactory::load($fileName);
            $sheet = $objPHPxls->getActiveSheet();
            $res = array();
            foreach($sheet->getRowIterator(2) as $row) {
                $tmp = array();
                foreach ($row->getCellIterator() as $cell) {
                    $tmp[] = $cell->getFormattedValue();
                }
                $res[] =array_values(array_filter($tmp));
            }
            return $res;
        }catch (\Exception $e){
            return json(['confirm'=> ['width'=>400, 'prompt'=> "info"],'content'=>$e->getMessage(),'list'=>[],'state'=>2,'type'=>1]);
        }

    }

}
