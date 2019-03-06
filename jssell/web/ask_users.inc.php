<?php
global $_GPC, $_W;
checklogin();
$uniacid=$_W['uniacid'];
load()->func('tpl');
$op=!empty($_GPC['op']) ? $_GPC['op'] : 'display';
$start = $_GPC['time']['start'] ? $_GPC['time']['start'] : $_GPC['start'];
$end = $_GPC['time']['end'] ? $_GPC['time']['end'] : $_GPC['end'];
if(!empty($_GPC['id'])){

	$user_id=intval($_GPC['id']);
	$ask_user=pdo_fetch("SELECT is_openask,is_recommend FROM ".tablename("chat_users")." WHERE id=:id ",array(":id"=>$user_id));

	if($op=='recommend'){
		header('content-type:application/json;charset=utf8');
		$is_recommend=$ask_user['is_recommend'];
		if($is_recommend=="0"){
			$is_recommend=1;
		}else{
			$is_recommend=0;
		}
		pdo_update("chat_users",array('is_recommend'=>$is_recommend),array("id"=>$user_id));
		$fmdata = array(
				"success" => 1,
				"data" =>$is_recommend,
		);
		echo json_encode($fmdata);
		exit;
	}elseif ($op == 'balance') {
		$id = intval($_GPC['id']);
		if (checksubmit('submit')) {
			$data = array(
					'balance' => $_GPC['balance']
			);
			pdo_update("chat_users", $data, array('id' => $id));
			// itoast('更新余额成功！', $this->createWebUrl('ask_users', array('op' => 'display','version_id'=>0)), 'success');
			itoast('更新余额成功！', $this->createWebUrl('ask_users', array('op' => 'display','version_id'=>0)));
		}
		$ask_user=pdo_fetch("SELECT * FROM ".tablename("chat_users")." WHERE id=:id",array(":id"=>$user_id));
		$sid = $id;
	//编辑用户信息
	}elseif($op == 'edit_users'){
        $sid = intval($_GPC['id']);

        if (checksubmit('submit')) {
            $data = array(
                     'nickname'=>$_GPC['nickname'],
                     'mobile' => $_GPC['mobile'],
                     'real_name'=>$_GPC['real_name']
            );
            pdo_update("chat_users", $data, array('id' => $sid));
            // itoast('操作成功！', $this->createWebUrl('ask_users', array('op' => 'display','version_id'=>0)), 'success');
            itoast('操作成功！', $this->createWebUrl('ask_users', array('op' => 'display','version_id'=>0)));
        }
        $user = pdo_fetch('select nickname,mobile,real_name,user_title from '.tablename('chat_users') . "where id=$sid");


    }elseif($op == 'onoff_users'){
        $sid = intval($_GPC['id']);
        $onoff = intval($_GPC['onoff']);
        if(empty($sid)){
            itoast('用户不存在', "",error);
        }
        if($onoff == 1){
            $data['onoff'] = 2;
        }else{
            $data['onoff'] = 1;

        }
        pdo_update("chat_users", $data, array('id' => $sid));
        itoast('操作成功！', $this->createWebUrl('ask_users', array('op' => 'display','version_id'=>0)));

    }
}else{
	if($op == 'display'){
		if($_POST){//搜索进行指定
			if(!empty($_GPC['keyword'])){
				$appointCondition = " where uniacid = :uniacid and ( mobile LIKE '%{$_GPC['keyword']}%' OR real_name LIKE '%{$_GPC['keyword']}%' ) ";
				$appointWhere[':uniacid'] = $_W['uniacid'];
				$appointUser = pdo_fetchall(' select id,nickname,real_name,mobile,avatar from '.tablename('chat_users').$appointCondition,$appointWhere);
				foreach ($appointUser as $key => $val) {
					$appointUser[$key]['avatar'] = tomedia($val['avatar']);
				}
				if($appointUser){
					echo json_encode(array('code'=>200,'data'=>$appointUser));exit;
				}
			}
		}

		$tempcondition=" where uniacid = '{$_W['uniacid']}'";
		$keyword=$_GPC['keyword'];
		$tempArray=array();

		//时间查询
		if($_GPC['status_select'] != '' && $_GPC['status_select'] != 1){

			$start = $_GPC['time']['start'] ? $_GPC['time']['start'] : $_GPC['start'];
			$end = $_GPC['time']['end'] ? $_GPC['time']['end'] : $_GPC['end'];

			$tempcondition .=  ' AND '.$_GPC['status_select'].' BETWEEN :start and :end';
            $tempArray[':start'] = strtotime($start.' 00:00:00');
            $tempArray[':end'] = strtotime($end.' 23:59:59');

		}

		//性别
		if($_GPC['sex'] != '' && $_GPC['sex'] != 3){
			$tempcondition .= ' and sex = '.$_GPC['sex'];
		}

		//身份类型
		if(!empty($_GPC['type'])){
			if($_GPC['type'] == 'expert'){//专家
				$tempcondition .= ' and vstatus = 1 ';
			}elseif($_GPC['type'] == 'platformExpert'){//平台专家
				$tempcondition .= ' and is_platform = 1 ';
			}elseif($_GPC['type'] == 'salesman'){//税媒大使
				$tempcondition .= ' and is_salesman = 1 ';
			// }elseif($_GPC['type'] == 'partnerSalesman'){//城市合伙人
			// 	$tempcondition .= ' and is_salesman = 2 ';
			}elseif($_GPC['type'] == 'partner'){//运营合伙人
				$tempcondition .= ' and is_partner = 1 ';
			}elseif($_GPC['type'] == 'operate'){//运营总监
				$tempcondition .= ' and is_boss = 1 ';
			}
		}

		//搜索关键词
		if(!empty($keyword)){
			$tempcondition=$tempcondition." AND (A1.nickname LIKE '%{$keyword}%' OR A1.real_name LIKE '%{$keyword}%' OR A1.mobile LIKE '%{$keyword}%')";
		}

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;

		$records = pdo_fetchall("SELECT * FROM ".tablename("chat_users")." A1 ".  $tempcondition." ORDER BY A1.id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$tempArray);
//var_dump(date("Y-m-d",$tempArray[':start']));
		foreach($records as &$temp_record){
			if($temp_record['is_openask']==0)
				$temp_record['is_openask']="未开启";
				else if($temp_record['is_openask']==1)
					$temp_record['is_openask']="已开启";
				else
					$temp_record['is_openask']="被禁用";
		}
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename("chat_users")." A1 " . $tempcondition,$tempArray);
		$pager = pagination($total, $pindex, $psize);
	}

	if($op == 'ciphertext'){
		$keyword=$_GPC['keyword'];
		$tempArray=array();

		if(!empty($keyword)){
			$tempcondition=$tempcondition." AND (A1.company LIKE '%{$keyword}%' OR A1.name LIKE '%{$keyword}%' OR A1.mobile LIKE '%{$keyword}%')";
		}

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		//var_dump($tempcondition);
		$records = pdo_fetchall("SELECT * FROM ".tablename("secretaries")." A1  where is_del=0 ".  $tempcondition." ORDER BY A1.se_id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$tempArray);
		// var_dump($records);exit
		$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename("secretaries")." A1 where is_del=0 " . $tempcondition,$tempArray);
		$pager = pagination($total, $pindex, $psize);
	}

	if($op == 'integral_log'){//积分日志
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;

		$condition = ' where I.uniacid = :uniacid ';
		$where[':uniacid'] = $_W['uniacid'];

		//时间查询
		if($_GPC['status_select'] != '' && $_GPC['status_select'] != 1){
			$condition .= ' and I.'.$_GPC['status_select'].' BETWEEN :start and :end ';

			$where[':start'] = strtotime($start.' 00:00:00');
			$where[':end'] = strtotime($end.' 23:59:59');
		}

		//类型查询
		if($_GPC['difference'] != '' && $_GPC['difference'] != 0){
			$condition .= ' and I.type = :type ';
			$where[':type'] = $_GPC['difference'];
		}

		//关键字
		if(!empty($_GPC['keyword'])){
			$condition .= " and ( U.nickname LIKE '%{$_GPC['keyword']}%' OR U.real_name LIKE '%{$_GPC['keyword']}%' ) ";
		}

		$integralLog = pdo_fetchall(' select I.uid,I.integral,I.type,I.create_time,I.type_id,U.avatar,U.real_name,U.nickname from ' . tablename('chat_users_integral_log') . ' I left JOIN ' . tablename('chat_users') . ' U ON U.id = I.uid ' . $condition . ' ORDER BY I.create_time desc LIMIT '. ($pindex - 1) * $psize . ',' . $psize,$where);

		foreach ($integralLog as $key => $val) {
			if($val['type'] == 6 || $val['type'] == 14){//评论文章或问题
				//评论表
				$comment = pdo_get('chat_article_ask_comment',array('uniacid'=>$_W['uniacid'],'aaid'=>$val['type_id']),array('type_id'));
				if($val['type'] == 6){
					$type = 1;
				}elseif($val['type'] == 14){
					$type = 2;
				}
				//精选表
				$integralLog[$key]['data'] = pdo_get('chat_index_record',array('reid'=>$comment['type_id'],'uniacid'=>$_W['uniacid'],'type'=>$type));
			}
			if($val['type'] == 7 || $val['type'] == 11 || $val['type'] == 15){//查看付费文章或文章精选或查看付费问答
				$recordData = array(
					'reid'=>$val['type_id'],
					'uniacid'=>$_W['uniacid'],
				);
				if($val['type'] == 7 || $val['type'] == 11){
					$recordData['type'] = 1;
				}elseif($val['type'] == 15){
					$recordData['type'] = 2;
				}
				//精选表
				$integralLog[$key]['data'] = pdo_get('chat_index_record',$recordData);
			}
			if($val['type'] == 8 || $val['type'] == 12){//发布悬赏或悬赏回答被采纳
				//问题表
				$integralLog[$key]['data'] = pdo_get('chat_ask',array('id'=>$val['type_id'],'uniacid'=>$_W['uniacid'],'ask_type'=>2));
			}
		}
		// var_dump($integralLog);

		$total = pdo_fetch(' select count(*) as num from ' . tablename('chat_users_integral_log') . ' I left JOIN ' . tablename('chat_users') . ' U ON U.id = I.uid ' . $condition,$where);

		$integralTotal = pdo_fetch(' select sum(I.integral) as fraction from ' . tablename('chat_users_integral_log') . ' I left JOIN ' . tablename('chat_users') . ' U ON U.id = I.uid ' . $condition,$where);

		$pager = pagination($total['num'], $pindex, $psize);
	}

	if($op == 'partnerList' || $op == 'edit_partnerList'){//合伙人管理
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;

		$condition = ' where uniacid = :uniacid and is_partner = 1';
		$where[':uniacid'] = $_W['uniacid'];

		if($_POST){
			if(!empty($_GPC['keyword'])){
				if($op == 'edit_partnerList'){
					$searchCondition = " where uniacid = :uniacid and ( nickname LIKE '%{$_GPC['keyword']}%' OR real_name LIKE '%{$_GPC['keyword']}%' OR mobile LIKE '%{$_GPC['keyword']}%' ) ";
				}else{
					$searchCondition = " where uniacid = :uniacid and is_partner = 0 and is_salesman = 0 and ( nickname LIKE '%{$_GPC['keyword']}%' OR real_name LIKE '%{$_GPC['keyword']}%' OR mobile LIKE '%{$_GPC['keyword']}%' ) ";
				}

				$SearchData[':uniacid'] = $_W['uniacid'];
				$userSearchData = pdo_fetchall(' select * from '.tablename('chat_users').$searchCondition,$SearchData);

				echo json_encode(array('code'=>200,'data'=>$userSearchData));exit;
			}
		}

		$user = pdo_fetchall(' select * from ' . tablename('chat_users') . $condition . ' ORDER BY create_time desc LIMIT '. ($pindex - 1) * $psize . ',' . $psize,$where);

		$total = pdo_fetch(' select count(*) as num from '.tablename('chat_users').$condition,$where);

		$pager = pagination($total['num'], $pindex, $psize);
	}

	//创建成为合伙人身份
	if($op == 'partnerUpdate'){
		if(!empty($_GPC['uid'])){
			$user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['uid']));
			if(empty($user)){
				echo json_encode(array('code'=>400,'msg'=>'用户不存在'));exit;
			}
			//是否已存在邀请码
			if(!empty($user['invitation_code'])){
				$invitationCode = $user['invitation_code'];
			}else{
				$invitationCode = invitationCode();//邀请码
				$user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'invitation_code'=>$invitationCode));
				if($user || $invitationCode == 999999){
					$invitationCode = invitationCode();
				}
			}

			//同时创建自己为运营总监和业务经理 添加上下级关系
			$partnerData = array('is_partner'=>1,'is_boss'=>1,'is_salesman'=>1,'hhr_id'=>$_GPC['uid'],'invitation_code'=>$invitationCode);
			$partnerUpdate = pdo_update('chat_users',$partnerData,array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['uid']));

			if($partnerUpdate){
				echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
			}else{
				echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
			}
		}
	}

	//是否可以更换城市合伙人
	if($op == 'is_editHhr'){
		$orderCondition = array(
			'uniacid' => $_W['uniacid'],
			'partner_id' => $_GPC['j_uid'],
			'status' => 2
		);
		$orderData = pdo_get('chat_order',$orderCondition);
		if(!empty($orderData)){
			//是否还有进行中的订单
			echo json_encode(array('code'=>100,'msg'=>'还有正在进行中的订单'));exit;
		}else{
			echo json_encode(array('code'=>200,'msg'=>'可以更换'));exit;
		}
	}

	//更换城市合伙人
	if($op == 'eidt_partner'){
		if(!empty($_GPC['uid']) && !empty($_GPC['j_uid'])){
			//把旧的合伙人标识去掉
			$orderCondition = array(
    			'uniacid' => $_W['uniacid'],
    			'partner_id' => $_GPC['j_uid'],
    			'status' => 2
    		);
			$orderData = pdo_get('chat_order',$orderCondition);
			if(!empty($orderData)){
				//是否还有进行中的订单
				echo json_encode(array('code'=>400,'msg'=>'还有正在进行中的订单'));exit;
			}else{
				//去除旧合伙人的标识
        		$salesmanData = array('is_partner'=>0,'is_boss'=>0,'is_salesman'=>0,'hhr_id'=>0);
        		$salesmanUpdate = pdo_update('chat_users',$salesmanData,array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['j_uid']));

        		//更换新的上级合伙人
        		pdo_update('chat_users',array('hhr_id'=>$_GPC['uid']),array('uniacid'=>$_W['uniacid'],'hhr_id'=>$_GPC['j_uid']));

        		//更换旧合伙人下级新的上级合伙人id
        		//是否已存在邀请码
        		$user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['uid']));
				if(!empty($user['invitation_code'])){
					$invitationCode = $user['invitation_code'];
				}else{
					$invitationCode = invitationCode();//邀请码
					$user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'invitation_code'=>$invitationCode));
					if($user || $invitationCode == 999999){
						$invitationCode = invitationCode();
					}
				}

				//同时创建自己为运营总监和业务经理 添加上下级关系
				$partnerData = array('is_partner'=>1,'is_boss'=>1,'is_salesman'=>1,'hhr_id'=>$_GPC['uid'],'invitation_code'=>$invitationCode);
				$partnerUpdate = pdo_update('chat_users',$partnerData,array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['uid']));

        		//更换旧合伙人区域的所属uid
				pdo_update('chat_user_partition',array('uid'=>$_GPC['uid']),array('uniacid'=>$_W['uniacid'],'uid'=>$_GPC['j_uid']));

        		//更换下级区域的上级id
        		pdo_update('chat_user_partition',array('pid'=>$_GPC['uid']),array('uniacid'=>$_W['uniacid'],'pid'=>$_GPC['j_uid']));
        		if($salesmanUpdate && $partnerUpdate){
					echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
				}else{
					echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
				}
			}
		}
	}

	if($op == 'hehuoren'){ //合伙人搜索
		if($_POST){
			if(!empty($_GPC['keyword'])){
				$searchCondition = " where uniacid = :uniacid and is_partner = 1 and ( nickname LIKE '%{$_GPC['keyword']}%' OR real_name LIKE '%{$_GPC['keyword']}%' OR mobile LIKE '%{$_GPC['keyword']}%' ) ";
				$SearchData[':uniacid'] = $_W['uniacid'];
				$userSearchData = pdo_fetchall(' select * from '.tablename('chat_users').$searchCondition,$SearchData);

				if(!empty($userSearchData)){
					echo json_encode(array('code'=>200,'data'=>$userSearchData));exit;
				}else{
					echo json_encode(array('code'=>100,'data'=>'没找到相关用户'));exit;
				}

			}
		}
	}

	if($op == 'salesmanList'){//业务经理管理
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;

		$condition = ' where uniacid = :uniacid and is_salesman in(1,2) ';
		$where[':uniacid'] = $_W['uniacid'];

		if($_POST){
			if(!empty($_GPC['keyword'])){
				$searchCondition = " where uniacid = :uniacid and is_salesman = 0 and ( nickname LIKE '%{$_GPC['keyword']}%' OR real_name LIKE '%{$_GPC['keyword']}%' OR mobile LIKE '%{$_GPC['keyword']}%' ) ";
				$SearchData[':uniacid'] = $_W['uniacid'];
				$userSearchData = pdo_fetchall(' select * from '.tablename('chat_users').$searchCondition,$SearchData);

				echo json_encode(array('code'=>200,'data'=>$userSearchData));exit;
			}
		}

		$user = pdo_fetchall(' select * from ' . tablename('chat_users') . $condition . ' ORDER BY create_time desc LIMIT '. ($pindex - 1) * $psize . ',' . $psize,$where);
		foreach ($user as $key => $value) {
			$puser = pdo_get('chat_users',array('uniacid'=>$uniacid,'id'=>$value['hhr_id']),array('real_name','nickname'));
			$user[$key]['pname'] = $puser['real_name'] ? $puser['real_name'] : $puser['nickname'];
		}

		$total = pdo_fetch(' select count(*) as num from '.tablename('chat_users').$condition,$where);

		$pager = pagination($total['num'], $pindex, $psize);

		$hhr = pdo_getall('chat_users', array('uniacid'=>$uniacid,'is_partner'=>1),'','','create_time desc');
	}

	if($op == 'salesmanUpdate'){//更新业务经理身份
		if(!empty($_GPC['uid'])){
			$pid = $_POST['pid']; //上级合伙人id
			$user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['uid'],'is_salesman'=>0));
			if(empty($user)){
				echo json_encode(array('code'=>400,'msg'=>'用户不存在'));exit;
			}
			//是否已存在邀请码
			if(!empty($user['invitation_code'])){
				$invitationCode = $user['invitation_code'];
			}else{
				$invitationCode = invitationCode();//邀请码
				$user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'invitation_code'=>$invitationCode));
				if($user || $invitationCode == 999999){
					$invitationCode = invitationCode();
				}
			}

			$salesmanData = array('is_salesman'=>1,'invitation_code'=>$invitationCode,'hhr_id'=>$pid);
			$salesmanUpdate = pdo_update('chat_users',$salesmanData,array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['uid']));
			if($salesmanUpdate){
	      		$hhr_qy = pdo_getall('chat_user_partition',array('uniacid'=>$uniacid,'uid'=>$pid),array() , '' , 'uid DESC' );
	      		foreach ($hhr_qy as $key => $value) {
	      			//共用合伙人区域
		      		$add_data = array(
		           		'uniacid'=>$uniacid,
		           		'uid'=>$_GPC['uid'],
				   		'level'=>$value['level'],
				   		'ssq'=>$value['ssq'],
				   		'province'=>$value['province'],
				   		'city'=>$value['city'],
				   		'area'=>$value['area'],
				   		'pid'=>$pid,
				   		'create_time'=>time()
		           	);
		      		$add_result = pdo_insert("chat_user_partition",$add_data);
	      		}

				echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
			}else{
				echo json_encode(array('code'=>400,'msg'=>'操作失败或用户已是业务经理'));exit;
			}
		}else{
			echo json_encode(array('code'=>400,'msg'=>'用户不存在'));exit;
		}
	}

	if($op == 'salesmanDel'){//删除
		if(!empty($_GPC['duid'])){
			if(!empty($_GPC['dtype'])){
				//删除录音员
                if($_GPC['dtype'] == 'tape'){
                	$salesmanData = array('is_weike'=>0);
                	$salesmanUpdate = pdo_update('chat_users',$salesmanData,array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['duid']));
                }
                //删除城市合伙人
                if($_GPC['dtype'] == 'hhr'){
            		$orderCondition = array(
            			'uniacid' => $_W['uniacid'],
            			'partner_id' => $_GPC['duid'],
            			'status' => 2
            		);
					$orderData = pdo_get('chat_order',$orderCondition);
					$quyuData = pdo_get('chat_user_partition',array('uniacid'=>$_W['uniacid'],'uid'=>$_GPC['duid']));
					if(!empty($orderData)){
						//是否还有进行中的订单
						echo json_encode(array('code'=>400,'msg'=>'还有正在进行中的订单'));exit;
					}else if(!empty($quyuData)){
						//是否还有区域
						echo json_encode(array('code'=>400,'msg'=>'还有管理的区域'));exit;
					}else{
						//删除下级标识和自己
	            		$salesmanData = array('is_partner'=>0,'is_boss'=>0,'is_salesman'=>0,'hhr_id'=>0);
	            		$salesmanUpdate = pdo_update('chat_users',$salesmanData,array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['duid']));
	            		pdo_update('chat_users',$salesmanData,array('uniacid'=>$_W['uniacid'],'hhr_id'=>$_GPC['duid']));
						//删除共用区域
						pdo_delete('chat_user_partition',array('uniacid'=>$_W['uniacid'],'pid'=>$_GPC['duid']));
						pdo_delete('chat_user_apply',array('uniacid'=>$_W['uniacid'],'user_id'=>$_GPC['duid'],'is_auditing'=>1));
					}
                }
                //删除运营总监
                if($_GPC['dtype'] == 'yyzj'){
                	$orderCondition = array(
            			'uniacid' => $_W['uniacid'],
            			'boss_id' => $_GPC['duid'],
            			'status' => 2
            		);
					$orderData = pdo_get('chat_order',$orderCondition);
					if(!empty($orderData)){
						//是否还有进行中的订单
						echo json_encode(array('code'=>400,'msg'=>'还有正在进行中的订单'));exit;
					}else{
						//删除标识
	                	$salesmanData = array('is_boss'=>0,'hhr_id'=>0);
						$salesmanUpdate = pdo_update('chat_users',$salesmanData,array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['duid']));
						//删除共用区域
						pdo_delete('chat_user_partition',array('uniacid'=>$_W['uniacid'],'uid'=>$_GPC['duid']));
					}
                }
                //删除业务经理
                if($_GPC['dtype'] == 'ywjl'){
                	$ywjl = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['duid']),array('invitation_code'));
                	if(!empty($ywjl['invitation_code'])){
                		$orderCondition = array(
	            			'uniacid' => $_W['uniacid'],
	            			'invitation_code' => $ywjl['invitation_code']
	            		);
						$orderData = pdo_get('chat_order',$orderCondition);
                	}
					if(!empty($orderData)){
						//是否还有进行中的订单
						echo json_encode(array('code'=>400,'msg'=>'还有正在进行中的订单'));exit;
					}else{
	                	$salesmanData = array('is_salesman'=>0,'hhr_id'=>0);
	                	$salesmanUpdate = pdo_update('chat_users',$salesmanData,array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['duid']));
	                	//删除共用区域
						pdo_delete('chat_user_partition',array('uniacid'=>$_W['uniacid'],'uid'=>$_GPC['duid']));
						pdo_delete('chat_user_apply',array('uniacid'=>$_W['uniacid'],'user_id'=>$_GPC['duid'],'is_auditing'=>1,'difference'=>2));
					}
                }

                //删除区域
            	if($_GPC['dtype'] == 'quyu'){
	            	$quyu = pdo_get('chat_user_partition', array('uniacid'=>$uniacid,'upid'=>$_GPC['duid']));
	            	if($quyu['level'] == 1){
	            		$orderCondition = array(
	            			'uniacid' => $_W['uniacid'],
	            			'province' => $quyu['province']
	            		);
	            	}
	            	if($quyu['level'] == 2){
	            		$orderCondition = array(
	            			'uniacid' => $_W['uniacid'],
	            			'province' => $quyu['province'],
	            			'city' => $quyu['city']
	            		);
	            	}
	            	if($quyu['level'] == 3){
	            		$orderCondition = array(
	            			'uniacid' => $_W['uniacid'],
	            			'province' => $quyu['province'],
	            			'city' => $quyu['city'],
	            			'area' => $quyu['area']
	            		);
	            	}

					$orderData = pdo_get('chat_order',$orderCondition);
					if(!empty($orderData)){
						//是否区域还有进行中的订单
						echo json_encode(array('code'=>400,'msg'=>'该区域还有正在进行中的订单'));exit;
					}else{
						//删除合伙人以及下级共用区域
						$salesmanUpdate = pdo_delete('chat_user_partition',array('uniacid'=>$_W['uniacid'],'upid'=>$_GPC['duid']));

						$orderCondition['pid'] = $quyu['pid'];
						pdo_delete('chat_user_partition',$orderCondition);
					}
	            }
	            //删除管理层
                if($_GPC['dtype'] == 'management_layer'){
                	$salesmanData = array('is_all_areas'=>0);
                	$salesmanUpdate = pdo_update('chat_users',$salesmanData,array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['duid']));
                }
			}

			if($salesmanUpdate){
				echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
			}else{
				echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
			}
		}
	}

   //运营总监管理
   if($op == 'operate'){
		$pindex = max(1, intval($_GPC['page']));
		$psize =20;

		$condition = ' where uniacid = :uniacid and is_boss = 1';
		$where[':uniacid'] = $_W['uniacid'];

		if($_POST){
			if(!empty($_GPC['keyword'])){
				$searchCondition = " where uniacid = :uniacid and is_boss = 0 and ( nickname LIKE '%{$_GPC['keyword']}%' OR real_name LIKE '%{$_GPC['keyword']}%' OR mobile LIKE '%{$_GPC['keyword']}%' ) ";
				$SearchData[':uniacid'] = $_W['uniacid'];
				$userSearchData = pdo_fetchall(' select * from '.tablename('chat_users').$searchCondition,$SearchData);
				echo json_encode(array('code'=>200,'data'=>$userSearchData));exit;
			}
		}

		$user = pdo_fetchall(' select * from ' . tablename('chat_users') . $condition . ' ORDER BY create_time desc LIMIT '. ($pindex - 1) * $psize . ',' . $psize,$where);


		foreach ($user as $key => $value) {
			$puser = pdo_get('chat_users',array('uniacid'=>$uniacid,'id'=>$value['hhr_id']),array('real_name','nickname'));
			$user[$key]['pname'] = $puser['real_name'] ? $puser['real_name'] : $puser['nickname'];
		}

		$total = pdo_fetch(' select count(*) as num from '.tablename('chat_users').$condition,$where);

		$pager = pagination($total['num'], $pindex, $psize);

		$hhr = pdo_getall('chat_users', array('uniacid'=>$uniacid,'is_partner'=>1),'','','create_time desc');
   	}

   	//更新运营总监身份
   	if($op == 'operateUpdate'){
   		$uid = $_GPC['uid']; //用户id
   		$pid = $_GPC['pid']; //合伙人id
      	if(!empty($uid)){
      		$data = array(
      			"is_boss" => 1, //运营总监
      			"hhr_id" => $pid //上级合伙人id
      		);
           	$result = pdo_update("chat_users",$data,array('uniacid'=>$uniacid,'id'=>$uid));
           	if($result){
	      		$hhr_qy = pdo_getall('chat_user_partition',array('uniacid'=>$uniacid,'uid'=>$pid));
	      		foreach ($hhr_qy as $key => $value) {
	      			//共用合伙人区域
		      		$add_data = array(
		           		'uniacid'=>$uniacid,
		           		'uid'=>$uid,
				   		'level'=>$value['level'],
				   		'ssq'=>$value['ssq'],
				   		'province'=>$value['province'],
				   		'city'=>$value['city'],
				   		'area'=>$value['area'],
				   		'pid'=>$pid,
				   		'create_time'=>time()
		           	);
		      		$add_result = pdo_insert("chat_user_partition",$add_data);
	      		}

				echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
			}else{
				echo json_encode(array('code'=>400,'msg'=>'操作失败或用户已是运营总监'));exit;
			}
		}
   	}

   	if($op == 'plat'){//改变平台专家状态
        if($_GPC['id']){
            if($_GPC['is_platform'] == 0){
                $is_platform = 1;
            }elseif($_GPC['is_platform'] == 1){
                $is_platform = 0;
            }
            $user_update = pdo_update('chat_users',array('is_platform'=>$is_platform),array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
            if($user_update){
                echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
            }else{
                echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
            }
        }
    }

   	if($op == 'quyuList'){//区域管理
   		//合伙人是否有下级
   		$is_xiaji = pdo_get('chat_user_partition', array('uniacid'=>$uniacid,'pid'=>$_GPC['uid']));

		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;

		$condition = ' where uniacid = :uniacid and uid = :uid';
		$where[':uniacid'] = $_W['uniacid'];
		$where[':uid'] = $_GPC['uid'];

		if($_POST){
			if(!empty($_GPC['keyword'])){
				$searchCondition = " where uniacid = :uniacid and ( nickname LIKE '%{$_GPC['keyword']}%' OR real_name LIKE '%{$_GPC['keyword']}%' OR mobile LIKE '%{$_GPC['keyword']}%' ) ";
				$SearchData[':uniacid'] = $_W['uniacid'];
				$userSearchData = pdo_fetchall(' select * from '.tablename('chat_users').$searchCondition,$SearchData);

				echo json_encode(array('code'=>200,'data'=>$userSearchData));exit;
			}
		}
		$quyu = pdo_fetchall('select * from ' . tablename('chat_user_partition') . $condition . ' ORDER BY create_time desc LIMIT '. ($pindex - 1) * $psize . ',' . $psize,$where);

		foreach ($quyu as $key => $value) {
			$puser = pdo_get('chat_users',array('uniacid'=>$uniacid,'id'=>$value['pid']));
			$quyu[$key]['pname'] = $puser['real_name'] ? $puser['real_name'] : $puser['nickname'];
		}

		$total = pdo_fetch(' select count(*) as num from '.tablename('chat_user_partition').$condition,$where);

		$pager = pagination($total['num'], $pindex, $psize);

		$hhr = pdo_getall('chat_users', array('uniacid'=>$uniacid,'is_partner'=>1),'','','create_time desc');
		$yyzj = pdo_getall('chat_users', array('uniacid'=>$uniacid,'is_boss'=>1),'','','create_time desc');
	}

	//添加区域
   	if($op == 'add_quyu'){
      	if(!empty($_GPC['uid']) && !empty($_GPC['type']) && !empty($_GPC['level'])){

      		if($_GPC['level'] == 1){
   				$get_data = array('uniacid'=>$_W['uniacid'],'type'=>$_GPC['type'],'level'=>$_GPC['level'],'province'=>$_GPC['province']);
   			}
   			if($_GPC['level'] == 2){
   				$get_data = array('uniacid'=>$_W['uniacid'],'type'=>$_GPC['type'],'level'=>$_GPC['level'],'province'=>$_GPC['province'],'city'=>$_GPC['city']);
   			}
   			if($_GPC['level'] == 3){
   				$get_data = array('uniacid'=>$_W['uniacid'],'type'=>$_GPC['type'],'level'=>$_GPC['level'],'province'=>$_GPC['province'],'city'=>$_GPC['city'],'area'=>$_GPC['area']);
   			}

      		//是否有人选择了相同区域
  			$others = pdo_get('chat_user_partition',$get_data);
  			$get_data['uid'] = $_GPC['uid'];
  			//是否自己选择了相同区域
  			$own = pdo_get('chat_user_partition',$get_data);

  			$add_data = array(
           		'uniacid'=>$uniacid,
		   		'level'=>$_GPC['level'],
		   		'pid'=>$_GPC['uid'],
		   		'create_time'=>time()
           	);

      		//2运营总监 3业务经理
      		if($_GPC['type'] == 1 || $_GPC['type'] == 2 || $_GPC['type'] == 3){
      			if(!empty($own)){
  					echo json_encode(array('code'=>400,'msg'=>'已代理该区域'));exit;
  				}
      		}
      		//1合伙人
      		if($_GPC['type'] == 1){
      			if(!empty($others)){
      				echo json_encode(array('code'=>400,'msg'=>'该区域已被代理'));exit;
      			}
      		}
      		//省代
      		if($_GPC['level'] == 1){
      			$add_data['province'] = $_GPC['province'];
      			$add_data['ssq'] = $_GPC['province'];
      		}
      		//市代
      		if($_GPC['level'] == 2){
      			$add_data['province'] = $_GPC['province'];
      			$add_data['city'] = $_GPC['city'];
      			$add_data['ssq'] = $_GPC['province'].','.$_GPC['city'];
      		}
      		//区代
      		if($_GPC['level'] == 3){
      			$add_data['province'] = $_GPC['province'];
      			$add_data['city'] = $_GPC['city'];
      			$add_data['area'] = $_GPC['area'];
      			$add_data['ssq'] = $_GPC['province'].','.$_GPC['city'].','.$_GPC['area'];
      		}

           	//添加共用区域
           	$users = pdo_getall('chat_users',array('uniacid'=>$_W['uniacid'],'hhr_id'=>$_GPC['uid']),array('id','hhr_id'));
           	if(!empty($users)){
           		foreach ($users as $key => $value) {
           			$add_data['uid'] = $value['id'];
           			$result = pdo_insert("chat_user_partition",$add_data);
           		}
           	}
           	if(!empty($result)){
				echo json_encode(array('code'=>200,'msg'=>'添加成功'));exit;
			}else{
				echo json_encode(array('code'=>400,'msg'=>'添加失败'));exit;
			}
		}
   	}

   	if($op == 'edit_quyu'){//编辑区域
		$condition = ' where uniacid = :uniacid and upid = :upid';
		$where[':uniacid'] = $_W['uniacid'];
		$where[':upid'] = $_GPC['upid'];
		$quyu = pdo_get('chat_user_partition',array('uniacid'=>$_W['uniacid'],'upid'=>$_GPC['upid']));
		$hhr = pdo_getall('chat_users', array('uniacid'=>$uniacid,'is_partner'=>1),'','','create_time desc');
		$yyzj = pdo_getall('chat_users', array('uniacid'=>$uniacid,'is_boss'=>1),'','','create_time desc');

   		if(!empty($_POST['uid'])){
   			$quyu = pdo_get('chat_user_partition',array('uniacid'=>$_W['uniacid'],'upid'=>$_POST['uid']));
   			if($_GPC['level'] == 1){
   				$get_data = array('uniacid'=>$_W['uniacid'],'type'=>$_GPC['type'],'level'=>$_GPC['level'],'province'=>$_GPC['province']);
   			}
   			if($_GPC['level'] == 2){
   				$get_data = array('uniacid'=>$_W['uniacid'],'type'=>$_GPC['type'],'level'=>$_GPC['level'],'province'=>$_GPC['province'],'city'=>$_GPC['city']);
   			}
   			if($_GPC['level'] == 3){
   				$get_data = array('uniacid'=>$_W['uniacid'],'type'=>$_GPC['type'],'level'=>$_GPC['level'],'province'=>$_GPC['province'],'city'=>$_GPC['city'],'area'=>$_GPC['area']);
   			}

      		//是否有人选择了相同区域
  			$others = pdo_get('chat_user_partition',$get_data);
  			$get_data['uid'] = $quyu['uid'];
  			//是否自己选择了相同区域
  			$own = pdo_get('chat_user_partition',$get_data);

  			$edit_data = array(
           		'uniacid'=>$uniacid,
		   		'level'=>$_GPC['level'],
		   		'pid'=>$_GPC['pid'],
		   		'create_time'=>time()
           	);

      		//2运营总监 3业务经理
      		if($_GPC['type'] == 1 || $_GPC['type'] == 2 || $_GPC['type'] == 3){
      			if(!empty($own)){
      				if(!empty($_GPC['pid'])){
      					if($_GPC['pid'] == $quyu['pid']){
      						echo json_encode(array('code'=>400,'msg'=>'已代理该区域'));exit;
      					}
      				}
  				}
      		}
      		//1合伙人
      		if($_GPC['type'] == 1){
      			if(!empty($others)){
      				if(!empty($_GPC['pid'])){
      					if($_GPC['pid'] == $quyu['pid']){
      						echo json_encode(array('code'=>400,'msg'=>'该区域已被代理'));exit;
      					}
      				}
      			}
      		}
      		//省代
      		if($_GPC['level'] == 1){
      			$edit_data['province'] = $_GPC['province'];
      			$edit_data['ssq'] = $_GPC['province'];
      		}
      		//市代
      		if($_GPC['level'] == 2){
      			$edit_data['province'] = $_GPC['province'];
      			$edit_data['city'] = $_GPC['city'];
      			$edit_data['ssq'] = $_GPC['province'].','.$_GPC['city'];
      		}
      		//区代
      		if($_GPC['level'] == 3){
      			$edit_data['province'] = $_GPC['province'];
      			$edit_data['city'] = $_GPC['city'];
      			$edit_data['area'] = $_GPC['area'];
      			$edit_data['ssq'] = $_GPC['province'].','.$_GPC['city'].','.$_GPC['area'];
      		}

			$tapeUpdate = pdo_update('chat_user_partition',$edit_data,array('uniacid'=>$uniacid,'upid'=>$_POST['uid']));
   			if($tapeUpdate){
				echo json_encode(array('code'=>200,'msg'=>'修改成功'));exit;
			}else{
				echo json_encode(array('code'=>200,'msg'=>'修改失败'));exit;
			}
		}
	}

   	//录音员管理
   	if($op == 'tape'){
   		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;

   		$condition = ' where uniacid = :uniacid and is_weike = 1 ';
   		$where[':uniacid'] = $_W['uniacid'];

   		if($_POST){
			if(!empty($_GPC['keyword'])){
				$TapeCondition = " where uniacid = :uniacid and is_weike = 0 and ( nickname LIKE '%{$_GPC['keyword']}%' OR real_name LIKE '%{$_GPC['keyword']}%' OR mobile LIKE '%{$_GPC['keyword']}%' ) ";
				$TapeData[':uniacid'] = $_W['uniacid'];
				$userTapeData = pdo_fetchall(' select * from '.tablename('chat_users').$TapeCondition,$TapeData);

				echo json_encode(array('code'=>200,'data'=>$userTapeData));exit;
			}
		}

		$user = pdo_fetchall(' select * from ' . tablename('chat_users') . $condition . ' ORDER BY create_time desc LIMIT '. ($pindex - 1) * $psize . ',' . $psize,$where);

		$total = pdo_fetch(' select count(*) as num from '.tablename('chat_users').$condition,$where);

		$pager = pagination($total['num'], $pindex, $psize);

   	}

   	//更新录音员身份
   	if($op == 'tapeUpdate'){
   		if(!empty($_GPC['uid'])){
   			$tapeUpdate = pdo_update('chat_users',array('is_weike'=>1),array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['uid']));
   			if($tapeUpdate){
				echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
			}else{
				echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
			}
   		}
   	}

   	//指定推荐
	if($op == 'appointRecommend'){
		if(!empty($_GPC['uid']) && !empty($_GPC['user_id'])){
			if($_GPC['uid'] == $_GPC['user_id']){
				echo json_encode(array('code'=>400,'msg'=>'不能指定自己为推荐人'));exit;
			}
			$recommendUpdate = pdo_update('chat_users',array('superior_id'=>'u'.$_GPC['uid']),array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['user_id']));
			if($recommendUpdate){
				echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
			}else{
				echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
			}
		}
	}
}

