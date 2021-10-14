<?php
declare (strict_types = 1);

namespace app\home\controller;


use app\common\controller\UserBase;
use app\common\library\MongData;
use app\common\model\CardModel;
use app\common\model\CardList;
use app\common\model\Order;
use think\facade\View;


class Sellcard extends UserBase
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
		$da=input();
		$cid=isset($da['cid'])?$da['cid']:CardModel::where(['status'=>1])->value('id');
		$id=0;
		if(isset($da['id'])){
			$cid=CardList::where(['id'=>$da['id']])->value('cid');
		}
		$res=CardModel::where(['id'=>$cid])->find();
		View::assign("cid",$cid);
		View::assign("tid",isset($da['id'])?$da['id']:$id);
		View::assign("ress",$res);
        return view('usercard');
    }

    public function cancel(){
        $userId = session('user_auth.user_id');
		if($this->user['money']<0){
			$this->success('预提现金额未补齐不能取消订单');
		}
        if(input('batchno')){
            $batch=input("batchno");
            $num=0;$err='';
            $newBatchno=[];
            foreach($batch as $item){
                $num++;
                $type=Order::where('batchno','=',$item)->where('state','in','0,1')->where('uid',$userId)->value('type');
                if($type!=0){
                    if(!Order::checkApi($type,'revokeData')){
                        $ok=false;
                    }else{
                        $ok=\think\facade\Queue::push("app\home\job\Jobone@getAdress", ['id'=>'cancelOrder','batchno'=>$item,'type'=>$type],'ipJobQueue');
                    }
                }else{
                    $ok=(new MongData)->stopUserData($item,$userId);
                }
                if(!$ok){
                  $err=$err."[$item]";
                }else{
                    $newBatchno[]=$item;
                }
            }
            $number = Order::where([['batchno','in',$newBatchno],['uid','=',$userId],['type','=',0],['state','=',0]])->update(['state'=>3,'chulitime'=>time(),'remarks'=>'用户取消订单']);
            if($err!=""){
                $this->success('取消失败的批次'.$err."可能本接口不支持取消");
            }else{
                $this->success('预计取消成功'.$num.'个批次');
            }

        }else if(input("ids")){
            $ids=input("ids");
            $number=0;
            $success=0;
            $newOid=[];
            $list=Order::field('orderno,id,type')->where([['id','in',$ids],['uid','=',$userId],['type','=',0],['state','in','0,1']])->select();
            foreach($list as $item){
                $number++;
                if($item['type']!=0){
                    if(Order::checkApi($item['type'], 'revokeData')){
                        $ok=\think\facade\Queue::push("app\home\job\Jobone@getAdress", ['id'=>'cancelOrder','orderno'=>$item['orderno'],'type'=>$item['type']],'ipJobQueue');
                    }else{
                        $ok=false;
                    }
                }else{
                    $ok=(new MongData)->stopUserSingle($item['orderno'],$userId);
                }
                if($ok){
                    $success++;
                    $newOid[]=$item['id'];
                }
            }
            $oknumber = Order::where([['batchno','in',$newOid],['uid','=',$userId],['type','=',0],['state','=',0]])->update(['state'=>3,'chulitime'=>time(),'remarks'=>'用户取消订单']);
            $this->success("总共取消{$number}笔，预计取消成功 {$success}");
        }
    }
	public function order(){//点卡订单
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
		$where['uid']=session('user_auth.user_id');
        $where['tmporder']='0';
		if(isset($da['rekey']) && !empty($da['rekey'])){
		    if($da['setype']=='card_no' || $da['setype']=='card_key'){
                    $da['rekey']=Order::enCardno($da['rekey']);
            }
                $where[$da['setype']]=$da['rekey'];
		}
		if(isset($da['cardype']) && !empty($da['cardype'])){
			$where['class']=$da['cardype'];
		}
		if(isset($da['status']) && !empty($da['status'])){
			$where['state']=$da['status']-1;
		}
		$list=Order::with('bsLei')->field('count(id) as ids,id,feilv,class,batchno,money as amoney,sum(money) as money,sum(amount) as amount,group_concat(state) as state,create_time,sum(state=2 or state=3) as sunum,sum(state=3) as errnum')
            ->where($where)
            ->whereTime('create_time', 'between',$map)
            ->group('batchno')
            ->order("create_time desc")
            ->paginate(15,$this->pagingState,['query' => request()->param()]);
		foreach($list as $k=>$v){
			$list[$k]['states']=batchno($v->getData('state'));
			$list[$k]['remarks']=batchRemaks($v['remarks']);
		}
		View::assign('setype',input('setype'));
		View::assign('cardype',input('cardype'));
		View::assign('status',input('status'));
		View::assign('clist',CardList::select());
		View::assign("list",$list);
		View::assign("day",isset($da['day'])?$da['day']:0);
		View::assign("starttime",$map[0]);
		View::assign("endtime",$map[1]);
		if(request()->isMobile()){
		    View::assign("empty",'<div class="messager messager-empty"><div class="messager-icon"><i class="iconfont iconfont-empty"></i></div><div class="messager-text"><h2 class="messager-title">暂无记录</h2></div></div>');
			return view('sellcard/wap/order',['title'=>'点卡订单']);
		}else{
            $em='<div class="box"><div class="nodata"><i class="iconfont icon-nodata"></i><br>抱歉，没有找到任何相关的数据！</div></div>';
            View::assign("empty",$em);
		  return view();
		}
	}
	
	public function selldetailinfo(){//订单详情
	    $id=input('id');
		$res=Order::find($id);
	    $result=Order::with('bsLei')->where(['batchno'=>$res['batchno'],'uid'=>session('user_auth.user_id')])->order('id desc')->paginate(15,$this->pagingState,['query' => request()->param()]);
		$test=Order::where(['batchno'=>$res['batchno'],'uid'=>session('user_auth.user_id')])->select();
		$da=['state'=>'','num'=>0,'ok'=>0,'money'=>0,'err'=>0];
		foreach($test as $k=>$v){
		    $da['state'].=$v->getData('state').",";
		    $da['num']++;
			if($v->getData('state')==3)$da['err']++;
			if($v->getData('state')==2)$da['ok']++;
			$da['money']+=$v['amount'];
		}
		$da['state']=batchno($da['state']);
		if($res['update_time']){
			$res['time']=$res['update_time'];
		}elseif($res['chulitime']){
			$res['time']=$res['chulitime'];
		}else{
			$res['time']=$res['create_time'];
		}
		View::assign('da',$da);
		View::assign('res',$res);
		View::assign('data',$result);
		if(request()->isMobile()){
            View::assign("empty",'<div class="messager messager-empty"><div class="messager-icon"><i class="iconfont iconfont-empty"></i></div><div class="messager-text"><h2 class="messager-title">暂无记录</h2></div></div>');

			return view('sellcard/wap/selldetailinfo',['title'=>'订单详情']);
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
            $map=[$starttime.' 23:59:59', date('Y-m-d 23:59:59')];
        }
        $where['uid']=session('user_auth.user_id');
        $where['tmporder']='0';

        $list=Order::field("FROM_UNIXTIME(create_time, '%Y-%m-%d') as datetime,count(id) as id,sum(money) as summoney,sum(state=2) as okcount,sum(if(state=2,money,0)) as okmian,sum(if(state=2,amount,0)) as okmoney,sum(state>2) as failcount,sum(if(state>2,money,0)) as failmian,sum(state<2) as loadcount,sum(if(state<2,money,0)) as loadmian")
            ->where($where)
            ->whereTime('create_time','between',$map)
            ->group('FROM_UNIXTIME(create_time,"%Y-%m-%d")')
            ->order("create_time desc")
            ->paginate(15,$this->pagingState,['query' => request()->param()]);

        View::assign("list",$list);
        View::assign("day",isset($da['day'])?$da['day']:0);
        View::assign("starttime",$map[0]);
        View::assign("endtime",$map[1]);
        if(request()->isMobile()){
            View::assign("empty",'<div class="messager messager-empty"><div class="messager-icon"><i class="iconfont iconfont-empty"></i></div><div class="messager-text"><h2 class="messager-title">暂无记录</h2></div></div>');
            return view('sellcard/wap/statistics',['title'=>'订单统计']);
        }else{
            $em='<div class="box"><div class="nodata"><i class="iconfont icon-nodata"></i><br>抱歉，没有找到任何相关的数据！</div></div>';
            View::assign("empty",$em);
            return view();
        }
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
            $map=[$starttime, date('Y-m-d 23:59:59')];
        }
		$where['uid']=session('user_auth.user_id');
		$where['tmporder']='0';
		if(isset($da['rekey']) && !empty($da['rekey'])){
			$where[$da['setype']]=$da['rekey'];
		}
		if(isset($da['cardype']) && !empty($da['cardype'])){
			$where['class']=$da['cardype'];
		}
		if(isset($da['status']) && !empty($da['status'])){
			$where['state']=$da['status']-1;
		}
        $listtotal=Order::where($where)->whereTime('create_time', 'between',$map)->count();
        $title=['ID','订单号','卡类', '卡号', '卡密','提交金额','实际面值','结算金额','状态','备注','提交时间','处理时间'];
        articleAccessLog($title,"导出卡".date('Y-m-d'),$listtotal,$this,$where,$map);
    }	
    
    public function reissued(){
            $order=input('batchno');
            $where=[['batchno','=',$order],['uid','=',session('user_auth.user_id')],['state','in','3,7']];
            $listtotal=Order::where($where)->count();
            $title=['ID','订单号','卡类', '卡号', '卡密','提交金额','实际面值','结算金额','状态','备注','提交时间','处理时间'];
            articleAccessLog($title,"导出卡".date('Y-m-d'),$listtotal,$this,$where,"");
    }

    public function getArticleAccessLog($where,$map="",$page,$limit)
    {
        if($map){
            $list=Order::with('bsLei')->where($where)->whereTime('create_time', 'between',$map)->order('id desc')->page($page,$limit)->select();
        }else{
            $list=Order::with('bsLei')->where($where)->order('id desc')->page($page,$limit)->select();
        }
        $map=[];
        foreach($list as $k=>$v){
            $map[$k]['id']=$v['id'];
            $map[$k]['batchno']=$v['batchno']."\t";
            $map[$k]['title']=$v['title'];
            $map[$k]['card_no']=$v['card_no']."\t";
            $map[$k]['card_key']=Order::getCardno($v->getData('card_key'))."\t";
            $map[$k]['money']=$v['money'];
            $map[$k]['settle_amt']=$v['settle_amt'];
            $map[$k]['amount']=$v['amount'];
            $map[$k]['state']=orderTyped($v->getData('state'));
            $map[$k]['remarks']=batchRemaks($v['remarks']);
            $map[$k]['create_time']=$v['create_time'];
            $map[$k]['update_time']=$v['update_time'];
        }
        return $map;
    }
    
}
