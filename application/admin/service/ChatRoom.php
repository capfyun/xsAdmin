<?php
/**
 * 服务层-聊天室
 * @author xs
 */
namespace app\admin\service;

class ChatRoom extends \app\common\service\Base{
	
	//用户
	private $user = [];
	/* 事件 */
	const EVENT_ONLINE       = 1; //上线
	const EVENT_CHAT         = 2; //聊天
	const EVENT_OFFLINE      = 3; //下线
	const EVENT_REFRESH_USER = 4; //刷新
	
	/**
	 * 响应请求
	 * @param array $param
	 */
	public function response($param){
		//msg type  1 初始化  2 通知  3 一般聊天  4 断开链接  5 获取在线用户 6 通知下线
		$data = json_decode($param, true);
		/* 消息类型 */
		switch($data['type']){
			//上线
			case self::EVENT_ONLINE:
				//通知其他客户端,当前用户上线
				$msg = [
					'type'        => self::EVENT_ONLINE,
					'username'    => $data['username'],
					'create_time' => date('Y-m-d H:i:s'),
				];
				break;
			//聊天
			case self::EVENT_CHAT:
				$msg = [
					'type'        => self::EVENT_CHAT,
					'username'    => $data['username'],
					'content'     => $data['content'],
					'create_time' => date('Y-m-d H:i:s'),
				];
				break;
			//离线
			case self::EVENT_OFFLINE:
				//通知用户离线
				$msg = [
					'type'     => self::EVENT_OFFLINE,
					'username' => $this->user[(int)$param['socket']]['username'],
				];
				break;
			default:
				$msg = '';
				break;
		}
		
		return json_encode($msg);
	}
}