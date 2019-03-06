<?php
use Workerman\Worker;
require_once __DIR__ . '/Workerman/Autoloader.php';

// 注意：这里与上个例子不同，使用的是websocket协议
$ws_worker = new Worker("websocket://0.0.0.0:45612");

// 启动4个进程对外提供服务
$ws_worker->count = 1;

// 当收到客户端发来的数据后返回hello $data给客户端
$ws_worker->onMessage = function($connection, $data)
{
	global $ws_worker;
	$data = json_decode($data,true);
	if($data['msg_type'] == 'login'){
		$connection->uid = $data['uid'];
		$connection->name = $data['name'];
		$connection->avatar = $data['avatar'];
		$res = ['msg_type'=>'login','name'=>$data['name'],'content'=>'登录成功'];
	    // 向客户端发送$data
	    $connection->send(json_encode($res));
	}

	if($data['msg_type'] == 'news'){
		// 遍历当前进程所有的客户端连接，发送当前服务器的时间
        foreach($ws_worker->connections as $con){
        	// 判断是否是自己
        	if($con->uid == $data['uid']){
        		$data['is_my'] = 1;
        	}else{
        		$data['is_my'] = 0;
        	}
        	$con->send(json_encode($data));
        }
	}
	
};

// 运行worker
Worker::runAll();