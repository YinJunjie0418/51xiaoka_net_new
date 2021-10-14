<?php
namespace app\common\controller;
use app\common\model\AuthRule;
use app\common\model\AuthGroupAccess;
use app\common\model\Sysconfig;
use think\facade\Session;
use think\facade\View;
use app\common\model\Security;
use app\common\model\Admin as Yonghu;

class AdminBase extends Base
{
    protected $noLogin = []; // 不用权限认证和登录的方法
    protected $noAuth = ['getModels','getFeilv']; // 不用权限认证要登录的方法


    public function initialize()
    {
        parent::initialize();
        $arr=[
            'ip'          => request()->ip(),
            'url'         => request()->url(true),
            'method'      => request()->method(),
            'type'        => request()->type(),
            'param'       => request()->param(),
            'create_time' => date("Y-m-d H:i:s")
        ];
        if(!strstr($arr['url'], 'ssedistill'))trace(json_encode($arr),'notice');
        $this->safe=Security::order("id desc")->find();

        if(!is_admin_login($this->safe)){
            session('admin_auth', null);
            session('admin_auth_sign', null);
            session("safecode",null);
            if(request()->controller()=="Index" && request()->action()=='index')$this->redirect(url('/Login/index'));
             $this->error("你的账号在其他地方登陆",url('/Login/index'));
		}else{
			$ad=Yonghu::find(is_admin_login($this->safe));
			if(!$ad){
				session('admin_auth', null);
                session('admin_auth_sign', null);
                session("safecode",null);
				$this->redirect(url('/Login/index'));
			}
			!$this->checkAuth() && $this->error('没有权限，请联系管理员');
			View::assign("safe",$this->safe);
            $res=Sysconfig::select()->toArray();
            $list=[];
            foreach($res as $k=>$v){
                $list[$v['name']]=$v['value'];
            }
			if($this->request->isGet()){
			    View::assign('title',$list['sitename']);
                View::assign('url',$list['domain']);
				View::assign('navbar', list_to_tree($this->getNavbar()));
				View::assign('breadcrumb', array_reverse(explode(',', $this->getBreadcrumb())));
				View::assign('empty', '<tr><td colspan="20">~~暂无数据</td></tr>');
			}
		}
    }
	
    // 权限认证
    public function checkAuth($toaction='')
    {
        if(substr($this->request->action(),-5) == '_json'){
            $action = substr($this->request->action(),0,-5);
        }else{
            $action = $this->request->action();
        }

        $action=$toaction?$toaction:$action;
        if (Session::get('admin_auth.admin_id') != '1' &&
            !in_array($this->request->action(), $this->noLogin) &&
            !in_array($this->request->action(), $this->noAuth) &&
            !(new \core\Auth())->check("/".to_under_score($this->request->controller()).'/'
                . $action, Session::get('admin_auth.admin_id'))) {
            return false;
        }
        return true;
    }

    // 获取导航栏
    public function getNavbar()
    {
        $where = ['type' => 'nav', 'status' => 1];
        if (Session::get('admin_auth.admin_id') != '1'){
            $access  = AuthGroupAccess::with('authGroup')->where('uid', session('admin_auth.admin_id'))->find();
            if($access){
                $where = "type='nav' and status = '1' and id in(".$access['rules'].")";
            }
        }
        $navs = AuthRule::where($where)->order('sort_order asc')->select();
        return collection($navs)->toArray();
    }

    // 获取面包屑
    public function getBreadcrumb($id = null)
    {
        if ($authRule = AuthRule::where(empty($id) ? ['url' => 'admin/'
            . to_under_score($this->request->controller()) . '/'
            . $this->request->action()] : ['id' => $id])->order('pid desc')->find()) {
            $breadcrumb = $authRule['name'];
            if ($authRule['pid'] != 0) {
                $breadcrumb .= ',' . $this->getBreadcrumb($authRule['pid']);
            }
            return $breadcrumb;
        }
    }
}
