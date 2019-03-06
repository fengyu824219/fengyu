<?php
/**
 * fy_carshow钉钉号接口定义
 *
 * @author jianglifeng
 * @url 
 */
defined('IN_IA') or exit('Access Denied');

class Fy_carshowModuleDingdingapp extends WeModuleDingdingapp {
	public function doPageTest(){
		global $_GPC, $_W;
		$errno = 0;
		$message = '返回消息';
		$data = array();
		return $this->result($errno, $message, $data);
	}
}