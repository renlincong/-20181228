<?php
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
header("Content-type: text/html; charset=utf-8");
//软件费订单管理
class SoftwareOrder extends Commen{

	/**
	 * [software_order_list description]
	 * @Author   Sxyang
	 * @DateTime 2018-11-01T10:55:58+0800
	 * @effect   软件费订单列表
	 * @return   [type]                   [description]
	 */
	public function software_order_list(){
		//取管理员所属地区
        $address_id = Session::get('address_id');
        $role_id = Session::get('role_id');
        if($address_id == 0){
            //查询数据
            $Software_Order_Data = Db::query("select software_order_id,order_number,s.user_id,order_price,pay_price,pay_time,pay_type,order_type,is_invoice,refund_state,product_name,FROM_UNIXTIME(service_period_begin, '%Y-%m-%d') as service_period_begin,FROM_UNIXTIME(service_period_end, '%Y-%m-%d') as service_period_end,admin_id,FROM_UNIXTIME(order_create_time) as order_create_time,u.mechanism_name from software_order as s LEFT JOIN user as u on s.user_id = u.user_id where s.is_delete = 0 order by software_order_id desc");
 
            //机构类型
            $mechanism_type = Db('mechanism_type')->select();

            //统计金额
            $money['total'] = Db::name('software_order')->where('is_delete = 0')->sum('order_price');//订单总额
            $money['payment'] = Db::name('software_order')->where('is_delete = 0 and order_type = 1')->sum('pay_price');//支付总额
            $money['unpaid'] = Db::name('software_order')->where('is_delete = 0 and order_type = 0')->sum('order_price');//未付总额
            $money['invoice'] = Db::name('software_order')->where('is_delete = 0 and order_type = 1 and is_invoice = 0')->sum('pay_price');//发票总额
        } else {
            //查询数据
            $Software_Order_Data = Db::query("select software_order_id,order_number,s.user_id,order_price,pay_price,pay_time,pay_type,order_type,is_invoice,refund_state,product_name,FROM_UNIXTIME(service_period_begin, '%Y-%m-%d') as service_period_begin,FROM_UNIXTIME(service_period_end, '%Y-%m-%d') as service_period_end,admin_id,FROM_UNIXTIME(order_create_time) as order_create_time,u.mechanism_name from software_order as s LEFT JOIN user as u on s.user_id = u.user_id where s.address_id = $address_id and s.is_delete = 0 order by software_order_id desc");
 
            //机构类型
            $mechanism_type = Db('mechanism_type')->select();

            //统计金额
            $money['total'] = Db::name('software_order')->where('is_delete = 0 and address_id = '.$address_id)->sum('order_price');//订单总额
            $money['payment'] = Db::name('software_order')->where('is_delete = 0 and order_type = 1 and address_id = '.$address_id)->sum('pay_price');//支付总额
            $money['unpaid'] = Db::name('software_order')->where('is_delete = 0 and order_type = 0 and address_id = '.$address_id)->sum('order_price');//未付总额
            $money['invoice'] = Db::name('software_order')->where('is_delete = 0 and order_type = 1 and is_invoice = 0 and address_id = '.$address_id)->sum('pay_price');//发票总额
        }
        foreach ($money as $k => $v) {
            if(strlen($v) > 4){
                $h = $v/10000;
                $money[$k] = round($h,2).'万';
            } else {
                $money[$k] = $v.'元';
            }
        }
		return view('software_order_list',['Software_Order_Data' => $Software_Order_Data, 'mechanism_type' => $mechanism_type, 'money' => $money, 'role_id'=>$role_id]);
	}

