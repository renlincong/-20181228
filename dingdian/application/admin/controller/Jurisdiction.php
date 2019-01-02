<?php
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Db;
header("Content-type: text/html; charset=utf-8");
//管理员，角色，权限管理控制器
class Jurisdiction extends Commen{

    //管理员模块程序代码
    /**
     * @Author   Sxyang
     * @DateTime 2018-10-09T11:02:34+0800
     * @effect   管理员列表
     * @return   [type]
     */
    public function admin_list(){
        $admin_data = Db('admin')->select();
        $role_data = Db('role')->select();
        return view('admin_list',['admin_data' => $admin_data, 'role_data' => $role_data]);
    }

    /**
     * @Author   Sxyang
     * @DateTime 2018-10-09T14:09:17+0800
     * @effect   渲染添加管理员页面
     */
    public function add_admin(){
        $role_list_data = Db('role')->select();
        return view('add_admin',['role_list_data' => $role_list_data]);
    }

    /**
     * @Author   Sxyang
     * @DateTime 2018-10-09T14:32:40+0800
     * @effect   将管理员数据添加入库
     */
    public function add_admin_do(){
        $admin_data = input('post.');//接收数据
        unset($admin_data['userpassword']);//删除多余密码
        $admin_data['admin_pwd'] = md5($admin_data['admin_pwd']);//md5加密用户密码
        $admin_data['registration_time'] = time();

        $add_state = Db('admin')->insert($admin_data);//添加数据入库
        if($add_state){
            return $this->success('添加成功','Jurisdiction/admin_list','',1);
        } else {
            return $this->error('添加失败','Jurisdiction/add_admin','',1);
        }
    }

    /**
     * [admin_update description]
     * @Author   Sxyang
     * @DateTime 2018-10-15T14:39:12+0800
     * @effect   渲染管理员修改页面
     * @return   [type]                   [description]
     */
    public function admin_update(){
        $admin_id = input('get.admin_id');
        $admin_data = Db::query('select admin_id,admin_name,address_id,role_id from admin where admin_id=:admin_id',['admin_id' => $admin_id]);
        $role_list_data = Db('role')->select();
        return view('admin_update',['admin_data' => $admin_data,'role_list_data' => $role_list_data]);
    }

    /**
     * [admin_update_do description]
     * @Author   Sxyang
     * @DateTime 2018-10-15T14:52:36+0800
     * @effect   管理员修改数据入库
     * @return   [type]                   [description]
     */
    public function admin_update_do(){
        $admin_update_data = input('post.');
        $update = [
                'admin_name' => $admin_update_data['admin_name'],
                'role_name' => $admin_update_data['role_name'],
                'role_id' => $admin_update_data['role_id'],
                'address_id' => $admin_update_data['address_id']
               ];
        $admin_update_state = Db::table('admin')->where('admin_id='.$admin_update_data['admin_id'])->update($update);
        if($admin_update_state){
            return $this->success('修改成功','Jurisdiction/admin_list','',1);
        } else {
            return $this->error('修改失败','Jurisdiction/admin_list','',1);
        }
    }

    /**
     * [update_admin_state description]
     * @Author   Sxyang
     * @DateTime 2018-10-19T09:07:22+0800
     * @effect   管理员状态修改
     * @return   [type]                   [description]
     */
    public function update_admin_state(){
        $state_information = input('post.');
        $info_state = Db('admin')->where('admin_id='.$state_information['admin_id'])->update(['admin_state' => $state_information['admin_state']]);
        if($info_state){
            $data['state'] = 1;
        } else {
            $data['state'] = 2;
        }
        echo json_encode($data);
        exit;
    }

    /**
     * [admin_del description]
     * @Author   Sxyang
     * @DateTime 2018-10-19T10:19:51+0800
     * @effect   管理员删除
     * @return   [type]                   [description]
     */
    public function admin_del(){
        $admin_id = input('post.admin_id');
        $admin_del_State = Db('admin')->where('admin_id', $admin_id)->delete();
        if($admin_del_State){
            $data['state'] = 1;
        } else {
            $data['state'] = 2;
        }
        echo json_encode($data);
        exit;
    }

