<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Session;
header("Content-type: text/html; charset=utf-8");
class Index extends Controller
{
	/**
	 * [num description]
	 * @Author   Rlc
	 * @DateTime 2018-12-14T15:29:17+0800
	 * @effect   首页未付款显示的数字
	 * @param    [type]                   $user_id [用户ID]
	 * @return   [type]                            [description]
	 */
	public function num(){
		$user_id = input('post.user_id');
		$sum = 0;
		//服务未付条数
		$num = Db::query("SELECT count(server_order_id) as num FROM server_order WHERE order_type = 0 and user_id = ".$user_id);
		//软件未付条数
		$nums = Db::query("SELECT count(software_order_id) as num FROM software_order WHERE order_type = 0 and  user_id = ".$user_id);

		//区域联系人
		$address_id = Db::query('select address_id from user where user_id='.$user_id);

		$data['sum'] = $num[0]['num'] + $nums[0]['num'];
		$data['telephone'] = $address_id[0]['address_id'];
		$data['responsecode'] = "10007";
		$data['msg'] = "未付款订单";
		echo json_encode($data);
		die;
		
	}

	/**
	 * [money description]
	 * @Author   Rlc
	 * @DateTime 2018-12-11T14:20:28+0800
	 * @effect	 订单列表
	 * @param    [type]                   $user_id [用户ID]
	 * @param    [type]                   $type    [款项类型（0 未付款；1已付款；3其他；全部type为空）]
	 * @return   [type]                            [description]
	 */
	public function order_list()
	{
		$user_id = input('post.user_id');//用户ID
		// $user_id = 1;
		$type = input('post.type');//订单类型（0 未付款；1已付款；3其他；全部type为空）
		// $type = '100';
		// var_dump($user_id, $type);
		if ($type == "100") {  //全部
			$where = " user_id =$user_id";
		} else if ($type == 3 || $type == 0) {  //未付款或者其他
			$where = " user_id =$user_id AND order_type = $type";  
		} else if ($type == 1) {  //已付款
			$where = " user_id = $user_id AND order_type = $type OR order_type = '4'";
		}
		// print_r($where);die;
		$software_order_data = Db::query("SELECT software_order_name,software_order_id,order_number,order_type,order_price,order_form_type FROM software_order WHERE".$where);
		$server_order_data = Db::query("SELECT server_order_name,server_order_id,order_number,order_type,order_price,order_form_type FROM server_order WHERE".$where);
		// print_r($software_order_data);die;

		$order_data = array_merge($software_order_data, $server_order_data);
		// var_dump($order_data);die;
		if (empty($order_data)) {
			$response['responsecode'] = "10003";
			$response['msg'] = "暂无订单";
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
	 * [order description]
	 * @Author   Rlc
	 * @DateTime 2018-12-13T11:28:48+0800
	 * @effect   订单详情
	 * @param    [type]                   $order_form_type [订单类型ID（如果ID为1则为服务订单，如果ID为2则为软件订单）]
	 * @param    [type]                   $order_number [订单ID]
	 * @return   [type]                            [description]
	 */
	public function order_info()
	{
		$order_form_type = input('post.order_form_type');  //订单类型ID
		$order_id = input('post.order_id');  //订单ID
		// $order_form_type = 1;
		// $order_id = 3;
		if ($order_form_type == 1) {
			$order = Db::query("SELECT server_order_id,order_type,order_number,server_order_name,pay_type,FROM_UNIXTIME(pay_time, '%Y-%m-%d %H:%i:%s') as pay_time,order_price,order_form_type FROM server_order WHERE server_order_id = $order_id");
			// $a = Db::name('server_order')->getLastSql();
			// print_r($a);
		} else {
			$order = Db::query("SELECT software_order_id,order_type,order_number,software_order_name,pay_type,FROM_UNIXTIME(pay_time, '%Y-%m-%d %H:%i:%s') as pay_time,order_price,order_form_type FROM software_order WHERE software_order_id = $order_id");
		}
		foreach ($order as $key => $value) {
			$data = $value;
		}
		if (empty($data)) {
			$response['responsecode'] = "10002";
			$response['msg'] = "查询失败";
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
	 * [change_password description]
	 * @Author   Rlc
	 * @DateTime 2018-12-12T10:42:02+0800
	 * @effect   修改密码
	 * @param    [type]                   $user_id      [用户ID]
	 * @param    [type]                   $new_password [新密码]
	 * @return   [type]                                 [description]
	 */
	public function change_password()
	{
		$user_id = input('post.user_id');  //用户ID
		$password = md5(input('post.password'));  //旧密码
		$new_password = md5(input('post.new_password')); //新密码
		// $new_password = md5('123');
		// $user_id = 1;
		// $password = md5('123');
		$pwd = Db::table('user')->where('user_id',$user_id)->value('user_pwd');
		if ($pwd != $password) {
			$response['responsecode'] = '10011';
			$response['msg'] = '原密码不正确';
		} else{
			$result = Db::table('user')->where('user_id',$user_id)->update(['user_pwd' => $new_password]);
			if ($result) {
				$response['responsecode'] = '10001';
				$response['msg'] = '成功';
			}
		}

		echo json_encode($response);
		die;
	}

	/**
	 * [user_info description]
	 * @Author   Rlc
	 * @DateTime 2018-12-12T12:57:09+0800
	 * @effect   基本信息
	 * @param    [type]                   $user_id [用户ID]
	 * @return   [type]                            [description]
	 */
	public function user_info()
	{
		$user_id = input('post.user_id');  //用户ID
		// $user_id = 3;
		$user_info = Db::query("SELECT contacts_name,mechanism_address,contact_information,mechanism_name,mechanism_code FROM user WHERE user_id = $user_id");
		if (empty($user_info)) {
			$response['responsecode'] = '10002';
			$response['msg'] = '查询失败';
			$response['list'] = '';
		} else {
			$response['responsecode'] = '10001';
			$response['msg'] = '成功';
			$response['list'] = $user_info;
		}
		echo json_encode($response);
		die;
	}

	/**
	 * [technical_support description]
	 * @Author   Rlc
	 * @DateTime 2018-12-13T09:37:34+0800
	 * @effect   技术支持
	 * @param    [type]                   $user_id [用户ID]
	 * @param    [type]                   $content [问题和建议]
	 * @param    [type]                   $phone   [手机号]
	 * @return   [type]                            [description]
	 */
	public function technical_support()
	{
		// $data = [
		// 	'user_id' => 1,
		// 	'content' => "各位范围分为非",
		// 	'phone' => 64313,
		// 	'technical_time' => time()
		// ];
		$data = [
			'user_id' => input('post.user_id'),
			'content' => input('post.content'),
			'phone' => input('post.phone'),
			'technical_time' => time()
		];
		$technicalId = Db::name('technical_support')->insertGetId($data);
		if (empty($technicalId)) {
			$response['responsecode'] = "10009";
			$response['msg'] = "提交失败";
			$response['list'] = '';
		} else {
			$response['responsecode'] = "10001";
			$response['msg'] = "成功";
			$response['list'] = $technicalId;
		}
		echo json_encode($response);
		die;
	}


}