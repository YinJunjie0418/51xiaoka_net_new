<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\User;
use app\common\model\Order;

class HangCard extends AdminBase{
    public function pressType($limit=15){
        if($this->request->isAjax()) {
            $da=input();
            $map[] = ['state', 'in', '0,1'];
            if (isset($da['Uid']) && !empty($da['Uid'])) {
                $uid = User::where(['shopid' => $da['Uid']])->value('id');
                $map[] = ['uid', '=', $uid];
            }
            if (isset($da['Name']) && !empty($da['Name'])) {
                $uid = User::where(['username|mobile' => $da['Name']])->value('id');
                $map[] = ['uid', '=', $uid];
            }
            $list = Order::with(['calssname.fenlei','calssname'])
                ->field('class,money,sum(if(state=0,money,0)) as amoney,sum(state=0) as ids,sum(if(state=1,money,0)) as lamoney,sum(state=1) as laids,min(create_time) as time')
                ->where($map)->group('class,money')
                ->order(null)
                ->paginate($limit, false, ['query' => request()->param()]);
            foreach($list as $k=>$v){
                $list[$k]['amoney']=$v['amoney']/10000;
                $list[$k]['lamoney']=$v['lamoney']/10000;
                $list[$k]['name']=$v['fenlei']['title'];
                $list[$k]['sc']=$v['ids']+$v['laids'];
                $list[$k]['sn']=sprintf("%.2f",$v['amoney']+$v['lamoney']);
                $list[$k]['time']=feng((int)(time()-$v['time']));
            }
            $this->result($list);
        }
        return view();
    }
    public function pressChannel($limit=15){
        if($this->request->isAjax()) {
            $da=input();
            $map[] = ['state', 'in', '0,1'];
            if (isset($da['Uid']) && !empty($da['Uid'])) {
                $uid = User::where(['shopid' => $da['Uid']])->value('id');
                $map[] = ['uid', '=', $uid];
            }
            if (isset($da['Name']) && !empty($da['Name'])) {
                $uid = User::where(['username|mobile' => $da['Name']])->value('id');
                $map[] = ['uid', '=', $uid];
            }
            $list = Order::with('opername')
                ->field('type,sum(if(state=0,money,0)) as money,sum(state=0) as ids,sum(if(state=1,money,0)) as lamoney,sum(state=1) as laids,min(create_time) as time')
                ->where($map)->group('type')
                ->order(null)
                ->paginate($limit, false, ['query' => request()->param()]);
            foreach ($list as $k => $v) {
                if($v['type']==0){
                    $list[$k]['oper']="站内消耗";
                }else{
                    $list[$k]['oper']=$v['oper']?:"通道已删除";
                }
                $list[$k]['money'] = $v['money'] / 10000;
                $list[$k]['lamoney'] = $v['lamoney'] / 10000;
                $list[$k]['name'] = $v['fenlei']['title'];
                $list[$k]['sc'] = $v['ids'] + $v['laids'];
                $list[$k]['sn'] = sprintf("%.2f", $v['money'] + $v['lamoney']);
                $list[$k]['time'] = feng((int)(time() - $v['time']));
            }
            $this->result($list);
        }
        return view();
    }
    public function pressShop($limit=15){
        if($this->request->isAjax()) {
            $da=input();
            $map[] = ['state', 'in', '0,1'];
            if (isset($da['Uid']) && !empty($da['Uid'])) {
                $uid = User::where(['shopid' => $da['Uid']])->value('id');
                $map[] = ['uid', '=', $uid];
            }
            if (isset($da['Name']) && !empty($da['Name'])) {
                $uid = User::where(['username|mobile' => $da['Name']])->value('id');
                $map[] = ['uid', '=', $uid];
            }
            $list = Order::with('uname')
                ->field('uid,sum(if(state=0,money,0)) as money,sum(state=0) as ids,sum(if(state=1,money,0)) as lamoney,sum(state=1) as laids,min(create_time) as time')
                ->where($map)->group('uid')
                ->order(null)
                ->paginate($limit, false, ['query' => request()->param()]);
            foreach ($list as $k => $v) {
                $list[$k]['money'] = $v['money'] / 10000;
                $list[$k]['lamoney'] = $v['lamoney'] / 10000;
                $list[$k]['name'] = $v['fenlei']['title'];
                $list[$k]['sc'] = $v['ids'] + $v['laids'];
                $list[$k]['sn'] = sprintf("%.2f", $v['money'] + $v['lamoney']);
                $list[$k]['time'] = feng((int)(time() - $v['time']));
            }
            $this->result($list);
        }
        return view();
    }

    public function estimate($limit=15){
        if($this->request->isAjax()) {
            $da=input();
            $map[] = ['state', 'in', '0,1'];
            if (isset($da['Uid']) && !empty($da['Uid'])) {
                $uid = User::where(['shopid' => $da['Uid']])->value('id');
                $map[] = ['uid', '=', $uid];
            }
            if (isset($da['Name']) && !empty($da['Name'])) {
                $uid = User::where(['username|mobile' => $da['Name']])->value('id');
                $map[] = ['uid', '=', $uid];
            }
            $list = Order::with(['calssname.fenlei','calssname'])
                ->field('class,money,sum(money) as sn,count(id) as sc,avg(feilv) as rate')
                ->where($map)->group('class,money')
                ->order(null)
                ->paginate($limit, false, ['query' => request()->param()]);
            foreach ($list as $k => $v) {
                $list[$k]['name'] = $v['fenlei']['title'];
                $list[$k]['sc'] = $v['sc'];
                $list[$k]['sn'] = $v['sn']/10000;
                $avgtime=Order::field("(chulitime - create_time) as time")->where([['state','>',1],['class','=',$v['class']],['money','=',$v['money']]])->order('id desc')->limit('8000')->select();
                $ttime=0;
                $iv=0;
                foreach ($avgtime as $item){
                    $ttime+=$item['time'];
                    $iv++;
                }
                $list[$k]['time'] = feng((int)($ttime/$iv));
                $list[$k]['rate']=sprintf("%.4f",$v['rate']);
                $list[$k]['hour']=Order::where('state','in','2,3,4,7,8,9')->where(['money'=>$v['money'],'class'=>$v['class']])->whereTime('chulitime','-1 hours')->count();
                $minutes=Order::where('state','in','2,3,4,7,8,9')->where(['money'=>$v['money'],'class'=>$v['class']])->whereTime('chulitime','-5 minutes')->count();
                $list[$k]['minutes']=$minutes;
                $list[$k]['Inventory']=feng($v['sc']/($minutes==0?1:$minutes)*5*60);
            }
            $this->result($list);
        }

        return view();
    }
}
