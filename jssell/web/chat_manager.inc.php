<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$uniacid=$_W['uniacid'];

if(!empty($_GPC['keyword'])){
	$keyword=$_GPC['keyword'];
	$condition="uniacid=".$uniacid." AND (nickname LIKE '%".$keyword."%' OR real_name LIKE '%".$keyword."%')";
	$records=pdo_fetchall("SELECT id,nickname,avatar,real_name FROM ".tablename("chat_users")." WHERE ".$condition." LIMIT 10");
	header('content-type:application/json;charset=utf8');

	$fmdata = array(
			"success" => 1,
			"data" =>$records,
	);

	echo json_encode($fmdata);
	exit;
}

if(!empty($_GPC['uid'])){
	$uid=intval($_GPC['uid']);
	$data=array(
			"uniacid"=>$uniacid,
			"uid"=>$uid,
			"create_time"=>time()
	);

	pdo_insert("chat_admin",$data);

	header('content-type:application/json;charset=utf8');

	$fmdata = array(
			"success" => 1,
			"data" =>$records,
	);

	echo json_encode($fmdata);
	exit;
}

if(!empty($_GPC['del'])){
	$uid=intval($_GPC['duid']);

	pdo_delete("chat_admin",array("uniacid"=>$uniacid,"uid"=>$uid));

	header('content-type:application/json;charset=utf8');

	$fmdata = array(
			"success" => 1,
			"data" =>"删除成功",
	);

	echo json_encode($fmdata);
	exit;
}

$pindex = max(1, intval($_GPC['page']));
$psize = 20;
$tempcondition="WHERE A1.uniacid=".$uniacid;
$records = pdo_fetchall("SELECT A1.id,A1.nickname,A1.real_name,A1.avatar,A2.create_time FROM ".tablename("chat_users")." A1 INNER JOIN ".tablename("chat_admin")." A2 ON A1.id=A2.uid ".  $tempcondition." ORDER BY A2.id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);

$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename("chat_admin")." A1 " . $tempcondition);

$pager = pagination($total, $pindex, $psize);


include $this->template('chat_manager');
?>