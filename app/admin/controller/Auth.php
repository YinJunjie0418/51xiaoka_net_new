<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\facade\View;
use think\facade\Request;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Session;

use app\common\model\AuthRule;
use app\common\model\AuthGroup;

class Auth extends AdminBase
{
    
    public function group()
    {
        return $this->fetch('group');
    }
    public function group_json($limit='15')
    {
        $list =  AuthGroup::paginate($limit);
        $this->result($list);
    }

    public function addGroup()
    {
        if ($this->request->isPost()) {
            $result = AuthGroup::create($this->request->param());
            if ($result == true) {
                insert_admin_log('添加了用户组');
                $this->success('添加成功', url('/auth/group'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        $authRule = collection(AuthRule::where(['status' => 1])->order('sort_order asc')->select())->toArray();
        foreach ($authRule as $k => $v) {
            //$authRule[$k]['open'] = true;
        }
        
        return View::fetch('saveGroup', ['authRule' => list_to_tree($authRule)]);
    }

    public function editGroup()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $verify = input('_verify', true);
            //验证规则
            if($verify!='0'){
                try{
                    $this->validate($param, 'authGroup');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            //更新数据
            $resule = AuthGroup::update($param);
            if ( $resule == true) {
                insert_admin_log('修改了用户组');
                $this->success('修改成功', url('/auth/group'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        $data     = AuthGroup::where('id', input('id'))->find();
        $authRule = collection(AuthRule::where(['status' => 1])->order('sort_order asc')->select())->toArray();
        foreach ($authRule as $k => $v) {
            $authRule[$k]['checked'] = in_array($v['id'], explode(',', $data['rules']));
        }
        return $this->fetch('saveGroup', ['data' => $data, 'authRule' => list_to_tree($authRule)]);
    }

    public function delGroup()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            AuthGroup::destroy($param['id']);
            insert_admin_log('删除了用户组');
            $this->success('删除成功');
        }
    }

    public function rule()
    {
        $authRule = collection(AuthRule::where(['status' => 1])->order('sort_order asc')->select())->toArray();
        foreach ($authRule as $k => $v) {
            //$authRule[$k]['open'] = true;
        }
        return $this->fetch('rule', ['authRule' => list_to_tree($authRule)]);
    }

    public function addRule()
    {
        if ($this->request->isPost()) {
            $result = AuthRule::create($this->request->param());
            if ($result == true) {
                insert_admin_log('添加了权限规则');
                $this->success('添加成功', url('/auth/rule'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('saveRule');
    }

    public function editRule()
    {
        if ($this->request->isPost()) {
            $result = AuthRule::update($this->request->param());
            if ($result == true) {
                insert_admin_log('修改了权限规则');
                $this->success('修改成功', url('/auth/rule'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        return view('saveRule', ['data' => AuthRule::where('id', input('id'))->find()]);
    }

    public function delRule()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            AuthRule::where('pid', input('id'))->count() && $this->error('请先删除子节点');
            AuthRule::destroy($param['id']);
            insert_admin_log('删除了权限规则');
            $this->success('删除成功');
        }
    }
}
