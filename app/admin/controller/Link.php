<?php
namespace app\admin\controller;
use app\common\controller\AdminBase;
use think\facade\View;
use think\facade\Request;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Session;
use app\common\model\Link as Flink;
use app\common\model\LinkModel;

class Link extends AdminBase
{
    protected function _initialize()
    {
        parent::_initialize();
        
    }
    
    //广告首页
    public function index()
    {
        return $this->fetch('index', [
            'category' => LinkModel::lists(),
        ]);
    }
    public function index_json($limit = '15')
    {
        $flink = new Flink();
        $param = $this->request->param();
        $where = [];
        if (isset($param['name'])) {
            $flink = $flink->where('name','like',"%" . $param['name'] . "%");
        }
        if (isset($param['category'])) {
            $flink = $flink->where('category',$param['category']);
        }
        $list = $flink->with('category')->order('id desc')->where($where)->paginate($limit);
        $this->result($list);
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $result = Flink::create($param);
            if ($result == true) {
                insert_admin_log('添加了友情链接');
                $this->success('添加成功',url('/Link/index'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('save',['category' => LinkModel::lists()]);
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $result = Flink::update($param,['id'=>$param['id']]);
            if ($result == true) {
                insert_admin_log('修改了友情链接');
                $this->success('修改成功',url('/Link/index'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('save', ['data' => Flink::where('id', input('id'))->find(),'category' => LinkModel::lists()]);
    }

    public function del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            Flink::destroy($param['id']);
            insert_admin_log('删除了友情链接');
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
        $list = LinkModel::order('id desc')->select();
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
            $result = LinkModel::create($param);
            if ($result == true) {
                insert_admin_log('添加了友情链接分类');
                $this->success('添加成功',url('/link/category'));
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
            $result = LinkModel::update($param,['id'=>$param['id']]);
            if ($result == true) {
                insert_admin_log('修改了友情链接分类');
                $this->success('修改成功',url('/link/category'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('category_save', [
            'data' => LinkModel::where('id', input('id'))->find(),
        ]);
    }
    //分类删除
    public function category_del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            LinkModel::destroy($param['id']);
            insert_admin_log('删除了友情链接分类');
            $this->success('删除成功',url('/link/category'));
        }
    }
}
