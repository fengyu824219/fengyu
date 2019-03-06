<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$op = empty($_GPC['op']) ? 'display' : $_GPC['op'];
$action=$_GPC['action']?$_GPC['action']:'';
$acid = $_W['account']['uniacid'];

$role = $_W['role'];

if ($op == "display") {
    $setTopUpdate = pdo_update('chat_index_record',array('is_top'=>0,'top_start_time'=>0,'top_end_time'=>0),array('uniacid'=>$_W['uniacid'],'top_end_time <'=>time(),'is_top'=>1));//处理过期置顶

    $pindex = max(1, intval($_GPC['page']));
    $psize  = 20;

    $condition = ' where r.uniacid = :uniacid ';
    $where     = array(":uniacid" => $_W['uniacid']);

    //时间
    if ($_GPC['status_select'] != 1 && $_GPC['status_select'] == 'create_time') {
        if ($_GPC['time']['start'] && $_GPC['time']['end']) {
            $condition       .= ' AND r.create_time' . $_GPC['create_time'] . ' BETWEEN :start and :end';
            $where[':start'] = strtotime($_GPC['time']['start'] . ' 00:00:00');
            $where[':end']   = strtotime($_GPC['time']['end'] . ' 23:59:59');
        }
    }

    if ($_GPC['name']) {
        $condition    .= " AND (r.title LIKE '%".$_GPC['name'] . "%' OR  r.real_name LIKE '%".$_GPC['name'] . "%' or r.nickname LIKE '%".$_GPC['name'] . "%')";
    }

    //上下架
    if ($_GPC['status'] != 7 && $_GPC['status'] != '') {
        $condition        .= " AND r.status =:status";
        $where[':status'] = $_GPC['status'];
    }
    //是否置顶
    if ($_GPC['is_top'] != 7 && $_GPC['is_top'] != '') {
        $condition        .= " AND r.is_top =:is_top";
        $where[':is_top'] = $_GPC['is_top'];
    }
    //栏目
    if ($_GPC['imid'] != 7 && $_GPC['imid'] != '') {
        $condition      .= " AND r.imid =:imid";
        $where[':imid'] = $_GPC['imid'];
    }
    //类型
    if ($_GPC['type'] != 7 && $_GPC['type'] != '') {
        $condition      .= " AND r.type =:type";
        $where[':type'] = $_GPC['type'];
    }

    //栏目
    $m_data = pdo_fetchall('select imid,name,status from ' . tablename('chat_index_menu') . ' where uniacid = ' . $_W['uniacid']);

    $total = pdo_fetchcolumn(' select count(*) as num  from ' . tablename('chat_index_record') . ' r left join ' . tablename('chat_index_menu') . ' m on m.imid = r.imid ' . ' left join ' . tablename('chat_article_article') . ' a on a.id = r.reid ' . $condition ." and if(r.type = 1,a.type = 1,'1=1') ", $where);

    $rec = pdo_fetchall(' select r.*,m.name,a.type as artType from ' . tablename('chat_index_record') . ' r left join ' . tablename('chat_index_menu') . ' m on m.imid = r.imid ' . ' left join ' . tablename('chat_article_article') . ' a on a.id = r.reid ' . $condition . " and if(r.type = 1,a.type = 1,'1=1') " . ' order by r.create_time desc limit ' . ($pindex - 1) * $psize . ',' . $psize . '', $where);

    foreach ($rec as $k => $v){
        if($v['type'] == 2){
            $rec[$k]['pinglun'] = pdo_fetch('SELECT COUNT(*) as num FROM '.tablename('chat_article_ask_comment')
          .' where type_id='.$v['reid'] . ' AND pid=0 AND is_show <> 3 AND type="ask" ');
        } else if($v['type'] == 1){
            $rec[$k]['pinglun'] = pdo_fetch('SELECT COUNT(*) as num FROM '.tablename('chat_article_ask_comment')
          .' where type_id='.$v['reid'] . ' AND pid=0  AND is_show <> 3 AND type="article"  ');
        }
    }

    $pager = pagination($total, $pindex, $psize);
}

