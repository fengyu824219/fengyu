<?php

//decode by http://www.yunlu99.com/
global $_GPC, $_W;
checklogin();
$uniacid = $_W['uniacid'];
load()->func('tpl');
$operation = !empty($_GPC['op']) ? $_GPC['op'] : 'display';
if ($operation == 'display') {
	$list = pdo_fetchall("SELECT * FROM " . tablename($this->table_usertags) . " WHERE uniacid = '{$uniacid}' ORDER BY displayorder DESC");
} elseif ($operation == 'post') {
	$id = intval($_GPC['id']);
	if (checksubmit('submit')) {
		$data = array('uniacid' => $uniacid, 'tag_name' => $_GPC['tag_name'], 'enabled' => intval($_GPC['enabled']), 'displayorder' => intval($_GPC['displayorder']), 'create_time' => time(), 'image'=>$_GPC['image']);
		if (!empty($id)) {
			pdo_update($this->table_usertags, $data, array('id' => $id));
		} else {
			pdo_insert($this->table_usertags, $data);
			$id = pdo_insertid();
		}
		itoast('更新标签成功！', $this->createWebUrl('user_tags', array('op' => 'display')), 'success');
	}
	$banner = pdo_fetch("select * from " . tablename($this->table_usertags) . " where id=:id and uniacid=:uniacid limit 1", array(":id" => $id, ":uniacid" => $uniacid));
} elseif ($operation == 'delete') {
	$id = intval($_GPC['id']);
	$banner = pdo_fetch("SELECT id  FROM " . tablename($this->table_usertags) . " WHERE id = '$id' AND uniacid=" . $uniacid);
	if (empty($banner)) {
		itoast('抱歉，标签不存在或是已经被删除！', $this->createWebUrl('user_tags', array('op' => 'display')), 'error');
	}
	pdo_delete($this->table_usertags, array('id' => $id));
	itoast('标签删除成功！', $this->createWebUrl('user_tags', array('op' => 'display')), 'success');
} elseif($operation=='execution_settlement'){
        if (checksubmit('submit')) {
            $order_sn = 0;
        	$order_sn = $_GPC['order_sn'];
        	if(empty($order_sn)){
                itoast('订单不能为空');
        	}
        	$sql = 'SELECT id FROM '.tablename('chat_order').' WHERE `order_sn`=:order_sn AND `uniacid`=:uniacid AND `status` IN("2","5","7","9","10") AND `is_maid`=:is_maid ';
        	$pars[':order_sn'] = $order_sn;
        	$pars[':uniacid'] = $uniacid;
        	$pars[':is_maid'] = 1;
        	$orderResult = pdo_fetch($sql,$pars);
        	if($orderResult['id']<=0){
                itoast('未查询到该订单的信息或此订单不是分期订单');
        	}else{
        		$this->domobilesettlement($orderResult['id']);
                itoast('操作成功');
        	}

        }
	}else {
		itoast('请求方式不存在');
}
include $this->template('user_tags');