<?php

namespace vendor\Pay\Driver;

class Alipay extends \vendor\Pay\Pay {

    protected $gateway = 'https://mapi.alipay.com/gateway.do';
    protected $verify_url = 'http://notify.alipay.com/trade/notify_query.do';
    protected $config = array(
        'email' => '',
        'key' => '',
        'partner' => ''
    );

    public function check() {
        if (!$this->config['email'] || !$this->config['key'] || !$this->config['partner']) {
           // E("支付宝设置有误！");
            echo "支付宝设置有误!";
        }
        return true;
    }

    public function buildRequestForm(\vendor\Pay\PayVo $vo){
        switch ($vo->getPayMethod()) {
            case 'direct':
                $seller = $vo->getPayMethod()=="mobile"?"seller_id":"seller_email";
                $param = array(
                    'service'        => $vo->getService(),//create_direct_pay_by_user
                    'payment_type'   => '1',
                    '_input_charset' => 'utf-8',
                    $seller          => $this->config['email'],//seller_email
                    'partner'        => $this->config['partner'],
                    'notify_url'     => $this->config['notify_url'],
                    'return_url'     => $this->config['return_url'],
                    'out_trade_no'   => $vo->getOrderNo(),
                    'subject'        => $vo->getTitle(),
                    'body'           => $vo->getBody(),
                    'total_fee'      => $vo->getFee(),
                    'it_b_pay'       => '30m'
                );
                #对数组进行排序
                $param = $this->argSort($param);
                #把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
                $arg = $this->createLinkstring($param);
                $param['sign'] = md5($arg . $this->config['key']);
                $param['sign_type'] = 'MD5';
                $sHtml = $this->_buildForm($param, $this->gateway, 'get');
                break;
            case 'mobile':
                //组装商品内容
                $jsonData = str_replace("\\/", "/", json_encode([
                    'timeout_express'=>'30m',
                    'product_code'=>"QUICK_MSECURITY_PAY",
                    'total_amount'=>$vo->getFee(),
                    'subject'=>$vo->getTitle(),
                    'body'=>$vo->getBody(),
                    'out_trade_no'=>$vo->getOrderNo()],JSON_UNESCAPED_UNICODE));
                $payData['charset'] = "utf-8";
                $payData['biz_content'] = $jsonData;
                $payData['method'] = "alipay.trade.app.pay";
                $payData['app_id'] = "";
                $payData['sign_type'] = "RSA2";
                $payData['version'] = '1.0';
                $payData['notify_url'] = "http://".$_SERVER['HTTP_HOST']."/notify/sdknotify/";
                $payData['timestamp'] = date("Y-m-d H:i:s",time());
                #对数组进行排序
                $param = $this->argSort($payData);
                $arg = $this->createLinkstring($param);
                $payData['sign'] = urlencode($this->sign2($arg));
                $datax = $this->createLinkstring($payData);
                //返回给SDK控制器
                $sHtml['arg']  = $datax;
                $sHtml['sign'] = $this->sign2($arg);
                $sHtml['out_trade_no'] = $vo->getOrderNo();

                break;
        }

        return $sHtml;
    }

    //RSA2签名
    public function sign2($data) {
        //读取私钥文件
        $priKey = file_get_contents("./Application/Sdk/SecretKey/alipay/rsa_private_key.pem");//私钥文件路径
        //转换为openssl密钥，必须是没有经过pkcs8转换的私钥
        $res = openssl_get_privatekey($priKey);
        //调用openssl内置签名方法，生成签名$sign
        openssl_sign($data, $sign, $res ,OPENSSL_ALGO_SHA256);
        //释放资源
        openssl_free_key($res);
        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }

//    public function buildRequestFormxxxx(\Think\Pay\PayVo $vo) {
//        $seller = $vo->getPayMethod()=="mobile"?"seller_id":"seller_email";
//        $param = array(
//            //'service'        => $vo->getService(),//create_direct_pay_by_user
//            //'payment_type'   => '1',
//            //'_input_charset' => 'utf-8', //编码
//            //$seller          => $this->config['email'],//seller_email
//            //'partner'        => $this->config['partner'],  //APPID合作ID
//            //'notify_url'     => $this->config['notify_url'],
//            //'return_url'     => $this->config['return_url'],
//            //'total_fee'      => $vo->getFee(), //金额
//            // 'it_b_pay'       => '30m' //时间
//
//            'gatewayUrl'     => "https://openapi.alipay.com/gateway.do",
//            'charset'        => 'utf-8',  //编码
//            'app_id'          => '',  //APPID合作ID
//            'format'         => "json",  //格式
//            'rsaPrivateKey'  => '', //私钥
//            'alipayrsaPublicKey' => '',  //公钥
//            'out_trade_no'   => $vo->getOrderNo(), //订单号
//            'subject'        => $vo->getTitle(),  //商品名称
//            'total_amount'   => $vo->getFee(), //金额
//            'body'           => $vo->getBody(),  //商品介绍
//            'product_code'   => 'QUICK_MSECURITY_PAY',
//            'timeout_express'=> '30m'
//        );
//        #对数组进行排序
//        $param = $this->argSort($param);
//        switch ($vo->getPayMethod()) {
//            case 'direct':
//                #把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
//                $arg = $this->createLinkstring($param);
//                $param['sign'] = md5($arg . $this->config['key']);
//                $param['sign_type'] = 'MD5';
//                $sHtml = $this->_buildForm($param, $this->gateway, 'get');
//                break;
//            case 'mobile':
//                #把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串 并对字符串做urlencode编码
//                $arg = $this->createLinkstring($param);
//                $param['sign'] = $this->sign($arg);  //私钥
//                //$sHtml['arg']  = $arg."&sign=".urlencode($param['sign'])."&sign_type=RSA";
//                $sHtml['arg']  = $arg."&sign=".urlencode($param['sign'])."&sign_type=RSA2";
//                $sHtml['sign'] = $param['sign'];
//                $sHtml['out_trade_no'] = $vo->getOrderNo();
//                break;
//        }
//        return $sHtml;
//    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    protected function createLinkstring($para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);
        
        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
        