    //角色模块程序代码
    /**
     * @Author   Sxyang
     * @DateTime 2018-10-09T11:02:14+0800
     * @effect   角色列表
     */
    public function role_list(){
        $role_data = Db('role')->select();

        foreach ($role_data as $key => $v) {
            $a = Db::query("select GROUP_CONCAT(admin_name) as admin_name from admin where role_id =".$v['role_id']);
            $role_data[$key]['son'] = $a[0]['admin_name'];
        }
        return view('role_list',['role_data' => $role_data]);
    }

    /**
     * [role_add description]
     * @Author   Sxyang
     * @DateTime 2018-10-15T13:33:13+0800
     * @effect   添加角色页面渲染
     * @return   [type]                   [description]
     */
    public function role_add(){
        $jurisdiction_data = Db::query("SELECT jurisdiction_id,jurisdiction_name,parent_id from jurisdiction");
        //处理权限数据
        foreach ($jurisdiction_data as $key => $value) {
            if($value['parent_id'] == 0){
                $new_jurisdiction_data[$value['jurisdiction_id']] = $value;
            } else {
                $new_jurisdiction_data[$value['parent_id']]['son'][] = $value;
            }
        }
        return view('role_add',['new_jurisdiction_data' => $new_jurisdiction_data]);
    }

    /**
     * [role_add_do description]
     * @Author   Sxyang
     * @DateTime 2018-10-15T13:38:09+0800
     * @effect   添加角色数据入库
     * @return   [type]                   [description]
     */
    public function role_add_do(){
        $role_data = input('post.');
        $role_name = array('role_name' => $role_data['role_name']);
        $role_id = Db('role')->insertGetId($role_name);
        if($role_id){
            foreach ($role_data['jurisdiction_id'] as $key => $value) {
                $data[$key]['jurisdiction_id'] = $value;
                $data[$key]['role_id'] = $role_id;
            }
            Db('role_jurisdiction')->insertAll($data);
            return $this->success('添加成功','Jurisdiction/role_list','',1);
        } else {
            return $this->error('添加失败','Jurisdiction/role_add','',1);
        }
    }

    //权限模块程序代码
    /**
     * @Author   Sxyang
     * @DateTime 2018-10-09T11:01:43+0800
     * @effect   权限列表
     */
    public function jurisdiction_list(){
        $jurisdiction_data_list = Db('jurisdiction')->select();
        foreach ($jurisdiction_data_list as $key => $value) {
            if($value['parent_id'] == 0){
                $new_Data[] = $value;
                foreach ($jurisdiction_data_list as $k => $v) {
                    if($value['jurisdiction_id'] == $v['parent_id']){
                        $new_Data[] = $v;
                    }
                }
            }
        }
        return view('jurisdiction_list',['jurisdiction_data_list' => $new_Data]);
    }

    /**
     * [jurisdiction_add description]
     * @Author   Sxyang
     * @DateTime 2018-10-15T11:19:56+0800
     * @effect   权限添加页面渲染
     * @return   [type]                   [description]
     */
    public function jurisdiction_add(){
        $jurisdiction_parent = Db('jurisdiction')->field('jurisdiction_id,jurisdiction_name')->where('parent_id = 0')->select();
        return view('jurisdiction_add',['jurisdiction_parent' => $jurisdiction_parent]);
    }

    /**
     * [jurisdiction_add_do description]
     * @Author   Sxyang
     * @DateTime 2018-10-15T13:11:31+0800
     * @effect   将权限数据添加入库
     * @return   [type]                   [description]
     */
    public function jurisdiction_add_do(){
        $jurisdiction_data = input('post.');
        $add_state = Db('jurisdiction')->insert($jurisdiction_data);
        if($add_state){
            return $this->success('添加成功','Jurisdiction/jurisdiction_list','',1);
        } else {
            return $this->error('添加失败','Jurisdiction/jurisdiction_add','',1);
        }
    }


}
