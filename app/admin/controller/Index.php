<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\CashNumberLog;
use app\common\model\Totaldata;
use think\facade\View;
use think\facade\Session;

use app\common\model\AuthGroupAccess;
use app\common\model\AuthRule;
use app\common\model\Admin;
use app\common\model\Order;
use app\common\model\Withdraw;
use app\common\model\UserAuth;
use app\common\model\User;
use app\common\model\CashOrder;

class Index extends AdminBase
{
    protected $noLogin = ['login', 'captcha','sendmsg'];
    protected $noAuth = ['index', 'uploadImage','home', 'getDa','icon','editPassword','guanbi', 'ssedistill','logout'];

    //后台首页
    public function index()
    {
        $userinfo = Session::get('admin_auth');
        return View::fetch('index',['userinfo'=>$userinfo]);
    }
    //默认首页
    public function home()
    {
        // 快捷导航
        $where = ['index' => 1, 'status' => 1];
        if (session('admin_auth.username') != config('administrator')) {
            $access      = AuthGroupAccess::with('authGroup')
                ->where('uid', session('admin_auth.admin_id'))->find();
            $where['id'] = ['in', $access['rules']];
        }
        $index = AuthRule::where($where)->order('pid asc,sort_order asc')->select();
        //统计信息
        $da['real']=UserAuth::where(['retype'=>3])->count();
        $da['cash']=Withdraw::where(['status'=>1])->count();
        $total=Totaldata::field('sum(count) as count,sum(apicount) as apicount,sum(apioknum) as apioknum,sum(apiokmoney) as apiokmoney,sum(apierrnum) as apierrnum,sum(apierrmoney) as apierrmoney,sum(apimoney) as apimoney,sum(apiprofit) as apiprofit,sum(money) as money,sum(oknum) as oknum,sum(okmoney) as okmoney,sum(errnum) as errnum,sum(errmoney) as errmoney,sum(profit) as profit,sum(cashnum) as cashnum,sum(cashmoney) as cashmoney,sum(cashoknum) as cashoknum,sum(cashokmoney) as cashokmoney,sum(casherrnum) as casherrnum,sum(casherrmoney) as casherrmoney,sum(cashprofit) as cashprofit')->select();
        $total=$total[0];
        $day=Totaldata::whereDay('create_time')->find();
        $da['order']=cache('order');
        $da['api']=cache('apiorder');
        // 服务器信息
        $server = [
            'os'                  => PHP_OS, // 服务器操作系统
            'sapi'                => PHP_SAPI, // 服务器软件
            'version'             => PHP_VERSION, // PHP版本
            'mysql'               => '5.7', // mysql 版本
            'root'                => $_SERVER['DOCUMENT_ROOT'], // 当前运行脚本所在的文档根目录
            'max_execution_time'  => ini_get('max_execution_time') . 's', // 最大执行时间
            'upload_max_filesize' => ini_get('upload_max_filesize'), // 文件上传限制
            'memory_limit'        => ini_get('memory_limit'), // 允许内存大小
            'date'                => date('Y-m-d H:i:s', time()), // 服务器时间
        ];
        $num['user']=User::count();
        $num['d_user']=User::whereDay('create_time')->count();
        $num['card_count']=$total['count']??0;
        $num['card_num']=$total['money']??0;

        $num['d_card_count']=$day['count']??0;
        $num['d_card_num']=$day['money']??0;

        $num['secc_count']=$total['oknum']??0;
        $num['secc_num']=$total['okmoney']??0;

        $num['d_secc_count']=$day['oknum']??0;
        $num['d_secc_num']=$day['okmoney']??0;

        $num['err_count']=$total['errnum']??0;
        $num['err_num']=$total['errmoney']??0;

        $num['d_err_count']=$day['errnum']??0;
        $num['d_err_num']=$day['errmoney']??0;

        $num['profit']=$total['profit']??0;
        $num['d_profit']=$day['profit']??0;

        $numb['card_count']=$total['apicount']??0;
        $numb['card_num']=$total['apimoney']??0;

        $numb['d_card_count']=$day['apicount']??0;
        $numb['d_card_num']=$day['apimoney']??0;

        $numb['secc_count']=$total['apioknum']??0;
        $numb['secc_num']=$total['apiokmoney']??0;

        $numb['d_secc_count']=$day['apioknum']??0;
        $numb['d_secc_num']=$day['apiokmoney']??0;

        $numb['err_count']=$total['apierrnum']??0;
        $numb['err_num']=$total['apierrmoney']??0;

        $numb['d_err_count']=$day['apierrnum']??0;
        $numb['d_err_num']=$day['apierrmoney']??0;

        $numb['profit']=$total['apiprofit']??0;
        $numb['d_profit']=$day['apiprofit']??0;

        $numa['card_count']=$total['cashnum']??0;
        $numa['card_num']=$total['cashmoney']??0;

        $numa['d_card_count']=$day['cashnum']??0;
        $numa['d_card_num']=$day['cashmoney']??0;

        $numa['secc_count']=$total['cashoknum']??0;
        $numa['secc_num']=$total['cashokmoney']??0;

        $numa['d_secc_count']=$day['cashoknum']??0;
        $numa['d_secc_num']=$day['cashokmoney']??0;

        $numa['err_count']=$total['casherrnum']??0;
        $numa['err_num']=$total['casherrmoney']??0;

        $numa['d_err_count']=$day['casherrnum']??0;
        $numa['d_err_num']=$day['casherrmoney']??0;

        $numa['profit']=$total['cashprofit']??0;
        $numa['d_profit']=$day['cashprofit']??0;

        return View::fetch('home', ['index' => $index,'da'=>$da, 'server' => $server,'f'=>$num,'ff'=>$numb,'df'=>$numa]);
    }

