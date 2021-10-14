<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\library\MongData;
use app\common\model\Emaillog;
use app\common\model\SellList;
use app\common\model\SellUser;
use app\common\model\UserFeilvModel;
use app\common\model\UserShopFeilv;
use think\facade\View;
use app\common\model\User as Yonghu;
use app\common\model\UserAuth;
use app\common\model\UserRate;
use app\common\model\UserLog;
use app\common\model\MoneyLog;
use app\common\model\CardList;
use app\common\model\Userbank;
use app\common\model\CardModel;
use app\common\model\Order;
use app\common\model\CashOrder;


class User extends AdminBase
{
    protected $noAuth = ['upmoney','getFeilv'];

    public function index($limit=15)
    {
        if ($this->request->isAjax()) {
            $type=input("state");
            $keys = input('keys');
            $usertype = input('type');
            $where[]=['id','>',0];
            if($usertype && $keys){
                if($usertype=='name'){
                    $uid=UserAuth::where('name','like',"%".$keys."%")->select();
                    $str="";
                    foreach ($uid as $ka=>$vv){
                        $str.=$vv['uid'].",";
                    }
                    $where[]=['id','in',$str];
                }elseif($usertype=='username'){
                    $where[]=['username','like',"%".$keys."%"];
                }else{
                    $where[]=[$usertype,'=',$keys];
                }
            }
            if($type && $type!=3){
                $where[]=['type','=',$type-1];
            }
            if($type==3){
                $list = Yonghu::onlyTrashed('realto')->where($where)->order('id desc')->paginate($limit);
            }else{
                $list = Yonghu::with('realto')->where($where)->order('id desc')->paginate($limit);
            }
            $list=$list->hidden(['update_time','apides','apikey','password','token','timeout','tradepwd']);

            $checkauth=(new \core\Auth())->check("/user/editMoney",session('admin_auth.admin_id'));
            $auth=session('admin_auth.admin_id')==1?1:$checkauth;
            foreach($list as $k=>$v){
                $list[$k]['retype']=realType($v['clas']);
                $list[$k]['assets']=Yonghu::where(['assets'=>$v['id']])->count();
                if($v['type']==0){
                    $urate=UserShopFeilv::find($v['rategroup']);
                }else{
                    $urate=UserFeilvModel::find($v['rategroup']);
                }
                $list[$k]['rategroup']=$urate['name']??"--";
                $list[$k]['company']=$v['company']?$v['company']:($v['clas']==2?$v['company_name']:($v['name']??'--'));
                $list[$k]['atype']=$v['type'];
                $list[$k]['utype']=(int)$type;
                $list[$k]['auth']=$auth;
                $list[$k]['apilib']=$v['type']==1?$v['cashapi']:$v['apilib'];
                $list[$k]['type']=$v['type']==0?"寄":"充";
            }
            $this->result($list);
        }
        View::assign("money",Yonghu::where(['type'=>0])->sum("money"));
        return $this->fetch('index');
    }

