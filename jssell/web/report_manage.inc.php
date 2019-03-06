<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$uniacid=$_W['uniacid'];
$op = empty($_GPC['op']) ? 'display' : $_GPC['op'];
if($op == 'display'){
	$starttime = empty($_GPC['time']['start']) ? TIMESTAMP -  86399 * 30 : strtotime($_GPC['time']['start']);
	$endtime = empty($_GPC['time']['end']) ? TIMESTAMP + 86400: strtotime($_GPC['time']['end']);

	$tempcondition=" AND A1.uniacid=".$uniacid;

	$tempArray=array();
	if(!empty($starttime)){
		$tempcondition=$tempcondition." AND A1.create_time>=:starttime";
		$tempArray['starttime']=$starttime;
	}

	if(!empty($endtime)){
		$tempcondition=$tempcondition." AND A1.create_time<:endtime";
		$tempArray['endtime']=$endtime;
	}

	$keyword=$_GPC['keyword'];
	if(!empty($keyword)){
		$tempcondition=$tempcondition." AND (A2.nickname LIKE '%{$keyword}%' or A1.content like '%{$keyword}%' or A1.type_content like '%{$keyword}%') ";
	}


	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;
	$total = pdo_fetchcolumn("SELECT COUNT(0) FROM ".tablename("chat_ask_report")." A1 LEFT JOIN ".tablename("chat_users")." A2 ON 
	A1.uid=A2.id WHERE 1=1 ".$tempcondition,$tempArray);
	$pages = ceil($total / $psize);
	if($pindex>$pages&&$pages>0)
		$pindex =$pages;

	$records=pdo_fetchall("SELECT A1.*,A2.nickname,A2.avatar,(select ask_content from ".tablename("chat_ask")." as ca where ca.id=A1.ask_id) as ask_content FROM ".tablename("chat_ask_report")." A1 LEFT JOIN ".tablename("chat_users")." A2 ON 
	A1.uid=A2.id WHERE 1=1 ".$tempcondition." ORDER BY A1.id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$tempArray);

	$pager = pagination($total, $pindex, $psize);
}

include $this->template('report_manage');

?>