<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$uniacid=$_W['uniacid'];
$operation =empty($_GPC['op']) ? "display" :$_GPC['op'];

//专家团管理
if($operation == 'display'){
	// 前台加载模型数据
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

	//状态查询
	if($_GPC['status'] != '' && $_GPC['status'] != 10){
		$condition .= " AND status =:status";
		$where[':status'] = $_GPC['status'];
	}

	//关键字查询
	if($_GPC['content'] != ''){
		$condition .= " AND ( name LIKE '%{$_GPC['content']}%' or real_name LIKE '%{$_GPC['content']}%' or eg_code LIKE '%{$_GPC['content']}%' )";
	}

	$expertGroup = pdo_fetchall(' select * from '.tablename('chat_expert_group').$condition.' order by egid desc limit '.($pindex - 1) * $psize .',' .$psize.'',$where);
	$total = pdo_fetch('select count(*) as num from'.tablename('chat_expert_group').$condition,$where);
	$pager = pagination($total['num'], $pindex, $psize);
}

//专家团审核
if($operation == 'examine'){
	$egid = $_GPC['egid'];
	$pageNum = $_GPC['page'];
	if($_POST){

		if(!empty($_GPC['egid']) && !empty($_GPC['status'])){
			$expertGroupUpdate = pdo_update('chat_expert_group',array('status'=>$_GPC['status'],'audit_time'=>time()),array('uniacid'=>$_W['uniacid'],'egid'=>$_GPC['egid']));
			if($expertGroupUpdate){
                $headOfTheGroup = pdo_get('chat_expert_group',array('uniacid'=>$_W['uniacid'],'egid'=>$_GPC['egid']));//团长信息
				if($_GPC['status'] == 1){//通过
					pdo_insert('chat_expert_users',array('uniacid'=>$_W['uniacid'],'egid'=>$_GPC['egid'],'uid'=>$headOfTheGroup['uid'],'is_head'=>1,'status'=>2,'create_time'=>time()));
					$result = '通过';
                    $content = '您可邀请好友进入专家团或创建专家团服务赚收益';
                    $edig = $_GPC['egid'];
				}
				if($_GPC['status'] == 2){//拒绝
					$result = '不通过';
                    $content = '请修改资料并重新提交，如有疑问，请咨询客服';
				}


				$user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$headOfTheGroup['uid']),array('openid'));

				$weObj = WeAccount::create($_W['uniacid']);
	            $send_data = array(
	                'first'=>array('value'=>'您好，您的审核请求已处理'),
	                'keyword1'=>array('value'=>'专家团认证'),
	                'keyword2'=>array('value'=>$result,'color'=>'#173177'),
	                'keyword3'=>array('value'=>date('Y年m月d日 H:i:s',time())),
	                'remark'=>array('value'=>$content),
	            );

	            $url = $_W['siteroot'] . $this->createMobileUrl('ask_chat_reward',array('op'=>'expert_page','from'=>'template','egid'=>$edig));
	            $weObj->sendTplNotice($user['openid'], 'bqSYSyEgX_GEk8xbV8Ah6qd2mQ7xmyLBSX4rclAbO2Q', $send_data, $url, '#173177');

                //App 消息
                $push_data = array(
                    'type'=>"link",
                    'title'=>'专家团认证审核通知',
                    'content'=> "您好，您申请的专家团认证". $result."，".$content,
                    'url'=>$this->createMobileUrl('ask_chat_reward',array('op'=>'expert_panel','from'=>'template'))
                );
                $this->pushMessageToSingle($headOfTheGroup['uid'],$push_data);

				echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
			}else{
				echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
			}
		}
	}
}

