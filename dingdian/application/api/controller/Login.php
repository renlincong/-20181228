<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
header("Content-type: text/html; charset=utf-8");
class Login extends Controller
{
	/**
	 * [login description]
	 * @Author   Rlc
	 * @DateTime 2018-12-11T12:46:03+0800
	 * @effect   登录
	 * @param    [type]                   $user_number [用户账号（手机号/机构编码）]
	 * @param    [type]                   $user_pwd    [用户密码]
	 * @return   [type]                                [description]
	 */
	public function login()
	{
		$user_number = input('post.user_number');//用户账号
		$user_pwd = input('post.user_pwd');//用户密码
		// $user_number = 100002;
		// $user_pwd = 123123;
		$user_number_lenth = strlen($user_number);
		$user_pwd_new = md5($user_pwd);
		if ($user_number_lenth == 6) {
			$where = " user_number = $user_number AND user_pwd = '$user_pwd_new'";
		} else {
			$where = " contact_information = $user_number AND user_pwd = '$user_pwd_new'";	
		}
		$user_data = Db::query("SELECT user_id,mechanism_code,mechanism_name,mechanism_type_name from user RIGHT JOIN mechanism_type ON `user`.mechanism_type = `mechanism_type`.mechanism_type_id WHERE".$where);
		
		if (empty($user_data)) {
			$response['responsecode'] = "10002";
			$response['msg'] = "登录失败";
			$response['list'] = '';
		} else {
			$response['responsecode'] = "10001";
			$response['msg'] = "登录成功";
			$response['list'] = $user_data;
		}
		echo json_encode($response);
		die;
	}
}