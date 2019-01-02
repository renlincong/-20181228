<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
header("Content-type: text/html; charset=utf-8");
class Invoice extends Controller
{
	/**
	 * [go_invoice description]
	 * @Author   Rlc
	 * @DateTime 2018-12-13T15:14:19+0800
	 * @effect   开发票
	 * @param    [type]                   $order_id [订单ID]
	 * @param    [type]                   $order_price  [订单总额]
	 * @param    [type]                   $order_type_id  [费用类别ID]
	 * @return   [type]                                 [description]
	 */
	public function go_invoice()
	{
		$order_id = input('post.order_id');  //订单ID
		$order_price = input('post.order_price');  //订单总额
		$order_form_type = input('post.order_form_type');  //订单类别ID
		// $order_id = 3;
		// $order_form_type = 1;
		// $order_price = 100001;
		// print_r($order_id);print_r($order_price);print_r($order_form_type);
		if ($order_form_type == 1) {  // 1为服务发票
			$invoice = Db::query("SELECT invoice_money FROM service_invoice WHERE order_id = $order_id");
		} else {  //软件发票
			$invoice = Db::query("SELECT invoice_money FROM software_invoice WHERE order_id = $order_id");
		}
		//没有发票返回订单总额，有发票判断是否超过订单总额，如果超过则显示余额不足，如果没有超过则显示剩余额度
		$rest = array_sum(array_column($invoice, 'invoice_money'));
		if ($rest >= $order_price) {
			$response['responsecode'] = "10004";
			$response['msg'] = "额度不足";
			$response['list'] = '0';
		} else {
			$response['responsecode'] = "10005";
			$response['msg'] = "余额";
			$response['list'] = $order_price-$rest;
		}
		echo json_encode($response);
		die;
	}

	/**
	 * [add_invoice description]
	 * @Author   Rlc
	 * @DateTime 2018-12-12T10:10:16+0800
	 * @effect   开发票
	 * @param    [type]                   $order_form_type     [订单类型（服务/软件）]
	 * @param    [type]                   $invoice_type        [发票类型（普票/专票）]
	 * @param    [type]                   $mechanism_name      [发票抬头]
	 * @param    [type]                   $duty_paragraph      [税号]
	 * @param    [type]                   $contract_amount     [合同金额]
	 * @param    [type]                   $address             [地址]
	 * @param    [type]                   $contact_information [电话]
	 * @param    [type]                   $open_bank           [购买方开户银行]
	 * @param    [type]                   $bank_number         [购买方开户银行账号]
	 */
	public function add_invoice()
	{
		$order_form_type = input('post.order_form_type');  //获取订单类型 （1 服务费订单； 2 软件费订单）
		// $order_form_type = 1;  //获取订单类型（1 服务费订单； 2 软件费订单）
		
		// if ($order_form_type == 1) {
		// 	$data = [
		// 		'order_id' => 1,  //订单ID
		// 		'invoice_type' => 1,  //发票类型（普票/专票）
		// 		'invoice_rise' => "嘉华大厦",  //发票抬头
		// 		'duty_paragraph' =>  "w72454452",  //税号
		// 		'invoice_money' => 541,  //合同金额
		// 		'mechanism_address' => "北京海淀嘉华大厦",  //地址
		// 		'contact_information' => 56434341,  //电话
		// 		'open_bank' => "北京海淀工商银行",  //购买方开户银行
		// 		'bank_number' => 8764687434343,  //购买方开户银行账号
		// 		'create_time' => time()  //创建时间
		// 	];
		// 	$invoiceId = Db::name('service_invoice')->insertGetId($data);
		// 	$status = Db::name('server_order')->where('server_order_id='.$data['order_id'])->update(['is_invoice' => 0]);
		// } else {
		// 	$data = [
		// 		'order_id' => 5,  //订单ID
		// 		'invoice_type' => 2,  //发票类型（普票/专票）
		// 		'invoice_rise' => "嘉华大厦111",  //发票抬头
		// 		'duty_paragraph' =>  "w72454452000",  //税号
		// 		'invoice_money' => 54155,  //合同金额
		// 		'mechanism_address' => "",  //地址
		// 		'contact_information' => "",  //电话
		// 		'open_bank' => "",  //购买方开户银行
		// 		'bank_number' => "",  //购买方开户银行账号
		// 		'create_time' => time()  //创建时间
		// 	];
		// 	$invoiceId = Db::name('software_invoice')->insertGetId($data);
		// 	$status = Db::name('software_order')->where('software_order_id='.$data['order_id'])->update(['is_invoice' => 0]);

		// }
		if ($order_form_type == 1) {
			$data = [
				'order_id' => intval(input('post.order_id')),  //订单ID
				'invoice_type' => intval(input('post.invoice_type')),  //发票类型（普票/专票）
				'invoice_rise' => input('post.invoice_rise'),  //发票抬头
				'duty_paragraph' =>  input('post.duty_paragraph'),  //税号
				'invoice_money' => floatval(input('post.invoice_money')),  //合同金额
				'mechanism_address' => input('post.mechanism_address'),  //地址
				'contact_information' => input('post.contact_information'),  //电话
				'open_bank' => input('post.open_bank'),  //购买方开户银行
				'bank_number' => input('post.bank_number'),  //购买方开户银行账号
				'create_time' => time()  //创建时间
			];
			$invoiceId = Db::name('service_invoice')->insertGetId($data);
			$status = Db::name('server_order')->where('server_order_id='.$data['order_id'])->update(['is_invoice' => 0]);
		} else {
			$data = [
				'order_id' => intval(input('post.order_id')),  //订单ID
				'invoice_type' => intval(input('post.invoice_type')),  //发票类型（普票/专票）
				'invoice_rise' => input('post.invoice_rise'),  //发票抬头
				'duty_paragraph' =>  input('post.duty_paragraph'),  //税号
				'invoice_money' => floatval(input('post.invoice_money')),  //合同金额
				'mechanism_address' => input('post.mechanism_address'),  //地址
				'contact_information' => input('post.contact_information'),  //电话
				'open_bank' => input('post.open_bank'),  //购买方开户银行
				'bank_number' => input('post.bank_number'),  //购买方开户银行账号
				'create_time' => time()  //创建时间
			];
			$invoiceId = Db::name('software_invoice')->insertGetId($data);
			$status = Db::name('software_order')->where('software_order_id='.$data['order_id'])->update(['is_invoice' => 0]);
		}
		$data = [
			'invoiceID' => $invoiceId,
			'order_form_type' => $order_form_type
		];
		if (empty($invoiceId)) {
			$response['responsecode'] = "10008";
			$response['msg'] = "开票失败";
			$response['list'] = '';
		} else {
			$response['responsecode'] = "10001";
			$response['msg'] = "成功";
			$response['list'] = $data;
		}
		echo json_encode($response);
		die;
	}

