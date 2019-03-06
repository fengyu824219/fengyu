<?php
/**
 * fy_carshow百度小程序接口定义
 *
 * @author jianglifeng
 * @url 
 */
defined('IN_IA') or exit('Access Denied');

class Fy_carshowModuleBaiduapp extends WeModuleBaiduapp {
	public function doPageTest(){
		global $_GPC, $_W;
		$errno = 0;
		$message = '返回消息';
		$data = array();
		return $this->result($errno, $message, $data);
	}
}