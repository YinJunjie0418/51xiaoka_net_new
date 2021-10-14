<?php
namespace app\common\model;

use think\facade\Log;
use think\Model;


class CashOrderErr extends Model
{

    protected $autoWriteTimestamp = true;
    protected $dateFormat = 'Y/m/d H:i:s';


    public function addOrder($data)
    {
       try{
		    if(!isset($data['itemId']))return true;
            $res = SellList::with('prolie')->where(['geway' => $data['itemId']])->find();
            $class = CardList::where(['id' => $res['bindid']])->value('type');
            $map['uid'] = $data['customerId'];
            $map['tmporder'] = $data['orderno'];
            $map['price']=isset($data['decimal'])?$data['decimal']:0;
            $map['type'] = $data['itemId'];
            $map['class'] = $class;
            $map['money'] = $data['checkItemFacePrice'];
            $map['number'] = $data['number'];
            $map['state'] = isset($data['state']) ? $data['state'] : -1;
            $map['remarks'] = isset($data['remarks']) ? $data['remarks'] : "";
            $map['ip'] = isset($data['ip'])?$data['ip']:request()->ip();
            $map['notify_url'] = $data['notify_url'];
            $map['overtime'] = $data['overtime'] ? time() + $data['overtime'] : 0;
            $map['ext1'] = isset($data['ext1']) ? $data['ext1'] : '0';
            $map['ext2'] = isset($data['ext2']) ? $data['ext2'] : '0';
            $map['ext3'] = isset($data['ext3']) ? $data['ext3'] : '0';
            return $this->create($map);
        }catch (\Exception $e){
           echo $e->getMessage();
           return false;
        }
    }

}
