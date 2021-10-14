<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\SellList;
use app\common\model\SellUser;
use app\common\model\UserFeilvList;
use app\common\model\UserFeilvModel;
use app\common\model\CardModel;
use think\facade\View;

class UserRate extends AdminBase{

    public function index($limit=15){
        if($this->request->isAjax()){
            $list=UserFeilvModel::order("id desc")->paginate($limit);
            $this->result($list);
        }
        return view();
    }

    public function addgroup(){
        if($this->request->isPost()){
            $data=$this->request->param();
            if((new UserFeilvModel)->allowField(['name','description'])->save($data)){
                $this->success("添加成功",url('/UserRate/index'));
            }else{
                $this->error("新增失败");
            }
        }
        return view('save');
    }
    public function editgroup(){
        $da=input();
        if($this->request->isAjax()){
            $resule = (new UserFeilvModel())->allowField(['name','description','status'])::update($da);
            if ($resule == true) {
                $this->success('修改成功',url('/UserRate/index'));
            } else {
                $this->error("修改失败");
            }
        }
        $da=UserFeilvModel::find($da['id']);
        View::assign("data",$da);
        return view('save');
    }

    public function shopfeilv($limit=15){
        $da=input();
        if($this->request->isAjax()){
            if(isset($da['type']) && $da['type']!=""){
                $res=(new UserFeilvList)->field("a.*,b.cid")->alias('a')->join('sell_list b','a.sellid=b.id')->where(['b.cid'=>$da['type'],'a.bid'=>$da['id']])->paginate($limit);
            }else{
                $res=UserFeilvList::where(['bid'=>$da['id']])->paginate($limit);
            }
            foreach($res as $k=>$v){
                $selllist=SellList::find($v['sellid']);
                $res[$k]['title']=$selllist['title']."【".$selllist['mianzhi']."】";
            }
            $this->result($res);
        }
        View::assign("uid",$da['id']);
        View::assign("li",CardModel::select());
        return view();
    }

    public function editshop(){
        if($this->request->isAjax()){
            $param = $this->request->param();
            if(isset($param['_verify']))unset($param['_verify']);
			if(isset($param['s']))unset($param['s']);
            $data = UserFeilvList::where(['id'=>$param['id']])->update($param);
            $this->success('修改成功');
        }
    }

    public function gengxin(){
        if($this->request->isAjax()){
            $id=input('id');
            (new SellUser)->gengxin($id);
            $this->success('更新成功');
        }
    }

    public function delgroup(){
        if($this->request->isAjax()) {
            $id = input('id');
            UserFeilvModel::destroy($id,true);
            $this->success('删除成功');
        }
    }

}
