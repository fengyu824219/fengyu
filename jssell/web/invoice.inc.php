<?php
global $_GPC, $_W;
checklogin();
load()->func('tpl');
$uniacid=$_W['uniacid'];
$operation =empty($_GPC['op']) ? "display" :$_GPC['op'];
require IA_ROOT . "/addons/dg_ask/class/invoice/HttpClientService.php";
require IA_ROOT . "/addons/dg_ask/class/invoice/CryptAES.php";
require IA_ROOT . "/addons/dg_ask/class/invoice/StaticData.php";

$eleInvoiceConfig = pdo_get('chat_electronics_invoice_config',array('name'=>'nuonuo'));
$nuonuoUrl = pdo_get('chat_invoice_config',array('uniacid'=>$_W['uniacid']),array('invoice_url'));

//开具发票
if($operation == 'display'){
	// 前台加载模型数据
	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;
    $condition = ' where uniacid = :uniacid ';
    $where[':uniacid'] = $_W['uniacid'];

    //时间查询
    if($_GPC['status_select'] != 1 && $_GPC['status_select'] != ''){
        $condition .= ' and '.$_GPC['status_select'].' BETWEEN :start and :end ';
        $where[':start'] = strtotime($_GPC['time']['start'].' 00:00:00 ');
        $where[':end'] = strtotime($_GPC['time']['end'].' 00:00:00 ');
    }

    //状态查询
    if($_GPC['status'] != 10 && $_GPC['status'] != ''){
        $condition .= ' and status = :status ';
        $where[':status'] = $_GPC['status'];
    }

    //类型查询
    if($_GPC['kptype'] != 10 && $_GPC['kptype'] != ''){
        $condition .= ' and kptype = :kptype ';
        $where[':kptype'] = $_GPC['kptype'];
    }

    if($_GPC['content'] != ''){
        $condition .= " and ( nsrmc LIKE '%{$_GPC['content']}%' or ghfmc LIKE '%{$_GPC['content']}%' or nsrsbh LIKE '%{$_GPC['content']}%' ) ";
    }

    $invoiceLog = pdo_fetchall('select * from'.tablename('chat_invoice_log').$condition.' order by inid desc limit '.($pindex - 1) * $psize .',' .$psize.'',$where);
    foreach ($invoiceLog as $key => $val) {
        if(!empty($val['invoice_data'])){
            $invoiceLog[$key]['invoiceFileUrl'] = json_decode($val['invoice_data'],1)[0]["invoiceFileUrl"];
        }
    }

    $total = pdo_fetchcolumn('select count(*) from'.tablename('chat_invoice_log').$condition,$where);

    $pager = pagination($total, $pindex, $psize);
}

