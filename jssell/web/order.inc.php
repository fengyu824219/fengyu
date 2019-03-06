<?php
	global $_W,$_GPC;
	$operation = empty($_GPC['op']) ? "productOrderList" :$_GPC['op'];
    $start = $_GPC['time']['start'] ? $_GPC['time']['start'] : $_GPC['start'];
    $end = $_GPC['time']['end'] ? $_GPC['time']['end'] : $_GPC['end'];

    function operationOrderCount($uniacid){
        $conutData = array();
        $conutData['productOrderCount'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_order').' where uniacid = :uniacid and status in (2,6) and (type_explain = "proct" or type_explain = "proct_server") and (zid = 0 or partner_id = 0 or invitation_code = 0) ',array(':uniacid'=>$uniacid));//产品订单统计
        $conutData['serviceOrderCount'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_order').' where uniacid = :uniacid and status = 6 and type_explain != "proct" and type_explain != "proct_server" and is_platform != 1 ',array(':uniacid'=>$uniacid));//服务订单统计
        $conutData['bankOrderCount'] = pdo_fetchcolumn(' select count(*) from '.tablename('chat_order').' o LEFT JOIN '.tablename('chat_order_by_stages').' s ON o.id = s.oid  '.' where o.uniacid = :uniacid and (o.status = 1 and o.pay_type = "yh") or (o.status = 9 and s.status = 1 and s.pay_type = "yh") ',array(':uniacid'=>$uniacid));
        return $conutData;
    }

	if($operation == 'display'){
        $countData = operationOrderCount($_W['uniacid']);
        //前台加载模型数据
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        if($_GPC['status'] != 8){
            $condition = " where o.uniacid=:uniacid and o.status > 1 and o.status != 8 and type_explain != 'proct' and type_explain != 'proct_server' ";
        }else{
            $condition = " where o.uniacid=:uniacid and o.status = 8 and type_explain != 'proct' and type_explain != 'proct_server' ";
        }
        $where = array(":uniacid"=>$_W['uniacid']);

        //查询时间
        if($_GPC['status_select'] != 1 && $_GPC['status_select'] != ''){
            if($_GPC['status_select'] == 'create_time'){
                $condition .=  ' AND o.'.$_GPC['status_select'].' BETWEEN :start and :end';
                $where[':start'] = strtotime($start.' 00:00:00');
                $where[':end'] = strtotime($end.' 23:59:59');
            }
            if($_GPC['status_select'] == 'pay_time'){
                $condition .=  ' AND o.'.$_GPC['status_select'].' BETWEEN :start and :end';
                $where[':start'] = strtotime($start.' 00:00:00');
                $where[':end'] = strtotime($end.' 23:59:59');
            }
        }
        //查询状态
        if($_GPC['status'] != 99 && $_GPC['status'] != ''){
            if($_GPC['status'] != 8){
                $condition .= " AND o.status =:status";
                $where[':status'] = $_GPC['status'];
            }
        }
        //查询支付类型
        if($_GPC['pay_type'] != 1 && $_GPC['pay_type'] != ''){
            $condition .= ' and o.pay_type = :pay_type ';
            $where[':pay_type'] = $_GPC['pay_type'];
        }
        //订单号或手机号
        if($_GPC['order_sn']){
         $condition .= " AND ( o.order_sn LIKE '%{$_GPC['order_sn']}%' OR u.mobile LIKE '%{$_GPC['order_sn']}%')";
        }
        //中天
        if($_GPC['thp_order'] != 10 && $_GPC['thp_order'] != ''){
            if($_GPC['thp_order'] == 'midheaven'){
                $condition .= " AND o.thp_order = 'midheaven' ";
            }else if($_GPC['thp_order'] == 2){
                $condition .= " AND thp_order = '' ";
            }
        }

        if($_GPC['export'] == 'yes'){
            $adv = pdo_fetchall('select o.*,u.mobile from'.tablename('chat_order').'o LEFT join'.tablename('chat_users').'u ON u.id = o.uid '.$condition.' order by o.id desc ',$where);
        }else{
            $total=pdo_fetch('select count(*) as num from'.tablename('chat_order').'o LEFT join'.tablename('chat_users').'u ON u.id = o.uid '.$condition,$where);
            $adv = pdo_fetchall('select o.*,u.mobile from'.tablename('chat_order').'o LEFT join'.tablename('chat_users').'u ON u.id = o.uid '.$condition.' order by o.id desc limit '.($pindex - 1) * $psize .',' .$psize.'',$where);
            foreach ($adv as $key => $val) {
                if($val['is_by_stages'] == 1){
                    $orderByStages = pdo_fetch(' select number,total_number from '.tablename('chat_order_by_stages').' where uniacid = :uniacid and oid = :oid and status = 2 order by pay_time desc ',array(':uniacid'=>$_W['uniacid'],':oid'=>$val['id']));
                    $adv[$key]['payNum'] = $orderByStages['number'].'/'.$orderByStages['total_number'];//（当前期数/总期数）
                    $adv[$key]['currentNum'] = $orderByStages['number'];//当前期数
                }
            }
            $pager = pagination($total['num'], $pindex, $psize);
        }
        
        if($_GPC['export'] == 'yes'){
            //导出
            ob_end_clean();//清除缓冲区,避免乱码
            header("Content-type:application/octet-stream");
            header("Accept-Ranges:bytes");
            header("Content-type:application/vnd.ms-execl;charset=utf-8");//设置导出格式
            header("Content-Disposition:filename=订单记录表--".date('Y-m-d H:i:s',time()).".xls");
            //制作表头
            echo "<table border='1'";
            echo "<tr>";
            echo "<th>".'服务名称'."</th>";
            echo "<th>".'订单编号'."</th>";
            echo "<th>".'手机号码'."</th>";
            echo "<th>".'状态'."</th>";
            echo "<th>".'支付金额'."</th>";
            echo "<th>".'支付'."</th>";
            echo "<th>".'代下单'."</th>";
            echo "<th>".'中天'."</th>";
            echo "<th>".'支付时间'."</th>";
            echo "<th>".'下单时间'."</th>";
            echo "</tr>";

            foreach($adv as $k => $v){
                echo "<tr>";
                echo "<td>".$v['name']."</td>";
                echo "<td>".$v['order_sn']."</td>";
                echo "<td>".$v['mobile']."</td>";
                if($v['status'] == 1){
                    echo "<td>".'未支付'."</td>";
                }elseif($v['status'] == 2){
                    echo "<td>".'已支付'."</td>";
                }elseif($v['status'] == 3){
                    echo "<td>".'申请退款中'."</td>";
                }elseif($v['status'] == 4){
                    echo "<td>".'已退款'."</td>";
                }elseif($v['status'] == 5){
                    echo "<td>".'已完成'."</td>";
                }elseif($v['status'] == 6){
                    echo "<td>".'议价中'."</td>";
                }elseif($v['status'] == 7){
                    echo "<td>".'待确认'."</td>";
                }elseif($v['status'] == 8){
                    echo "<td>".'已取消'."</td>";
                }elseif($v['status'] == 9){
                    echo "<td>".'已分期'."</td>";
                }elseif($v['status'] == 10){
                    echo "<td>".'待完成'."</td>";
                }elseif($v['status'] == 11){
                    echo "<td>".'议价成功'."</td>";
                }
                echo "<td>".$v['price']."</td>";
                if ($v['pay_type'] == 'wx'){
                    echo "<td>微信支付</td>";
                }elseif($v['pay_type'] == 'ye'){
                    echo "<td>余额支付</td>";
                }elseif($v['pay_type'] == 'yh'){
                    echo "<td>银行支付</td>";
                }else{
                    echo "<td>".''."</td>";
                }
                if($v['to_place_an_order'] == '1'){
                    echo "<td>代下单</td>";
                }else{
                    echo "<td>".''."</td>";
                }
                if($v['thp_order'] == 'midheaven'){
                    echo "<td>中天订单</td>";
                }else{
                    echo "<td>".''."</td>";
                }
                if(!empty($v['pay_time'])){
                    echo "<td>".date('Y/m/d H:i:s',$v['pay_time'])."</td>";
                }else{
                    echo "<td>".''."</td>";
                }
                echo "<td>".date('Y/m/d H:i:s',$v['create_time'])."</td>";
                echo "</tr>";
            }
            echo "</table>";exit;
        }
	}

    //产品订单列表
    if($operation == 'productOrderList'){
        $countData = operationOrderCount($_W['uniacid']);
        if($_GPC['requestCount'] == 1){
            echo json_encode(array('code'=>200,'data'=>$countData));
            return false;
        }
        //前台加载模型数据
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        if($_GPC['status'] != 8){
            $condition = " where o.uniacid=:uniacid and o.status > 1 and o.status != 8 and (type_explain = 'proct' or type_explain = 'proct_server') ";
        }else{
            $condition = " where o.uniacid=:uniacid and o.status = 8 and (type_explain = 'proct' and type_explain = 'proct_server') ";
        }
        $where = array(":uniacid"=>$_W['uniacid']);

        //查询时间
        if($_GPC['status_select'] != 1 && $_GPC['status_select'] != ''){
         if($_GPC['status_select'] == 'create_time'){
             $condition .=  ' AND o.'.$_GPC['status_select'].' BETWEEN :start and :end';
                $where[':start'] = strtotime($start.' 00:00:00');
                $where[':end'] = strtotime($end.' 23:59:59');
         }
         if($_GPC['status_select'] == 'pay_time'){
             $condition .=  ' AND o.'.$_GPC['status_select'].' BETWEEN :start and :end';
                $where[':start'] = strtotime($start.' 00:00:00');
                $where[':end'] = strtotime($end.' 23:59:59');
         }
        }
        //查询状态
        if($_GPC['status'] != 99 && $_GPC['status'] != ''){
            if($_GPC['status'] != 8){
                $condition .= " AND o.status =:status";
                $where[':status'] = $_GPC['status'];
            }
        }
        //查询支付类型
        if($_GPC['pay_type'] != 1 && $_GPC['pay_type'] != ''){
            $condition .= ' and o.pay_type = :pay_type ';
            $where[':pay_type'] = $_GPC['pay_type'];
        }
        //订单号或手机号
        if($_GPC['order_sn']){
         $condition .= " AND ( o.order_sn LIKE '%{$_GPC['order_sn']}%' OR u.mobile LIKE '%{$_GPC['order_sn']}%')";
        }
        //代下单
        if($_GPC['to_place_an_order'] != '' && $_GPC['to_place_an_order'] != 10){
            $condition .= " AND o.to_place_an_order = ".$_GPC['to_place_an_order'];
        }
        //中天
        if($_GPC['thp_order'] != 10 && $_GPC['thp_order'] != ''){
            if($_GPC['thp_order'] == 'midheaven'){
                $condition .= " AND o.thp_order = 'midheaven' ";
            }else if($_GPC['thp_order'] == 2){
                $condition .= " AND thp_order = '' ";
            }
        }
        //指定
        if($_GPC['appointStatus'] != '' && $_GPC['appointStatus'] != 10){
            if($_GPC['appointStatus'] == 1){//是
                $condition .= ' AND o.is_platform = 1 AND (o.status= 2 or o.status = 9 or o.status= 10) AND o.zid = 0 AND o.invitation_code = 0 AND o.partner_id = 0 ';
            }
        }
        if($_GPC['export'] == 'yes'){
            $adv = pdo_fetchall('select o.*,u.mobile from'.tablename('chat_order').'o LEFT join'.tablename('chat_users').'u ON u.id = o.uid '.$condition.' order by o.id desc ',$where);
        }else{
            $total=pdo_fetch('select count(*) as num from'.tablename('chat_order').'o LEFT join'.tablename('chat_users').'u ON u.id = o.uid '.$condition,$where);
            $adv = pdo_fetchall('select o.*,u.mobile from'.tablename('chat_order').'o LEFT join'.tablename('chat_users').'u ON u.id = o.uid '.$condition.' order by o.id desc limit '.($pindex - 1) * $psize .',' .$psize.'',$where);
            foreach ($adv as $key => $val) {
                if($val['is_by_stages'] == 1){
                    $orderByStages = pdo_fetch(' select number,total_number from '.tablename('chat_order_by_stages').' where uniacid = :uniacid and oid = :oid and status = 2 order by pay_time desc ',array(':uniacid'=>$_W['uniacid'],':oid'=>$val['id']));
                    $adv[$key]['payNum'] = $orderByStages['number'].'/'.$orderByStages['total_number'];//（当前期数/总期数）
                    $adv[$key]['currentNum'] = $orderByStages['number'];//当前期数
                }
            }
            $pager = pagination($total['num'], $pindex, $psize);
        }

        if($_GPC['export'] == 'yes'){
            //导出
            ob_end_clean();//清除缓冲区,避免乱码
            header("Content-type:application/octet-stream");
            header("Accept-Ranges:bytes");
            header("Content-type:application/vnd.ms-execl;charset=utf-8");//设置导出格式
            header("Content-Disposition:filename=订单记录表--".date('Y-m-d H:i:s',time()).".xls");
            //制作表头
            echo "<table border='1'";
            echo "<tr>";
            echo "<th>".'服务名称'."</th>";
            echo "<th>".'订单编号'."</th>";
            echo "<th>".'手机号码'."</th>";
            echo "<th>".'状态'."</th>";
            echo "<th>".'支付金额'."</th>";
            echo "<th>".'支付'."</th>";
            echo "<th>".'代下单'."</th>";
            echo "<th>".'中天'."</th>";
            echo "<th>".'支付时间'."</th>";
            echo "<th>".'下单时间'."</th>";
            echo "</tr>";

            foreach($adv as $k => $v){
                echo "<tr>";
                echo "<td>".$v['name']."</td>";
                echo "<td>".$v['order_sn']."</td>";
                echo "<td>".$v['mobile']."</td>";
                if($v['status'] == 1){
                    echo "<td>".'未支付'."</td>";
                }elseif($v['status'] == 2){
                    echo "<td>".'已支付'."</td>";
                }elseif($v['status'] == 3){
                    echo "<td>".'申请退款中'."</td>";
                }elseif($v['status'] == 4){
                    echo "<td>".'已退款'."</td>";
                }elseif($v['status'] == 5){
                    echo "<td>".'已完成'."</td>";
                }elseif($v['status'] == 6){
                    echo "<td>".'议价中'."</td>";
                }elseif($v['status'] == 7){
                    echo "<td>".'待确认'."</td>";
                }elseif($v['status'] == 8){
                    echo "<td>".'已取消'."</td>";
                }elseif($v['status'] == 9){
                    echo "<td>".'已分期'."</td>";
                }elseif($v['status'] == 10){
                    echo "<td>".'待完成'."</td>";
                }elseif($v['status'] == 11){
                    echo "<td>".'议价成功'."</td>";
                }
                echo "<td>".$v['price']."</td>";
                if ($v['pay_type'] == 'wx'){
                    echo "<td>微信支付</td>";
                }elseif($v['pay_type'] == 'ye'){
                    echo "<td>余额支付</td>";
                }elseif($v['pay_type'] == 'yh'){
                    echo "<td>银行支付</td>";
                }else{
                    echo "<td>".''."</td>";
                }
                if($v['to_place_an_order'] == '1'){
                    echo "<td>代下单</td>";
                }else{
                    echo "<td>".''."</td>";
                }
                if($v['thp_order'] == 'midheaven'){
                    echo "<td>中天订单</td>";
                }else{
                    echo "<td>".''."</td>";
                }
                if(!empty($v['pay_time'])){
                    echo "<td>".date('Y/m/d H:i:s',$v['pay_time'])."</td>";
                }else{
                    echo "<td>".''."</td>";
                }
                echo "<td>".date('Y/m/d H:i:s',$v['create_time'])."</td>";
                echo "</tr>";
            }
            echo "</table>";exit;
        }
    }

    //转账列表
    if($operation == 'transferAccountsList'){
        $countData = operationOrderCount($_W['uniacid']);
        // 前台加载模型数据
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $condition = " where o.uniacid = :uniacid ";
        $where = array(':uniacid'=>$_W['uniacid']);

        //查询时间
        if($_GPC['status_select'] != 1 && $_GPC['status_select'] != ''){
            if($_GPC['status_select'] == 'create_time'){
                $condition .=  ' AND o.'.$_GPC['status_select'].' BETWEEN :start and :end';
                $where[':start'] = strtotime($start.' 00:00:00 ');
                $where[':end'] = strtotime($end.' 23:59:59 ');
            }
            if($_GPC['status_select'] == 'pay_time'){
                $condition .=  ' AND o.'.$_GPC['status_select'].' BETWEEN :start and :end';
                $where[':start'] = strtotime($start.' 00:00:00 ');
                $where[':end'] = strtotime($end.' 23:59:59 ');
            }
        }

        //代下单
        if($_GPC['to_place_an_order'] != '' && $_GPC['to_place_an_order'] != 10){
            $condition .= " AND o.to_place_an_order = ".$_GPC['to_place_an_order'];
        }
        //中天
        if($_GPC['thp_order'] != 10 && $_GPC['thp_order'] != ''){
            if($_GPC['thp_order'] == 'midheaven'){
                $condition .= " AND o.thp_order = 'midheaven' ";
            }else if($_GPC['thp_order'] == 2){
                $condition .= " AND thp_order = '' ";
            }
        }
        //订单号或手机号
        if($_GPC['order_sn']){
            $condition .= " AND ( o.order_sn LIKE '%{$_GPC['order_sn']}%' OR u.mobile LIKE '%{$_GPC['order_sn']}%')";
        }

        $total = pdo_fetchcolumn('select count(*) from'.tablename('chat_order').'o LEFT join'.tablename('chat_users').'u ON u.id = o.uid '.$condition.' and ((o.status = 1 and o.pay_type = "yh") or o.status = 9) ',$where);
        $order = pdo_fetchall('select o.*,u.mobile from'.tablename('chat_order').'o LEFT join'.tablename('chat_users').'u ON u.id = o.uid '.$condition.' and ((o.status = 1 and o.pay_type = "yh") or o.status = 9) order by o.id desc limit '.($pindex - 1) * $psize .',' .$psize.'',$where);
        foreach ($order as $key => $val) {//分期支付
            if($val['is_by_stages'] == 1 && $val['status'] == 9){
                $orderByStages = pdo_fetch(' select bsid,number,total_number,price from '.tablename('chat_order_by_stages').' where uniacid = :uniacid and oid = :oid and pay_type = "yh" and status = 1 order by create_time asc ',array(':uniacid'=>$_W['uniacid'],':oid'=>$val['id']));
                if(!empty($orderByStages)){
                    $order[$key]['currentPrice'] = $orderByStages['price'];//当前期数的金额
                    $order[$key]['payNum'] = ($orderByStages['number'] - 1).'/'.$orderByStages['total_number'];//（已支付的期数/总期数）
                    $order[$key]['bsid'] = $orderByStages['bsid'];//分期Id
                    $order[$key]['is_bankPay'] = 'yes';
                }else{
                    $is_byStagesData = 'no';
                }
            }
        }
        if($is_byStagesData == 'no'){
            $total = pdo_fetchcolumn('select count(*) from'.tablename('chat_order').'o LEFT join'.tablename('chat_users').'u ON u.id = o.uid '.$condition.' and (o.status = 1 and o.pay_type = "yh") ',$where);
        }
        $pager = pagination($total, $pindex, $psize);
    }

    //线下转账
    if($operation == 'transferAccounts'){
        if($_POST){
            $orderData = array(
                'status' => 2,
                'pay_time' => time(),
                'voucher' => $_GPC['voucher'],
                'serial_number' => $_GPC['serial_number']
            );
            $orderUpdate = pdo_update('chat_order',$orderData,array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
            if(!empty($orderUpdate)){
                $order = pdo_get('chat_order',array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
                pdo_update('chat_users_pay_log',array('pay_status'=>2,'pay_time'=>time()),array('uniacid'=>$_W['uniacid'],'out_trade_no'=>$order['order_sn']));

                $weObj = WeAccount::create($_W['uniacid']);
                $send_data = array(
                    'first'=>array('value'=>'已确认您的转账。'),
                    'keyword1'=>array('value'=>$order['price']),
                    'keyword2'=>array('value'=>'银行支付'),
                    'keyword3'=>array('value'=>date('Y-m-d H:i',time())),
                    'keyword4'=>array('value'=>$order['order_sn']),
                    'remark'=>array('value'=>'感谢您的使用，如有疑问，请咨询客服：0769-22859180。'),
                );
                $url = $_W['siteroot'] . $this->createMobileUrl('my_chat',array('op'=>'my_consumption','from'=>'template'));

                if($_W['siteroot'] == 'https://wx.jssell.com/'){
                    $templateId = 'HptfWHhozb0Us7KMYsr8KkiBM6hPY7UW14Ikg0xPiQo';
                }else{
                    $templateId = 'VxVpYQT-JPe-n8QTiGBv1VRy9Sqfp__BtnBSQsF4Fhk';
                }

                $weObj->sendTplNotice($_GPC['openid'], $templateId, $send_data, $url, '#173177');

                echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
            }else{
                echo json_encode(array('code'=>100,'msg'=>'操作失败'));exit;
            }
        }

        $page = $_GPC['page'];
        $eid = $_GPC['eid'];
        $order = pdo_get('chat_order',array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
        $user = pdo_get('chat_users',array('id'=>$order['uid'],'uniacid'=>$_W['uniacid']),array('nickname','mobile','openid'));
    }

    //订单指定
    if($operation == 'orderAppoint'){
        if($_POST){
            $order = pdo_get('chat_order',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']),array('is_by_stages'));
            if(!empty($_GPC['id'])){
                $orderUpdateData = array(
                    'expert_scale' => $_GPC['expert_scale'],//专家结算比例
                    'salesman_scale' => $_GPC['salesman_scale'],//业务员结算比例
                    'partner_scale' => $_GPC['partner_scale']//合伙人结算比例
                    // 'boss_scale' => $_GPC['boss_scale']//总监结算比例
                );
                if(!empty($_GPC['zid'])){
                    $orderUpdateData['zid'] = $_GPC['zid'];
                }
                if(!empty($_GPC['group_egid'])){
                    $expertGroupIsOpen = pdo_get('chat_expert_group',array('uniacid'=>$_W['uniacid'],'egid'=>$_GPC['group_egid']),array('is_open'));
                    if($expertGroupIsOpen['is_open'] == 1 && $order['is_by_stages'] == 1){//分期订单不能指定自己开票的专家团
                        echo json_encode(array('code'=>400,'msg'=>'不能指定自开票的专家团'));exit;
                    }
                    $orderUpdateData['group_egid'] = $_GPC['group_egid'];
                    $orderUpdateData['is_group'] = 1;
                    $orderUpdateData['is_ticket_opening'] = $expertGroupIsOpen['is_open'];
                }
                if(!empty($_GPC['invitation_code'])){
                    $orderUpdateData['invitation_code'] = $_GPC['invitation_code'];
                }
                // if(!empty($_GPC['boss_id'])){
                //     if($_GPC['boss_id'] == 'jssell'){
                //         $_GPC['boss_id'] = '999999';
                //     }
                //     $orderUpdateData['boss_id'] = $_GPC['boss_id'];
                // }
                if(!empty($_GPC['partner_id'])){
                    if($_GPC['partner_id'] == 'jssell'){
                        $_GPC['partner_id'] = '999999';
                    }
                    $orderUpdateData['partner_id'] = $_GPC['partner_id'];
                }

                $orderUpdate = pdo_update('chat_order',$orderUpdateData,array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
                if($orderUpdate){
                    $orderData = pdo_get('chat_order',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
                    $userOpenid = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$orderData['zid']),array('openid','id'));//专家openid
                    $userName = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$orderData['uid']),array('nickname'));//用户名称
                    $weObj = WeAccount::create($_W['uniacid']);
                    $send_data = array(
                        'first'=>array('value'=>'您有一笔新的待定待确认'),
                        'tradeDateTime'=>array('value'=>date('Y年m月d日 H:i:s',time())),
                        'orderType'=>array('value'=>$orderData['name']),
                        'customerInfo'=>array('value'=>$userName['nickname']),
                        'remark'=>array('value'=>''),
                    );
                    //App 消息
                    $push_data = array(
                        'type'=>"link",
                        'title'=>'订单通知',
                        'content'=> "您好，平台为你指派了一笔新的订单",
                        'url'=>$this->createMobileUrl('ask_chat_reward',array('op'=>'customer','from'=>'template'))
                    );
                    $this->pushMessageToSingle($userOpenid['id'],$push_data);

                    $url = $_W['siteroot'] . $this->createMobileUrl('ask_chat_reward',array('op'=>'agency','ask_status'=>2,'from'=>'template'));

                    $weObj->sendTplNotice($userOpenid['openid'], '6pzrEFSq3UzqnckCFg4wCPuSYsKTQ4Nhzju-2Xg9jQ8', $send_data, $url, '#173177');
                    // $weObj->sendTplNotice($userOpenid['openid'], 'aXNR6sQ1jefEmvm2Wre-4w7dyMsikGhCFyQvQjXLhbg', $send_data, $url, '#173177');//测试环境

                    echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
                }else{
                    echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
                }
            }
        }
        $countData = operationOrderCount($_W['uniacid']);
        $page = $_GPC['page'];
        if(!empty($_GPC['id'])){
            $order = pdo_get('chat_order',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
            if($order['pay_type'] == 'wx'){
                $order['pay_type'] = '微信支付';
            }
            if($order['pay_type'] == 'ye'){
                $order['pay_type'] = '余额支付';
            }
            if($order['pay_type'] == 'yh'){
                $order['pay_type'] = '银行支付';
            }
            if($order['pay_time'] != 0){
                $order['pay_time'] = date('Y-m-d H:i',$order['pay_time']);
            }
            if($order['end_time'] != 0){
                $order['end_time'] = date('Y-m-d H:i',$order['end_time']);
            }
            $user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$order['uid']),array('real_name','mobile','nickname'));//用户信息
            $condition = ' where uniacid = :uniacid and vstatus = 1 and is_platform = 1 and is_salesman = 1 ';
            $where[':uniacid'] = $_W['uniacid'];

            if(!empty($order['invitation_code'])){
                $salesman = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'invitation_code'=>$order['invitation_code']),array('real_name','nickname'));//业务员信息
                if(!empty($salesman)){
                    if(empty($salesman['real_name'])){
                        $salesman['real_name'] = $salesman['nickname'];
                    }
                }
                if($order['invitation_code'] == 999999){
                    $salesman['real_name'] = '税媒平台';
                }
            }

            if($order['group_egid'] == 0){
                $expert = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$order['zid']),array('real_name'));//专家信息
                $expert['name'] = $expert['real_name'];
            }else{
                $expert = pdo_get('chat_expert_group',array('uniacid'=>$_W['uniacid'],'uid'=>$order['zid']),array('name'));//专家团信息
            }

            // $chief = pdo_fetch(' select id,real_name,nickname from '.tablename('chat_users').' where uniacid = :uniacid and is_boss = 1 and id = :id ',array('uniacid'=>$_W['uniacid'],':id'=>$order['boss_id']));//业务总监信息
            // if(empty($chief['real_name'])){
            //     $chief['real_name'] = $chief['nickname'];
            // }

            $partner = pdo_fetch(' select id,real_name,nickname from '.tablename('chat_users').' where uniacid = :uniacid and is_partner = 1 and id = :id ',array('uniacid'=>$_W['uniacid'],':id'=>$order['partner_id']));//合伙人信息
            if(!empty($partner)){
                if(empty($partner['real_name'])){
                    $partner['real_name'] = $partner['nickname'];
                }
            }
            if($order['partner_id'] == 999999){
                $partner['real_name'] = '税媒平台';
            }
        }
    }

    //搜索指定的专家或专家团或业务员
    if($operation == 'searchExpert'){
        if($_POST){
            $condition = ' where uniacid = :uniacid ';
            $where[':uniacid'] = $_W['uniacid'];
            if($_GPC['appointType'] == 'appointExpert'){//搜索指定的专家或专家团

                if($_GPC['type'] == 1){//搜索专家
                    $condition .= ' and vstatus = 1 ';
                    if(!empty($_GPC['level_id'])){//专家等级搜索
                        $condition .= ' and level_id = :level_id ';
                        $where[':level_id'] = $_GPC['level_id'];
                    }
                    if($_GPC['keyword'] != ''){//搜索关键字
                        $condition .= " and (real_name LIKE '%{$_GPC['keyword']}%' OR mobile LIKE '%{$_GPC['keyword']}%')";
                    }

                    $expert = pdo_fetchall(' select id,level,real_name,avatar from '.tablename('chat_users').$condition,$where);
                    foreach ($expert as $key => $val) {
                        $expert[$key]['avatar'] = tomedia($val['avatar']);
                        $expert[$key]['nickname'] = $val['level'];
                        $expert[$key]['typeData'] = 1;
                    }
                    if($expert){
                        echo json_encode(array('code'=>200,'content'=>$expert));exit;
                    }
                }
                if($_GPC['type'] == 2){//搜索专家团
                    $condition .= ' and status = 1 ';
                    if($_GPC['keyword'] != ''){//搜索关键字
                        $condition .= " and (name LIKE '%{$_GPC['keyword']}%' OR real_name LIKE '%{$_GPC['keyword']}%')";
                    }
                    $expertGroup = pdo_fetchall(' select egid,name,headimg,uid,real_name from '.tablename('chat_expert_group').$condition,$where);
                    foreach ($expertGroup as $key => $val) {
                        $expertGroup[$key]['id'] = $val['egid'];
                        $expertGroup[$key]['avatar'] = tomedia($val['headimg']);
                        $expertGroup[$key]['nickname'] = $val['name'];
                        $expertGroup[$key]['typeData'] = 2;
                    }
                    if($expertGroup){
                        echo json_encode(array('code'=>200,'content'=>$expertGroup));exit;
                    }
                }

            }else if($_GPC['appointType'] == 'appointSalesman'){//搜索业务员
                $condition .= ' and is_salesman = 1 and invitation_code != 0 ';
                if($_GPC['keyword'] != ''){//搜索关键字
                    $condition .= " and (real_name LIKE '%{$_GPC['keyword']}%' OR mobile LIKE '%{$_GPC['keyword']}%' OR nickname LIKE '%{$_GPC['keyword']}%')";
                }
                $salesman = pdo_fetchall('select id,invitation_code,real_name,nickname from'.tablename('chat_users').$condition,$where);
                foreach ($salesman as $key => $val) {
                    $salesman[$key]['avatar'] = tomedia($val['avatar']);
                }
                if($salesman){
                    echo json_encode(array('code'=>200,'content'=>$salesman));exit;
                }
            }
        }
    }

    if($operation == 'searchAppoint'){//搜索指定总监或合伙人
        if($_GPC['appointType'] == 'appointBoss'){
            $type = 2;//总监
            $msg = '没有该区域的总监';
        }else{
            $type = 1;//合伙人
            $msg = '没有该区域的城市合伙人';
        }

        $userArea = pdo_get('chat_user_partition',array('uniacid'=>$_W['uniacid'],'province'=>$_GPC['province'],'city'=>$_GPC['city'],'area'=>$_GPC['area'],'type'=>$type));
        if(!empty($userArea['uid'])){
            $userData = array('id'=>$userArea['uid'],'uniacid'=>$_W['uniacid']);
        }else{
            $userCity = pdo_get('chat_user_partition',array('uniacid'=>$_W['uniacid'],'province'=>$_GPC['province'],'city'=>$_GPC['city'],'type'=>$type));
            if(!empty($userCity)){
                $userData = array('id'=>$userCity['uid'],'uniacid'=>$_W['uniacid']);
            }else{
                $userProvince = pdo_get('chat_user_partition',array('uniacid'=>$_W['uniacid'],'province'=>$_GPC['province'],'type'=>$type));
                if(!empty($userProvince)){
                    $userData = array('id'=>$userProvince['uid'],'uniacid'=>$_W['uniacid']);
                }else{
                    $default = array('id'=>'jssell','real_name'=>'税媒平台');
                    echo json_encode(array('code'=>200,'content'=>$default));exit;
                    // echo json_encode(array('code'=>400,'msg'=>$msg));exit;
                }
            }
        }
        $user = pdo_get('chat_users',$userData,array('id','real_name','nickname'));
        if(!empty($user)){
            if(empty($user['real_name'])){
                $user['real_name'] = $user['nickname'];
            }
            echo json_encode(array('code'=>200,'content'=>$user));exit;
        }else{
            $default = array('id'=>'jssell','real_name'=>'税媒平台');
            echo json_encode(array('code'=>200,'content'=>$default));exit;
            // echo json_encode(array('code'=>400,'msg'=>$msg));exit;
        }
    }

    //订单详情
    if($operation == 'orderDetails'){
        if($_POST){
            if(!empty($_GPC['bsid'])){
                $orderByStages = pdo_get('chat_order_by_stages',array('uniacid'=>$_W['uniacid'],'bsid'=>$_GPC['bsid']));
                if($orderByStages['status'] == 2){
                    echo json_encode(array('code'=>200,'msg'=>'该订单已改变支付方式'));exit;
                }
                $order = pdo_get('chat_order',array('uniacid'=>$_W['uniacid'],'id'=>$orderByStages['oid']));
                //查询支付日志记录
                $usersPayLog = pdo_get('chat_users_pay_log',array('uniacid'=>$_W['uniacid'],'out_trade_no'=>$orderByStages['order_sn']));

                // $orderByStagesData = array();
                $orderByStagesData = array(
                    'status' => 2,
                    'pay_time' => time(),
                    'serial_number' => $_GPC['serial_number'],
                    'voucher' => $_GPC['voucher'],
                    'content' => $_GPC['content'],
                    'admin_uid' => $_W['user']['uid']
                );

                if($orderByStages['is_full_section'] == 1){//全款
                    $orderByStagesData['is_full_section'] = 1;
                    $orderByStagesData['status'] = 3;
                    $orderByStagesUpdate = pdo_update('chat_order_by_stages',$orderByStagesData,array('uniacid'=>$_W['uniacid'],'bsid'=>$_GPC['bsid']));
                    if($orderByStagesUpdate){
                        $orderByStagesAll = pdo_fetchall(' select bsid from '.tablename('chat_order_by_stages').' where uniacid = :uniacid and oid = :oid and number > :number and status = :status ',array(':uniacid'=>$_W['uniacid'],'oid'=>$orderByStages['oid'],':number'=>$orderByStages['number'],':status'=>1));//查询该订单剩下的期数
                        unset($orderByStagesData['serial_number']);
                        unset($orderByStagesData['voucher']);
                        unset($orderByStagesData['content']);
                        $orderByStagesData['status'] = 2;
                        $orderByStagesData['pay_time'] = $orderByStages['pay_time'];
                        $orderByStagesData['pay_type'] = 'yh';
                        foreach ($orderByStagesAll as $key => $val) {
                            pdo_update('chat_order_by_stages',$orderByStagesData,array('uniacid'=>$_W['uniacid'],'bsid'=>$val['bsid']));
                        }
                        //修改订单状态
                        $orderUpdate = pdo_update('chat_order',array('status'=>10),array('uniacid'=>$_W['uniacid'],'id'=>$orderByStages['oid']));
                        //修改支付日志记录该订单状态
                        $usersPayLogUpdate = pdo_update('chat_users_pay_log',array('pay_status'=>2),array('uniacid'=>$_W['uniacid'],'out_trade_no'=>$orderByStages['order_sn']));
                        if($order['is_group'] == 1){//专家团订单
                            $this->add_profit(9,$order['id'],$usersPayLog['price'],0,$order['uid']);//专家团收益
                        }else{//个人专家订单
                            $this->add_profit(5,$order['id'],$usersPayLog['price'],$order['zid'],$order['uid'],1);//专家收益
                        }
                        $this->add_profit(6,$order['id'],$usersPayLog['price'],0,$order['uid']);//业务员收益
                        $this->add_profit(7,$order['id'],$usersPayLog['price'],0,$order['uid']);//合伙人收益
                        // $this->add_profit(8,$order['id'],$usersPayLog['price'],0,$order['uid']);//运营总监收益
                    }
                }else{//非全款
                    if($orderByStages['number'] != $orderByStages['total_number']){//总期数不等于当前期数
                        $orderByStagesUpdate = pdo_update('chat_order_by_stages',$orderByStagesData,array('uniacid'=>$_W['uniacid'],'bsid'=>$_GPC['bsid']));
                        pdo_update('chat_users_pay_log',array('pay_status'=>2,'pay_time'=>time()),array('uniacid'=>$_W['uniacid'],'out_trade_no'=>$orderByStages['order_sn']));
                        if($orderByStages['number'] != 1){
                            if($order['is_group'] == 1){//专家团订单
                                $this->add_profit(9,$order['id'],$usersPayLog['price'],0,$order['uid']);//专家团收益
                            }else{//个人专家订单
                                $this->add_profit(5,$order['id'],$usersPayLog['price'],$order['zid'],$order['uid'],1);//专家收益
                            }
                            $this->add_profit(6,$order['id'],$usersPayLog['price'],0,$order['uid']);//业务员收益
                            $this->add_profit(7,$order['id'],$usersPayLog['price'],0,$order['uid']);//合伙人收益
                            // $this->add_profit(8,$order['id'],$usersPayLog['price'],0,$order['uid']);//运营总监收益
                        }else{
                            pdo_update('chat_order',array('status'=>2,'pay_time'=>time()),array('uniacid'=>$_W['uniacid'],'id'=>$orderByStages['oid']));
                        }
                    }else{//总期数等于当前期数,最后一期
                        $orderByStagesUpdate = pdo_update('chat_order_by_stages',$orderByStagesData,array('uniacid'=>$_W['uniacid'],'bsid'=>$_GPC['bsid']));
                        //修改支付日志记录该订单状态
                        $usersPayLogUpdate = pdo_update('chat_users_pay_log',array('pay_status'=>2,'pay_time'=>time()),array('uniacid'=>$_W['uniacid'],'out_trade_no'=>$orderByStages['order_sn']));
                        if($order['is_group'] == 1){//专家团订单
                            $this->add_profit(9,$order['id'],$usersPayLog['price'],0,$order['uid']);//专家团收益
                        }else{//个人专家订单
                            $this->add_profit(5,$order['id'],$usersPayLog['price'],$order['zid'],$order['uid'],1);//专家收益
                        }
                        $this->add_profit(6,$order['id'],$usersPayLog['price'],0,$order['uid']);//业务员收益
                        $this->add_profit(7,$order['id'],$usersPayLog['price'],0,$order['uid']);//合伙人收益
                        // $this->add_profit(8,$order['id'],$usersPayLog['price'],0,$order['uid']);//运营总监收益
                    }
                }
                $orderByStagesNum = pdo_fetchcolumn('select count(*) from'.tablename('chat_order_by_stages').' where uniacid = :uniacid and oid = :oid and status in (2,3) ',array(':uniacid'=>$_W['uniacid'],':oid'=>$orderByStages['oid']));
                if($orderByStagesNum == $orderByStages['total_number']){//判断是否全部分期已支付
                    //修改订单状态
                    $orderUpdate = pdo_update('chat_order',array('status'=>10),array('uniacid'=>$_W['uniacid'],'id'=>$orderByStages['oid']));
                }
                if($orderByStagesUpdate){
                    echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
                }else{
                    echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
                }
            }
        }
        $countData = operationOrderCount($_W['uniacid']);
        if(!empty($_GPC['id'])){
            $order = pdo_get('chat_order',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));//订单表
            if(!empty($order['group_egid'])){
                $expertGroup = pdo_get('chat_expert_group',array('uniacid'=>$_W['uniacid'],'egid'=>$order['group_egid']),array('name'));
            }

            // if($order['boss_id'] == '999999'){
            //     $boss['real_name'] = '税媒平台';
            // }else{
            //     $boss = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$order['boss_id']),array('real_name','nickname'));//总监
            //     if(empty($boss['real_name'])){
            //         $boss['real_name'] = $boss['nickname'];
            //     }
            // }
            if($order['partner_id'] == '999999'){
                $partner['real_name'] = '税媒平台';
            }else{
                $partner = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$order['partner_id']),array('real_name','nickname'));//城市合伙人
                if(empty($partner['real_name'])){
                    $partner['real_name'] = $partner['nickname'];
                }
            }
            if($order['invitation_code'] == '999999'){
                $salesman['real_name'] = '税媒平台';
            }else{
                if(!empty($order['invitation_code'])){
                    $salesman = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'invitation_code'=>$order['invitation_code']),array('real_name','nickname'));//业务员
                }
                if(empty($salesman['real_name'])){
                    $salesman['real_name'] = $salesman['nickname'];
                }
            }

            // $boss = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$order['boss_id']),array('real_name','nickname'));//总监
            // if(empty($boss['real_name'])){
            //     $boss['real_name'] = $boss['nickname'];
            // }
            // $partner = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$order['partner_id']),array('real_name','nickname'));//城市合伙人
            // if(empty($partner['real_name'])){
            //     $partner['real_name'] = $partner['nickname'];
            // }
            //     $salesman = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'invitation_code'=>$order['invitation_code']),array('real_name','nickname'));//业务员
            // if(empty($salesman['real_name'])){
            //     $salesman['real_name'] = $salesman['nickname'];
            // }
            if($order['pay_type'] == 'wx'){
                $order['pay_type'] = '微信支付';
            }
            if($order['pay_type'] == 'ye'){
                $order['pay_type'] = '余额支付';
            }
            if($order['pay_type'] == 'yh'){
                $order['pay_type'] = '银行支付';
            }
            if($order['create_time'] != 0){
                $order['create_time'] = date('Y-m-d H:i',$order['create_time']);
            }
            if($order['pay_time'] != 0){
                $order['pay_time'] = date('Y-m-d H:i',$order['pay_time']);
            }
            if($order['is_maid'] == 1){
                $order['is_maid'] = '是';
            }else{
                $order['is_maid'] = '否';
            }
            if($order['type_explain'] == 'sm'){
                $order['type_explain'] = '上门服务';
            }
            if($order['type_explain'] == 'year'){
                $order['type_explain'] = '年度服务';
            }
            if($order['type_explain'] == 'text'){
                $order['type_explain'] = '图文服务';
            }
            if($order['type_explain'] == 'phone'){
                $order['type_explain'] = '电话服务';
            }
            if($order['type_explain'] == 'proct'){
                $order['type_explain'] = '平台年度服务';
            }
            if($order['type_explain'] == 'custom_serve'){
                $order['type_explain'] = '专家自定义服务';
            }
            if($order['type_explain'] == 'group_service'){
                $order['type_explain'] = '专家团服务';
            }
            if($order['type_explain'] == 'proct_server'){
                $order['type_explain'] = '平台自定义服务';
            }
            if($order['to_place_an_order'] == 1){
                $order['to_place_an_order'] = '是';
            }else{
                $order['to_place_an_order'] = '否';
            }
            $user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$order['uid']),array('real_name','mobile','nickname'));//用户信息

            $expert = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$order['zid']),array('real_name'));//专家信息

            $orderStep = pdo_fetchall('select * from'.tablename('chat_order_step').' where uniacid = :uniacid and oid = :oid ',array(':uniacid'=>$_W['uniacid'],':oid'=>$order['id']));//专家服务订单记录

            $orderByStages = pdo_fetchall('select * from'.tablename('chat_order_by_stages').' where uniacid = :uniacid and oid = :oid ',array(':uniacid'=>$_W['uniacid'],':oid'=>$order['id']));//订单分期付款记录
            foreach ($orderByStages as $key => $val) {
                if(mb_strlen($val['content'],'utf8') > 10){
                    $orderByStages[$key]['contentEllipsis'] = mb_substr($val['content'],0,10,'utf-8').'...';
                }else{
                    $orderByStages[$key]['contentEllipsis'] = $val['content'];
                }
            }
            if($order['is_by_stages'] == 1){
                $userPayLog = pdo_getall('chat_users_pay_log',array('uniacid'=>$_W['uniacid'],'type_id'=>$order['id'],'type'=>2));//支付日志记录
            }else{
                $userPayLog = pdo_get('chat_users_pay_log',array('uniacid'=>$_W['uniacid'],'out_trade_no'=>$order['order_sn']));//支付日志记录
            }

            $refundsLog = pdo_fetchall('select * from'.tablename('chat_refunds_log').' where uniacid = :uniacid and oid = :oid ',array(':uniacid'=>$_W['uniacid'],':oid'=>$order['id']));//结算日志
        }
    }

    //分期转账处理
    if($operation == 'byStagesTa'){
        $countData = operationOrderCount($_W['uniacid']);
        $orderByStages = pdo_get('chat_order_by_stages',array('bsid'=>$_GPC['bsid'],'uniacid'=>$_W['uniacid']));
        $order = pdo_get('chat_order',array('id'=>$orderByStages['oid'],'uniacid'=>$_W['uniacid']),array('name'));
    }

    //产品议价
    if($operation == 'bargaining'){
        if($_POST){
            $orderUpdate = pdo_update('chat_order',array('status'=>11,'price'=>$_GPC['price']),array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
            $order = pdo_get('chat_order',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
            if(!empty($orderUpdate)){
                $weObj = WeAccount::create($_W['uniacid']);
                $send_data = array(
                    'first'=>array('value'=>'您好，您的订单已确定价格'),
                    'keyword1'=>array('value'=>$order['order_sn']),
                    'keyword2'=>array('value'=>$order['price']),
                    'remark'=>array('value'=>'请及时付款'),
                );
                $url = $_W['siteroot'] . $this->createMobileUrl('ask_chat_reward',array('op'=>'my-ask-expert','from'=>'template'));
                $templateId = '1-FssQNpDPN9WufHQY7JF_I3OZZhR0c6lvreDuVs0ZE';
                $weObj->sendTplNotice($_GPC['openid'], $templateId, $send_data, $url, '#173177');

                //App 消息
                $push_data = array(
                    'type'=>"link",
                    'title'=>'订单通知',
                    'content'=> "您好，您已成功议价，请及时跟进订单",
                    'url'=>$this->createMobileUrl('ask_chat_reward',array('op'=>'my-ask-expert'))
                );
                $this->pushMessageToSingle($order['uid'],$push_data);

                echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
            }else{
                echo json_encode(array('code'=>100,'msg'=>'操作失败'));exit;
            }
        }
        $countData = operationOrderCount($_W['uniacid']);
        $eid = $_GPC['eid'];
        $pageNum = $_GPC['page'];
        $order = pdo_get('chat_order',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));
        $user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$order['uid']),array('mobile','real_name','nickname','openid'));
        if (empty($user['real_name'])) {
            $user['real_name'] = $user['nickname'];
        }
    }

    //取消订单
    if($operation == 'cancelOrder'){
        $countData = operationOrderCount($_W['uniacid']);
        $orderUpdate = pdo_update('chat_order',array('status'=>8),array('id'=>$_GPC['id'],'uniacid'=>$_W['uniacid']));
        $order = pdo_get('chat_order',array('uniacid'=>$_W['uniacid'],'id'=>$_GPC['id']));

        if(!empty($orderUpdate)){
            //App 消息
            $push_data = array(
                    'type'=>"link",
                    'title'=>'订单通知',
                    'content'=> "您好，您的订单已取消，如有疑问请咨询客服",
                    'url'=>$this->createMobileUrl('my_chat',array('op'=>'my_consumption'))
            );
            $this->pushMessageToSingle($order['uid'],$push_data);

            itoast('操作成功','','success');exit;
        }else{
            itoast('操作失败','','error');exit;
        }
    }

    //分期列表
    if($operation == 'byStagesList'){
        $countData = operationOrderCount($_W['uniacid']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $condition = ' where s.uniacid = :uniacid ';
        $where[':uniacid'] = $_W['uniacid'];

        //时间查询
        if($_GPC['status_select'] != '' && $_GPC['status_select'] != 1){
            $condition .= ' and s.'.$_GPC['status_select'].' BETWEEN :start and :end ';
            $where[':start'] = strtotime($start.' 00:00:00 ');
            $where[':end'] = strtotime($end.' 23:59:59 ');
        }

        //支付状态查询
        if($_GPC['status'] != '' && $_GPC['status'] != 99){
            $condition .= ' and s.status = :status ';
            $where[':status'] = $_GPC['status'];
        }

        //支付类型查询
        if($_GPC['pay_type'] != '' && $_GPC['pay_type'] != 1){
            $condition .= ' and s.pay_type = :pay_type ';
            $where[':pay_type'] = $_GPC['pay_type'];
        }

        //关键字查询
        if($_GPC['order_sn'] != ''){
            $condition .= " and o.order_sn LIKE '%{$_GPC['order_sn']}%' ";
        }

        $orderByStages = pdo_fetchall(' select s.*,o.type_explain,o.order_sn as o_sn from '.tablename('chat_order_by_stages').' s LEFT JOIN '.tablename('chat_order').' o ON o.id = s.oid '.$condition.' order by bsid desc limit '.($pindex - 1) * $psize .',' .$psize.'',$where);

        $total = pdo_fetchcolumn(' select count(*) from '.tablename('chat_order_by_stages').' s LEFT JOIN '.tablename('chat_order').' o ON o.id = s.oid '.$condition,$where);

        $pager = pagination($total, $pindex, $psize);
    }

    //分期详情
    if($operation == 'byStagesDetails'){
        $countData = operationOrderCount($_W['uniacid']);
        $pageNumber = $_GPC['page'];
        $orderByStages = pdo_get('chat_order_by_stages',array('uniacid'=>$_W['uniacid'],'bsid'=>$_GPC['bsid']));
        $user = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$orderByStages['uid']),array('real_name','nickname','mobile'));
        $adminUser = pdo_get('users',array('uid'=>$orderByStages['admin_uid']),array('username'));

        if($orderByStages['is_full_section'] == 1){
            $orderByStages['is_full_section'] = '是';
        }elseif($orderByStages['is_full_section'] == 0){
            $orderByStages['is_full_section'] = '否';
        }

        if($orderByStages['status'] == 1){
            $orderByStages['status'] = '未支付';
        }elseif($orderByStages['status'] == 2){
            $orderByStages['status'] = '已支付';
        }elseif($orderByStages['status'] == 3){
            $orderByStages['status'] = '已全款支付';
        }

        if($orderByStages['pay_type'] == 'wx'){
            $orderByStages['pay_type'] = '微信支付';
        }elseif($orderByStages['pay_type'] == 'ye'){
            $orderByStages['pay_type'] = '余额支付';
        }elseif($orderByStages['pay_type'] == 'yh'){
            $orderByStages['pay_type'] = '银行支付';
        }

        if($orderByStages['pay_time'] != 0){
            $orderByStages['pay_time'] = date('Y-m-d H:i',$orderByStages['pay_time']);
        }else{
            $orderByStages['pay_time'] = '';
        }

        if($orderByStages['payment_time'] != 0){
            $orderByStages['payment_time'] = date('Y-m-d H:i',$orderByStages['payment_time']);
        }else{
            $orderByStages['payment_time'] = '';
        }
    }

    //代理产品订单表
    if($operation == 'agent_order_list'){
        $countData = operationOrderCount($_W['uniacid']);
        // 前台加载模型数据
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = " where uniacid = :uniacid ";
        $where[":uniacid"] = $_W['uniacid'];

        //时间查询
        if($_GPC['status_select'] != '' && $_GPC['status_select'] != 1){
            $condition .=  ' AND '.$_GPC['status_select'].' BETWEEN :start and :end';
            $where[':start'] = strtotime($_GPC['time']['start'].' 00:00:00');
            $where[':end'] = strtotime($_GPC['time']['end'].' 23:59:59');
        }

        //状态查询
        if($_GPC['order_status'] != '' && $_GPC['order_status'] != 10){
            $condition .= " AND status =:status";
            $where[':status'] = $_GPC['order_status'];
        }

        //关键字查询
        if($_GPC['content'] != ''){
            $condition .= " AND ( name LIKE '%{$_GPC['content']}%' or mobile LIKE '%{$_GPC['content']}%' or goods_name LIKE '%{$_GPC['content']}%' or order_sn LIKE '%{$_GPC['content']}%' )";
        }

        $total=pdo_fetch('select count(*) as num from'.tablename('chat_agent_order').$condition,$where);
        $agent_order = pdo_fetchall('select * from'.tablename('chat_agent_order').$condition.' order by create_time desc limit '.($pindex - 1) * $psize .',' .$psize.'',$where);

        $pager = pagination($total['num'], $pindex, $psize);

    }

    //代理产品订单详情
    if($operation == 'agent_order_details'){
        $countData = operationOrderCount($_W['uniacid']);
        if($_GPC['aoid']){
            $ao_details = pdo_get('chat_agent_order',array('uniacid'=>$_W['uniacid'],'aoid'=>$_GPC['aoid']));
            $agent_user = pdo_get('chat_agent',array('uniacid'=>$_W['uniacid'],'agid'=>$ao_details['parent_id']));
        }
    }

    //上传
    if($operation == 'upload_status'){
        if($_GPC['aoid']){
            $agent_order = pdo_get('chat_agent_order',array('aoid'=>$_GPC['aoid'],'uniacid'=>$_W['uniacid'])); //获取代理订单信息
            $url = 'http://mw.jssell.com/company/registerUserCpyService';
            $data = array(
                'user_name' => $agent_order['name'],
                'user_mobile' => $agent_order['mobile'],
                'user_company' => $agent_order['corporate'],
                'out_trade_no' => $agent_order['order_sn'],
                'mch_id' => $agent_order['mch_id'],
                'prepay_id' => $agent_order['prepay_id'],
                'transaction_id' => $agent_order['transaction_id'],
                'fee_type' => 'CNY',
                'cloud_month_num' => 12
            );
            //是否代理 0否 1是
            if($agent_order['is_ontrial'] == 1){//是
                $data['agent_Flag'] = 1;
                $data['attach_msg'] = '公众号';
                $data['total_fee'] = 99900;
                $data['product_amt'] = 99900;
                $data['diskDution_num'] = 50;
            }elseif($agent_order['is_ontrial'] == 0){//否
                $data['agent_Flag'] = 0;
                $data['attach_msg'] = '税媒';
                $data['total_fee'] = 699100;
                $data['product_amt'] = 499900;
                $data['diskDution_num'] = 100;
            }
            $data['sign'] = getSign($data);//取到签名
            $sendCmd = sendCmd($url,$data);//发送请求数据
            if($sendCmd){
                $sendCmd = json_decode($sendCmd,1);

                if($sendCmd['status'] == 200){
                    $agent_update = pdo_update('chat_agent_order',array('status'=>3),array('aoid'=>$_GPC['aoid'],'uniacid'=>$_W['uniacid']));
                    if($agent_update){
                        echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
                    }else{
                        echo json_encode(array('code'=>400,'msg'=>'操作失败'));exit;
                    }
                }else{
                    echo json_encode(array('code'=>400,'msg'=>$sendCmd['msg']));exit;
                }
            }
        }
    }

    //创建签名
    function getSign($data)
    {
        foreach ($data as $k => $v) {
           $Parameters[$k] = $v;
        }
        $String = formatBizQueryParaMap($Parameters, false);

        $String = $String . "&key=fdsaf5fsdfvcnklkkiefds3fdsauqd";

        $String = md5($String);

        $result_ = strtoupper($String);

        return $result_;
    }

    function formatBizQueryParaMap($paraMap, $urlencode)
    {
       $buff = "";
       ksort($paraMap);
       foreach ($paraMap as $k => $v)
       {
           if($urlencode)
           {
               $v = urlencode($v);
           }

           $buff .= $k . "=" . $v . "&";
       }
       $reqPar = '';
       if (strlen($buff) > 0)
       {
           $reqPar = substr($buff, 0, strlen($buff)-1);
       }
       return $reqPar;
    }

    /**
    * 发起请求
    * @param  string $url  请求地址
    * @param  string $data 请求数据包
    * @return   string      请求返回数据
    */
    function sendCmd($url,$data)
    {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检测
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:')); //解决数据包大不能提交
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回

        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
           echo 'Errno'.curl_error($curl);
        }
        curl_close($curl); // 关键CURL会话
        return $tmpInfo; // 返回数据
    }

 	include $this -> template('order');
?>