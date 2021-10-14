<?php
namespace app\admin\controller;
use app\common\controller\AdminBase;
use think\facade\View;
use think\facade\Request;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Session;
use app\common\model\Ad as Guanggao;
use app\common\model\AdModel;

class Ad extends AdminBase
{
    protected function _initialize()
    {
        parent::_initialize();
        
    }
    //广告首页
    public function index()
    {
        return $this->fetch('index', [
            'category' => AdModel::lists(),
        ]);
    }
    public function indexjson($limit = '15')
    {
        $ad = new Guanggao();
        $param = $this->request->param();
        $where = [];
        if (isset($param['name'])) {
            $ad = $ad->where('name','like',"%" . $param['name'] . "%");
        }
        if (isset($param['category'])) {
            $ad = $ad->where('category',$param['category']);
        }
        $list = $ad->with('category')->order('id desc')->where($where)->paginate($limit);
        $this->result($list);
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $result = Guanggao::create($param);
            if ($result == true) {
                insert_admin_log('添加了广告');
                $this->success('添加成功');
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('save',['category' => AdModel::lists()]);
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $result = Guanggao::update($param,['id'=>$param['id']]);
            if ($result == true) {
                insert_admin_log('修改了广告');
                $this->success('修改成功');
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('save', ['data' => Guanggao::where('id', input('id'))->find(),'category' => AdModel::lists()]);
    }

    public function del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            Guanggao::destroy($param['id']);
            insert_admin_log('删除了广告');
            $this->success('删除成功');
        }
    }
    //分类管理
    public function category()
    {
        return $this->fetch('category');
    }
    //分类管理
    public function category_json()
    {
        $param = $this->request->param();
        $list = AdModel::order('id desc')->select();
        $this->result($list);
    }
    //分类添加
    public function category_add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'ad_model');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            $result = AdModel::create($param);
            if ($result == true) {
                insert_admin_log('添加了广告分类');
                $this->success('添加成功');
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('category_save');
    }
    //分类编辑
    public function category_edit()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'ad_model');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            $result = AdModel::update($param);
            if ($result == true) {
                insert_admin_log('修改了广告分类');
                $this->success('修改成功');
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('category_save', [
            'data' => AdModel::where('id', input('id'))->find(),
        ]);
    }
    //分类删除
    public function category_del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            AdModel::destroy($param['id']);
            insert_admin_log('删除了广告分类');
            $this->success('删除成功');
        }
    }
}
