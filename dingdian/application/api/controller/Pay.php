<?php
namespace app\api\controller;
use think\Controller;
use think\Request;
use think\Db;
header("Content-type: text/html; charset=utf-8");
class Pay extends Controller {

	protected function _initialize(){
		
		// if($_SERVER['REQUEST_METHOD'] != 'POST'){
		// 	self::return_err('error request method');
		// }
		
		//微信支付参数配置(appid,商户号,支付秘钥)
		$config = array(
			'appid'		 => 'wx6ac59ce0901d4894',
			'pay_mchid'	 => '1521392971',
			'pay_apikey' => 'Beijingdiweisaibogongsi201812191',
			'appsecret'  => 'd5ebfa88b60b2628798d7dcf1e193ba9',
		);
		$this->config = $config;
	}
	
	/**
     * 统一下单请求接口(POST)
     * @param string $code 	    code码
     * @param string $body 		商品简单描述
     * @param string $order_sn  订单编号
     * @param string $total_fee 金额
     * @return  json的数据
     */
	public function prepay(){
		$config = $this->config;
		$code = input('post.code');
		if (empty($code)) {
			echo json_encode($error(['error' => "10011"]));
			exit;
		}
		
		//http函数为封装的请求函数
        $openid = $this->getcurl($code);

		$body = input('post.body');  //商品简单描述
		$order_sn = input('post.order_sn');  //订单编号
		$total_fee = input('post.total_fee');  //金额
		// $order_id = input('post.order_id');  //订单ID
		
		//统一下单参数构造
		$unifiedorder = array(
			'appid'			=> $config['appid'],  //微信分配的小程序ID
			'mch_id'		=> $config['pay_mchid'],  //微信支付分配的商户号
			'nonce_str'		=> self::getNonceStr(), //随机字符串，长度要求在32位以内。
			'body'			=> $body,  //商品简单描述
			'out_trade_no'	=> $order_sn,  //商户系统内部订单号，要求32个字符内，只能是数字、大小写字母_-|*且在同一个商户号下唯一。
			'total_fee'		=> $total_fee * 100,  //订单总金额，单位为分
			'spbill_create_ip'	=> $_SERVER['REMOTE_ADDR'],  //APP和H5支付提交用户端ip，Native支付填调用微信支付API的机器IP
			'notify_url'    => $_SERVER['HTTP_HOST'].'/api/pay/notify',  //异步接收微信支付结果通知的回调地址，通知url必须为外网可访问的url，不能携带参数。
			'trade_type'	=> 'JSAPI',  //小程序取值如下：JSAPI
			'openid'		=> $openid  //trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。
		);
		// echo json_encode($unifiedorder);die;
		 // echo json_encode($unifiedorder);die;
		$unifiedorder['sign'] = self::makeSign($unifiedorder);
		//请求数据
		$xmldata = self::array2xml($unifiedorder);
		$url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $res = self::curl_post_ssl($url, $xmldata);
        if(!$res){
			self::return_err("Can't connect the server");
        }
		// 这句file_put_contents是用来查看服务器返回的结果 测试完可以删除了
		// file_put_contents(APP_ROOT.'/Statics/log1.txt',$res,FILE_APPEND);
		$content = self::xml2array($res);
		
		if(strval($content['result_code']) == 'FAIL'){
			self::return_err(strval($content['err_code_des']));
        }
		if(strval($content['return_code']) == 'FAIL'){
			self::return_err(strval($content['return_msg']));
        }
		$prepay_id = $content['prepay_id'];
		$data = array(
			'appId'     => 'wx6ac59ce0901d4894',
			'timeStamp'	=> strval(time()),
			'nonceStr'	=> self::getNonceStr(),
			'package'	=> 'prepay_id='.$prepay_id,
			'signType'	=> 'MD5'
		);
		$data['paySign'] = $this->makeSign($data);
		// $data['order_id'] = $order_id;
		self::return_data($data);
	}