//专家团上下架
if($operation == 'setOperation'){
    $pageNum = $_GPC['page'];
   if($_GPC['is_operation'] == 1){
        $GroupOperation = 0;
        $content = '您所在的专家团已上线，您可提供专家团服务';
        $status = "已上线";
   }else{
        $GroupOperation = 1;
        $content = '您所在的专家团已下线，暂时无法提供专家团服务，如有疑问，请咨询客服';
         $status = "已下线";
   }
   $operationUpdate = pdo_update('chat_expert_group',array('operation'=>$GroupOperation),array('uniacid'=>$_W['uniacid'],'egid'=>$_GPC['egid']));
   if(!empty($operationUpdate)){
        $groupChief = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'uid'=>$_GPC['uid']),array('openid','real_name'));
        $weObj = WeAccount::create($_W['uniacid']);
        $send_data = array(
            'first'=>array('value'=>'您好，您的账号状态已变更'),
            'keyword1'=>array('value'=>$groupChief['real_name']),
            'keyword2'=>array('value'=>$status,'color'=>'#173177'),
            'keyword3'=>array('value'=>date('Y年m月d日 H:i:s',time())),
            'remark'=>array('value'=>$content),
        );
        $url = $_W['siteroot'] . $this->createMobileUrl('ask_chat_reward',array('op'=>'expert_panel','from'=>'template'));
        $weObj->sendTplNotice($groupChief['openid'], 'bqSYSyEgX_GEk8xbV8Ah6qd2mQ7xmyLBSX4rclAbO2Q', $send_data, $url, '#173177');

        //App 消息
        $push_data = array(
            'type'=>"link",
            'title'=>'专家团状态变更',
            'content'=> $status.$content,
            'url'=>$this->createMobileUrl('ask_chat_reward',array('op'=>'expert_panel','from'=>'template'))
        );
        $this->pushMessageToSingle($_GPC['uid'],$push_data);

        itoast('操作成功','',success);
   }else{
        itoast('操作失败','',error);
   }
}

//专家团详情
if($operation == 'details'){
	if($_POST){
		if(!empty($_GPC['egid'])){
			$expertGroupData = array(
				'name' => $_GPC['name'],
				'headimg' => $_GPC['headimg'],
				'explain' => $_GPC['explain'],
				'real_name' => $_GPC['real_name'],
				'yyzz' => $_GPC['yyzz'],
				'type' => $_GPC['type'],
				'is_open' => $_GPC['is_open'],
				'province' => $_GPC['province'],
				'city' => $_GPC['city'],
				'area' => $_GPC['district'],
				'operation' => $_GPC['operation']
			);
            $expertGroup = pdo_get('chat_expert_group',array('uniacid'=>$_W['uniacid'],'egid'=>$_GPC['egid']));//专家团信息
            $expertGroupUser = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$expertGroup['uid']),"openid");//专家信息

			$expertGroupUpdate = pdo_update('chat_expert_group',$expertGroupData,array('uniacid'=>$_W['uniacid'],'egid'=>$_GPC['egid']));
			if($expertGroupUpdate){
                if($_GPC['operation']==1){
                    //下线
                    $content = '您所在的专家团已下线，暂时无法提供专家团服务，如有疑问，请咨询客服';
                    $status = "已下线";
                }else{
                    //上线
                    $content = '您所在的专家团已上线，您可提供专家团服务';
                    $status = "已上线";

                }
                $weObj = WeAccount::create($_W['uniacid']);
                $send_data = array(
                    'first'=>array('value'=>'您好，您的账号状态已变更'),
                    'keyword1'=>array('value'=>$_GPC['real_name']),
                    'keyword2'=>array('value'=>$status,'color'=>'#173177'),
                    'keyword3'=>array('value'=>date('Y年m月d日 H:i:s',time())),
                    'remark'=>array('value'=>$content),
                );
                $url = $_W['siteroot'] . $this->createMobileUrl('ask_chat_reward',array('op'=>'expert_panel','from'=>'template'));
                $weObj->sendTplNotice($expertGroupUser['openid'], 'bqSYSyEgX_GEk8xbV8Ah6qd2mQ7xmyLBSX4rclAbO2Q', $send_data, $url, '#173177');

                 //App 消息
                $push_data = array(
                    'type'=>"link",
                    'title'=>'专家团状态变更',
                    'content'=> $status.$content,
                    'url'=>$this->createMobileUrl('ask_chat_reward',array('op'=>'expert_panel','from'=>'template'))
                );
                $this->pushMessageToSingle($_GPC['uid'],$push_data);

				echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
			}else{
				echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
			}
		}
	}
	if(!empty($_GPC['egid'])){
		$pageNum = $_GPC['page'];
		$expertGroup = pdo_get('chat_expert_group',array('uniacid'=>$_W['uniacid'],'egid'=>$_GPC['egid']));//专家团信息
		$expertGroup['yyzz'] = explode(',',$expertGroup['yyzz']);
		$condition = ' where e.uniacid = :uniacid and e.egid = :egid and e.status in (1,2) ';
		$where[':uniacid'] = $_W['uniacid'];
		$where[':egid'] = $_GPC['egid'];
		$expertUsers = pdo_fetchall('select e.*,u.real_name,u.mobile from'.tablename('chat_expert_users').' e LEFT JOIN '.tablename('chat_users').' u ON u.id = e.uid '.$condition,$where);//专家团员信息
	}
}

