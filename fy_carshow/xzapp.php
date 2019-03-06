<?php
/**
 * fy_carshow熊掌号接口定义
 *
 * @author jianglifeng
 * @url 
 */
defined('IN_IA') or exit('Access Denied');

class Fy_carshowModuleXzapp extends WeModuleXzapp {
	public function doPageTest(){
		global $_GPC, $_W;
		// 此处开发者自行处理
		include $this->template('test');
	}

}