	   	 //作用：生成签名
   	private function getSign($Obj){
   	 		foreach ($Obj as $k => $v){
   	    		 $Parameters[$k] = $v;
   	   		}
   	     	//签名步骤一：按字典序排序参数
   	        ksort($Parameters);
   	        $String = $this->formatBizQueryParaMap($Parameters, false);
   	        //签名步骤二：在string后加入KEY 
   	        $String = $String."&key=".$this->key; 
   	        //签名步骤三：MD5加密 
   	        $String = md5($String);  
   	        //签名步骤四：所有字符转为大写  
   	        $result_ = strtoupper($String);
   	        return $result_; 
   	}  
   	///作用：格式化参数，签名过程需要使用 
   	 private function formatBizQueryParaMap($paraMap, $urlencode){ 
   	 		$buff = ""; 
   	   		ksort($paraMap); 
   	        foreach ($paraMap as $k => $v){ 
   	      		if($urlencode)    {   
   	        		$v = urlencode($v); 
   	         	}  
   	           $buff .= $k . "=" . $v . "&"; 
   	        } 
   	        $reqPar;
   	            if (strlen($buff) > 0){  
   	                $reqPar = substr($buff, 0, strlen($buff)-1);  
   	            } 

   	    return $reqPar; 
   	} 
	
	//微信支付回调验证
	public function notify(){
		//如果PHP版本为7，那么应使用下面第二个，因为PHP7已经移除 $GLOBALS['HTTP_RAW_POST_DATA'] 中的这个属性
		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
		// $xml = file_get_contents('php://input');

		// $xml="<xml>
		//    <appid><![CDATA[wx4424d1b95e43c823]]></appid>
		//    <attach><![CDATA[支付测试]]></attach>
		//    <bank_type><![CDATA[CFT]]></bank_type>
		//    <fee_type><![CDATA[CNY]]></fee_type>
		//    <is_subscribe><![CDATA[Y]]></is_subscribe>
		//    <mch_id><![CDATA[1521392971]]></mch_id>
		//    <nonce_str><![CDATA[5d2b6c2a8db53831f7eda20af46e531c]]></nonce_str>
		//    <openid><![CDATA[oYUe45a4EZuWGW43zyVTs_5uV8po]]></openid>
		//    <out_trade_no><![CDATA[a34343124]]></out_trade_no>
		//    <result_code><![CDATA[SUCCESS]]></result_code>
		//    <return_code><![CDATA[SUCCESS]]></return_code>
		//    <sign><![CDATA[9A9D25D01C1E7284861D3F23BA7C67ED]]></sign>
		//    <sub_mch_id><![CDATA[1521392971]]></sub_mch_id>
		//    <time_end><![CDATA[20140903131540]]></time_end>
		//    <total_fee>0.1</total_fee>
		// <coupon_fee><![CDATA[10]]></coupon_fee>
		// <coupon_count><![CDATA[1]]></coupon_count>
		// <coupon_type><![CDATA[CASH]]></coupon_type>
		// <coupon_id><![CDATA[10000]]></coupon_id>
		// <coupon_fee><![CDATA[100]]></coupon_fee>
		//    <trade_type><![CDATA[JSAPI]]></trade_type>
		//    <transaction_id><![CDATA[1004400740201409030005092168]]></transaction_id>
		// </xml> 
		// ";

		//将服务器返回的XML数据转化为数组
		$data = self::xml2array($xml);

		// 保存微信服务器返回的签名sign
		$data_sign = $data['sign'];
		// print_r($data_sign);
		// sign不参与签名算法
		unset($data['sign']);
		$sign = self::makeSign($data);
		// print_r($sign);die;
		// 判断签名是否正确  判断支付状态
		if ( ($sign===$data_sign) && ($data['return_code']=='SUCCESS') && ($data['result_code']=='SUCCESS') ) {
			$result = $data;
			//获取服务器返回的数据
			$order_sn = strval($data['out_trade_no']);  //订单编号
			// $transaction_id = $data['transaction_id']; 	//微信支付流水号
			$server = [
				'pay_price' => $data['total_fee']/100,	//付款金额,
				'order_type' => 1,            //订单状态
				'pay_type' => 2,              //支付方式
				'pay_time' => time(),         //支付时间
				'openid' => $data['openid']    //付款人openID
			];
			// $where = array('order_number' => strval($data['out_trade_no']));
			//更新数据库
			if (substr($order_sn,0, 1) == 'a') {
				//服务订单表
				$server = Db::name('server_order')->where("order_number='$order_sn'")->update($server);
			} else {
				//软件订单表
				$server = Db::name('software_order')->where("order_number='$order_sn'")->update($server);
			}	
		}else{
			$result = false;
		}
		// 返回状态给微信服务器
		if ($result) {
			$str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
		}else{
			$str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
		}
		echo $str;
		return $result;
	}
	
//---------------------------------------------------------------用到的函数------------------------------------------------------------
	/**
     * 错误返回提示
     * @param string $errMsg 错误信息
     * @param string $status 错误码
     * @return  json的数据
     */
	protected function return_err($errMsg='error',$status=10012){
		exit(json_encode(array('status'=>$status,'result'=>'fail','errmsg'=>$errMsg)));
	}
	
	
	/**
     * 正确返回
     * @param 	array $data 要返回的数组
     * @return  json的数据
     */
	protected function return_data($data=array()){
		exit(json_encode(array('status'=>10013,'result'=>'success','data'=>$data)));
	}
  
