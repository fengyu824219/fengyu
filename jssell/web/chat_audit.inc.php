

<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$uniacid=$_W['uniacid'];

$op = empty($_GPC['op']) ? "display" : $_GPC['op'];

if($op == "display"){

    $pindex = max(1, intval($_GPC['page']));
    $tempcondition=" WHERE uniacid = '{$_W['uniacid']}' and vstatus=2 ";
    if(!empty($keyword = $_GPC['keyword'])){
        //根据用户昵称丶电话丶真实姓名
       $tempcondition .= " AND ( nickname LIKE '%{$keyword}%' OR mobile LIKE '%{$keyword}%' OR real_name LIKE '%{$keyword}%' ) ";
    }
    if(!empty($jjr = $_GPC['jjr'])){
        //根据举荐人昵称丶电话丶真实姓名
        $jjrcondition.= " WHERE uniacid = '{$_W['uniacid']}' AND ( nickname LIKE '%{$jjr}%' OR mobile LIKE '%{$jjr}%' OR real_name LIKE '%{$jjr}%' ) ";
        $jjr_data = pdo_fetchall("SELECT id FROM ".tablename("chat_users") .$jjrcondition);
        if(!empty($jjr_data)){
            $jjr_id = ""; //举荐人id
            foreach ($jjr_data as $key => $value) {
                $jjr_id .= $value['id'].',';
            }
            $jjr_id = explode(',',rtrim($jjr_id, ','));
            $yh_data = pdo_fetchall("SELECT id,superior_id FROM ".tablename("chat_users") .$tempcondition);
            if(!empty($yh_data)){
                $yh_id = ""; //审核中用户id
                foreach ($yh_data as $key => $value) {
                    $yh_id .= substr($value['superior_id'],1).',';
                }
                $yh_id = explode(',',rtrim($yh_id, ','));
            }
            $con_id = implode(',',array_intersect($jjr_id,$yh_id)); //对比得到相同的id
            if(!empty($con_id)){
                $tempcondition .= " AND superior_id in('".'u'.$con_id."')";
            }else{
                $tempcondition .= " AND id = 0";
            }
        }
    }

    $psize = 20;
    $totalnum = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename("chat_users") . $tempcondition);
    $audit = pdo_fetchall("SELECT id,superior_id,avatar,nickname,mobile,create_time,real_name,apply_time,sex FROM ".tablename("chat_users") .$tempcondition ." ORDER BY apply_time ASC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    foreach ($audit as $key => $value) {
        if(!empty($value['superior_id'])){
            $superior_id = substr($value['superior_id'],1);
            $jjr = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$superior_id),array('real_name')); //举荐人
            $audit[$key]['jjr_name'] = $jjr['real_name'] ? $jjr['real_name'] : $jjr['nickname'];
        }
    }
    $pager = pagination($totalnum, $pindex, $psize);

}

