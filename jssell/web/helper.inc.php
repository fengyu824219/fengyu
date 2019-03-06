<?php
	global $_W,$_GPC;
	$operation =empty($_GPC['op']) ? "display" :$_GPC['op'];

	if($operation == 'display'){
		// 前台加载模型数据
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$condition = 'where uniacid=:uniacid and is_del = 0' ;
		$where = array(":uniacid"=>$_W['uniacid']);

		if($_GPC['ask']){
			$condition .= " AND ask LIKE :ask";
			$where[':ask'] = '%'.$_GPC['ask'].'%';
		}
		if($_GPC['status'] != 7 && $_GPC['status'] != ''){
			$condition .= " AND status =:status";
			$where[':status'] = $_GPC['status'];
		}
		$total=pdo_fetch('select count(*) as num from'.tablename('helper').$condition,$where);
		$adv = pdo_fetchall('select * from'.tablename('helper').$condition.' order by sort asc limit '.($pindex - 1) * $psize .',' .$psize.'',$where);
		$pager = pagination($total['num'], $pindex, $psize);
	}




    if($operation == 'ask'){
	    if(empty($_GPC['ask'])){
	        echo 1;exit;
        }
        $res = pdo_get('helper',array('ask'=>trim($_GPC['ask'])),array('ask','type'));

	    if($res['ask'] == $_GPC['ask'] || $res <> false){
	        echo 2;exit; //该问题已经存在
        }else{
	        echo 3;exit;
        }

    }



	if($operation == 'help'){
		// 修改时：加载该条数据
		$v=pdo_get('helper',array('uniacid'=>$_W['uniacid'],'hid'=>$_GPC['hid']));
		// 添加商户时，校验前台表单提交
		if(checksubmit('submit')){
            $pattern = array('/ /','/ /','/\r\n/','/\n/');
                $replace = array('&nbsp;','&nbsp;','<br />');
                $message=preg_replace($pattern, $replace, $_POST['answer']);

			$str = array(
				'uniacid'=>intval($_W['uniacid']),
				'type'=>$_GPC['type'],
				'ask'=>$_GPC['ask'],
				'answer'=>$message,
				'content'=>$_GPC['content'],
				'status'=>$_GPC['status'],
				'sort'=>$_GPC['sort']
			);
            $res = pdo_get('helper',array('type'=>trim($_GPC['type'])),array('type'));

			// 判断是修改、还是添加
			if(empty($_GPC['hid'])){
				// 插入数据库
				$str['create_time'] = time();
				$str['clientip'] = $_W['clientip'];
				$res=pdo_insert('helper',$str);
				if($res){
					itoast('添加成功',$this->createWebUrl('helper', array('eid'=>$_GPC['eid'],'version_id'=>0)),success);
				}else{
					itoast('添加失败','',error);
				}
			}else{
				$res=pdo_update('helper',$str,array('hid'=>$_GPC['hid']));
				if($res || $_GPC['hid']){
					itoast('修改成功',$this->createWebUrl('helper',array('eid'=>$_GPC['eid'],'version_id'=>0)),success);
				}else{
					itoast('修改失败','',error);
				}
			}
		}
	}

    //修改
    if($operation == 'status'){
        $res=pdo_update('helper',array('status'=>$_GPC['status']),array('hid'=>$_GPC['hid']));
        if($res){
            itoast('操作成功.', '', success);
        }else{
            itoast('操作失败','',error);
        }
    }
    //删除
    if($operation == 'del'){
    	$is_del = $_GPC['is_del'];
    	$helper_where = array('is_del'=>$is_del);
    	$helper = pdo_update('helper',$helper_where,array('uniacid'=>$_W['uniacid'],'hid'=>$_GPC['hid']));
    	if($helper == 1){
            itoast('操作成功',$this->createWebUrl('helper',array('eid'=>$_GPC['eid'],'version_id'=>0)),success);
        }else{
            itoast('操作失败','',error);
        }
    }

    //用户反馈意见
    if($operation == 'userfeedback'){

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = 'where s.uniacid=:uniacid';
        $where = array(":uniacid"=>$_W['uniacid']);

        //模糊查询  根据时间
        if($_GPC['status_select'] != 1 && $_GPC['status_select'] != ''){
            if($_GPC['time']){
                $condition .=  ' AND s.'.$_GPC['status_select'].' BETWEEN :start and :end';
                $where[':start'] = strtotime($_GPC['time']['start'].' 00:00:00');
                $where[':end'] = strtotime($_GPC['time']['end'].' 23:59:59');
            }
        }
        //模糊查询  根据商户名,用户名,手机号
        if($_GPC['title']){
            $condition .= " AND (  u.nickname LIKE :title OR s.contact LIKE :title ) ";
            $where[':title'] = '%'.$_GPC['title'].'%';
        }
        $total=pdo_fetch('select count(*) as num from '.tablename('user_suggest').'s left join '.tablename('chat_users').' u on u.id=s.uid '.$condition,$where);
        $adv=pdo_fetchall('select s.contact,s.opinion,s.create_time,u.nickname,s.suid,s.answer,s.photo from '.tablename('user_suggest').'s left join '.tablename('chat_users').' u on u.id=s.uid '.$condition.' order by suid desc limit '.($pindex - 1) * $psize .',' .$psize.'',$where);
        $pager = pagination($total['num'], $pindex, $psize);
    }

    if($operation == 'answer'){

    	if($_GPC['suid']){
    		$user_suggest = pdo_get('user_suggest',array('suid'=>$_GPC['suid'],'uniacid'=>$_W['uniacid']));
    		if (checksubmit('submit')) {
                $pattern = array('/ /','/ /','/\r\n/','/\n/');
                $replace = array('&nbsp;','&nbsp;','<br />');
                $message=preg_replace($pattern, $replace, $_POST['user_answer']);
        		$suggest_update = pdo_update('user_suggest',array('answer'=>$message),array('suid'=>$_GPC['suid']));
        		if($suggest_update){
        			itoast('操作成功',$this->createWebUrl('helper',array('op'=>'userfeedback','eid'=>$_GPC['eid'],'version_id'=>0)),success);
        		}else{
        			itoast('操作失败','',error);
        		}
    		}
    	}
    }

    include $this -> template('helper');
?>