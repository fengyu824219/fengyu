<?php
global $_GPC, $_W;
load()->func('tpl');
$id=$_GPC["id"];

$res=pdo_fetch("select * from ".tablename("chat_audit")." where id=:id",array(":id"=>$id));
$imgurl=$res["imgurl"];
include $this->template('audit_img');