//审核详情
if($op == "chat_user_lst"){
    $uid = $_GPC['uid'];
    if (!empty($uid)) {
        $user = pdo_fetch('SELECT * FROM ' . tablename("chat_users") . " where id={$uid}"); //用户信息
        if($user['working_life'] > 10){
            $wo_life = 10;
        }elseif($user['working_life'] < 0){
            $wo_life = 0;
        }else{
            $wo_life = $user['working_life'];
        }
        $certs = explode(',',$user['certs']);
        $cert_data = pdo_fetchall('select * from'.tablename('chat_certificate').' where uniacid = '.$_W['uniacid']);
        $id_card_img = explode(',', $user['card_img']);//身份证图片
        $user_img = explode(',', $user['avatar']);//真实头像图片
        $wpi = explode(',', $user['work_proof_img']);//学历证书图片
        $edu = explode(',', $user['real_education']);//工作证明图片
        $cer = explode(',', $user['real_certificate']);//工作证明图片

        //查看是否已加分
        $photo = pdo_get('chat_score',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'content'=>'手机号'));
        $mail = pdo_get('chat_score',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'content'=>'邮箱'));
        $real_name = pdo_get('chat_score',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'content'=>'真实姓名'));
        $id_card = pdo_get('chat_score',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'content'=>'身份证'));
        $head_portrait = pdo_get('chat_score',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'content'=>'真实头像'));
        $work_proof = pdo_get('chat_score',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'content'=>'工作证明'));
        $education = pdo_get('chat_score',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'content'=>'学历证书'));
        $certificate = pdo_get('chat_score',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'content'=>'资格证/职业证书'));
        $working_life = pdo_get('chat_score',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'content'=>'工作年限'));
        $career_name = pdo_get('chat_score',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'content'=>'专业职称'));
        $introduce = pdo_get('chat_score',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'content'=>'自我介绍'));
        $ask_tags = pdo_getall('chat_ask_tags',array('uniacid'=>$_W['uniacid'],'id'=>array('in'=>$user['tags'])));//分类表
        $frac = pdo_fetch('select sum(total) as num from'.tablename('chat_score').' where uniacid = :uniacid and uid = :uid ',array(':uniacid'=>$_W['uniacid'],':uid'=>$uid));//总分
    }
    //加分操作
    if($_GPC['type'] == 'fraction'){
        $score_data = array(
            'uid' => $_GPC['uid'],
            'uniacid' => $_W['uniacid'],
            'content' => $_GPC['content'],
            'total' => $_GPC['num'],
            'create_time' => time(),
            'admin_uid' => $_W['uid']
        );
        $chat_score = pdo_insert('chat_score',$score_data);
        if($chat_score){
            echo json_encode(array('code'=>1,'msg'=>'操作成功'));exit;
        }else{
            echo json_encode(array('code'=>2,'msg'=>'操作失败'));exit;
        }
    }
    //取消加分
    if($_GPC['type'] == 'cancel_fraction'){
        $chat_score = pdo_delete('chat_score',array('uniacid'=>$_W['uniacid'],'uid'=>$_GPC['uid'],'content'=>$_GPC['content']));
        if($chat_score){
            echo json_encode(array('code'=>1,'msg'=>'操作成功'));exit;
        }else{
            echo json_encode(array('code'=>2,'msg'=>'操作失败'));exit;
        }
    }
    //通过拒绝操作
    if($_GPC['type'] == 'is_adopt'){
        if($_GPC["submit"] == "通过"){

            if($_GPC['uid']){//判断是否存在用户Id
                $score = pdo_fetch('select SUM(total) as num from'.tablename('chat_score').' where uniacid = '.$_W['uniacid'].' and uid = '.$_GPC['uid']);//查看该用户有多少分
                if($score['num'] > 100){$score['num'] = 100;}//最高大师级
                if(!empty($score['num'])){//判断分数是否为空
                    $expert = pdo_fetch('select * from'.tablename('chat_set_expert').' where uniacid = '.$_W['uniacid'].' and fraction_sta <= '.$score['num'].' and fraction_end >= '.$score['num']);//查看哪个等级的专家
                    // if(empty($expert)){//判断
                    //     echo json_encode(array('code'=>2,'msg'=>'审核数值不符合'));exit;
                    // }
                }
                // else{
                //     echo json_encode(array('code'=>2,'msg'=>'审核数值不符合'));exit;
                // }

            }

            //保存用户头像
            $is_wxtx = strstr($user['avatar'],'http://thirdwx.qlogo.cn'); //是否微信头像
            if(!empty($is_wxtx)){ //微信的头像才存
                $userpath = ATTACHMENT_ROOT . "images/".$uniacid."/".date('Y')."/".date('m')."/";  //保存地址
                $avatar_name = "user_".$user['id'].".png"; //头像名称
                //如果没有文件夹生成
                if (!file_exists($userpath)) {
                    mkdir($userpath, 0777, true);
                }
                $userpath = $userpath.$avatar_name; //保存地址
                $path = "images/".$uniacid."/".date('Y')."/".date('m')."/".$avatar_name; //数据库保存地址
                $avatar_path = getImg($user['avatar'],$userpath);
                if(!empty($avatar_path)){
                    pdo_update("chat_users", array('avatar'=>$path), array('id'=>$uid));
                }
            }

            // 用户更改为专家
            $data = [
                "vstatus" => 1,
                "apply_succtime" => time(),
                "level" => $_GPC['level'],
                "level_id" => $_GPC['level_id'],
                "remarks" => $_GPC['remarks']
            ];
        }

        if($_GPC["submit"] == "拒绝"){
            $data = [
                "vstatus" => 0,
                "remarks" => $_GPC['remarks']
            ];
        }
        if(!empty($_GPC['certs'])){
            $data['certs'] = implode(',',$_GPC['certs']);
        }
        $result = pdo_update("chat_users", $data, array('id' => $uid));
        if($result){
            //模板消息
            if($_GPC["submit"] == "通过"){
                $weObj = WeAccount::create($_W['uniacid']);
                $send_data = array(
                    'first'=>array('value'=>'您好，您的审核请求已处理'),
                    'keyword1'=>array('value'=>empty($data['level']) ? '专家' : $data['level'],'color'=>'#173177'),
                    'keyword2'=>array('value'=>"通过",'color'=>'#173177'),
                    'keyword3'=>array('value'=>date('Y年m月d日 H:i:s',time()),'color'=>'#173177'),
                    'remark'=>array('value'=>'去悬赏广场回答问题赚收益吧!','color'=>'#173177'),
                );

                $url = $_W['siteroot'] . $this->createMobileUrl('ask_index',array('from'=>'template'));
                $weObj->sendTplNotice($user['openid'], 'bqSYSyEgX_GEk8xbV8Ah6qd2mQ7xmyLBSX4rclAbO2Q', $send_data, $url, '#173177');
                //App 消息
                $push_data = array(
                    'type'=>"link",
                    'title'=>'专家认证审核通知',
                    'content'=> "您好，您的审核已通过，去悬赏广场回答问题赚收益吧",
                    'url'=>$this->createMobileUrl('ask_index',array('from'=>'template'))
                );
                $this->pushMessageToSingle($user['id'],$push_data);

            }else if($_GPC["submit"] == "拒绝"){
                $weObj = WeAccount::create($_W['uniacid']);
                $send_data = array(
                    'first'=>array('value'=>'您好，您的审核请求已处理'),
                    'keyword1'=>array('value'=>empty($data['level']) ? '专家' : $data['level'],'color'=>'#173177'),
                    'keyword2'=>array('value'=>"不通过",'color'=>'#173177'),
                    'keyword3'=>array('value'=>date('Y年m月d日 H:i:s',time()),'color'=>'#173177'),
                    'remark'=>array('value'=>$data['remarks']."\r\n".'请修改资料并重新提交，如有疑问，请咨询客服','color'=>'#173177'),
                );

                $url = $_W['siteroot'] . $this->createMobileUrl('my_chat_audit',array('ty'=>1,'from'=>'template'));
                $weObj->sendTplNotice($user['openid'], 'bqSYSyEgX_GEk8xbV8Ah6qd2mQ7xmyLBSX4rclAbO2Q', $send_data, $url, '#173177');
                //App 消息
                $push_data = array(
                    'type'=>"link",
                    'title'=>'专家认证审核通知',
                    'content'=> "您好，您的审核未通过，请修改资料并重新提交，如有疑问，请咨询客服",
                    'url'=>$this->createMobileUrl('my_chat_audit',array('ty'=>1,'from'=>'template'))
                );
                $this->pushMessageToSingle($user['id'],$push_data);

            }
            //更改个人服务
            $user_service = pdo_get('chat_ask_user_service',array('uniacid'=>$_W['uniacid'],'s_type'=>'1'));//服务表
            $user_service_log = pdo_get('chat_ask_user_service_log',array('uniacid'=>$_W['uniacid'],'uid'=>$uid,'is_del'=>0));//个人服务表
            if($user_service && empty($user_service_log)){
                if($_GPC['level_id'] == '1'){
                    $money = $user_service['price'];
                }
                if($_GPC['level_id'] == '2'){
                    $money = $user_service['leveii_price'];
                }
                if($_GPC['level_id'] == '3'){
                    $money = $user_service['leveiii_price'];
                }
                if($_GPC['level_id'] == '4'){
                    $money = $user_service['leveiiii_price'];
                }
                $ser_log_data = array('uniacid'=>$_W['uniacid'],'sid'=>$user_service['sid'],'uid'=>$uid,'price'=>$money,'create_time'=>time());
                $user_ser_log = pdo_insert('chat_ask_user_service_log',$ser_log_data);
                if($user_ser_log){
                    pdo_update('chat_users',array('pay_money'=>$money),array('uniacid'=>$_W['uniacid'],'id'=>$uid));
                }
            }

            if($data['vstatus'] == 1){
                $this->pc_users($uid);//为专家创建后台账号
                $this->add_integral($uid,9,$uid);//添加积分
            }

            echo json_encode(array('code'=>1,'msg'=>'操作成功'));exit;
        }else{
            echo json_encode(array('code'=>2,'msg'=>'操作失败'));exit;
        }
    }
}

    /**
    * //curl抓取远程微信图片
     * @param string $url        //抓取的地址
     * @param string $filename   //保存的路径
     * @return bool
     */
    function getImg($url = "", $filename = "")
    {
        if (is_dir(basename($filename))) {
            echo "The Dir was not exits";
            return false;
        }
        //去除URL连接上面可能的引号
        $hander = curl_init();
        $fp     = fopen($filename, 'wb');
        $dir = pathinfo($url);
        $host = $dir['dirname'];
        $refer = $host.'/';

        curl_setopt($hander, CURLOPT_URL, $url);
        curl_setopt ($hander, CURLOPT_REFERER, $refer);
        curl_setopt($hander, CURLOPT_FILE, $fp);
        curl_setopt($hander, CURLOPT_HEADER, 0);
        curl_setopt($hander, CURLOPT_FOLLOWLOCATION, 1);
        //curl_setopt($hander,CURLOPT_RETURNTRANSFER,false);//以数据流的方式返回数据,当为false是直接显示出来
        curl_setopt($hander, CURLOPT_TIMEOUT, 60);
        curl_exec($hander);
        curl_close($hander);
        header("Content-type: image/jpeg");
        fclose($fp);
        return true;
    }