	/**
	 * [invoice description]
	 * @Author   Rlc
	 * @DateTime 2018-12-14T10:24:49+0800
	 * @effect   我的发票
	 * @param    [type]                   $user_id [用户ID]
	 * @return   [type]                            [description]
	 */
	public function invoice()
	{
		$user_id = input('post.user_id');  //用户ID
		//查询服务发票表里的所有发票
		$service_invoice = Db::query("SELECT server_order_id,service_invoice_id,invoice_rise,invoice_money,order_form_type,order_number,FROM_UNIXTIME(create_time, '%Y-%m-%d %H:%i:%s') as create_time,server_order_name,invoice_type,duty_paragraph FROM service_invoice LEFT JOIN server_order ON `service_invoice`.order_id = `server_order`.server_order_id WHERE is_invoice = 0 AND is_delete = 0 AND user_id =".$user_id." order by service_invoice_id desc");
		//查询软件发票表里的所有发票

		$software_invoice = Db::query("SELECT software_order_id,software_invoice_id,invoice_rise,invoice_money,order_form_type,order_number,FROM_UNIXTIME(create_time, '%Y-%m-%d %H:%i:%s') as create_time,software_order_name,invoice_type,duty_paragraph FROM software_invoice LEFT JOIN software_order ON `software_invoice`.order_id = `software_order`.software_order_id WHERE is_invoice = 0 AND is_delete = 0 AND user_id =".$user_id." order by software_invoice_id desc");
 		$order_data = array_merge($service_invoice, $software_invoice);

		if (empty($order_data)) {
			$response['responsecode'] = "10005";
			$response['msg'] = "暂无发票";
			$response['list'] = '';
		} else {
			$response['responsecode'] = "10001";
			$response['msg'] = "成功";
			$response['list'] = $order_data;
		}
		echo json_encode($response);
		die;
	}

	/**
	 * [invoice_info description]
	 * @Author   Rlc
	 * @DateTime 2018-12-18T17:02:55+0800
	 * @effect   发票详情
	 * @param    [type]                   $order_from_type [发票类型（1 服务；2 软件）]
	 * @param    [type]                   $invoice_id        [发票ID]
	 * @return   [type]                                    [description]
	 */
	public function invoice_info()
	{
		$order_form_type = input('post.order_form_type');
		$invoice_id = input('post.invoice_id');
		// $order_form_type = 2;
		// $invoice_id = 3;
		if ($order_form_type == 1) {
			$invoice_info = Db::query("SELECT invoice_type,invoice_rise,invoice_money,FROM_UNIXTIME(create_time, '%Y-%m-%d %H:%i:%s') as create_time,duty_paragraph,server_order_name FROM service_invoice AS a LEFT JOIN server_order AS b ON `a`.order_id = `b`.server_order_id WHERE service_invoice_id = $invoice_id");
		} else {
			$invoice_info = Db::query("SELECT invoice_type,invoice_rise,invoice_money,FROM_UNIXTIME(create_time, '%Y-%m-%d %H:%i:%s') as create_time,duty_paragraph,software_order_name FROM software_invoice AS a LEFT JOIN software_order AS b ON `a`.order_id = `b`.software_order_id WHERE software_invoice_id = $invoice_id");
		}

		if ($invoice_info) {
			$response['responsecode'] = "10001";
			$response['msg'] = "成功";
			$response['list'] = $invoice_info;
		} else {
			$response['responsecode'] = "10010";
			$response['msg'] = "查询失败";
			$response['list'] = "";
		}

		echo json_encode($response);
		die;
	}
}