<?php
date_default_timezone_set('Asia/Shanghai');//'Asia/Shanghai'   亚洲/上海
global $_GPC, $_W;
load()->func("logging");
ignore_user_abort();//关闭浏览器后，继续执行php代码
set_time_limit(0);//程序执行时间无限制
$uniacid = $_W['uniacid'];
$runing=cache_load("dg:runing");
$cfg=$this->module['config'];
$ask_templete=$cfg['answer_templete'];


$task_list=array();
$task_list['send_notice']=array(
		"run_time"=>"9",
		"last_run_time"=>0,
);



/*发送未选标提醒*/
function send_notice(){
  $ask_list=pdo_fetchall("SELECT * FROM ".tablename("chat_ask")." WHERE ask_type='reward' 
         AND reward_expirytime<UNIX_TIMESTAMP() AND payto_uid=0 AND pay_time>0 AND id=622 ");
  logging_run("进来啦——11");
  foreach ($ask_list as $list){
  	if($list['is_answer']==1){
  		$user_openid=$list['pay_openid']; 		
  		if(empty($ask_templete)){
  			return false;
  		}
  		
  		$post_data=array(
  				'first' => array(
  						'value' => "您的悬赏已经到期，请即时选标",
  						"color" => "#4a5077"
  				),
  				'keyword1' => array(
  						'value' => $list['ask_content'],
  						"color" => "#4a5077"
  				),
  				'keyword2' => array(
  						'value' => date('Y/m/d H:i:s', $list['pay_time']),
  						"color" => "#4a5077"
  				),
  				'keyword3' => array(
  						'value' => $list['ask_type']=='悬赏',
  						"color" => "#4a5077"
  				),
  				'keyword4' => array(
  						'value' => date('Y/m/d H:i:s', time()),
  						"color" => "#4a5077"
  				),
  				'remark' => array(
  						'value' => "\r\n您为这个问题已经悬赏了".$record['pay_money']."元,点击开始选标！",
  						"color" => "#09BB07"
  				)
  		);
  		logging_run("进来啦——12");
  		$url=$this->get_normal_url('reward_detail',array("ask_id"=>$ask_id));
  		
  		$Account = WeAccount::create($ask_list['uniacid']);
  		$Account->sendTplNotice($user_openid,$ask_templete,$postdata,$url,'#FF683F'); 
        
  		$data=array(
  				"uniacid"=>$list['uniacid'],
  				"log_type"=>"send_notice",
  				"content"=>"已提醒 ".$list['pay_nickname']." 选标！",
  				"create_time"=>time()
  		);
  		pdo_insert("chat_ask_logs",$data);
  	}  	
  }
  
  $task_list['send_notice']['last_run_time']=time();
  
}



if(empty($runing)){
	echo "isruning";
	exit;
}
/*如果正在运行，直接终止 防止多次调用*/
if($runing['runing']==true){
	echo "isruning";
	exit;
}




$sleep_time = 20;//多长时间执行一次
$i=1;

while(true){
    $val = pdo_fetchcolumn("SELECT `value` FROM " . tablename('core_cache') . " WHERE `key`='dg:autorun'");
    $is_process=iunserializer($val);
	if($is_process==1){
		cache_write("dg:runing", array("runing"=>true,"time"=>time()));
		
		$hour=intval(date("H"));
		
		foreach ($task_list as $key=>$CurrentTask){
			switch ($key){
				case "send_notice":
					if($hour>$CurrentTask['run_time']&&$CurrentTask['last_run_time']==0)
					   send_notice();
				    logging_run("进来啦——31");
					break;
			}
		}
				
		sleep($sleep_time);//等待时间，进行下一次操作。
		ob_flush();
		flush();
		$i++;
	}else{
		cache_delete("dg:runing");
		exit;
	}
}

cache_delete("dg:runing");
exit();