//企业认证
if($op == "chat_qyrz"){
    $pindex = max(1, intval($_GPC['page']));
    $tempcondition=" WHERE uniacid = '{$_W['uniacid']}'";
    if(!empty($_GPC['keyword'])){
        //根据用户昵称丶电话丶真实姓名"
       $tempcondition .= " AND ( qy_name LIKE '%{$_GPC['keyword']}%' OR mobile LIKE '%{$_GPC['keyword']}%' OR zjzch LIKE '%{$_GPC['keyword']}%' ) ";
    }
    if(!empty($_GPC['status']) && $_GPC['status'] != 99){
        //根据用户昵称丶电话丶真实姓名"
       $tempcondition .= " AND status =".$_GPC['status'];
    }

    $psize = 20;
    $totalnum = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename("chat_qyrz") . $tempcondition);
    $audit = pdo_fetchall("SELECT * FROM ".tablename("chat_qyrz").$tempcondition ." ORDER BY status asc,create_time ASC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    foreach ($audit as $key => $value) {
        $users = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$value['uid']));
        $audit[$key]['nickname'] = !empty($users['real_name']) ? $users['real_name'] : $users['nickname'];
        if($value['status'] == 1){
            $status = '审核中';
        }
        if($value['status'] == 2){
            $status = '已通过';
        }
        if($value['status'] == 3){
            $status = '不通过';
        }
        $audit[$key]['status'] = $status;
    }
    $pager = pagination($totalnum, $pindex, $psize);

}

