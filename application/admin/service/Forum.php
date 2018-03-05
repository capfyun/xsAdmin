<?php
/**
 * 服务层-论坛
 * @author 夏爽
 */
namespace app\admin\service;

class Forum extends \app\common\service\Base{
	
	/**
	 * 楼内回复列表
	 */
	public function raplyList($thread_id = 0, $page = 1, $limit = 4){
		//数据对象初始化
		$paginator = db('forum_thread_reply')->alias('m')
			->join(config('database.prefix').'user u', 'm.user_id=u.id', 'LEFT')
			->where(['m.thread_id' => $thread_id, 'm.status' => 1])
			->field('m.*,u.face,u.realname')
			->order('m.id ASC')
			->paginate(['list_rows' => $limit, 'page' => $page]);
		
		$data = $paginator->toArray();
		foreach($data['data'] as $k => $v){
			$data['data'][$k]['face_url'] = service('Transmit')->file($v['face']);
		}
		//保存分页信息
		$this->page = [
			'page'  => $data['current_page'],
			'total' => $data['total'],
			'limit' => $data['per_page'],
		];
		//模板赋值
		return $data['data'];
	}
	
	/**
	 * 观看次数增加
	 * @param int $forum_id 主题ID
	 * @param int $step 增加数
	 * @return int|string
	 */
	public function viewInc($forum_id = 0, $step = 1){
		return db('forum')
			->where(['id' => $forum_id])
			->inc('view_count', $step)
			->update();
	}
	
	/**
	 * 回复后更新主题
	 * @param array $param ['forum_id' => 主题ID, 'user_id' => 用户ID]
	 * @return false|int
	 */
	public function forumReplyUpdate($forum_id = 0, $user_id = 0){
		return model('Forum')
			->save([
				'last_user_id' => $user_id,
				'last_time'    => time(),
				'reply_count'  => ['exp', '+1'],
			], ['id' => $forum_id]);
	}
	
	/**
	 * 主题校验
	 * @param int $forum_id 主题ID
	 */
	public function checkForum($forum_id = 0){
		$info = db('forum')->where(['id' => $forum_id])->field('id,is_lock')->find();
		if(!$info){
			$this->error = '该主题不存在！';
			return false;
		}
		if($info['is_lock']==1){
			$this->error = '该主题已锁定！';
			return false;
		}
		return true;
	}
	
}