//电子发票
if($operation == 'electronicsInvoice'){
    if(!empty($_GPC['inid']) && !empty($_GPC['responseText'])){
        //获取授权码 $_GPC['obtain'] 1数据库取出  2接口请求
        if($_GPC['obtain'] == 1){
            $access_token = $_GPC['responseText'];
        }elseif($_GPC['obtain'] == 2){
            $access_token = $_GPC['responseText']['access_token'];
        }
        if($access_token != $eleInvoiceConfig['access_token']){
            pdo_update('chat_electronics_invoice_config',array('access_token'=>$access_token,'create_time'=>time()),array('name'=>'nuonuo'));
        }
        $invoiceLog = pdo_get('chat_invoice_log',array('inid'=>$_GPC['inid'],'uniacid'=>$_W['uniacid']));//发票数据
        $server = new HttpClientService();
        $timestmp = time();
        $aes = new CryptAES();
        $aes->iniAES(StaticData::$cipher,StaticData::$mode,StaticData::$pkmethod,$eleInvoiceConfig['app_secret']);
        /**
         * 业务参数
         *  PHP版本目前只支持JSON格式
        */
        $input = json_encode(array(
            'public' => array(
                'timestamp' => time(),
                'method' => StaticData::$apiName,
                'version' => StaticData::$apiVersion
            ),
            'private' => array(
                'servicedata' => array(
                    array(
                        'order'=>array(
                            'invoiceDate' => date('Y-m-d H:i:s',$invoiceLog['create_time']),//订单时间  Y
                            'listName' => '1',//详见销货清单  N
                            'remark' => $invoiceLog['bz'], //备注信息  N
                            'pushMode' => 0,//-1不推送  0邮箱  1手机  2手机、邮箱
                            'buyerAddress' => $invoiceLog['receive_address'],//购方地址（企业要填，个人可为空） N
                            'invoiceDetail' => array(
                                array(
                                    'favouredPolicyFlag' => '0',  //优惠政策标识:0不使用 1使用
                                    'taxRate' => $invoiceLog['sl'],  //税率  Y
                                    'withTaxFlag' => '1',  //单价含税标志，0不含税 1含税  Y
                                    'taxIncludedAmount' => $invoiceLog['hjje'],  //含税金额，[不含税金额] + [税额] = [含税金额]，红票为负。不含税金额、税额、含税金额任何一个不传时，会根据传入的单价，数量进行计算，可能和实际数值存在误差，建议都传入  N
                                    'goodsCode' => '',  //商品编码（商品税收分类编码开发者自行填写） N
                                    'zeroRateFlag' => '',  //零税率标识:空,非零税率;1免税 2不征税 3普通零税率  N
                                    'specType' => '',  //规格型号  N
                                    'unit' => '项',  //单位 N
                                    'num' => '1',  //数量（冲红时项目数量为负数） N
                                    'price' => $invoiceLog['hjje'],  //单价,当商品单价(price)为空时，商品数量(num)也必须为空；同时(price)为空时，含税金额(taxIncludedAmount)、不含税金额(taxExcludedAmount)、税额(tax)都不能为空  Y
                                    'tax' => $tax,  //税额，[不含税金额] * [税率] = [税额]；税额允许误差为 0.06。红票为负。不含税金额、税额、含税金额任何一个不传时，会根据传入的单价，数量进行计算，可能和实际数值存在误差，建议都传入  N
                                    'selfCode' => '',  //自行编码（可不填） N
                                    'favouredPolicyName' => '',  //增值税特殊管理（优惠政策名称）,当favouredPolicyFlag为1时，此项必填  N
                                    'deduction' => '',  //扣除额。差额征收时填写，目前只支持填写一项  N
                                    'taxExcludedAmount' => '',  //不含税金额。红票为负。不含税金额、税额、含税金额任何一个不传时，会根据传入的单价，数量进行计算，可能和实际数值存在误差，建议都传入  N
                                    'goodsName' => '服务费',  // 商品名称（如invoiceLineProperty =1，则此商品行为折扣行，折扣行不允许多行折扣，折扣行必须紧邻被折扣行，商品名称必须与被折扣行一致） Y
                                    'invoiceLineProperty' => '' //发票行性质:0,正常行;1,折扣行;2,被折扣行  N
                                )
                            ),
                            'buyerTel' => $invoiceLog['ghfdh'],  //购方电话  Y
                            'buyerAccount' => $invoiceLog['ghfbank'].$invoiceLog['ghfbankid'],  //购方银行账号及开户行地址（企业要填，个人可为空） N
                            'salerAddress' => $invoiceLog['nsrdz'],  //销方地址  Y
                            'invoiceNum' => '',  //冲红时填写的对应蓝票发票号码（红票必填，不满8位请左补0） N
                            'salerTaxNum' => $invoiceLog['nsrsbh'],  //销方税号（使用沙箱环境请求时消息体参数salerTaxNum和消息头参数userTax填写339901999999142） Y
                            'invoiceType' => '1',  //开票类型:1,正票;2,红票  Y
                            'payee' => '',  //收款人  N
                            'listFlag' => '',  //清单标志:0,根据项目名称数，自动产生清单;1,将项目信息打印至清单  N
                            'salerAccount' => $invoiceLog['nsrbank'].$invoiceLog['nsrbankid'],  //销方银行账号和开户行地址  Y
                            'buyerName' => $invoiceLog['ghfmc'],  //购方名称  Y
                            'checker' => '',  //复核人  N
                            'buyerTaxNum' => $invoiceLog['ghfnsrsbh'],  //购方税号（企业要填，个人可为空） N
                            'proxyInvoiceFlag' => '',  //代开标志:0非代开;1代开。代开蓝票时备注要求填写文案：代开企业税号:***,代开企业名称:***；代开红票时备注要求填写文案：对应正数发票代码:***号码:***代开企业税号:***代开企业名称:***  N
                            'orderNo' => $invoiceLog['order_no'],  //订单号（每个企业唯一） Y
                            'clerk' => $invoiceLog['kpr'],  //开票员  Y
                            'invoiceCode' => '',  //冲红时填写的对应蓝票发票代码（红票必填，不满12位请左补0） N
                            'email' => $invoiceLog['receive_mail'],  //推送邮箱（pushMode为0或2时，此项为必填） N
                            'buyerPhone' => $invoiceLog['ghfdh'],  //购方手机（开票成功会短信提醒购方，不受推送方式影响） Y
                            'departmentId' => '',  //部门门店id（诺诺系统中的id） N
                            'clerkId' => '', //开票员id（诺诺系统中的id） N
                            'salerTel' => $invoiceLog['nsrdh'] //销方电话  Y
                        )
                    )
                )
            )
        ));
        // var_dump($input);exit;
        $input = parameterHandle($input,$aes); //入参加密，压缩（可选），url编码
        // var_dump($input);exit;
        $hearde = buildHeaders($input,$server,$eleInvoiceConfig['app_key'],$access_token,$nuonuoUrl['invoice_url']);  //构建消息头
        // var_dump($hearde);exit;
        $result = $server->sendSyncSingleHttp($nuonuoUrl['invoice_url'], StaticData::$port, $hearde); //发送请求
        // var_dump($result);exit;
        if(!empty($result)){
            $result = explode("\r\n",$result);//转换成数组
            foreach ($result as $key => $val) {
                $interfaceData = strpos($val,'result');
                if($interfaceData != false){//判断是否存在
                    $dataArr = json_decode($val,1);
                }
            }
            if($dataArr['code'] == 'E0000'){
                $invoiceLogUpdate = pdo_update('chat_invoice_log',array('fpqqlsh'=>$dataArr['result']['invoiceSerialNum']),array('inid'=>$_GPC['inid'],'uniacid'=>$_W['uniacid']));
                echo json_encode(array('code'=>200,'msg'=>$dataArr['describe']));exit;
            }else{
                echo json_encode(array('code'=>100,'msg'=>$dataArr['describe']));exit;
            }
        }
    }else{
        echo json_encode(array('code'=>100,'msg'=>'缺少inid参数或responseText参数'));exit;
    }
}

