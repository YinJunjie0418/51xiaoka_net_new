<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\BankPrcptcd;
use app\common\model\Banks;
use think\facade\View;

class Prcptcd extends AdminBase{
    protected $noAuth = ['getcity', 'citys'];

    public function index($limit=15){
        $da=input();
        if($this->request->isAjax()){
            $where[]=['bank_prcptcd.id','>',0];
            $cityArr=$this->getcity();
            if(isset($da['type']) && !empty($da['type']))$where[]=['bank_code','=',$da['type']];
            if(isset($da['name']) && !empty($da['name']))$where[]=['bankname','like',"%".$da['name']."%"];
            if(isset($da['citys']) && !empty($da['citys']))$where[]=['city_code','=',$da['citys']];
            $res=BankPrcptcd::withJoin('protle')->where($where)->order('id desc')->paginate($limit);
            foreach($res as $k=>$v){
                $res[$k]['diqu']=isset($cityArr[$v['city_code']])?$cityArr[$v['city_code']]:"--".$v['city_code'];
            }
            $this->result($res);
        }
        View::assign("da",$da);
        View::assign('info',Banks::field('bankName,procode')->select());
        View::assign("citys",openJson('citylist'));
        return view();
    }

    public function getcity(){
        $res=openJson('citylist');
        $map=[];
        foreach($res as $k=>$v){
            foreach($v['regionEntitys'] as $item){
                $map[$item['code']]=$v['region']."[".$item['region']."]";
            }
        }
        return $map;
     }
     public function citys(){
        if($this->request->isAjax()){
            $cityid=input('code');
            $res=openJson('citylist');
            $newArr=array_column($res,'regionEntitys','code');
            if(isset($newArr[$cityid])){
                $this->result($newArr[$cityid]);
            }else{
                $this->result([]);
            }
        }
     }
     public function adddata(){
        $da=input();
        if($this->request->isAjax()){
            if($da['id']){
                $ok=(new BankPrcptcd)->allowField(['bankname','bank_code','prcptcd','city_code','province'])->where('id',$da['id'])->update($da);
            }else{
                $ok=(new BankPrcptcd)->allowField(['bankname','bank_code','prcptcd','city_code','province'])->save($da);
            }
            if ($ok == true) {
                $this->success('操作成功',url('/Prcptcd/index'));
            } else {
                $this->error("操作失败");
            }
        }
        $info=Banks::field('bankName,procode')->select();
         View::assign("data",BankPrcptcd::where(['id'=>isset($da['id'])?$da['id']:0])->find());
         View::assign('info',$info);
         View::assign("citys",openJson('citylist'));
        return view('save');
     }

     public function del(){
         if ($this->request->isPost()) {
             $param = $this->request->param();
             BankPrcptcd::destroy($param['id'],true);
             $this->success('删除成功');
         }
     }
}