//城市合伙人
if($op == "user_partner"){
    $pindex = max(1, intval($_GPC['page']));
    $tempcondition=" WHERE uniacid = '{$_W['uniacid']}' and difference='{$_GPC['difference']}'";
    if(!empty($_GPC['keyword'])){
        //根据用户昵称丶电话丶真实姓名"
       $tempcondition .= " AND ( rname LIKE '%{$_GPC['keyword']}%' ) ";
    }
    if(!empty($_GPC['status']) && $_GPC['status'] != 99){
        //根据用户昵称丶电话丶真实姓名"
       $tempcondition .= " AND is_auditing =".$_GPC['status'];
    }

    $psize = 20;
    $totalnum = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename("chat_user_apply") . $tempcondition);
    $audit = pdo_fetchall("SELECT * FROM ".tablename("chat_user_apply").$tempcondition ." order by case when is_auditing = 3 then 1 else 2 end, create_time DESC  LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    foreach ($audit as $key => $value) {
        $users = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$value['user_id']));
        $audit[$key]['nickname'] = !empty($users['real_name']) ? $users['real_name'] : $users['nickname'];
        $audit[$key]['mobile'] = $users['mobile'];

        if($value['is_auditing'] == 1){
            $status = '已通过';
        }
        if($value['is_auditing'] == 2){
            $status = '拒绝';
        }
        if($value['is_auditing'] == 3){
            $status = '审核中';
        }
        $audit[$key]['status'] = $status;
    }
    $pager = pagination($totalnum, $pindex, $psize);

}
//生成邀请码
function invitationCode(){
    for ($i=0; $i < 6; $i++) {
        $invitationCode[$i] = rand(0,99);
    }
    $invitationCode = implode('',$invitationCode);
    if(strlen($invitationCode) > 6){
        $invitationCode = substr($invitationCode,0,6);
    }
    return $invitationCode;
}
if($op=='partne_update'){
    $id = $_GPC['id'];
    $res = pdo_get("chat_user_apply",["uniacid"=>$uniacid,"id"=>$id]);
    $user = pdo_get("chat_users",["uniacid"=>$uniacid,"id"=>$res['user_id']],["openid","id","invitation_code"]);

    if($id<=0 || $res['is_auditing']!=3){
        itoast('操作失败','','error');die;
    }else{

            if($res['level']==1){
                $like_name = ' province LIKE :province ';//省
                $keyword = ":province";
                $short_name =$res['province'];
            }
            else if($res['level']==2){
                $like_name = ' city LIKE :city';//市
                $keyword = ":city";
                $short_name =$res['city'];

            }
            else if($res['level']==3){
                $like_name = ' area LIKE :area';//区
                $keyword = ":area";
                $short_name =$res['area'];
            }

            if(!empty($keyword)){
                $is_user = pdo_fetch("SELECT upid as id FROM ".tablename('chat_user_partition').' WHERE type=:type AND level=:level AND '.$like_name,array($keyword=> '%'.$short_name.'%',':type'=>($res['difference']==2 ? 3 : $res['difference']),':level'=>$res['level']));
            }

            if($is_user!=false && $_GPC['par']==1 && $res['difference']==1){
                itoast('操作失败，该区域已有人员','','error');die;
            }

            $result = pdo_update("chat_user_apply",["is_auditing"=>$_GPC['par']],["uniacid"=>$uniacid,"id"=>$id]);

            $invitation_code=$user['invitation_code'];
            $audiu_data = [
                "hhr_id"=>$res['user_id']
                        ];
            if($result){
                if($res['difference']==1){
                    $name = "城市合伙人";
                    $audiu_data['is_partner']=1;
                    $audiu_data['is_salesman']=1;

                }else if($res['difference']==2){
                    $name = "税媒大使";
                    $audiu_data['is_salesman']=1;
                }

                if($_GPC['par']==1){
                    if($user['invitation_code']<=0){
                        $invitationCode = invitationCode();//邀请码
                    }

                    $audit_status = "通过";
                    $data = [
                        "uniacid"=>$uniacid,
                        "uid"=>$res['user_id'],
                        "city"=>$res['city'],
                        "province"=>$res['province'],
                        "area"=>$res['area'],
                        "type"=> ($res['difference']==2 ? 3 : $res['difference']),
                        "level"=>$res['level'],
                        "ssq"=>$res['province'].($res['city'] ? "," : '').$res['city'].($res['area'] ? "," : '').$res['area'],
                        "create_time"=>time()
                    ];
                   $par = pdo_insert("chat_user_partition",$data);

                    $audiu_data['invitation_code']=$invitationCode;
                    pdo_update("chat_users",$audiu_data,["id"=>$res['user_id']]);
                }else{
                    $audit_status = "拒绝";
                }

                $weObj = WeAccount::create($_W['uniacid']);
                    $send_data = array(
                        'first'=>array('value'=>'您好，您的审核请求已经处理'),
                        'keyword1'=>array('value'=>$name,'color'=>'#173177'),
                        'keyword2'=>array('value'=>$audit_status,'color'=>'#173177'),
                        'keyword3'=>array('value'=>date('Y年m月d日 H:i:s',time()),'color'=>'#173177'),
                        'remark'=>array('value'=>'点击查看详情','color'=>'#173177'),
                    );
                $url = $_W['siteroot'].'app/'.$this->createMobileUrl('ask_index',array('from'=>'template'));
                $weObj->sendTplNotice($user['openid'], 'bqSYSyEgX_GEk8xbV8Ah6qd2mQ7xmyLBSX4rclAbO2Q', $send_data, $url, '#173177');

                 //App 消息
                $push_data = array(
                    'type'=>"link",
                    'title'=>$name.'审核通知',
                    'content'=> "您好，您申请的".$name."审核已". $audit_status."，如有疑问，请咨询客服",
                    'url'=>$this->createMobileUrl('ask_index',array('from'=>'template'))
                );
                $this->pushMessageToSingle($user['id'],$push_data);

            itoast('操作成功','','success');die;
        }else{
            itoast('操作失败','','error');die;

        }
    }
}


