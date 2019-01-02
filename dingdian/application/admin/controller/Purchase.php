<?php
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
header("Content-type: text/html; charset=utf-8");
//采购管理
class Purchase extends Commen{

	/**
	 * [Purchase_list description]
	 * @Author   Sxyang
	 * @DateTime 2018-12-11T10:16:27+0800
	 * @effect   采购订单列表
	 */
	public function purchase_list(){
		$purchase_list_data = Db::query('select purchase_id,purchase_contract_number,purchase_address,purchase_number,purchase_price,purchase_sum_price,purchase_state,purchase_pay_time,invoice_number from card_stock_purchase');
		return view('purchase_list',['purchase_list_data' => $purchase_list_data]);
	}

	/**
	 * [purchase_add description]
	 * @Author   Sxyang
	 * @DateTime 2018-12-11T10:19:06+0800
	 * @effect   渲染采购申请添加表
	 * @return   [type]                   [description]
	 */
	public function purchase_add(){
		return view('purchase_add');
	}

	/**
	 * [purchase_add_do description]
	 * @Author   Sxyang
	 * @DateTime 2018-12-11T14:17:28+0800
	 * @effect   采购申请数据入库
	 * @return   [type]                   [description]
	 */
	public function purchase_add_do(){
		$purchase_data = input('post.');
		$purchase_data['admin_id'] = Session::get('admin_id'); //创建人
		$purchase_data['purchase_state'] = 1; //订单状态
		$purchase_data['create_time'] = time();//创建时间

		$insert_data = Db::name('card_stock_purchase')->insert($purchase_data); //向数据库中添加数据

		if(!empty($insert_data)){
    		return $this->success('添加成功','Purchase/purchase_list','',1);
        } else {
            return $this->error('添加失败','Purchase/purchase_list','',1);
        }

	}

	/**
	 * [encryption_card_stock description]
	 * @Author   Sxyang
	 * @DateTime 2018-12-11T10:20:38+0800
	 * @effect   地区加密卡库存列表展示
	 * @return   [type]                   [description]
	 */
	public function encryption_card_stock(){
		$card_stock_data = Db::name('card_stock')->select();
		return view('encryption_card_stock',['card_stock_data' => $card_stock_data]);
	}

	/**
	 * [financial_payment description]
	 * @Author   Sxyang
	 * @DateTime 2018-12-11T16:07:34+0800
	 * @effect   渲染财务付款写入发票号码页面
	 * @return   [type]                   [description]
	 */
	public function financial_payment(){
		//查询出采购订单不为已完成的订单
		$purchase_list_data = Db::query('select purchase_id,purchase_contract_number,purchase_address,admin_id,purchase_number,purchase_price,purchase_sum_price,purchase_state,invoice_number from card_stock_purchase where purchase_state != 3');

		return view('financial_payment',['purchase_list_data' => $purchase_list_data]);
	}

	/**
	 * [financial_payment_pay description]
	 * @Author   Sxyang
	 * @DateTime 2018-12-11T16:46:50+0800
	 * @effect   财务付款
	 * @return   [type]                   [description]
	 */
	public function financial_payment_pay(){
		$update['purchase_state'] = input('post.purchase_state');//采购订单状态
		$purchase_id = input('post.purchase_id');//采购订单iD
		$purchase_number = input('post.purchase_number');//采购数量
		$purchase_address = input('post.purchase_address');//采购库房
		$update['purchase_pay_time'] = time(); //付款时间

		$pay_update_state= Db::table('card_stock_purchase')->where('purchase_id='.$purchase_id)->update($update);

		if($pay_update_state){
			//产生入库单
			$Warehouse_receipt['warehousing_number'] = $this->generate_Purchase_order();//入库单编号（程序产生）
			$Warehouse_receipt['purchase_id'] = $purchase_id;//采购订单id
			$Warehouse_receipt['card_stock_id'] = $purchase_address;//所入库房
			$Warehouse_receipt['warehousing_quota'] = $purchase_number;//入库数量
			$Warehouse_receipt['warehousing_time'] = time();//入库时间
			Db::name('card_stock_warehousing')->insert($Warehouse_receipt);
			Db::name('card_stock')->where('card_stock_address ='.$purchase_address)->setInc('card_stock_number', $purchase_number);
			$data['state'] = 1;//成功
		} else {
			$data['state'] = 2;//失败
		}
		echo json_encode($data);
		exit;
	}

	/**
	 * [financial_payment_invoice description]
	 * @Author   Sxyang
	 * @DateTime 2018-12-11T16:55:04+0800
	 * @effect   财务写入发票号码
	 * @return   [type]                   [description]
	 */
	public function financial_payment_invoice(){
		$update['purchase_state'] = 3;//采购订单状态
		$update['invoice_number'] = input('post.invoice_number');//采购订单发票号码
		$purchase_id = input('post.purchase_id');//采购订单iD

		$pay_update_state= Db::table('card_stock_purchase')->where('purchase_id='.$purchase_id)->update($update);

		if($pay_update_state){
			$data['state'] = 1;//成功
		} else {
			$data['state'] = 2;//失败
		}
		echo json_encode($data);
		exit;
	}

}