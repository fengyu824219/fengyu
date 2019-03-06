<?php
	global $_W,$_GPC;
	$operation =empty($_GPC['op']) ? "display" :$_GPC['op'];

	if($operation == 'display'){//评论管理
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;

		$condition = " where uniacid = :uniacid ";
		$where[':uniacid'] = $_W['uniacid'];

		//时间查询
		if($_GPC['status_select'] != '' && $_GPC['status_select'] != 1){
			$condition .=  ' AND '.$_GPC['status_select'].' BETWEEN :start and :end';
            $where[':start'] = strtotime($_GPC['time']['start'].' 00:00:00');
            $where[':end'] = strtotime($_GPC['time']['end'].' 23:59:59');
		}

		//类型
		if($_GPC['type'] != '' && $_GPC['type'] != '10'){
			$condition .= ' and type = :type';
			$where[':type'] = $_GPC['type'];
		}

		//状态
		if($_GPC['is_show'] != '' && $_GPC['is_show'] != '10'){
			$condition .= ' and is_show = :is_show';
			$where[':is_show'] = $_GPC['is_show'];
		}

		//关键字
		if($_GPC['nickname'] != ''){
			$condition .= " and nickname LIKE '%{$_GPC['nickname']}%' ";
		}

		$comment = pdo_fetchall('select aaid,pid,content,nickname,avatar,create_time,type,type_id,is_show,is_answer,uid from'.tablename('chat_article_ask_comment').$condition." ORDER BY create_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$where);
		$total = pdo_fetch('select count(*) as num from'.tablename('chat_article_ask_comment').$condition,$where);
		$pager = pagination($total['num'], $pindex, $psize);
		foreach ($comment as $key => $val) {
			if($val['type'] == 'article'){//文章
				$comment[$key]['art_title'] = pdo_fetch('select title from'.tablename('chat_article_article').' where uniacid = :uniacid and id = :id ',array(':uniacid'=>$_W['uniacid'],':id'=>$val['type_id']));
			}
			if($val['type'] == 'ask'){//问答
				$comment[$key]['ask_title'] = pdo_fetch('select ask_content from'.tablename('chat_ask').' where uniacid = :uniacid and id = :id ',array(':uniacid'=>$_W['uniacid'],':id'=>$val['type_id']));
			}
			if($val['type'] == 'weike'){//微课
				$comment[$key]['weike_title'] = pdo_fetch('select title from'.tablename('chat_weike').' where uniacid = :uniacid and mi_id = :mi_id ',array(':uniacid'=>$_W['uniacid'],':mi_id'=>$val['type_id']));
			}
		}
	}

	if($operation == 'set_show'){//改变审核状态
		if($_GPC['aaid']){
			$comment_update = pdo_update('chat_article_ask_comment',array('is_show'=>1),array('aaid'=>$_GPC['aaid'],'uniacid'=>$_W['uniacid']));
			if($comment_update){
				$commentData = pdo_get('chat_article_ask_comment',array('uniacid'=>$_W['uniacid'],'aaid'=>$_GPC['aaid']),array('uid','type'));
				if(!empty($commentData)){
					if($commentData['type'] == 'article'){//文章评论
						$this->add_integral($commentData['uid'],6,$_GPC['aaid']);//添加积分
					}elseif($commentData['type'] == 'ask'){//问题评论
						$this->add_integral($commentData['uid'],14,$_GPC['aaid']);//添加积分
					}
				}
				echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
			}else{
				echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
			}
		}
	}

	if($operation == 'del'){//删除评论
		if($_GPC['aaid']){
			$comment_del = pdo_delete('chat_article_ask_comment',array('uniacid'=>$_W['uniacid'],'aaid'=>$_GPC['aaid']));
			if($comment_del){
				echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
			}else{
				echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
			}
		}
	}

	if($operation == 'reply'){//回复评论
		if($_GPC['aaid']){
			$comment = pdo_get('chat_article_ask_comment',array('uniacid'=>$_W['uniacid'],'aaid'=>$_GPC['aaid']));
			// $user = pdo_get('chat_users',array('id'=>$_W['user']['uid'],'uniacid'=>$_W['uniacid']));
		}
		if($_POST){
			if($_GPC['content']){
				$data = [
					'uniacid' => $_W['uniacid'],
					'uid' => $_W['user']['uid'],
					'pid' => $_GPC['aaid'],
					'type_id' => $comment['type_id'],
					'type' => $comment['type'],
					'content' => $_GPC['content'],
					'create_time' => time(),
					'nickname' => $_W['user']['username'],
					// 'avatar' => $user['avatar'],
					'is_show' => 1
				];
				$com_update = pdo_update('chat_article_ask_comment',array('is_answer'=>1),array('uniacid'=>$_W['uniacid'],'aaid'=>$comment['aaid']));
				$comment_insert = pdo_insert('chat_article_ask_comment',$data);
				if($comment_insert){
					itoast('操作成功',$this->createWebUrl('comment',array('eid'=>$_GPC['eid'],'version_id'=>0,'page'=> $_GPC['page'])), "success");
				}else{
					itoast('操作失败','', "error");
				}
			}
		}
	}

	if($operation == 'batch'){
		if($_GPC['aaids']){
			if($_GPC['is_show'] == 1){//审核通过
				$aaids = explode(',',$_GPC['aaids']);
				foreach ($aaids as $key => $val) {
					$comment = pdo_update('chat_article_ask_comment',array('is_show'=>1),array('uniacid'=>$_W['uniacid'],'aaid'=>$val));
					if($comment){
						$commentData = pdo_get('chat_article_ask_comment',array('uniacid'=>$_W['uniacid'],'aaid'=>$val),array('uid','type'));
						if(!empty($commentData)){
							if($commentData['type'] == 'article'){//文章评论
								$this->add_integral($commentData['uid'],6,$val);//添加积分
							}elseif($commentData['type'] == 'ask'){//问题评论
								$this->add_integral($commentData['uid'],14,$val);//添加积分
							}
						}
					}
				}
			}else if($_GPC['is_show'] == 2){//审核拒绝
				$aaids = explode(',',$_GPC['aaids']);
				foreach ($aaids as $key => $val) {
					$comment = pdo_delete('chat_article_ask_comment',array('uniacid'=>$_W['uniacid'],'aaid'=>$val));
				}
			}
			if($comment){
				echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
			}else{
				echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
			}
		}
	}

	include $this -> template('comment');
?>