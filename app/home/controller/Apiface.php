<?php
declare (strict_types = 1);
namespace app\home\controller;

use app\common\model\Card;
use app\common\model\Security;
use app\common\model\User;
use app\common\controller\UserBase;
use app\common\model\UserRate;
use app\common\model\CardList;
use app\common\model\Order;
use think\facade\View;

class Apiface extends UserBase
{
	 public function apiindex(){
	     if($this->request->isPost()){
	         $id=input('id');
             $list=CardList::find($id);
             $map['title']=$list['title'];
             $map['daima']=$list['type'];
             $arr=[];
             $ulist=UserRate::where(['uid'=>session('user_auth.user_id'),'listid'=>$id])->select();
             foreach ($ulist as $item=>$val){
                 $arr[$item]['mian']= $val['mianzhi']=='0'?"自定义":$val['mianzhi'];
                 $arr[$item]['feilv']=$val['feilv'];
                 $arr[$item]['open']=$val['status'];
                 $arr[$item]['id']=$val['id'];
             }
             $map['feilv']=$arr;
             $this->result($map);
         }

		 return view('index',['data'=>CardList::select()]);
	 }
	
	public function getmiyao(){
		if ($this->request->isPost()){
			$da=input('pass');
			if($da){
				$user=User::find(session('user_auth.user_id'));
				if(md6($da,$user['tradepwd'])===true){
				   return json(['miyao'=>1,'key'=>$user['apikey'],'des'=>$user['apides']]);
				}else{
					return json(["tip"=>"#pass","content"=>"安全密码错误"]);
				}
			}else{
				return json(["tip"=>"#pass","content"=>"请输入安全密码"]);
			}
		    
		}
		return view('actmi');
	}
	public function geteditmi(){
		if ($this->request->isPost()){
			$da=input('pass');
			if($da){
				$user=User::find(session('user_auth.user_id'));
				if(md6($da,$user['tradepwd'])===true){
					$a=generate_password(15);
					$b=generate_password(8);
					$user->apikey=$a;
					$user->apides=$b;
					$user->save();
				   return json(['miyao'=>1,'key'=>$a,'des'=>$b]);
				}else{
					return json(["tip"=>"#pass","content"=>"安全密码错误"]);
				}
			}else{
				return json(["tip"=>"#pass","content"=>"请输入安全密码"]);
			}
		    
		}
		return view('actcz');
	}
	
	public function xiafa(){
		if ($this->request->isPost()){
		    $id=input('id');
			if(Order::find($id)){
				 \think\facade\Queue::push("app\home\job\Jobone@sendPost", ['id'=>$id],'sendPostJobQueue');
				 return json(['code'=>0,'msg'=>tips('加入回掉队列成功，请等待回掉','success')]);
			}else{
				return json(['code'=>-1,'msg'=>tips('参数错误')]);
			}
		}
	}
	
	public function setStatus(){
		if ($this->request->isPost()){
		   $da=input();
		   $res=UserRate::where(['uid'=>session('user_auth.user_id'),'id'=>$da['id']])->find();
		   if($res){
               if($res['status']==0 || $res['status']==1){
                   $res->status=$da['status']==1?1:0;
                   $res->save();
                   return json(['code'=>1,'msg'=>'操作成功']);
               }else{
                   return json(['code'=>-1,'msg'=>tips('接口禁用中...')]);
               }
		   }else{
			   return json(['code'=>-1,'msg'=>tips('参数错误')]);
		   }
		}
	}
	