if($operation == 'refuseInvoice'){//拒绝开票
    if($_POST){
        if(!empty($_GPC['inid'])){
            $invoiceLogUpdate = pdo_update('chat_invoice_log',array('status'=>3),array('inid'=>$_GPC['inid'],'uniacid'=>$_W['uniacid']));
            if(!empty($invoiceLogUpdate)){
                $invoiceLog = pdo_get('chat_invoice_log',array('inid'=>$_GPC['inid'],'uniacid'=>$_W['uniacid']),array('pids'));
                $pids = explode("|",$invoiceLog['pids']);
                foreach ($pids as $key => $val) {
                    pdo_update('chat_users_pay_log',array('is_invoice'=>0),array('pid'=>$val,'uniacid'=>$_W['uniacid']));
                }
                echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
            }else{
                echo json_encode(array('code'=>100,'msg'=>'操作失败'));exit;
            }
        }else{
            echo json_encode(array('code'=>100,'msg'=>'操作失败'));exit;
        }
    }
}

//查询电子发票明细
if($operation == 'queryInvoiceSerialNum'){
    if($_POST){
        if(!empty($_GPC['inid'])){
            $invoiceLog = pdo_get('chat_invoice_log',array('inid'=>$_GPC['inid'],'uniacid'=>$_W['uniacid']),array('fpqqlsh','invoice_data'));
            // if(empty($invoiceLog['invoice_data'])){//如果为空就请求接口
                $aes = new CryptAES();
                $aes->iniAES(StaticData::$cipher,StaticData::$mode,StaticData::$pkmethod,$eleInvoiceConfig['app_secret']);
                $server = new HttpClientService();
                $timestmp = time();
                $input = json_encode(
                    array(
                        'public' => array(
                            'timestamp' => time(),
                            'method' => 'nuonuo.electronInvoice.CheckEInvoice',
                            'version'=> '1.0'
                        ),
                        'private' => array(
                            'servicedata' => array(
                                array(
                                    'invoiceSerialNum' => array($invoiceLog['fpqqlsh'])
                                )
                            )
                        )
                    )
                );     
                $input = parameterHandle($input,$aes);
                $hearde = buildHeaders($input,$server,$eleInvoiceConfig['app_key'],$eleInvoiceConfig['access_token'],$nuonuoUrl['invoice_url']);     
                $result = $server->sendSyncSingleHttp($nuonuoUrl['invoice_url'], StaticData::$port, $hearde);
                if(!empty($result)){
                    $result = explode("\r\n",$result);//转换成数组
                    foreach ($result as $key => $val) {
                        $interfaceData = strpos($val,'result');
                        if($interfaceData != false){//判断是否存在
                            $dataArr = json_decode($val,1);
                        }
                    }
                    if($dataArr['code'] == 'E0000'){
                        if($dataArr['result'][0]['status'] == 0){
                            $status = 4;
                        }else if($dataArr['result'][0]['status'] == 1){
                            $status = 5;
                        }else if($dataArr['result'][0]['status'] == 2){
                            $status = 2;
                        }else if($dataArr['result'][0]['status'] == 3){
                            $status = 3;
                        }
                        $invoiceLogUpdate = pdo_update('chat_invoice_log',array('invoice_data'=>json_encode($dataArr['result']),'status'=>$status),array('inid'=>$_GPC['inid'],'uniacid'=>$_W['uniacid']));
                        echo json_encode(array('code'=>200,'msg'=>$dataArr['result'][0]['statusMsg']));exit;
                    }else{
                        echo json_encode(array('code'=>100,'msg'=>$dataArr['describe']));exit;
                    }
                }
            // }else{
            //     $invoiceUrl = json_decode($invoiceLog['invoice_data'],1);
            //     echo json_encode(array('code'=>200,'url'=>$invoiceUrl[0]['invoiceFileUrl']));exit;
            // }
        }else{
            echo json_encode(array('code'=>100,'msg'=>'查询发票信息失败'));exit;
        }
    }
}


