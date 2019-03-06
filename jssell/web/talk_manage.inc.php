<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$uniacid=$_W['uniacid'];

$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
$id = intval($_GPC['id']);
if($op == 'display'){
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;

	$condition = ' where w.uniacid = :uniacid and w.status = 1 ';
	$where[':uniacid'] = $_W['uniacid'];

	//查询
	if($_GPC['is_audit'] != ''){
		$condition .=' and w.is_audit = :is_audit ';
		$where[':is_audit'] = $_GPC['is_audit'];
	}

	if($_GPC['keyword']){
		$condition .= " AND ( w.title LIKE '%{$_GPC['keyword']}%' OR w.content LIKE '%{$_GPC['keyword']}%' OR u.real_name LIKE '%{$_GPC['keyword']}%' ) ";
	}
	
	$total = pdo_fetchcolumn("SELECT COUNT(0) FROM ".tablename('chat_weike').' w INNER JOIN '.tablename('chat_users').' u ON w.uid = u.id '.$condition,$where);

	$weike = pdo_fetchall('select w.mi_id,w.answer_content_down,w.create_time,w.title,w.yuedu,w.status,w.content,w.is_jing,w.is_audit,u.nickname,u.real_name from'.tablename('chat_weike').' w INNER JOIN '.tablename('chat_users').' u ON w.uid = u.id '.$condition." ORDER BY w.create_time DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$where);

	foreach ($weike as $key => $value) {
		if($value['answer_content_down'] != ''){
			$weike[$key]['answer_content_down'] = $this->to_qiniu_url($value['answer_content_down']);
		}
	}

	$pager = pagination($total, $pindex, $psize);
}

//改变精选状态
if($op == 'essence'){
	if($_GPC['mi_id']){
		$weike = pdo_get('chat_weike',array('uniacid'=>$_W['uniacid'],'mi_id'=>$_GPC['mi_id']));

		if($weike['is_jing'] == 0){
			$is_jing = 1;
		}elseif($weike['is_jing'] == 1){
			$is_jing = 0;
		}

		$weike_update = pdo_update('chat_weike',array('is_jing'=>$is_jing),array('uniacid'=>$_W['uniacid'],'mi_id'=>$_GPC['mi_id']));

		if($weike_update){
			echo json_encode(array('code'=>1,'jing'=>$is_jing,'msg'=>'操作成功'));exit;
		}else{
			echo json_encode(array('code'=>2,'msg'=>'操作失败'));exit;
		}
	}
}

//改变上下架状态
if($op == 'trial'){
	if($_GPC['mi_id']){
		$weike = pdo_get('chat_weike',array('uniacid'=>$_W['uniacid'],'mi_id'=>$_GPC['mi_id']));

		if($weike['is_audit'] == 1){
			$is_audit = 2;
		}elseif($weike['is_audit'] == 2){
			$is_audit = 1;
		}

		$weike_update = pdo_update('chat_weike',array('is_audit'=>$is_audit),array('uniacid'=>$_W['uniacid'],'mi_id'=>$_GPC['mi_id']));

		if($weike_update){
			echo json_encode(array('code'=>1,'audit'=>$is_audit,'msg'=>'操作成功'));exit;
		}else{
			echo json_encode(array('code'=>2,'msg'=>'操作失败'));exit;
		}

	}
}

//删除
if($op == 'del'){
	if($_GPC['mi_id']){
		$weike_update = pdo_update('chat_weike',array('status'=>0),array('uniacid'=>$_W['uniacid'],'mi_id'=>$_GPC['mi_id']));
		if($weike_update){
			echo json_encode(array('code'=>1,'msg'=>'操作成功'));exit;
		}else{
			echo json_encode(array('code'=>2,'msg'=>'操作失败'));exit;
		}
	}
}

//评论列表
if($op == 'comment'){
	$mi_id = $_GPC['mi_id'];
	if($mi_id){
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;

		$condition = " where uniacid = :uniacid and type_id = :type_id and type = :type ";
		$where[':uniacid'] = $_W['uniacid'];
		$where[':type_id'] = $_GPC['mi_id'];
		$where[':type'] = "weike";

		//查询
		if($_GPC['is_answer']){
			$condition .= ' and is_answer = '.$_GPC['is_answer'];
		}
		if($_GPC['keyword']){
			$condition .= " and ( nickname LIKE '%{$_GPC['keyword']}%' or content LIKE '%{$_GPC['keyword']}%' ) ";
		}

		$comment = pdo_fetchall('select * from'.tablename('chat_article_ask_comment').$condition." ORDER BY create_time DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$where);//评论
		foreach ($comment as $key => $val) {
	        $comment[$key]['contents'] = html_entity_decode($val['content']);
	    }

		$total = pdo_fetchcolumn("SELECT COUNT(0) FROM ".tablename('chat_article_ask_comment').$condition,$where);

		$pager = pagination($total, $pindex, $psize);
	}

}

//回复
if($op == 'reply'){
	$aaid = $_GPC['aaid'];
	$mi_id = $_GPC['mi_id'];
	if($_W['ispost']){
		$comment = pdo_get('chat_article_ask_comment',array('uniacid'=>$_W['uniacid'],'aaid'=>$aaid));
		$user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'ruid'=>$_W['user']['uid']));
        $com_data = array(
            'uniacid' => $_W['uniacid'],
            'uid' => $user['id'],
            'pid' => $comment['aaid'],
            'type_id' => $comment['type_id'],
            'type' => $comment['type'],
            'content' => $_GPC['info'],
            'create_time' => time(),
            'nickname' => $user['nickname'],
            'avatar' => $user['avatar'],
            'is_answer' => 1,
            'is_show' => 1
        );

        $com_update = pdo_update('chat_article_ask_comment',array('is_answer'=>1),array('aaid'=>$comment['aaid'],'uniacid'=>$_W['uniacid']));
        $com_insert = pdo_insert('chat_article_ask_comment',$com_data);
        if($com_insert){
            itoast('操作成功',$this->createWebUrl('talk_manage',array('op' => 'comment','version_id'=>0,'mi_id'=>$_GPC['mi_id'])), "success");
        }else{
            itoast('操作失败','', "error");
        }

	}
}