    public function email(){
        if($this->request->isAjax()){
            $da=input();
            if(empty($da['title']))$this->error("请填写发送标题");
            if(empty($da['content']))$this->error("请填写发送内容");
            if(empty($da['email']) && !isset($da['shop']) && !isset($da['sell'])){
                $this->error("必须填写接收会员或选择用户组接收邮件");
            }
            $res=$this->request->param(false);
            $da['content']=$res['content'];
            $cont=0;
            $userall=[];
            if(empty($da['email'])){
                if(isset($da['shop'])){
                    $shop=array_keys($da['shop']);
                    $da['shoplist']=$shop;
                    $users=Yonghu::where([['type','=',0],['rategroup','in',$shop]])->select();
                    foreach($users as $k=>$v){
                        $cont++;
                        $userall[]=['id'=>$v['shopid'],'email'=>$v['email'],'msg'=>$da['content'],'title'=>$da['title']];
                    }
                }
                if(isset($da['sell'])){
                    $sell=array_keys($da['sell']);
                    $da['selllist']=$sell;
                    $usersa=Yonghu::where([['type','=',1],['rategroup','in',$sell]])->select();
                    foreach($usersa as $kk=>$vv){
                        $cont++;
                        $userall[]=['id'=>$vv['shopid'],'email'=>$vv['email'],'msg'=>$da['content'],'title'=>$da['title']];
                    }
                }
            }else{
                $da['userlist']=$da['email'];
                if($da['email']=='all'){
                    $ul=Yonghu::where(['status'=>1])->select();
                    foreach($ul as $ka=>$kv){
                        $userall[]=['id'=>$kv['shopid'],'email'=>$kv['email'],'msg'=>$da['content'],'title'=>$da['title']];
                        $cont++;
                    }
                }else{
                    $str=explode("|",$da['email']);
                    $ula=Yonghu::where([['shopid','in',$str]])->select();
                    foreach($ula as $kb=>$kc){
                        $userall[]=['id'=>$kc['shopid'],'email'=>$kc['email'],'msg'=>$da['content'],'title'=>$da['title']];
                        $cont++;
                    }
                }
            }
            $da['sendnum']=$cont;
            if($cont==0)$this->error("满足接收条件的用户为0，请检查接收用户是否填写正确");
            $ok=Emaillog::create($da);
            if($ok){
                foreach($userall as $v){
                    $v['logid']=$ok->id;
                    \think\facade\Queue::push("app\home\job\Jobone@sendEmail", $v,'sendEmailJobQueue');
                }
                $this->success("发送邮件任务已经添加，请稍后查看记录",url('/Email/index'));
            }else{
                $this->error("发送任务失败");
            }
        }
        View::assign('shoplist',UserShopFeilv::where(['status'=>1])->select());
        View::assign('selllist',UserFeilvModel::where(['status'=>1])->select());
        return view();
    }