	/**
     * 将一个数组转换为 XML 结构的字符串
     * @param array $arr 要转换的数组
     * @param int $level 节点层级, 1 为 Root.
     * @return string XML 结构的字符串
     */
    protected function array2xml($arr, $level = 1) {
        $s = $level == 1 ? "<xml>" : '';
        foreach($arr as $tagname => $value) {
            if (is_numeric($tagname)) {
                $tagname = $value['TagName'];
                unset($value['TagName']);
            }
            if(!is_array($value)) {
                $s .= "<{$tagname}>".(!is_numeric($value) ? '<![CDATA[' : '').$value.(!is_numeric($value) ? ']]>' : '')."</{$tagname}>";
            } else {
                $s .= "<{$tagname}>" . $this->array2xml($value, $level + 1)."</{$tagname}>";
            }
        }
        $s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
        return $level == 1 ? $s."</xml>" : $s;
    }
	
	/**
	 * 将xml转为array
	 * @param  string 	$xml xml字符串
	 * @return array    转换得到的数组
	 */
	protected function xml2array($xml){
		//禁止引用外部xml实体
		libxml_disable_entity_loader(true);
		$result= json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
		return $result;
	}
	
	/**
	 * 
	 * 产生随机字符串，不长于32位
	 * @param int $length
	 * @return 产生的随机字符串
	 */
	protected function getNonceStr($length = 32) {
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		} 
		return $str;
	}
	
	/**
	* 生成签名
	* @return 签名
	*/
	protected function makeSign($data){
		//获取微信支付秘钥
		$key = $this->config['pay_apikey'];
		// 去空
		$data=array_filter($data);
		//签名步骤一：按字典序排序参数
		ksort($data);
		$string_a=http_build_query($data);
		$string_a=urldecode($string_a);
		//签名步骤二：在string后加入KEY
		//$config=$this->config;
		$string_sign_temp=$string_a."&key=".$key;
		//签名步骤三：MD5加密
		$sign = md5($string_sign_temp);
		// 签名步骤四：所有字符转为大写
		$result=strtoupper($sign);
		return $result;
	}
	
	/**
	 * 微信支付发起请求
	 */
	protected function curl_post_ssl($url, $xmldata, $second=30,$aHeader=array()){
		$ch = curl_init();
		//超时时间
		curl_setopt($ch,CURLOPT_TIMEOUT,$second);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		//这里设置代理，如果有的话
		//curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
		//curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		
	 
		if( count($aHeader) >= 1 ){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
		}
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$xmldata);
		$data = curl_exec($ch);
		if($data){
			curl_close($ch);
			return $data;
		}
		else { 
			$error = curl_errno($ch);
			echo "call faild, errorCode:$error\n"; 
			curl_close($ch);
			return false;
		}
	}

	/**
	 * 发送HTTP请求方法 (获取用户的openid)
	 * @param  string $url    请求URL
	 * @param  array  $params 请求参数
	 * @param  string $method 请求方法GET/POST
	 * @return array  $data   响应数据
	 */
	function getcurl($code){
		$url = "https://api.weixin.qq.com/sns/jscode2session?appid=wx6ac59ce0901d4894&secret=d5ebfa88b60b2628798d7dcf1e193ba9&js_code=".$code."&grant_type=authorization_code";
		$ch = curl_init();
	    //设置超时
	    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
	    curl_setopt($ch, CURLOPT_HEADER, FALSE);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	  
	  //   //运行curl，结果以jason形式返回
	    $res = curl_exec($ch);
	   curl_close($ch);
	  //   //取出openid
	    $data = json_decode($res,true);
	    $openid = $data['openid'];
	    return $openid;
	}

}