//修改显示状态
if($op == 'set_show'){
	if($_W['ispost']){
		if($_GPC['aaid'] && $_GPC['is_show']){
			if($_GPC['is_show'] == 1){
				$is_show = '0';
			}else{
				$is_show = 1;
			}

			//修改评论状态
			$comment_update = pdo_update('chat_article_ask_comment',array('is_show'=>$is_show),array('uniacid'=>$_W['uniacid'],'aaid'=>$_GPC['aaid']));
			//判断是否有回复
			$comment = pdo_get('chat_article_ask_comment',array('uniacid'=>$_W['uniacid'],'pid'=>$_GPC['aaid']));
			if($comment){
				//修改回复状态
				$reply_update = pdo_update('chat_article_ask_comment',array('is_show'=>$is_show),array('uniacid'=>$_W['uniacid'],'pid'=>$_GPC['aaid']));
			}
			if($comment_update){
				echo json_encode(array('code'=>1,'status'=>$is_show,'msg'=>'操作成功'));exit;
			}else{
				echo json_encode(array('code'=>2,'msg'=>'操作失败'));exit;
			}
		}
	}
}

//微课详情
if($op == 'wk_details'){
	if($_GPC['mi_id']){
		$weike = pdo_get('chat_weike',array('uniacid'=>$_W['uniacid'],'mi_id'=>$_GPC['mi_id']));
		$user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$weike['uid']));
		$weike['answer_content_down'] = $weike['answer_content_down'] != '' ? $this->to_qiniu_url($weike['answer_content_down']) : '';
	}
}

include $this->template('talk_manage');
?>