//专家团服务
if($operation == 'expertGroupService'){
    $pindex = max(1, intval($_GPC['page']));
    $psize = 20;

    $condition = ' where s.uniacid = :uniacid and s.s_type = 6 and s.is_del = 0 ';
    $where[':uniacid'] = $_W['uniacid'];

    //时间查询
    if($_GPC['status_select'] != 1 && $_GPC['status_select'] != ''){
        $condition .= ' and s.'.$_GPC['status_select'].' BETWEEN :start and :end ';
        $where[':start'] = strtotime($_GPC['time']['start'].' 00:00:00 ');
        $where[':end'] = strtotime($_GPC['time']['end'].' 23:59:59 ');
    }

    //审核状态查询
    if($_GPC['status'] != '' && $_GPC['status'] != 10){
        $condition .= ' and s.status = :status ';
        $where[':status'] = $_GPC['status'];
    }

    //关键字查询
    if($_GPC['fwtitle'] != ''){
        $condition .= " and (s.title LIKE '%{$_GPC['fwtitle']}%' OR g.name LIKE '%{$_GPC['fwtitle']}%' OR g.real_name LIKE '%{$_GPC['fwtitle']}%') ";
    }

    $expertGroupService = pdo_fetchall('select s.sid,s.title,s.status,s.service_desc,s.create_time,s.price,s.service_time,g.name,g.real_name,s.is_by_stages,s.is_price from'.tablename('chat_ask_user_service').' s LEFT JOIN '.tablename('chat_expert_group').' g ON g.egid = s.uid '.$condition.' order by s.create_time desc limit '.($pindex - 1) * $psize .',' .$psize.'',$where);

    $total = pdo_fetchcolumn('select count(*) from'.tablename('chat_ask_user_service').' s LEFT JOIN '.tablename('chat_expert_group').' g ON g.egid = s.uid '.$condition,$where);

    $pager  = pagination($total, $pindex, $psize);
}

//专家团服务审核
if($operation == 'groupServiceExamine'){

    $sid = $_GPC['sid'];
    $pageNum = $_GPC['page'];
    if($_POST){

        if(!empty($_GPC['sid']) && !empty($_GPC['status'])){
            $userServiceUpdate = pdo_update('chat_ask_user_service',array('status'=>$_GPC['status']),array('uniacid'=>$_W['uniacid'],'sid'=>$_GPC['sid']));

            if($userServiceUpdate){
                $userService = pdo_get('chat_ask_user_service',array('uniacid'=>$_W['uniacid'],'sid'=>$_GPC['sid']));
                if($_GPC['status'] == 2){
                    pdo_insert('chat_ask_user_service_log',array('uniacid'=>$_W['uniacid'],'sid'=>$_GPC['sid'],'price'=>$userService['price'],'uid'=>$userService['uid'],'is_del'=>0,'create_time'=>time()));
                    //通过
                    $status = "通过";
                    $content = '上架服务赚收益吧';
                }else if($_GPC['status']==4){
                    //拒绝
                    $status = "不通过";
                    $content = '请修改服务并重新提交，如有疑问，请咨询客服';
                }

                $expertGroup = pdo_get('chat_expert_group',array('uniacid'=>$_W['uniacid'],'egid'=>$userService['uid']));//专家团信息
                $expertGroupUser = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$expertGroup['uid']),"openid");//专家信息

                $weObj = WeAccount::create($_W['uniacid']);
                $send_data = array(
                    'first'=>array('value'=>'您好，您的审核请求已处理'),
                    'keyword1'=>array('value'=>'专家团服务'),
                    'keyword2'=>array('value'=>$status,'color'=>'#173177'),
                    'keyword3'=>array('value'=>date('Y年m月d日 H:i:s',time())),
                    'remark'=>array('value'=>$content),
                );

                $url = $_W['siteroot'] . $this->createMobileUrl('ask_chat_reward',array('op'=>'the_team','from'=>'template','egid'=>$userService['uid']));
                $weObj->sendTplNotice($expertGroupUser['openid'], 'bqSYSyEgX_GEk8xbV8Ah6qd2mQ7xmyLBSX4rclAbO2Q', $send_data, $url, '#173177');

                 //App 消息
                $push_data = array(
                    'type'=>"link",
                    'title'=>'专家团审核',
                    'content'=> $status.$content,
                    'url'=>$this->createMobileUrl('ask_chat_reward',array('op'=>'the_team','from'=>'template','egid'=>$userService['uid']))
                );
                $this->pushMessageToSingle($expertGroup['uid'],$push_data);

                itoast('操作成功',$this->createWebUrl('agent',array('op'=>'expertGroupService','version_id'=>0,'page'=>$_GPC['pageNum'])),'success');
            }else{
                itoast('操作失败',$this->createWebUrl('agent',array('op'=>'expertGroupService','version_id'=>0,'page'=>$_GPC['pageNum'])),'error');
            }
        }
    }
}

