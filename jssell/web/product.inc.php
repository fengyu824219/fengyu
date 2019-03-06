<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$op = empty($_GPC['op']) ? 'display' : $_GPC['op'];
$action=$_GPC['action']?$_GPC['action']:'';
$acid = $_W['account']['uniacid'];

$role = $_W['role'];

if ($op == "display") {
    $pindex = max(1, intval($_GPC['page']));
    $psize  = 20;


    $condition = " where p.uniacid={$_W['uniacid']} ";

    //产品类型
    if ($_GPC['ptid'] !=7 && $_GPC['ptid'] !='') {
        $condition.= " AND p.ptid = '{$_GPC['ptid']}' ";
    }

    //产品标题或者编号
    if ($_GPC['key']!='') {
        $condition.= " AND p.title LIKE '%{$_GPC['key']}%' OR p.prid LIKE '%{$_GPC['key']}%' ";
    }


    //上下架
    if ($_GPC['status'] != 7 && $_GPC['status'] != '') {
        $condition.= " AND p.status = {$_GPC['status']} ";
    }

    $total = pdo_fetchcolumn(' select count(*) as num  from ' . tablename('chat_product') . ' p left join '. tablename('chat_product_type').' t on p.ptid=t.ptid ' . $condition);

    $product   = pdo_fetchall(' select p.*,t.type_name from ' . tablename('chat_product') . ' p left join '. tablename('chat_product_type').' t on p.ptid=t.ptid ' . $condition . ' order by p.sort  limit ' . ($pindex - 1) * $psize . ',' . $psize );

    $protype = pdo_fetchall(' select * from ' . tablename('chat_product_type').' where uniacid = :uniacid ',array(':uniacid'=>$_W['uniacid']));

    $pager = pagination($total, $pindex, $psize);

}
if($op == 'set_status'){//产品上下架
    if($_GPC['prid']){
        $product_update = pdo_update('chat_product',array('status'=>$_GPC['status']),array('uniacid'=>$_W['uniacid'],'prid'=>$_GPC['prid']));
        if($product_update){
            itoast("操作成功",'',success);
        }else{
            itoast("操作失败",'',error);
        }
    }
}
if($op == 'edit_product'){
    $prid = $_GPC['prid'];
    $condition = " where prid={$prid} and uniacid = ".$_W['uniacid'];
    $product = pdo_fetchall(' select * from ' . tablename('chat_product').$condition);
    $product_type = pdo_fetchall(' select * from ' . tablename('chat_product_type').' where uniacid = '.$_W['uniacid']);
    $pageNum = $_GPC['page'];
    if(checksubmit('submit'))
    {
        $data['images'] = $_GPC['images'];
        $data['title'] = $_GPC['title'];
        $data['desc'] = $_GPC['desc'];
        $data['ptid'] = $_GPC['ptid'];
        $data['img'] = $_GPC['img'];
        $data['agent_profit'] = $_GPC['agent_profit'];
        $data['price'] = $_GPC['price'];
        $data['original_price'] = $_GPC['original_price'];
        $data['url'] = $_GPC['url'];
        $data['ptid'] = $_GPC['ptid'];
        $data['status'] = $_GPC['status'];
        $data['is_by_stages'] = $_GPC['is_by_stages'];
        $data['sort'] = $_GPC['sort'];
        $data['content'] = $_GPC['content'];
        // $data['is_appoint'] = $_GPC['is_appoint'];
        $data['classification'] = $_GPC['classification'];
        $data['agreement_title'] = $_GPC['agreement_title'];
        $data['agreement_content'] = $_GPC['agreement_content'];
        $data['service_time'] = $_GPC['service_time'];
        $data['appoint_type'] = $_GPC['appoint_type'];
        $data['is_recommend'] = $_GPC['recommend'];

        $data['appoint_level'] = $_GPC['appoint_level'];
        $data['is_price'] = $_GPC['is_price'];
        $result = pdo_update('chat_product',$data,array('prid'=>$prid));
        if ($result) {
            itoast("操作成功！",$this->createWebUrl('product',array('op' => 'display', 'version_id' => 0,'page'=>$_GPC['pageNum'])),success);
        }else{
            itoast("操作失败！","",error);
        }
    }

}
if ($op == 'delete_product') {
    $prid = $_GPC['prid'];
    $result=pdo_delete('chat_product',array('prid'=>$prid));
    if($result){
       itoast("删除成功！",$this->createWebUrl('product',array('op' => 'display', 'version_id' => 0)),success);
    }else{
       itoast("删除失败！",$this->createWebUrl('product',array('op' => 'display', 'version_id' => 0)),error);
    }

}
if($op == 'add_product'){
    $product_type   = pdo_getall('chat_product_type');
    if(checksubmit('submit')){
        $data['uniacid'] = $_W['uniacid'];
        $data['images'] = $_GPC['images'];
        $data['ptid'] = $_GPC['ptid'];
        $data['price'] = $_GPC['price'];
        $data['original_price'] = $_GPC['original_price'];
        $data['title'] = $_GPC['title'];
        $data['desc'] = $_GPC['desc'];
        $data['img'] = $_GPC['img'];
        $data['agent_profit'] = $_GPC['agent_profit'];
        $data['url'] = $_GPC['url'];
        $data['status'] = $_GPC['status'];
        $data['is_by_stages'] = $_GPC['is_by_stages'];
        $data['create_time'] = time();
        // $data['is_appoint'] = $_GPC['is_appoint'];
        $data['is_appoint'] = 1;
        $data['sort'] = $_GPC['sort'];
        $data['content'] = $_GPC['content'];
        $data['classification'] = $_GPC['classification'];
        $data['agreement_title'] = $_GPC['agreement_title'];
        $data['agreement_content'] = $_GPC['agreement_content'];
        $data['service_time'] = $_GPC['service_time'];
        $data['appoint_type'] = $_GPC['appoint_type'];
        $data['appoint_level'] = $_GPC['appoint_level'];
        $data['is_recommend'] = $_GPC['recommend'];
        $data['is_price'] = $_GPC['is_price'];
        $result = pdo_insert('chat_product',$data);
        if ($result){
            itoast("添加成功！",$this->createWebUrl('product',array('op' => 'display', 'version_id' => 0)),success);
        }else{
            itoast("添加失败！","",error);
        }
    }
}
if ($op == "product_type") {
    $pindex = max(1, intval($_GPC['page']));
    $psize  = 20;
    $condition = " where uniacid={$_W['uniacid']} ";
	//产品标题或者编号
    if ($_GPC['key']) {
        $condition.= " AND ptid LIKE '%{$_GPC['key']}%' OR type_name LIKE '%{$_GPC['key']}%' ";
    }
    $total = pdo_fetch(' select count(*) as num  from ' . tablename('chat_product_type') . $condition);
    $type   = pdo_fetchall(' select * from ' . tablename('chat_product_type') . $condition . ' order by sort limit ' . ($pindex - 1) * $psize . ',' . $psize );
    $pager = pagination($total['num'], $pindex, $psize);
}
if($op == "set_status_product_type"){//产品类型上下架
    if($_GPC['ptid']){
        $product_data = pdo_get('chat_product',array('uniacid'=>$_W['uniacid'],'ptid'=>$_GPC['ptid'],'status'=>1));
        if($product_data && $_GPC['status'] == 0){
            itoast("请把该类型的产品下架","",error);exit;
        }else{
            $productType_update = pdo_update('chat_product_type',array('status'=>$_GPC['status']),array('uniacid'=>$_W['uniacid'],'ptid'=>$_GPC['ptid']));
            if($productType_update){
                itoast("操作成功",'',success);
            }else{
                itoast("操作失败",'',error);
            }
        }
    }
}
if ($op == "edit_product_type") {
    $ptype = pdo_fetch(' select * from ' . tablename('chat_product_type').' where ptid='.$_GPC['ptid']);
    if(checksubmit('submit')){
        $result = pdo_update('chat_product_type',array('type_name'=>$_GPC['title'],'image'=>$_GPC['image'],'sort'=>$_GPC['sort'],'status'=>$_GPC['status']),array('ptid'=>$_GPC['id']));
        if ($result) {
            itoast("操作成功！",$this->createWebUrl('product',array('op' => 'product_type', 'version_id' => 0)),success);
        }else{
            itoast("操作失败！","",error);
        }
    }
}
if ($op == "add_product_type") {
    if(checksubmit('submit')){
        $data['uniacid'] = $_W['uniacid'];
        $data['image'] = $_GPC['image'];
        $data['type_name'] = $_GPC['type_name'];
        $data['sort'] = $_GPC['sort'];
        $data['status'] = $_GPC['status'];
        $result = pdo_insert('chat_product_type',$data);
        if ($result) {
            itoast("添加成功！",$this->createWebUrl('product',array('op' => 'product_type', 'version_id' => 0)),success);
        }else{
            itoast("添加失败！","",error);
        }
    }

}
if ($op == "delete_product_type") {

    $ptid = $_GPC['ptid'];
    $product_data = pdo_get('chat_product',array('uniacid'=>$_W['uniacid'],'ptid'=>$ptid));
    if($product_data){
        itoast("存在该类型的产品","",error);exit;
    }else{
        $result = pdo_delete('chat_product_type',array('ptid'=>$ptid));
        if ($result) {
            itoast("删除成功！",$this->createWebUrl('product',array('op' => 'product_type', 'version_id' => 0)),success);
        }else{
            itoast("删除失败！","",error);
        }
    }

}
include $this->template('product');
?>