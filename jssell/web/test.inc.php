<?php
$this->load(array("Dg_Base","Chat_Ask","Chat_Ask_Share"));

global $_GPC, $_W;

checklogin();
$uniacid=$_W['uniacid'];

$records=Chat_Ask::getInfoById($uniacid,569);

var_dump($records);

$records=Chat_Ask_Share::getInfoById($uniacid,2);

var_dump($records);