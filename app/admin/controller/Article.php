<?php
namespace app\admin\controller;
use app\common\controller\AdminBase;
use think\facade\View;
use think\facade\Request;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Session;
use app\common\model\Article as ArticleModel;
use app\common\model\Category;
use app\common\model\Page;
use app\common\model\CategoryModelField;

class Article extends AdminBase
{
    protected $noAuth = ['index','get_category_tree','get_content','page_edit'];
    public function initialize()
    {
        parent::initialize();
        //$this->category = list_to_level(Category::where(['model'=>'article'])->order('sort_order asc')->select());
    }
    //内容列表
    public function index()
    {
        $cid = input('cid')?:'0';
        //判断是否单页
        if($cid != '0'){
            $category = Category::where('id',$cid)->find();
            if($category['model'] == 'page'){
                Page::CreateNewpage($cid);
                $data = Page::where('cid',$cid)->find();
                $field = CategoryModelField::get_list('page');
                return $this->fetch('page_save',['field'=>$field,'cid'=>$cid,'show_template' => Category::show_template(),'data'=>$data]);
            }
        }
        return $this->fetch('index',['cid'=>$cid,'category'=> list_to_level(Category::get_category_list($cid))]);
    }
    //获取树形栏目列表
    public function get_category_tree()
    {
        $model =  Category::field(['id','pid','pid'=>'parentId','name'=>'title','model'])->group('model')->select();
        foreach ($model as $k=>$m){
            $list[$k]['id'] = $k;
            $list[$k]['pid'] = "'".$m['model']."'";
            $category_model = \app\common\model\CategoryModel::where('model',$m['model'])->find();
            $list[$k]['title'] = $category_model['name'];
            $list[$k]['parentId'] = "'".$m['model']."'";
            $list[$k]['children'] = list_to_tree(collection(Category::field(['id','pid','pid'=>'parentId','name'=>'title','model'])->where('model',$m['model'])->order('sort_order desc,id desc')->select())->toArray());
        }
        $list = list_to_tree(collection($list)->toArray());
        $json = [
                'status' =>[
                    'code' => '200',
                    'message' => '成功'
                ],
                'data' => $list
            ];
        return json_encode($json);
    }
    //获取栏目数据
    public function get_content($limit='15')
    {
        $param = $this->request->param();
        $article = new ArticleModel();
        if (isset($param['title']) and $param['title'] <> '') {
            $article = $article->whereLike('title','%'.$param['title'].'%');
        }
        if (isset($param['is_top']) and $param['is_top'] <> '') {
            $article = $article->where('is_top',$param['is_top']);
        }
        if (isset($param['is_hot']) and $param['is_hot'] <> '') {
            $article = $article->where('is_hot',$param['is_hot']);
        }
        if (isset($param['status']) and $param['status'] <> '') {
            $article = $article->where('status',$param['status']);
        }
        if (isset($param['cid']) and $param['cid'] != '0') {
            $article = $article->where('cid',$param['cid']);
        }
        $list = $article->with(['category'])->order('id desc')->paginate($limit);
        $this->result($list);
    }
    public function add($cid = '0')
    {
        if ($this->request->isPost()) {
            $param = $this->request->param(false);
            $param['userid'] = Session::get('admin_auth.admin_id');
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'article');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            $param['jdata'] = $param;
            $data = ArticleModel::create($param);
            if ($data == true) {
                $url = ArticleModel::update_url($data->id);
                insert_admin_log('添加了文章');
                $this->success('添加成功', url('/Article/index',['cid'=>$cid]));
            } else {
                $this->error($this->errorMsg);
            }
        }
        $category = Category::get_category_list($cid);
        $model = Category::where('id',$cid)->find();
        $field = CategoryModelField::get_list($model['model'] ?: 'article');
        return $this->fetch('save',[
            'field'        => $field,
            'category'     => list_to_level($category),
			'cid'=>$cid,
            'show_template'=> Category::show_template()
        ]);
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param(false);
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'article');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            $param['jdata'] = $param;
            if (is_array($param['id'])) {
                $data = [];
                foreach ($param['id'] as $v) {
                    $data[] = ['id' => $v, $param['name'] => $param['value']];
                }
                $result = ArticleModel::saveAll($data);
            } else {
                $result = ArticleModel::update($param);
                $url = ArticleModel::update_url($param['id']);
            }
            if ($result == true) {
                insert_admin_log('修改了文章');
                $this->success('修改成功', url('/article/index'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        $data = ArticleModel::where('id',input('id'))->find();
        $model = Category::where('id',$data['cid'])->find();
        $field = CategoryModelField::get_list($model['model'] ?: 'article');
        return $this->fetch('save', [
            'data'         => $data,
            'field'        => $field,
			'cid'=>$data['cid'],
            'category'     => Category::get_category_list($data['cid']),
            'show_template'=> Category::show_template()
        ]);
    }

    public function del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            ArticleModel::destroy($param['id'],true);
            insert_admin_log('删除了文章');
            $this->success('删除成功');
        }
    }
    
    //内容添加
    public function page_edit($cid)
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'article');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            //添加
            $data = Page::update($param,['cid'=>$param['cid']]);
            if ($data == true) {
                $url = Page::update_url($param['cid']);
                insert_admin_log('修改了单页');
                $this->success('操作成功');
            } else {
                $this->error($this->errorMsg);
            }
        }
    }
    
    
}
