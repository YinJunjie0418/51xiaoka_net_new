<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Banks;
use app\common\model\CashNumberLog;
use app\common\model\Neworder;
use think\facade\View;
use think\facade\Db;
use app\common\model\Order as Orders;
use app\common\model\Admin;
use app\common\model\CardList;
use app\common\model\User;
use app\common\model\CardOperator;
use app\common\model\Newapi;


class Order extends AdminBase
{
    public function index($limit=15)
    {
        $da=input();
        if ($this->request->isAjax() ){
            $where[]=['tmporder','=','0'];
            $map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
            if(isset($da['st']) && isset($da['se']) && !empty($da['st']) && !empty($da['se'])){
                $map=[$da['st'], $da['se']];
            }elseif(isset($da['st']) && isset($da['se']) && empty($da['st']) && !empty($da['se'])){
                $map=["2020-1-1 00:00:00", $da['se']];
            }elseif(isset($da['st']) && isset($da['se']) && !empty($da['st']) && empty($da['se'])){
                $map=[$da['st'], date("Y-m-d H:i:s")];
            }
            if(!empty($da['name']) && !empty($da['kk'])){
                if($da['kk']=="card_no" || $da['kk']=="card_key"){
                    $da['name']=Orders::enCardno($da['name']);
                    $where[]=[$da['kk'],'=',$da['name']];
                }elseif($da['kk']=='shopid') {
                    $uid=User::where(['shopid'=>$da['name']])->find()['id'];
                    $where[]=['uid','=',$uid];
                }else{
                    $where[]=[$da['kk'],'=',$da['name']];
                }
            }
            if(!empty($da['state'])){
                switch($da['state']){
                    case 1:
                        $where[]=['state','=',0];
                        break;
                    case 2:
                        $where[]=['state','=',1];
                        break;
                    case 3:
                        $where[]=['state','in','2,8'];
                        break;
                    case 4:
                        $where[]=['state','in','3,7'];
                        break;
                    case 5:
                        $where[]=['state','=',4];
                        break;
                    case 6:
                        $where[]=['ispei','>',0];
                        break;
                }
            }
            if(!empty($da['classa'])){
                $where[]=['class','=',$da['classa']];
            }
            if(isset($da['timetype']) && $da['timetype']=="off"){
                $timename="chulitime";
                $orderby="chulitime desc";
            }else{
                $timename="create_time";
                $orderby="id desc";
            }

            $list=Orders::with(['bsLei','uname','opername'])->where($where)->whereTime($timename, 'between',$map)->order($orderby)->paginate($limit);
            foreach($list as $k=>$v){
                $list[$k]['remarks']=$v['remarks'];
                $list[$k]['atime']=date("Y-m-d",strtotime($v['create_time']))."</br>".date("H:i:s",strtotime($v['create_time']));
                if($v->getData('state')>1){
                    $list[$k]['chutime']=date("Y-m-d",strtotime($v['chulitime']))."</br>".date("H:i:s",strtotime($v['chulitime']));
                }else{
                    $list[$k]['chutime']="--";
                }
                   $list[$k]['username']=$v['username']?:$v['shopid'];
                   $list[$k]['oper']= $v['oper']?:($v['type']==0?'????????????':"???????????????");
                $list[$k]['getstate']=$v->getData('state');
                if($v->getData('state')==0 && cache($v['batchno'])){
                    $list[$k]['isrest']=1;
                }else{
                    $list[$k]['isrest']=0;
                }
            }
            $this->result($list);
        }
        $li=CardList::field('type,title')->select();
        if(isset($da['batchno'])){
            $da['tc']=1;
            $da['str']=$da['batchno'];
            $da['field']="batchno";
        }

        if(isset($da['orderno'])){
            $da['tc']=1;
            $da['str']=$da['orderno'];
            $da['field']="orderno";
        }
        View::assign('da',$da);
        return $this->fetch('index',['id'=>session('admin_auth.admin_id'),'li'=>$li]);
    }

    public function editpriority(){
        if($this->request->isAjax()){
            $order=input('order');
            $num=input('priority');
            if(is_numeric($num) && $num>=0 && $num<101){
                $norder=new Neworder();
                $norder->startTrans();
                try{
                    $res=Neworder::where(['orderno'=>$order])->update(['priority'=>$num]);
                    $resok=Orders::where(['orderno'=>$order])->update(['priority'=>$num]);
                    if($res && $resok){
                        $norder->commit();
                        $this->success("?????????????????????{$res}?????????");
                    }else{
                        $norder->rollback();
                        $this->error("????????????,???????????????????????????????????????");
                    }
                }catch (\Exception $e){
                    $norder->rollback();
                    $this->error("????????????" . $e->getMessage());
                }
            }else{
                $this->error("?????????0-100?????????");
            }

        }
    }

