<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$key_path=MODULE_ROOT .'/cert/apiclient_key_'.$_W['uniacid'].'.pem';
$cert_path=MODULE_ROOT .'/cert/apiclient_cert_'.$_W['uniacid'].'.pem';

$wechat['signcertexists']=file_exists($cert_path);
$wechat['signkeyexists']=file_exists($cert_path);


if($_W['ispost']){
    $signcertpath = $_FILES['wechat']['tmp_name']['signcertpath'];
	$signkeypath = $_FILES['wechat']['tmp_name']['signkeypath'];
	
	if (empty($signcertpath) && !file_exists($cert_path)) {
		exit('请上传微信支付CERT密钥(pem格式)');
	}
	if (empty($signkeypath) && !file_exists($key_path)) {
		exit('请上传微信支付KEY密钥(pem格式)');
	}
	
	//load()->func('file');
	//$result=mkdirs(MODULE_ROOT.'/cert/');
	// if(!$result){
		// exit("系统没有对本插件文件夹的写权限");
	// }
	move_uploaded_file($signcertpath,$cert_path);
	move_uploaded_file($signkeypath,$key_path);

    exit("上传成功！");
}
include $this->template('pay_setting');
?>