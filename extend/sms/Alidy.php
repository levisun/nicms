<?php

namespace sms;

class Alidy
{
    private $api = 'https://dysmsapi.aliyuncs.com/?';
    private $config = [
        'Signature'        => '',
        'AccessKeyId'      => '',
        'Action'           => 'SendSms',
        'Format'           => 'json',
        'SignatureMethod'  => 'HMAC-SHA1',
        'SignatureNonce'   => 0,
        // 'SignatureNonce'   => time() . rand(111111111, 999999999),
        'SignatureVersion' => '1.0',
        'Timestamp'        => '',
        // 'Timestamp'        => gmdate('Y-m-d') . 'T' . gmdate('H:i:m') . 'Z',
        'Version'          => '2017-05-25',

        'SignName'         => '',
        'TemplateCode'     => '',
        'PhoneNumbers'     => '',
        'TemplateParam'    => '',
    ];

    public function __construct(array $_config)
    {
        $this->config['SignatureNonce'] = time() . rand(111111111, 999999999);
        $this->config['Timestamp'] = gmdate('Y-m-d') . 'T' . gmdate('H:i:m') . 'Z';
        $this->config = array_merge($this->config, $_config);
    }

    public function send(array $_param): bool
    {
        $params = array_merge($this->config, $_param);
        $params['Signature'] = $this->sign($params);

        $result = file_get_contents($this->api . http_build_query($params));
        $result = $result ? json_decode($result, true) : false;
        if ($result && $result['Message'] == 'OK' && $result['Code'] == 'OK') {
            return true;
        } else {
            return false;
        }
    }

    private function sign(array &$_params): string
    {
        ksort($_params);
        $str_sign = '';
        foreach ($_params as $key => $value) {
            if ($key != 'Signature') {
                $str_sign .= urlencode($key) . '=' . urlencode($value) . '&';
            }
        }
        $str_sign = rtrim($str_sign, '&');
        $signature = 'GET&' . urlencode('/') . '&' . urlencode($str_sign);
        return base64_encode(hash_hmac('sha1', $signature, 'ftagFtNscXpWJ26EpvDauyRMspNjQb&', true));
    }
}
