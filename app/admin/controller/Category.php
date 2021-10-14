<?php
namespace app\admin\controller;
use app\common\controller\AdminBase;
use think\facade\View;
use app\common\model\Category as Cg;
use app\common\model\CategoryModel;
use app\common\model\CategoryModelField;


class Category extends AdminBase
{
       //首页
    public function index()
    {
        if($this->request->isAjax()){
            $list = list_to_level(Cg::with(['models'])->order('sort_order asc')->select());
            $this->result($list);
        }
        return $this->fetch('index', ['list' => list_to_level(Cg::with(['models'])->order('sort_order desc,id desc')->select())]);
    }

    //获取上级
    public function get_pcategory()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $list = Cg::where('id',$param['id'])->find();
            $data = CategoryModel::where('model',$list['model'])->find();
            $this->result($data);
        }
    }
    //新增分类
    public function add() 
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'category');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            //添加
            $data = Cg::create($param);
            if ($data == true) {
                $url = Cg::update_url($data->id);
                $this->success('添加成功', url('/category/index'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        View::assign('models', CategoryModel::lists());
        return $this->fetch('save', [
            'category' => list_to_level(Cg::order('sort_order asc')->select()),
            'list_template' => Cg::list_template(),
            'show_template' => Cg::show_template()
        ]);
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'category');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            //添加
            $data = Cg::update($param,['id'=>$param['id']]);
            if ($data == true) {
                $url = Cg::update_url($param['id']);
                insert_admin_log('修改了分类');
                $this->success('修改成功');
            } else {
                $this->error($this->errorMsg);
            }
        }
        View::assign('models', CategoryModel::lists());
        return $this->fetch('save', [
            'data'     => Cg::where('id', input('id'))->find(),
            'category' => list_to_level(Cg::order('sort_order asc')->select()),
            'list_template' => Cg::list_template(),
            'show_template' => Cg::show_template()
        ]);
    }

    public function del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            Cg::destroy($param['id'],true);
            insert_admin_log('删除了分类');
            $this->success('删除成功');
        }
    }
    
    
    //模型管理
    public function models()
    {
        return $this->fetch('models');
    }
    public function models_json()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            extraconfig($param, 'website');
            $this->success('设置成功');
        }
        $list = CategoryModel::with(['field_list'])->select();
        $this->result($list);
    }
    //模型添加
    public function models_add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'category_model');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            $data = CategoryModel::create($param);
            if ($data == true) {
                insert_admin_log('添加了模型');
                $this->success('添加成功', url('/category/models'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('models_save');
    }
    //模型编辑
    public function models_edit()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $data = CategoryModel::update($param);
            if ($data == true) {
                insert_admin_log('修改了模型');
                $this->success('修改成功', url('/category/models'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('models_save',['data'=> CategoryModel::where('id', input('id'))->find()]);
    }
    //模型删除
    public function models_del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $data = CategoryModel::where('id',$param['id'])->find();
            if($data['is_sys']=='1'){
                $this->error('系统模型，禁止删除');
            }else{
                CategoryModel::destroy($param['id'],true);
                insert_admin_log('删除了模型');
                $this->success('删除成功');
            }
        }
    }
    //模型字段列表
    //首页
    public function models_field()
    {
        return $this->fetch('models_field');
    }
	
    public function models_field_json($model,$limit='8')
    {
        $list = CategoryModelField::where('model',$model)->order('sort_order asc,id desc')->paginate($limit);
        if(count($list)=='0'){
            CategoryModelField::CreateDefaultField($model);
            $list = CategoryModelField::where('model',$model)->order('sort_order asc,id desc')->paginate($limit);
        }
        $this->result($list);
    }
    //字段添加
    public function models_field_add($model)
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'category_model_field');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            //判断是否系统保留字段
            CategoryModelField::IsSysField($param['name']) && $this->error('系统保留字段!请修改配置标识');
            //多图字段只能一个
            if($param['type'] == 'photo'){
                $count = CategoryModelField::where('model',$param['model'])->where('type',$param['type'])->count();
                if($count>='1'){
                    $this->error('多图上传已存在');
                }
            }
			if($param['type'] == 'radio'){
                if(empty($param['options'])){
					$this->error('请设置选项值');
				}
            }
            $data = CategoryModelField::create($param);
            if ($data == true) {
                insert_admin_log('添加了字段');
                $this->success('添加成功',url('/Category/models'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('models_field_save');
    }
    //模型编辑
    public function models_field_edit()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'category_model_field_edit');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            $data = CategoryModelField::update($param,['id'=>$param['id']]);
            if ($data == true) {
                insert_admin_log('修改了字段');
                $this->success('修改成功');
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('models_field_save',['data'=> CategoryModelField::where('id', input('id'))->find()]);
    }
    //模型删除
    public function models_field_del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $data = CategoryModelField::where('id',$param['id'])->find();
            if($data['is_sys']=='1'){
                $this->error('系统字段，禁止删除');
            }else{
                CategoryModelField::destroy($param['id'],true);
                insert_admin_log('删除了字段');
                $this->success('删除成功');
            }
        }
    }
    
}
