<?php

/**
 * 微信支付
 *
 * @package   NICMS
 * @category  app\common\library\pay
 * @author    失眠小枕头 [312630173@qq.com]
 * @copyright Copyright (c) 2013, 失眠小枕头, All rights reserved.
 * @link      www.NiPHP.com
 * @since     2019
 */
/*
$config = array(
    'appid' => 'xxx',
    'appsecret' => 'xxx',
    'mch_id' => '123',
    'key' => 'xxx',
    'sslcert_path' => '123_cert.pem',
    'sslkey_path' => '123_key.pem',
);
$obj = new PayWechat($config);
$param = array(
    'body'         => '商品描述 128位',
    'detail'       => '商品详情',
    'attach'       => '附加数据 127位',
    'out_trade_no' => '商户订单号 32位 数字',
    'total_fee'    => 1000,
    'goods_tag'    => '商品标记 32位',
    'notify_url'   => '异步通知回调地址,不能携带参数',
    'respond_url'  => '同步通知回调地址,不能携带参数',
    'product_id'   => '商品ID 32位',
    'openid'       => '请求微信OPENID 必填',
);
$obj->jsPay($param);

$param = array(
    'out_trade_no' => '商户订单号 32位 数字',
    'total_fee'    => '订单金额',
    'refund_fee'   => '退款金额',
    'refund_desc'  => '退款描述',
    );
$obj->refund($param);

*/

namespace app\common\library\pay;

class Wechat
{
    // 支付配置
    protected $config = [];

    protected $params = [];

    /**
     * 微信支付配置信息
     * @access public
     * @param  array  $config
     * @return void
     */
    public function __construct($config)
    {
        $this->config = [
            'appid'        => !empty($config['appid']) ? $config['appid'] : '',
            'appsecret'    => !empty($config['appsecret']) ? $config['appsecret'] : '',
            'mch_id'       => !empty($config['mch_id']) ? $config['mch_id'] : '',
            'key'          => !empty($config['key']) ? $config['key'] : '',
            'sign_type'    => !empty($config['sign_type']) ? $config['sign_type'] : 'md5',
            'sslcert_path' => !empty($config['sslcert_path']) ?
                EXTEND_PATH . 'net' . DIRECTORY_SEPARATOR . 'pay' . DIRECTORY_SEPARATOR . $config['sslcert_path'] : '',
            'sslkey_path'  => !empty($config['sslkey_path']) ?
                EXTEND_PATH . 'net' . DIRECTORY_SEPARATOR . 'pay' . DIRECTORY_SEPARATOR . $config['sslkey_path'] : '',
        ];
    }

    /**
     * 支付
     * @access public
     * @param  array $_params
     * @return mixed
     */
    public function transfer(array $_params)
    {
        /*$_params = array(
            'openid'       => '用户openid',
            // NO_CHECK：不校验真实姓名 FORCE_CHECK：强校验真实姓名
            'check_name'   => '校验用户姓名',
            // check_name为FORCE_CHECK时必填
            're_user_name' => '收款用户姓名',
            'amount'       => '金额',
            'desc'         => '企业付款描述信息',
        );*/
        $this->params = $_params;

        $this->params['mch_appid']        = $this->config['appid'];
        $this->params['mchid']            = $this->config['mch_id'];
        $this->params['nonce_str']        = $this->getNonceStr(32);
        $this->params['partner_trade_no'] = $this->config['mch_id'] . date('YmdHis') . mt_rand(111, 999);
        $this->params['spbill_create_ip'] = app('request')->ip();
        $this->params['sign']             = $this->getSign($this->params);

        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $response = $this->postXmlCurl($this->toXml(), $url, true);
        $result = $this->formXml($response);

        return $result;
    }

