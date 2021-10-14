<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\CardChannel;
use app\common\model\CardList;
use app\common\model\UserShopFeilv;
use app\common\model\User;
use app\common\model\UserRate;
use app\common\model\UserShopList;

class UserFeilv extends AdminBase{
    public function  index($limit=15){
        if($this->request->isAjax()){
            $result=UserShopFeilv::order('id desc')->paginate($limit);
            $this->result($result);
        }
        return view();
    }

    public function add(){
        if($this->request->isPost()){
            $data=$this->request->param();
            if((new UserShopFeilv)->allowField(['name','description'])->save($data)){
                $this->success("添加成功",url('/UserFeilv/index'));
            }else{
                $this->error("新增失败");
            }
        }
        return view('save');
    }

    public function feilv(){
        $id=input('id');
        $rules=CardList::select();
        return view('userfeilv',['list'=>$rules,'id'=>$id]);
    }

    public function getFeilv(){
        if ($this->request->isAjax()) {
            $da=input();
            $res=UserShopList::where(['bid'=>$da['id'],'listid'=>$da['cid']])->select();
            $this->result($res);
        }
    }

    public function editfeilv()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $resule = (new UserShopFeilv)->allowField(['name','description','status','rules'])::update($param);
            if ($resule == true) {
                $this->success('修改成功', url('/UserFeilv/index'));
            } else {
                $this->error("修改失败");
            }
        }
        $data = UserShopFeilv::where('id', input('id'))->find();
        return view('save', ['data' => $data]);
    }

    public function delfeilv()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            UserShopFeilv::destroy($param['id'],true);
            $this->success('删除成功');
        }
    }

    public function setfeilv()
    {
        $da=input();
        if ($this->request->isAjax()) {
               $res=UserShopList::find($da['id']);
               if($res){
                   (new UserShopList)->allowField(['status','feilv'])::update($da);
                   return json(['code'=>1,'msg'=>'操作成功']);
               }else{
                   return json(['code'=>-1,'msg'=>'参数错误']);
               }

        }
    }



    public function gengxin(){
        if($this->request->isAjax()){
                $id=input('id');
                (new UserRate)->gengxin($id);
                $this->success('更新成功');
            }
    }
}