//批量操作
if($_GPC['op'] == 'batchOperation'){
    if(!empty($_GPC['irids']) && $_GPC['parameter'] != '' && $_GPC['name'] != ''){
        $irids = explode(',',$_GPC['irids']);
        if($_GPC['name'] == 'SetStatus'){//批量上下架
            foreach ($irids as $key => $val) {
                $record_update = pdo_update('chat_index_record',array('status'=>$_GPC['parameter']),array('irid'=>$val,'uniacid'=>$_W['uniacid']));
            }
        }else{//批量置顶或取消置顶
            $setTopData = array();
            if($_GPC['parameter'] == 1){//置顶 增加置顶开始时间和结束时间
                $setTopData['top_start_time'] = time();
                if($_GPC['dayNumber'] != 0 && $_GPC['dayNumber'] != ''){
                    $setTopData['top_end_time'] = time() + ($_GPC['dayNumber'] * 86400);
                }
            }else{//取消置顶 去除置顶开始时间和结束时间
                $setTopData['top_start_time'] = 0;
                $setTopData['top_end_time'] = 0;
            }
            foreach ($irids as $key => $val) {
                $setTopData['is_top'] = $_GPC['parameter'];
                $record_update = pdo_update('chat_index_record',$setTopData,array('irid'=>$val,'uniacid'=>$_W['uniacid']));
            }
        }
        if($record_update){
            echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
        }else{
            echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
        }
    }
}

//上下架
if ($_GPC['op'] == 'shangjia') {
    $irid = $_GPC['irid'];
    if (empty($irid)) {
        itoast("此数据不存在", '', error);
    } else {
        if ($_GPC['status'] == 0) {
            $status = 1;
        } else {
            $status = 0;
        }

        $temp = pdo_update("chat_index_record", ['status' => $status], array('irid' => $irid));
        $temp_jx = pdo_get("chat_index_record", array('irid' => $irid));

        if($_GPC['status']=="0"){
            if($temp_jx['is_template']!=1){
                $type = pdo_get("chat_index_record",["irid"=>$irid]);
                //$count = pdo_get("chat_index_record",["reid"=>$type['reid'],"type"=>$type['type']]);
                if($type['type']==1){
                    $name = "恭喜你,您发布的文章被选定为精选文章";
                    $article = pdo_get("chat_article_article",["id"=>$type['reid']]);
                    $user = pdo_get("chat_users",["id"=>$article['uid']]);
                    $title = $user['real_name'] ?  $user['real_name'] :  $user['nickname'];
                    $op = '&op=ask_article_lst&id='.$type['reid'].'&type=1';
                } else if($type['type']==2){
                    $name = "恭喜你,您提问的问答被选定为精选问答";
                    $ask_title = pdo_get("chat_ask",["id"=>$type['reid']]);
                    //是问答还是悬赏
                    if($ask_title['ask_type']==1){//问答
                        $zj_id = $ask_title['fuid'];
                    } else if($ask_title['ask_type']==2){//悬赏
                        $zj_id = $ask_title['payto_uid'];
                    }
                    $user = pdo_get("chat_users",["id"=>$ask_title['pay_uid']]); //用户
                    $zj_user = pdo_get("chat_users",["id"=>$zj_id]);//专家
                    $title = $user['real_name'] ?  $user['real_name'] :  $user['nickname'];
                    $op = '&op=ask_article_lst&id='.$type['reid'].'&type=2';
                }
                $weObj = WeAccount::create($_W['uniacid']);
                $send_data = array(
                    'first'=>array('value'=>$name),
                    'keyword1'=>array('value'=>$title),
                    'keyword2'=>array('value'=>$temp_jx['title']),
                    'keyword3'=>array('value'=>date("Y-m-d H:i:s",time())),
                    'remark'=>array('value'=>'点击查看详情'),
                );

                //专家模版
                $zj_data = array(
                    'first'=>array('value'=>"恭喜你,您回答的问答被选定为精选问答"),
                    'keyword1'=>array('value'=>$title),
                    'keyword2'=>array('value'=>$temp_jx['title']),
                    'keyword3'=>array('value'=>date("Y-m-d H:i:s",time())),
                    'remark'=>array('value'=>'点击查看详情'),
                );
                $url = 'https://'.$_SERVER['HTTP_HOST'].'/app/index.php?i='.$_W['uniacid'].'&c=entry&do=ask_index&m=dg_ask'.$op.'&from=template';
                $weObj->sendTplNotice($user['openid'], 'UHXZFZPB4YsarRYDb8_dLLrNMYi63l0qqPQQUfduIbY', $send_data, $url, '#173177');

                $weObj->sendTplNotice($zj_user['openid'], 'UHXZFZPB4YsarRYDb8_dLLrNMYi63l0qqPQQUfduIbY', $zj_data, $url, '#173177');
                //pdo_update("chat_index_record", ['is_template' => 1], array('irid' => $irid));

                pdo_update("chat_index_record", ['is_template' => 1], array('reid' => $type['reid'],'type'=>$type['type']));
            }
        }
        itoast('操作成功', '', success);
    }
}

