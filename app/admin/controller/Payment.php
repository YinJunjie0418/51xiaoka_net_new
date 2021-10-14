<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\library\MongData;
use app\common\model\Recharge;
use app\common\model\User;
use app\common\model\Payment as PAY;
use think\facade\View;

class Payment extends AdminBase{

    public function index($limit=15){
        $da=input();
        if($this->request->isAjax()){
            $map[]=['id','>',0];
            if(isset($da['state']) && $da['state']!=0){
                $map[]=['status','=',$da['state']-1];
            }
            if(isset($da['keys']) && isset($da['shop_id']) && !empty($da['keys']) && !empty($da['shop_id'])){
                if($da['keys']=='shopid'){
                    $uid=User::where(['shopid'=>$da['shop_id']])->value('id');
                    $map[]=['uid','=',$uid];
                }
                if($da['keys']=='name'){
                    $uid=User::where('company','like',"%".$da['shop_id']."%")->select();
                    $str="";
                    foreach ($uid as $ka=>$vv){
                        $str.=$vv['id'].",";
                    }
                    $map[]=['uid','in',$str];
                }
                if($da['keys']=='orderno')$map[]=['order','=',$da['shop_id']];
                if($da['keys']=='money')$map[]=['money','=',$da['shop_id']];
            }
            $list=Recharge::with(['preone','shopdata'])->where($map)->order('id desc')->paginate($limit);
            foreach($list as $k=>$v){
                $list[$k]['statusa']=caType($v['status']);
                if($v->getdata('create_time')==$v->getdata('update_time')){
                    $list[$k]['uptime']=0;
                }else{
                    $list[$k]['uptime']=1;
                }
            }
            $this->result($list);
        }
        return view();
    }

    public function editOrder(){
        $id=input('id');
        if($this->request->isAjax()){
            $result=Recharge::where(['status'=>0,'id'=>$id])->find();
            $type=input('type');
            $remarks=input('content');
            if($result){
                switch($type){
                    case "success":
                        $res=(new MongData)->editMoney(['id'=>$result->uid,'typea'=>8,'order'=>$result['order'],'type'=>'money','money'=>$result->money,'remarks'=>$remarks]);
                        if($res['code']==1){
                            $result->status=1;
                        }
                        $result->remarks='['.session('admin_auth.username').']'.$remarks;
                        $result->save();
                        PAY::where(['id'=>$result['cid']])->inc('remamoney',$result['money'])->update();
                        $this->success($res['msg'],url('/Payment/index'));
                        break;
                    case "loaded":
                        $result->remarks='['.session('admin_auth.username').']'.$remarks;
                        $result->save();
                        $this->success("标注成功，不改变订单状态",url('/Payment/index'));
                        break;
                    case "fail":
                        $result->remarks='['.session('admin_auth.username').']'.$remarks;
                        $result->status=2;
                        $result->save();
                        $this->success("审核成功",url('/Payment/index'));
                        break;
                }
            }else{
                $this->error("该订单已经审核");
            }
        }
        $data=Recharge::with(['preone','shopdata'])->where(['id'=>$id])->find();
        View::assign('data',$data);
        return view('save');
    }
}