    public function guanbi(){
        $tab=input('tab');
        switch($tab){
            case 'Order':
                cache('order',0);
                break;
            case 'Apiorder':
                cache('apiorder',0);
                break;
            case 'recharge':
                cache('recharge',0);
                break;
            default:
                cache('cash',0);
        }
    }

    public function ssedistill(){
        $order=cache('order');
        $num=cache('cash');
        $api=cache('apiorder');
        $bigcard=cache('bigcard');
        $recharge=cache('recharge');
        $war=cache('adminErr');
        cache('order',0);
        cache('apiorder',0);
        cache('recharge',0);
        cache('cash',0);
        return json(['order'=>$order,"cash"=>$num,'api'=>$api,'bigcard'=>$bigcard,'recharge'=>$recharge,'war'=>$war]);
    }

    public function getDa(){
        $arr=[];
        for($i=7;$i>=0;$i--){
            $s=date('Y-m-d 00:00:00',strtotime('-'.$i.' day'));
            $e=date('Y-m-d 23:59:59',strtotime('-'.$i.' day'));
            $arr['t'][]=date('m/d',strtotime('-'.$i.' day'));
            $total=Totaldata::whereTime('create_time','between',[$s,$e])->find();
            $arr['pay'][]=$total['apimoney'];
            $arr['rech'][]=$total['money'];
            $arr['user'][]=(new Withdraw)->whereTime('create_time','between',[$s,$e])->sum('money');
            $arr['cash'][]=$total['cashokmoney'];
            $arr['lirun'][]=(float)sprintf("%.2f",$total['profit']+$total['cashprofit']+$total['apiprofit']);
        }
        return json(['code'=>1,'data'=>$arr]);
    }

    //修改密码
    public function editPassword()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            // 验证条件
            empty($param['password']) && $this->error('请输入旧密码');
            empty($param['new_password']) && $this->error('请输入新密码');
            empty($param['rep_password']) && $this->error('请输入确认密码');
            !check_password($param['new_password'], 6, 16) && $this->error('请输入6-16位的密码');
            $param['new_password'] != $param['rep_password'] && $this->error('两次密码不一致');
            $admin = Admin::where('id', session('admin_auth.admin_id'))->find();
            !md6($param['password'],$admin['password']) && $this->error('旧密码错误');
            $data = ['id' => session('admin_auth.admin_id'), 'password' => $param['new_password']];
            $result = Admin::update($data);
            if ($result == true) {
                insert_admin_log('修改了登录密码');
                session('admin_auth', null);
                session('admin_auth_sign', null);
                $this->success('更新成功', url('/index/index'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('editPassword');
    }

}
