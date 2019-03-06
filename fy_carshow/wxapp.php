<?php
/**
 * fy_carshow模块小程序接口定义
 *
 * @author jianglifeng
 * @url 
 */
defined('IN_IA') or exit('Access Denied');

class Fy_carshowModuleWxapp extends WeModuleWxapp {
	public function doPageTest(){
		global $_GPC, $_W;
		$errno = 0;
		$message = '返回消息';
		$data = array();
		return $this->result($errno, $message, $data);
	}

	public function doPageContent(){
		global $_GPC, $_W;
		$user = pdo_get('fy_carshow_user',array('openid'=>$_W['openid']));
		if(empty($user)){
			$user = array(
				'uniacid'=>$_W['uniacid'],
				'openid'=>$_W['openid'],
				'name'=>$_W['fans']['nickname'],
				'avatarurl'=>$_W['fans']['avatar'],
				'city'=>$_W['fans']['tag']['city'],
				'country'=>$_W['fans']['tag']['country'],
				'province'=>$_W['fans']['tag']['province'],
				'time'=>date('Y-m-d H:i:s',time())
			);
			pdo_insert('fy_carshow_user',$user);
			$user['id'] = pdo_insertid();
		}
		$content = array(
			'uniacid'=>$_W['uniacid'],
			'content'=>$_GPC['content'],
			'uid'=>$user['id'],
			'time'=>date('Y-m-d H:i:s',time())
		);
		pdo_insert('fy_carshow_content',$content);
		return $this->result(200,'世界因你而更精彩',json_encode(array('data'=>$content)));
	}

	public function doPageGetuid(){

	}
}