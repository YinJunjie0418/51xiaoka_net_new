<?php
namespace app\admin\controller;
use app\common\controller\AdminBase;
use app\common\model\UserShopFeilv;
use think\facade\View;
use app\common\model\CardModel;
use app\common\model\CardList;
use app\common\model\CardOperator;
use app\common\model\CardChannel;
use app\common\model\UserRate;
use app\common\model\User;

class Card extends AdminBase
{

    //首页
    public function index()
    {
        return $this->fetch('index');
    }
    public function index_json()
    {
        $list = CardModel::order('sort_order desc,id desc')->select();
        $this->result($list);
    }
	public function cardlist($limit=15){
		$id=input('id')?:0;
		if ($this->request->isAjax()) {
			   $hot=CardModel::where(['id'=>$id])->value('istype');
			   if($hot==1){$map['is_hot']=1;}else{
				   if($id>0){$map['cid']=$id;}else{$map="1=1";}
			   }
			$list=CardList::with('models')->where($map)->order('id desc')->paginate($limit)->toArray();
			foreach($list['data'] as $k=>$v){
			    if($v['tid']==0){
                    $list['data'][$k]['name']='站内消耗';
                }else{
                    $list['data'][$k]['name']=CardOperator::find($v['tid'])['name'];
                }
				$list['data'][$k]['cid']=CardModel::where(['id'=>$v['cid']])->value('title');
				$list['data'][$k]['rate']=substrates($v['models']);
			}
			$this->result($list);
		}
		View::assign('fenlei',CardModel::select());
		View::assign('id',$id);
		return view();
	}

	public function restfeilv(){
		if ($this->request->isPost()) {
			$ulist=User::select();
			foreach($ulist as $k=>$v){
				UserRate::addRate($v['shopid']);
			}
			$this->success('更新成功', url('/card/cardlist'));
		}
	}
    
