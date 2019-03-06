<?php
global $_W;
	$uniacid=$_W['uniacid'];

	$orderlist=pdo_fetchall("SELECT * FROM ".tablename("chat_ask")." WHERE is_answer=2");
	for($i=0;$i<count($orderlist);$i++){
		$order=$orderlist[$i];
		$id=$order["id"];
		$result=$this->refund($order);
		$is_success=$result['return_code']=='SUCCESS';
		$status=-1;
		if($is_success){
			/*更新状态为已退款*/
			pdo_update("chat_ask",array("is_answer"=>-2,"refund_time"=>time()),array("id"=>$id));
			$status=1;
		}
		$log_data=array(
				"uniacid"=>$uniacid,
				"ask_id"=>$id,
				"money"=>$order["pay_money"],
				"status"=>$status,
				"create_time"=>time()
		);
		pdo_insert("chat_refund_log",$log_data);
	}
	exit();