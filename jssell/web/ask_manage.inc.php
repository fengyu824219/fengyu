<?php

//decode by http://www.yunlu99.com/
global $_GPC, $_W;
checklogin();
$uniacid    = $_W['uniacid'];
$is_openask = -1;
load()->func('tpl');
$op = !empty($_GPC['op']) ? $_GPC['op'] : 'display';

if (!empty($_GPC['id'])) {
    $user_id = intval($_GPC['id']);
    $ask_user = pdo_fetch("SELECT is_openask,is_recommend FROM " . tablename("chat_users") . " WHERE id=:id", array(":id" => $user_id));
    if ($op == 'recommend') {
        header('content-type:application/json;charset=utf8');
        $is_recommend = $ask_user['is_recommend'];
        if ($is_recommend == "0") {
            $is_recommend = 1;
        } else {
            $is_recommend = 0;
        }

        pdo_update("chat_users", array('is_recommend' => $is_recommend), array("id" => $user_id));
        $fmdata = array("success" => 1, "data" => $is_recommend);
        echo json_encode($fmdata);
        exit;
    } elseif ($op == 'shut') {
        if ($_GPC['is_openask'] == 1) {
            $is_openask = 0;
            $status = "下线";
            $title = "您暂时无法回答问题或提供专家服务，如有疑惑，请咨询客服";
        } else if ($_GPC['is_openask'] == 0) {
            $is_openask = 1;
            $status = "上线";
            $title = "您可回答悬赏或提供专家服务赚取收益";
            $op = "user_fuwu";
        }

        $user_update = pdo_update("chat_users", array('is_openask' => $is_openask), array("id" =>$_GPC['id']));
        if($user_update){
            $expertUser = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
            $weObj = WeAccount::create($_W['uniacid']);
            $send_data = array(
                'first'=>array('value'=>'您好，您的账号状态已变更'),
                'keyword1'=>array('value'=>($expertUser['real_name'] ? $expertUser['real_name'] : $expertUser['nickname']).'（'.$expertUser['mobile'].'）'),
                'keyword2'=>array('value'=>$status),
                'keyword3'=>array('value'=>date('Y-m-d H:i',time())),
                'remark'=>array('value'=> $title),
            );
            $url = $_W['siteroot'] . $this->createMobileUrl('my_chat',array('from'=>'template',array("op"=>$op)));
            $weObj->sendTplNotice($expertUser['openid'], 'q2TEP-QEoIcIOqmrghTu8SMhDS2zkSRHhqPMydTc5Rs', $send_data, $url, '#173177');
			$push_data = array(
				'type'=>"link",
				'title'=>"您好，您的账号状态已变更",
				'content'=>"您的帐号变成" . $status . "状态",
				'url'=>""
			);
			$this->pushMessageToSingle($_GPC['id'],$push_data);
            echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
        }else{
            echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
        }
    } elseif ($op == 'edit_manage') {
        $id = intval($_GPC['id']);
        if (checksubmit('submit')) {
            $card_img = implode(',',$_GPC['card_img']);//身份证图片
            $work_proof_img = implode(',',$_GPC['work_proof_img']);//工作证明图片
            $real_education = implode(',',$_GPC['real_education']);//学历证书图片
            $real_certificate = implode(',',$_GPC['real_certificate']);//资格证书图片
            if($_GPC['level_id'] == 1){
                $expertLevel = '中级专家';
            }else if($_GPC['level_id'] == 2){
                $expertLevel = '高级专家';
            }else if($_GPC['level_id'] == 3){
                $expertLevel = '资深专家';
            }else if($_GPC['level_id'] == 4){
                $expertLevel = '大师级专家';
            }
            $data = array(
                'mobile' => $_GPC['mobile'],//手机号
                'mailbox' => $_GPC['mailbox'],//邮箱
                'real_name' => $_GPC['real_name'],//真实姓名
                'card_img' => $card_img,//身份证图片
                'avatar' => $_GPC['avatar'],//头像图片
                'user_unit' => $_GPC['user_unit'],//工作单位
                'duties' => $_GPC['duties'],//职务
                'unit_address' => $_GPC['unit_address'],//单位地址
                'work_proof_img' => $work_proof_img,//工作证明图片
                'real_education' => $real_education,//学历证书图片
                'real_certificate' => $real_certificate,//资格证图片
                'certs' => $_GPC['certs'],//资格证标签
                'working_life' => $_GPC['working_life'],//工作年限
                'professional_title' => $_GPC['professional_title'],//专业职称
                'user_desc' => $_GPC['user_desc'],//自我介绍
                'level_id' => $_GPC['level_id'],//专家等级Id
                'level' => $expertLevel,//专家等级
                'remarks' => $_GPC['remarks'],//备注
            );
            $userUpdata = pdo_update("chat_users", $data, array('id' => $id));
            if(!empty($userUpdata)){
                $userData = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$id),array('level'));
                if($_GPC['level'] != $userData['level']){//判断是否更改专家等级
                    $userServiceLog = pdo_fetchall('select * from'.tablename('chat_ask_user_service_log').' where uniacid = :uniacid and uid = :uid and is_del = 0 ',array(':uniacid'=>$_W['uniacid'],':uid'=>$id));//查询该专家的服务
                    foreach ($userServiceLog as $key => $val) {
                        $userService = pdo_fetch('select * from'.tablename('chat_ask_user_service').' where uniacid = :uniacid and sid = :sid and s_type in (1,2,3,4) ',array(':uniacid'=>$_W['uniacid'],':sid'=>$val['sid']));//查询服务价格
                        if($_GPC['level_id'] == 1){
                            $price = $userService['price'];
                        }else if($_GPC['level_id'] == 2){
                            $price = $userService['leveii_price'];
                        }else if($_GPC['level_id'] == 3){
                            $price = $userService['leveiii_price'];
                        }else if($_GPC['level_id'] == 4){
                            $price = $userService['leveiiii_price'];
                        }
                        pdo_update('chat_ask_user_service_log',array('price'=>$price),array('uniacid'=>$_W['uniacid'],'sid'=>$userService['sid'],'uid'=>$id,'is_del'=>0));
                        pdo_update("chat_users",array('pay_money'=>$price), array('id' => $id));
                    }
                }
                itoast('操作成功', $this->createWebUrl('ask_manage', array('op' => '', 'version_id' => 0)),'success');
            }else{
                itoast('操作失败','',error);
            }
        }
        $user = pdo_fetch('select * from ' . tablename('chat_users') . "where id=$id");
        if( (!$user['working_life'] >= 1)){
            $user['working_life'] = 0;
        }
        $certs = explode(',',$user['certs']);
        $duties = pdo_fetchall('select * from'.tablename('chat_duties').' where uniacid = '.$_W['uniacid'].' and type = 1 ');//职务
        $title = pdo_fetchall('select * from'.tablename('chat_duties').' where uniacid = '.$_W['uniacid'].' and type = 2 ');//职称
        $id_card_img = explode(',', $user['card_img']);//身份证图片
        $wpi = explode(',', $user['work_proof_img']);//工作证明图片
        $edu = explode(',', $user['real_education']);//学历证书图片
        $cer = explode(',', $user['real_certificate']);//资格证书图片
        $cert_data = pdo_fetchall('select * from'.tablename('chat_certificate').' where uniacid = '.$_W['uniacid']);
    }
} else {
    $tempcondition = " WHERE uniacid = '{$_W['uniacid']}' and vstatus=1 ";
    $keyword       = $_GPC['keyword'];
    $tempArray     = array();

    //时间查询
    if(!empty($_GPC['status_select']) && $_GPC['status_select'] != 1){
        $tempcondition = $tempcondition . ' and  '.$_GPC['status_select'].' BETWEEN :start and :end ';
        $tempArray[':start'] = strtotime($_GPC['time']['start'].' 00:00:00 ');
        $tempArray[':end'] = strtotime($_GPC['time']['start'].' 23:59:59 ');
    }

    //查询专家类型
    if($_GPC['is_platform'] != '' && $_GPC['is_platform'] != 10){
        $tempcondition = $tempcondition . ' and is_platform = :is_platform ';
        $tempArray[':is_platform'] = $_GPC['is_platform'];
    }

    //上下线查询
    if($_GPC['is_openask'] != 10 && $_GPC['is_openask'] != ''){
        $tempcondition = $tempcondition . ' and is_openask = :is_openask ';
        $tempArray[':is_openask'] = $_GPC['is_openask'];
    }

    //查询专家等级
    if(!empty($_GPC['level']) && $_GPC['level'] != 10){
        $tempcondition = $tempcondition . ' and level = :level ';
        $tempArray[':level'] = $_GPC['level'];
    }

    //关键字查询
    if (!empty($keyword)) {
        $tempcondition = $tempcondition . " AND ( A1.nickname LIKE '%{$keyword}%' OR A1.real_name LIKE '%{$keyword}%' )";
    }
    $pindex     = max(1, intval($_GPC['page']));
    $psize      = 20;
    $records    = pdo_fetchall("SELECT * FROM " . tablename("chat_users") . " A1 " . $tempcondition . " ORDER BY A1.apply_succtime DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $tempArray);
    foreach ($records as $key => $value) {
        if(!empty($value['superior_id'])){
            $superior_id = substr($value['superior_id'],1);
            $jjr = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$superior_id),array('real_name')); //举荐人
            $records[$key]['jjr_name'] = $jjr['real_name'];
        }
    }

    $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename("chat_users") . " A1 " . $tempcondition, $tempArray);
    $pager = pagination($total, $pindex, $psize);
}