	/**
     * [server_order_where_page description]
     * @Author   Sxyang
     * @DateTime 2018-11-09T17:28:11+0800
     * @effect   服务费订单列表筛选条件查询数据
     * @return   [type]                   [description]
     */
    public function software_order_where_page(){
        $search_data = input('get.');
        $address_id = Session::get('address_id');

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
        $Server_Order_Data = Db::name('software_order')
                ->alias('s')
                ->join('user u','s.user_id = u.user_id','LEFT')
                ->where($where)
                ->column('software_order_id,order_number,s.user_id,order_price,pay_price,pay_time,pay_type,order_type,is_invoice,refund_state,product_name,FROM_UNIXTIME(service_period_begin, "%Y-%m-%d") as service_period_begin,FROM_UNIXTIME(service_period_end,"%Y-%m-%d") as service_period_end,admin_id,FROM_UNIXTIME(order_create_time) as order_create_time,u.mechanism_name');
        $Server_Order_Data = array_values($Server_Order_Data);//将下标从0开始

        //机构类型
        $mechanism_type = Db('mechanism_type')->select();

        //统计金额
        $money['total'] = Db::name('software_order')->alias('s')->join('user u','s.user_id = u.user_id','LEFT')->where($where)->sum('order_price');//订单总额
        $money['payment'] = Db::name('software_order')->alias('s')->join('user u','s.user_id = u.user_id','LEFT')->where($where.' and order_type = 1')->sum('pay_price');//支付总额
        $money['unpaid'] = Db::name('software_order')->alias('s')->join('user u','s.user_id = u.user_id','LEFT')->where($where.' and order_type = 0')->sum('order_price');//未付总额
        $money['invoice'] = Db::name('software_order')->alias('s')->join('user u','s.user_id = u.user_id','LEFT')->where($where.' and order_type = 1 and is_invoice = 0')->sum('pay_price');//发票总额
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
	 * [software_order_add description]
	 * @Author   Sxyang
	 * @DateTime 2018-11-01T10:56:36+0800
	 * @effect   渲染软件费订单添加页面
	 * @param    string                   $value [description]
	 * @return   [type]                          [description]
	 */
	public function software_order_add(){
		return view('software_order_add');
	}
	
	/**
	 * [server_order_add_do description]
	 * @Author   Sxyang
	 * @DateTime 2018-11-14T09:54:30+0800
	 * @effect   软件费添加入库
	 * @return   [type]                   [description]
	 */
	public function server_order_add_do(){
        $Server_Data = input('post.');//接收全部数据

        $Order_Data['order_number'] = 'b'.parent::produce_order_number();//订单编号
        $Order_Data['user_id'] = $Server_Data['user_id'];//机构ID
        $Order_Data['software_order_name'] = $Server_Data['software_order_name'];//订单名称
        $Order_Data['order_price'] = $Server_Data['order_price'];//订单金额
        $Order_Data['product_name'] = $Server_Data['product_name'];//产品名称
        $Order_Data['service_period_begin'] = strtotime($Server_Data['service_period_begin']);//服务开始时间
        $Order_Data['service_period_end'] = strtotime($Server_Data['service_period_end']);//服务结束时间
        $Order_Data['admin_id'] = Session::get('admin_id');//创建订单者ID
        $Order_Data['address_id'] = Session::get('address_id');//用户地址ID
        $Order_Data['software_label'] = $Server_Data['software_label'];//订单标签
        $Order_Data['cards_number'] = $Server_Data['cards_number'];//加密卡数量
        $Order_Data['order_create_time'] = time();//订单创建时间

        $add_State = Db('software_order')->insert($Order_Data);
        if($add_State){
            return $this->success('创建订单成功','SoftwareOrder/software_order_list','',1);
        } else {
            return $this->error('创建订单失败','SoftwareOrder/software_order_list','',1);
        }
    }

    /**
     * [input_information description]
     * @Author   Sxyang
     * @DateTime 2018-12-17T09:33:58+0800
     * @effect   渲染财务收账信息录入页面
     * @return   [type]                   [description]
     */
    public function software_input_information(){
        $software_order_id = input('get.software_order_id');
        $software_data = Db::query("select software_order_id,software_order_name,order_number,s.user_id,order_price,product_name,FROM_UNIXTIME(service_period_begin, '%Y-%m-%d') as service_period_begin,FROM_UNIXTIME(service_period_end, '%Y-%m-%d') as service_period_end,admin_id,u.mechanism_name,mechanism_code from software_order as s LEFT JOIN user as u on s.user_id = u.user_id where software_order_id = $software_order_id");
        return view('software_input_information',['software_data' => $software_data[0]]);
    }

    /**
     * [server_input_information description]
     * @Author   Sxyang
     * @DateTime 2018-12-17T13:17:42+0800
     * @effect   收账信息存入数据库
     * @return   [type]                   [description]
     */
    public function software_input_information_do(){
        $information_data = input('post.');
        $update = [
            'pay_price' => $information_data['pay_price'],
            'pay_time' => time(),
            'pay_type' =>3,
            'order_type' =>1
        ];

        $updata_state = Db::name('server_order')->where('software_order_id', $information_data['software_order_id'])->update($update);
        if($updata_state){
            return $this->success('信息提交成功','SoftwareOrder/software_order_list','',1);
        } else {
            return $this->error('创建订单失败','SoftwareOrder/software_order_list','',1);
        }
    }

    /**
     * [import description]
     * @Author   Rlc
     * @DateTime 2018-12-19T15:45:28+0800
     * @effect   Excel导入
     * @return   [type]                   [description]
     */
    public function import(){
        if(request()->isPost()) {

            Loader::import('PHPExcel.PHPExcel');
            Loader::import('PHPExcel.PHPExcel.PHPExcel_IOFactory');
            Loader::import('PHPExcel.PHPExcel.PHPExcel_Cell');
            //实例化PHPExcel
            $objPHPExcel = new \PHPExcel();
            $file = request()->file('excel');
            if ($file) {

                $file_types = explode(".", $_FILES ['excel'] ['name']); // ["name"] => string(25) "excel文件名.xls"
                $file_type = $file_types [count($file_types) - 1];//xls后缀
                $file_name = $file_types [count($file_types) - 2];//xls去后缀的文件名
                /*判别是不是.xls文件，判别是不是excel文件*/
                if (strtolower($file_type) != "xls" && strtolower($file_type) != "xlsx") {
                    echo '不是Excel文件，重新上传';
                    die;
                }
                $info = $file->move(ROOT_PATH . 'public' . DS . 'excel');//上传位置
                $path = ROOT_PATH . 'public' . DS . 'excel' . DS;
                $file_path = $path . $info->getSaveName();//上传后的EXCEL路径
                //echo $file_path;//文件路径

                //获取上传的excel表格的数据，形成数组
                $re = $this->actionRead($file_path, 'utf-8');
                array_splice($re, 1, 0);
                unset($re[0]);
                //dump($re); //查看数组
                
                //获取到机构编码查询用户ID
                $mechanism_code = $re[1][3];
                $user_id = Db::table('user')->where('mechanism_code',$mechanism_code)->value('user_id');
                
                $data['encryption_card_number'] = $re[1][0];  //加密卡卡号
                $data['serial_number'] = $re[1][1];  //写卡序列号
                $data['user_id'] = $user_id;  //用户ID
                $data['application_date'] = strtotime($re[1][5]);  //申请日期
                $data['area_coding'] = $re[1][6];  //地区编码
                $data['term_validity'] = strtotime($re[1][8]);  //有效期
                $encryption_card_id = Db::name('encryption_card')->insertGetId($data);
                
                foreach ($re as $key => $value) {
                    $arr[$key]['social_code'] = $value[7];
                    $arr[$key]['registration_code'] = $value[9];
                    $arr[$key]['encryption_card_id'] = $encryption_card_id;

                }

                $result = Db::table('registration_code')->insertAll($arr);
            }
        }
        return $this->success('导入成功','SoftwareOrder/software_order_list','', 1);
        // return view('software_order_list');
    }

    /**
     * [actionRead description]
     * @Author   Rlc
     * @DateTime 2018-12-20T16:58:59+0800
     * @effect
     * @param    [type]                   $filename [description]
     * @param    string                   $encode   [description]
     * @return   [type]                             [description]
     */
    public function actionRead($filename, $encode = 'utf-8')
    {
        // $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objReader = PHPExcel_IOFactory::createReader('Excel5');
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($filename, $encode = 'utf-8');
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $highestRow = $objWorksheet->getHighestRow();
         $highestColumn = $objWorksheet->getHighestColumn();
         $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
         $excelData = array();
         for($row = 1; $row <= $highestRow; $row++)
         {
         for ($col = 0; $col < $highestColumnIndex; $col++)
         {
         $excelData[$row][]=(string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
         }
         }
         return $excelData;
    }

    /**
     * [software_order_del description]
     * @Author   Rlc
     * @DateTime 2018-12-26T15:21:56+0800
     * @effect   软件订单删除
     * @return   [type]                   [description]
     */
    public function software_order_del(){
        $software_order_id = input('get.software_order_id');
        $res = Db::name('software_order')->where('software_order_id',$software_order_id)->setField('is_delete', 1);
        if ($res == 0) {
            exit(json_encode($res));
        } else {
            exit(json_encode($res));
        }
    }

    /**
     * [software_order_update description]
     * @Author   Rlc
     * @DateTime 2018-12-26T15:41:58+0800
     * @effect   软件订单修改
     * @return   [type]                   [description]
     */
    public function software_order_update(){
        $software_order_id = input('get.software_order_id');
        $mechanism_name = input('get.mechanism_name');
        $software_data = Db('software_order')->where('software_order_id='.$software_order_id)->find();
        $software_data['mechanism_name'] = $mechanism_name;
        $software_data['server_id'] = $software_order_id;
        return view('software_order_update', ['software_data' =>$software_data]);
    }

    /**
     * [software_order_update_do description]
     * @Author   Rlc
     * @DateTime 2018-12-26T16:22:12+0800
     * @effect   软件订单修改页接值
     * @return   [type]                   [description]
     */
    public function software_order_update_do(){
        $software_order_id = input('post.software_order_id');
        $order_data['software_order_name'] = input('post.software_order_name');
        $order_data['order_price'] = input('post.order_price');
        $order_data['product_name'] = input('post.product_name');
        $order_data['service_period_begin'] = strtotime(input('post.service_period_begin'));
        $order_data['service_period_end'] = strtotime(input('post.service_period_end'));
        $order_data['software_label'] = input('post.software_label');
        $order_data['cards_number'] = input('post.cards_number');
        $order_update_state= Db::table('software_order')->where('software_order_id='.$software_order_id)->update($order_data);
        if($order_update_state){
            return $this->success('修改成功','SoftwareOrder/software_order_list','',1);
        } else {
            return $this->error('修改失败','SoftwareOrder/software_order_list','',1);
        }
    }

    /**
     * [software_order_install description]
     * @Author   Rlc
     * @DateTime 2018-12-27T14:42:32+0800
     * @effect   软件订单安装
     * @return   [type]                   [description]
     */
    public function software_order_install(){
        $software_order_id = input('get.software_order_id');
        $res = Db::name('software_order')->where('software_order_id',$software_order_id)->setField('order_type', 5);
        if ($res == 0) {
            exit(json_encode($res));
        } else {
            exit(json_encode($res));
        }
    }
}
