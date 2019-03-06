

<?php
global $_W;
	$uniacid=$_W['uniacid'];

	$orderlist=pdo_fetchall("SELECT A1.*,A2.openid,A2.nickname FROM ". tablename("chat_ask_summary_last")." A1 INNER JOIN ".tablename("chat_users")." A2 ON A1.payto_uid=A2.id WHERE A1.status=1");
	for($i=0;$i<count($orderlist);$i++){
		$order=$orderlist[$i];
		$id=$order["id"];
		$result=$this->pay_cash($order['openid'],$order['last_amount']);
		$status=-1;
		if($result['errno']==0){
			pdo_update("chat_ask_summary_last",array("status"=>2,'pay_time'=>time(),'transaction_id'=>$result['payment_no']),array("id"=>$order['id']));
			$status=1;
		}

		$log_data=array(
				"uniacid"=>$uniacid,
				"openid"=>$order['openid'],
				"nickname"=>$order['nickname'],
				"money"=>$order["last_amount"],
				"status"=>$status,
				"create_time"=>time()
		);
		pdo_insert("chat_reflect_log",$log_data);
	}
	exit();