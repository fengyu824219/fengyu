<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$uniacid=$_W['uniacid'];
$talkid=$_GPC["talkid"];

$pindex = max(1, intval($_GPC['page']));
$psize = 20;
$total = pdo_fetchcolumn("SELECT COUNT(0) FROM ".tablename("chat_talk_answer")." where talk_id=:talkid and uniacid=:uniacid",array(":talkid"=>$talkid,":uniacid"=>$uniacid));
$pages = ceil($total / $psize);
if($pindex>$pages&&$pages>0)
	$pindex =$pages;

$records=pdo_fetchall("SELECT *,(SELECT u.avatar FROM ".tablename("chat_users")." AS u WHERE u.id=a.answer_uid) AS avatar,(SELECT u.real_name FROM ".tablename("chat_users")." AS u WHERE u.id=a.answer_uid) AS real_name,(SELECT u.nickname FROM ".tablename("chat_users")." AS u WHERE u.id=a.answer_uid) AS nickname
FROM ".tablename("chat_talk_answer")." AS a where talk_id=:talkid and uniacid=:uniacid",array(":talkid"=>$talkid,":uniacid"=>$uniacid));
foreach($records as &$mrecord){
	if(!empty($mrecord['answer_content_down'])){
		$mrecord['answer_content']=$this->to_qiniu_url($mrecord['answer_content_down']);
	}
	unset($mrecord);
}
$type=$_GPC["type"];
include $this->template('talk_view_answer');
?>