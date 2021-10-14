<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\facade\View;
use think\facade\Request;
use app\common\model\Wechat;
use app\common\model\Wechattpl;


class Weixin extends AdminBase
{
   

    public function index()
    {
		$app=Wechat::weixin();
		try{
		    $list = $app->menu->list();
		}catch(\Exception $e) {
		    echo "请确认微信配置是否正确:".$e->getMessage();exit;
	    }
		$menu=isset($list['menu']['button'])?$list['menu']['button']:[];
		cache("menu",$menu);
        return $this->fetch('index',['list' =>cache("menu")]);
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $app=Wechat::weixin();
            $result=$app->menu->create($param);
            if ($result['errcode'] == '0') {
                $this->success('发布成功');
            } else {
                $this->error($result['errmsg']);
            }
        }
    }
    
    public function wxtpl($limit=15){
        if ($this->request->isAjax()) {
            $app=Wechat::weixin();
			try{
				$list=$app->template_message->getPrivateTemplates();
				$this->result(['data'=>$list['template_list'],'total'=>count($list['template_list'])]);
			}catch(\Exception $e) {
				$this->error("请确认微信配置是否正确:".$e->getMessage());
			}
        }
        return view();
    }
    
    public function gettpl(){
        if($this->request->isAjax()){
             $app=Wechat::weixin();
             $list=$app->template_message->getPrivateTemplates();
             $this->result($list['template_list']);
        }
        return view();
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            if(empty($param['id'])){
                $param['status']=1;
                $data=Wechattpl::create($param);
            }else{
               $data = Wechattpl::update($param);
            }
            if ($data == true) {
                $this->success('操作成功', url('/weixin/wxtpl'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        $data = Wechattpl::where('id', input('id'))->find();
        return $this->fetch('save', [
            'info' => $data]);
    }
    
    public function message(){
        if($this->request->isAjax()){
            $offset=0;
            $page=15;
            $app=Wechat::weixin();
			try{
				$list=$app->material->list('news', $offset, $page);
				$this->result($list);
			}catch(\Exception $e) {
				$this->error("请确认微信配置是否正确:".$e->getMessage());
			}
        }
        return view();
    }
    
    public function addmsg(){
        return view();
    }

    public function del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $app=Wechat::weixin();
			$app->template_message->deletePrivateTemplate($param['id']);
            $this->success('删除成功');
        }
    }

 

}