//删除
if ($_GPC['op'] == 'delete') {
    $irid = $_GPC['irid'];
    if (empty($irid)) {
        itoast("此数据不存在", '', error);
    } else {
        $temp = pdo_delete("chat_index_record",array('irid' => $irid));

        if($temp && $_GPC['reid']){
            $ask_art = pdo_fetch('select * from'.tablename('chat_index_record').' where uniacid = :uniacid and reid = :reid ',array(':uniacid'=>$_W['uniacid'],':reid'=>$_GPC['reid']));//查询是否还有此文章或问答的类目
            if(empty($ask_art)){//文章
                if($_GPC['type'] == 1){
                    $art_update = pdo_update('chat_article_article',array('status'=>0),array('id'=>$_GPC['reid'],'uniacid'=>$_W['uniacid']));
                }else if($_GPC['type'] == 2){//问答
                    $ask_update = pdo_update('chat_ask',array('is_chosen'=>0),array('id'=>$_GPC['reid'],'uniacid'=>$_W['uniacid']));
                }
            }
        }

        itoast('操作成功', '', success);
    }
}

//查看
if($_GPC['op'] == 'examine') {
    if($_GPC['irid'] && $_GPC['type']){//判断关系表的id和文章或问答的id是否存在
        $type = $_GPC['type'];
        //判断是什么类型的
        if($_GPC['type'] == 1){//文章
            $rec_data = pdo_fetch('select * from'.tablename('chat_index_record').' where uniacid = :uniacid and irid = :irid ',array(':uniacid'=>$_W['uniacid'],'irid'=>$_GPC['irid']));//关系表
            $art_data = pdo_fetch('select * from'.tablename('chat_article_article').' where uniacid = :uniacid and id = :id ',array(':uniacid'=>$_W['uniacid'],':id'=>$rec_data['reid']));//文章表

            $tags = pdo_get('chat_ask_tags',array('id'=>$rec_data['tid'],'uniacid'=>$_W['uniacid']));//分类
            $user = pdo_get('chat_users',array('id'=>$art_data['uid'],'uniacid'=>$_W['uniacid']));//用户
            $country = pdo_get('chat_position',array('id'=>$art_data['country']));//国
            $province = pdo_get('chat_position',array('id'=>$art_data['province']));//省
            $city = pdo_get('chat_position',array('id'=>$art_data['city']));//市
            $area = pdo_get('chat_position',array('id'=>$art_data['area']));//区
            $index_menu = pdo_get('chat_index_menu',array('uniacid'=>$_W['uniacid'],'imid'=>$rec_data['imid']));//栏目


        }elseif($_GPC['type'] == 2){//问答
            $rec_data = pdo_fetch('select * from'.tablename('chat_index_record').' where uniacid = :uniacid and irid = :irid ',array(':uniacid'=>$_W['uniacid'],'irid'=>$_GPC['irid']));//关系表
            $ask_data = pdo_fetch('select * from'.tablename('chat_ask').' where uniacid = :uniacid and id = :id ',array(':uniacid'=>$_W['uniacid'],':id'=>$rec_data['reid']));//问答

            $imgs = explode(',',$ask_data['ask_imgs']);//图片
            $tags = pdo_get('chat_ask_tags',array('id'=>$rec_data['tid'],'uniacid'=>$_W['uniacid']));//分类
            $user = pdo_get('chat_users',array('id'=>$ask_data['pay_uid'],'uniacid'=>$_W['uniacid']));//用户
            $index_menu = pdo_get('chat_index_menu',array('uniacid'=>$_W['uniacid'],'imid'=>$rec_data['imid']));//栏目
            if($ask_data['id']){
                $answer = pdo_fetch('select * from'.tablename('chat_ask_answer').' an INNER join '.tablename('chat_ask').' ak ON an.ask_id = ak.id '.' where an.uniacid = '.$_W['uniacid'].' and an.ask_id = '.$ask_data['id'].' and an.status = 1 ');//问答和回答
            }
        }
    }

}