    public function setStatus(){
        if ($this->request->isPost()) {
            $da=input();
            $order=Orders::where(['id'=>$da['id']])->find();
            if(!$order)return json(['code'=>-1,'msg'=>'????????????????????????']);
            $type=isset($da['type'])?$da['type']:'';
            try{
                switch($type){
                    case 'error':
                        $ok=successful($da['id'],3);
                        break;
                    case 'success':
                        $ok=successful($da['id']);
                        break;
                    default:
                        $ok=false;
                        if($order['amount']+$da['xitmoney']>$da['mianzi'])return json(['code'=>-1,'msg'=>'????????????+???????????????????????????????????????']);
                        $order->settle_amt=$da['mianzi'];
                        $order->amount+=$da['xitmoney'];
                        $order->ispei=2;
                        $order->state=2;
                        $isok=$order->save();
                        if($isok){
                            $ok=User::where(['id'=>$order['uid']])->inc('money',(float)$da['xitmoney'])->update();
                            addlog($order['uid'],$da['xitmoney'],4,$order['orderno'],"[????????????]{$da['xitmoney']}");
                        }else{
                            return json(['code'=>-1,'msg'=>'??????????????????']);
                        }
                }
                $this->addNumberLog($order['orderno']);
            }catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            if($ok===true){
                $this->success('????????????????????????',url('/Order/index'));
            }else{
                return json(['code'=>-1,'msg'=>'????????????,????????????:'.$ok]);
            }

        }
    }

    public function addNumberLog($order){
        $isk=CashNumberLog::where(['cardorder'=>$order])->order('id desc')->find();
        if($isk){
             CashNumberLog::where('id',$isk['id'])->update(['cardmount'=>$isk['amount']]);
        }
    }

    public function peifu(){
        $res=Orders::with('bsLei')->where(['id'=>input('id')])->find();
        $str=CardOperator::where(['id'=>$res['type']])->value('name');
        $res['atype']=$res['type']==0?"????????????":$str;
        $res['classa']=CardList::where(['type'=>$res['class']])->value('title');
        View::assign('res',$res);
        View::assign('op',CardOperator::where(['type'=>0,'status'=>1])->select());
        return view();
    }

