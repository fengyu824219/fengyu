<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$operation =empty($_GPC['op']) ? "display" :$_GPC['op'];

//提现列表
if($operation == 'display'){
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;

	$condition = " where s.uniacid = :uniacid ";
	$where[':uniacid'] = $_W['uniacid'];

	//模糊查询
	if($_GPC['status'] != ''){
		$condition .=" and s.status = :status ";
		$where[':status'] = $_GPC['status'];
	}
	if($_GPC['status_select'] != ''){
		if($_GPC['status_select'] == 1){
			if($_GPC['time']){
                $condition .=  ' AND s.create_time'.' BETWEEN :start and :end';
                $where[':start'] = strtotime($_GPC['time']['start'].' 00:00:00');
                $where[':end'] = strtotime($_GPC['time']['end'].' 23:59:59');
            }
		}
	}

	$ask_summary_last = pdo_fetchall('select s.id,s.uid,s.pay_amount,tx_type,s.last_amount,s.create_time,s.status,s.order_sn,s.remakes,u.avatar,u.real_name,u.nickname,u.user_title from'.tablename('chat_ask_summary_last').' s INNER join '.tablename('chat_users').' u ON s.uid = u.id '.$condition." ORDER BY s.create_time LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$where);

	foreach ($ask_summary_last as $key => $value) {
		if($value['tx_type'] == 1){
			$tx_type = '微信钱包';
		}
		if($value['tx_type'] == 2){
			$tx_type = '银行卡';
		}
		$ask_summary_last[$key]['tx_type'] = $tx_type;
	}

	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' .tablename('chat_ask_summary_last').' s INNER join '.tablename('chat_users').' u ON s.uid = u.id '.$condition,$where);

	$price = pdo_fetchcolumn('SELECT SUM(pay_amount) FROM ' .tablename('chat_ask_summary_last').' s INNER join '.tablename('chat_users').' u ON s.uid = u.id '.$condition,$where);
	if(empty($price)){
		$price = 0;
	}

	$pager = pagination($total, $pindex, $psize);
}

//提现列表
if($operation == 'ask_group'){
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;

	$condition = " where s.uniacid = :uniacid";
	$where[':uniacid'] = $_W['uniacid'];

	//模糊查询
	if($_GPC['status'] != ''){
		$condition .=" and s.status = :status ";
		$where[':status'] = $_GPC['status'];
	}
	if($_GPC['status_select'] != ''){
		if($_GPC['status_select'] == 1){
			if($_GPC['time']){
                $condition .=  ' AND s.create_time'.' BETWEEN :start and :end';
                $where[':start'] = strtotime($_GPC['time']['start'].' 00:00:00');
                $where[':end'] = strtotime($_GPC['time']['end'].' 23:59:59');
            }
		}
	}

	$ask_summary_last = pdo_fetchall('select s.*,u.avatar,u.real_name,u.nickname,u.user_title,o.order_sn from'.tablename('chat_expert_paylog').' s INNER join '.tablename('chat_users').' u ON s.uid = u.id left join ' . tablename('chat_order').' o on s.oid=o.id '.$condition." ORDER BY s.create_time LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$where);

	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' .tablename('chat_expert_paylog').' s INNER join '.tablename('chat_users').' u ON s.uid = u.id '.$condition,$where);

	$price = pdo_fetchcolumn('SELECT  SUM(price) FROM ' .tablename('chat_expert_paylog').' s INNER join '.tablename('chat_users').' u ON s.uid = u.id '.$condition,$where);
	if(empty($price)){
		$price = 0;
	}

	$pager = pagination($total, $pindex, $psize);
}


//提现处理
if($operation == 'handle'){
	$tx_data = pdo_get('chat_ask_summary_last',array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid'])); //提现信息
	if($tx_data['status'] == 1){
		$status = '处理中';
	}
	if($tx_data['status'] == 2){
		$status = '已打款';
	}
	if($tx_data['status'] == 3){
		$status = '已拒绝';
	}
	if($tx_data['is_ptdj'] == 1){
		$is_ptdj = '是';
	}
	if($tx_data['is_ptdj'] == 0){
		$is_ptdj = '否';
	}
	if($tx_data['tx_type'] == 1){
		$tx_type = '微信钱包';
	}
	if($tx_data['tx_type'] == 2){
		$tx_type = '银行卡';
	}
	$tx_data['is_ptdj'] = $is_ptdj;
	$tx_data['tx_type'] = $tx_type;
	$tx_data['status'] = $status;

    $money = $tx_data['last_amount'];
    if(!empty($money)){
    	$pay_money = number_format($money,2,'.','');
    }

	if($_GPC['id']){
		if($_GPC['uid']){
			$user = pdo_get('chat_users',array('id'=>$_GPC['uid'],'uniacid'=>$_W['uniacid']));//用户
		}

		if(checksubmit('submit')){
			//提现方式
			if($tx_data['tx_type'] == '银行卡'){
				//同意
				if($_GPC['status'] == 2){
					$summary_data = array(
						'last_amount' => $_GPC['last_amount'],
						'admin_id' => $_W['uid'],
						'status' => $_GPC['status'],
						'remakes' => $_GPC['remakes'],
						'pay_time' => time()
					);

					//同意提现
					$is_pay = $this->pay_bank($user['openid'],$tx_data['xhkh'],$tx_data['ckr'],$tx_data['ssyh'],$_GPC['last_amount']);
				}
				//拒绝
				if($_GPC['status'] == 3){
					$summary_data = array(
						'admin_id' => $_W['uid'],
						'status' => $_GPC['status'],
						'remakes' => $_GPC['remakes']
					);
					//返回收益
					$balance = round($user['user_profit'] + $_GPC['pay_amount'],2);
					$user_update = pdo_update('chat_users',array('user_profit'=>$balance),array('id'=>$user['id'],'uniacid'=>$_W['uniacid']));
				}

			}

			if($tx_data['tx_type'] == '微信钱包'){
				if($_GPC['status'] == 2){
					$summary_data = array(
						'last_amount' => $_GPC['last_amount'],
						'admin_id' => $_W['uid'],
						'status' => $_GPC['status'],
						'remakes' => $_GPC['remakes'],
						'pay_time' => time()
					);

					//同意提现
					$is_pay=$this->pay_cash($user['openid'],$_GPC['last_amount']);
				}elseif($_GPC['status'] == 3){
					$summary_data = array(
						'admin_id' => $_W['uid'],
						'status' => $_GPC['status'],
						'remakes' => $_GPC['remakes'],
					);
					$balance = round($user['user_profit'] + $_GPC['pay_amount'],2);
					$user_update = pdo_update('chat_users',array('user_profit'=>$balance),array('id'=>$user['id'],'uniacid'=>$_W['uniacid']));
				}
			}

			if(!empty($is_pay)){
				if($is_pay['errno']==-1 || $is_pay['errno']==-2){
	               itoast($is_pay['message'],"",error);
				}elseif($is_pay['errno']==0 && $is_pay['error']=='success'){
	                $datPayArr = [
	                    "uniacid"=>$_W['uniacid'],
	                    "pay_type"=>'wx',
	                    "type"=>8,
	                    "type_id"=>$_GPC['id'],
	                    "explain"=>"收益提现",
	                    "create_time"=>time(),
	                    "pay_time"=>time(),
	                    "uid"=>$_GPC['uid'],
	                    "price"=>$_GPC['last_amount'],
	                    "price_type"=>3,
	                    "pay_status"=>2,
	                    "pay_content"=>"收益提现"
	                ];
	                pdo_insert("chat_users_pay_log",$datPayArr);
				}else{
					itoast($is_pay['message'] ? $is_pay['message'] : "系统未知错误","",error);
				}
			}

			$summary_update = pdo_update('chat_ask_summary_last',$summary_data,array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
			if($summary_update){
				//发送模版消息
				$weObj = WeAccount::create($_W['uniacid']);
				if($_GPC['status'] == 2){
					$last_amount = $_GPC['last_amount'];
					$shjg = '通过';
					$remark = '提现金额将在1-15个工作日内到账，如有疑问，请咨询客服（跳提现明细）';
				}
				if($_GPC['status'] == 3){
					$last_amount = $tx_data['pay_amount'];
					$shjg = '不通过';
					$remark = '请修改提现信息并重新提交，如有疑问，请咨询客服（跳提现）';
				}
	            $send_data = array(
	                'first'=>array('value'=>'您好，您的提现申请已处理'),
	                'keyword1'=>array('value'=>'￥'.$last_amount),
	                'keyword2'=>array('value'=>$tx_type),
	                'keyword3'=>array('value'=>date('Y-m-d H:i:s',$tx_data['create_time'])),
	                'keyword4'=>array('value'=>$shjg),
	                'keyword5'=>array('value'=>date('Y-m-d H:i:s',time())),
	                'remark'=>array('value'=>$remark)
	            );
	            $url = $_W['siteroot'] .'app/'. $this->createMobileUrl('view_recharge',array('op'=>'forward','from'=>'template'));
	            $weObj->sendTplNotice($user['openid'], 'Rv1B0_4ot6Lh4GRJ9-syaKHcmj96RdaKy6Hs0u6ABE0', $send_data, $url);

				itoast('操作成功',$this->createWebUrl('ask_payment',array('op'=>'display','eid'=>$_GPC['eid'],'version_id'=>0)),success);
			}else{
				itoast('操作失败',"",error);
			}
		}

	}
}
//提现处理
if($operation == 'group_handle'){

	if($_GPC['grid']){
        $status = $_GPC['status'];
		 $res = pdo_update("chat_expert_paylog",["status"=> $status,"content"=>$_GPC["content"],"admin_uid"=>$_W['user']['uid']],["epid"=>$_GPC['grid']]);
		if($res){
			itoast('操作成功',$this->createWebUrl('ask_payment',array('op'=>'ask_group','eid'=>$_GPC['eid'],'version_id'=>0)),success);
		}else{
			itoast('操作失败',"",error);
		}
	}else{
		itoast('系统错误，请清除缓存后在操作',"",error);
	}
}


if($operation == 'recharge_manage'){//转账列表
	$starttime = empty($_GPC['time']['start']) ? TIMESTAMP -  86399 * 30 : strtotime($_GPC['time']['start']);
	$endtime = empty($_GPC['time']['end']) ? TIMESTAMP + 86400: strtotime($_GPC['time']['end']);

	$tempcondition=" where A1.uniacid=".$_W['uniacid']." and ((A1.pay_status = 2 and A1.type = 'wx') or A1.type = 'yh') ";

	$tempArray=array();
	//时间查询
	if($_GPC['timeType'] != '' && $_GPC['timeType'] != 10){
		$tempcondition .= ' AND A1.'.$_GPC['timeType'].' between :start and :end ';
		$tempArray[':start'] = strtotime($_GPC['time']['start'].' 00:00:00 ');
		$tempArray[':end'] = strtotime($_GPC['time']['end'].' 23:59:59 ');
	}

	//支付类型查询
	if(!empty($_GPC['type'])){
		$tempcondition = $tempcondition." AND A1.type = :type ";
		$tempArray[':type'] = $_GPC['type'];
	}

	//状态查询
	$_GPC['pay_status'] = empty($_GPC['pay_status']) ? 2 : $_GPC['pay_status'];
	if(!empty($_GPC['pay_status']) && $_GPC['pay_status'] != 10){
		$tempcondition = $tempcondition." AND A1.pay_status = :pay_status ";
		$tempArray[':pay_status'] = $_GPC['pay_status'];
	}

	//关键词
	$keyword=$_GPC['keyword'];
	if(!empty($keyword)){
		$tempcondition = $tempcondition." AND (A2.nickname LIKE '%{$keyword}%' or A2.real_name LIKE '%{$keyword}%' or A1.out_trade_no LIKE '%{$keyword}%') ";
	}

	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;
	$total = pdo_fetchcolumn("SELECT COUNT(0) FROM ".tablename("chat_recharge")." A1 LEFT JOIN ".tablename("chat_users")." A2 ON
	A1.uid=A2.id ".$tempcondition,$tempArray);

	$records = pdo_fetchall("SELECT A1.*,A2.nickname,A2.real_name,A2.user_title,A2.avatar FROM ".tablename("chat_recharge")." A1 LEFT JOIN ".tablename("chat_users")." A2 ON
	A1.uid=A2.id ".$tempcondition." ORDER BY A1.id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$tempArray);

	$price = pdo_fetch("SELECT SUM(money) as money FROM ".tablename("chat_recharge")." A1 LEFT JOIN ".tablename("chat_users")." A2 ON
	A1.uid=A2.id ".$tempcondition,$tempArray);
	if(empty($price['money'])){
		$price['money'] = 0;
	}

	$pager = pagination($total, $pindex, $psize);
}

if($operation == 'check_recharge'){//转账处理

    $checkre = pdo_get('chat_recharge',array('id'=>$_GPC['id']));

    if(checksubmit('submit'))
    {
    	$user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'openid'=>$_GPC['openid']));
    	if($_GPC['pay_status'] == 2){//通过状态
    		$balance = $user['balance'] + $_GPC['money'];
    		$money = pdo_update('chat_users',array('balance'=>$balance),array('openid'=>$_GPC['openid'],'uniacid'=>$_W['uniacid']));
    		if($money){
    			$rec_data = array(
					'pay_status' => $_GPC['pay_status'],
					'remarks' => $_GPC['remarks'],
					'admin_uid' => $_W['user']['uid'],
					'admin_username' => $_W['user']['username']

				);
    			$status = pdo_update('chat_recharge',$rec_data,array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
    			pdo_update("chat_users_pay_log",["pay_status"=>2],["out_trade_no"=>$checkre['out_trade_no'],"uid"=>$checkre['uid']]);
    			if($status){
    				$this->add_integral($user['id'],4,$_GPC['id'],$_GPC['money']);
    			}
    		}
    	}else if($_GPC['pay_status'] == 3){//不通过状态
    		$rec_data = array(
				'pay_status' => $_GPC['pay_status'],
				'remarks' => $_GPC['remarks'],
				'admin_uid' => $_W['user']['uid'],
				'admin_username' => $_W['user']['username']

			);
			$status = pdo_update('chat_recharge',$rec_data,array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
    	}

    	if ($status) {
    		if($_GPC['pay_status'] == 2){
	            $weObj = WeAccount::create($_W['uniacid']);
	            $send_data = array(
	                'first'=>array('value'=>'您好，已核对您的打款。'),
	                'keyword1'=>array('value'=>$user['real_name']),
	                'keyword2'=>array('value'=>$_GPC['money']),
	                'keyword3'=>array('value'=>date('Y年m月d日 H:i',time())),
	                'remark'=>array('value'=>'您可查询您的账户余额，如有疑惑，请咨询客服。'),
	            );

	            $url = 'https://'.$_SERVER['HTTP_HOST'].'/app/index.php?i='.$_GPC['i'].'&c=entry&op=recharge&do=my_chat&m=dg_ask';

	            $weObj->sendTplNotice($user['openid'], 'oBlw1FvjBKdwC7zTQ2SHPUnZhGw2tWj-1b68mO02wVU', $send_data, $url, '#173177');

    		}else if($_GPC['pay_status'] == 3){
	            $weObj = WeAccount::create($_W['uniacid']);
	            $send_data = array(
	                'first'=>array('value'=>'您好，经核查未发现您的转账记录，该笔充值已被拒绝，请重新充值。'),
	                'keyword1'=>array('value'=>$user['real_name']),
	                'keyword2'=>array('value'=>$_GPC['money']),
	                'keyword3'=>array('value'=>date('Y年m月d日 H:i',time())),
	                'remark'=>array('value'=>$_GPC['remarks']),
	            );

	            $url = 'https://'.$_SERVER['HTTP_HOST'].'/app/index.php?i='.$_GPC['i'].'&c=entry&op=recharge&do=my_chat&m=dg_ask';

	            $weObj->sendTplNotice($user['openid'], 'oBlw1FvjBKdwC7zTQ2SHPUnZhGw2tWj-1b68mO02wVU', $send_data, $url, '#173177');
    		}

    		itoast('操作成功！',$this->createWebUrl('ask_payment',array('op'=> 'recharge_manage','version_id'=>0)),success);
    	}else{
    		itoast('操作失败！',$this->createWebUrl('ask_payment',array('op'=> 'recharge_manage','version_id'=>0)),error);
    	}
    }
}

if($operation == 'zs_manage'){//赞赏记录
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;

	$condition = " where r.uniacid = :uniacid and status = 1 ";
	$where[':uniacid'] = $_W['uniacid'];

	//时间查询
	if($_GPC['status_select'] != ''){
		$condition .= ' and r.'.$_GPC['status_select'].' BETWEEN :start and :end ';
		$where[':start'] = strtotime($_GPC['time']['start'].'00:00:00');
		$where[':end'] = strtotime($_GPC['time']['end'].'23:59:59');
	}

	//支付类型查询
	if(!empty($_GPC['pay_type'])){
		$condition .= ' and pay_type = :pay_type ';
		$where[':pay_type'] = $_GPC['pay_type'];
	}

	//订单号查询
	if($_GPC['keyword'] != ''){
		$condition .= " and out_trade_no LIKE '%{$_GPC['keyword']}%' ";
	}

	$reward = pdo_fetchall('select r.*,u.real_name,u.nickname,u.avatar from'.tablename('chat_ask_reward').' r left join '.tablename('chat_users').' u on u.id = r.uid '.$condition." ORDER BY r.create_time DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$where);

	foreach ($reward as $key => $val) {//被赞赏人信息
		$reward[$key]['be_admired'] = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$val['to_uid']),array('real_name','avatar','nickname'));
	}

	$total = pdo_fetch('select count(*) as num from'.tablename('chat_ask_reward').' r left join '.tablename('chat_users').' u on u.id = r.uid '.$condition,$where);

	$price = pdo_fetch('select SUM(money) as money from'.tablename('chat_ask_reward').' r left join '.tablename('chat_users').' u on u.id = r.uid '.$condition,$where);
	if(empty($price)){
		$price['money'] = 0;
	}

	$pager = pagination($total['num'], $pindex, $psize);

}

if($operation == 'art_ask'){//支付文章记录
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;
	$condition = ' where uniacid = :uniacid and pay_sta = 2 ';
	$where[':uniacid'] = $_W['uniacid'];

	//时间查询
	if($_GPC['status_select'] != ''){
		$condition .= ' and '.$_GPC['status_select'].' BETWEEN :start and :end ';
		$where[':start'] = strtotime($_GPC['time']['start'].'00:00:00');
		$where[':end'] = strtotime($_GPC['time']['end'].'23:59:59');
	}

	//支付类型查询
	if(!empty($_GPC['type'])){
		$condition .= ' and type = :type ';
		$where[':type'] = $_GPC['type'];
	}

	//支付类型查询
	if(!empty($_GPC['pay_type'])){
		$condition .= ' and pay_type = :pay_type ';
		$where[':pay_type'] = $_GPC['pay_type'];
	}

	//关键字查询
	if(!empty($_GPC['keyword'])){
		$condition .= " and (order_sn LIKE '%{$_GPC['keyword']}%' OR title LIKE '%{$_GPC['keyword']}%' OR nickname LIKE '%{$_GPC['keyword']}%' ) ";
	}

	$total = pdo_fetchcolumn('select count(*) from'.tablename('chat_reflect_log').$condition,$where);

	$reflect = pdo_fetchall('select * from'.tablename('chat_reflect_log').$condition." ORDER BY create_time DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$where);

	$price = pdo_fetch('select sum(money) as money from'.tablename('chat_reflect_log').$condition,$where);
	$price['money'] = $price['money'] / 100;
	if(empty($price)){
		$price['money'] = 0;
	}

	$pager = pagination($total, $pindex, $psize);
}

//代理提现列表
if($operation == 'agent_put_forward_list'){
    // 前台加载模型数据
    $pindex = max(1, intval($_GPC['page']));
    $psize = 20;
    $condition = " where f.uniacid = :uniacid ";
    $where[':uniacid'] = $_W['uniacid'];

    //时间查询
    if($_GPC['status_select'] != '' && $_GPC['status_select'] != 1){
        $condition .=  ' AND f.'.$_GPC['status_select'].' BETWEEN :start and :end';
        $where[':start'] = strtotime($_GPC['time']['start'].' 00:00:00');
        $where[':end'] = strtotime($_GPC['time']['end'].' 23:59:59');
    }

    //状态查询
    if($_GPC['status'] != '' && $_GPC['status'] != 10){
        $condition .= " AND f.status =:status";
        $where[':status'] = $_GPC['status'];
    }

    //关键字查询
    if($_GPC['content'] != ''){
        $condition .= " AND (a.name LIKE '%{$_GPC['content']}%' or a.mobile LIKE '%{$_GPC['content']}%' or f.order_sn LIKE '%{$_GPC['content']}%')";
    }

    $total=pdo_fetch('select count(*) as num from'.tablename('chat_agent_put_forward').' f INNER join '.tablename('chat_agent').' a ON a.agid = f.parent_id '.$condition,$where);
    $ap_forward = pdo_fetchall(' select f.*,a.name,a.mobile from '.tablename('chat_agent_put_forward').' f INNER join '.tablename('chat_agent').' a ON a.agid = f.parent_id '.$condition.' order by f.create_time desc limit '.($pindex - 1) * $psize .',' .$psize.'',$where);

    $pager = pagination($total['num'], $pindex, $psize);
}

//更改代理提现状态
if($operation == 'set_apf_status'){
    if($_GPC['apfid']){
        $apf_data = pdo_get('chat_agent_put_forward',array('uniacid'=>$_W['uniacid'],'apfid'=>$_GPC['apfid']));
    }
    if($_POST){
        $apf_update = pdo_update('chat_agent_put_forward',array('status'=>$_GPC['status'],'remarks'=>$_GPC['remarks']),array('apfid'=>$_GPC['apfid']));
        if($_GPC['status'] == 3){//判断是拒绝的话，就返回收益
            $agent = pdo_get('chat_agent',array('uniacid'=>$_W['uniacid'],'agid'=>$_GPC['parent_id']));
            $money = $agent['profit'] + $_GPC['price'];//收益
            $agent_update = pdo_update('chat_agent',array('profit'=>$money),array('agid'=>$_GPC['parent_id']));
        }
        if($apf_update){
            echo json_encode(array('code'=>1,'msg'=>'操作成功'));exit;
        }else{
            echo json_encode(array('code'=>2,'msg'=>'操作失败'));exit;
        }

    }
}

include $this->template('ask_payment');

?>