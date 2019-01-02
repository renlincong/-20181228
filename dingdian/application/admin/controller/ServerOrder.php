<?php
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
header("Content-type: text/html; charset=utf-8");
//服务费订单管理
class ServerOrder extends Commen{

    /**
     * [server_order_list description]
     * @Author   Sxyang
     * @DateTime 2018-10-31T16:03:37+0800
     * @effect   服务费订单列表
     * @return   [type]                   [description]
     */
    public function server_order_list(){
        //取管理员所属地区
        $address_id = Session::get('address_id');
        $role_id = Session::get('role_id');
        if($address_id == 0){
            $Server_Order_Data = Db::query("select server_order_id,order_number,s.user_id,order_price,pay_price,pay_time,pay_type,order_type,is_invoice,refund_state,product_name,FROM_UNIXTIME(service_period_begin, '%Y-%m-%d') as service_period_begin,FROM_UNIXTIME(service_period_end, '%Y-%m-%d') as service_period_end,admin_id,FROM_UNIXTIME(order_create_time) as order_create_time,u.mechanism_name from server_order as s LEFT JOIN user as u on s.user_id = u.user_id where s.is_delete = 0 order by server_order_id desc");
 
            //机构类型
            $mechanism_type = Db('mechanism_type')->select();

            //统计金额
            $money['total'] = Db::name('server_order')->where('is_delete = 0')->sum('order_price');//订单总额
            $money['payment'] = Db::name('server_order')->where('is_delete = 0 and order_type = 1')->sum('pay_price');//支付总额
            $money['unpaid'] = Db::name('server_order')->where('is_delete = 0 and order_type = 0')->sum('order_price');//未付总额
            $money['invoice'] = Db::name('server_order')->where('is_delete = 0 and order_type = 1 and is_invoice = 0')->sum('pay_price');//发票总额
        } else {
            $Server_Order_Data = Db::query("select server_order_id,order_number,s.user_id,order_price,pay_price,pay_time,pay_type,order_type,is_invoice,refund_state,product_name,FROM_UNIXTIME(service_period_begin, '%Y-%m-%d') as service_period_begin,FROM_UNIXTIME(service_period_end, '%Y-%m-%d') as service_period_end,admin_id,FROM_UNIXTIME(order_create_time) as order_create_time,u.mechanism_name from server_order as s LEFT JOIN user as u on s.user_id = u.user_id where s.address_id = $address_id and s.is_delete = 0 order by server_order_id desc");
 
            //机构类型
            $mechanism_type = Db('mechanism_type')->select();
            //统计金额
            $money['total'] = Db::name('server_order')->where('is_delete = 0 and address_id = '.$address_id)->sum('order_price');//订单总额
            $money['payment'] = Db::name('server_order')->where('is_delete = 0 and order_type = 1 and address_id = '.$address_id)->sum('pay_price');//支付总额
            $money['unpaid'] = Db::name('server_order')->where('is_delete = 0 and order_type = 0 and address_id = '.$address_id)->sum('order_price');//未付总额
            $money['invoice'] = Db::name('server_order')->where('is_delete = 0 and order_type = 1 and is_invoice = 0 and address_id = '.$address_id)->sum('pay_price');//发票总额

        }
        foreach ($money as $k => $v) {
            if(strlen($v) > 4){
                $h = $v/10000;
                $money[$k] = round($h,2).'万';
            } else {
                $money[$k] = $v.'元';
            }
        }
        //将数据渲染到页面
        return view('server_order_list',['Server_Order_Data' => $Server_Order_Data, 'mechanism_type' => $mechanism_type, 'money' => $money, 'role_id'=>$role_id]);
    }

