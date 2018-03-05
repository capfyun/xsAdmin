<?php
/**
 * 控制器-论坛
 * @author 夏爽
 */
namespace app\admin\controller;

class Forum extends \app\common\controller\BaseAdmin{
	
	/**
	 * 主题列表
	 * 参数 $pageNum=页码数 $numPerPage=每页数据条数 $search=搜索
	 */
	public function forum_list($pageNum = 1, $numPerPage = null, $search = []){
		/* 获取数据 */
		$db_bulletin = db('forum')->alias('m')
			->join(config('database.prefix').'forum_category c', 'm.category_id=c.id', 'LEFT');
		if(!empty($search['keyword'])){
			$db_bulletin->where(function($query) use ($search){
				$query->whereOr([
					'm.title' => ['like', '%'.$search['keyword'].'%'],
				]);
			});
		}
		$where       = [];
		$db_bulletin = $db_bulletin->field('m.*,c.name AS category_name')->where($where)->order('m.top DESC,m.id DESC');
		$list        = $this->dwzPaging($db_bulletin, $pageNum, $numPerPage);
		foreach($list as $k => $v){
			$list[$k]['realname']      = db('user')->where(['id' => $v['user_id']])->value('realname');
			$list[$k]['last_realname'] = db('user')->where(['id' => $v['last_user_id']])->value('realname');
		}
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list' => $list,
		]]);
	}
	
	/**
	 * 主题新增
	 */
	public function forum_add(){
		if($this->request->isPost()){
			/* 事务写入 */
			db()->startTrans();
			//写入主题表
			$m_forum = model('Forum');
			$result  = $m_forum->save([
				'user_id'      => $this->user_id,
				'title'        => $this->request->param('title'),
				'category_id'  => $this->request->param('category_id'),
				'last_user_id' => $this->user_id,
				'last_time'    => time(),
			]);
			if(!$result){
				db()->rollback();
				return $this->dwzReturn(300);
			}
			//写入主题内容表
			$forum_id = $m_forum->id;
			$result   = model('ForumThread')->save([
				'user_id'  => $this->user_id,
				'forum_id' => $forum_id,
				'content'  => $this->request->param('content'),
				'number'   => 1,
			]);
			if(!$result){
				db()->rollback();
				return $this->dwzReturn(300);
			}
			/* 成功 */
			db()->commit();
			return $this->dwzReturn(200, '', ['url' => 'forum_list']);
		}
		//分类
		$category_list = db('forum_category')->where(['status' => 1])->column('name', 'id');
		/* 视图 */
		return $this->fetch('', ['data' => [
			'category_list' => $category_list,
		]]);
	}
	
	/**
	 * 主题编辑
	 */
	public function forum_edit($id = 0){
		if($this->request->isPost()){
			$info = db('forum')->where(['id' => $id])->find();
			if($info['user_id']!=$this->user_id) return $this->dwzReturn(300, '无法编辑别人的主题！');
			/* 更新 */
			//写入主题表
			$result = model('Forum')->save([
				'title'       => $this->request->param('title'),
				'category_id' => $this->request->param('category_id'),
			], ['id' => $id]);
			if(!$result) return $this->dwzReturn(300);
			//写入主题内容表
			$result = model('ForumThread')->save([
				'content' => $this->request->param('content'),
			], ['forum_id' => $id, 'number' => 1]);
			if(!$result) return $this->dwzReturn(300);
			/* 成功 */
			return $this->dwzReturn(200, '', ['url' => 'forum_list']);
		}
		//详情
		$info = db('forum')->alias('m')
			->join(config('database.prefix').'forum_thread t', 'm.id=t.forum_id', 'LEFT')
			->where(['m.id' => $id, 't.number' => 1])
			->field('m.*,t.content')
			->find();
		if($info['user_id']!=$this->user_id) return $this->inform(100, '无法编辑别人的主题！');
		//分类
		$category_list = db('forum_category')->where(['status' => 1])->column('name', 'id');
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info'          => $info,
			'category_list' => $category_list,
		]]);
	}
	
	/**
	 * 主题管理
	 */
	public function forum_manage($id = 0){
		if($this->request->isPost()){
			$result = model('Forum')->allowField(true)->save($this->request->post(), ['id' => $id]);
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200, '', ['url' => 'forum_list']);
		}
		//详情
		$info = db('forum')->where(['id' => $id])->find();
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info' => $info,
		]]);
	}
	
	/**
	 * 主题详情
	 * @param int $forum_id 主题ID
	 * @param int $reply_id 楼内回复ID
	 * @param int $page 楼内回复分页
	 */
	public function forum_thread($pageNum = 1, $numPerPage = null, $search = [], $forum_id = 0, $thread_id = 0, $page = 1){
		if($this->request->isPost() && $this->request->post('type')=='reply_list'){
			/* 获取回复分页数据 */
			$s_forum = service('Forum');
			$list    = $s_forum->raplyList($thread_id, $page);
			foreach($list as $k => $v){
				$list[$k]['create_time_format'] = date('Y-m-d H:i', $v['create_time']);
				$list[$k]['reply_add_url']      = url('forum/reply_add', ['thread_id' => $thread_id, 'reply_id' => $v['id'],]);
			}
			$data = [
				'list' => $list,
				'page' => $s_forum->page,
			];
			return $this->ajaxReturn(['code' => 0, 'msg' => '', 'data' => $data]);
		}
		/* 获取数据 */
		//主题详情
		$info_forum = db('forum')->alias('m')
			->join(config('database.prefix').'forum_category c', 'm.category_id=c.id', 'LEFT')
			->where(['m.id' => $forum_id])
			->field('m.*,c.name AS category_name')->find();
		if(!$info_forum) exit($this->inform(400, '主题不存在'));
		
		//帖子列表
		$db_thread = db('forum_thread')->alias('m')
			->join(config('database.prefix').'user u', 'm.user_id=u.id', 'LEFT');
		if(!empty($search['keyword'])){
			$db_thread->where(function($query) use ($search){
				$query->whereOr([
					'm.content' => ['like', '%'.$search['keyword'].'%'],
				]);
			});
		}
		$where     = ['m.forum_id' => $forum_id, 'm.status' => 1];
		$db_thread = $db_thread->field('m.*,u.realname,u.face')->where($where)->order('m.number ASC,m.id ASC');
		$list      = $this->dwzPaging($db_thread, $pageNum, $numPerPage);
		$s_forum   = service('Forum');
		foreach($list as $k => $v){
			$list[$k]['face_url'] = service('Transmit')->file($v['face']);
			//楼内回复
			$list[$k]['reply_list'] = $s_forum->raplyList($v['id'], 1);
			$list[$k]['page']       = $s_forum->page;
		}
		/* 观看次数+1 */
		service('Thread')->push('Forum/viewInc', $forum_id);
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list'   => $list,
			'info'   => $info_forum,
			'extend' => ['forum_id' => $forum_id],
		]]);
	}
	
	/**
	 * 主题回复
	 * @param int $forum_id 主题ID
	 */
	public function thread_add($forum_id = 0){
		if($this->request->isPost()){
			/* 校验 */
			//校验内容
			$content = $this->request->param('content');
			if(mb_strlen(trim(strip_tags($content)))<=6) return $this->dwzReturn(300, '请至少输入6个字符！');
			//校验主题
			$s_forum = service('Forum');
			if(!$s_forum->checkForum($forum_id)) return $this->dwzReturn(100, $s_forum->getError());
			//程序锁
			$result = service('ExecLock')->setTime(2)->open();
			if(!$result) return $this->dwzReturn(300, '系统繁忙，请稍后再试！');
			/* 写入 */
			$number = db('forum_thread')->where(['forum_id' => $forum_id])->max('number');
			$result = model('ForumThread')->allowField(true)->save([
				'forum_id' => $forum_id,
				'user_id'  => $this->user_id,
				'content'  => $content,
				'number'   => $number+1,
			]);
			if(!$result){
				service('ExecLock')->close();
				return $this->dwzReturn(300);
			}
			/* 成功 */
			service('ExecLock')->close();
			//更新
			service('Thread')->push('Forum/forumReplyUpdate', $forum_id, $this->user_id);
			return $this->dwzReturn(200, '', ['navTabId' => 'forum-'.$forum_id]);
		}
		//校验主题
		$s_forum = service('Forum');
		if(!$s_forum->checkForum($forum_id)) return $this->inform(100, $s_forum->getError());
		
		/* 视图 */
		return $this->fetch('', ['data' => []]);
	}
	
	/**
	 * 楼层编辑
	 * @param int $forum_id 主题ID
	 * @param int $thread_id 楼层ID
	 */
	public function thread_edit($thread_id = 0){
		if($this->request->isPost()){
			/* 校验 */
			//内容校验
			$content = $this->request->param('content');
			if(mb_strlen(trim(strip_tags($content)))<=6) return $this->dwzReturn(300, '请至少输入6个字符！');
			//详情
			$info = db('forum_thread')->where(['id' => $thread_id])->find();
			//校验主题
			$s_forum = service('Forum');
			if(!$s_forum->checkForum($info['forum_id'])) return $this->dwzReturn(100, $s_forum->getError());
			//作者校验
			if($info['user_id']!=$this->user_id) return $this->dwzReturn(300, '无法编辑别人的内容！');
			/* 写入 */
			$result = model('ForumThread')->allowField(true)->save([
				'content' => $content,
			], ['id' => $thread_id]);
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200, '', ['navTabId' => 'forum-'.$info['forum_id']]);
		}
		//详情
		$info = db('forum_thread')->where(['id' => $thread_id])->find();
		//校验主题
		$s_forum = service('Forum');
		if(!$s_forum->checkForum($info['forum_id'])) return $this->inform(100, $s_forum->getError());
		
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info' => $info,
		]]);
	}
	
	/**
	 * 楼内回复
	 * @param int $thread_id 楼层ID
	 * @param int $reply_id 回复ID
	 */
	public function reply_add($thread_id = 0, $reply_id = 0){
		if($this->request->isPost()){
			/* 校验 */
			//校验内容
			$content = $this->request->param('content');
			if(mb_strlen(trim(strip_tags($content)))<=6) return $this->dwzReturn(300, '请至少输入6个字符！');
			//详情
			$forum_id = db('forum_thread')->where(['id' => $thread_id])->value('forum_id');
			//校验主题
			$s_forum = service('Forum');
			if(!$s_forum->checkForum($forum_id)) return $this->dwzReturn(100, $s_forum->getError());
			
			/* 写入 */
			$result = model('ForumThreadReply')->allowField(true)->save([
				'thread_id' => $thread_id,
				'user_id'   => $this->user_id,
				'content'   => $content,
			]);
			if(!$result) return $this->dwzReturn(300);
			/* 成功 */
			//更新
			service('Thread')->push('Forum/forumReplyUpdate', $forum_id, $this->user_id);
			return $this->dwzReturn(200, '', ['navTabId' => 'forum-'.$forum_id]);
		}
		//详情
		$info = db('forum_thread')->where(['id' => $thread_id])->find();
		//校验主题
		$s_forum = service('Forum');
		if(!$s_forum->checkForum($info['forum_id'])) return $this->inform(100, $s_forum->getError());
		//回复某人
		$content = '';
		if($reply_id){
			$realname = db('forum_thread_reply')->alias('m')
				->join(config('database.prefix').'user u', 'm.user_id=u.id', 'LEFT')
				->where(['m.id' => $reply_id])
				->value('u.realname');
			$content .= $realname ? '@'.$realname.' ' : '';
		}
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info'    => $info,
			'content' => $content,
		]]);
	}
	
	/**
	 * 楼层删除
	 * @param int $thread_id 楼层ID
	 */
	public function thread_del($thread_id = 0){
		if($this->request->isPost()){
			//详情
			$info = db('forum_thread')->where(['id' => $thread_id])->find();
			if(!$info) return $this->ajaxReturn(['code'=>1000,'msg'=>'内容不存在！']);
			if($info['user_id']!=$this->user_id) return $this->ajaxReturn(['code'=>1000,'msg'=>'不能删除别人的内容！']);
			//主楼则删除主题
			if($info['number']==1){
				$result = model('Forum')->update(['status' => -1], ['id' => $info['forum_id']]);
				if(!$result) return $this->ajaxReturn(['code'=>1000,'msg'=>'操作失败']);
			}
			//删除楼层
			$result = model('ForumThread')->update(['status' => -1], ['id' => $thread_id]);
			if(!$result) return $this->ajaxReturn(['code'=>1000,'msg'=>'操作失败']);
			/* 成功 */
			return $this->ajaxReturn(['code'=>0,'msg'=>'操作成功！']);
		}
	}
	
	/**
	 * 楼内回复删除
	 * @param int $reply_id 回复ID
	 */
	public function reply_del($reply_id = 0){
		if($this->request->isPost()){
			//详情
			$info = db('forum_thread_reply')->where(['id' => $reply_id])->find();
			if(!$info) return $this->ajaxReturn(['code'=>1000,'msg'=>'内容不存在！']);
			if($info['user_id']!=$this->user_id) return $this->ajaxReturn(['code'=>1000,'msg'=>'不能删除别人的内容！']);
			//主楼则删除主题
			//删除楼层
			$result = model('ForumThreadReply')->update(['status' => -1], ['id' => $reply_id]);
			if(!$result) return $this->ajaxReturn(['code'=>1000,'msg'=>'操作失败']);
			/* 成功 */
			return $this->ajaxReturn(['code'=>0,'msg'=>'操作成功！']);
		}
	}
	
	/**
	 * 分类列表
	 */
	public function category_list($pageNum = 1, $numPerPage = null, $search = []){
		/* 获取数据 */
		$db_bulletin = db('forum_category');
		if(!empty($search['keyword'])){
			$db_bulletin->where(function($query) use ($search){
				$query->whereOr([
					'name' => ['like', '%'.$search['keyword'].'%'],
				]);
			});
		}
		$where       = [];
		$db_bulletin = $db_bulletin->field('*')->where($where)->order('id ASC');
		$list        = $this->dwzPaging($db_bulletin, $pageNum, $numPerPage);
		/* 视图 */
		return $this->fetch('', ['data' => [
			'list' => $list,
		]]);
	}
	
	/**
	 * 分类-新增
	 */
	public function category_add(){
		if($this->request->isPost()){
			$result = model('ForumCategory')->allowField(true)->save($this->request->post());
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200, '', ['url' => 'category_list']);
		}
		/* 视图 */
		return $this->fetch('', ['data' => []]);
	}
	
	/**
	 * 分类-编辑
	 */
	public function category_edit($id = 0){
		if($this->request->isPost()){
			$result = model('ForumCategory')->allowField(true)->save($this->request->post(), ['id' => $id]);
			if(!$result) return $this->dwzReturn(300);
			return $this->dwzReturn(200, '', ['url' => 'category_list']);
		}
		//详情
		$info = db('forum_category')->where(['id' => $id])->find();
		/* 视图 */
		return $this->fetch('', ['data' => [
			'info' => $info,
		]]);
	}
	
}