//置顶
if ($_GPC['op'] == 'zhiding') {
    if($_POST){
        if(!empty($_GPC['irid'])){
            $top_end_time = time() + ($_GPC['dayNumber'] * 86400);
            $temp = pdo_update("chat_index_record", array('is_top' => 1,'top_start_time'=>time(),'top_end_time'=>$top_end_time), array('irid' =>$_GPC['irid']));
            if($temp){
                itoast('操作成功', $this->createWebUrl('chat_index', array('eid' => $_GPC['eid'], 'op' => 'display', 'version_id' => 0)), success);
            }else{
                itoast('操作失败', '', error);
            }
        }
    }
    if(!empty($_GPC['irid'])){
        if($_GPC['is_top'] == 1){
            $temp = pdo_update("chat_index_record", array('is_top' => $is_top,'top_start_time'=>0,'top_end_time'=>0), array('irid' =>$_GPC['irid']));
            if($temp){
                itoast('操作成功', '', success);
            }else{
                itoast('操作失败', '', error);
            }
        }
        $record = pdo_get('chat_index_record',array('uniacid'=>$_W['uniacid'],'irid'=>$_GPC['irid']));
    }
}

//排序通用方法
if ($_GPC['op'] == "paixu") {

    //表名
    $tab = $_GPC['tab'];
    //截取第一个逗号
    $key   = trim($_GPC['key'], ',');
    $value = trim($_GPC['value'], ',');

    //字符串转 数组  准备合并
    $key_arr   = explode(',', $key);
    $value_arr = explode(',', $value);

    $data = array_combine($key_arr, $value_arr);
    if ($tab == "chat_index_menu") {
        $id = "imid"; //栏目排序
        $op = "lanmu";
    } else if ($tab == "chat_index_record") {
        $id = "irid"; //精选排序
        $op = "display";
    }

    foreach ($data as $k => $v) {
        pdo_update($tab, ['sort' => $v], array($id => $k));
    }
    itoast('操作成功', '', success);

}


#---------------------------------------------栏目管理---------------------------------------
if ($op == "lanmu") {
    $pindex = max(1, intval($_GPC['page']));
    $psize  = 20;


    $condition = ' where uniacid=:uniacid and del=:del ';
    $where     = array(":uniacid" => $_W['uniacid'], ':del' => 1);


//时间
    if ($_GPC['status_select'] != 1 && $_GPC['status_select'] == 'create_time') {
        if ($_GPC['time']['start'] && $_GPC['time']['end']) {
            $condition       .= ' AND create_time' . $_GPC['create_time'] . ' BETWEEN :start and :end';
            $where[':start'] = strtotime($_GPC['time']['start'] . ' 00:00:00');
            $where[':end']   = strtotime($_GPC['time']['end'] . ' 23:59:59');
        }
    }

    if ($_GPC['name']) {
        $condition      .= " AND title LIKE :name";
        $where[':name'] = '%' . $_GPC['name'] . '%';
    }


//上下架
    if ($_GPC['status'] != 7 && $_GPC['status'] != '') {
        $condition        .= " AND status =:status";
        $where[':status'] = $_GPC['status'];
    }

    $total = pdo_fetch('select count(*) as num from ' . tablename('chat_index_menu') . $condition, $where);
    $adv   = pdo_fetchall('select * from ' . tablename('chat_index_menu') . $condition . ' order by sort desc limit ' . ($pindex - 1) * $psize . ',' . $psize . '', $where);
    $pager = pagination($total['num'], $pindex, $psize);
}