    /**
     * [server_order_where_page description]
     * @Author   Sxyang
     * @DateTime 2018-11-09T17:28:11+0800 
     * @effect   服务费订单列表筛选条件查询数据
     * @return   [type]                   [description]
     */
    public function server_order_where_page(){
        $search_data = input('get.');
        $address_id = Session::get('address_id');
        $role_id = Session::get('role_id');

        $where = " 1 = 1";
        //地区
        if($address_id == 0){
            if(!empty($search_data['address_id'])){
                $where .= " and s.address_id = ".$search_data['address_id'];
            }
        } else {
            $where .= " and s.address_id = ".$address_id;
        }
        
        //机构类型
        if(!empty($search_data['mechanism_type'])){
            $where .= " and u.mechanism_type = ".$search_data['mechanism_type'];
        }
        //机构名称
        if(!empty($search_data['mechanism_name'])){
            $where .= " and u.mechanism_name like '%".$search_data['mechanism_name']."%'";
        }
        //服务开始时间（以月份查询）
        if(!empty($search_data['search_begindate'])){
            $month_start = strtotime($search_data['search_begindate']);//指定月份月初时间戳  
            $month_end = mktime(23, 59, 59, date('m', strtotime($search_data['search_begindate']))+1, 00);//指定月份月末时间戳 
            $where .= " and service_period_begin >= ".$month_start." and service_period_begin <= ".$month_end;
        }

        //符合条件的数据
        $Server_Order_Data = Db::name('server_order')
                ->alias('s')
                ->join('user u','s.user_id = u.user_id','LEFT')
                ->where($where)
                ->column('server_order_id,order_number,s.user_id,order_price,pay_price,pay_time,pay_type,order_type,is_invoice,refund_state,product_name,FROM_UNIXTIME(service_period_begin, "%Y-%m-%d") as service_period_begin,FROM_UNIXTIME(service_period_end,"%Y-%m-%d") as service_period_end,admin_id,FROM_UNIXTIME(order_create_time) as order_create_time,u.mechanism_name');
        $Server_Order_Data = array_values($Server_Order_Data);//将下标从0开始

        //机构类型
        $mechanism_type = Db('mechanism_type')->select();

        //统计金额
        $money['total'] = Db::name('server_order')->alias('s')->join('user u','s.user_id = u.user_id','LEFT')->where($where)->sum('order_price');//订单总额
        $money['payment'] = Db::name('server_order')->alias('s')->join('user u','s.user_id = u.user_id','LEFT')->where($where.' and order_type = 1')->sum('pay_price');//支付总额
        $money['unpaid'] = Db::name('server_order')->alias('s')->join('user u','s.user_id = u.user_id','LEFT')->where($where.' and order_type = 0')->sum('order_price');//未付总额
        $money['invoice'] = Db::name('server_order')->alias('s')->join('user u','s.user_id = u.user_id','LEFT')->where($where.' and order_type = 1 and is_invoice = 0')->sum('pay_price');//发票总额
        foreach ($money as $k => $v) {
            if(strlen($v) > 4){
                $h = $v/10000;
                $money[$k] = round($h,2).'万';
            } else {
                $money[$k] = $v.'元';
            }
        }
        if(!empty($Server_Order_Data)){
            $data['state'] = 1;
            $data['content'] = $Server_Order_Data;
            $data['money'] = $money;
        } else {
            $data['state'] = 2;
        }
        //以json格式返回数据
        echo json_encode($data);
        exit;
    }

    /**
     * [server_order_add description]
     * @Author   Sxyang
     * @DateTime 2018-10-31T16:05:33+0800
     * @effect   渲染添加服务费订单页面
     * @return   [type]                   [description]
     */
    public function server_order_add(){
        return view('server_order_add');
    }

    /**
     * [server_order_add_do description]
     * @Author   Sxyang
     * @DateTime 2018-11-01T16:11:37+0800
     * @effect   服务费用数据添加入库
     * @return   [type]                   [description]
     */
    public function server_order_add_do(){
        $Server_Data = input('post.');//接收全部数据

        $Order_Data['order_number'] = 'a'.parent::produce_order_number();//订单编号
        $Order_Data['user_id'] = $Server_Data['user_id'];//机构ID
        $Order_Data['server_order_name'] = $Server_Data['server_order_name'];//订单名称
        $Order_Data['order_price'] = $Server_Data['order_price'];//订单金额
        $Order_Data['product_name'] = $Server_Data['product_name'];//产品名称
        $Order_Data['service_period_begin'] = strtotime($Server_Data['service_period_begin']);//服务开始时间
        $Order_Data['service_period_end'] = strtotime($Server_Data['service_period_end']);//服务结束时间
        $Order_Data['admin_id'] = Session::get('admin_id');//创建订单者ID
        $Order_Data['address_id'] = Session::get('address_id');//用户地址ID
        $Order_Data['order_create_time'] = time();//订单创建时间
        $add_State = Db('server_order')->insert($Order_Data);
        if($add_State){
            return $this->success('创建订单成功','ServerOrder/server_order_list','',1);
        } else {
            return $this->error('创建订单失败','ServerOrder/server_order_list','',1);
        }
    }