//代理人
if($op == "user_agent"){
    $pindex = max(1, intval($_GPC['page']));
    $tempcondition=" WHERE uniacid = '{$_W['uniacid']}'";
    if(!empty($_GPC['keyword'])){
        //根据用户昵称丶电话丶真实姓名"
       $tempcondition .= " AND ( qy_name LIKE '%{$_GPC['keyword']}%' OR mobile LIKE '%{$_GPC['keyword']}%' OR zjzch LIKE '%{$_GPC['keyword']}%' ) ";
    }
    if(!empty($_GPC['status']) && $_GPC['status'] != 99){
        //根据用户昵称丶电话丶真实姓名"
       $tempcondition .= " AND status =".$_GPC['status'];
    }

    $psize = 20;
    $totalnum = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename("chat_qyrz") . $tempcondition);
    $audit = pdo_fetchall("SELECT * FROM ".tablename("chat_qyrz").$tempcondition ." ORDER BY status asc,create_time ASC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
    foreach ($audit as $key => $value) {
        $users = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$value['uid']));
        $audit[$key]['nickname'] = !empty($users['real_name']) ? $users['real_name'] : $users['nickname'];
        if($value['status'] == 1){
            $status = '审核中';
        }
        if($value['status'] == 2){
            $status = '已通过';
        }
        if($value['status'] == 3){
            $status = '不通过';
        }
        $audit[$key]['status'] = $status;
    }
    $pager = pagination($totalnum, $pindex, $psize);

}
//企业认证详情
if($op == "qyrz_xq"){
    if(empty($_GPC['qy_id'])){
        itoast('缺少qy_id参数','','error');die;
    }
    $qyrz = pdo_get('chat_qyrz',array('uniacid'=>$_W['uniacid'],'qy_id'=>$_GPC['qy_id']));
    $yyzz = explode(',', $qyrz['yyzz']);//身份证图片
}

