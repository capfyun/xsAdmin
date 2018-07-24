<?php
/**
 * 传输
 * @author xs
 */
namespace app\admin\controller;

class Transmit extends \app\common\controller\AdminBase{
	
	/**
	 * 上传，支持多文件多类型
	 */
	public function upload(){
		//进行上传
		$data = model('File')->upload();
		!$data && $this->apiReturn(['code' => 1000, 'msg' => model('File')->getError()]);
		//上传成功
		$this->apiReturn(['code' => 0, 'msg' => '上传成功！', 'data' => $data]);
	}
	
	/**
	 * 下载
	 */
	public function download(){
		$result = model('File')->download(input('name'));
		$result || abort(404, model('File')->getError());
	}
	
	/**
	 * 百度编辑器
	 */
	public function ueditor(){
		$config = [
			//上传图片配置项
			'imageActionName'         => 'uploadimage', //执行上传图片的action名称
			'imageFieldName'          => 'upfile', //提交的图片表单名称
			'imageCompressEnable'     => true, //是否压缩图片,默认是true
			'imageCompressBorder'     => 1600, //图片压缩最长边限制
			'imageUrlPrefix'          => '', //图片访问路径前缀
			'imageInsertAlign'        => 'none', //插入的图片浮动方式
			
			//涂鸦图片上传配置项
			'scrawlActionName'        => 'uploadscrawl', //执行上传涂鸦的action名称
			'scrawlFieldName'         => 'upfile', //提交的图片表单名称
			'scrawlUrlPrefix'         => '',
			'scrawlInsertAlign'       => 'none',
			
			//截图工具上传
			'snapscreenActionName'    => 'uploadimage', //执行上传截图的action名称
			'snapscreenUrlPrefix'     => '', //图片访问路径前缀
			'snapscreenInsertAlign'   => 'none', //插入的图片浮动方式
			
			//上传视频配置
			'videoActionName'         => 'uploadvideo', //执行上传视频的action名称
			'videoFieldName'          => 'upfile', //提交的视频表单名称
			'videoUrlPrefix'          => '', //视频访问路径前缀
			
			//上传文件配置
			'fileActionName'          => 'uploadfile', //controller里,执行上传视频的action名称
			'fileFieldName'           => 'upfile', //提交的文件表单名称
			'fileUrlPrefix'           => '', //文件访问路径前缀
			
			//抓取远程图片配置
			'catcherLocalDomain'      => ['img.baidu.com'],
			'catcherActionName'       => 'catchimage', //执行抓取远程图片的action名称
			'catcherFieldName'        => 'source', //提交的图片列表表单名称
			'catcherUrlPrefix'        => '', //图片访问路径前缀
			
			//列出指定目录下的图片
			'imageManagerActionName'  => 'listimage', //执行图片管理的action名称
			'imageManagerUrlPrefix'   => '', //图片访问路径前缀
			'imageManagerInsertAlign' => 'none', //插入的图片浮动方式
			
			//列出指定目录下的文件
			'fileManagerActionName'   => 'listfile', //执行文件管理的action名称
			'fileManagerUrlPrefix'    => '', //文件访问路径前缀
		];
		
		switch(input('action')){
			case 'config':
				$result = $config;
				break;
			//上传
			case 'uploadimage': //上传图片
			case 'uploadvideo': //上传视频
			case 'uploadfile': //上传文件
				//进行上传
				$upload = model('File')->upload();
				if($upload){
					$file = array_shift($upload);
					
					$result = [
						'state'    => 'SUCCESS', //上传状态，上传成功时必须返回'SUCCESS'
						'url'      => $file['url'], //返回的地址
						'title'    => $file['save_name'], //新文件名
						'original' => $file['name'], //原始文件名
						'type'     => $file['ext'], //文件类型，后缀
						'size'     => $file['size'], //文件大小
					];
				}else{
					$result = ['state' => '上传失败！'];
				}
				break;
			//上传涂鸦
			case 'uploadscrawl':
				$result = ['state' => '暂不支持涂鸦上传！'];
				break;
			
			//列出图片
			case 'listimage': //列出图片
			case 'listfile': //列出文件
				input('size');
				input('start');
				$result = [
					'state' => 'no match file', 'list' => [], 'start' => 0, 'total' => 0,
				];
				$result = [
					'state' => 'SUCCESS',
					'list'  => [
						['url' => '/resource/file/2018-05-23/5b05464177e1a.jpeg', 'mtime' => 'image/jpeg'],
					],
					'start' => 0,
					'total' => 10,
				];
				break;
			
			//抓取远程文件
			case 'catchimage':
				$images = input($config['catcherFieldName'].'/a');
				$upload = model('File')->upload($images,['type'=>'remote']);
				if($upload){
					$result = [
						'state' => 'SUCCESS',
						'list'  => [],
					];
					foreach($upload as $k => $v){
						$result['list'][] = [
							'state'    => 'SUCCESS',
							'url'      => $v['url'],
							'size'     => $v['size'],
							'title'    => $v['save_name'],
							'original' => $v['save_name'],
							'source'   => $v['name'],
						];
					}
				}else{
					$result = ['state' => '上传失败！'];
				}
				break;
			
			default:
				$result = ['state' => '处理失败',];
				break;
		}
		
		//返回
		if(input('callback')){
			if(preg_match('/^[\w_]+$/', input('callback'))){
				return htmlspecialchars(input('callback')).'('.$result.')';
			}else{
				return json(['state' => 'callback参数不合法']);
			}
		}else{
			return json($result);
		}
	}
	
}