    /**
     * [server_update description]
     * @Author   Sxyang
     * @DateTime 2018-11-19T10:21:27+0800
     * @effect   服务费列表数据修改，渲染修改页面
     * @return   [type]                   [description]
     */
    public function server_order_update(){
        $server_id = input('get.server_id');
        $mechanism_name = input('get.mechanism_name');
        $server_data = Db('server_order')->where('server_order_id='.$server_id)->find();
        $server_data['mechanism_name'] = $mechanism_name;
        $server_data['server_id'] = $server_id;
        return view('server_order_update', ['server_data' =>$server_data]);
    }

    /**
     * [server_order_update_do description]
     * @Author   Rlc
     * @DateTime 2018-12-26T14:36:06+0800
     * @effect   服务费修改页面数据提交
     * @return   [type]                   [description]
     */
    public function server_order_update_do(){
        $server_id = input('post.server_id');
        $order_data['server_order_name'] = input('post.server_order_name');
        $order_data['order_price'] = input('post.order_price');
        $order_data['product_name'] = input('post.product_name');
        $order_data['service_period_begin'] = strtotime(input('post.service_period_begin'));
        $order_data['service_period_end'] = strtotime(input('post.service_period_end'));
        $order_update_state= Db::table('server_order')->where('server_order_id='.$server_id)->update($order_data);
        if($order_update_state){
            return $this->success('修改成功','ServerOrder/server_order_list','',1);
        } else {
            return $this->error('修改失败','ServerOrder/server_order_list','',1);
        }
    }

    /**
     * [input_information description]
     * @Author   Sxyang
     * @DateTime 2018-12-17T09:33:58+0800
     * @effect   渲染财务收账信息录入页面
     * @return   [type]                   [description]
     */
    public function server_input_information(){
        $server_order_id = input('get.server_order_id');
        $server_data = Db::query("select server_order_id,order_number,server_order_name,s.user_id,order_price,product_name,FROM_UNIXTIME(service_period_begin, '%Y-%m-%d') as service_period_begin,FROM_UNIXTIME(service_period_end, '%Y-%m-%d') as service_period_end,admin_id,u.mechanism_name,mechanism_code from server_order as s LEFT JOIN user as u on s.user_id = u.user_id where server_order_id = $server_order_id");
        return view('server_input_information',['server_data' => $server_data[0]]);
    }

    /**
     * [server_input_information description]
     * @Author   Sxyang
     * @DateTime 2018-12-17T13:17:42+0800
     * @effect   收账信息存入数据库
     * @return   [type]                   [description]
     */
    public function server_input_information_do(){
        $information_data = input('post.');
        $update = [
            'pay_price' => $information_data['pay_price'],
            'pay_time' => time(),
            'pay_type' =>3,
            'order_type' =>1
        ];

        $updata_state = Db::name('server_order')->where('server_order_id', $information_data['server_order_id'])->update($update);
        if($updata_state){
            return $this->success('信息提交成功','ServerOrder/server_order_list','',1);
        } else {
            return $this->error('创建订单失败','ServerOrder/server_order_list','',1);
        }
    }

    /**
     * [server_order_del description]
     * @Author   Rlc
     * @DateTime 2018-12-26T15:09:49+0800
     * @effect   订单删除操作
     * @return   [type]                   [description]
     */
    public function server_order_del(){
        $server_order_id = input('get.server_order_id');
        $res = Db::name('server_order')->where('server_order_id',$server_order_id)->setField('is_delete', 1);
        if ($res == 0) {
            exit(json_encode($res));
        } else {
            exit(json_encode($res));
        }
    }


}