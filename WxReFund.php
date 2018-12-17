<?php
//注意:证书目录需要根据自己的实际目录进行填写
//退款需要提供的参数
/*$params = array(
    'nonce_str'     => md5(time()), //随机串
    'sign'          => 'md5',          //签名方式
    'transaction_id'=> $transactionId,//微信支付订单号 与商户订单号二选一
    //'out_trade_no'=> '', //商户订单号 和微信支付订单号二选一,由开发者后台生成
    'out_refund_no' => $sn,//退单号,数据库中的订单号,开发者自己生成
    'total_fee'     => $totalFee*100,   //订单金额,单位为分
    'refund_fee'    => $totalFee*100    //退款金额,单位为分
);
$wxRefund = new WxReFund($config['mchid'],$config['appid'],$config['appKey'],$config['apiKey']);
$data = $wxRefund->createRefund($params);//发起退款
if ($data){
    //你要干的事
}*/
class WxReFund
{
    protected $mchid;
    protected $appid;
    protected $appKey;
    protected $apiKey;
    const NOTIFY = '';   //回调通知地址需要更改成你自己服务器的地址
    const REFUNDURl = "https://api.mch.weixin.qq.com/secapi/pay/refund";//微信退款接口url

    public function __construct($mchid,$appid,$appKey,$apiKey) {
       $this->mchid  = $mchid; //商户id
       $this->appid  = $appid; //appid
       $this->appKey = $appKey;//微信支付申请对应的小程序)的appSecret
       $this->apiKey = $apiKey;//https://pay.weixin.qq.com 帐户设置-安全设置-API安全-API密钥-设置API密钥
    }
    //获取签名
    public function getSign($arr){ 
        //去除数组的空值
        array_filter($arr);
        if(isset($arr['sign'])){
            unset($arr['sign']);
        }
        //排序
        ksort($arr);
        //组装字符
        $str = $this->arrToUrl($arr) . '&key=' . $this->apiKey;
        //使用md5 加密 转换成大写 
       return strtoupper(md5($str));
    }
    //获取带签名的数组
    public function setSign($arr){
        $arr['sign'] = $this->getSign($arr);
        return $arr;
    }
    //校验签名,如果有需要进行验证
    public function checkSign($arr){        
        //生成新签名
        $sign = $this->getSign($arr);
        //和数组中原始签名比较
        if($sign == $arr['sign']){
            return true;
        }else{
            return false;
        }
    }
    //数组转URL字符串 不带key
    public function arrToUrl($arr){
        return urldecode(http_build_query($arr));
    }

    //Xml 文件转数组
    public function XmlToArr($xml)
    {	
        if($xml == '') return '';
        libxml_disable_entity_loader(true);
        $arr = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
        return $arr;
    }
    //数组转XML
    public function ArrToXml($arr)
    {
        if(!is_array($arr) || count($arr) == 0) return '';

        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
                if (is_numeric($val)){
                        $xml.="<".$key.">".$val."</".$key.">";
                }else{
                        $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
                }
        }
        $xml.="</xml>";
        return $xml; 
    }
    //post 字符串到接口
    public function postStr($url,$postfields){
        $ch = curl_init();
        $params[CURLOPT_URL]            = $url; //请求url地址
        $params[CURLOPT_HEADER]         = false;//是否返回响应头信息
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_FOLLOWLOCATION] = true; //是否重定向
        $params[CURLOPT_POST]           = true;
        $params[CURLOPT_SSL_VERIFYPEER] = false;//禁用证书校验
	$params[CURLOPT_SSL_VERIFYHOST] = false;
        $params[CURLOPT_POSTFIELDS]     = $postfields;
        curl_setopt_array($ch, $params); //传入curl参数
        $content = curl_exec($ch); //执行
        curl_close($ch); //关闭连接
        return $content;
    }

    //退款需要双向证书
    public function sendRefundPostStr($url,$postfields)
    {
        $ch = curl_init();
        $params[CURLOPT_URL]            = $url; //请求url地址
        $params[CURLOPT_HEADER]         = false;//是否返回响应头信息
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_FOLLOWLOCATION] = true; //是否重定向
        $params[CURLOPT_POST] = true;
        $params[CURLOPT_SSL_VERIFYPEER] = false;//禁用证书校验
        $params[CURLOPT_SSL_VERIFYHOST] = false;
        //以下是证书相关代码
        $params[CURLOPT_SSLCERTTYPE]    = 'PEM';
        $params[CURLOPT_SSLCERT]        =  FCPATH.'cert/apiclient_cert.pem';//证书目录,根据自己目录进行修改
        $params[CURLOPT_SSLKEYTYPE]     = 'PEM';
        $params[CURLOPT_SSLKEY]         = FCPATH.'cert/apiclient_key.pem';//证书目录,根据自己目录进行修改

        $params[CURLOPT_POSTFIELDS]     = $postfields;
        curl_setopt_array($ch, $params); //传入curl参数
        $content = curl_exec($ch); //执行
        curl_close($ch); //关闭连接
        return $content;
    }

    //退款接口
    public function createRefund($params)
    {
        $params['appid']  = $this->appid;//APPID
        $params['mch_id'] = $this->mchid;//商户号

        //生成签名
        $signParams = $this->setSign($params);

        //将数据转换为xml
        $xmlData = $this->ArrToXml($signParams);

        //发送请求
        $res = $this->sendRefundPostStr(self::REFUNDURl, $xmlData);

        return $this->XmlToArr($res);
    }
  
}
