<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$uniacid=$_W['uniacid'];
$askid=$_GPC["askid"];
$type = $_GPC['type'];
$pindex = max(1, intval($_GPC['page']));
$psize = 20;

if($_GPC['cl_ajax'] == 'cl'){
	$score = 10;
    $zuid  = $_GPC['zuid'];//专家UID
    $money = $_GPC['money'];//悬赏金额
    $answer = $_GPC['answer'];//采纳答案ID
    $ask_id = $_GPC['ask_id'];//问答ID
    $pay_uid = $_GPC['pay_uid'];//悬赏人
    $reward_title = pdo_get('chat_ask',['id'=>$ask_id]); //问题信息
    if($reward_title['reward_status']==1){
        echo 3;exit;
    }

    $answer_content = pdo_get('chat_ask_answer',['ask_id'=>$ask_id]); //回答信息
    $user = pdo_get('chat_users',['id'=>$pay_uid]);//用户信息
    $user['real_name'] = $user['real_name'] ? $user['real_name'] : $user['nickname'];
    $zj_title = pdo_get("chat_users",["id"=>$zuid]); //专家信息
    $zj_title['real_name'] = $zj_title['real_name'] ? $zj_title['real_name'] : $zj_title['nickname'];

    if($answer_content['user_type']==1){ //快捷回答
        $answer_title = $answer_content['answer_wenzi'];
    }else if($reward_title['user_type'] ==2){ //专业回答
         $answer_title = $answer_content['answer_content_down'];
    }
    //回答字数超过30字省略号代替
    if (strlen($answer_title)>90) {
        $answer_title = substr($answer_title,0,90) . '...';
    }

   if($reward_title['ask_type']==1){//问答
         $op = "&op=ans_details&ask_id=".$ask_id;
         $rewa = 5;
   }else if($reward_title['ask_type']==2){//悬赏
         $op = "&op=questions_to&id=".$ask_id . "&status=1";
         $rewa = 1;


   }

       $ask_ret = pdo_update("chat_ask",array("reward_overtime"=>time(),"payto_uid"=>$zuid,'reward_status'=>1,"is_cana"=>2),['id'=>$ask_id]);
       $order = pdo_get('chat_order',array('order_sn'=>$reward_title['out_trade_no']));
       //修改悬赏为已结束
       if($ask_ret){
            $this->add_integral($zuid,12,$ask_id);//添加积分
            //修改问答 评分
            $ans_ret = pdo_update("chat_ask_answer",array("score"=>$score,"status"=>1),["answer_uid"=>$zuid,"ask_id"=>$ask_id]);

               if($ans_ret){
                    $this->add_profit($rewa,$ask_id, $money,$zuid,$pay_uid);
                    //模版消息
                    $weObj = WeAccount::create($_W['uniacid']);
                    //发给专家
                    $send_data = array(
                        'first'=>array('value'=>'恭喜您的回答被采纳'),
                        'ask'=>array('value'=>$reward_title['ask_content'],'color'=>'#4a5077'),
                        'user'=>array('value'=>$zj_title['real_name'],'color'=>'#4a5077'),
                        'answer'=>array('value'=>$answer_title,'color'=>'#4a5077'),
                        'remark'=>array('value'=>'点击这里继续帮助更多人','color'=>'#173177'),
                    );
                    $url = $_W['siteroot'] .'app/'. $this->createMobileUrl('ask_index',array('goto'=>'add'));
                    $weObj->sendTplNotice($zj_title['openid'], 'numVqD_uNuBQRgz0YBTYEsAkLpV8RGY58d5vpFvClUQ', $send_data, $url, '#173177');

                    //发给用户
                    $send_data = array(
                        'first'=>array('value'=>'由于您在指定时间内未进行采纳，平台已为您匹配了最佳答案'),
                        'keyword1'=>array('value'=>$user['real_name'],'color'=>'#4a5077'),
                        'keyword2'=>array('value'=>date('Y-m-d H:i:s',$reward_title['create_time']),'color'=>'#4a5077'),
                        'keyword3'=>array('value'=>'￥'.$reward_title['pay_money'],'color'=>'#4a5077'),
                        'remark'=>array('value'=>"回答内容：".$answer_title."\r\n点击查看详情",'color'=>'#173177'),
                    );
                    $url = $_W['siteroot'] .'app/'. $this->createMobileUrl('ask_chat_reward',array('op'=>'questions_to','id'=>$reward_title['id'],'from'=>'template'));
                    //App 消息
                    $push_data = array(
                        'type'=>"link",
                        'title'=>'悬赏通知',
                        'content'=> "由于您在指定时间内未进行采纳，平台已为您匹配了最佳答案",
                        'url'=>$this->createMobileUrl('ask_chat_reward',array('op'=>'questions_to','id'=>$reward_title['id'],'from'=>'template'))
                    );
                    $this->pushMessageToSingle($user['id'],$push_data);

                    //App 消息
                    $push_data = array(
                        'type'=>"link",
                        'title'=>'悬赏通知',
                        'content'=> "恭喜你,你回答的问题被采纳了",
                        'url'=>$this->createMobileUrl('ask_chat_reward',array('op'=>'questions_to','id'=>$reward_title['id'],'from'=>'template'))
                    );
                    $this->pushMessageToSingle($zj_title['id'],$push_data);

                    $weObj->sendTplNotice($user['openid'], '4JpIlwfCzcjWpIC6kee1U3m0TEd49BEdh2HA75_gVtU', $send_data, $url, '#173177');
                    echo 2;exit;
               }
       }
}