//专家团服务上下架
if($operation == 'setStatus'){
    if(!empty($_GPC['sid']) && !empty($_GPC['status'])){
        $serviceUpdate = pdo_update('chat_ask_user_service',array('status'=>$_GPC['status']),array('uniacid'=>$_W['uniacid'],'sid'=>$_GPC['sid']));
        if($serviceUpdate){
            itoast('操作成功','','success');
        }else{
            itoast('操作失败','','error');
        }
    }else{
        itoast('操作失败','','error');
    }
}

//专家团服务删除
if($operation == 'groupServiceDel'){
    if(!empty($_GPC['sid'])){
        $serviceDel = pdo_update('chat_ask_user_service',array('is_del'=>1),array('uniacid'=>$_W['uniacid'],'sid'=>$_GPC['sid']));
        if($serviceDel){
            pdo_update('chat_ask_user_service_log',array('is_del'=>1),array('uniacid'=>$_W['uniacid'],'sid'=>$_GPC['sid']));
            itoast('操作成功','','success');
        }else{
            itoast('操作失败','','error');
        }
    }else{
        itoast('操作失败','','error');
    }
}

//编辑专家团服务
if($operation == 'editGroupService'){
    if($_POST){
        if(!empty($_GPC['sid'])){
            $GroupServiceData = array(
                'title' => $_GPC['title'],
                'service_desc' => $_GPC['service_desc'],
                'price' => $_GPC['price'],
                'is_by_stages' => $_GPC['is_by_stages'],
                'is_price' => $_GPC['is_price'],
                'service_time' => $_GPC['service_time'],
                'status' => $_GPC['status']
            );
            $GroupServiceUpdate = pdo_update('chat_ask_user_service',$GroupServiceData,array('uniacid'=>$_W['uniacid'],'sid'=>$_GPC['sid']));
            if($GroupServiceUpdate){
                pdo_update('chat_ask_user_service_log',array('price'=>$_GPC['price']),array('uniacid'=>$_W['uniacid'],'sid'=>$_GPC['sid']));
                itoast('操作成功',$this->createWebUrl('agent',array('op'=>'expertGroupService','version_id'=>0,'page'=>$_GPC['pageNum'])),'success');
            }else{
                itoast('操作失败','','error');
            }
        }
    }
    if(!empty($_GPC['sid'])){
        $sid = $_GPC['sid'];
        $pageNum = $_GPC['page'];
        $groupService = pdo_get('chat_ask_user_service',array('uniacid'=>$_W['uniacid'],'sid'=>$_GPC['sid']));//服务信息
        $expertGroup = pdo_get('chat_expert_group',array('uniacid'=>$_W['uniacid'],'egid'=>$groupService['uid']));
    }
}

include $this->template('agent');
?>