/**
 * 业务入参处理（http body部分数据处理）
 *   加密，数据压缩，url编码
 * @param unknown $StaticData
 * @param unknown $compress
 * @param unknown $input
 */
function parameterHandle($input,$aes){
    $compress = new Compress();
    $input_ = urlencode($aes->encrypt($input));
    if(StaticData::$compress=='GZIP'){
        $input_ = $compress->gzipCompress($input_);
    }
    $input_ = 'param='.urlencode($input_);
    return $input_;
}

/**
 * 构建消息头信息（http header部分数据）
 * @param unknown $StaticData
 * @param unknown $input
 */
function buildHeaders($input,$server,$appKey,$accessToken,$invoice_url){
    //初始化数据
    $server->setInputData($input,
                          StaticData::$compress,
                          StaticData::$security,
                          StaticData::$dataType);
    //构建header部分数据
    $hearde = $server->makeHeader(StaticData::$path,
                                  $appKey,
                                  $accessToken,
                                  StaticData::$userTax,
                                  StaticData::$compress,
                                  StaticData::$security,
                                  StaticData::$dataType,
                                  StaticData::$app_rate,
                                  $invoice_url,
                                  StaticData::$referer,
                                  StaticData::$Content_Type);
    return $hearde;
}

//电子发票配置
if($operation == 'invoiceConfig'){
    if($_POST){
        if($_GPC['requestType'] == 'obtain'){
            $eleInvoiceConfigData = array('app_key'=>$_GPC['app_key'],'app_secret'=>$_GPC['app_secret'],'access_token'=>$_GPC['responseText']['access_token'],'create_time'=>time());
            if(empty($eleInvoiceConfig)){
                $eleInvoiceConfigData['name'] = 'nuonuo';
                $eleInvoiceConfigSave = pdo_insert('chat_electronics_invoice_config',$eleInvoiceConfigData);
            }else{
                $eleInvoiceConfigSave = pdo_update('chat_electronics_invoice_config',$eleInvoiceConfigData,array('name'=>'nuonuo'));
            }
        }else if($_GPC['requestType'] == 'eliminate'){
            $eleInvoiceConfigSave = pdo_update('chat_electronics_invoice_config',array('access_token'=>'','create_time'=>0),array('id'=>$_GPC['id']));
        }
        if(!empty($eleInvoiceConfigSave)){
            echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
        }else{
            echo json_encode(array('code'=>100,'msg'=>'操作失败'));exit;
        }
    }
}



