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

	$cfg=$this->module['config'];
	$ask_templete=$cfg['answer_templete'];
	
	$user_openid=$order['pay_openid'];
	if(empty($ask_templete)){
		return false;
	}
	
	$post_data=array(
			'first' => array(
					'value' => "您的悬赏已经到期，请即时选标",
					"color" => "#4a5077"
			),
			'keyword1' => array(
					'value' => $order['ask_content'],
					"color" => "#4a5077"
			),
			'keyword2' => array(
					'value' => date('Y/m/d H:i:s', $order['pay_time']),
					"color" => "#4a5077"
			),
			'keyword3' => array(
					'value' => '悬赏',
					"color" => "#4a5077"
			),
			'keyword4' => array(
					'value' => date('Y/m/d H:i:s', time()),
					"color" => "#4a5077"
			),
			'remark' => array(
					'value' => "\r\n您为这个问题已经悬赏了".$order['pay_money']."元,点击开始选标！",
					"color" => "#09BB07"
			)
	);

	$url=$this->get_normal_url('reward_detail',array("ask_id"=>$id));
	
	$sendResult=$this->sendTplNotice($user_openid,$ask_templete,$post_data,$url);
	
	$fmdata=array(
			"success"=>1,
			"result"=>$sendResult,
			"data"=>"发送成功！"
	);
	header('content-type:application/json;charset=utf8');
	echo json_encode($fmdata);
	exit();
}

?>