    public function findorder(){
        if ($this->request->isPost()) {
            $id=input('id');
            $order=Orders::find($id);
            $api=new Newapi($order['type']);
            $ok=$api->blindSearch($order);
            if($ok['code']==1){
                $this->success($ok['msg']);
            }else{
                $this->error($ok['msg']);
            }
        }
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $result = Banks::create($param);
            if ($result == true) {
                $this->success('????????????',url('/Bank/index'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('save');
    }

    public function pic(){
        $id=input('id');
        return view('pic',['img'=>Orders::find($id)['card_no']]);
    }

    public function zhipai(){
        if ($this->request->isPost()) {
            $da=input();
            $admin=Admin::find($da['uid']);
            if($admin){
                if($admin['status']==1){
                    $order=Orders::where([['id','in',$da['ids']],['qiang','=',0]])->select();
                    $map=[];
                    foreach($order as $k=>$v){
                        $map[$k]['id']=$v['id'];
                        $map[$k]['qiang']=$da['uid'];
                    }
                    (new Orders)->saveAll($map);
                    $this->success('????????????');
                }else{
                    $this->error("?????????????????????");
                }
            }else{
                $this->error("??????????????????");
            }
        }
    }

    public function batchOrder(){
        if ($this->request->isPost()) {
            $da=input();
            if($da['type']=='del'){
                Db::name('order')->delete($da['ids']);
                $this->success('????????????');
            }elseif($da['type']=="rest"){
                $order=Orders::where([['id','in',$da['ids']],['state','in',[0,1,3]],['type','>',0]])->select();
                $count=count($da['ids']);
                $ok=count($order);
                foreach($order as $k=>$v){
                    $map=$v->toarray();
                    $map['card_no']=$v->getData('card_no');
                    $map['card_key']=$v->getData('card_key');
                    \think\facade\Queue::push("app\home\job\Jobone@sendCard", $map,'sendCardJobQueue');
                }
                $this->success("?????????{$count}???????????????????????????????????????{$ok}??????????????????????????????");
            }elseif($da['type']=="success"){
                $list=Orders::where('id','in',$da['ids'])->select();
                $count=count($da['ids']);
                $ok=0;
                foreach($list as $k=>$v){
                    $state=$v->getData('state');
                    if($state!=2 && $state!=0){
                        successful($v['id']);
						$this->addNumberLog($v['orderno']);
                        $ok++;
                    }
                }
                $this->success("?????????{$count}???????????????????????????????????????{$ok}????????????????????????");
            }elseif($da['type']=="err"){
                $list=Orders::where('id','in',$da['ids'])->select();
                $count=count($da['ids']);
                $ok=0;
                foreach($list as $k=>$v){
                    $state=$v->getData('state');
                    if($state!=2 && $state!=3){
                        successful($v['id'],3);
                        $ok++;
                    }
                }
                $this->success("?????????{$count}???????????????????????????????????????{$ok}????????????????????????");
            }elseif($da['type']=="chaxun"){
                $list=Orders::where('id','in',$da['ids'])->select();
                foreach($list as $v){
                    $api=new Newapi($v['type']);
                    $ok=$api->blindSearch($v);
                }
                $count=count($da['ids']);
                $this->success("???????????????{$count}??????????????????????????????");
            }else{
                $this->error("????????????");
            }
        }
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $id=$param['id'];
            unset($param['id']);
            unset($param['_verify']);
            $data = Banks::where(['id'=>$id])->update($param);
            if ($data == true) {
                $this->success('????????????', url('/Bank/index'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        $data = Banks::where(['id'=>input('id')])->find();
        return view('save', [
            'data' => $data]);
    }

    public function del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            Orders::destroy(isset($param['id'])?$param['id']:$param['ids']);
            $this->success('????????????');
        }
    }



    public function export()
    {
            $data=input();
            $map=["2020-1-1 00:00:00", date('Y-m-d 23:59:59')];
            if(isset($data['st']) && isset($data['se']) && !empty($data['st']) && !empty($data['se'])){
                $map=[$data['st'], $data['se']];
            }elseif(isset($data['st']) && isset($data['se']) && empty($data['st']) && !empty($data['se'])){
                $map=["2020-1-1 00:00:00", $data['se']];
            }elseif(isset($data['st']) && isset($data['se']) && !empty($data['st']) && empty($data['se'])){
                $map=[$data['st'], date("Y-m-d H:i:s")];
            }
            $where[]=['tmporder','=',0];
            if(!empty($data['name']) && !empty($data['kk'])){
                if($data['kk']=="card_no" || $data['kk']=="card_key"){
                    $data['name']=Orders::enCardno($data['name']);
                    $where[]=[$data['kk'],'=',$data['name']];
                }elseif($data['kk']=='shopid') {
                    $uid=User::where(['shopid'=>$data['name']])->find()['id'];
                    $where[]=['uid','=',$uid];
                }else{
                    $where[]=[$data['kk'],'=',$data['name']];
                }
            }
            if(!empty($data['state'])){
                switch($data['state']){
                    case 1:
                        $where[]=['state','=',0];
                        break;
                    case 2:
                        $where[]=['state','in','1'];
                        break;
                    case 3:
                        $where[]=['state','in','2,8'];
                        break;
                    case 4:
                        $where[]=['state','in','3,7'];
                        break;
                    case 5:
                        $where[]=['state','=','4'];
                        break;
                }
            }
            if(!empty($data['classa'])){
                $where[]=['class','=',$data['classa']];
            }
            $listtotal=Orders::where($where)->whereTime('create_time', 'between',$map)->count();
            $title=['??????ID','?????????','???????????????','??????', '??????', '??????','????????????','????????????','????????????','??????','??????IP','??????','????????????','????????????'];
            articleAccessLog($title,"????????????".date('Y-m-d'),$listtotal,$this,$where,$map);
    }

    public function getArticleAccessLog($where,$map,$page,$limit)
    {
        $list=Orders::with(['bsLei','uname'])->where($where)->whereTime('create_time', 'between',$map)->order('id desc')->page($page,$limit)->select();
        $map=[];
        foreach($list as $k=>$v){
            $map[$k][]=$v['shopid'];
            $map[$k][]=$v['username'];
            $map[$k][]=$v['orderno']."\t";
            $map[$k][]=$v['title'];
            $map[$k][]=$v['card_no']."\t";
            $map[$k][]=$v['card_key']."\t";
            $map[$k][]=$v['money'];
            $map[$k][]=$v['settle_amt'];
            $map[$k][]=$v['amount'];
            $map[$k][]=orderTyped($v->getData('state'));
            $map[$k][]=$v['ip'];
            $map[$k][]=$v['remarks']?$v['remarks']:'--';
            $map[$k][]=$v['create_time'];
            $map[$k][]=$v['update_time'];
        }
        return $map;
    }
}
