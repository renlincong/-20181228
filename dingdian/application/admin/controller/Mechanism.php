<?php
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
header("Content-type: text/html; charset=utf-8");
//机构类型管理
class Mechanism extends Commen{

	/**
	 * [mechanism_list description]
	 * @Author   Sxyang
	 * @DateTime 2018-10-29T14:05:58+0800
	 * @effect   机构类型管理列表
	 * @return   [type]                   [description]
	 */
	public function mechanism_list(){
		$mechanism_data = Db('mechanism_type')->select();
		return view('mechanism_list', ['mechanism_data' => $mechanism_data]);
	}

	/**
	 * [update_admin_state description]
	 * @Author   Sxyang
	 * @DateTime 2018-10-29T14:06:50+0800
	 * @effect   机构类型状态修改
	 * @return   [type]                   [description]
	 */
	public function update_mechanism_state(){
        $state_mechanism = input('post.');
        $info_state = Db('mechanism_type')->where('mechanism_type_id='.$state_mechanism['mechanism_type_id'])->update(['mechanism_type_state' => $state_mechanism['mechanism_type_state']]);
        if($info_state){
            $data['state'] = 1;
        } else {
            $data['state'] = 2;
        }
        echo json_encode($data);
        exit;
    }

    /**
     * [mechanism_add description]
     * @Author   Sxyang
     * @DateTime 2018-10-29T14:11:44+0800
     * @effect   渲染添加机构类型页面
     * @return   [type]                   [description]
     */
    public function mechanism_add(){
    	return view('mechanism_add');
    }

    /**
     * [mechanism_add_do description]
     * @Author   Sxyang
     * @DateTime 2018-11-01T09:40:55+0800
     * @effect   机构类型添加数据至数据库
     * @return   [type]                   [description]
     */
    public function mechanism_add_do(){
    	$mechanism_data = input('post.');
    	$add_state = Db('mechanism_type')->insert($mechanism_data);
    	if($add_state){
            return $this->success('添加成功','Mechanism/mechanism_list','',1);
        } else {
            return $this->error('添加失败','Mechanism/mechanism_list','',1);
        }
    }

    /**
     * [mechanism_del description]
     * @Author   Rlc
     * @DateTime 2018-12-26T13:47:05+0800
     * @effect   机构类型删除
     * @return   [type]                   [description]
     */
    public function mechanism_del(){
        $mechanism_type_id = input('get.mechanism_type_id');
        $res = Db::name('mechanism_type')->delete($mechanism_type_id);
        if ($res == 0) {
            exit(json_encode($res));
        } else {
            exit(json_encode($res));
        }
    }

}