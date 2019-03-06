<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$uniacid=$_W['uniacid'];



$share=pdo_fetch("SELECT * FROM ".tablename("chat_ask_share")." WHERE uniacid=:uniacid",array(":uniacid"=>$uniacid));
if(checksubmit()){

	empty($_GPC['share_title']) && itoast('请填写分享标题');
	empty($_GPC['share_img']) && itoast('请填写分享图片');
	empty($_GPC['share_desc']) && itoast('请填写分享描述');

	$data=array(
			"uniacid"=>$uniacid,
			"share_title"=>preg_replace("/\s/","",$_GPC['share_title']),
			"share_img"=>$_GPC['share_img'],
			"share_desc"=>preg_replace("/\s/","",$_GPC['share_desc'])
	);

	if(empty($share)){
		$data['create_time']=time();
	    pdo_insert("chat_ask_share",$data);
	}else {
		pdo_update("chat_ask_share",$data,array("id"=>$share['id']));
	}
	itoast("更新成功！",$this->createWebUrl('ask_share'),"success");
}
include $this->template('ask_share');
?>