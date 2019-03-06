<?php

//decode by http://www.yunlu99.com/
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$uniacid = $_W['uniacid'];

    if (!empty($_GPC['id'])) {
        header('content-type:application/json;charset=utf8');
        $ask_id = intval($_GPC['id']);
        $op     = $_GPC['op'];
        $ask    = pdo_fetch("SELECT * FROM " . tablename("chat_ask") . " WHERE id=:id", array(":id" => $ask_id));
        if ($op == 'hot') {
            $is_hot = $ask['is_hot'];
            if ($is_hot == "0") {
                $is_hot = 1;
            } else {
                $is_hot = 0;
            }
            pdo_update("chat_ask", array('is_hot' => $is_hot), array("id" => $ask_id));
            $fmdata = array("success" => 1, "data" => $is_hot);
            echo json_encode($fmdata);
            exit;
        }
        // if ($op == "del") {
        //     $id = intval($_GPC['id']);
        //     pdo_delete("chat_ask", array("id" => $id));
        //     $fmdata = array("success" => 1, "data" => "删除成功!", "id" => $id);
        //     header('content-type:application/json;charset=utf8');
        //     echo json_encode($fmdata);
        //     exit;
        // }
        if ($op == "caina") {
            $id = intval($_GPC['id']);

        }

        if ($op == 'is_chosen') {
            if($ask['open'] == 1){
                $fmdata = array(
                    "success" => 2,
                    "data"    => "私密不能设为精选!"
                );
                echo json_encode($fmdata);exit;
            }else if($ask['open'] == 2){
                $is_chosen = $ask['is_chosen'];
                if ($is_chosen == "0") {
                    $is_chosen = 1;
                } else {
                    $is_chosen = 0;
                }
                pdo_update("chat_ask", array('is_chosen' => $is_chosen), array("id" => $ask_id));
                $fmdata = array(
                    "success" => 1,
                    "data"    => "操作成功!",
                    "is_cho"      => $is_chosen
                );
                echo json_encode($fmdata);exit;
            }
        }
    }


    if ($_GPC['op'] == "sendall") {
        $ids                = implode(",", $_GPC['ids']);
        $cfg                = $this->module['config'];
        $ask                = pdo_fetch("select * from " . tablename("chat_ask") . " where id in (" . $ids . ") order by pay_money desc limit 1");
        $recommend_template = $cfg['recommend_templete'];
        if (empty($recommend_template)) {
            return;
            exit;
        }
        $post_data  = array('first' => array('value' => "您有新的可接任务", "color" => "#4a5077"), 'keyword1' => array('value' => "悬赏", "color" => "#4a5077"), 'keyword2' => array('value' => "最高 " . $ask["pay_money"] . " 元", "color" => "#FF0000"), 'keyword3' => array('value' => date("Y-m-d h:i", time()), "color" => "#4a5077"), 'remark' => array('value' => "点击进入任务大厅，完成任务拿赏金", "color" => "#09BB07"));
        $url        = $this->get_normal_url('recommenlist', array("ids" => $ids));
        $expertlist = pdo_fetchall("select * from " . tablename("chat_users") . " where is_openask=1 and uniacid=:uniacid", array(":uniacid" => $uniacid));
        for ($i = 0; $i < count($expertlist); $i++) {
            $expert     = $expertlist[$i];
            $sendResult = $this->sendTplNotice($expert["openid"], $recommend_template, $post_data, $url);
        }
        $fmdata = array("success" => 1, "count" => count($expertlist));
        echo json_encode($fmdata);
        exit;
    }
    
    $is_answer = $_GPC['is_answer'];
    $tempcondition = " AND A1.uniacid=" . $uniacid;
    if ($is_answer != "" && $is_answer != 7) {
        $tempcondition .= " AND A1.reward_status=" . $is_answer;
    }

    //时间查询
    if(!empty($_GPC['status_select']) && $_GPC['status_select'] != 1){
        $tempcondition .= " AND A1.".$_GPC['status_select']." BETWEEN :start and :end ";
        $tempArray[':start'] = strtotime($_GPC['time']['start'].' 00:00:00');
        $tempArray[':end'] = strtotime($_GPC['time']['end'].' 23:59:59');
    }

    $keyword = $_GPC['keyword'];
    if (!empty($keyword)) {
        $tempcondition = $tempcondition . " AND (A1.ask_content LIKE '%{$keyword}%' OR A1.pay_nickname LIKE '%{$keyword}%' OR A2.real_name LIKE '%{$keyword}%' OR A2.user_title LIKE '%{$keyword}%')";
    }
    $pindex = max(1, intval($_GPC['page']));
    $psize  = 20;
    $total  = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename("chat_ask") . " A1 LEFT JOIN " . tablename("chat_users") . " A2 ON A1.payto_uid=A2.id WHERE  ask_type=2 " . $tempcondition . " AND pay_sta=2", $tempArray);

    $records = pdo_fetchall("SELECT A1.*,A2.nickname,A2.real_name,A2.user_title,A2.avatar FROM " . tablename("chat_ask") . " A1 LEFT JOIN " . tablename("chat_users") . " A2 ON A1.pay_uid=A2.id WHERE A1.pay_sta =2 " . $tempcondition . " AND A1.ask_type=2 ORDER BY A1.id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $tempArray);

    foreach ($records as &$t_record) {
        if ($t_record['ask_type'] == 'reward' && $t_record['is_answer'] == 1) {
            if (empty($t_record['real_name']) && empty($t_record['nickname'])) {
                $t_record['real_name'] = "(未选标)";
            } else {
                $t_record['real_name'] = $t_record['nickname'];
            }
        }
    }


    $pager = pagination($total, $pindex, $psize);
    $now   = time();
    include $this->template('reward_manage');


