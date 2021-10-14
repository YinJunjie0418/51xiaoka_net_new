<?php
namespace app\common\controller;
use think\facade\Db;
use think\facade\View;
use think\facade\Config as Con;
use app\common\model\Article;
use app\common\model\CardModel;
use app\common\model\CardList;
use app\common\model\User;
use app\common\model\Sysconfig;
use think\facade\Request;

class IndexBase extends Base
{
   public $user;
   public $iswx;
   public $isreg;

	
   public function initialize()
    {
        parent::initialize();
		$res=Sysconfig::select()->toArray();
		$list=[];
		foreach($res as $k=>$v){
			$list[$v['name']]=$v['value'];
		}
		$this->isreg=isset($list['isreg'])?$list['isreg']:'off';
		$wx = Con::load('setting/wxapp','wxapp');
		$wxapp = Con::load('setting/wxpay','wxpay');
		$aly = Con::load('setting/aly','aly');
		$this->real=$aly;
		$news=Article::where(['status'=>1,'is_top'=>1])->select();
		$cardModel=CardModel::with('comments')->where(['status'=>1])->order("sort_order desc,id desc")->select()->toArray();
		$rt=feilvto();
		$this->feilv=$rt;
		foreach($cardModel as $k=>$v){
			if($v['istype']==1){
                $comments=CardList::where(['is_hot'=>1])->select()->toArray();
			    $rem=[];
			    foreach ($comments as $ke=>$item){
			        $rem[$ke]=$item;
			        $rem[$ke]['feilv']=isset($rt[$item['id']])?$rt[$item['id']]:0;
                }
				$cardModel[$k]['comments']=$rem;
			}else{
                $rem=[];
                foreach ($v['comments'] as $ke=>$item){
                    $rem[$ke]=$item;
                    $rem[$ke]['feilv']=isset($rt[$item['id']])?$rt[$item['id']]:0;
                }
                $cardModel[$k]['comments']=$rem;
            }
		}

		if($id=is_user_login()){
			$this->user=User::with('userReal')->where(['id'=>$id,'token'=>session("user_auth.token")])->whereTime('timeout','>',time())->find();
			if(!$this->user){
				session('user_auth', null);
                session('user_auth_sign', null);
			}
			
		}
        View::assign("back",str_replace("\\","/",isset($list['loginimg'])?$list['loginimg']:""));
		$isqq=con::load('setting/qq','qq');
		$this->iswx=isset($wxapp['appid'])?$wxapp['appid']:'';
		$cidwen=Article::where(['status'=>1,'cid'=>12])->limit(12)->select();
		$zi=Article::where(['status'=>1,'cid'=>24])->limit(12)->select();
		View::assign('isqq',$isqq['qqlogin']);
		View::assign('iswx',isset($wxapp['wxlogin'])?$wxapp['wxlogin']:'');
		View::assign("contr",Request::controller());
        View::assign("action",Request::action());
		View::assign("remen",$rem);
		View::assign("C",array_merge($list,$wxapp));
		View::assign("wx",$wx);
		View::assign("thisli",$news);
		View::assign("cardModel",$cardModel);
		View::assign("wen",$cidwen);
		View::assign("zi",$zi);
    }
}