//企业认证审核
if($op == "qyrz_audit"){
    if($_W['ispost']){
        if(empty($_POST['uid'])){
            echo json_encode(array('code'=>2,'msg'=>'缺少uid参数'));exit;
        }
        if(empty($_POST['status'])){
            echo json_encode(array('code'=>2,'msg'=>'缺少status参数'));exit;
        }
        if(empty($_POST['qy_id'])){
            echo json_encode(array('code'=>2,'msg'=>'缺少qy_id参数'));exit;
        }

        $qyrzData = pdo_fetch('SELECT * FROM ' . tablename("chat_qyrz") . " where uniacid = ".$_W['uniacid']." and qy_id=".$_POST['qy_id']); //企业认证信息
        $user = pdo_fetch('SELECT * FROM ' . tablename("chat_users") . " where uniacid = ".$_W['uniacid']." and id=".$_POST['uid']); //用户信息

        $result = pdo_update("chat_qyrz", array('status'=>$_POST['status'],'audit_time'=>time(),'pass_reason'=>$_POST['pass_reason']), array('uniacid'=>$_W['uniacid'],'qy_id'=>$_POST['qy_id']));

        if(!empty($result)){
            //通过
            if($_POST['status'] == 2){
                //修改用户工作单位
                pdo_update("chat_users", array('user_unit'=>$qyrzData['qy_name']), array('uniacid'=>$_W['uniacid'],'id'=>$_POST['uid']));
                $audit_status = '已通过';
            }
            //不通过
            if($_POST['status'] == 3){
                $audit_status = '不通过';
            }

            $weObj = WeAccount::create($_W['uniacid']);
            $send_data = array(
                'first'=>array('value'=>'您好，您的审核请求已经处理'),
                'keyword1'=>array('value'=>'企业认证','color'=>'#173177'),
                'keyword2'=>array('value'=>$audit_status,'color'=>'#173177'),
                'keyword3'=>array('value'=>date('Y年m月d日 H:i:s',time()),'color'=>'#173177'),
                'remark'=>array('value'=>'点击查看详情（跳转到企业认证页面）','color'=>'#173177'),
            );

            $url = $_W['siteroot'].'app/'.$this->createMobileUrl('my_qyrz',array('from'=>'template'));
            $weObj->sendTplNotice($user['openid'], 'bqSYSyEgX_GEk8xbV8Ah6qd2mQ7xmyLBSX4rclAbO2Q', $send_data, $url, '#173177');

             //App 消息
            $push_data = array(
                'type'=>"link",
                'title'=>'企业认证审核通知',
                'content'=> "您好，您申请的企业认证审核". $audit_status."，如有疑问，请咨询客服",
                'url'=>$this->createMobileUrl('my_qyrz',array('from'=>'template'))
            );
            $this->pushMessageToSingle($user['id'],$push_data);

            echo json_encode(array('code'=>1,'msg'=>'操作成功'));exit;
        }else{
            echo json_encode(array('code'=>2,'msg'=>'操作失败'));exit;
        }
    }
}