//开票详情
if($operation == 'invoiceDetails'){
    $invoiceLog = pdo_get('chat_invoice_log',array('uniacid'=>$_W['uniacid'],'inid'=>$_GPC['inid']));
    if(empty($invoiceLog['nsrdz'])){
        $invoiceLog['nsrdz'] = '无';
    }
    if(empty($invoiceLog['nsrdh'])){
        $invoiceLog['nsrdh'] = '无';
    }
    if(empty($invoiceLog['nsrbank'])){
        $invoiceLog['nsrbank'] = '无';
    }
    if(empty($invoiceLog['nsrbankid'])){
        $invoiceLog['nsrbankid'] = '无';
    }
    $pids = explode("|",$invoiceLog['pids']);

    foreach ($pids as $key => $val) {
        $userPayLog[$key] = pdo_get('chat_users_pay_log',array('uniacid'=>$_W['uniacid'],'pid'=>$val));
        $userPayLog[$key]['userData'] = pdo_get('chat_users',array('uniacid'=>$_W['uniacid'],'id'=>$userPayLog[$key]['uid']),array('real_name','nickname'));
        if(empty($userPayLog[$key]['userData']['real_name'])){
            $userPayLog[$key]['userData']['real_name'] = $userPayLog[$key]['userData']['nickname'];
        }
    }
}

//更新状态
if($operation == 'setStatus'){
    if($_POST){
        $invoiceLogData = array(
            'status' => $_GPC['status'],
            'express_status' => $_GPC['express_status'],
            'express_name' => $_GPC['express_name'],
            'express_num' => $_GPC['express_num']
        );
        
        $invoiceLogUpdate = pdo_update('chat_invoice_log',$invoiceLogData,array('inid'=>$_GPC['inid'],'uniacid'=>$_W['uniacid']));
        if(!empty($invoiceLogUpdate)){
            $invoiceLog = pdo_get('chat_invoice_log',array('inid'=>$_GPC['inid'],'uniacid'=>$_W['uniacid']),array('hjje','pids','uid'));

            if($_GPC['status'] == 3){//判断是否发票失败
                $pids = explode('|',$invoiceLog['pids']);
                foreach ($pids as $key => $val) {
                    pdo_update('chat_users_pay_log',array('is_invoice'=>0),array('pid'=>$val,'uniacid'=>$_W['uniacid']));
                }
            }
            if($_GPC['express_status'] == 2){//判断是否已发快递
                $user = pdo_get('chat_users',array('id'=>$invoiceLog['uid'],'uniacid'=>$_W['uniacid']),array('openid'));
                $weObj = WeAccount::create($_W['uniacid']);
                $send_data = array(
                    'first'=>array('value'=>'您好，您申请的发票已寄递'),
                    'keyword1'=>array('value'=>$_GPC['express_num']),
                    'keyword2'=>array('value'=>$_GPC['express_name']),
                    'keyword3'=>array('value'=>$invoiceLog['hjje']),
                    'remark'=>array('value'=>'请注意签收'),
                );

                $url = $_W['siteroot'] . $this->createMobileUrl('my_chat',array('op'=>'my_consumption','from'=>'template'));
                $weObj->sendTplNotice($user['openid'], 'Vt04I9EGbouJ-6OURqT-yNHoEiaxv_Ow0Ukf9YKBJlM', $send_data, $url, '#173177');
            }
            echo json_encode(array('code'=>200,'msg'=>'操作成功'));exit;
        }else{
            echo json_encode(array('code'=>100,'msg'=>'操作失败'));exit;
        }
    }

    $pageNum = $_GPC['page'];
    $inid = $_GPC['inid'];
    $eid = $_GPC['eid'];
}

include $this->template('invoice');
?>