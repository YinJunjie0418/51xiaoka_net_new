<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\CardList;
use app\common\model\CardModel;
use app\common\model\CardOperator;
use app\common\model\SellList;
use app\common\model\SellListRate;
use think\facade\View;

class SellCard extends AdminBase{
    public function index($limit=15){
        $cid=input('cid')??0;
        if($this->request->isAjax()){
            if($cid>0){$map=['cid'=>$cid];}
            else{$map=[['id','>',0]];}
            $list=SellList::where($map)->order("id desc")->paginate($limit);
            foreach($list as $item=>$val){
                $list[$item]['name']=$val['title']."【".$val['mianzhi']."】";
                $str='';
                $tname=CardOperator::where(['id'=>$val['operid']])->value('name');
                $list[$item]['operid']=$tname?$tname:'未配置';
                $cardname=SellListRate::where(['listid'=>$val['id']])->select();
                foreach($cardname as $kk=>$vv){
                    $str.="本站关联：".$vv['title'];
                   /* if($vv['type']==0){
                        $str.="本站关联：".$vv['title']."  分配比例：".$vv['rate']."%</br>";
                    }else{
                        $str.="充值通道：".$vv['title']."  分配比例：".$vv['rate']."%</br>";
                    }*/

                }
                if(empty($str)){
                    $str="未配置充值通道";
                }
                $list[$item]['typeas']=$str;
            }
            $this->result($list);
        }
        $res = CardModel::where(['istype'=>0])->order('sort_order desc,id desc')->select();

        View::assign("res",$res);

        View::assign("id",$cid);
        return view();
    }

   public function savesell(){
        $da=input();
        if($this->request->isAjax()){
            if($da['id']){
                if(SellList::where([['geway','=',$da['geway']],['id','<>',$da['id']]])->find()){
					$this->error("通道标识代码已经存在");
				}
                $res=SellList::find($da['id']);
                $res->allowField(['cid', 'geway','title','iconurl','batch','mianzhi','rate'])->update($da);
            }else{
				if(SellList::where([['geway','=',$da['geway']]])->find()){
					$this->error("通道标识代码已经存在");
				}
				if(SellList::where([['title','=',$da['title']],['mianzhi','=',$da['mianzhi']]])->find()){
					$this->error("该产品面值已经存在");
				}
                $saveres=new SellList();
                $saveres->allowField(['cid', 'geway','title','iconurl','batch','mianzhi','rate','type','operid'])->save($da);
                $da['id']=$saveres->id;
            }
            $map['bindid']=$da['bid'];
            $map['title']=CardList::find($da['bid'])['title'];
            $map['rate']=0;
            if($bres=SellListRate::where(['listid'=>$da['id'],'type'=>0])->find()){
                if($da['bid']==0){
                    SellListRate::destroy($bres['id'],true);
                }else{
                    SellListRate::update($map,['id'=>$bres['id']]);
                }
            }else{
                $map['listid']=$da['id'];
                SellListRate::create($map);
            }
            $this->success("操作成功",url('/SellCard/index'));
        }
        $da['id']=isset($da['id'])?$da['id']:'';
        $ratelist=SellListRate::where(['listid'=>$da['id']])->select()->toArray();
        $bid=[];
        $ids=[];
        foreach ($ratelist as $k=>$v){
            if($v['type']==0){
                $bid=$v;
            }else{
                $ids[$k]=$v;
            }
        }
        View::assign("ratelist",$ratelist);
        View::assign('fenlei',CardModel::select());
        View::assign("data",SellList::find($da['id']));
        View::assign('operlist',CardOperator::where('type','in',[2,5])->select());
        View::assign("bid",$bid);
        View::assign("bindids",$ids);
        return view();
    }


    public function editrate(){
        if($this->request->isAjax()){
            $data=input();
            if($res=SellListRate::find($data['id'])){
                $res->rate=$data['rate'];
                $ok=$res->save();
                if($ok){
                    $this->success("保存成功");
                }else{
                    $this->error("出错了");
                }
            }else{
                $this->error("参数错误");
            }
        }
    }

    public function apidel(){
        if($this->request->isAjax()){
            $data=input();
            if(SellListRate::destroy($data['id'],true)){
                    $this->success("删除成功");
                }else{
                    $this->success("出错了");
                }

        }
    }

    public function getModels(){
        if($this->request->isAjax()){
            $id=input('id');
            $res=CardList::where(['cid'=>$id])->select();
            $this->result($res);
        }
    }

    public function editapi(){
        if($this->request->isAjax()){
            $da=input();
            $res=CardOperator::find($da['cid']);
            if(!$res)$this->error("参数错误");
            $map['listid']=$da['id'];
            $map['type']=1;
            $map['bindid']=$res['id'];
            $map['title']=$res['name'];
            $map['rate']=$da['bili'];
            $ok=SellListRate::create($map);
            if($ok){
                $this->success("添加成功",url('/SellCard/index'));
            }else{
                $this->success("保存失败");
            }
        }
        $apidata=CardOperator::where(['status'=>1,'isok'=>1,'type'=>2])->select();
        View::assign("fenlei",$apidata);
        View::assign("id",input('id'));
        return view();
     }

    public function edit(){
        if($this->request->isAjax()){
            $data=input();
            if(isset($data['geway'])){
                if(SellList::where(['geway'=>$data['geway']])->find())$this->success("通道标识代码已经存在");
            }
            if($result=SellList::find($data['id'])){
                if(isset($data['way']) && isset($data['maxtopup'])){
                    cache($data['way'],$data['maxtopup']);
                }
                (new SellList)->allowField(['status', 'rate','geway','batch','maxtopup'])->update($data,['id'=>$result['id']]);
                $this->success("更新成功");
            }else{
                $this->success("参数错误");
            }
        }
    }
    public function delcard(){
        if($this->request->isAjax()){
            $id=input('id');
            SellList::destroy($id,true);
            SellListRate::where(['listid'=>$id])->delete();
            $this->success("删除成功");
        }
    }
}