    /**
     *
     */
    public function sendBonus(array $params)
    {
        /*
        $params[
            'send_name' => '商户名称',
            're_openid' => '接受红包的用户',
            'total_amount' => '付款金额，单位分'
            'wishing' => '红包祝福语',
            'act_name' => '活动名称',
            'remark' => '备注',
        ]
        */
        $this->params = $params;

        $this->params['nonce_str']  = $this->getNonceStr(32);
        $this->params['mch_billno'] = $this->config['mch_id'] . date('YmdHis') . mt_rand(111, 999);
        $this->params['mch_id']     = $this->config['mch_id'];
        $this->params['wxappid']    = $this->config['appid'];
        $this->params['client_ip']  = app('request')->ip();
        $this->params['sign']       = $this->getSign($this->params);

        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        $response = $this->postXmlCurl($this->toXml(), $url, true);
        $result = $this->formXml($response);

        if ($result['result_code'] == 'SUCCESS' && $result['err_code'] == 'SUCCESS') {
            return true;
        } else {
            return $result;
        }
    }

    /**
     * H5支付
     * @param  array $params
     * @return mixed
     */
    public function H5Pay(array $params): string
    {
        // 同步通知回调地址
        $respond_url = $params['respond_url'];
        unset($params['respond_url']);

        $this->params = $params;
        $this->params['trade_type']  = 'MWEB';  // 交易类型
        $this->params['device_info'] = 'WEB';

        $result = $this->unifiedOrder();

        if ($result['return_code'] === 'FAIL') {
            return $result['return_msg'];
        } else {
            return $result['mweb_url'] . '&redirect_url=' . urlencode($respond_url);
        }
    }

    /**
     * 统一下单
     * @access public
     * @param  array  $params 支付参数
     * @return string JS
     */
    public function jsPay(array $params)
    {
        // 同步通知回调地址
        $respond_url = $params['respond_url'];
        unset($params['respond_url']);

        $this->params = $params;
        $this->params['trade_type']  = 'JSAPI';  // 交易类型
        $this->params['device_info'] = 'WEB';

        $result = $this->unifiedOrder();
        if ($result['return_code'] === 'FAIL') {
            return $result['return_msg'];
        } elseif ($result['result_code'] === 'FAIL') {
            return $result['err_code_des'];
        }

        // 新请求参数
        $params = [
            'appId'     => $result['appid'],
            'timeStamp' => (string) time(),
            'nonceStr'  => $this->getNonceStr(32),
            'package'   => 'prepay_id=' . $result['prepay_id'],
            'signType'  => strtoupper($this->config['sign_type']),
        ];

        $params['paySign'] = $this->getSign($params);
        $js_api_parameters = json_encode($params);

        return [
            'js_api_parameters' => $js_api_parameters,
            'notify_url' => $this->params['notify_url'],
            'js' => 'function jsApiCall(){WeixinJSBridge.invoke("getBrandWCPayRequest",' . $js_api_parameters . ',function(res){if (res.err_msg == "get_brand_wcpay_request:ok") {window.location.replace("' . $respond_url . '?out_trade_no=' . $this->params['out_trade_no'] . '");} else if (res.err_msg == "get_brand_wcpay_request:cancel") {}});}function callpay(){if (typeof WeixinJSBridge == "undefined"){if( document.addEventListener ){document.addEventListener("WeixinJSBridgeReady", jsApiCall, false);}else if (document.attachEvent){document.attachEvent("WeixinJSBridgeReady", jsApiCall);document.attachEvent("onWeixinJSBridgeReady", jsApiCall);}}else{jsApiCall();}}',
        ];
    }

    /**
     * 二维码支付
     * @access public
     * @param  array  $params 支付参数
     * @return string 二维码图片地址
     */
    public function qrcodePay(array $params): string
    {
        // 同步通知回调地址
        $respond_url = $params['respond_url'];
        unset($params['respond_url']);

        $this->params = $params;
        $this->params['trade_type']  = 'NATIVE';  // 交易类型
        $this->params['device_info'] = 'WEB';

        $result = $this->unifiedOrder();
        if ($result['return_code'] === 'FAIL') {
            return $result['return_msg'];
        } elseif ($result['result_code'] === 'FAIL') {
            return $result['err_code_des'];
        }
        return $result['code_url'];
    }

