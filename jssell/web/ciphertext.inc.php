<?php
global $_GPC, $_W;
checklogin();
$uniacid=$_W['uniacid'];

load()->func('tpl');
	if($_GPC['op'] == 'edit_ciphertext'){
       $seid = intval($_GPC['se_id']);

        if (checksubmit('submit')) {
            $data = array(
                     'name'=>$_GPC['name'],
                     'mobile' => $_GPC['mobile'],
                     'company'=>$_GPC['company'],
                     'pid'=>$_GPC['pid']
            );
            pdo_update("secretaries", $data, array('se_id' => $seid));
            itoast('操作成功！', $this->createWebUrl('ciphertext', array('version_id'=>0)), 'success');
        }
    }
    if ($_GPC['op'] == 'del_ciphertext') {
    	 $seid = intval($_GPC['se_id']);
    	 pdo_delete("secretaries",array('se_id' => $seid));
    	 itoast('操作成功！', $this->createWebUrl('ciphertext',array('version_id'=>0)),'success');
    }
    $seid = intval($_GPC['se_id']);
      $ciphertext = pdo_fetch('select name,mobile,company,pid from '.tablename('secretaries') . "where se_id=$seid");
     //$tempcondition=" and uniacid = '{$_W['uniacid']}'";
     $keyword=$_GPC['keyword'];
     $tempArray=array();
      	
     if(!empty($keyword)){
      	$tempcondition=$tempcondition." AND (A1.company LIKE '%{$keyword}%' OR A1.name LIKE '%{$keyword}%' OR A1.mobile LIKE '%{$keyword}%')";
     }
      	
     $pindex = max(1, intval($_GPC['page']));
     $psize = 20;
     //var_dump($tempcondition);
     $records = pdo_fetchall("SELECT * FROM ".tablename("secretaries")." A1  where is_del=0 ".  $tempcondition." ORDER BY A1.se_id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize,$tempArray);
     // var_dump($records);exit
      $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename("secretaries")." A1 where is_del=0 " . $tempcondition,$tempArray);
      $pager = pagination($total, $pindex, $psize);

include $this->template('ciphertext');
