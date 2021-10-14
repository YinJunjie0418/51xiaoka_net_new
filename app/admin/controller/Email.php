<?php
namespace app\admin\controller;
use app\common\controller\AdminBase;
use think\facade\View;
use think\facade\Request;
use app\common\model\Emaillog;
use app\common\model\UserShopFeilv;
use app\common\model\UserFeilvModel;


class Email extends AdminBase
{
   
    public function index($limit=15)
    {
        if($this->request->isAjax()){
            $res=Emaillog::paginate($limit,false,['query' => request()->param()]);;
            foreach($res as $k=>$v){
                $res[$k]['jindu']=sprintf("%.2d",$v['sum']/$v['sendnum'])*100 ."%";
                $str='';
                if(empty($v['userlist'])){
                    if($v['shoplist']){
                        foreach ($v['shoplist'] as $kk=>$vv){
                            $name=UserShopFeilv::where(['id'=>$vv])->value('name');
                            $str.="[{$name}]";
                        }
                    }
                    if($v['selllist']){
                        foreach ($v['selllist'] as $ka=>$va){
                            $name=UserFeilvModel::where(['id'=>$va])->value('name');
                            $str.="[{$name}]";
                        }
                    }
                    $res[$k]['yonghu']=$str;
                }else{
                    $res[$k]['yonghu']=$v['userlist'];
                }
                if(is_array($v['errmsg'])){
                    foreach($v['errmsg'] as $v){
                        $res[$k]['err'].="([{$v['id']}]{$v['msg']})";
                    }
                }
            }
            $this->result($res);
        }
        return view();
    }
   
}