    //新增分类
    public function add() 
    {
        if($this->request->isPost()){
            $param = $this->request->param();
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'card');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            //添加
            $data = CardModel::create($param);
            if ($data == true) {
                insert_admin_log('添加了卡分类');
                $this->success('添加成功', url('/card/index'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('save');
    }
	public function edit()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'card');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            //添加
            $data = CardModel::update($param,['id'=>$param['id']]);
            if ($data == true) {
                insert_admin_log('修改了卡分类');
                $this->success('修改成功',url('/Card/index'));
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('save', [
            'data'     => CardModel::where('id', input('id'))->find(),
        ]);
    }
	//新增分类
    public function addcard() 
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'CardList');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
			$param['batch']=isset($param['batch'])?1:0;
			$param['single']=isset($param['single'])?1:0;
            //添加
            $data = CardList::create($param);
            if ($data == true) {
                insert_admin_log('添加了卡类');
                $this->success('添加成功', url('/card/cardlist'));
            } else {
                $this->error("添加失败");
            }
        }
        return $this->fetch('savecard',['fenlei'=>CardModel::select(),'oper'=>CardOperator::where(['type'=>0,'status'=>1])->select()]);
    }
	public function stedit(){
		if ($this->request->isPost()) {
			$data=input();
			$res=CardList::find($data['id']);
			if($res){
				$rt=$data['type'];
				if($res->$rt==1){
					$res->$rt=0;
				}else{
					$res->$rt=1;
				}
				$res->save();
				$this->success('设置成功', url('/card/cardlist'));
			}else{
				$this->error("参数错误");
			}
		}
	}
	public function editcard()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param(false);
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'CardList');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
			if(isset($param['content'])){
			   $param['content']=array_merge($param['content']);
			}
			$param['batch']=isset($param['batch'])?1:0;
			$param['single']=isset($param['single'])?1:0;
            $data = CardList::update($param,['id'=>$param['id']]);
            if ($data == true) {
                insert_admin_log('修改了卡类');
                $this->success('修改成功',url('/card/cardlist',['id'=>input('cid')]));
            } else {
                $this->error($this->errorMsg);
            }
        }
        return $this->fetch('savecard', [
            'data'     => CardList::where('id', input('id'))->find(),
			'fenlei'=>CardModel::select(),
			'oper'=>CardOperator::where(['type'=>0,'status'=>1])->select(),
			'cid'=>input('cid')
        ]);
    }
	public function editis()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            //验证规则
            $verify = input('_verify', true);
            if($verify!='0'){
                try{
                    $this->validate($param, 'CardList');
                }catch (\Exception $e){
                    $this->error($e->getMessage());
                }
            }
            $data = CardList::update($param,['id'=>$param['id']]);
            if ($data == true) {
                insert_admin_log('修改了卡类');
                $this->success('修改成功');
            } else {
                $this->error($this->errorMsg);
            }
        }
    }

    public function delChanel(){
        if($this->request->isPost()){
            $id=input('id');
            $ok=CardChannel::destroy($id,true);
            if($ok){
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }
    }
	public function editchcard()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
			if(isset($param['content'])){
			   CardChannel::onUpdate(['id'=>$param['id'],'content'=>$param['content'],'geng'=>isset($param['geng'])?1:0]);
			}
			$data = CardChannel::update($param,['id'=>$param['id']]);
            if ($data == true) {
                insert_admin_log('修改了卡类');
                $this->success('修改成功',url('/Card/channel'));
            } else {
                $this->error($this->errorMsg);
            }
        }
		$res=CardChannel::with('protle')->find(input('id'));
		$res['cardname']=CardList::where(['type'=>$res['tid']])->value('title');
		return view('savechcard',['data'=>$res]);
    }
	

	public function editRateCard()
    {
        $da=input();
        if ($this->request->isPost()) {
            $param = $this->request->param();
			$ok=CardChannel::onUpdate($param);
            if ($ok=== true) {
                insert_admin_log('修改了卡类');
                $this->success('修改成功',url('/card/cardlist',['id'=>input('cid')]));
            } else {
                $this->error($ok);
            }
        }
		$res=CardChannel::where(['listid'=>$da['id']])->select();
		$cardname=CardList::find($da['id'])['title'];

		return view('savechcard',['data'=>$res,'cardname'=>$cardname]);
    }
    

    public function del()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            CardModel::destroy($param['id'],true);
            insert_admin_log('删除了分类');
            $this->success('删除成功');
        }
    }
	public function delcard()
    {
        if ($this->request->isPost()) {
            $param = $this->request->param();
            CardList::destroy($param['id'],true);
            insert_admin_log('删除了分类');
            $this->success('删除成功');
        }
    }
	
	public function installapi(){
		if ($this->request->isAjax()) {
			$url=$_SERVER['DOCUMENT_ROOT'] ."/../extend/api/";
			$list=get_file_list($url);
			$map=CardOperator::select();
			foreach($map as $k=>$vv){
				$news=str_replace(".php","","\api\\".$vv['class']);
				if(!class_exists($news)){
				   $map[$k]['isok']=2;
				}
			}
			foreach($list as $v){
				$news=str_replace(".php","","\api\\".$v);
				if(class_exists($news)){
					$new=new $news;
					if(method_exists($new,'getData') && $new instanceof $news){
						$xarr=$new->getData();
						$res=CardOperator::where(['class'=>$xarr['ApiCalss']])->find();
						if(!$res){
							$map[]=['name'=>$xarr['ApiName'],'class'=>$xarr['ApiCalss'],'type'=>isset($xarr['type'])?$xarr['type']:0,'status'=>0,'url'=>$xarr['ApiUrl'],'qq'=>$xarr['ApiQq'],'isok'=>0];
						}
					}
				}
			}
			$data=[];
			foreach($map as $k=>$v){
			    $data[$k]=$v;
			    $data[$k]['istype']=$v['type'];
                $data[$k]['type']=apitype($v['type']);
			}
			$this->result($data);
		}
		return view();
	}
	
	public function apiedit(){
		if ($this->request->isAjax()) {
			$param = $this->request->param();
			$res=CardOperator::find($param['id']);
			if ($res){
				$data = (new CardOperator)->allowField(['status', 'isload'])->update($param,['id'=>$param['id']]);
				if ($data == true) {
					insert_admin_log('修改了接口');
					$this->success('修改成功',url('/Card/installapi'));
				}else {
					$this->error("修改失败");
				}
			}else{
				$this->error("请先安装接口");
			}
		}
	}
	public function configEdit(){
		$id=input("id");
		$data=CardOperator::find($id);
        $className="\api\\".$data['class'];
        if(class_exists($className)) {
            $superNew = new $className;
            if (method_exists($superNew, 'getData')) {
                $configuration = $superNew->getData();
            }else {
                $this->error("接口缺少类");
            }
        }else {
            $this->error("接口错误");
        }
		if(!$data)$this->error("数据错误");
		if($this->request->isAjax()) {
			$param = $this->request->param();
			try{
				$this->validate($param, 'CardOperator.edit');
			}catch (\Exception $e){
				$this->error($e->getMessage());
			}
            $arr = [];
            foreach ($configuration['ApiParameter'] as $k => $v) {
                $arr[$v['field']] = isset($param[$v['field']]) ? $param[$v['field']] : "";
            }
            $param['content'] = $arr;
            $da = $data->allowField(['name', 'class', 'content', 'qq', 'url'])->save($param);
            if ($da == true) {
                insert_admin_log('修改了接口');
                $this->success('修改成功', url('/Card/installapi'));
            }else{
                $this->error("修改失败");
            }
		}
		return view('addapi',['data'=>$data,'fields'=>$configuration['ApiParameter']]);
	}


	
	public function addapi(){
		if ($this->request->isAjax()){
			$da=input();
			$className="\api\\".$da['id'];
			if(class_exists($className)){
				$superNew=new $className;
				if(method_exists($superNew,'getData')){
					$configuration=$superNew->getData();
					try{
                        $this->validate($configuration, 'CardOperator.add');
					}catch (\Exception $e){
						$this->error($e->getMessage());
					}
					$map=['name'=>$configuration['ApiName'],'type'=>isset($configuration['type'])?$configuration['type']:0,'class'=>$configuration['ApiCalss'],'status'=>0,'url'=>$configuration['ApiUrl'],'qq'=>$configuration['ApiQq'],'isok'=>1,'fields'=>$configuration['ApiParameter']];
					$ok=CardOperator::create($map);
					if($ok){
						insert_admin_log('安装接口');
					    $this->success('安装成功');
					}else{
						$this->error("安装失败");
					}
				}else{
					$this->error("配置信息错误：[getData]方法未定义");
				}
			}else{
				$this->error("未能实例化接口,请检查接口文件是否存在");
			}
		}
		return view('upapi');
	}
	public function apidel(){
		if ($this->request->isPost()) {
            $param = $this->request->param();
            CardOperator::destroy($param['id'],true);
            insert_admin_log('删除了接口');
            $this->success('删除成功');
        }
	}
    
    
}