//邀请列表
if($op == 'invitationList'){
	if(!empty($_GPC['id'])){
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;

		$condition = ' where uniacid = :uniacid and superior_id = :superior_id ';
		$where[':uniacid'] = $_W['uniacid'];
		$where[':superior_id'] = 'u'.$_GPC['id'];

		//时间查询
		if($_GPC['status_select'] != '' && $_GPC['status_select'] != 1){
			$condition .=  ' AND '.$_GPC['status_select'].' BETWEEN :start and :end';
            $where[':start'] = strtotime($_GPC['time']['start'].' 00:00:00');
            $where[':end'] = strtotime($_GPC['time']['end'].' 23:59:59');
		}

		//性别查询
		if($_GPC['sex'] != '' && $_GPC['sex'] != 3){
			$condition .= ' and sex = '.$_GPC['sex'];
		}

		//身份类型
		if(!empty($_GPC['type'])){
			if($_GPC['type'] == 'expert'){//专家
				$condition .= ' and vstatus = 1 ';
			}elseif($_GPC['type'] == 'platformExpert'){//平台专家
				$condition .= ' and is_platform = 1 ';
			}elseif($_GPC['type'] == 'salesman'){//业务经理
				$condition .= ' and is_salesman = 1 ';
			}elseif($_GPC['type'] == 'partnerSalesman'){//城市合伙人
				$condition .= ' and is_salesman = 2 ';
			}elseif($_GPC['type'] == 'partner'){//运营合伙人
				$condition .= ' and is_partner = 1 ';
			}elseif($_GPC['type'] == 'operate'){//运营总监
				$condition .= ' and is_boss = 1 ';
			}
		}

		//搜索关键词
		if(!empty($_GPC['keyword'])){
			$condition .= " AND (nickname LIKE '%{$_GPC['keyword']}%' OR real_name LIKE '%{$_GPC['keyword']}%' OR mobile LIKE '%{$_GPC['keyword']}%')";
		}

		$users = pdo_fetchall(' select * from '.tablename('chat_users').$condition." ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$where);
		$total = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').$condition,$where);
		$pager = pagination($total, $pindex, $psize);
	}
}

//生成邀请码
function invitationCode(){
	for ($i=0; $i < 6; $i++) {
		$invitationCode[$i] = rand(0,99);
	}
	$invitationCode = implode('',$invitationCode);
	if(strlen($invitationCode) > 6){
		$invitationCode = substr($invitationCode,0,6);
	}
	return $invitationCode;
}
//生成二维码
if($op=='userCode'){
	$uid =$_GPC['userId'];

    //获取用户信息
    $user = pdo_fetch('select nickname,avatar,real_name,level_id,is_salesman,is_partner,vstatus,is_boss from ' . tablename('chat_users') . ' where id=' . $uid);
    //业务员生成永久二维码
    if($user['is_salesman'] != 0 || $user['is_partner']==1 || $user['vstatus']==1 || $user['is_boss']==1){
        if($_W['siteroot'] == 'https://demo.jssell.com/'){
            $qrcode = getQrCode($uid,1,1);
        }else{
            $qrcode_data = pdo_fetch('SELECT * FROM ' . tablename('qrcode') . ' WHERE uniacid = :uniacid AND acid = :acid AND scene_str = :scene_str AND model = 2', array(':uniacid' => $_W['uniacid'], ':acid' => $_W['acid'], ':scene_str' =>'u'.$uid));

            if(!empty($qrcode_data)){
                $qrcode = array('code'=>200,'data'=>$qrcode_data);
            }else{
                $qrcode = getQrCode($uid,1,2);
            }
        }
    }else{
        //不是业务员生成临时二维码
        if($user['is_salesman'] == 0 && $user['is_partner']!=1 && $user['vstatus']!=1 && $user['is_boss']!=1){
            //获取推广二维码
            $qrcode = getQrCode($uid,1,1);
        }
    }

    if(!empty($qrcode) && $qrcode['code'] == 200){
        $res = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . $qrcode['data']['ticket'];
        $data = array(
            'code' => 200,
            'data' => $res
        );
        echo json_encode($data);
        exit;
    }else{
    	$data = array(
            'code' => 100,
            'message' => $qrcode['msg'],
            'data'=>$qrcode
        );
        echo json_encode($data);
        exit;
    }
}

//管理层管理
if($op == 'management_layer'){
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;

	$condition = ' where uniacid = :uniacid and is_all_areas = 1';
	$where[':uniacid'] = $_W['uniacid'];

	if($_POST){
		if(!empty($_GPC['keyword'])){
			$searchCondition = " where uniacid = :uniacid and is_all_areas = 0 and ( nickname LIKE '%{$_GPC['keyword']}%' OR real_name LIKE '%{$_GPC['keyword']}%' OR mobile LIKE '%{$_GPC['keyword']}%' ) ";
			$SearchData[':uniacid'] = $_W['uniacid'];
			$userSearchData = pdo_fetchall(' select * from '.tablename('chat_users').$searchCondition,$SearchData);

			echo json_encode(array('code'=>200,'data'=>$userSearchData));exit;
		}
	}

	$user = pdo_fetchall(' select * from ' . tablename('chat_users') . $condition . ' ORDER BY create_time desc LIMIT '. ($pindex - 1) * $psize . ',' . $psize,$where);

	$total = pdo_fetch(' select count(*) as num from '.tablename('chat_users').$condition,$where);

	$pager = pagination($total['num'], $pindex, $psize);
}

//创建成为管理层身份
if($op == 'management_layerUpdate'){
	if(!empty($_GPC['uid'])){
		$user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['uid']));
		if(empty($user)){
			echo json_encode(array('code'=>400,'msg'=>'用户不存在'));exit;
		}

		$partnerData = array('is_all_areas'=>1);
		$partnerUpdate = pdo_update('chat_users',$partnerData,array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['uid']));

		if($partnerUpdate){
			echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
		}else{
			echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
		}
	}
}

include $this->template('ask_users');