if ($op == 'ask_serfuwu') {
    $id        = intval($_GPC['id']);
    $pindex    = max(1, intval($_GPC['page']));
    $psize     = 20;
    $contition = " and uniacid = '{$_W['uniacid']}' and s_type not in (5,6)";
    $fwtitle   = $_GPC['fwtitle'];
    // var_dump($fwtitle);exit;
    //模糊查询
    if (!empty($fwtitle)) {
        $contition .= " and title LIKE '%{$fwtitle}%'";
    }
    $total  = pdo_fetchcolumn('select count(*) from ' . tablename('chat_ask_user_service') . 'where is_del=0' . $contition);
    $result = pdo_fetchall('select * from ' . tablename('chat_ask_user_service') . 'where is_del=0' . $contition . ' order by sid desc limit ' . ($pindex - 1) * $psize . ',' . $psize);
    $pager  = pagination($total, $pindex, $psize);
}

//专家评分记录
if ($op == 'chat_score') {
    if($_POST){
        if($_GPC['setExpertLevel'] == 1){
            if($_GPC['level_id'] == 1){
                $level = '中级专家';
            }else if($_GPC['level_id'] == 2){
                $level = '高级专家';
            }else if($_GPC['level_id'] == 3){
                $level = '资深专家';
            }else if($_GPC['level_id'] == 4){
                $level = '大师级专家';
            }
            $expertLevelUpdate = pdo_update('chat_users',array('level_id'=>$_GPC['level_id'],'level'=>$level),array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['uid']));
            if(!empty($expertLevelUpdate)){
                echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
            }else{
                echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
            }
        }
    }
    $user_id = intval($_GPC['id']);
    $user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$user_id),array('level','level_id'));

    $count = pdo_fetch('select SUM(total) as num from'.tablename('chat_score'). " WHERE uid=:uid and uniacid=:uniacid ", array(":uid" =>$user_id,":uniacid"=>$_W['uniacid']));//总分数

    if($count['num']){
        $expert = pdo_fetch('select name from'.tablename('chat_set_expert').' where uniacid = :uniacid and fraction_sta <= '.$count['num'].' and fraction_end >= '.$count['num'],array(':uniacid'=>$_W['uniacid']));
    }

    $tempcondition = " WHERE uniacid = '{$_W['uniacid']}' and uid = '{$user_id}' ";

    $array = array(
        array('content' => '手机号'),
        array('content' => '邮箱'),
        array('content' => '真实姓名'),
        array('content' => '身份证'),
        array('content' => '真实头像'),
        array('content' => '工作证明'),
        array('content' => '学历证书'),
        array('content' => '资格证/职业证书'),
        array('content' => '工作年限'),
        array('content' => '专业职称'),
        array('content' => '自我介绍')
    );

    $score_array = [];
    $records    = pdo_fetchall("SELECT * FROM ".tablename("chat_score").$tempcondition);
    $total    = pdo_fetch("SELECT COUNT(*) as num FROM ".tablename("chat_score").$tempcondition);
    foreach ($records as $key => $value) {
        $score_array[$value['content']] = $value;
        $score_array[$value['content']]['user_data'] = pdo_fetch("SELECT username FROM " . tablename("users") . " WHERE uid=:uid", array(":uid" => $value['admin_uid']));
    }
}