if ($op == "add_lanmu") {
    if($_GPC['imid'] != ""){
        $lanmu = pdo_fetch('select *  from ' . tablename('chat_index_menu') . " where imid=" .$_GPC['imid']);
    }

    if (checksubmit('submit')) {
        $data = [
            'uniacid' => $_W['uniacid'],
            'name'    => $_GPC['name'],
            'price'    => $_GPC['price'],
            'type'    => $_GPC['type']
        ];
        if (empty($_GPC['imid'])) {
            $data['create_time'] = time();
            $res                 = pdo_insert('chat_index_menu', $data);

        } else {
            $res = pdo_update('chat_index_menu', $data, array('imid' => $_GPC['imid']));
        }
        if ($res != false) {
            itoast('操作成功', $this->createWebUrl('chat_index', array('eid' => $_GPC['eid'], 'op' => 'lanmu', '', 'version_id' => 0)), success);
        } else {
            itoast('操作失败', '', error);
        }
    }
}

//上下架
if ($_GPC['op'] == 'lan_status') {
    $imid = $_GPC['imid'];
    $del  = $_GPC['del'];
    if (empty($imid)) {
        itoast("此数据不存在", '', error);
    } else {
        //软删
        if ($del == 2) {
            $status = ['del' => 2];
        } else {
            //上下架
            if ($_GPC['status'] == 0) {
                $status = ['status' => 1];
            } else {
                $status = ['status' => 0];
            }
        }
        $temp = pdo_update("chat_index_menu", $status, array('imid' => $imid));

        itoast('操作成功', '', success);
    }
}

/**
 * [$action 预览]
 */
if($op == 'preview'){
    $id = $_GPC['id'];
    if(!$id){echo json_encode(array('code'=>100, 'msg'=>'参数错误'));exit;}
    $previewlog = pdo_get('chat_preview_log',array('uniacid'=>$_W['uniacid'],'type'=>$_GPC['type'],'type_id'=>$id,'create_time >='=>time()-7200));
    if(!empty($previewlog)){
        echo json_encode(array('code'=>200,'url'=>$_W['siteroot'].'app/index.php?i='.$_W['uniacid'].'&c=entry&do=index&m=seapow_sm&pvid='.$pvid));exit;
    }
    $preview = array(
        'uniacid' => $_W['uniacid'],
        'type' => $_GPC['type'],
        'type_id' => $id,
        'create_time' => time(),
        'admin_uid' =>$_W['user']['uid']
    );
    $res = pdo_insert('chat_preview_log',$preview);
    $pvid = pdo_insertid();
    if($pvid){
        echo json_encode(array('code'=>200,'url'=>$_W['siteroot'].'app/index.php?i='.$_W['uniacid'].'&c=entry&do=index&m=seapow_sm&pvid='.$pvid));exit;
    }else{
        echo json_encode(array('code'=>100,'msg'=>'参数错误'));exit;
    }
}


/*
 * 管理评论
 * */
