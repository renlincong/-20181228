<?php
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
header("Content-type: text/html; charset=utf-8");
//后台登录管理控制器
class Login extends Controller{

	/**
	 * [admin_login description]
	 * @Author   Sxyang
	 * @DateTime 2018-11-22T13:57:44+0800
	 * @effect   渲染登录页面
	 * @return   [type]                   [description]
	 */
	public function admin_login(){
		return view('admin_login');
	}

	/**
	 * [admin_login_do description]
	 * @Author   Sxyang
	 * @DateTime 2018-11-23T13:57:55+0800
	 * @effect   接收管理登录的数据，进行验证
	 * @return   [type]                   [description]
	 */
	public function admin_login_do(){
		$Login_Data = input('post.');//接收数据

		$admin_pwd = md5($Login_Data['admin_pwd']);//密码进行加密与数据库中作比较
		$Login_Where = array(
				'admin_name' => $Login_Data['admin_name'],
				'admin_pwd' => $admin_pwd
			);

		$Admin_Data = Db::name('admin')->where($Login_Where)->find();//根据账号密码查询

		if(!empty($Admin_Data)){
			//检查账户状态是否被锁定
			if($Admin_Data['admin_state'] == 2){
				return $this->error('您的账户已停用，请联系开发人员...','Login/admin_login','',2);
			}
			//向session中存入管理员ID.所属地区，名称
			Session::set('admin_id', $Admin_Data['admin_id']);
			Session::set('role_id', $Admin_Data['role_id']);
			Session::set('address_id', $Admin_Data['address_id']);
			Session::set('admin_name', $Admin_Data['admin_name']);
			Session::set('role_name', $Admin_Data['role_name']);

			$Login_info['last_login_time'] = time();

			Db::name('admin')->where("admin_id =".$Admin_Data['admin_id'])->update($Login_info);//修改管理员登录的时间

			return $this->success('登录成功...','Index/index','',1);
        } else {
            return $this->error('登录失败，请检查登录账号密码...','Login/admin_login','',2);
        }

	}

	/**
	 * [sign_out description]
	 * @Author   Sxyang
	 * @DateTime 2018-11-23T13:55:47+0800
	 * @effect   管理员退出，清除session
	 * @return   [type]                   [description]
	 */
	public function sign_out(){
		session(null);
		$data['msg'] = '1';
		echo json_encode($data);
		exit;
	}

}