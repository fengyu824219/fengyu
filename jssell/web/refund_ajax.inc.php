<?php
global $_GPC, $_W;
checklogin();
$uniacid=$_W['uniacid'];

if($_GPC['id']!=""){
	$id=intval($_GPC['id']);
	$order=pdo_fetch("SELECT * FROM ".tablename("chat_ask")." WHERE id=:id",array(":id"=>$id));
	if(empty($order)){
		exit;
	}

	/** 
		$result=$this->refund($order);
		$is_success=$result['return_code']=='SUCCESS'; 
		if($is_success){
			pdo_update("chat_ask",array("is_answer"=>-2,"refund_time"=>time()),array("id"=>$id));
		}
	*/
	//$result=$this->pay_cash($order['pay_openid'],$order['pay_money'],'税媒退款');
	
	$result=pdo_update('chat_users',array('balance +='=>$order['pay_money']),array('openid'=>$order['pay_openid']));
	
	/*更新状态为已退款*/
    if(strlen(mysql_error())==0){
        pdo_update("chat_ask",array("is_answer"=>-2,"refund_time"=>time()),array("id"=>$id));
		$fmdata=array(
			"success"=>0,
			"data"=>'退款成功',
			"id"=>$id
		);
	}else{
       $fmdata=array(
			"success"=>1,
			"data"=>'退款失败',
			"id"=>$id
		);
    }
	
	header('content-type:application/json;charset=utf8');
	echo json_encode($fmdata);
	exit();
}

?>