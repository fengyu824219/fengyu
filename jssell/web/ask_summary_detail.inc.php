<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$op = $_GPC["op"] ? $_GPC["op"] : 'display';
$uniacid=$_W['uniacid'];

if($op == 'display'){
	$starttime = empty($_GPC['time']['start']) ? TIMESTAMP -  86399 * 30 : strtotime($_GPC['time']['start'].' 00:00:00');
	$endtime = empty($_GPC['time']['end']) ? TIMESTAMP+ 86400: strtotime($_GPC['time']['end'].' 23:59:59');
	$keyword = $_GPC['keyword'];
	$tempcondition=" where t.uniacid=:uniacid ";
	$tempArray=array(":uniacid"=>$uniacid);
	if(!empty($starttime)){
		$tempcondition .= " AND t.create_time>=:starttime";
		$tempArray['starttime']=$starttime;
	}
	if(!empty($endtime)){
		$tempcondition .= " AND t.create_time<:endtime";
		$tempArray['endtime']=$endtime;
	}
	if(!empty($_GPC['type']) && $_GPC['type'] != 9){
		$tempcondition .= " AND t.type = :type";
		$tempArray['type'] = $_GPC['type'];
	}
	if(!empty($_GPC['role']) && $_GPC['role'] != 9){
		$tempcondition .= " AND t.role = :role";
		$tempArray['role'] = $_GPC['role'];
	}
	if(!empty($keyword)){
		$tempcondition=$tempcondition." AND s.real_name LIKE '%{$keyword}%' ";
	}

	$pindex = max(1, intval($_GPC['page']));
	$psize = 15;
	$records = pdo_fetchall('SELECT t.*,s.real_name FROM '.tablename('chat_profit').' t left join '.
		                     tablename('chat_users').' s on t.uid=s.id '.
		                     $tempcondition.' ORDER BY t.create_time desc LIMIT '.
		                     ($pindex-1) * $psize . ','.$psize,$tempArray);

	foreach ($records as $key => $value) {
		if($value['type'] == 1){
			$records[$key]['go_url'] = $this->createWebUrl('view_answer',array('askid'=>$value['type_id'],'type'=>2));
		}
		if($value['type'] == 2){
			$records[$key]['go_url'] = $this->createWebUrl('ask_payment',array('reid'=>$value['type_id'],'op'=>'art_ask'));
		}
		if($value['type'] == 3){
			$records[$key]['go_url'] = $this->createWebUrl('ask_payment',array('reid'=>$value['type_id'],'op'=>'art_ask'));
		}
		if($value['type'] == 4){
			$records[$key]['go_url'] = $this->createWebUrl('ask_payment',array('reid'=>$value['type_id'],'op'=>'zs_manage'));
		}
		if($value['type'] == 5){
			$records[$key]['go_url'] = $this->createWebUrl('order',array('oid'=>$value['type_id'],'op'=>'display'));
		}
	}

	$total=pdo_fetchcolumn('SELECT COUNT(*) FROM '.tablename('chat_profit').' t left join '.
		                     tablename('chat_users').' s on t.uid=s.id '.
		                     $tempcondition,$tempArray);

	$totalmoney=pdo_fetchcolumn('SELECT SUM(get_price) FROM '.tablename('chat_profit').' t left join '.
		                     tablename('chat_users').' s on t.uid=s.id '.
		                     $tempcondition,$tempArray);
	$pager = pagination($total, $pindex, $psize);
}


if($op == "download"){
	$starttime = empty($_GPC['time']['start']) ? TIMESTAMP -  86399 * 30 : strtotime($_GPC['time']['start'])+86399;
	$endtime = empty($_GPC['time']['end']) ? TIMESTAMP+ 86400: strtotime($_GPC['time']['end'])+86399;
	$keyword = $_GPC['keyword'];
	$tempcondition=" where t.uniacid=:uniacid ";
	$tempArray=array(":uniacid"=>$uniacid);
	if(!empty($starttime)){
		$tempcondition .= " AND t.create_time>=:starttime";
		$tempArray['starttime']=$starttime;
	}
	if(!empty($endtime)){
		$tempcondition .= " AND t.create_time<:endtime";
		$tempArray['endtime']=$endtime;
	}
	if(!empty($_GPC['type']) && $_GPC['type'] != 9){
		$tempcondition .= " AND t.type = :type";
		$tempArray['type'] = $_GPC['type'];
	}
	if(!empty($_GPC['role']) && $_GPC['role'] != 9){
		$tempcondition .= " AND t.role = :role";
		$tempArray['role'] = $_GPC['role'];
	}
	if(!empty($keyword)){
		$tempcondition=$tempcondition." AND s.real_name LIKE '%{$keyword}%' ";
	}

	$records = pdo_fetchall('SELECT t.*,s.real_name FROM '.tablename('chat_profit').' t left join '.
	                         tablename('chat_users').' s on t.uid=s.id '.
		                     $tempcondition,$tempArray);

	//导出
	ob_end_clean();//清除缓冲区,避免乱码
    header("Content-type:application/octet-stream"); 
    header("Accept-Ranges:bytes");
    header("Content-type:application/vnd.ms-execl;charset=utf-8");//设置导出格式
    header("Content-Disposition:filename=收益数据记录表--".date('Y-m-d H:i:s',time()).".xls");
    //制作表头
    echo "<table border='1'";
    echo "<tr>";
    echo "<th>".'编号'."</th>";
    echo "<th>".'收益类目'."</th>";
    echo "<th>".'收益角色'."</th>";
    echo "<th>".'收益人名称'."</th>";
    echo "<th>".'总金额'."</th>";
    echo "<th>".'收益金额'."</th>";
    echo "<th>".'收益比'."</th>";
    echo "<th>".'添加时间'."</th>";
    echo "</tr>";
    foreach($records as $k => $v){
    	$b = round($v['get_price'] / $v['total_price'],2)*100;
        echo "<tr>";
        echo "<td>".$v['prid']."</td>";
        if($v['type'] == 1){
        	echo "<td>悬赏收益</td>";
        }elseif($v['type'] == 2){
        	echo "<td>文章收益</td>";
        }elseif($v['type'] == 3){
        	echo "<td>问答收益</td>";
        }elseif($v['type'] == 4){
        	echo "<td>赞赏收益</td>";
        }elseif($v['type'] == 5){
        	echo "<td>服务收益</td>";
        }
        if($v['role'] == 1){
        	echo "<td>税媒平台</td>";
        }elseif($v['role'] == 2){
        	echo "<td>税媒用户</td>";
        }elseif($v['role'] == 3){
        	echo "<td>普通专家</td>";
        }elseif($v['role'] == 4){
        	echo "<td>平台专家</td>";
        }
        if($v['role'] == 1){
        	echo "<td>税媒平台</td>";
        }else{
        	echo "<td>".$v['real_name']."</td>";
        }
        echo "<td>".$v['total_price']."</td>";
       	echo "<td>".$v['get_price']."</td>";
       	echo "<td>".$b."%</td>";
        echo "<td>".date('Y-m-d H:i:s',$v['create_time'])."</td>";
        echo "</tr>";
    }
    echo "</table>";exit;
}
include $this->template('ask_summary_detail');
?>