if($op == 'plat'){//改变平台专家状态
    if($_GPC['id']){
        if($_GPC['is_platform'] == 0){
            $is_platform = 1;
            $content = "您好，您已被平台选定为税媒平台专家";
        }elseif($_GPC['is_platform'] == 1){
            $is_platform = 0;
            $content = "您好，您已被取消平台专家";
        }
        $user_update = pdo_update('chat_users',array('is_platform'=>$is_platform),array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
        if($user_update){
            //App 消息
            $push_data = array(
                'type'=>"link",
                'title'=>'通知',
                'content'=> $content,
                'url'=>$this->createMobileUrl('my_qyrz',array('from'=>'template'))
            );
            $this->pushMessageToSingle($_GPC['id'],$push_data);

            echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
        }else{
            echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
        }
    }
}

if ($op == 'is_del') {//删除专家
    if($_GPC['id']){
        $score = pdo_delete('chat_score',array('uniacid'=>$_W['uniacid'],'uid'=>$_GPC['id']));
        $user_update = pdo_update('chat_users',array('vstatus'=>0),array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
        if($user_update){
            echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
        }else{
            echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
        }
    }
}

if($op=='upda_recommend'){//中天推荐

    if($_GPC['id']){
        $user_update = pdo_update('chat_users',array('recommend'=>$_GPC['recommend']),array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
        if($user_update){
            itoast('操作成功');
        }else{
            itoast('操作失败');
        }
    }
}

//添加服务/编辑服务
if ($op == "add_service") {
    $id     = intval($_GPC['id']);
    $result = pdo_fetch(' select * from '.tablename('chat_ask_user_service')." where sid = :sid and uniacid = :uniacid and is_del = 0 ",array(':sid'=>$id,':uniacid'=>$_W['uniacid']));
    $serviceTypeArr = array(array('name'=>'图文','value'=>1),array('name'=>'电话','value'=>2),array('name'=>'年度','value'=>3),array('name'=>'上门','value'=>4));//服务类型
    if(!empty($id)){
        foreach ($serviceTypeArr as $key => $val) {
            if($val['value'] != $result['s_type']){
                unset($serviceTypeArr[$key]);
            }
        }
    }else{
        $userService = pdo_fetchall('select s_type from'.tablename('chat_ask_user_service')." where uniacid = :uniacid and s_type  in (1,2,3,4) and is_del = 0 ",array(':uniacid'=>$_W['uniacid']));
        foreach ($userService as $key => $val) {
            foreach ($serviceTypeArr as $k => $v) {
                if($val['s_type'] == $v['value']){
                    unset($serviceTypeArr[$k]);
                }
            }
        }
    }
    if(empty($serviceTypeArr)){
        $serviceTypeArr = array(array('name'=>'暂无服务类型','value'=>0));//服务类型
    }
    if (checksubmit('submit')) {
        $data = [
            "title"             => $_GPC['title'],
            "uniacid"           => $uniacid,
            "s_type"            => $_GPC['s_type'],
            "price"             => $_GPC['price'],
            "leveii_price"      => $_GPC['leveii_price'],
            "leveiii_price"     => $_GPC['leveiii_price'],
            "leveiiii_price"    => $_GPC['leveiiii_price'],
            "sort"              => $_GPC['sort'],
            "status"            => $_GPC['status'],
            "service_desc"      => $_GPC['service_desc'],
            // "type"              => $_GPC['type'],
            "service_img"       => $_GPC['service_img'],
            "is_del"            => 0,
            "gray_img"          => $_GPC['gray_img'],
            'profit'            => $_GPC['profit'],
            'service_content'   => $_GPC['service_content'],
            'agreement_title'   => $_GPC['agreement_title'],
            'agreement_content' => $_GPC['agreement_content']
        ];

        if (!empty($id)) {
            $res = pdo_update('chat_ask_user_service', $data, array('sid' => $id));
            if (!empty($res)) {
                itoast('修改成功', $this->createWebUrl('ask_manage', array('op' =>'ask_serfuwu','id'=>$result['uid'])));
            }else{
                itoast('修改失败','','error');
            }
        } else {
            $data["create_time"] = time();
            $res = pdo_insert('chat_ask_user_service', $data);
            if(!empty($res)){
                itoast('添加成功', $this->createWebUrl('ask_manage', array('op' =>'ask_serfuwu')));
            }else{
                itoast('添加失败','','error');
            }
        }
    }
}

//修改上下架
if($op == 'set_status'){
    if(!empty($_GPC['id']) && !empty($_GPC['status'])){
        $serRes = pdo_get("chat_ask_user_service",["sid"=>$_GPC['id'],'uniacid'=>$uniacid]);
        $userServiceUpdate = pdo_update('chat_ask_user_service',array('status'=>$_GPC['status']),array('sid'=>$_GPC['id']));
        if($_GPC['custom']==1){
              $serve_url = 'user_serve';//跳转值自定义服务列表
              //自定义服务记录
                $serve_log = pdo_get("chat_ask_user_service_log",["sid"=>$serRes["uid"],"sid"=>$serRes['sid'],'is_del'=>0,"uniacid"=>$uniacid]);

                if(empty($serve_log)){
                       $serveArr = [
                              "uniacid"=>$uniacid,
                              "sid"=>$serRes['sid'],
                              "uid"=>$serRes["uid"],
                              "price"=>$serRes['price'],
                              "create_time"=>time()
                       ];
                       pdo_insert("chat_ask_user_service_log",$serveArr);
                }
        }else{
              $serve_url = 'ask_serfuwu';
        }
        if($userServiceUpdate){
            itoast('操作成功',$this->createWebUrl('ask_manage', array('op' =>  $serve_url,'version_id'=>0,'id'=>$_GPC['uid'])));

        }else{
            itoast('操作失败',$this->createWebUrl('ask_manage', array('op' =>  $serve_url,'version_id'=>0)));
        }
    }
}

//删除
if ($op == "del_service"){
    $id = intval($_GPC['id']);
    if($_GPC['custom']==1){
        $serve_url = 'user_serve';//跳转值自定义服务列表
    }else{
        $serve_url = 'ask_serfuwu';
    }
    if(!empty($id)){
        $del = pdo_update('chat_ask_user_service',array('is_del'=>1),array('sid'=>$id,'uniacid'=>$_W['uniacid']));
        if(!empty($del)){
            if($_GPC['custom'] != 1){
                pdo_update('chat_ask_user_service_log',array('is_del'=>1),array('uniacid'=>$_W['uniacid'],'sid'=>$id));
            }
            itoast('操作成功',$this->createWebUrl('ask_manage', array('op' => $serve_url,'version_id'=>0,'id'=>$_GPC['uid'])));
        }
    }else{
        itoast('操作失败','','error');
    }
}

//编辑个人专家服务
if($op == 'edit_expertService'){
    if($_POST){
        $userServiceData = array(
            'title' => $_GPC['title'],
            'service_desc' => $_GPC['service_desc'],
            'is_price' => $_GPC['is_price'],
            'price' => $_GPC['price'],
            'service_time' => $_GPC['service_time'],
            'is_by_stages' => $_GPC['is_by_stages'],
            'status' => $_GPC['status']
        );
        $userServiceUpdate = pdo_update('chat_ask_user_service',$userServiceData,array('uniacid'=>$_W['uniacid'],'sid'=>$_GPC['sid']));
        if(!empty($userServiceUpdate)){
            $serviceLogCondition = array('sid'=>$_GPC['sid'],'uniacid'=>$_W['uniacid'],'uid'=>$_GPC['uid']);
            pdo_update('chat_ask_user_service_log',array('price'=>$_GPC['price']),$serviceLogCondition);
            echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
        }else{
            echo json_encode(array('code'=>100,'msg'=>'操作失败'));exit;
        }
    }
    $sid = $_GPC['sid'];
    $userService = pdo_get('chat_ask_user_service',array('sid'=>$sid,'uniacid'=>$_W['uniacid']));
}

//指定录入
if($op == "designated_entry"){
    $id = $_GPC['id'];
    $module_name = 'seapow_article';
    $modulelist = uni_modules(false);
    $module = $_W['current_module'] = $modulelist[$module_name];
    $user_permissions = module_clerk_info($module_name,$_GPC['page']);
    $pager = $user_permissions['pa']['page'];

    if (!empty($user_permissions)) {
        foreach ($user_permissions as $key => &$permission) {
            if (!empty($permission['permission'])) {
                $permission['permission'] = explode('|', $permission['permission']);
                foreach ($permission['permission'] as $k => $val) {
                    $permission['permission'][$val] = $permission_name[$val];
                    unset($permission['permission'][$k]);
                }
            }
        }
        unset($permission);
    }
    unset($user_permissions[0]);
}

if($op == "edit_designated_entry"){
    if(ISPOST){
        $user = pdo_update("chat_users",array('ruid'=>$_GPC['ruid']),array('id'=>$_GPC['id']));
        if($user){
            itoast('操作成功！', $this->createWebUrl('ask_manage', array('op' => '', 'version_id' => 0)));
        }else{
            itoast('操作失败', $this->createWebUrl('ask_manage', array('op' => 'edit_designated_entry','version_id' => 0)));
        }
    }
}

//专家服务
if($op == 'expert_service'){
    if($_GPC['id']){
        $user = pdo_get('chat_users',array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
        $users_service = pdo_fetchall('select sl.*,s.title,s.service_desc,s.s_type,s.type from'.tablename('chat_ask_user_service_log').' sl INNER join '.tablename('chat_ask_user_service').' s ON sl.sid = s.sid '.' where sl.uniacid = '.$_W['uniacid'].' and sl.uid = '.$_GPC['id'].' and sl.is_del = 0 ');
        $total = pdo_fetch('select COUNT(*) as num from'.tablename('chat_ask_user_service_log').' sl INNER join '.tablename('chat_ask_user_service').' s ON sl.sid = s.sid '.' where sl.uniacid = '.$_W['uniacid'].' and sl.uid = '.$_GPC['id'].' and sl.is_del = 0 ');


    }

    if($_W['ispost']){
        if($_GPC['seid']){
            $service_update = pdo_update('chat_ask_user_service_log',array('price'=>$_GPC['price']),array('seid'=>$_GPC['seid'],'uniacid'=>$_W['uniacid']));
            if($service_update){
                echo json_encode(array('code'=>1,'msg'=>'操作成功'));exit;
            }else{
                echo json_encode(array('code'=>1,'msg'=>'操作失败'));exit;
            }
        }
    }
}

//绑定日志
if($op == 'bind_log'){
    $id        = intval($_GPC['id']);
    $pindex    = max(1, intval($_GPC['page']));
    $psize     = 20;
    $contition = " and uniacid = '{$_W['uniacid']}'";
    $ex_name   = $_GPC['ex_name'];

    //模糊查询
    if (!empty($ex_name)) {
        $contition .= " and ex_name LIKE '%{$ex_name}%'";
    }

    $total  = pdo_fetchcolumn('select count(*) from ' . tablename('chat_bind_log') . 'where is_del = 0' . $contition);
    $result = pdo_fetchall('select * from ' . tablename('chat_bind_log') . 'where is_del = 0' . $contition . ' order by bd_id desc limit ' . ($pindex - 1) * $psize . ',' . $psize);
    $pager  = pagination($total, $pindex, $psize);
}

/**
 * [ax_untie AX解绑]
 * @param  [int] $id [绑定id]
 */
if($op == 'ax_untie'){
    $bind_data = pdo_get('chat_bind_log',array('bd_id'=>$_GPC['id'],'uniacid'=>$_W['uniacid'])); //绑定日志信息
    if(empty($bind_data)){
        itoast('日志不存在','','error');die;
    }

    //米糠配置
    $AppKey = '6c41c6293c3453b8e3e8e6b951f5ba88';
    $AppSecret = 'XAD7Gn';
    $ServerRoot = 'mapi.mixcom.cn';

    //AX解绑
    $un_url = 'http://'.$ServerRoot.'/virtual/unaxbindnumber';
    $un_data = array(
        'number' => $bind_data['xiaohao'],
        'aparty' => $bind_data['mobile'],
        'time' => time()+'600',
        'appkey' => $AppKey
    );
    $un_sign = $this->mk_sign($un_data);
    $un_data['sign'] = $un_sign;
    $un_result = ihttp_post($un_url, $un_data);
    $un_result = json_decode($un_result['content'],1);

    //是否解绑成功
    if($un_result['code'] == 200){
        //修改日志状态
        pdo_update('chat_bind_log',array('status'=>'2'),array('bd_id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
        itoast('操作成功');die;
    }else{
        itoast($un_result['msg'], '','error');die;
    }
}

/**
 * [axlogicalswitch AX开关机]
 * @param  [int] $id [绑定id]
 * @param  [int] $type [类型 on开机 off关机]
 */
if($op == 'axlogicalswitch'){
    $bind_data = pdo_get('chat_bind_log',array('bd_id'=>$_GPC['id'],'uniacid'=>$_W['uniacid'])); //绑定日志信息
    if(empty($bind_data)){
        itoast('日志不存在','','error');die;
    }
    if(empty($_GPC['type'])){
        itoast('缺少操作类型','','error');die;
    }

    //米糠配置
    $AppKey = '6c41c6293c3453b8e3e8e6b951f5ba88';
    $AppSecret = 'XAD7Gn';
    $ServerRoot = 'mapi.mixcom.cn';

    //AX开关机
    $sw_url = 'http://'.$ServerRoot.'/virtual/axlogicalswitch';
    $sw_data = array(
        'number' => $bind_data['xiaohao'],
        'virtual_status' => $type,
        'time' => time()+'600',
        'appkey' => $AppKey
    );
    $sw_sign = $this->mk_sign($sw_data);
    $sw_data['sign'] = $sw_sign;
    $sw_result = ihttp_post($sw_url, $sw_data);
    $sw_result = json_decode($sw_result['content'],1);

    //是否开关成功
    if($sw_result['code'] == 200){
        //修改日志状态
        pdo_update('chat_bind_log',array('kg_status'=>$type),array('bd_id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
        itoast('操作成功');die;
    }else{
        itoast($sw_result['msg'],'','error');die;
    }
}

/**
 * [bd_del 绑定日志删除]
 * @param  [int] $id [绑定id]
 */
if($op == 'bd_del'){
    $bind_data = pdo_get('chat_bind_log',array('bd_id'=>$_GPC['id'],'uniacid'=>$_W['uniacid'])); //绑定日志信息
    if(empty($bind_data)){
        itoast('日志不存在','','error');die;
    }

    $bind_update = pdo_update('chat_bind_log',array('is_del'=>1),array('uniacid'=>$_W['uniacid'],'bd_id'=>$_GPC['id']));
    if(!empty($bind_update)){
        itoast('操作成功');die;
    }else{
        itoast('操作失败','','error');die;
    }
}

//唤醒专家 发送模版消息
if($op=='awaken_temp'){
    if($_GPC['is_ajax']==1){
        //查询7天内 有无发过此专家的模版消息
        $dayLog = pdo_fetchall("select uid from ".tablename("chat_users_code_log") . ' where type=2 and create_time >='.strtotime('-7 days'));

        foreach ($dayLog as $key => $value) {
            $uidStr .= ','.$value['uid'];
        }
        if($uidStr){
            $str = ltrim($uidStr,",");
        }
        $condition="";
        if(!empty($str)){
            $condition = ' and id not in ('.$str . ')';
        }
        $user_data =  pdo_fetchall("select openid,id,real_name,nickname,last_time from ".tablename('chat_users') . ' where vstatus=1 and is_openask=1 and last_time <='.strtotime('-7 days') .$condition);
        if(empty($user_data)){
            echo json_encode(["code"=>100,"message"=>"暂时没有需要唤醒的专家"],JSON_UNESCAPED_UNICODE);exit;
        }else{
            foreach ($user_data as $key => $value) {
                $userTempLog = [
                    "create_time" => time(),
                    "type" =>2,
                    "uid" => $value["id"]
                ];
                pdo_insert("chat_users_code_log",$userTempLog);
                pdo_update('chat_users',array('last_time'=>time()),array('id'=>$value['id']));
                //模版消息
                $weObj = WeAccount::create($_W['uniacid']);
                $send_data = array(
                    'first'=>array('value'=>'您已长时间未登录平台'),
                    'keyword1'=>array('value'=>$value['real_name'] ? $value['real_name'] : $value['nickname']),
                    'keyword2'=>array('value'=>date("Y-m-d H:i:s",time())),
                    'remark'=>array('value'=> '大量悬赏问题正在等待您来回答。')
                );
                $url = $_W['siteroot'] . $this->createMobileUrl('ask_index');
                $weObj->sendTplNotice($value['openid'], 'NaSIyL4XP5kifIR1aP3BebyNa11Wx-B85B67x0fkfqY', $send_data, $url);
            }
            echo json_encode(["code"=>200,"message"=>"操作成功"],JSON_UNESCAPED_UNICODE);exit;
        }
    }
}

//审核列表
if($op=='custom_serve'){
    $pindex    = max(1, intval($_GPC['page']));
    $psize     = 20;
    $contition = ' where s.s_type=5 and s.uid<>0 and s.uniacid='.$_W['uniacid'] .' and s.status=3 and s.is_del=0';
    $service_name   = $_GPC['fwtitle'];
//      $condition .= " AND (nickname LIKE '%".$_GPC['keyword']."%' or real_name LIKE '%".$_GPC['keyword']."%')";

    //模糊查询
    if (!empty($service_name)) {
        $contition .= " and (s.title LIKE '%{$service_name}%' or u.real_name LIKE '%{$service_name}%' or u.nickname LIKE '%{$service_name}%' )";
    }

    $total = pdo_fetch('SELECT count(*) as num FROM '. tablename('chat_ask_user_service').' s left join ' . tablename('chat_users') .' u on s.uid=u.id ' .$contition);

    $res_serve = pdo_fetchall('SELECT s.*,u.nickname,u.real_name FROM '.tablename("chat_ask_user_service") .' s left join ' . tablename('chat_users') .' u on s.uid=u.id '.$contition . ' order by s.create_time desc limit ' . ($pindex - 1) * $psize . ',' . $psize);

    $pager  = pagination($total['num'], $pindex, $psize);

}
//截取字符串
 function  str_strs($str,$str_at,$str_end)
 {
       //截取的个数  大于字符串的长度 则直接获取全部
       if(mb_strlen($str) <= $str_end ){
            $strs = $str;
       }else{
         $strs = mb_substr($str,$str_at,$str_end,'utf-8');
       }
       return $strs;
 }

if($op=='to_examine'){
     $service_id = $_GPC['id'];
    $serRes = pdo_get("chat_ask_user_service",["sid"=>$service_id,'uniacid'=>$uniacid]);

    if(empty($service_id)){
        itoast('该服务不存在','','error');die;
    }else{

        if($_GPC['refuse']==1){
           $status = 4;
           $name = "审核被拒绝通过";
           $name_list = "如有疑问，请咨询客服：0769-22859180";
        }else{
            $status = 2;
            $name = "审核已通过";
            $name_list = "请及时到我的服务把服务上架";
        }
        $weObj = WeAccount::create($_W['uniacid']);
        $url = $_W['siteroot'] . $this->createMobileUrl('my_chat',array('op'=>'user_fuwu','from'=>'template'));

        if(empty($_GPC['ser'])){
            $res = pdo_update('chat_ask_user_service',['status'=> $status],["sid"=>$service_id]);
        }else{
            pdo_fetchall("UPDATE ".tablename('chat_ask_user_service') . ' set status='.$status.' where sid in ('.$service_id .')');
            $ser_res = pdo_fetchall("SELECT se.*,u.openid,u.id as uid FROM ".tablename("chat_ask_user_service") . ' se left join '.tablename('chat_users') .' u on se.uid=u.id ' .' where se.sid in ('.$service_id .')');
            foreach ($ser_res as $key => $value) {
                    $serve_log = pdo_get("chat_ask_user_service_log",["sid"=>$value["uid"],"sid"=>$value['sid'],"uniacid"=>$uniacid,'is_del'=>0]);
                if(empty($serve_log)){
                   $serveArr = [
                          "uniacid"=>$uniacid,
                          "sid"=>$value['sid'],
                          "uid"=>$value["uid"],
                          "price"=>$value['price'],
                          "create_time"=>time()
                   ];
                    pdo_insert("chat_ask_user_service_log",$serveArr);
                }
                $send_data = array(
                    'first'=>array('value'=>'您好，您提交的自定义服务审核结果如下：'),
                    'keyword1'=>array('value'=>$value['title'],'color'=>'#173177'),
                    'keyword2'=>array('value'=> $name,'color'=>'#173177'),
                    'keyword3'=>array('value'=>date('Y年m月d日 H:i:s',time()),'color'=>'#173177'),
                );
                if($_GPC['refuse']==1){
                    $send_data['keyword4']['value'] = $_GPC['desc'];
                    $send_data['keyword4']['color'] = '#173177';
                }
                $send_data['remark']['value'] = $name_list;
                $send_data['remark']['color'] = '#173177';
                $weObj->sendTplNotice($value['openid'], 'bqSYSyEgX_GEk8xbV8Ah6qd2mQ7xmyLBSX4rclAbO2Q', $send_data, $url, '#173177');

                //App 消息
                $push_data = array(
                    'type'=>"link",
                    'title'=>'自定义服务审核',
                    'content'=> "您好，您申请的".$value['title']."，".$name,
                    'url'=>$this->createMobileUrl('my_chat',array('op'=>'user_fuwu','from'=>'template'))
                );
                $this->pushMessageToSingle($value['uid'],$push_data);

            }
            itoast('操作成功','','success');die;
        }

        //自定义服务记录
        $serve_log = pdo_get("chat_ask_user_service_log",["sid"=>$serRes["uid"],"sid"=>$serRes['sid'],"uniacid"=>$uniacid,'is_del'=>0]);
        if(empty($serve_log)){
               $serveArr = [
                      "uniacid"=>$uniacid,
                      "sid"=>$serRes['sid'],
                      "uid"=>$serRes["uid"],
                      "price"=>$serRes['price'],
                      "create_time"=>time()
               ];

               pdo_insert("chat_ask_user_service_log",$serveArr);
        }

        $serveRes = pdo_get("chat_ask_user_service",["sid"=>$service_id]);
        $openid = pdo_get("chat_users",["id"=>$serveRes['uid']]);
            $send_data = array(
                'first'=>array('value'=>'您好，您提交的自定义服务审核结果如下：'),
                'keyword1'=>array('value'=>$serveRes['title'],'color'=>'#173177'),
                'keyword2'=>array('value'=> $name,'color'=>'#173177'),
                'keyword3'=>array('value'=>date('Y年m月d日 H:i:s',time()),'color'=>'#173177'),
                'remark'=>array('value'=>$name_list,'color'=>'#173177'),
            );
            //App 消息
            $push_data = array(
                    'type'=>"link",
                    'title'=>'自定义服务审核',
                    'content'=> "您好，您申请的".$serveRes['title']."，".$name,
                    'url'=>$this->createMobileUrl('my_chat',array('op'=>'user_fuwu','from'=>'template'))
                );
            $this->pushMessageToSingle($openid['id'],$push_data);

             if($_GPC['refuse']==1){
                $send_data['keyword4']['value'] = $_GPC['desc'];
                $send_data['keyword4']['color'] = '#173177';
             }
             $send_data['remark']['value'] = $name_list;
             $send_data['remark']['color'] = '#173177';
            $weObj->sendTplNotice($openid['openid'], 'bqSYSyEgX_GEk8xbV8Ah6qd2mQ7xmyLBSX4rclAbO2Q', $send_data, $url, '#173177');
        if($res){

            itoast('操作成功','','success');die;
        }else{
            itoast('操作失败','','error');die;
        }

    }

}

if($op=='user_serve'){
    $pindex    = max(1, intval($_GPC['page']));
    $psize     = 20;
    $contition = ' where s_type=5 and  uniacid='.$_W['uniacid'] .' and status<>3 and is_del=0 and uid='.$_GPC['id'];
    $service_name   = $_GPC['fwtitle'];

    //模糊查询
    if (!empty($service_name)) {
        $contition .= " and title LIKE '%{$service_name}%'";
    }

    $total = pdo_fetch('SELECT count(*) as num FROM '. tablename('chat_ask_user_service').$contition);
    $res_serve = pdo_fetchall('SELECT * FROM '.tablename("chat_ask_user_service") .$contition . ' order by create_time desc limit ' . ($pindex - 1) * $psize . ',' . $psize);
    $pager  = pagination($total['num'], $pindex, $psize);
}

include $this->template('ask_manage');