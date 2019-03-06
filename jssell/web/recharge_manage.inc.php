<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$uniacid=$_W['uniacid'];

$starttime = empty($_GPC['time']['start']) ? TIMESTAMP -  86399 * 30 : strtotime($_GPC['time']['start']);
$endtime = empty($_GPC['time']['end']) ? TIMESTAMP + 86400: strtotime($_GPC['time']['end']);

$tempcondition=" AND A1.uniacid=".$uniacid;

$tempArray=array();
if(!empty($starttime)){
	$tempcondition=$tempcondition." AND A1.time>=:starttime";
	$tempArray['starttime']=$starttime;
}

if(!empty($endtime)){
	$tempcondition=$tempcondition." AND A1.time<:endtime";
	$tempArray['endtime']=$endtime;
}

$keyword=$_GPC['keyword'];
if(!empty($keyword)){
	$tempcondition=$tempcondition." AND (A2.nickname LIKE '%{$keyword}%' or A2.real_name like '%{$keyword}%') ";
}

$pindex = max(1, intval($_GPC['page']));
$psize = 20;
$total = pdo_fetchcolumn("SELECT COUNT(0) FROM ".tablename("chat_recharge")." A1 LEFT JOIN ".tablename("chat_users")." A2 ON 
A1.openid=A2.openid WHERE pay_status=1 ".$tempcondition,$tempArray);
$pages = ceil($total / $psize);
if($pindex>$pages&&$pages>0)
	$pindex =$pages;

$records=pdo_fetchall("SELECT A1.*,A2.nickname,A2.real_name,A2.user_title,A2.avatar FROM ".tablename("chat_recharge")." A1 LEFT JOIN ".tablename("chat_users")." A2 ON 
A1.openid=A2.openid WHERE pay_status=1 ".$tempcondition." ORDER BY A1.id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$tempArray);

foreach($records as &$t_record){
	if($t_record['pay_status']=='1'){
		  $t_record['pay_status']="已支付";
	}
}


$pager = pagination($total, $pindex, $psize);

include $this->template('recharge_manage');

?>