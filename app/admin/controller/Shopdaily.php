<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Shopdaily as Daily;
use app\common\model\User;

class Shopdaily extends AdminBase{
     protected $noAuth = ['export'];
    
    
    public function index($limit=15){
        if($this->request->isAjax()){
            $da=input();
            $da['STime']=isset($da['STime'])?(!empty($da['STime'])?$da['STime']:"2010-01-12 00:00:00"):"2010-01-12 00:00:00";
            $da['ETime']=isset($da['ETime'])?(!empty($da['ETime'])?$da['ETime']:date("Y-m-d 23:59:59")):date("Y-m-d 23:59:59");
            $map[]=['type','=',0];
            if(isset($da['Uid']) && !empty($da['Uid'])){
                $shopid=User::where(['shopid'=>$da['Uid']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            if(isset($da['Name']) && !empty($da['Name'])){
                $shopid=User::where(['username|mobile'=>$da['Name']])->find();
                $map[]=['uid','=',$shopid['id']];
            }

            $total=Daily::where($map)->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])->count();
            $list=Daily::with('uname')->where($map)->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])->order('id desc')->paginateX(['list_rows'=>$limit]);
            foreach($list as $k=>$v){
                $list[$k]['username']=$v['username']?:$v['mobile'];
                $list[$k]['uptime']=date("Y-m-d",strtotime($v['create_time']));
            }
            $list=$list->toArray();
            $list['total']=$total;
            $this->result($list);
        }
        return view();
    }
    public function selldata($limit=15){
        if($this->request->isAjax()){
            $da=input();
            $da['STime']=isset($da['STime'])?(!empty($da['STime'])?$da['STime']:"2010-01-12 00:00:00"):"2010-01-12 00:00:00";
            $da['ETime']=isset($da['ETime'])?(!empty($da['ETime'])?$da['ETime']:date("Y-m-d 23:59:59")):date("Y-m-d 23:59:59");
            $map[]=['type','=',1];
            if(isset($da['Uid']) && !empty($da['Uid'])){
                $shopid=User::where(['shopid'=>$da['Uid']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            if(isset($da['Name']) && !empty($da['Name'])){
                $shopid=User::where(['username|mobile'=>$da['Name']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            $total=Daily::where($map)->count();
            $list=Daily::with('uname')->where($map)->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])->order('id desc')->paginateX(['list_rows'=>$limit]);
            foreach($list as $k=>$v){
                $list[$k]['username']=$v['company']?:($v['username']?:$v['mobile']);
                $list[$k]['uptime']=date("Y-m-d",strtotime($v['create_time']));
            }
            $list=$list->toArray();
            $list['total']=$total;
            $this->result($list);
        }
        return view();
    }

    public function export()
    {
        $da=input();
        $da['STime']=isset($da['STime'])?(!empty($da['STime'])?$da['STime']:"2010-01-12 00:00:00"):"2010-01-12 00:00:00";
        $da['ETime']=isset($da['ETime'])?(!empty($da['ETime'])?$da['ETime']:date("Y-m-d 23:59:59")):date("Y-m-d 23:59:59");
        $map[]=['type','=',$da['type']];
        if(isset($da['Uid']) && !empty($da['Uid'])){
            $shopid=User::where(['shopid'=>$da['Uid']])->find();
            $map[]=['uid','=',$shopid['id']];
        }
        if(isset($da['Name']) && !empty($da['Name'])){
            $shopid=User::where(['username|mobile'=>$da['Name']])->find();
            $map[]=['uid','=',$shopid['id']];
        }
        
        $listtotal=Daily::where($map)->whereTime('create_time', 'between',[$da['STime'],$da['ETime']])->count();
         if($da['type']==0){
                    $title=['日期','用户名','商户ID','余额', '成功提现', '提现处理中','提现渠道'];
                }else{
                    $title=['日期','用户名','商户ID','余额', '充值总额', '成功金额','加款金额'];
                }
        articleAccessLog($title,"商户日报".date('Y-m-d'),$listtotal,$this,$map,[$da['STime'],$da['ETime']]);
    }
    
     public function getArticleAccessLog($where,$map,$page,$limit)
    {
        $list=Daily::with('uname')->where($where)->whereTime('create_time', 'between',$map)->order('id desc')->page($page,$limit)->select();
        $map=[];
        foreach($list as $k=>$v){
                $map[$k]['time']=date("Y-m-d",strtotime($v['create_time']));
                $map[$k]['uname']=$v['company']?:$v['username'];
                $map[$k]['shopid']=$v['shopid'];
                $map[$k]['money']=$v['money'];
                $map[$k]['price']=$v['price'];
                $map[$k]['loadmoney']=$v['loadmoney'];
                if($v['type']==0){
                    $map[$k]['data']=$v['data'];
                }else{
                    $map[$k]['addmoney']=$v['addmoney'];
                }
        }
        return $map;
    }

    public function shopcard($limit=15){
        if($this->request->isAjax()){
            $map[]=['id','>',0];
            if(isset($da['Uid']) && !empty($da['Uid'])){
                $shopid=User::where(['shopid'=>$da['Uid']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            if(isset($da['Name']) && !empty($da['Name'])){
                $shopid=User::where(['username|mobile'=>$da['Name']])->find();
                $map[]=['uid','=',$shopid['id']];
            }
            $time=strtotime(date('Y-m-d',strtotime("-30 day")));
            $list=Daily::with('uname')->field('uid,sum(cardnum) as cardnum,sum(errcard) as errcard,sum(if(`create_time`>'.$time.',cardnum,0)) as mouthnum,sum(if(`create_time`>'.$time.',errcard,0)) as moutherr')
                ->where($map)->group('uid')
                ->order('id desc')
                ->paginate($limit,false,['query' => request()->param()]);
            foreach($list as $k=>$v){
                $list[$k]['username']=$v['username']?:$v['shopid'];
                $cardnu=$v['cardnum']==0?1:$v['cardnum'];
                $mouth=$v['mouthnum']==0?1:$v['mouthnum'];
                $list[$k]['numerr']=sprintf("%.4f",$v['errcard']/$cardnu)*100 ."%";
                $list[$k]['moutherr']=sprintf("%.4f",$v['moutherr']/$mouth)*100 ."%";
            }
            $this->result($list);
        }
        return view();
    }

}