    /**
     * 同步通知回调
     * @access public
     * @param
     * @return mexid
     */
    public function respond()
    {
        if (!app('request')->has('out_trade_no', 'param')) {
            return false;
        }

        $out_trade_no = app('request')->param('out_trade_no');
        $result = $this->queryOrder(['out_trade_no' => $out_trade_no]);
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS' && $result['trade_state'] == 'SUCCESS') {
            return [
                'out_trade_no'   => $result['out_trade_no'],    // 商户订单号
                'openid'         => $result['openid'],          // 支付人OPENID
                'total_fee'      => $result['total_fee'],       // 支付金额
                'trade_type'     => $result['trade_type'],      // 支付类型
                'transaction_id' => $result['transaction_id'],  // 微信订单号
            ];
        }

        return false;
    }

    /**
     * 异步通知回调
     * @access public
     * @param
     * @return mexid
     */
    public function notify()
    {
        if (empty($GLOBALS['HTTP_RAW_POST_DATA'])) {
            return false;
        }

        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $result = (array) simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            $result = $this->queryOrder(array('out_trade_no' => $result['out_trade_no']));

            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS' && $result['trade_state'] == 'SUCCESS') {
                return array(
                    'out_trade_no'   => $result['out_trade_no'],    // 商户订单号
                    'openid'         => $result['openid'],          // 支付人OPENID
                    'total_fee'      => $result['total_fee'],       // 支付金额
                    'trade_type'     => $result['trade_type'],      // 支付类型
                    'transaction_id' => $result['transaction_id'],  // 微信订单号
                );
            }
        }

        return false;
    }

    /**
     * 退款操作
     * @access public
     * @param
     * @return mixed
     */
    public function refund(array $params)
    {
        $this->params = $params;

        $this->params['appid']         = $this->config['appid'];
        $this->params['mch_id']        = $this->config['mch_id'];
        $this->params['nonce_str']     = $this->getNonceStr(32);
        $this->params['out_refund_no'] = $this->config['mch_id'] . date('YmdHis') . mt_rand(111, 999);
        $this->params['op_user_id']    = $this->config['mch_id'];
        $this->params['sign']          = $this->getSign($this->params);

        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $response = $this->postXmlCurl($this->toXml(), $url, true);
        $result = $this->formXml($response);

        if ($result['return_code'] == 'FAIL') {
            return false;
        }

        if ($result['result_code'] == 'SUCCESS') {
            return true;
        } elseif ($result['err_code'] == 'TRADE_STATE_ERROR') {
            return true;
        } else {
            return false;
        }

        return $result;

        /*if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            // 退款成功
            // 订单处理业务
            return true;
        }
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'FAIL') {
            if ($result['err_code'] == 'TRADE_STATE_ERROR') {
                return '此订单已退达款，请勿重复操作';
            }
            return '退款失败';
        }
        return $result;*/
    }

    /**
     * 获得退款信息
     * @access public
     * @param
     * @return mixed
     */
    public function queryRefund(array $params): array
    {
        $this->params['appid']     = $this->config['appid'];
        $this->params['mch_id']    = $this->config['mch_id'];
        $this->params['nonce_str'] = $this->getNonceStr(32);

        if (!empty($params['transaction_id'])) {
            $this->params['transaction_id'] = $params['transaction_id'];
        }

        if (!empty($params['out_trade_no'])) {
            $this->params['out_trade_no'] = $params['out_trade_no'];
        }

        if (empty($this->params['transaction_id'])) {
            unset($this->params['transaction_id']);
        }

        if (empty($this->params['out_trade_no'])) {
            unset($this->params['out_trade_no']);
        }

        $this->params['sign'] = $this->getSign($this->params);

        $url = 'https://api.mch.weixin.qq.com/pay/refundquery';
        $response = $this->postXmlCurl($this->toXml(), $url);
        return $this->formXml($response);
    }

    /**
     * 获得订单信息
     * @access public
     * @param
     * @return mixed
     */
    public function queryOrder(array $params): array
    {
        $this->params['appid']     = $this->config['appid'];
        $this->params['mch_id']    = $this->config['mch_id'];
        $this->params['nonce_str'] = $this->getNonceStr(32);

        if (!empty($params['transaction_id'])) {
            $this->params['transaction_id'] = $params['transaction_id'];
        }

        if (!empty($params['out_trade_no'])) {
            $this->params['out_trade_no'] = $params['out_trade_no'];
        }

        if (empty($this->params['transaction_id'])) {
            unset($this->params['transaction_id']);
        }

        if (empty($this->params['out_trade_no'])) {
            unset($this->params['out_trade_no']);
        }

        $this->params['sign'] = $this->getSign($this->params);

        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $response = $this->postXmlCurl($this->toXml(), $url);
        return $this->formXml($response);
    }

    /**
     * 生成支付临时订单
     * @access private
     * @param
     * @return array
     */
    private function unifiedOrder(): array
    {
        $this->params['appid']            = $this->config['appid'];
        $this->params['mch_id']           = $this->config['mch_id'];
        $this->params['nonce_str']        = $this->getNonceStr(32);
        $this->params['spbill_create_ip'] = app('request')->ip();
        $this->params['time_start']       = date('YmdHis');
        $this->params['time_expire']      = date('YmdHis', time() + 600);
        $this->params['sign']             = $this->getSign($this->params);

        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $response = $this->postXmlCurl($this->toXml(), $url);
        return $this->formXml($response);
    }

    /**
     * 将array转为xml
     * @access private
     * @param
     * @return array
     */
    private function toXml(): string
    {
        $xml = '<xml>';
        foreach ($this->params as $key => $value) {
            if ($value != '' && !is_array($value)) {
                $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
            }
        }
        $xml .= '</xml>';

        return $xml;
    }

    /**
     * 将xml转为array
     * @access private
     * @param  string $xml
     * @return array
     */
    private function formXml(string $xml): array
    {
        libxml_disable_entity_loader(true);
        return (array) simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    }

    /**
     * 以post方式提交xml到对应的接口url
     * @access private
     * @param  string  $xml    需要post的xml数据
     * @param  string  $url    url
     * @param  intval  $second url执行超时时间，默认30s
     * @return mixed
     */
    private function postXmlCurl(string $xml, string $url, bool $use_cert = false, int $second = 30)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_TIMEOUT, $second);       // 设置超时
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);      // 严格校验
        curl_setopt($curl, CURLOPT_HEADER, false);          // 设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);   // 要求结果为字符串且输出到屏幕上
        if ($use_cert == true) {
            //设置证书 使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($curl, CURLOPT_SSLCERT, $this->config['sslcert_path']);
            curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($curl, CURLOPT_SSLKEY, $this->config['sslkey_path']);
        }
        curl_setopt($curl, CURLOPT_POST, true);             // post提交方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);       // post传输数据
        $result = curl_exec($curl);                         // 运行curl

        if ($result) {
            curl_close($curl);
            return $result;
        } else {
            $error = curl_errno($curl);
            curl_close($curl);
            return 'curl出错，错误码:' . $error;
        }
    }

    /**
     * 生成签名
     * @access private
     * @param  array $params
     * @return 加密签名
     */
    private function getSign(array $params): string
    {
        ksort($params);

        $sign = '';
        foreach ($params as $key => $value) {
            if (!in_array($key, ['sign', 'sslcert_path']) && !is_array($value) && $value != '') {
                $sign .= $key . '=' . $value . '&';
            }
        }
        $sign .= 'key=' . $this->config['key'];
        $sign = trim($sign, '&');
        $sign = $this->config['sign_type']($sign);

        return strtoupper($sign);
    }

    /**
     * 产生随机字符串，不长于32位
     * @access private
     * @param  intval $length
     * @return 产生的随机字符串
     */
    private function getNonceStr(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $count = strlen($chars) - 1;
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= substr($chars, mt_rand(0, $count), 1);
        }
        return $string;
    }
}
