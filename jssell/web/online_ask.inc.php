<?php
global $_GPC, $_W;
$tempcondition=" uniacid = '{$_W['uniacid']}'";
$uniacid=$_W['uniacid'];
$pay_status=$_GPC['status'];
$starttime = empty($_GPC['time']['start']) ? TIMESTAMP -  86399 * 6 : strtotime($_GPC['time']['start']);
$endtime = empty($_GPC['time']['end']) ? TIMESTAMP + 86400: strtotime($_GPC['time']['end']);

if(!empty($_GPC['id'])){
	$id = intval($_GPC['id']);
	$online=pdo_get('chat_ask_online',array("id"=>$id,'uniacid'=>$uniacid));

	if(!empty($online)){
		$ret = pdo_update("chat_ask_online",array("pay_status"=>2,"pay_time"=>time(),'end_time'=>0),array("id"=>$online["id"]));
		if(empty($ret)){
			$fmdata = array(
				"success" => 1,
				"msg" => '操作失败',
			);
		}else{
			$ret = pdo_update("chat_users",array("pay_status"=>2,'pay_time'=>time(),'balance +='=>$balance),array("id"=>$user['id']));
			$fmdata = array(
				"success" => 0,
				"msg" => '操作成功',
			);
		}
		header('content-type:application/json;charset=utf8');
		echo json_encode($fmdata);
	}
	exit();
}

$tempArray=array();
if(!empty($pay_status)){
	$tempcondition=$tempcondition." AND pay_type=:status";
	$tempArray['pay_type']=$pay_status;
}

if(!empty($starttime)){
	$tempcondition=$tempcondition." AND pay_time>=:starttime";
	$tempArray['starttime']=$starttime;
}

if(!empty($endtime)){
	$tempcondition=$tempcondition." AND pay_time<:endtime";
	$tempArray['endtime']=$endtime;
}
$pindex = max(1, intval($_GPC['page']));
$psize = 20;
$records = pdo_fetchall("SELECT *,(select nickname from ".tablename("chat_users")." where id = A.payto_uid) as pay_tonick FROM ".tablename("chat_ask_online").' as A where '.$tempcondition." ORDER BY id desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$tempArray);

$total = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename("chat_ask_online"). $tempcondition,$tempArray);
$pager = pagination($total, $pindex, $psize);
include $this->template('online_ask');
?>