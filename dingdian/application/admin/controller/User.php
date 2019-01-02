<?php
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
header("Content-type: text/html; charset=utf-8");
//用户（机构）管理
class User extends Commen{

	/**
	 * [user_list description]
	 * @Author   Sxyang
	 * @DateTime 2018-10-23T10:49:28+0800
	 * @effect   渲染用户列表页面
	 * @return   [type]                   [description] 
	 */
	public function user_list(){
		//取管理员所属地区
		$address_id = Session::get('address_id');
		if($address_id == 0){
			$user_Data = Db::query('select user_id,mechanism_name,mechanism_code,contacts_name,contact_information,mechanism_type_name from user LEFT JOIN mechanism_type on user.mechanism_type = mechanism_type.mechanism_type_id where is_delete = 0');
		} else {
			$user_Data = Db::query('select user_id,mechanism_name,mechanism_code,contacts_name,contact_information,mechanism_type_name from user LEFT JOIN mechanism_type on user.mechanism_type = mechanism_type.mechanism_type_id where is_delete = 0 and address_id = '.$address_id);
		}
		
		return view('user_list',['user_Data' => $user_Data]);
	}

	/**
	 * [user_Add description]
	 * @Author   Sxyang  
	 * @DateTime 2018-10-25T14:32:27+0800
	 * @effect   渲染添加用户页面
	 * @return   [type]                   [description]
	 */
	public function user_Add(){
		$mechanism_data = Db('mechanism_type')->where('mechanism_type_state = 1')->select();
		return view('user_Add', ['mechanism_data' => $mechanism_data]);
	}

	/**
	 * [user_Add_do description]
	 * @Author   Sxyang
	 * @DateTime 2018-10-29T15:53:41+0800
	 * @effect   添加用户数据入库
	 * @return   [type]                   [description]
	 */
	public function user_Add_do(){
		$user_Add_Data = input('post.');
		$user_Add_Data['user_number'] = $user_Add_Data['mechanism_code'];
		$user_Add_Data['user_pwd'] = md5($user_Add_Data['contact_information']);
		$user_Add_Data['create_time'] = time();

		//取管理员所属地区
		$user_Add_Data['address_id'] = Session::get('address_id');
		$add_state = Db('user')->insert($user_Add_Data);
    	if($add_state){
            return $this->success('添加成功','User/user_list','',1);
        } else {
            return $this->error('添加失败','User/user_list','',1);
        }
	}

	/**
	 * [user_update description]
	 * @Author   Sxyang
	 * @DateTime 2018-12-07T16:33:32+0800
	 * @effect   渲染用户（机构）修改页面
	 * @param    string                   $value [description]
	 * @return   [type]                          [description]
	 */
	public function user_update(){
		$user_id = input('get.user_id');
		//获取相对应的用户信息
		$User_Date = Db::query('select user_id,mechanism_name,mechanism_code,mechanism_type,contacts_name,contact_information,mechanism_address from user where user_id ='.$user_id);

		//获取机构类型
		$mechanism_data = Db('mechanism_type')->where('mechanism_type_state = 1')->select();
		return view('user_update', ['User_Date' => $User_Date[0], 'mechanism_data' => $mechanism_data]);
	}

	/**
	 * [user_update_do description]
	 * @Author   Sxyang
	 * @DateTime 2018-12-07T16:52:56+0800
	 * @effect   将用户的数据修改
	 * @return   [type]                   [description]
	 */
	public function user_update_do(){
		$user_datas = input('post.');
		$user_id = $user_datas['user_id'];
		unset($user_datas['user_id']);//将用户ID从数组中抛掉

		$user_update_state= Db::table('user')->where('user_id='.$user_id)->update($user_datas);
		if($user_update_state){
            return $this->success('修改成功','User/user_list','',1);
        } else {
            return $this->error('修改失败','User/user_list','',1);
        }
	}

	/**
	 * [user_del description]
	 * @Author   Rlc
	 * @DateTime 2018-12-26T11:01:21+0800
	 * @effect   讲用户的数据删除
	 * @return   [type]                   [description]
	 */
	public function user_del(){
		$user_id = input('get.user_id');
		$res = Db::name('user')->where('user_id',$user_id)->setField('is_delete', 1);
		if ($res == 0) {
			exit(json_encode($res));
		} else {
			exit(json_encode($res));
		}
	}
	
}