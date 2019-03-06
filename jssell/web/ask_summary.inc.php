<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$op = $_GPC['op'] ? $_GPC['op'] : 'totaldata';
$uniacid=$_W['uniacid'];
$cfg = $this->module['config']; //配置信息
if($op == 'totaldata'){
// 精选类目统计
// ------------------------------------------------------------------------------------------------------------------------------------------------------
	//文章条件
	$condition = ' where r.uniacid = :uniacid and r.type = 1 and r.status = 1 ';
	$where[':uniacid'] = $_W['uniacid'];
	$articleNum = array();

											//--一天--
	//平台文章今日数量
	$startTime = strtotime(date("Y-m-d"),time());
	$endTime = strtotime(date("Y-m-d".'23:59:59'),time());
	$articleNum['day']['platform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').' r left join '.tablename('chat_article_article').' a ON
		a.id = r.reid '.$condition.' and r.create_time BETWEEN '.$startTime.' and '.$endTime.' and a.is_jssell = 1 ',$where);

	//专家文章今日数量
	$articleNum['day']['expert'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').' r left join '.tablename('chat_article_article').' a ON
		a.id = r.reid '.$condition.' and r.create_time BETWEEN '.$startTime.' and '.$endTime.' and a.is_jssell = 0 ',$where);


											//--一周--
	//平台文章一周数量
	$startTime = time() - (86400 * 7);
	$articleNum['week']['platform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').' r left join '.tablename('chat_article_article').' a ON
		a.id = r.reid '.$condition.' and r.create_time BETWEEN '.$startTime.' and '.time().' and a.is_jssell = 1 ',$where);

	//专家文章一周数量
	$articleNum['week']['expert'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').' r left join '.tablename('chat_article_article').' a ON
		a.id = r.reid '.$condition.' and r.create_time BETWEEN '.$startTime.' and '.time().' and a.is_jssell = 0 ',$where);


											//--一个月--
	//平台文章一个月数量
	$startTime = strtotime(' -1 month ',time());
	$articleNum['month']['platform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').' r left join '.tablename('chat_article_article').' a ON
		a.id = r.reid '.$condition.' and r.create_time BETWEEN '.$startTime.' and '.time().' and a.is_jssell = 1 ',$where);

	//专家文章一个月数量
	$articleNum['month']['expert'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').' r left join '.tablename('chat_article_article').' a ON
		a.id = r.reid '.$condition.'  and r.create_time BETWEEN '.$startTime.' and '.time().' and a.is_jssell = 0 ',$where);


											//--总数量上架--
	//平台文章总量上架
	$articleNum['statusOne']['platform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').' r left join '.tablename('chat_article_article').' a ON
		a.id = r.reid '.$condition.' and a.is_jssell = 1 ',$where);

	//专家文章总量上架
	$articleNum['statusOne']['expert'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').' r left join '.tablename('chat_article_article').' a ON
		a.id = r.reid '.$condition.' and a.is_jssell = 0 ',$where);


											//--总数量下架--
	//平台文章总量下架
	$articleNum['statusTwo']['platform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').' r left join '.tablename('chat_article_article').' a ON
		a.id = r.reid where r.uniacid = :uniacid and r.type = 1 and r.status = 0 and a.is_jssell = 1 ',array(':uniacid'=>$_W['uniacid']));

	//专家文章总量下架
	$articleNum['statusTwo']['expert'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').' r left join '.tablename('chat_article_article').' a ON
		a.id = r.reid where r.uniacid = :uniacid and r.type = 1 and r.status = 0 and a.is_jssell = 0 ',array(':uniacid'=>$_W['uniacid']));


	//问答条件
	$condition = ' where uniacid = :uniacid and type = 2 and status = 1 ';
	$where[':uniacid'] = $_W['uniacid'];
	$askNum = array();

	//用户问答今日数量
	$startTime = strtotime(date("Y-m-d"),time());
	$endTime = strtotime(date("Y-m-d".'23:59:59'),time());
	$askNum['day'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').$condition.' and create_time BETWEEN '.$startTime.' and '.$endTime,$where);

	//用户问答一周数量
	$startTime = time() - (86400 * 7);
	$askNum['week'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').$condition.' and create_time BETWEEN '.$startTime.' and '.time(),$where);

	//用户问答一个月数量
	$startTime = strtotime(' -1 month ',time());
	$askNum['month'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').$condition.' and create_time BETWEEN '.$startTime.' and '.time(),$where);

	//用户问答总量上架
	$askNum['statusOne'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').$condition,$where);

	//用户问答总量下架
	$askNum['statusTwo'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_index_record').' where uniacid = :uniacid and status = 0 and type = 2 ',array(':uniacid'=>$_W['uniacid']));


// 专家数量统计
// ------------------------------------------------------------------------------------------------------------------------------------------------------

	//专家条件
	$expertCondition = ' where uniacid = :uniacid and vstatus = 1 and is_openask = 1 ';
	$expertWhere[':uniacid'] = $_W['uniacid'];
	$expertNum = array();


											//--一天--
	//平台专家今日数量
	$startTime = strtotime(date("Y-m-d"),time());
	$endTime = strtotime(date("Y-m-d".'23:59:59'),time());
	$expertNum['day']['platform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').$expertCondition.' and apply_succtime BETWEEN '.$startTime.' and '.$endTime.' and is_platform = 1 ',$expertWhere);

	//非平台专家今日数量
	$expertNum['day']['noPlatform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').$expertCondition.' and apply_succtime BETWEEN '.$startTime.' and '.$endTime.' and is_platform = 0 ',$expertWhere);


											//--一周--
	//平台专家一周数量
	$startTime = time() - (86400 * 7);
	$expertNum['week']['platform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').$expertCondition.' and apply_succtime BETWEEN '.$startTime.' and '.time().' and is_platform = 1 ',$expertWhere);

	//非平台专家一周数量
	$expertNum['week']['noplatform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').$expertCondition.' and apply_succtime BETWEEN '.$startTime.' and '.time().' and is_platform = 0 ',$expertWhere);


											//--一个月--
	//平台专家一个月数量
	$startTime = strtotime(' -1 month ',time());
	$expertNum['month']['platform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').$expertCondition.' and apply_succtime BETWEEN '.$startTime.' and '.time().' and is_platform = 1 ',$expertWhere);

	//非平台专家一个月数量
	$expertNum['month']['noplatform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').$expertCondition.' and apply_succtime BETWEEN '.$startTime.' and '.time().' and is_platform = 0 ',$expertWhere);


											//--总数量上架--
	//平台专家总数量上架
	$expertNum['isOpenaskOne']['platform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').$expertCondition.' and is_platform = 1 ',$expertWhere);

	//平台专家总数量上架
	$expertNum['isOpenaskOne']['noplatform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').$expertCondition.' and is_platform = 0 ',$expertWhere);


											//--总数量下架--
	//平台专家总数量下架
	$expertNum['isOpenaskZero']['platform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').' where uniacid = :uniacid and vstatus = 1 and is_platform = 1 and is_openask = 0 ',array(':uniacid'=>$_W['uniacid']));

	//非平台专家总数量下架
	$expertNum['isOpenaskZero']['noplatform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').' where uniacid = :uniacid and vstatus = 1 and is_platform = 0 and is_openask = 0 ',array(':uniacid'=>$_W['uniacid']));


											//--受邀数量--
	//平台专家受邀数量
	$expertNum['superiorId']['platform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').' where uniacid = :uniacid and vstatus = 1 and is_platform = 1 and superior_id <> null ',array(':uniacid'=>$_W['uniacid']));

	//非平台专家受邀数量
	$expertNum['superiorId']['noplatform'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').' where uniacid = :uniacid and vstatus = 1 and is_platform = 0 and superior_id <> null ',array(':uniacid'=>$_W['uniacid']));

// 用户数量统计
// ------------------------------------------------------------------------------------------------------------------------------------------------------

	$userCondition = ' where uniacid = :uniacid ';
	$userWhere[':uniacid'] = $_W['uniacid'];
	$user = array();

	//一天的用户数量
	$startTime = strtotime(date("Y-m-d"),time());
	$endTime = strtotime(date("Y-m-d".'23:59:59'),time());
	$user['day'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').$userCondition.' and create_time BETWEEN '.$startTime.' and '.$endTime,$userWhere);

	//一周的用户数量
	$startTime = time() - (86400 * 7);
	$user['week'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').$userCondition.' and create_time BETWEEN '.$startTime.' and '.time(),$userWhere);

	//一个月的用户数量
	$startTime = strtotime(' -1 month ',time());
	$user['month'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').$userCondition.' and create_time BETWEEN '.$startTime.' and '.time(),$userWhere);

	//用户总数量
	$user['total'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').$userCondition,$userWhere);

	//用户受邀数量
	$user['superior'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_users').$userCondition.' and superior_id <> null ',$userWhere);

// 用户一对一问答数量统计(不包括悬赏)
// ------------------------------------------------------------------------------------------------------------------------------------------------------

	$expertAsk = array();

													//一天
	//一天的一对一问答中级专家数量
	$startTime = strtotime(date("Y-m-d"),time());
	$endTime = strtotime(date("Y-m-d".'23:59:59'),time());
	$expertAsk['day']['intermediate'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 1 and a.ask_type = 1 and a.create_time BETWEEN '.$startTime.' and '.$endTime,array(':uniacid'=>$_W['uniacid']));

	//一天的一对一问答高级专家数量
	$expertAsk['day']['high'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 2 and a.ask_type = 1 and a.create_time BETWEEN '.$startTime.' and '.$endTime,array(':uniacid'=>$_W['uniacid']));

	//一天的一对一问答资深级专家数量
	$expertAsk['day']['senior'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 3 and a.ask_type = 1 and a.create_time BETWEEN '.$startTime.' and '.$endTime,array(':uniacid'=>$_W['uniacid']));

	//一天的一对一问答大师级专家数量
	$expertAsk['day']['master'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 4 and a.ask_type = 1 and a.create_time BETWEEN '.$startTime.' and '.$endTime,array(':uniacid'=>$_W['uniacid']));


													//--一周--
	//一周的一对一问答中级专家数量
	$startTime = time() - (86400 * 7);
	$expertAsk['week']['intermediate'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 1 and a.ask_type = 1 and a.create_time BETWEEN '.$startTime.' and '.time(),array(':uniacid'=>$_W['uniacid']));

	//一周的一对一问答高级专家数量
	$expertAsk['week']['high'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 2 and a.ask_type = 1 and a.create_time BETWEEN '.$startTime.' and '.time(),array(':uniacid'=>$_W['uniacid']));

	//一周的一对一问答资深级专家数量
	$expertAsk['week']['senior'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 3 and a.ask_type = 1 and a.create_time BETWEEN '.$startTime.' and '.time(),array(':uniacid'=>$_W['uniacid']));

	//一周的一对一问答大师级专家数量
	$expertAsk['week']['master'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 4 and a.ask_type = 1 and a.create_time BETWEEN '.$startTime.' and '.time(),array(':uniacid'=>$_W['uniacid']));


													//--一个月--
	//一个月的一对一问答中级专家数量
	$startTime = strtotime(' -1 month ',time());
	$expertAsk['month']['intermediate'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 1 and a.ask_type = 1 and a.create_time BETWEEN '.$startTime.' and '.time(),array(':uniacid'=>$_W['uniacid']));

	//一个月的一对一问答高级专家数量
	$expertAsk['month']['high'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 2 and a.ask_type = 1 and a.create_time BETWEEN '.$startTime.' and '.time(),array(':uniacid'=>$_W['uniacid']));

	//一个月的一对一问答资深级专家数量
	$expertAsk['month']['senior'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 3 and a.ask_type = 1 and a.create_time BETWEEN '.$startTime.' and '.time(),array(':uniacid'=>$_W['uniacid']));

	//一个月的一对一问答大师级专家数量
	$expertAsk['month']['master'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 4 and a.ask_type = 1 and a.create_time BETWEEN '.$startTime.' and '.time(),array(':uniacid'=>$_W['uniacid']));


													//--总数量--
	//一对一问答中级专家总数量
	$expertAsk['total']['intermediate'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 1 and a.ask_type = 1 ',array(':uniacid'=>$_W['uniacid']));

	//一对一问答高级专家总数量
	$expertAsk['total']['high'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 2 and a.ask_type = 1 ',array(':uniacid'=>$_W['uniacid']));

	//一对一问答资深级专家总数量
	$expertAsk['total']['senior'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 3 and a.ask_type = 1 ',array(':uniacid'=>$_W['uniacid']));

	//一对一问答大师级专家总数量
	$expertAsk['total']['master'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 4 and a.ask_type = 1 ',array(':uniacid'=>$_W['uniacid']));


													//--采纳数量--
	//一对一问答中级专家采纳数量
	$expertAsk['adopt']['intermediate'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 1 and a.ask_type = 1 and a.reward_status = 1 ',array(':uniacid'=>$_W['uniacid']));

	//一对一问答高级专家采纳数量
	$expertAsk['adopt']['high'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 2 and a.ask_type = 1 and a.reward_status = 1 ',array(':uniacid'=>$_W['uniacid']));

	//一对一问答资深级专家采纳数量
	$expertAsk['adopt']['senior'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 3 and a.ask_type = 1 and a.reward_status = 1 ',array(':uniacid'=>$_W['uniacid']));

	//一对一问答大师级专家采纳数量
	$expertAsk['adopt']['master'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 4 and a.ask_type = 1 and a.reward_status = 1 ',array(':uniacid'=>$_W['uniacid']));


												 //--未采纳数量--
	//一对一问答中级专家未采纳数量
	$expertAsk['noAdopt']['intermediate'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 1 and a.ask_type = 1 and a.reward_status <> 1 and a.answer_num <> 0 ',array(':uniacid'=>$_W['uniacid']));

	//一对一问答高级专家未采纳数量
	$expertAsk['noAdopt']['high'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 2 and a.ask_type = 1 and a.reward_status <> 1 and a.answer_num <> 0 ',array(':uniacid'=>$_W['uniacid']));

	//一对一问答资深级专家未采纳数量
	$expertAsk['noAdopt']['senior'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 3 and a.ask_type = 1 and a.reward_status <> 1 and a.answer_num <> 0 ',array(':uniacid'=>$_W['uniacid']));

	//一对一问答大师级专家未采纳数量
	$expertAsk['noAdopt']['master'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').' a left join '.tablename('chat_users').' u on u.id = a.fuid where a.uniacid = :uniacid and u.level_id = 4 and a.ask_type = 1 and a.reward_status <> 1 and a.answer_num <> 0 ',array(':uniacid'=>$_W['uniacid']));


// 用户悬赏数量统计
// ------------------------------------------------------------------------------------------------------------------------------------------------------

	$rewardCondition = ' where uniacid = :uniacid and ask_type = 2 ';
	$rewardWhere[':uniacid'] = $_W['uniacid'];
	$reward = array();

	//一天的悬赏数量
	$startTime = strtotime(date("Y-m-d"),time());
	$endTime = strtotime(date("Y-m-d".'23:59:59'),time());
	$reward['day'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').$rewardCondition.' and create_time BETWEEN '.$startTime.' and '.$endTime,$where);

	//一周的悬赏数量
	$startTime = time() - (86400 * 7);
	$reward['week'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').$rewardCondition.' and create_time BETWEEN '.$startTime.' and '.time(),$where);

	//一个月的悬赏数量
	$startTime = strtotime(' -1 month ',time());
	$reward['month'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').$rewardCondition.' and create_time BETWEEN '.$startTime.' and '.time(),$where);

	//悬赏总数量
	$reward['total'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').$rewardCondition,$where);

	//悬赏采纳数量
	$reward['adopt'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').$rewardCondition.' and reward_status = 1 ',$where);

	//悬赏未采纳数量
	$reward['noAdopt'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').$rewardCondition.' and reward_status <> 1 and answer_num <> 0 ',$where);

	//悬赏未回答数量
	$reward['noAnswer'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_ask').$rewardCondition.' and reward_status <> 1 and answer_num = 0 ',$where);

// 平台订单数量统计(已支付)
// ------------------------------------------------------------------------------------------------------------------------------------------------------
	$orderCondition = ' where uniacid = :uniacid and status in (2,5,7,9,10) ';
	$orderWhere[':uniacid'] = $_W['uniacid'];
	$order = array();

	//一天的订单数量
	$startTime = strtotime(date("Y-m-d"),time());
	$endTime = strtotime(date("Y-m-d".'23:59:59'),time());
	$order['day']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_order').$orderCondition.' and pay_time BETWEEN '.$startTime.' and '.$endTime,$orderWhere);

	//一天的订单金额
	$order['day']['price'] = pdo_fetchcolumn(' select SUM(price) from '.tablename('chat_order').$orderCondition.' and pay_time BETWEEN '.$startTime.' and '.$endTime,$orderWhere);
	if(empty($order['day']['price'])){
		$order['day']['price'] = 0;
	}

	//一周的订单数量
	$startTime = time() - (86400 * 7);
	$order['week']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_order').$orderCondition.' and pay_time BETWEEN '.$startTime.' and '.time(),$orderWhere);

	//一周的订单金额
	$order['week']['price'] = pdo_fetchcolumn(' select SUM(price) from '.tablename('chat_order').$orderCondition.' and pay_time BETWEEN '.$startTime.' and '.time(),$orderWhere);
	if(empty($order['week']['price'])){
		$order['week']['price'] = 0;
	}

	//一个月的的订单数量
	$startTime = strtotime(' -1 month ',time());
	$order['month']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_order').$orderCondition.' and pay_time BETWEEN '.$startTime.' and '.time(),$orderWhere);

	//一周的订单金额
	$order['month']['price'] = pdo_fetchcolumn(' select SUM(price) from '.tablename('chat_order').$orderCondition.' and pay_time BETWEEN '.$startTime.' and '.time(),$orderWhere);
	if(empty($order['month']['price'])){
		$order['month']['price'] = 0;
	}

	//订单总数量
	$order['total']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_order').$orderCondition,$orderWhere);

	//订单总金额
	$order['total']['price'] = pdo_fetchcolumn(' select SUM(price) from '.tablename('chat_order').$orderCondition,$orderWhere);
	if(empty($order['total']['price'])){
		$order['total']['price'] = 0;
	}


// 用户充值统计(已到账)
// ------------------------------------------------------------------------------------------------------------------------------------------------------
	$rechargeCondition = ' where uniacid = :uniacid and pay_status = 2 ';
	$rechargeWhere[':uniacid'] = $_W['uniacid'];
	$recharge = array();

	//一天的充值数量
	$startTime = strtotime(date("Y-m-d"),time());
	$endTime = strtotime(date("Y-m-d".'23:59:59'),time());
	$recharge['day']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_recharge').$rechargeCondition.' and time BETWEEN '.$startTime.' and '.$endTime,$rechargeWhere);

	//一天的充值金额
	$recharge['day']['price'] = pdo_fetchcolumn(' select SUM(money) from '.tablename('chat_recharge').$rechargeCondition.' and time BETWEEN '.$startTime.' and '.$endTime,$rechargeWhere);
	if(empty($recharge['day']['price'])){
		$recharge['day']['price'] = 0;
	}

	//一周的充值数量
	$startTime = time() - (86400 * 7);
	$recharge['week']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_recharge').$rechargeCondition.' and time BETWEEN '.$startTime.' and '.time(),$rechargeWhere);

	//一周的充值金额
	$recharge['week']['price'] = pdo_fetchcolumn(' select SUM(money) from '.tablename('chat_recharge').$rechargeCondition.' and time BETWEEN '.$startTime.' and '.time(),$rechargeWhere);
	if(empty($recharge['week']['price'])){
		$recharge['week']['price'] = 0;
	}

	//一个月的充值数量
	$startTime = strtotime(' -1 month ',time());
	$recharge['month']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_recharge').$rechargeCondition.' and time BETWEEN '.$startTime.' and '.time(),$rechargeWhere);

	//一个月的充值金额
	$recharge['month']['price'] = pdo_fetchcolumn(' select SUM(money) from '.tablename('chat_recharge').$rechargeCondition.' and time BETWEEN '.$startTime.' and '.time(),$rechargeWhere);
	if(empty($recharge['month']['price'])){
		$recharge['month']['price'] = 0;
	}

	//充值总数量
	$recharge['total']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_recharge').$rechargeCondition,$rechargeWhere);

	//充值总金额
	$recharge['total']['price'] = pdo_fetchcolumn(' select SUM(money) from '.tablename('chat_recharge').$rechargeCondition,$rechargeWhere);
	if(empty($recharge['total']['price'])){
		$recharge['total']['price'] = 0;
	}


// 平台总账汇总统计
// ------------------------------------------------------------------------------------------------------------------------------------------------------

	$profit = array();

	//文章收益
	$profit['article'] = pdo_fetchcolumn(' select SUM(total_price) from '.tablename('chat_profit').' where uniacid = :uniacid and role = 1 and type = 2 ',array(':uniacid'=>$_W['uniacid']));
	if(empty($profit['article'])){
		$profit['article'] = 0;
	}

	//问答收益
	$profit['ask'] = pdo_fetchcolumn(' select SUM(total_price) from '.tablename('chat_profit').' where uniacid = :uniacid and role = 1 and type = 3 ',array(':uniacid'=>$_W['uniacid']));
	if(empty($profit['ask'])){
		$profit['ask'] = 0;
	}

	//悬赏收益
	$profit['reward'] = pdo_fetchcolumn(' select SUM(total_price) from '.tablename('chat_profit').' where uniacid = :uniacid and role = 1 and type = 1 ',array(':uniacid'=>$_W['uniacid']));
	if(empty($profit['reward'])){
		$profit['reward'] = 0;
	}

	//赞赏收益
	$profit['appreciate'] = pdo_fetchcolumn(' select SUM(total_price) from '.tablename('chat_profit').' where uniacid = :uniacid and role = 1 and type = 4 ',array(':uniacid'=>$_W['uniacid']));
	if(empty($profit['appreciate'])){
		$profit['appreciate'] = 0;
	}

	//服务收益
	$profit['service'] = pdo_fetchcolumn(' select SUM(total_price) from '.tablename('chat_profit').' where uniacid = :uniacid and role = 1 and type = 5 ',array(':uniacid'=>$_W['uniacid']));
	if(empty($profit['service'])){
		$profit['service'] = 0;
	}

	//平台收益
	$profit['platform'] = pdo_fetchcolumn(' select SUM(get_price) from '.tablename('chat_profit').' where uniacid = :uniacid and role = 1 ',array(':uniacid'=>$_W['uniacid']));
	if(empty($profit['platform'])){
		$profit['platform'] = 0;
	}

	//支付方式
	$payment = array();

	//余额支付
	$payment['ye'] = pdo_fetchcolumn(' select SUM(price) from '.tablename('chat_users_pay_log').' where uniacid = :uniacid and pay_status = 2 and pay_type = :pay_type ',array(':uniacid'=>$_W['uniacid'],':pay_type'=>'ye'));
	if(empty($payment['ye'])){
		$payment['ye'] = 0;
	}

	//微信支付
	$payment['wx'] = pdo_fetchcolumn(' select SUM(price) from '.tablename('chat_users_pay_log').' where uniacid = :uniacid and pay_status = 2 and pay_type = :pay_type ',array(':uniacid'=>$_W['uniacid'],':pay_type'=>'wx'));
	if(empty($payment['wx'])){
		$payment['wx'] = 0;
	}

	//用户余额
	$userBalance = pdo_fetchcolumn(' select SUM(balance) from '.tablename('chat_users').' where uniacid = :uniacid ',array(':uniacid'=>$_W['uniacid']));
	if(!empty($userBalance)){
		$userBalance = $userBalance;
	}else{
		$userBalance = 0;
	}

	#专家团 汇总
#-----------------------------------------------------------------------------------------------------------------------------------------------------

    $experCondition = ' where uniacid = :uniacid ';
	$experWhere[':uniacid'] = $_W['uniacid'];
	$experResult = array();

	//一天的审核数量
	$experStartTime = strtotime(date("Y-m-d"),time());
	$experEndTime = strtotime(date("Y-m-d".'23:59:59'),time());
	$experResult['day']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_expert_group').$experCondition.' and audit_time BETWEEN '.$experStartTime.' and '.$experEndTime . ' and status=1',$experWhere);

    //本周的审核数量
    $startTime = time() - (86400 * 7);
	$experResult['week']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_expert_group').$experCondition.' and audit_time BETWEEN '.$experEndTime.' and '.time().' and status=1',$experWhere);

	//本月的审核数量
	$experEndTime = strtotime(' -1 month ',time());
	$experResult['month']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_expert_group').$experCondition.' and audit_time BETWEEN '.$experEndTime.' and '.time().' and status=1',$experWhere);

	//总数量
	$experResult['total']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_expert_group').$experCondition.' and status=1',$experWhere);

	#-----------------------------专家团订单统计---------------------------------------------------------------------------------
    $experOrderCondition = ' where uniacid = :uniacid and is_group=:is_group ';
	$experOrderWhere[':uniacid'] = $_W['uniacid'];
 	$experOrderWhere[':is_group'] = 1;


    //一天的订单数量
	$orderstartTime = strtotime(date("Y-m-d"),time());
	$orderendTime = strtotime(date("Y-m-d".'23:59:59'),time());
	$experOrder['day']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_order').$experOrderCondition.' and pay_time BETWEEN '.$orderstartTime.' and '.$orderendTime,$experOrderWhere);

	//一天的订单金额
	$experOrder['day']['price'] = pdo_fetchcolumn(' select SUM(price) from '.tablename('chat_order').$experOrderCondition.' and pay_time BETWEEN '.$orderstartTime.' and '.$orderendTime,$experOrderWhere);
	if(empty($experOrder['day']['price'])){
		$experOrder['day']['price'] = 0;
	}

	//一周的订单数量
	$orderstartTime = time() - (86400 * 7);
	$experOrder['week']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_order').$experOrderCondition.' and pay_time BETWEEN '.$orderstartTime.' and '.time(),$experOrderWhere);

	//一周的订单金额
	$experOrder['week']['price'] = pdo_fetchcolumn(' select SUM(price) from '.tablename('chat_order').$experOrderCondition.' and pay_time BETWEEN '.$orderstartTime.' and '.time(),$experOrderWhere);
	if(empty($experOrder['week']['price'])){
		$experOrder['week']['price'] = 0;
	}

	//一个月的的订单数量
	$orderstartTime = strtotime(' -1 month ',time());
	$experOrder['month']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_order').$experOrderCondition.' and pay_time BETWEEN '.$orderstartTime.' and '.time(),$experOrderWhere);

	//一周的订单金额
	$experOrder['month']['price'] = pdo_fetchcolumn(' select SUM(price) from '.tablename('chat_order').$experOrderCondition.' and pay_time BETWEEN '.$orderstartTime.' and '.time(),$experOrderWhere);
	if(empty($experOrder['month']['price'])){
		$experOrder['month']['price'] = 0;
	}

	//订单总数量
	$experOrder['total']['num'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_order').$experOrderCondition,$experOrderWhere);
	//订单总金额
	$experOrder['total']['price'] = pdo_fetchcolumn(' select SUM(price) from '.tablename('chat_order').$experOrderCondition,$experOrderWhere);
	if(empty($recharge['total']['price'])){
		$experOrder['total']['price'] = 0;
	}
#end---------------------------------------------------------------------------------------------------------------------------------------------------

	if($_GPC['excel'] == 'export'){
		//导出
		ob_end_clean();//清除缓冲区,避免乱码
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-execl;charset=utf-8");//设置导出格式
        header("Content-Disposition:filename=数据统计记录表--".date('Y-m-d H:i:s',time()).".xls");
        //制作表头
        echo   "<table border='1'>";
	    echo   "<thead class='navbar-inner'>";
		echo	    "<tr>";
		echo		 "<th colspan='6' style='text-align: center;font-size: 20px;'>".iconv('utf-8', 'GB18030','精选类目')."</th>";
		echo        "</tr>";
		echo   "</thead>";
		echo   "<tr>";
		echo		"<td rowspan='2' style='line-height: 400%;'>".iconv('utf-8', 'GB18030','分类')."</td>";
		echo		"<td colspan='4'>".iconv('utf-8', 'GB18030','上架数量')."</td>";
		echo		"<td>".iconv('utf-8', 'GB18030','下架数量')."</td>";
		echo	"</tr>";
		echo	"<tr>";
		echo		"<td>".iconv('utf-8', 'GB18030','今日增量')."</td>";
		echo		"<td>".iconv('utf-8', 'GB18030','一周')."</td>";
		echo		"<td>".iconv('utf-8', 'GB18030','一个月')."</td>";
		echo		"<td>".iconv('utf-8', 'GB18030','总量')."</td>";
		echo		"<td>".iconv('utf-8', 'GB18030','总量')."</td>";
		echo	"</tr>";
		echo	"<tr>";
		echo		"<td>".iconv('utf-8', 'GB18030','平台文章')."</td>";
		echo		"<td>".$articleNum['day']['platform']."</td>";
		echo		"<td>".$articleNum['week']['platform']."</td>";
		echo		"<td>".$articleNum['month']['platform']."</td>";
		echo		"<td>".$articleNum['statusOne']['platform']."</td>";
		echo		"<td>".$articleNum['statusTwo']['platform']."</td>";
		echo	"</tr>";
		echo	"<tr>";
		echo		"<td>".iconv('utf-8', 'GB18030','专家文章')."</td>";
		echo		"<td>".$articleNum['day']['expert']."</td>";
		echo		"<td>".$articleNum['week']['expert']."</td>";
		echo		"<td>".$articleNum['month']['expert']."</td>";
		echo		"<td>".$articleNum['statusOne']['expert']."</td>";
		echo		"<td>".$articleNum['statusTwo']['expert']."</td>";
		echo	"</tr>";
		echo	"<tr>";
		echo		"<td>".iconv('utf-8', 'GB18030','用户问答')."</td>";
		echo		"<td>".$askNum['day']."</td>";
		echo		"<td>".$askNum['week']."</td>";
		echo		"<td>".$askNum['month']."</td>";
		echo		"<td>".$askNum['statusOne']."</td>";
		echo		"<td>".$askNum['statusTwo']."</td>";
		echo	"</tr>";
        // echo    "</table>";


        // echo "<table class='table table-hover'>";
        echo	"<thead class='navbar-inner'>";
		echo		"<tr>";
		echo			"<th colspan='7' style='text-align: center;font-size: 20px;'>".iconv('utf-8', 'GB18030','专家(数量)')."</th>";
		echo		"</tr>";
		echo	"</thead>";
		echo	"<tr>";
		echo		"<td rowspan='2' style='line-height: 400%;'>".iconv('utf-8', 'GB18030','分类')."</td>";
		echo		"<td colspan='4'>".iconv('utf-8', 'GB18030','上架数量')."</td>";
		echo		"<td>".iconv('utf-8', 'GB18030','下架数量')."</td>";
		echo		"<td>".iconv('utf-8', 'GB18030','受邀数量')."</td>";
		echo	"</tr>";
		echo	"<tr>";
		echo		"<td>".iconv('utf-8', 'GB18030','今日增量')."</td>";
		echo		"<td>".iconv('utf-8', 'GB18030','一周')."</td>";
		echo		"<td>".iconv('utf-8', 'GB18030','一个月')."</td>";
		echo		"<td>".iconv('utf-8', 'GB18030','总量')."</td>";
		echo		"<td>".iconv('utf-8', 'GB18030','总量')."</td>";
		echo		"<td>".iconv('utf-8', 'GB18030','总量')."</td>";
		echo	"</tr>";
		echo	"<tr>";
		echo		"<td>".iconv('utf-8', 'GB18030','平台')."</td>";
		echo		"<td>".$expertNum['day']['platform']."</td>";
		echo		"<td>".$expertNum['week']['platform']."</td>";
		echo		"<td>".$expertNum['month']['platform']."</td>";
		echo		"<td>".$expertNum['isOpenaskOne']['platform']."</td>";
		echo		"<td>".$expertNum['isOpenaskZero']['platform']."</td>";
		echo		"<td>".$expertNum['superiorId']['platform']."</td>";
		echo	"</tr>";
		echo	"<tr>";
		echo		"<td>".iconv('utf-8', 'GB18030','其他')."</td>";
		echo		"<td>".$expertNum['day']['noPlatform']."</td>";
		echo		"<td>".$expertNum['week']['noplatform']."</td>";
		echo		"<td>".$expertNum['month']['noplatform']."</td>";
		echo		"<td>".$expertNum['isOpenaskOne']['noplatform']."</td>";
		echo		"<td>".$expertNum['isOpenaskZero']['noplatform']."</td>";
		echo		"<td>".$expertNum['superiorId']['noplatform']."</td>";
		echo	"</tr>";



		echo "<thead class='navbar-inner'>";
		echo	"<tr>";
		echo		"<th colspan='7' style='text-align: center;font-size: 20px;'>".iconv('utf-8', 'GB18030','平台用户')."</th>";
		echo	"</tr>";
		echo "</thead>";
		echo "<tr>";
		echo	"<td>".iconv('utf-8', 'GB18030','分类')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','今日增量')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','一周')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','一个月')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','总量')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','受邀请总量')."</td>";
		echo "</tr>";
		echo "<tr>";
		echo	"<td>".iconv('utf-8', 'GB18030','用户数量')."</td>";
		echo	"<td>{$user['day']}</td>";
		echo	"<td>{$user['week']}</td>";
		echo	"<td>{$user['month']}</td>";
		echo	"<td>{$user['total']}</td>";
		echo	"<td>{$user['superior']}</td>";
		echo "</tr>";



		echo "<thead class='navbar-inner'>";
		echo	"<tr>";
		echo		"<th colspan='7' style='text-align: center;font-size: 20px;'>".iconv('utf-8', 'GB18030','用户一对一问答（数量）')."</th>";
		echo	"</tr>";
		echo "</thead>";
		echo "<tr>";
		echo	"<td>".iconv('utf-8', 'GB18030','分类')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','今日增量')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','一周')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','一个月')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','总量')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','采纳总量')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','未采纳总量')."</td>";
		echo "</tr>";
		echo "<tr>";
		echo	"<td>".iconv('utf-8', 'GB18030','中级专家')."</td>";
		echo	"<td>{$expertAsk['day']['intermediate']}</td>";
		echo	"<td>{$expertAsk['week']['intermediate']}</td>";
		echo	"<td>{$expertAsk['month']['intermediate']}</td>";
		echo	"<td>{$expertAsk['total']['intermediate']}</td>";
		echo	"<td>{$expertAsk['adopt']['intermediate']}</td>";
		echo	"<td>{$expertAsk['noAdopt']['intermediate']}</td>";
		echo "</tr>";
		echo "<tr>";
		echo	"<td>".iconv('utf-8', 'GB18030','高级专家')."</td>";
		echo	"<td>{$expertAsk['day']['high']}</td>";
		echo	"<td>{$expertAsk['week']['high']}</td>";
		echo	"<td>{$expertAsk['month']['high']}</td>";
		echo	"<td>{$expertAsk['total']['high']}</td>";
		echo	"<td>{$expertAsk['adopt']['high']}</td>";
		echo	"<td>{$expertAsk['noAdopt']['high']}</td>";
		echo "</tr>";
		echo "<tr>";
		echo	"<td>".iconv('utf-8', 'GB18030','资深级专家')."</td>";
		echo	"<td>{$expertAsk['day']['senior']}</td>";
		echo	"<td>{$expertAsk['week']['senior']}</td>";
		echo	"<td>{$expertAsk['month']['senior']}</td>";
		echo	"<td>{$expertAsk['total']['senior']}</td>";
		echo	"<td>{$expertAsk['adopt']['senior']}</td>";
		echo	"<td>{$expertAsk['noAdopt']['senior']}</td>";
		echo "</tr>";
		echo "<tr>";
		echo	"<td>".iconv('utf-8', 'GB18030','大师级专家')."</td>";
		echo	"<td>{$expertAsk['day']['master']}</td>";
		echo	"<td>{$expertAsk['week']['master']}</td>";
		echo	"<td>{$expertAsk['month']['master']}</td>";
		echo	"<td>{$expertAsk['total']['master']}</td>";
		echo	"<td>{$expertAsk['adopt']['master']}</td>";
		echo	"<td>{$expertAsk['noAdopt']['master']}</td>";
		echo "</tr>";



		echo "<thead class='navbar-inner'>";
		echo	"<tr>";
		echo		"<th colspan='7' style='text-align: center;font-size: 20px;'>".iconv('utf-8', 'GB18030','用户悬赏（数量）')."</th>";
		echo	"</tr>";
		echo "</thead>";
		echo "<tr>";
		echo	"<td>".iconv('utf-8', 'GB18030','今日增量')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','一周')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','一个月')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','总量')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','采纳总量')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','未采纳总量')."</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','未回答总量')."</td>";
		echo "</tr>";
		echo "<tr>";
		echo	"<td>{$reward['day']}</td>";
		echo	"<td>{$reward['week']}</td>";
		echo	"<td>{$reward['month']}</td>";
		echo	"<td>{$reward['total']}</td>";
		echo	"<td>{$reward['adopt']}</td>";
		echo	"<td>{$reward['noAdopt']}</td>";
		echo	"<td>{$reward['noAnswer']}</td>";
		echo "</tr>";



		echo "<thead class='navbar-inner'>";
		echo	"<tr>";
		echo		"<th colspan='8' style='text-align: center;font-size: 20px;'>".iconv('utf-8', 'GB18030','平台订单')."</th>";
		echo	"</tr>";
		echo "</thead>";
		echo "<tr>";
		echo	"<td>".iconv('utf-8', 'GB18030','今日增量')."</td>";
		echo	"<td>{$order['day']['num']}</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','一周数量')."</td>";
		echo	"<td>{$order['week']['num']}</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','一月数量')."</td>";
		echo	"<td>{$order['month']['num']}</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','总订单数量')."</td>";
		echo	"<td>{$order['total']['num']}</td>";
		echo "</tr>";
		echo "<tr>";
		echo	"<td>".iconv('utf-8', 'GB18030','合计金额')."</td>";
		echo	"<td>{$order['day']['price']}</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','合计金额')."</td>";
		echo	"<td>{$order['week']['price']}</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','合计金额')."</td>";
		echo	"<td>{$order['month']['price']}</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','总合计金额')."</td>";
		echo	"<td>{$order['total']['price']}</td>";
		echo "</tr>";



		echo "<thead class='navbar-inner'>";
		echo	"<tr>";
		echo		"<th colspan='8' style='text-align: center;font-size: 20px;'>".iconv('utf-8', 'GB18030','用户充值')."</th>";
		echo	"</tr>";
		echo "</thead>";
		echo "<tr>";
		echo	"<td>".iconv('utf-8', 'GB18030','今日增量')."</td>";
		echo	"<td>{$recharge['day']['num']}</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','一周数量')."</td>";
		echo	"<td>{$recharge['week']['num']}</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','一月数量')."</td>";
		echo	"<td>{$recharge['month']['num']}</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','总订单数量')."</td>";
		echo	"<td>{$recharge['total']['num']}</td>";
		echo "</tr>";
		echo "<tr>";
		echo	"<td>".iconv('utf-8', 'GB18030','合计金额')."</td>";
		echo	"<td>{$recharge['day']['price']}</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','合计金额')."</td>";
		echo	"<td>{$recharge['week']['price']}</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','合计金额')."</td>";
		echo	"<td>{$recharge['month']['price']}</td>";
		echo	"<td>".iconv('utf-8', 'GB18030','总合计金额')."</td>";
		echo	"<td>{$recharge['total']['price']}</td>";
		echo "</tr>";



		// echo "<thead class='navbar-inner'>";
		// echo	"<tr>";
		// echo		"<th colspan='11' style='text-align: center;font-size: 20px;'>".iconv('utf-8', 'GB18030','平台总账汇总')."</th>";
		// echo	"</tr>";
		// echo "</thead>";
		// echo "<tr>";
		// echo	"<td colspan='6'>".iconv('utf-8', 'GB18030','消费汇总')."</td>";
		// echo	"<td colspan='2'>".iconv('utf-8', 'GB18030','支付方式')."</td>";
		// echo	"<td colspan='2'>".iconv('utf-8', 'GB18030','金额汇总')."</td>";
		// echo	"<td rowspan='2' style='line-height: 400%;'>".iconv('utf-8', 'GB18030','差异金额')."</td>";
		// echo "</tr>";
		// echo "<tr>";
		// echo	"<td>".iconv('utf-8', 'GB18030','文章收益')."</td>";
		// echo	"<td>".iconv('utf-8', 'GB18030','问答收益')."</td>";
		// echo	"<td>".iconv('utf-8', 'GB18030','悬赏收益')."</td>";
		// echo	"<td>".iconv('utf-8', 'GB18030','赞赏收益')."</td>";
		// echo	"<td>".iconv('utf-8', 'GB18030','服务收益')."</td>";
		// echo	"<td>".iconv('utf-8', 'GB18030','平台收益')."</td>";
		// echo	"<td>".iconv('utf-8', 'GB18030','余额')."</td>";
		// echo	"<td>".iconv('utf-8', 'GB18030','微信')."</td>";
		// echo	"<td>".iconv('utf-8', 'GB18030','余额')."</td>";
		// echo	"<td>".iconv('utf-8', 'GB18030','入账金额')."</td>";
		// echo "</tr>";
		// echo "<tr>";
		// echo	"<td>{$profit['article']}</td>";
		// echo	"<td>{$profit['ask']}</td>";
		// echo	"<td>{$profit['ask']}</td>";
		// echo	"<td>{$profit['reward']}</td>";
		// echo	"<td>{$profit['service']}</td>";
		// echo	"<td>{$profit['platform']}</td>";
		// echo	"<td>{$payment['ye']}</td>";
		// echo	"<td>{$payment['wx']}</td>";
		// echo	"<td>{$userBalance}</td>";
		// echo	"<td></td>";
		// echo	"<td></td>";
		// echo "</tr>";

		echo "</table>";exit;
	}


}

if($op == 'display'){
	$starttime = empty($_GPC['time']['start']) ? TIMESTAMP -  86399 * 30 : strtotime($_GPC['time']['start']);
	$endtime = empty($_GPC['time']['end']) ? TIMESTAMP+ 86400: strtotime($_GPC['time']['end']);
	$data = array();
	for($i = 1; $i <= 5; $i++){
		$data[$i]['type'] = $i;
		//平台专家收益数量
		$data[$i]['platform_number'] = pdo_fetchcolumn("SELECT COUNT(prid) FROM ".tablename('chat_profit').' where uniacid = '.$uniacid.' and create_time >= '.$starttime.' and create_time < '.$endtime.' and type = '.$i.' and role = 1 and source = 1');
		//普通专家收益数量
		$data[$i]['expert_number'] = pdo_fetchcolumn("SELECT COUNT(prid) FROM ".tablename('chat_profit').' where uniacid = '.$uniacid.' and create_time >= '.$starttime.' and create_time < '.$endtime.' and type = '.$i.' and role = 1 and source = 2');
		//平台专家总收益
		$data[$i]['platform_money'] = pdo_fetchcolumn("SELECT sum(total_price) FROM ".tablename('chat_profit').' where uniacid = '.$uniacid.' and create_time >= '.$starttime.' and create_time < '.$endtime.' and type = '.$i.' and role = 1 and source = 1');
		//普通专家总收益
		$data[$i]['expert_money'] = pdo_fetchcolumn("SELECT sum(total_price) FROM ".tablename('chat_profit').' where uniacid = '.$uniacid.' and create_time >= '.$starttime.' and create_time < '.$endtime.' and type = '.$i.' and role = 1 and source = 2');
		//专家收益 需要除开平台专家的收益 只是用于统计而已
		$data[$i]['expert'] = pdo_fetchcolumn("SELECT sum(get_price) FROM ".tablename('chat_profit').' where uniacid = '.$uniacid.' and create_time >= '.$starttime.' and create_time < '.$endtime.' and type = '.$i.' and role = 3');
		//用户收益
		$data[$i]['user'] = pdo_fetchcolumn("SELECT sum(get_price) FROM ".tablename('chat_profit').' where uniacid = '.$uniacid.' and create_time >= '.$starttime.' and create_time < '.$endtime.' and type = '.$i.' and role = 2');
		//平台收益
		$data[$i]['platform'] = pdo_fetchcolumn("SELECT sum(get_price) FROM ".tablename('chat_profit').' where uniacid = '.$uniacid.' and create_time >= '.$starttime.' and create_time < '.$endtime.' and type = '.$i.' and role = 1');

		$data[$i]['detail_url'] = $this->createWebUrl('ask_summary_detail',array('time'=>array('start'=>date('Y-m-d',$starttime),'end'=>date('Y-m-d',$endtime)),'type'=>$i,'version_id'=>0));
	}
	$total_platform_number = 0;//平台总收益数量
	$total_expert_number = 0;//普通专家总收益数量
	$total_platform_money = 0;//平台总收益
	$total_expert_money = 0;//普通专家总收益
	$total_expert = 0;
	$total_user = 0;
	$total_platform = 0;
	foreach ($data as $key => $value) {
		$total_platform_number += $value['platform_number'];
		$total_expert_number += $value['expert_number'];
		$total_platform_money += $value['platform_money'];
		$total_expert_money += $value['expert_money'];
		$total_expert += $value['expert'];
		$total_user += $value['user'];
		$total_platform += $value['platform'];
	}

	if($_GPC['excel'] == 'yes'){
		//导出
		ob_end_clean();//清除缓冲区,避免乱码
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-execl;charset=utf-8");//设置导出格式
        header("Content-Disposition:filename=数据统计记录表--".date('Y-m-d H:i:s',time()).".xls");
        //制作表头
        echo "<table border='1'";
        echo "<tr>";
        echo "<th>".'类目'."</th>";
        echo "<th>".'收益数目'."</th>";
        echo "<th>".'总收益'."</th>";
        echo "<th>".'总收入'."</th>";
        echo "</tr>";

        foreach($data as $k => $v){
            echo "<tr>";
            if($v['type'] == 1){
            	echo "<td>发布悬赏</td>";
            }elseif ($v['type'] == 2) {
            	echo "<td>文章付费</td>";
            }elseif ($v['type'] == 3) {
            	echo "<td>问答付费</td>";
            }elseif ($v['type'] == 4) {
            	echo "<td>赞赏专家</td>";
            }elseif ($v['type'] == 5) {
            	echo "<td>专家服务</td>";
            }
            $total_number = $v['platform_number']+$v['expert_number'];
            echo "<td>平台专家数目：".$v['platform_number']."<br/>其他专家数目：".$v['expert_number']."<br/>合计数目：".$total_number."</td>";
            $total_number = $v['platform_money']+$v['expert_money'];
            echo "<td>平台专家收益总数：".$v['platform_money']."<br/>其他专家收益总数：".$v['expert_money']."<br/>合计收益总数：".$total_number."</td>";
            if($v['type'] == 3){
            	echo "<td>平台收益：".$v['platform']."<br/>专家收益：".$v['expert']."<br/>会员收益：".$v['uses']."</td>";
            }else{
            	echo "<td>平台收益：".$v['platform']."<br/>专家收益：".$v['expert']."</td>";
            }
            echo "</tr>";
        }
        echo "<tr><td>汇总</td>";
        $total_number_h = $total_platform_number + $total_expert_number;
		echo "<td>平台专家总数：".$total_platform_number."<br/>其他专家总数：".$total_expert_number."<br/>合计：".$total_number_h."</td>";
		$total_money_h = $total_platform_money + $total_expert_money;
		echo "<td>平台专家收益总数：".$total_platform_money."<br/>其他专家收益总数：".$total_expert_money."<br/>合计收益总数：".$total_money_h."</td>";
		$hj = $total_platform + $total_expert + $total_user;
		echo "<td>平台总收益：".$total_platform."<br/>专家总收益：".$total_expert."<br/>会员总收益：".$total_user."<br/>合计：".$hj."</td>";
		echo "</tr>";
        echo "</table>";exit;
	}
}

//收益日志
if($op == 'income_log'){
	$starttime = empty($_GPC['time']['start']) ? TIMESTAMP -  86399 * 30 : strtotime($_GPC['time']['start'].' 00:00:00');
	$endtime = empty($_GPC['time']['end']) ? TIMESTAMP+ 86400: strtotime($_GPC['time']['end'].' 23:59:59');
	$keyword = $_GPC['keyword'];
	$tempcondition=" where t.uniacid=:uniacid ";
	$tempArray=array(":uniacid"=>$uniacid);
	if(!empty($starttime)){
		$tempcondition .= " AND t.create_time>=:starttime";
		$tempArray['starttime']=$starttime;
	}
	if(!empty($endtime)){
		$tempcondition .= " AND t.create_time<:endtime";
		$tempArray['endtime']=$endtime;
	}
	if(!empty($_GPC['type']) && $_GPC['type'] != 9){
		$tempcondition .= " AND t.type = :type";
		$tempArray['type'] = $_GPC['type'];
	}
	if(!empty($_GPC['role']) && $_GPC['role'] != 9){
		$tempcondition .= " AND t.role = :role";
		$tempArray['role'] = $_GPC['role'];
	}
	if(!empty($keyword)){
		$tempcondition=$tempcondition." AND s.real_name LIKE '%{$keyword}%' or s.nickname LIKE '%{$keyword}%' ";
	}

	$pindex = max(1, intval($_GPC['page']));
	$psize = 15;
	$records = pdo_fetchall('SELECT t.*,s.real_name,s.nickname FROM '.tablename('chat_profit').' t left join '.tablename('chat_users').' s on t.uid=s.id '. $tempcondition.' ORDER BY t.create_time desc LIMIT '.($pindex-1) * $psize . ','.$psize,$tempArray);

	foreach ($records as $key => $value) {
		if($value['type'] == 1){
			$records[$key]['go_url'] = $this->createWebUrl('view_answer',array('askid'=>$value['type_id'],'type'=>2));
		}
		if($value['type'] == 2){
			$records[$key]['go_url'] = $this->createWebUrl('ask_payment',array('reid'=>$value['type_id'],'op'=>'art_ask'));
		}
		if($value['type'] == 3){
			$records[$key]['go_url'] = $this->createWebUrl('ask_payment',array('reid'=>$value['type_id'],'op'=>'art_ask'));
		}
		if($value['type'] == 4){
			$records[$key]['go_url'] = $this->createWebUrl('ask_payment',array('reid'=>$value['type_id'],'op'=>'zs_manage'));
		}
		if($value['type'] == 5 || $value['type'] == 6 || $value['type'] == 7 || $value['type'] == 8 || $value['type'] == 9){
			$records[$key]['go_url'] = $this->createWebUrl('order',array('oid'=>$value['type_id'],'op'=>'display'));
		}
	}

	$total=pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('chat_profit').' t left join '.tablename('chat_users').' s on t.uid=s.id '. $tempcondition,$tempArray);

	$totalmoney=pdo_fetchcolumn('SELECT SUM(get_price) FROM '.tablename('chat_profit').' t left join '.tablename('chat_users').' s on t.uid=s.id '. $tempcondition,$tempArray);
	$pager = pagination($total, $pindex, $psize);
}

//导出收益日志
if($op == "download"){
	$starttime = empty($_GPC['time']['start']) ? TIMESTAMP -  86399 * 30 : strtotime($_GPC['time']['start'].' 00:00:00');
	$endtime = empty($_GPC['time']['end']) ? TIMESTAMP+ 86400: strtotime($_GPC['time']['end'].' 23:59:59');
	$keyword = $_GPC['keyword'];
	$tempcondition=" where t.uniacid=:uniacid ";
	$tempArray=array(":uniacid"=>$uniacid);
	if(!empty($starttime)){
		$tempcondition .= " AND t.create_time>=:starttime";
		$tempArray['starttime']=$starttime;
	}
	if(!empty($endtime)){
		$tempcondition .= " AND t.create_time<:endtime";
		$tempArray['endtime']=$endtime;
	}
	if(!empty($_GPC['type']) && $_GPC['type'] != 9){
		$tempcondition .= " AND t.type = :type";
		$tempArray['type'] = $_GPC['type'];
	}
	if(!empty($_GPC['role']) && $_GPC['role'] != 9){
		$tempcondition .= " AND t.role = :role";
		$tempArray['role'] = $_GPC['role'];
	}
	if(!empty($keyword)){
		$tempcondition=$tempcondition." AND s.real_name LIKE '%{$keyword}%' ";
	}

	$records = pdo_fetchall('SELECT t.*,s.real_name FROM '.tablename('chat_profit').' t left join '.tablename('chat_users').' s on t.uid=s.id '. $tempcondition,$tempArray);

	//导出
	ob_end_clean();//清除缓冲区,避免乱码
    header("Content-type:application/octet-stream");
    header("Accept-Ranges:bytes");
    header("Content-type:application/vnd.ms-execl;charset=utf-8");//设置导出格式
    header("Content-Disposition:filename=收益数据记录表--".date('Y-m-d H:i:s',time()).".xls");
    //制作表头
    echo "<table border='1'";
    echo "<tr>";
    echo "<th>".'编号'."</th>";
    echo "<th>".'收益类目'."</th>";
    echo "<th>".'收益角色'."</th>";
    echo "<th>".'收益人名称'."</th>";
    echo "<th>".'总金额'."</th>";
    echo "<th>".'收益金额'."</th>";
    echo "<th>".'收益比'."</th>";
    echo "<th>".'添加时间'."</th>";
    echo "</tr>";
    foreach($records as $k => $v){
    	if($v['get_price'] == 0 || $v['total_price'] == 0){
    		$b = 0;
    	}else{
    		$b = round($v['get_price'] / $v['total_price'],2)*100;
    	}
        echo "<tr>";
        echo "<td>".$v['prid']."</td>";
        if($v['type'] == 1){
        	echo "<td>悬赏收益</td>";
        }elseif($v['type'] == 2){
        	echo "<td>文章收益</td>";
        }elseif($v['type'] == 3){
        	echo "<td>问答收益</td>";
        }elseif($v['type'] == 4){
        	echo "<td>赞赏收益</td>";
        }elseif($v['type'] == 5){
        	echo "<td>服务收益</td>";
        }elseif($v['type'] == 6){
        	echo "<td>业务员收益</td>";
        }elseif($v['type'] == 7){
        	echo "<td>合伙人收益</td>";
        }elseif($v['type'] == 8){
        	echo "<td>运营总监收益</td>";
        }
        if($v['role'] == 1){
        	echo "<td>税媒平台</td>";
        }elseif($v['role'] == 2){
        	echo "<td>税媒用户</td>";
        }elseif($v['role'] == 3){
        	echo "<td>普通专家</td>";
        }elseif($v['role'] == 4){
        	echo "<td>平台专家</td>";
        }elseif($v['role'] == 5){
        	echo "<td>平台业务员</td>";
        }elseif($v['role'] == 6){
        	echo "<td>合伙人业务员</td>";
        }elseif($v['role'] == 7){
        	echo "<td>合伙人</td>";
        }elseif($v['role'] == 8){
        	echo "<td>运营老总</td>";
        }
        if($v['role'] == 1){
        	echo "<td>税媒平台</td>";
        }else{
        	echo "<td>".$v['real_name']."</td>";
        }
        echo "<td>".$v['total_price']."</td>";
       	echo "<td>".$v['get_price']."</td>";
       	echo "<td>".$b."%</td>";
        echo "<td>".date('Y-m-d H:i:s',$v['create_time'])."</td>";
        echo "</tr>";
    }
    echo "</table>";exit;
}

include $this->template('ask_summary');
?>