   public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $param['shopid'] = Yonghu::order('id desc')->value('shopid')+1;
            empty($param['password']) && $this->error('密码不能为空');
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'user');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            if (empty($param['tradepwd'])) {
                unset($param['tradepwd']);
            }
            if(isset($param['money']))unset($param['money']);
            if(!empty($param['assets'])){
                $param['assets']=Yonghu::where(['shopid'=>$param['assets']])->value('id');
            }
            $result = Yonghu::create($param);
            if ($result == true) {
                insert_admin_log('添加了用户');
                $this->success('添加成功',url('/user/index'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        View::assign('ratelist',UserShopFeilv::where(['status'=>1])->select());
        View::assign("shopgroup",UserFeilvModel::where(['status'=>1])->select());
        return $this->fetch('save', ['userAuthGroup' => []]);
    }


    public function edityuti(){
        if ($this->request->isPost()) {
            $param = $this->request->param();
            if(!is_numeric($param['yuti']))$this->error("请输入数字");
            if($param['yuti']!=0 && ($param['yuti']<30 || $param['yuti']>99)){
                $this->error("设置区间为30-99");
            }
            $data = (new Yonghu)->allowField(['yuti'])->update($param);
            if ($data == true) {
                insert_admin_log('修改了用户预提比例');
                $this->success('修改成功', url('/user/index'));
            } else {
                $this->error("修改失败");
            }
        }
    }



    public function txlog(){
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $data = Userbank::update($param);
            if ($data == true) {
                insert_admin_log('修改了用户');
                $this->success('修改成功', url('/user/index'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        $data = Userbank::withTrashed()->where(['uid'=>input('id')])->order('create_time desc')->paginate(['list_rows' => 13,'query' => request()->param()]);
        foreach($data as $k=>$v){
            $data[$k]['bankid']=$v['bankid']==-1?"支付宝":($v['bankid']==-2?"微信":"银行卡");
            $data[$k]['state']=$v['delete_time']==0?"正常":"已删除";
            $data[$k]['delete_time']=$v['delete_time']==0?"--":$v['delete_time'];
        }
        return $this->fetch('txlog', [
            'list' => $data]);
    }

    public function upmoney(){
        if ($this->request->isPost()) {
            $da=input();
            $user=Yonghu::field('money,xin')->where(['id'=>$da['id']])->find();
            if($user){
                return json(['code'=>1,'data'=>$user]);
            }else{
                return json(['code'=>-1,'data'=>[]]);
            }
        }
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            if (empty($param['password'])) {
                unset($param['password']);
            }
            if (empty($param['tradepwd'])) {
                unset($param['tradepwd']);
            }
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'user');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            if(isset($param['type']) && isset($param['apilib'])){
                if($param['type']==1){
                    $param['cashapi']=$param['apilib'];
                    unset($param['apilib']);
                }
                unset($param['type']);

            }
             if(!empty($param['assets'])){
                $param['assets']=Yonghu::where(['shopid'=>$param['assets']])->value('id');
            }
            $data = Yonghu::update($param);
            if ($data == true) {
                insert_admin_log('修改了用户');
                $this->success('修改成功', url('/user/index'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        $data = Yonghu::with('userReal')->where('id', input('id'))->find();
        View::assign('ratelist',UserShopFeilv::where(['status'=>1])->select());
        View::assign("shopgroup",UserFeilvModel::where(['status'=>1])->select());
        return $this->fetch('save', [
            'data' => $data]);
    }

    public function del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            Yonghu::destroy($param['id']);
            UserRate::where(['uid'=>$param['id']])->delete();
            Selluser::where(['uid'=>$param['id']])->delete();
            UserBank::where(['uid'=>$param['id']])->delete();
            insert_admin_log('注销了用户');
            $this->success('注销成功');
        }
    }

    public function authreal(){
        $id=input('id');
        $result=UserAuth::where(['uid'=>$id])->find();
        View::assign("res",$result);
        View::assign("moren","/static/simple/img/no_image_100x100.jpg");
        return view();
    }

    public function export()
    {
        $type=input("type");
        $where[]=['id','>',0];
        if($type)$where[]=['type','=',$type-1];
        $data = Yonghu::field('id,shopid,username,mobile,email,qq,money,xin,yuti,create_time')->where($where)->order('id desc')->select()->toArray();
        $title=['用户ID','商户号', '用户名', '手机号','邮箱','QQ','账户余额','授信额度','预提比例(%)','注册时间'];
        insert_admin_log('导出了用户');
        export_excel($data,$title, date('YmdHis'));
    }


    public function moneylog($limit=15){
        $da=input();
        if($this->request->isAjax()) {
            $mapp[]=['uid','=',$da['id']];
            if(isset($da['type']) && !empty($da['type'])){
                $mapp[]=['type','in','5,8'];
            }
            if(isset($da['classtype']) && isset($da['keys']) && !empty($da['classtype']) && !empty($da['keys'])){
                $mapp[]=[$da['classtype'],'like',"%{$da['keys']}%"];
            }

            if(isset($da['st']) && isset($da['se']) && !empty($da['st']) && !empty($da['se'])){
                $map=[$da['st'], $da['se']];
                $list=MoneyLog::where($mapp)->whereTime('addtime', 'between',$map)->order('addtime desc')->paginate(['list_rows'=>$limit]);
            }else{
                $list=MoneyLog::where($mapp)->order('addtime desc')->paginate(['list_rows'=>$limit]);
            }
            foreach($list as $k=>$v){
                $list[$k]['type']=moneyType($v['type']);
            }
            $this->result($list);
        }
        View::assign('id',input('id'));
        return view();
    }

    public function feilv(){
        $da=input();

        return view('userfeilv',['list'=>CardList::select(),'uid'=>$da['id']]);
    }
    public function editfeilv(){
        if ($this->request->isAjax()) {
            $da=input();
            if(UserRate::find($da['id'])){
                if(isset($da['status']))$da['status']=$da['status']==0?2:1;
                $ok=(new UserRate)->allowField(['status','feilv'])->update($da);
                if($ok){
                    return json(['code'=>1,'msg'=>'修改成功']);
                }else{
                    return json(['code'=>-1,'msg'=>'更新失败']);
                }
            }else{
                return json(['code'=>-1,'msg'=>'参数错误']);
            }
        }
    }

    public function getFeilv(){
        if ($this->request->isAjax()) {
            $da=input();
            $res=UserRate::where(['uid'=>$da['id'],'listid'=>$da['cid']])->select();
            $this->result($res);
        }
    }

    public function log($limit=15)
    {
        $da=input();
        if($this->request->isAjax()) {
            if(isset($da['st']) && isset($da['se'])){
                $map=[$da['st'], $da['se']];
                $list=UserLog::where(['uid'=>$da['id']])->whereTime('create_time', 'between',$map)->order('id desc')->paginate($limit);
            }else{
                $list=UserLog::where(['uid'=>$da['id']])->order('id desc')->paginate($limit);
            }
            $user=Yonghu::where(['id'=>$da['id']])->find();
            foreach($list as $k=>$v){
                $list[$k]['shopid']=$user['shopid'];
                $list[$k]['shop_name']=$user['mobile'];
                if($v['ism']=='0'){
                    $list[$k]['pc']="PC";
                }elseif($v['ism']=='1'){
                    $list[$k]['pc']="移动端";
                }else {
                    $list[$k]['pc']="微信";
                }
            }
            $this->result($list);
        }
        View::assign("id",$da['id']);
        return view();
    }

    public function truncate()
    {
        if ($this->request->isPost()) {
            UserLog::where('id','>',0)->delete();
            $this->success('操作成功');
        }
    }

    public function usershopfeilv($limit=15){
        $da=input();
        if($this->request->isAjax()){
            if(isset($da['type']) && !empty($da['type'])){
                $list=(new SellUser)->field("a.*,b.cid")->alias('a')->join('sell_list b','a.sellid=b.id')->field('a.*,b.cid')->where(['b.cid'=>$da['type'],'a.uid'=>$da['id']])->paginate($limit);
            }else{
                $list=(new SellUser)->where(['uid'=>$da['id']])->paginate($limit);
            }
            foreach($list as $k=>$v){
                $selllist=SellList::find($v['sellid']);
                $list[$k]['title']=$selllist['title']."【".$selllist['mianzhi']."】";
            }
            $this->result($list);
        }
        View::assign("uid",$da['id']);
        View::assign("li",CardModel::select());
        return view();
    }

    public function editshop(){
        if($this->request->isAjax()){
            $param = $this->request->param();
            if(isset($param['_verify']))unset($param['_verify']);
            if(isset($param['status']) && $param['status']==0)$param['status']=2;
			if(isset($param['s']))unset($param['s']);
            $data = SellUser::where(['id'=>$param['id']])->update($param);
            $this->success('修改成功');
        }
    }

    public function getUser(){
        if($this->request->isAjax()){
            $id=input('id');
            $user=Yonghu::find($id);
            if($user){
                $arr['istype']=$user['type'];
                $arr['order']=Order::where([['uid','=',$id],['state','in','0,1,4']])->count();
                $arr['cash']=CashOrder::where([['uid','=',$id],['state','in','0,1,4,6']])->count();
                $arr['money']=$user['money'];
                $arr['uid']=$user['id'];
                $this->success("获取成功",'',$arr);
            }else{
                $this->error("id错误",'',$user);
            }
        }
    }

    public function editMoney(){
        $da=input();
        if($this->request->isAjax()){
            if(empty($da['money']))$this->error("金额不能为空");
            return (new MongData)->editMoney($da);
        }
        View::assign('data',Yonghu::find($da['id']));
        return view();
    }


}
