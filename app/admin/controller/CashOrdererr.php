<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\CashOrderErr as cs;
use app\common\model\SellList;
use app\common\model\User;
use think\facade\View;

class CashOrdererr extends AdminBase{
    public function index($limit=15)
    {
        $da=input();
        if ($this->request->isAjax()){
            
            $where[]=['id','<>',0];
            $map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
            if(isset($da['st']) && isset($da['se']) && !empty($da['st']) && !empty($da['se'])){
                $map=[$da['st'], $da['se']];
            }
            if(!empty($da['name']) && !empty($da['kk'])){
                  if($da['kk']=='shopid') {
                    $uid=User::where(['shopid'=>$da['name']])->find()['id'];
                    $where[]=['uid','=',$uid];
                }else{
                    $where[]=[$da['kk'],'=',$da['name']];
                }
            }
            if(!empty($da['state'])){
                $where[]=['state','=',$da['state']-1];
            }
            if(!empty($da['classa'])){
                $where[]=['type','=',$da['classa']];
            }
            $list=cs::where($where)->whereTime('create_time', 'between',$map)->order('id desc')->paginate($limit);
            foreach($list as $k=>$v){
                $list[$k]['remarks']=$v['remarks'];
                $user=User::where(['id'=>$v['uid']])->find();
                $uname=!empty($user['username'])?$user['username']:$user['mobile'];
                $list[$k]['uname']=empty($uname)?"--":$uname;
                $list[$k]['price']=$v['price']>0?$v['price']:"--";
                $sell=SellList::where(['geway'=>$v['type']])->find();
                $list[$k]['title']=$sell['title']."【".$sell['mianzhi']."】";
                $list[$k]['atime']=date("Y-m-d",strtotime($v['create_time']))."</br>".date("H:i:s",strtotime($v['create_time']));
                $list[$k]['notifymsg']=empty($v['feilv'])?"实时返回":$v['notifymsg'];
                $list[$k]['feilv']=empty($v['feilv'])?"未计算":$v['feilv'];
            }
            $this->result($list);
        }
        $li=SellList::select();
        if(isset($da['orderno'])){
		    $da['tc']=1;
		    $da['str']=$da['orderno'];
		}
		View::assign('da',$da);
        return $this->fetch('index',['li'=>$li]);
    }

    public function del(){
        if($this->request->isAjax()){
            $id=input('ids');
            cs::destroy($id,true);
            $this->success("删除成功");
        }
    }
}