        return $arg;
    }
    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    protected function createLinkstringUrlencode($para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".urlencode($val)."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);
        
        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
        return $arg;
    }
    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    protected function paraFilter($para) {
        $para_filter = array();
        while (list ($key, $val) = each ($para)) {
            if($key == "sign" || $key == "sign_type" || $val == "")continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }
    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    protected function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }

    //RSA签名
    public function sign($data) {
        //读取私钥文件
        $priKey = file_get_contents("./Application/Sdk/SecretKey/alipay/rsa_private_key.pem");//私钥文件路径
        //转换为openssl密钥，必须是没有经过pkcs8转换的私钥
        $res = openssl_get_privatekey($priKey);
        //$res = openssl_pkey_get_private($priKey);
        //调用openssl内置签名方法，生成签名$sign
        openssl_sign($data, $sign, $res);
        //释放资源
        openssl_free_key($res);
        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }

    //验签
    public function rsa_verify($data, $sign) {
        // 读取公钥文件
        $pubKey = file_get_contents("./Application/Sdk/SecretKey/alipay/rsa_public_key.pem");//私钥文件路径
        //$pubKey = file_get_contents("./Application/Sdk/SecretKey/alipay/ali_public_key.pem");//私钥文件路径
        // 转换为openssl格式密钥
        $res = openssl_get_publickey($pubKey);
        // 调用openssl内置方法验签，返回bool值
        $result = ( bool ) openssl_verify ( $data, base64_decode ( $sign ), $res );
        // 释放资源
        openssl_free_key ( $res );   
        return $result;
    }

    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
    protected function getSignVeryfy($param, $sign) {
        
        $param_filter = array();
        #除去待签名参数数组中的空值和签名参数
        $param_filter = $this->paraFilter($param);
        #对数组排序
        $param_filter = $this->argSort($param_filter);

        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($param_filter);
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $isSgin = false;
        switch (strtoupper(trim($param['sign_type']))) {
            case "MD5" :
                $mysgin = MD5($prestr . $this->config['key']);
                $isSgin = $mysgin == $sign ? true:false;
                break;
            case "RSA" :
                $isSgin = $this->rsa_verify($prestr,$sign);
                break;
            default :
                $isSgin = false;
        }
        return $isSgin;
    }

    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
    public function verifyNotify($notify) {
        //生成签名结果
        $isSign = $this->getSignVeryfy($notify, $notify["sign"]);
        //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
        $responseTxt = 'true';
        if (!empty($notify["notify_id"])) {
            $responseTxt = $this->getResponse($notify["notify_id"]);
        }

        if (preg_match("/true$/i", $responseTxt) && $isSign) {
            $this->setInfo($notify);
            return true;
        } else {
            return false;
        }
    }

    protected function setInfo($notify) {
        $info = array();
        //支付状态
        $info['status'] = ($notify['trade_status'] == 'TRADE_FINISHED' || $notify['trade_status'] == 'TRADE_SUCCESS') ? true : false;
        $info['money']  = $notify['total_fee'];
        $info['trade_no'] = $notify['trade_no'];
        $info['out_trade_no'] = $notify['out_trade_no'];
        $this->info = $info;
    }

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空 
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    protected function getResponse($notify_id) {
        $partner = $this->config['partner'];
        $veryfy_url = $this->verify_url . "?partner=" . $partner . "&notify_id=" . $notify_id;
        $responseTxt = $this->fsockOpen($veryfy_url);
        return $responseTxt;
    }

}