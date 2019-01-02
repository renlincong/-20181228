<?php
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
header("Content-type: text/html; charset=utf-8");
class Commen extends Controller{
    /**
     * [__construct description]
     * @Author   Sxyang
     * @DateTime 2018-11-23T14:34:26+0800
     * @effect   检测登录，权限等
     */
    public function __construct(){
        $admin_id = session::get('admin_id');
        if(empty($admin_id)){
            return $this->error('请您先登录...', 'Login/admin_login', '', 2);
        }

    }

	/**
     * [code_get_user description]
     * @Author   Sxyang
     * @DateTime 2018-11-01T15:43:24+0800
     * @effect   根据机构编码获取机构信息
     * @return   [type]                   [description]
     */
    public function code_get_user(){
        $address_id = Session::get('address_id');
        $mechanism_code = input('post.mechanism_code');//机构编号
        $user_data = Db::query("select user_id,mechanism_name from user where mechanism_code = $mechanism_code and address_id = $address_id");

        if(!empty($user_data)){
            $data['state'] = 1;
            $data['content'] = $user_data;
        } else {
            $data['state'] = 2;
        }
        echo json_encode($data);
        exit;
    }

    /**
     * @Author   Sxyang
     * @DateTime 2018-11-15T09:36:33+0800
     * @effect   利用admin_id获取到用户城市ID
     * @param    [type]                   $admin_id [管理员ID]
     * @return   [type]                             [description]
     */
    public function get_user_address_id($admin_id){
    	$where = ['admin_id' => $admin_id];
    	$address_id = Db::name('admin')->where($where)->value('address_id');
    	return $address_id;
    }

	/**
     * [produce_order_number description]
     * @Author   Sxyang
     * @DateTime 2018-11-01T16:46:16+0800
     * @effect   产生订单号
     * @return   [type]                   [description]
     */
    function produce_order_number(){
        $order_date = date('Y-m-d');
        //订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
        $order_id_main = date('YmdHis') . rand(10000000,99999999);
        //订单号码主体长度
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for($i=0; $i<$order_id_len; $i++){
            $order_id_sum += (int)(substr($order_id_main,$i,1));
        }
        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
        $order_id = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100,2,'0',STR_PAD_LEFT);
        $order_id = substr($order_id, 2);
        return $order_id;
    }

    /**
     * [generate_Purchase_order description]
     * @Author   Sxyang
     * @DateTime 2018-12-13T10:19:17+0800
     * @effect   生成入库单编号（13位）
     * @return   [type]                   [description]
     */
    function generate_Purchase_order(){
        $Purchase_order = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        return $Purchase_order;
    }
}