	public function consign(){
		$da=input();
        $map=[date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')];
		if(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && !empty($da['endtime'])){
            $map=[$da['starttime'], $da['endtime']];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && empty($da['starttime']) && !empty($da['endtime'])){
            $map=["2020-1-1 00:00:00", $da['endtime']];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && empty($da['endtime'])){
            $map=[$da['starttime'], date("Y-m-d  H:i:s")];
        }elseif(isset($da['day']) && !empty($da['day'])){
            $starttime=date('Y-m-d',strtotime("-{$da['day']}day"));
            $map=[$starttime." 23:59:59", date('Y-m-d 23:59:59')];
        }
        
		$where[]=['uid','=',session('user_auth.user_id')];
        $where[]=['tmporder','<>',0];
		if(isset($da['rekey']) && !empty($da['rekey'])){
            if($da['setype']=='card_no' || $da['setype']=='card_key'){
                    $da['rekey']=Order::enCardno($da['rekey']);
            }
			$where[]=[$da['setype'],'=',$da['rekey']];
		}
		if(isset($da['cardype']) && !empty($da['cardype'])){
			$where[]=['class','=',$da['cardype']];
		}
		if(isset($da['status']) && !empty($da['status'])){
			$where[]=['state','=',$da['status']-1];
		}
        $list = Order::with('bsLei')->where($where)->whereTime('create_time', 'between', $map)->order('id desc')->paginate(15, $this->pagingState, ['query' => request()->param()]);
        foreach($list as $k=>$v){
			$list[$k]['haos']=($v->getData('state')<2)?'--':feng((int)$v->getData('chulitime')-(int)$v->getData('create_time'));
		}
		View::assign("list",$list);
		View::assign('setype',input('setype'));
		View::assign('cardype',input('cardype'));
		View::assign('status',input('status'));
		View::assign('clist',CardList::select());
		View::assign("day",isset($da['day'])?$da['day']:0);
        View::assign("starttime",$map[0]);
        View::assign("endtime",$map[1]);
		if(request()->isMobile()){
		    View::assign("empty",'<div class="messager messager-empty"><div class="messager-icon"><i class="iconfont iconfont-empty"></i></div><div class="messager-text"><h2 class="messager-title">暂无记录</h2></div></div>');
			return view('apiface/wap/consign',['title'=>'API收单']);
		}else{
            $em='<div class="box"><div class="nodata"><i class="iconfont icon-nodata"></i><br>抱歉，没有找到任何相关的数据！</div></div>';
            View::assign("empty",$em);
		    return view();
		}
	}
	public function statistics(){
		$da=input();
        $map=[date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')];
		if(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && !empty($da['endtime'])){
            $map=[$da['starttime'], $da['endtime']];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && empty($da['starttime']) && !empty($da['endtime'])){
            $map=["2020-1-1 00:00:00", $da['endtime']];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && empty($da['endtime'])){
            $map=[$da['starttime'], date("Y-m-d  H:i:s")];
        }elseif(isset($da['day']) && !empty($da['day'])){
            $starttime=date('Y-m-d',strtotime("-{$da['day']}day"));
            $map=[$starttime." 23:59:59", date('Y-m-d 23:59:59')];
        }
		$where[]=['uid','=',session('user_auth.user_id')];
		$where[]=['tmporder','<>',0];
		$list=Order::field("FROM_UNIXTIME(create_time, '%Y-%m-%d') as datetime,count(id) as id,sum(money) as summoney,sum(state=2) as okcount,sum(if(state=2,money,0)) as okmian,sum(if(state=2,amount,0)) as okmoney,sum(state>2) as failcount,sum(if(state>2,money,0)) as failmian,sum(state<2) as loadcount,sum(if(state<2,money,0)) as loadmian")
            ->where($where)
            ->whereTime('create_time','between',$map)
            ->group('FROM_UNIXTIME(create_time,"%Y-%m-%d")')
            ->paginate(15,$this->pagingState,['query' => request()->param()]);
		View::assign('cardype',input('cardype'));
		View::assign("list",$list);
		View::assign("day",isset($da['day'])?$da['day']:0);
        View::assign("starttime",$map[0]);
        View::assign("endtime",$map[1]);
		if(request()->isMobile()){
		    View::assign("empty",'<div class="messager messager-empty"><div class="messager-icon"><i class="iconfont iconfont-empty"></i></div><div class="messager-text"><h2 class="messager-title">暂无记录</h2></div></div>');
			return view('apiface/wap/statistics',['title'=>'API统计']);
		}else{
            $em='<div class="box"><div class="nodata"><i class="iconfont icon-nodata"></i><br>抱歉，没有找到任何相关的数据！</div></div>';
            View::assign("empty",$em);
            return view();
		}
	}
	
	public function selldetailinfo(){
		$data=Order::with('bsLei')->where(['id'=>input('id')])->find();
		$data['haos']=empty($data->getData('update_time'))?'--':$data->getData('update_time')-$data->getData('create_time');
		return view('apiface/wap/selldetailinfo',['title'=>'API订单详情','p'=>$data]);
	}
	 public function export()
    {
		$da=input();
        $map=[date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')];
		if(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && !empty($da['endtime'])){
            $map=[$da['starttime'], $da['endtime']];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && empty($da['starttime']) && !empty($da['endtime'])){
            $map=["2020-1-1 00:00:00", $da['endtime']];
        }elseif(isset($da['starttime']) && isset($da['endtime']) && !empty($da['starttime']) && empty($da['endtime'])){
            $map=[$da['starttime'], date("Y-m-d  H:i:s")];
        }elseif(isset($da['day']) && !empty($da['day'])){
            $starttime=date('Y-m-d',strtotime("-{$da['day']}day"));
            $map=[$starttime." 23:59:59", date('Y-m-d 23:59:59')];
        }
        $where[]=['uid','=',session('user_auth.user_id')];
        $where[]=['tmporder','<>',0];
        if(isset($da['rekey']) && !empty($da['rekey'])){
            if($da['setype']=='card_no' || $da['setype']=='card_key'){
                $da['rekey']=Order::getCardno($da['rekey']);
            }
            $where[]=[$da['setype'],'=',$da['rekey']];
        }
        if(isset($da['cardype']) && !empty($da['cardype'])){
            $where[]=['class','=',$da['cardype']];
        }
        if(isset($da['status']) && !empty($da['status'])){
            $where[]=['state','=',$da['status']-1];
        }
        $listtotal=Order::where($where)->whereTime('create_time', 'between',$map)->count();
        $title=['系统订单号','商户订单号','卡类', '卡号', '卡密','提交金额','实际面值','结算金额','状态','异步回掉地址','提交IP','自定义','备注','提交时间','处理时间'];
        articleAccessLog($title,"导出卡".date('Y-m-d'),$listtotal,$this,$where,$map);
    }

    public function getArticleAccessLog($where,$map,$page,$limit)
    {
        $list=Order::with('bsLei')->where($where)->whereTime('create_time', 'between',$map)->order('id desc')->page($page,$limit)->select();
        $map=[];
        foreach($list as $k=>$v){
            $map[$k]['orderno']=$v['orderno']."\t";
            $map[$k]['batchno']=$v['tmporder']."\t";
            $map[$k]['title']=$v['title'];
            $map[$k]['card_no']=$v['card_no']."\t";
            $map[$k]['card_key']=Order::getCardno($v->getData('card_key'))."\t";
            $map[$k]['money']=$v['money'];
            $map[$k]['settle_amt']=$v['settle_amt'];
            $map[$k]['amount']=$v['amount'];
            $map[$k]['state']=orderTyped($v->getData('state'));
            $map[$k]['notify']=$v['notify'];
            $map[$k]['ip']=$v['ip'];
            $map[$k]['custom']=$v['custom'];
            $map[$k]['remarks']=$v['remarks']?$v['remarks']:'--';
            $map[$k]['create_time']=$v['create_time'];
            $map[$k]['update_time']=$v['update_time'];
        }
        return $map;
    }
	
}