//代理列表
if($op == 'agentList'){
    // 前台加载模型数据
    $pindex = max(1, intval($_GPC['page']));
    $psize = 20;
    $condition = " where uniacid = :uniacid ";
    $where[':uniacid'] = $_W['uniacid'];

    //时间查询
    if($_GPC['status_select'] != '' && $_GPC['status_select'] != 1){
        $condition .=  ' AND '.$_GPC['status_select'].' BETWEEN :start and :end';
        $where[':start'] = strtotime($start.' 00:00:00 ');
        $where[':end'] = strtotime($end.' 23:59:59 ');
    }

    //状态查询
    if($_GPC['to_examine'] != '' && $_GPC['to_examine'] != 10){
        $condition .= " AND status =:status";
        $where[':status'] = $_GPC['to_examine'];
    }

    //关键字查询
    if($_GPC['content'] != ''){
        $condition .= " AND ( name LIKE '%{$_GPC['content']}%' or mobile LIKE '%{$_GPC['content']}%' )";
    }

    $total=pdo_fetch('select count(*) as num from'.tablename('chat_agent').$condition,$where);
    $chat_agent = pdo_fetchall('select * from'.tablename('chat_agent').$condition.' order by create_time desc limit '.($pindex - 1) * $psize .',' .$psize.'',$where);

    $pager = pagination($total['num'], $pindex, $psize);
}

//更改代理状态
if($op == 'set_agent_status'){
    $agid = $_GPC['agid'];
    if($_POST){
        $agent_update = pdo_update('chat_agent',array('status'=>$_GPC['status'],'remarks'=>$_GPC['remarks']),array('agid'=>$_GPC['agid']));
        if($agent_update){
            echo json_encode(array('code'=>1,'msg'=>'操作成功'));exit;
        }else{
            echo json_encode(array('code'=>2,'msg'=>'操作失败'));exit;
        }
    }
}

include $this->template('chat_audit');