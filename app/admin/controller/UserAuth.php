<?php
namespace app\admin\controller;
use app\common\controller\AdminBase;
use think\facade\View;
use think\facade\Request;
use app\common\model\User;
use app\common\model\UserAuth as UA;

class UserAuth extends AdminBase
{
    protected $noAuth = ['index','lookPhoto'];

    public function index($limit=15)
    {
		if ($this->request->isAjax()) {
			$shopid=input('shopid');
			if($shopid){
				$map[]=['name','like',"%{$shopid}%"];
				$list=UA::with('uname')->where($map)->order("id desc")->paginate($limit);
			}else{
				$list=UA::with('uname')->order("id desc")->paginate($limit);
			}
			
			foreach($list as $k=>$v){
				$list[$k]['rename']=realType($v['clas']);
				$list[$k]['shopid']=$v['shopid']?$v['shopid']:'已删除';
			}
			$this->result($list);
		}
        return view();
    }

    public function lookPhoto(){
		$da=input();
		$res=UA::where(['id'=>$da['id']])->find();
		return View($da['tpl'],['data'=>$res]);
	}
    public function edit()
    {
        if ($this->request->isAjax()) {
            $param = $this->request->param();
			if($param['real']==2){
				$arr['retype']=2;
			}else{
				$arr['retype']=1;
			}
			$result=UA::where(['id'=>$param['id']])->update($arr);
            if ($result == true) {
                $this->success('审核成功', url('/user_auth/group'));
            } else {
                $this->error($this->errorMsg);
            }
        }
    }
	
	public function editres(){
		if ($this->request->isAjax()) {
            $param = $this->request->param();
			$result=UA::where(['id'=>$param['id']])->update(['remarks'=>$param['remarks'],'retype'=>4]);
            if ($result == true) {
                $this->success('审核成功', url('/user_auth/group'));
            } else {
                $this->error($this->errorMsg);
            }
		}
	}

    public function del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            UA::destroy($param['id'],true);
            $this->success('删除成功');
        }
    }

    
}
