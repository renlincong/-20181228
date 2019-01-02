<?php
namespace app\admin\controller;
use think\Db;
use think\Session;
header("Content-type: text/html; charset=utf-8");
class Index extends Commen{

	/**
	 * 首页内容
	 */
    public function index(){
        $admin_id = session::get('admin_id');
        //判断如果是超级管理员 展示所有操作列表
        if ($admin_id != 1) {
            $jurisdiction_data = Db::query("select jurisdiction_id,jurisdiction_name,CONCAT(jurisdiction_controller,'/',jurisdiction_action)as jurisdiction_controller,parent_id from jurisdiction where jurisdiction_id in (select jurisdiction_id from admin LEFT JOIN role_jurisdiction on admin.role_id = role_jurisdiction.role_id where admin_id = $admin_id) and is_left_show = 1");
        } else {
            $jurisdiction_data = Db::query("select jurisdiction_id,jurisdiction_name,CONCAT(jurisdiction_controller,'/',jurisdiction_action)as jurisdiction_controller,parent_id from jurisdiction where is_left_show = 1");
        }
        foreach ($jurisdiction_data as $key => $value) {
            if($value['parent_id'] == 0){
                $new_jurisdiction_data[$value['jurisdiction_id']] = $value;
            } else {
                $new_jurisdiction_data[$value['parent_id']]['son'][] = $value;
            }
        }
        return view('index',['new_jurisdiction_data' => $new_jurisdiction_data]);
    }
    
    /**
     * 右侧内容
     */
    public function index_info(){
        return view('index_info');
    }

}