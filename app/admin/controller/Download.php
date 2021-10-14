<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\facade\View;
use think\facade\Request;
use app\common\model\Config;

class Download extends AdminBase
{
	public function getjson(){
		$name=input('name');
		$file = root_path().'updata/'.$name.'.json';
		if(file_exists($file))
		{				
			$data = file_get_contents($file);	
			$data=json_decode($data,true);
			$data['code']=1;
			return json($data);
		}
		else{
			return json(['code'=>-1]);
		}
	}
	
	
	 /**
	 * 下载文件（非接口）
	 * $url 远程URL
	 * $folder 本地存放路径
	 */
	function download() {
		$da=input();
		$url='https://'.$da['turl'];
		$folder='./../updata/';
		$callbackPath="./../updata/";
	    set_time_limit (24 * 3600 * 3600); // 设置超时时间   
	    $destination_folder = $folder ; // 文件下载保存目录，默认为当前文件目录   
	  	stream_context_set_default( [
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
			],
		]);
		$ok=Config::where(['value'=>$da['ver']])->find();
		if($ok){
		    return json(['code'=>-1,'msg'=>'该版本已经升级，请不要重复升级']);
			exit;
		}
		if(empty($url) || empty($da['name'])){
			return json(['code'=>-1,'msg'=>'获取下载参数失败']);
			exit;
		}
		try{
		   $total = get_headers($url,true)['Content-Length'];
		}catch (\Exception $e){
		    return json(['code'=>-1,'msg'=>'获取文件失败，文件路径错误']);
			exit;
		}
		if(empty($total)){
		     return json(['code'=>-1,'msg'=>'文件不存在']);
			exit;
		}
	  	$fileName = str_replace('._php','.php',basename($url));
		$newfname = $destination_folder . $fileName; // 取得文件的名称
		$stime = microtime(true); #获取程序开始执行的时间   
	    $file = fopen($url, "rb"); // 远程下载文件，二进制模式   
		    if ($file) { // 如果下载成功   
		        $newf = fopen ($newfname, "wb"); // 远在文件文件   
		        if ($newf) // 如果文件保存成功   
		            while (!feof($file)) { // 判断附件写入是否完整   
		                $length = fwrite($newf, fread($file, 1024 * 8), 1024 * 8); // 没有写完就继续 
		                if($callbackPath) //有回调，则写入回调文件
		                {		                
		                	if(empty($currSize)) $currSize =0;
			                if(empty($newSleep)) $newSleep =0;		                
			                $currSize += $length;		  //当前文件大小 
			                $etime=microtime(true); #获取程序执行结束的时间
							$totalTime=$etime-$stime;   #计算差值		                          
			                $rate = intval(($currSize/ $total) * 100);
			                $speed = $currSize/$totalTime;			                			                
			                if($rate > $newSleep)
			                {			                		                	
			                	$json = '{"name": "文件下载","total": '.$total.',"currSize": '.$currSize.',"rate": '.$rate.', "speed": '.$speed.' }';	
			              		file_put_contents($callbackPath.$da['name'].'.json',$json);
			              		$newSleep= $rate;  
			                }	               
		                }	          
		        	}    
		    }   
		    if ($file) {   
		        fclose($file); // 关闭远程文件   
		    }    
		    if ($newf) {   
		        fclose($newf); // 关闭本地文件   
		    }   
	}
	
	
	public function gengxin(){
	    $da=input();
    	$wenj=root_path().'updata/'.$da['name'].".zip";
		$mulu=root_path().$da['mulu'];
		if(file_exists($wenj) && file_exists($mulu)){
		    $ok=$this->UnZipFile($wenj,$mulu,root_path().'updata/'.$da['name'].".json");
			if($ok){
				Config::create(['name'=>$da['title'],'value'=>$da['ver']]);
				return json(['code'=>1,'msg'=>'更新文件成功']); 
			}else{
				return json(['code'=>-1,'msg'=>'解压文件失败']); 
			}
		}else{
			return json(['code'=>-1,'msg'=>'更新文件不存在']); 
		}
	}
	/**
	 * 解压文件(非接口)
	 * $file zip文件路径
	 * $localFile 本地存放路径
	 */	
	private function UnZipFile($filename, $path,$json){ 
		$filename = iconv("utf-8","gb2312",$filename);
		$path = iconv("utf-8","gb2312",$path);
		$resource = zip_open($filename);
		while ($dir_resource = zip_read($resource)) {
		//如果能打开则继续
			if (zip_entry_open($resource,$dir_resource)) {
			  //获取当前项目的名称,即压缩包里面当前对应的文件名
			  $file_name = $path.zip_entry_name($dir_resource);
			  //以最后一个“/”分割,再用字符串截取出路径部分
			  $file_path = substr($file_name,0,strrpos($file_name, "/"));
			  //如果路径不存在，则创建一个目录，true表示可以创建多级目录
			  if(!is_dir($file_path)){
				mkdir($file_path,0777,true);
			  }
			  //如果不是目录，则写入文件
			  if(!is_dir($file_name)){
				//读取这个文件
				$file_size = zip_entry_filesize($dir_resource);
				//最大读取6M，如果文件过大，跳过解压，继续下一个
				if($file_size<(1024*1024*30)){
				  $file_content = zip_entry_read($dir_resource,$file_size);
				  file_put_contents($file_name,$file_content);
				}
			  }
			  //关闭当前
			  zip_entry_close($dir_resource);
			}
		  }
		  zip_close($resource);
		  unlink($filename);
		  unlink($json);
		  return true;
	}

}
