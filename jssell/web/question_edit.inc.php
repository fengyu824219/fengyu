<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$uniacid=$_W['uniacid'];
$menus = pdo_fetchall('SELECT imid,name,price FROM '.tablename('chat_index_menu') . ' where status=1 and uniacid = :uniacid order by sort asc ',array('uniacid'=>$_W['uniacid']));
if($_GPC['op'] == "reward_edit") {
    $row_id = $_GPC['row_id'];//问答Id
    if(!empty($row_id)){
        $ask = pdo_fetch('SELECT ask_content,steal_money,ask_type,fuid,payto_uid FROM '.tablename('chat_ask') . ' where id='.$row_id);
    }


    //选中的栏目
    $check_menus = pdo_fetchall('select imid from'.tablename('chat_index_record').' where uniacid = :uniacid and type = 2 and reid = :reid ',array(':uniacid'=>$_W['uniacid'],':reid'=>$row_id));//类目与文章的关系表
    foreach ($check_menus as $key => $val) {
        $strs[] = $val['imid'];
    }

    if (checksubmit('submit')) {
        $cmid = $_GPC['cmid'];
        $ask = pdo_fetch('SELECT ask_content,steal_money,ask_type,fuid,payto_uid FROM '.tablename('chat_ask') . ' where id='.$_GPC['reid']);
        $ask_user = pdo_get("chat_users",["id"=>$ask['payto_uid'],"uniacid"=>$_W['uniacid']],["nickname","real_name","id"]);

        if (!empty($cmid)) {
            $str  = implode(",", $cmid);
            $list = pdo_fetchall("DELETE FROM " . tablename("chat_index_record") . " where imid not in (" . $str . ") and type=2 and reid=" . $_GPC['reid']);
            pdo_update("chat_ask",["is_chosen"=>1],["id"=>$_GPC['reid']]);
            // $this->add_integral($_GPC['fuid'],11,$_GPC['reid']);
        }
        else{
            $list = pdo_fetchall("DELETE FROM " . tablename("chat_index_record") . " where  type=2 and reid=" . $_GPC['reid']);
            pdo_update("chat_ask",["is_chosen"=>0],["id"=>$_GPC['reid']]);
        }

        foreach ($cmid as $k => $v) {
            $datas['uniacid']     = $_W['uniacid'];
            $datas['imid']        = $v;
            $datas['reid']        = $_GPC['reid'];
            $datas['type']        = 2;
            $datas['title']       = $_GPC['content'];
            $datas['real_name']       = $ask_user['real_name'];
            $datas['uid']       = $ask_user['uid'];
            $datas['nickname']       = $ask_user['nickname'];

            $datas['create_time'] = time();
            $record = pdo_get('chat_index_record',array('uniacid'=>$_W['uniacid'],'reid'=>$_GPC['reid'],'imid'=>$v,'type'=>2));
            if(empty($record)){
                pdo_insert('chat_index_record', $datas);
            }
        }

        if($_GPC['price']){//价格
            $price = explode(',',$_GPC['price']);
            $price = max($price);
        }

        $data = [
            "steal_money" => $price
        ];

        pdo_update('chat_ask', $data, array("id" => $_GPC['reid']));
        if($_GPC['ask_type'] == 1){
            itoast("编辑成功", $this->createWebUrl('question_manage', array( 'version_id' => 0)), success);
        }else if($_GPC['ask_type'] == 2){
            itoast("编辑成功", $this->createWebUrl('reward_manage', array( 'version_id' => 0)), success);
        }
    }



}
include $this->template('question_edit');
?>