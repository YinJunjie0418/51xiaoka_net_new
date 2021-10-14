<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\BankPrcptcd;
use app\common\model\Payment;
use think\facade\Console;
use think\facade\View;
use app\common\model\Banks;


class Bank extends AdminBase
{

    public function index($limit=15)
    {
		if ($this->request->isAjax()){
			$name=input('shop_id');
			if($name){
			    $list = Banks::where('bankName','like',"%".$name."%")->order('id desc')->paginate($limit);
			}else{
				$list = Banks::order('id desc')->paginate($limit);
			}
			foreach ($list as $item=>$v){
			    $list[$item]['num']=BankPrcptcd::where(['bank_code'=>$v['procode']])->count();
            }
			$this->result($list);
		}


        return $this->fetch('index');
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $result = Banks::create($param);
            if ($result == true) {
                $this->success('添加成功',url('/Bank/index'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        $lists=openJson('bank');
        View::assign('bankid',$lists);
        return $this->fetch('save');
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
			$id=$param['id'];
			unset($param['id']);
			unset($param['_verify']);
            $data = Banks::where(['id'=>$id])->update($param);
            if ($data == true) {
                $this->success('修改成功', url('/Bank/index'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        View::assign('bankid',openJson('bank'));
        $data = Banks::where(['id'=>input('id')])->find();
        return view('save', [
            'data' => $data]);
    }

    public function del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            Banks::destroy($param['id'],true);
            $this->success('删除成功');
        }
    }

    public function payment($limit=15){
        if ($this->request->isAjax()){
            $list = Payment::order('id desc')->paginate($limit);
            $this->result($list);
        }
        return $this->fetch('payment');
    }

    public function addbank()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $result = Payment::create($param);
            if ($result == true) {
                $this->success('添加成功',url('/Bank/payment'));
            } else {
                $this->error("系统错误，添加失败");
            }
        }
        return $this->fetch('savebank');
    }

    public function editbank()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            $id=$param['id'];
            unset($param['id']);
            unset($param['_verify']);
            $data = Payment::where(['id'=>$id])->update($param);
            if ($data == true) {
                $this->success('修改成功', url('/Bank/payment'));
            }else{
                $this->error($this->errorMsg);
            }
        }
        $data = Payment::where(['id'=>input('id')])->find();
        return view('savebank', ['data' => $data]);
    }

    public function delbank()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            Payment::destroy($param['id'],true);
            $this->success('删除成功');
        }
    }

    public function updata(){
        if($this->request->isAjax()){
            $procode=input('procode');
            if(!$procode)$this->error("该银行没有匹配关键数据，请重新编辑");
            $ok=\think\facade\Queue::push("app\home\job\Jobone@getAdress", ['id'=>'addprocode','procode'=>$procode],'ipJobQueue');
            if($ok){
                $this->error("正在执行更新操作，请稍后刷新");
            }else{
                $this->error("发送任务失败");
            }
        }
    }
    
}