if($askid){
    $page_num = $_GPC['page_num'];
    $ks = pdo_get("chat_ask",["id"=>$askid]);

     	$pages = ceil($total / $psize);
	    if($pindex>$pages&&$pages>0)
		$pindex =$pages;
     	$total = pdo_fetch("SELECT COUNT(*) AS num FROM " . tablename("chat_ask_answer") . " N left join " . tablename("chat_users") . " u ON N.answer_uid=u.id WHERE N.ask_id=" .$askid);
     	$res = pdo_fetchall("SELECT N.status,N.answer_imgs,N.score,N.answer_content,N.answer_content,N.answer_problem_refining,N.policy_basis,N.expert_conclusion,N.answer_content_down,N.user_type,N.user_type,N.answer_uid,N.id as answer_id,N.ask_id as nid,N.answer_wenzi,N.create_time,N.time_last,u.id as uid,u.avatar,u.real_name,u.nickname,u.duties,u.unionid,u.level FROM " . tablename("chat_ask_answer") . " N left join " . tablename("chat_users") . " u ON N.answer_uid=u.id WHERE N.ask_id=" .$askid . ' LIMIT '.($pindex - 1) * $psize .',' .$psize.'');

     	$pager = pagination($total['num'], $pindex, $psize);

	//追问  问题和答案
	    foreach ($res as $k=>$v){
            $res[$k]['wen'] = pdo_fetchall("SELECT N.id as Nsid,A.id as ask_id,A.create_time as Atime,A.ask_content,N.answer_wenzi,N.create_time as an_time FROM ".tablename('chat_ask') . ' A LEFT JOIN ' . tablename('chat_ask_answer') . ' N  on N.ask_id=A.id WHERE A.parent_id = :parent_id AND A.payto_uid = :payto_uid' . ' ORDER BY A.create_time asc ',array(':parent_id'=>$v['nid'],':payto_uid'=>$v["uid"]));
	    }
}

if($_GPC['cl_ajax'] == 'answer_update'){//修改已采纳的回答
    if(!empty($_GPC['answer_id'])){
        if(!empty($_GPC['answer_wenzi'])){//快捷回答
            $answer_data = array('answer_wenzi'=>$_GPC['answer_wenzi']);
        }

        if(!empty($_GPC['answer_problem_refining']) && !empty($_GPC['answer_content_down']) && !empty($_GPC['policy_basis']) && !empty($_GPC['expert_conclusion'])){//专家回答
            $answer_data = array(
                'answer_problem_refining' => $_GPC['answer_problem_refining'],
                'answer_content_down' => $_GPC['answer_content_down'],
                'policy_basis' => $_GPC['policy_basis'],
                'expert_conclusion' => $_GPC['expert_conclusion']
            );

        }

        if(!empty($_GPC['inquiriesAnswer'])){//追问的回答
            $answer_data = array('answer_wenzi'=>$_GPC['inquiriesAnswer']);
        }

        $answer_update = pdo_update('chat_ask_answer',$answer_data,array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['answer_id']));
        if($answer_update){
            $update_answer_data = array(
                'uniacid' => $_W['uniacid'],
                'uid' => $_W['user']['uid'],
                'ask_id' => $_GPC['ask_id'],
                'answer_id' => $_GPC['answer_id'],
                'username' => $_W['user']['username'],
                'create_time' =>time()
            );
            pdo_insert('chat_update_answer',$update_answer_data);//记录修改回答日志
            echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
        }else{
            echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
        }
    }
}

include $this->template('view_answer');
?>