if($op=="message"){
    $pagesize=10;
    $pageindex =max(1,intval($_GPC['page']));
    if($_GPC['type'] ==1){
         $condition = ' AND type="article"';
    } else if($_GPC['type']==2){
         $condition = ' AND type="ask"';
    }else{
         $condition = ' AND type="weike"';
    }

    $pager = pagination($totals, $pageindex, $pagesize);
    $p = ($pageindex-1) * $pagesize;
    $totals =  pdo_fetch('SELECT COUNT(*) as num FROM '.tablename('chat_article_ask_comment') .' where pid=0 AND is_show <> 3 and type_id='.$_GPC['reid'] .$condition);
    $data = pdo_fetchall("SELECT A.*,(SELECT content FROM ".tablename("chat_article_ask_comment") . " B WHERE B.pid=A.aaid limit 1) as hf_content
     FROM ".tablename("chat_article_ask_comment")." a WHERE A.pid=0 AND is_show <> 3 AND A.type_id=".$_GPC['reid'].$condition." GROUP BY aaid ORDER BY create_time DESC LIMIT ".$p.",".$pagesize);

}

//回复
if($op=="reply"){
        $aaid = $_GPC['aaid']; //评论ID
        $type_id = $_GPC['type_id'];//内容ID
        $type = $_GPC['type']; //内容类型
        $uid = $_GPC['uid'];
        $is_answer = $_GPC['is_answer'];
        $hj_content = $_GPC['cont_info'];

    if($_W['ispost']){
        $data = array(
            "uid"=>$_GPC['uid'],
            "content"=>$_GPC['content'],
            "pid"=>$_GPC['aaid'],
            "type_id"=>$_GPC['type_id'],
            "uniacid"=>$acid,
            "content"=>$_GPC['info'],
            "create_time"=>time(),
            "type"=>$_GPC["type"]
       );
          $ty_s = $_GPC['type'] == 'article' ? 1 : 2;
        //编辑回复
        if($_GPC['is_answer']==1){
            $reley = pdo_update("chat_article_ask_comment",array("content"=>$_GPC['info'],"answer_time"=>time()),array("pid"=>$_GPC['aaid'],"type"=>$_GPC["type"]));
            pdo_update("chat_article_ask_comment",array("answer_time"=>time()),array("aaid"=>$_GPC['aaid']));
                if($reley){
                    itoast('修改成功',$this->createWebUrl('chat_index',array('op' => 'message','reid'=>$_GPC['type_id'],'eid'=>$_GPC['eid'],'version_id'=>0,'type'=> $ty_s)), "success");
                }else{
                    itoast('修改失败',$this->createWebUrl('chat_index',array('op' => 'message','reid'=>$_GPC['type_id'],'eid'=>$_GPC['eid'],'version_id'=>0,'type'=>$ty_s)), "error");
                }
        // 回复
        }else{
            $reply = pdo_insert("chat_article_ask_comment",$data);
                if($reply){
                    pdo_update("chat_article_ask_comment",array("is_answer"=>1,"answer_time"=>time()),array("uniacid"=>$acid,"aaid"=>$_GPC['aaid'])); //评论状态更改为  已评论

                    itoast('回复成功',$this->createWebUrl('chat_index',array('op' => 'message','reid'=>$_GPC['type_id'],'eid'=>$_GPC['eid'],'version_id'=>0,'type'=> $ty_s)), "success");
                }else{
                    itoast('回复失败',$this->createWebUrl('chat_index',array('op' => 'message','reid'=>$_GPC['type_id'],'eid'=>$_GPC['eid'],'version_id'=>0,'type'=> $ty_s)), "error");
                }
        }
    }
}


/*
 * 删除评论
 * */
if($op == "delete_message"){
    $id=$_GPC['aaid'];

    $ty_s = $_GPC['type'] == 'article' ? 1 : 2;
    $result = pdo_update('chat_article_ask_comment',["is_show"=>3] ,array("uniacid"=>$acid,'aaid' => $id));

    if ($result) {
        itoast('删除成功',$this->createWebUrl('chat_index',array('op' => 'message','reid'=>$_GPC['type_id'],'eid'=>$_GPC['eid'],'version_id'=>0,'type'=> $ty_s)), "success");
    }else{
        itoast('删除失败',$this->createWebUrl('chat_index',array('op' => 'message','reid'=>$_GPC['type_id'],'eid'=>$_GPC['eid'],'version_id'=>0,'type'=> $ty_s)), "error");
    }
}


/*
 * 显示与隐藏
 * */
if($op == "is_show"){
    $id=$_GPC['aaid'];
    $show = $_GPC['is_show']==1 ? 0 :($_GPC['is_show']=='0' ? 1 : 2);
    $ty_s = $_GPC['type'] == 'article' ? 1 : 2;
    $result = pdo_update('chat_article_ask_comment',["is_show"=>$show] ,array("uniacid"=>$acid,'aaid' => $id));
    if ($result) {
        itoast('操作成功',$this->createWebUrl('chat_index',array('op' => 'message','reid'=>$_GPC['type_id'],'eid'=>$_GPC['eid'],'version_id'=>0,'type'=> $ty_s)), "success");
    }else{
        itoast('操作失败',$this->createWebUrl('chat_index',array('op' => 'message','reid'=>$_GPC['type_id'],'eid'=>$_GPC['eid'],'version_id'=>0,'type'=> $ty_s)), "error");
    }
}




include $this->template('chat_index');
?>