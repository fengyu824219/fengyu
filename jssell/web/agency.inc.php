<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$uniacid=$_W['uniacid'];
$operation =empty($_GPC['op']) ? "display" :$_GPC['op'];

//待办管理
if($operation == 'display'){
    $agencyData = array();
    $expertGroupMenu = pdo_get('modules_bindings',array('module'=>'dg_ask','do'=>'agent'),array('eid'));
    $orderMenu = pdo_get('modules_bindings',array('module'=>'dg_ask','do'=>'order'),array('eid'));
    $articleMenu = pdo_get('modules_bindings',array('module'=>'seapow_sm','do'=>'article'),array('eid'));
    $commentMenu = pdo_get('modules_bindings',array('module'=>'dg_ask','do'=>'comment'),array('eid'));
    $invoiceMenu = pdo_get('modules_bindings',array('module'=>'dg_ask','do'=>'invoice'),array('eid'));

	//专家认证
    $agencyData[0]['name'] = '专家认证';
    $agencyData[0]['total'] = pdo_fetchcolumn('select count(*) from'.tablename('chat_users').' where uniacid = :uniacid and vstatus = 2 ',array(':uniacid'=>$_W['uniacid']));
    //企业认证
    $agencyData[1]['name'] = '企业认证';
    $agencyData[1]['total'] = pdo_fetchcolumn('select count(*) from'.tablename('chat_qyrz').' where uniacid = :uniacid and status = 1 ',array(':uniacid'=>$_W['uniacid']));

    //专家团
    $agencyData[2]['name'] = '专家团申请';
    $agencyData[2]['total'] = pdo_fetchcolumn('select count(*) from'.tablename('chat_expert_group').' where uniacid = :uniacid and status = 0 ',array(':uniacid'=>$_W['uniacid']));

    //代理人申请
    $agencyData[3]['name'] = '税媒大使申请';
    $agencyData[3]['total'] = pdo_fetchcolumn('select count(*) from'.tablename('chat_user_apply')." where uniacid = :uniacid and is_auditing = 3 and difference = 2 ",array(':uniacid'=>$_W['uniacid']));

    //城市合伙人申请
    $agencyData[4]['name'] = '城市合伙人申请';
    $agencyData[4]['total'] = pdo_fetchcolumn('select count(*) from'.tablename('chat_user_apply')." where uniacid = :uniacid and is_auditing = 3 and difference = 1 ",array(':uniacid'=>$_W['uniacid']));

    //专家自定义服务
    $agencyData[5]['name'] = '专家自定义服务审核';
    $agencyData[5]['total'] = pdo_fetchcolumn('select count(*) from'.tablename('chat_ask_user_service').' where uniacid = :uniacid and status = 3 and s_type = 5 ',array(':uniacid'=>$_W['uniacid']));

    //专家团服务
    $agencyData[6]['name'] = '专家团服务审核';
    $agencyData[6]['total'] = pdo_fetchcolumn('select count(*) from'.tablename('chat_ask_user_service').' where uniacid = :uniacid and status = 3 and s_type = 6 ',array(':uniacid'=>$_W['uniacid']));

    //订单指定
    $agencyData[7]['name'] = '需指派的订单';
    $agencyData[7]['total'] = pdo_fetchcolumn('select count(*) from'.tablename('chat_order')." where uniacid = :uniacid and is_platform = 1 and (status = 2 or status = 9 or status = 10) and (zid = 0 AND invitation_code = 0 AND partner_id = 0 ) ",array(':uniacid'=>$_W['uniacid']));

    //订单转账
    $agencyData[8]['name'] = '银行转账审核';
    $agencyData[8]['total'] = pdo_fetchcolumn('select count(*) from'.tablename('chat_order')." where uniacid = :uniacid and status = 1 and pay_type = 'yh' ",array(':uniacid'=>$_W['uniacid']));

    //提现
    $agencyData[9]['name'] = '提现申请';
    $agencyData[9]['total'] = pdo_fetchcolumn('select count(*) from'.tablename('chat_ask_summary_last')." where uniacid = :uniacid and status = 1 ",array(':uniacid'=>$_W['uniacid']));
    
    //开具发票
    $agencyData[10]['name'] = '发票申请';
    $agencyData[10]['total'] = pdo_fetchcolumn('select count(*) from'.tablename('chat_invoice_log')." where uniacid = :uniacid and status = 1 ",array(':uniacid'=>$_W['uniacid']));

     //文章
    $agencyData[11]['name'] = '文章审核';
    $agencyData[11]['total'] = pdo_fetchcolumn('select count(*) from'.tablename('chat_article_article').' where uniacid = :uniacid and type = 2 and state = 1 ',array(':uniacid'=>$_W['uniacid']));

    //评论
    $agencyData[12]['name'] = '评论审核';
    $agencyData[12]['total'] = pdo_fetchcolumn('select count(*) from'.tablename('chat_article_ask_comment').' where uniacid = :uniacid and is_show = 0 and pid = 0 ',array(':uniacid'=>$_W['uniacid']));
}

include $this->template('agency');
?>