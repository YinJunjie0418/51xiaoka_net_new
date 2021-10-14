<?php
declare (strict_types = 1);

namespace app\home\controller;
use app\common\controller\IndexBase;
use app\common\library\MongoClass;
use app\common\model\CardChannel;
use app\common\model\CardModel;
use app\common\model\CardList;
use app\common\model\Order;
use app\common\model\Totaldata;
use think\facade\Cache;
use think\facade\Log;
use think\facade\View;
use app\common\model\Card as AD;
use app\common\model\UserShopFeilv;

class Card extends IndexBase
{
    private $cardall;
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $da=input();
        $okcid=CardModel::where(['status'=>1,'istype'=>0])->order('sort_order desc')->find()['id'];
        $cid=isset($da['cid'])?$da['cid']:$okcid;
        $id=0;
        if(isset($da['id'])){
            $cid=CardList::where(['id'=>$da['id']])->value('cid');
        }
        $res=CardModel::where(['id'=>$cid])->find();
        View::assign("cid",$cid);
        View::assign("tid",isset($da['id'])?$da['id']:$id);
        View::assign("ress",$res);
        if(request()->isMobile()){
            return view('card/wap/index');
        }else{
            return view();
        }
    }

    public function mach(){
        $da=input('post.');
        $preg=CardList::where(['type'=>$da['type']])->find();
        if($preg && !empty($preg['regularity'])){
            $str=$da['card'];
            $str=preg_replace("/([\x{4e00}-\x{9fa5}])/u",",",$str);
            $pattern="/[\\space|\\:：.,\\/\\\_]/";
            $spr=preg_split($pattern,$str);
			$spr=array_values(array_filter($spr));
			if($da['type']==201){
			    $regarr=explode('.',$preg['regularity']);
			}else{
                $regarr=explode(',',$preg['regularity']);
			}
            $rega="/".$regarr[0]."/";
            $regb=count($regarr)==2?"/".$regarr[1]."/":"";
            $regc=count($regarr)==3?"/".$regarr[2]."/":"";
            $regdan="/".$preg['regularity']."/";
            $nt=[];
            $ok=0;
            for($i=0;$i<count($spr);$i++){
                if($preg['iskami']==0 && !empty($regb)){
                    if($preg['isyzm']>0){
                        if(preg_match($rega,$spr[$i]) && preg_match($regb,$spr[$i+1]) && preg_match($regc,$spr[$i+2])){
                            $nt[]=$spr[$i]." ".$spr[$i+1]." ".$spr[$i+2];
                            $i++;
                            $ok++;
                        }
                    }else{
                        if(preg_match($rega,$spr[$i]) && preg_match($regb,$spr[$i+1])){
                            $nt[]=$spr[$i]." ".$spr[$i+1];
                            $i++;
                            $ok++;
                        }
                    }
                }else{
                    if(preg_match($regdan,$spr[$i])){
                        $nt[]=$spr[$i];
                        $ok++;
                    }
                }
            }
            return json(['result'=>1,'msg'=>$nt,'ok'=>$ok]);
        }else{
            return json(['result'=>1,'msg'=>$da['card'],'ok'=>0]);
        }
    }

    public function tocard(){
        $da=input();
        if(request()->isPost()){
            if(cookie('isan')){
                return json(['confirm'=>['width'=>'350', 'prompt'=> "warning"],'content'=>"请不要频繁操作",'list'=> [],'type'=>"2"]);
            }else{
                cookie('isan',1,1);
            }
        }
        if(!is_user_login()){
            return json(['run'=> "login"]);
        }
        if($this->user['userReal']['retype']!=1 && $this->user['userReal']['retype']!=2){
            return json(['confirm'=>['width'=>'350', 'prompt'=> "warning"],'content'=>"请先实名认证",'list'=> [],'type'=>"2"]);
        }
        //if(empty($this->user['userReal']['evidenceHash'])){
            // return json(['confirm'=>['width'=>'350', 'prompt'=> "warning"],'content'=>"请先签署协议",'list'=> [],'type'=>"2"]);
        //}
        try{
            $order=build_order_no('D');
            $card=CardList::where(['type'=>$da['cardtype']])->find();
            $type=$card['tid'];
            if($da['urgent']){
                if(($da['feilv']>$card['pricemax'] || $da['feilv']<$card['pricemin']) && $da['feilv']>0){
                    return json(['confirm'=>['width'=>'350', 'prompt'=> "warning"],'content'=>"自定义费率区间{$card['pricemin']}-{$card['pricemax']}",'list'=> [],'type'=>"2"]);
                }
            }
            $ip=request()->ip();
            $userfeilv=getUserFeilv(session("user_auth.user_id"),$da['cardtype'],$da['cardprice']);
            $mcode=CardChannel::where(['listid'=>$card['id']])->value('type');
            $ut=UserShopFeilv::field('isrest')->where(['id'=>$this->user['rategroup']])->find();
            $isrest=0;
            if(isset($ut['isrest'])){
                $isrest=$ut['isrest'];
            }
            switch($da['type']){
                case 0://单张提交
                    $ok=$this->yanka($da['cardtype'],$da['cardno'][0]." ".$da['cardpsw'][0]." ".(isset($da['cardcode'][0])?$da['cardcode'][0]:""),$card,$isrest);
                    if(is_array($ok)){
                        $map['uid']=session("user_auth.user_id");
                        $map['type']=$type;
                        $map['class']=$da['cardtype'];
                        $map['mcode']=$mcode;
                        $map['orderno']=build_order_no();
                        $map['batchno']=$order;
                        $map['money']=$da['cardprice'];
                        $map['card_no']=$ok[0];
                        $map['card_key']=$ok[1];
                        $map['seccode']=isset($ok[2])?$ok[2]:"";
                        $map['ip']=$ip;
                        $map['jiajiok']=$da['urgent'];
                        $map['feilv']=isset($da['feilv'])?(empty($da['feilv'])?$userfeilv:$da['feilv']):$userfeilv;
                        $map['create_time']=time();
                        (new Order)->insert($map);
                        MongoClass::addBatchno($order,2,session("user_auth.user_id"),$da['cardtype']);
                        (new Totaldata)->addData(['count'=>['inc',1],'money'=>['inc',$da['cardprice']]]);
                        \think\facade\Queue::push("app\home\job\Jobone@sendCard", $map,'sendCardJobQueue');
                        Cache::inc('order');
                        return json(["state"=>1,"type"=>"0","content"=>"共有 1 张卡成功提交,请在卖卡明细中查看进度！","list"=>[],"confirm"=>["name"=>"提交成功","prompt"=>"success","width"=>400,"time"=>5,"url"=>"reload"]]);
                    }else{
                        return json(['confirm'=>['width'=>'350', 'prompt'=> "warning"],'content'=>$ok,'list'=> [],'type'=> "2"]);
                    }
                    break;
                case 1://批量提交
                    $data=str_replace("\r\n",',',$da['cardlist']);
                    $data=str_replace("\n",',',$data);
                    $data=str_replace("\r",',',$data);
                    $list=explode(',',$data);
                    $err=[];
                    $errnum=0;
                    $map=[];
                    $unique=input('submit');
                    foreach($list as $k=>$v){
                        $ok=$this->yanka($da['cardtype'],$v,$card,$isrest);
                        if(is_array($ok) && !cache($unique.$ok[0])){
                            $map[$k]['uid']=session("user_auth.user_id");
                            $map[$k]['type']=$type;
                            $map[$k]['class']=$da['cardtype'];
                            $map[$k]['mcode']=$mcode;
                            $map[$k]['orderno']=build_order_no();
                            $map[$k]['batchno']=$order;
                            $map[$k]['money']=$da['cardprice'];
                            $map[$k]['card_no']=$ok[0];
                            $map[$k]['card_key']=$ok[1];
                            $map[$k]['seccode']=isset($ok[2])?$ok[2]:"";
                            $map[$k]['ip']=$ip;
                            $map[$k]['jiajiok']=$da['urgent'];
                            $map[$k]['feilv']=isset($da['feilv'])?(empty($da['feilv'])?$userfeilv:$da['feilv']):$userfeilv;
                            $map[$k]['create_time']=time();
                            cache($unique.$ok[0],'1',5);
                        }else{
                            if(cache($unique.$ok[0])){
                                cache($unique.$ok[0],null);
                                $ok="卡号重复【".$this->getCardno($ok[0])."】";
                            }
                            $err[$k]=$ok;
                            $errnum++;
                        }
                    }
                    $count=count($list);
                    if($errnum>0 && $unique==0){
                        $msg=['confirm'=> ['width'=>400, 'prompt'=> "info"],'num'=>$count,'content'=>"共提交{$count}张卡卷,有{$errnum}张卡劵有错误",'list'=>$err,'state'=>2,'type'=>1];
                    }else{
                        if(count($map)==0){
                            $msg=['confirm'=>['width'=>'350', 'prompt'=> "warning"],'content'=>"提交失败，提交成功了0张卡卷",'list'=> [],'type'=>"2"];
                        }else{
                            (new Order)->insertAll($map);
                             MongoClass::addBatchno($order,2,session("user_auth.user_id"),$da['cardtype']);
                            (new Totaldata)->addData(['count'=>['inc',$count],'money'=>['inc',$count*$da['cardprice']]]);
                            $i=0;
                            foreach($map as $k=>$v){
                                if($i%3==0){
                                   \think\facade\Queue::push("app\home\job\Jobone@sendCard", $v,'sendCardbJobQueue');
                                }elseif($i%2==0) {
                                   \think\facade\Queue::push("app\home\job\Jobone@sendCard", $v,'sendCardcJobQueue');
                                }else{
                                    \think\facade\Queue::push("app\home\job\Jobone@sendCard", $v,'sendCardJobQueue');
                                }
                                $i++;
                            }
                            Cache::inc('order',$count);
                            $msg=["state"=>1,"type"=>2,'num'=>$count,"content"=>"共提交{$count}张卡卷,有{$errnum}张卡劵提交失败,请在卖卡明细中查看进度！","list"=>$err,"confirm"=>["name"=>"提交成功","prompt"=>"success","width"=>400,"time"=>5,"url"=>"reload"]];
                        }
                    }
                    return json($msg);
                    break;
                case 2://图片提交
                    $data=str_replace("\r\n",',',$da['cardlist']);
                    $data=str_replace("\n",',',$data);
                    $data=str_replace("\r",',',$data);
                    $list=explode(',',$data);
                    $n=0;
                    foreach($list as $k=>$v){
                        if(empty($v))continue;
                        $map[$k]['uid']=session("user_auth.user_id");
                        $map[$k]['type']=$type;
                        $map[$k]['class']=$da['cardtype'];
                        $map[$k]['mcode']=$mcode;
                        $map[$k]['orderno']=build_order_no();
                        $map[$k]['batchno']=$order;
                        $map[$k]['money']=$da['cardprice'];
                        $map[$k]['card_no']=$v;
                        $map[$k]['card_key']=$k;
                        $map[$k]['ip']=$ip;
                        $map[$k]['jiajiok']=$da['urgent'];
                        $map[$k]['feilv']=isset($da['feilv'])?$da['feilv']:$userfeilv;
                        $map[$k]['state']=1;
                        $n++;
                    }
                    (new Order)->saveAll($map);
                    $msg=["state"=>1,"type"=>"0",'num'=>$n,"content"=>"共有{$n} 张卡成功提交,请在卖卡明细中查看进度！","list"=>[],"confirm"=>["name"=>"提交成功","prompt"=>"success","width"=>400,"time"=>5,"url"=>"reload"]];
                    return json($msg);
                    break;
                default:
                    return json(['code'=>-1,'msg'=>'参数错误']);
            }
        }catch (\Exception $e){
            return json(['code'=>-1,'msg'=>$e->getMessage()]);
        }
    }

    public function yanka($type,$card,$preg,$isrest){
		if($preg && $preg['regularity']){
			$regarr=explode(',',$preg['regularity']);
			$rega="/{$regarr[0]}/";
			$regb=count($regarr)==2?"/{$regarr[1]}/":"";
			$regc=count($regarr)==3?"/{$regarr[2]}/":"";
			if($preg['iskami']==0 && !empty($regb)){
				$card=explode(" ",$card);
				if(preg_match($rega,$card[0]) && preg_match($regb,$card[1])){
				     $card[0]=$this->encard($card[0]);
				     $card[1]=$this->encard($card[1]);
					 $res=Order::where(['card_no'=>$card[0]])->order('id desc')->find();
				    if($res){
				        $state=$res->getData('state');
				        if($state==0 || $state==1 || $state==4){
				            $msg='卡正在处理';
				        }elseif($state==2 || $state==8){
				            $msg='卡已成功';
				        }elseif($state==3){
				            if($res->getData('card_no')==$card[0] && $isrest!=1){
				                $msg='卡有处理记录';
				            }elseif($res['restok']==2){
				                $msg='卡已有成功失败记录';
				            }else{
				                 $msg=[$card[0],$card[1],isset($card[2])?$card[2]:""];
				            }
				        }else{
				            $msg=[$card[0],$card[1],isset($card[2])?$card[2]:""];
				        }
                    }else{
                        $msg=[$card[0],$card[1],isset($card[2])?$card[2]:""];
                    }
				}else{
					$msg='卡不符合规则';
				}
				if(!empty($regc) && !preg_match($rega,$card[2])){
					$msg='卡不符合规则';
				}
			}else{
				if(preg_match($rega,$card)){
					$msg=[1,$card];
				}else{
					$msg='卡不符合规则';
				}
			}
		    return $msg;
		}else{
			return explode(" ",$card);
		}
	}

    public function encard($cardno){
        $card=new AD($this->getKas());
        $value=$card->encrypt($cardno);
        if(!$value)$value=$cardno;
        return $value;
    }

    public function getCardno($cardno){
        $card=new AD($this->getKas());
        $value=$card->decrypt($cardno);
        if(!$value)$value=$cardno;
        return $value;
    }

    public function cardType(){
        $list=CardModel::field("id,title,route")->with('comments')->where(['status'=>1,'istype'=>0])->select()->toArray();
        $map=[];
        $feiall=feilvall();
        cache("cardall",$feiall);
        foreach($list as $k=>$v){
            foreach($v['comments'] as $kk=>$vv){
                $data=[];
                if(!isset($feiall[$vv['id']]))$feiall[$vv['id']]=[];
                foreach($feiall[$vv['id']] as $ka=>$kv){
                    if($kv['status']!=1)continue;
                    $data[$ka]['price']=$kv['mianzhi'];
                    $data[$ka]['flv']=$kv['feilv'];
                }
                $last_names = array_column($data,'price');
                array_multisort($last_names,SORT_DESC,$data);
                $vv['list']=$data;
                $vv['canal']=sendType($vv['batch'],$vv['single'],$vv['iscode']);//3qq 4|5上传图片 4可以连卡密上传  5只能图片
                $rt=showFeilv($data,$vv['mode']);
                $vv['discount']=$rt?sprintf("%.2f",$rt):"";
                $vv['mark']=$vv['iscode']==1?1:0;//0实体卡1二维码
                $vv['nocode']=$vv['iskami'];//1无需卡号只填卡密  id=2  可以加急处理
                $vv['repair']=$this->repair($vv['status'],$vv['isqiye']);//维护管理
                $vv['shide']="";
                $vv['warning']=1;//1提醒确认面值
                $v[$vv['id']]=$vv;
            }
            $map[$v['id']]=$v;
            unset($map[$v['id']]['comments']);
        }
        $this->result($map);
    }

    public function repair($status=0,$qiye){
        $as=$status==0?1:0;
        if($as==0 && session("?user_auth") && $qiye==1){
            if(!isset($this->user['userReal']['retype']) || $this->user['userReal']['retype']==1){
                $as=2;
            }
        }
        return $as;
    }

    public function gapiData(){
        $res=CardModel::with(['comments','comments.models'])->field("id")->where(['status'=>1,'istype'=>0])->select();
        $map=[];
        $cardall=cache('cardall');
        foreach($res as $k=>$v){
            $list=$v['comments'];
            foreach($list as $kk=>$vv){
                $arr=[];
                foreach($vv['models'] as $item=>$val){
                    if(!isset($cardall[$vv['id']]))continue;
                    $statusArr=array_column($cardall[$vv['id']],"status",'mianzhi');
                    if(!isset($statusArr[$val['mianzhi']]))continue;
                    if($statusArr[$val['mianzhi']]!=1)continue;
                    $dfg=array_column($cardall[$vv['id']],'feilv','mianzhi');
                    $arr[$item]['price'] = $val['mianzhi'];
                    $arr[$item]['flv'] = $dfg[$val['mianzhi']];
                }
                $last_names = array_column($arr,'price');
                array_multisort($last_names,SORT_DESC,$arr);
                $list[$kk]['list']=$arr;
                unset($list[$kk]['models']);
            }
            $map[$v['id']]=$list;
        }
        $this->result($map);
    }

}
