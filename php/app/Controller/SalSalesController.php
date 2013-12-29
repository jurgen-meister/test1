<?php
App::uses('AppController', 'Controller');
/**
 * SalSales Controller
 *
 * @property SalSale $SalSale
 */
class SalSalesController extends AppController {

/**
 *  Layout
 *
 * @var string
 */
	public $layout = 'default';

/**
 * Helpers
 *
 * @var array
 */
	//public $helpers = array('TwitterBootstrap.BootstrapHtml', 'TwitterBootstrap.BootstrapForm', 'TwitterBootstrap.BootstrapPaginator');
/**
 * Components
 *
 * @var array
 */
	//public $components = array('Session')
	//*******************************************************************************************************//
	///////////////////////////////////////// START - FUNCTIONS ///////////////////////////////////////////////
	//*******************************************************************************************************//
	
	//////////////////////////////////////////// START - PDF ///////////////////////////////////////////////
	public function view_document_movement_pdf($id = null) {
		
		$this->InvMovement->id = $id;
		
		if (!$this->InvMovement->exists()) {
			throw new NotFoundException(__('Invalid post'));
		}
		// increase memory limit in PHP 
		ini_set('memory_limit', '512M');
		$movement = $this->InvMovement->read(null, $id);
		
		if($movement['InvMovement']['inv_movement_type_id'] == 4){
			$this->redirect(array('action'=>'index_warehouses_transfer'));
		}
		
		if($movement['InvMovement']['inv_movement_type_id'] == 3){
			
			$movementIdOut = $this->InvMovement->find('all', array(
				'conditions'=>array(
					'InvMovement.document_code'=>$movement['InvMovement']['document_code'],
					'InvMovement.inv_movement_type_id ='=>4
			)));//Out Origin
			$movement['Transfer']['code'] = $movementIdOut[0]['InvMovement']['code'];
			$movement['Transfer']['warehouseName'] = $movementIdOut[0]['InvWarehouse']['name'];
		}
		
		
		$details=$this->_get_movements_details_without_stock($id);
		$this->set('movement', $movement);
		$this->set('details', $details);
	}
	//////////////////////////////////////////// END - PDF /////////////////////////////////////////////////
	
	//////////////////////////////////////////// START - REPORT ////////////////////////////////////////////////
	public function vreport_generator(){
		$this->loadModel('AdmUser');
		$salesmanClean = $this->AdmUser->AdmProfile->find('list', array(
			'order'=>array('first_name'),
			'fields'=>array('adm_user_id','full_name')
			)); 
		$salesman = $salesmanClean;
		$salesman[0] = "TODOS";
		$salesmanWO0 = $salesmanClean;
		
		$customerClean = $this->SalSale->SalEmployee->SalCustomer->find('list');
		$customer = $customerClean;
		$customer[0] = "TODOS";
		$customerWO0 = $customerClean;
		
		$this->loadModel("InvWarehouse");
		$warehouseClean = $this->InvWarehouse->find('list');
		$warehouse = $warehouseClean;
		$warehouse[0] = "TODOS";
		$item = $this->_find_items();
		$this->set(compact("warehouse", "item", "salesman", "customer", "customerWO0", "salesmanWO0"));
	}
	
	private function _find_items($type = 'none', $selected = array(), $items = ""){
		$conditions = array();
		$order = array('InvItem.code');
		$conditionsTypes = array();
		
		switch ($type){
			case 'category':
				$conditionsTypes = array('InvItem.inv_category_id'=>$selected);
				//$order = array('InvCategory.name');
				break;
			case 'brand':
				$conditionsTypes = array('InvItem.inv_brand_id'=>$selected);
				//$order = array('InvBrand.name');
				break;
		}
		
		if($items <> ""){
			$conditions = array_merge($conditionsTypes, array("InvItem.id"=>$items));
		}else{
			$conditions = $conditionsTypes;
		}
		
		$this->loadModel("InvItem");
		$this->InvItem->unbindModel(array('hasMany' => array('InvPrice', 'InvCategory', 'InvMovementDetail', 'InvItemsSupplier')));
		return $this->InvItem->find("all", array(
					"fields"=>array('InvItem.code', 'InvItem.name', 'InvCategory.name', 'InvBrand.name', 'InvItem.id'),
					"conditions"=>$conditions,
					"order"=>$order
				));
	}
	

	
	public function ajax_get_group_items_and_filters(){
		if($this->RequestHandler->isAjax()){
			$type = $this->request->data['type'];
			$group = array();
			switch ($type) {
				case 'category':
					$this->loadModel("InvCategory");
					$group = $this->InvCategory->find("list", array("order"=>array("InvCategory.name")));
					$this->set('group', $group);
					break;
				case 'brand':
					$this->loadModel("InvBrand");
					$group = $this->InvBrand->find("list", array("order"=>array("InvBrand.name")));
					$this->set('group', $group);
					break;
			}
//			$item = $this->_find_items($type, array_keys($group));
			$item = $this->_find_items($type, array_keys(array()));
			$this->set(compact("item"));
		}
	}
	
	public function ajax_get_group_items(){
		if($this->RequestHandler->isAjax()){
			$type = $this->request->data['type'];
			if(isset($this->request->data['selected'])){
				$selected = $this->request->data['selected'];
			}else{
				$selected = array(); 
			}
			$item = $this->_find_items($type, $selected);
			$this->set(compact("item"));
		}
	}

	
	public function ajax_generate_report(){
		if($this->RequestHandler->isAjax()){
			//SETTING DATA
			$this->Session->write('ReportMovement.startDate', $this->request->data['startDate']);
			$this->Session->write('ReportMovement.finishDate', $this->request->data['finishDate']);
			$this->Session->write('ReportMovement.showByType', $this->request->data['showByType']);
			$this->Session->write('ReportMovement.showByTypeName', $this->request->data['showByTypeName']);
			$this->Session->write('ReportMovement.warehouse', $this->request->data['warehouse']);
			$this->Session->write('ReportMovement.warehouseName', $this->request->data['warehouseName']);
			$this->Session->write('ReportMovement.customer', $this->request->data['customer']);
			$this->Session->write('ReportMovement.customerName', $this->request->data['customerName']);
			$this->Session->write('ReportMovement.customerWO0', $this->request->data['customerWO0']);
			$this->Session->write('ReportMovement.customerNameWO0', $this->request->data['customerNameWO0']);
			$this->Session->write('ReportMovement.salesman', $this->request->data['salesman']);
			$this->Session->write('ReportMovement.salesmanName', $this->request->data['salesmanName']);
			$this->Session->write('ReportMovement.salesmanWO0', $this->request->data['salesmanWO0']);
			$this->Session->write('ReportMovement.salesmanNameWO0', $this->request->data['salesmanNameWO0']);
			$this->Session->write('ReportMovement.currency', $this->request->data['currency']);
			$this->Session->write('ReportMovement.detail', $this->request->data['detail']);
			//for transfer
//			$this->Session->write('ReportMovement.warehouse2', $this->request->data['warehouse2']);
//			$this->Session->write('ReportMovement.warehouseName2', $this->request->data['warehouseName2']);
			//array items
			$this->Session->write('ReportMovement.items', $this->request->data['items']);
			
			//to send data response to ajax success so it can choose the report view
			echo $this->request->data['showByType']; 
		///END AJAX
		}
	}
	
	public function vreport_ins_or_outs(){
		$this->_generate_report();
	}
	
	public function vreport_ins_and_outs(){
		$this->_generate_report();
	}
	
	public function vreport_transfers(){
		$this->_generate_report(); 
	}
	
	private function _generate_report(){
		//special ctp template for printing due DOMPdf colapses generating too many pages
		$this->layout = 'print';
		
		//Check if session variables are set otherwise redirect
		if(!$this->Session->check('ReportMovement')){
			$this->redirect(array('action' => 'vreport_generator'));
		}
		
		//put session data sent data into variables
		$initialData = $this->Session->read('ReportMovement');
		
//		debug($initialData);
		
		$settings = $this->_generate_report_settings($initialData);
		
//		debug($settings);
		
		$movements=$this->_generate_report_movements($settings['values'], $settings['conditions'], $settings['fields']);
//		debug($movements);
		
		$currencyFieldPrefix = '';
		$currencyAbbreviation = '(BS)';
		if(trim($initialData['currency']) == 'DOLARES'){
			$currencyFieldPrefix = 'ex_';
			$currencyAbbreviation = '($US)';
		}
		
		
		
		if($initialData['showByType'] == 1000){
			$clientsComplete = $this->_generate_report_clients_complete($initialData['customerWO0']);
//			debug($clientsComplete);
		} elseif ($initialData['showByType'] == 998) {
			$salesmenComplete = $this->_generate_report_salesmen_complete($initialData['salesmanWO0']);
//			debug($salesmenComplete);
		}else {
			$itemsComplete = $this->_generate_report_items_complete($initialData['items']);
//		debug($itemsComplete);
		}
		
		
		
		if($initialData['showByType'] == 1000){
			$clientsMovements = $this->_generate_report_clients_movements($clientsComplete, $movements, $currencyFieldPrefix);
//			debug($clientsMovements);
		}   elseif ($initialData['showByType'] == 998) {
			$salesmenMovements = $this->_generate_report_salesmen_movements($salesmenComplete, $movements, $currencyFieldPrefix);
//			debug($salesmenMovements);
		}else {
			$itemsMovements = $this->_generate_report_items_movements($itemsComplete, $movements, $currencyFieldPrefix);
//		debug($itemsMovements);
		}
		
		
		$initialData['currencyAbbreviation']=$currencyAbbreviation;//setting currency abbreviation before send
		$initialData['items']='';//cleaning items ids 'cause won't be needed begore send
		//debug($initialData);
		$this->set('initialData', $initialData);
		
		if($initialData['showByType'] == 1000){
			$this->set('clientsMovements', $clientsMovements);
		}  elseif ($initialData['showByType'] == 998) {
			$this->set('salesmenMovements', $salesmenMovements);
		} else {
			$this->set('itemsMovements', $itemsMovements);
		}
		//debug($settings['initialStocks']);
		$this->set('initialStocks', $settings['initialStocks']);
		$this->Session->delete('ReportMovement');
	//END FUNCTION	
	}
	
	
	
	private function _generate_report_items_movements($itemsComplete, $movements, $currencyFieldPrefix){
		//I'll not calculate totals 'cause will be easier in the view and specially cleaner due the variation of calculation in every report
		$auxArray=array();
		foreach($itemsComplete as $item){
			$fobQuantityTotal = 0;
			$cifQuantityTotal = 0;
			$saleQuantityTotal = 0;
			$counter = 0;
			
			$forPricesSubQuery = 0; //before 'InvMovementDetail'
//			debug($item);
			//movements
			foreach($movements as $movement){
				if($item['InvItem']['id'] == $movement['SalDetail']['inv_item_id']){
					$fobQuantity = $movement['SalDetail']['quantity'] * $movement[$forPricesSubQuery][$currencyFieldPrefix.'fob_price'];
					$cifQuantity = $movement['SalDetail']['quantity'] * $movement[$forPricesSubQuery][$currencyFieldPrefix.'cif_price'];
					$saleQuantity = $movement['SalDetail']['quantity'] * $movement['SalDetail'][$currencyFieldPrefix.'sale_price']/*[$forPricesSubQuery][$currencyFieldPrefix.'sale_price']*/;
					$fobQuantityTotal = $fobQuantityTotal + $fobQuantity;
					$cifQuantityTotal = $cifQuantityTotal + $cifQuantity;
					$saleQuantityTotal = $saleQuantityTotal + $saleQuantity;
					$auxArray[$item['InvItem']['id']]['Movements'][$counter] = array(
						'code'=>$movement['SalSale']['code'],
						'doc_code'=>$movement['SalSale']['doc_code'],
						'note_code'=>$movement/*[$forPricesSubQuery]*/['SalSale']['note_code'],
						'customer'=>$movement[$forPricesSubQuery]['customer'],
						'salesman'=>$movement[$forPricesSubQuery]['salesman'],
						'quantity'=> $movement['SalDetail']['quantity'],
						'date'=>date("d/m/Y", strtotime($movement['SalSale']['date'])),
						'fob'=> $movement[$forPricesSubQuery][$currencyFieldPrefix.'fob_price'],
						'cif'=> $movement[$forPricesSubQuery][$currencyFieldPrefix.'cif_price'],
						'sale'=> $movement['SalDetail'][$currencyFieldPrefix.'sale_price']/*[$forPricesSubQuery][$currencyFieldPrefix.'sale_price']*/,
						'fobQuantity'=>$fobQuantity,
						'cifQuantity'=>$cifQuantity,
						'saleQuantity'=>$saleQuantity,
						'warehouse'=>$movement['SalDetail']['inv_warehouse_id']
					);
////					if(isset($movement['InvMovementType']['status'])){
////						$auxArray[$item['InvItem']['id']]['Movements'][$counter]['status']=$movement['InvMovementType']['status'];
////					}
					$counter++;
				}
			}
			//Items
			$auxArray[ $item['InvItem']['id'] ]['Item']['codeName']='[ '.$item['InvItem']['code'].' ] '.$item['InvItem']['name'];
			$auxArray[ $item['InvItem']['id'] ]['Item']['brand']=$item['InvBrand']['name'];
			$auxArray[ $item['InvItem']['id'] ]['Item']['category']=$item['InvCategory']['name'];
			$auxArray[ $item['InvItem']['id'] ]['Item']['id']=$item['InvItem']['id'];
			//Totals
			$auxArray[ $item['InvItem']['id'] ]['TotalMovements']['fobQuantityTotal'] = $fobQuantityTotal;
			$auxArray[ $item['InvItem']['id'] ]['TotalMovements']['cifQuantityTotal'] = $cifQuantityTotal;
			$auxArray[ $item['InvItem']['id'] ]['TotalMovements']['saleQuantityTotal'] = $saleQuantityTotal;
			////I don't calculate total quantity here 'cause could vary in every report, it will be done in the report views
		}
		return $auxArray;
	}
	
	
	private function _generate_report_clients_movements($clientsComplete, $movements, $currencyFieldPrefix){
//		debug($clientsComplete);
		//I'll not calculate totals 'cause will be easier in the view and specially cleaner due the variation of calculation in every report
		$auxArray=array();
		foreach($clientsComplete as $client){
			$fobQuantityTotal = 0;
			$cifQuantityTotal = 0;
			$saleQuantityTotal = 0;
			$counter = 0;
			
			$forPricesSubQuery = 0; //before 'InvMovementDetail'
//			debug($movements);
			//movements
			foreach($movements as $movement){
				if($client['SalCustomer']['id'] == $movement[$forPricesSubQuery]['customerid']){
					$fobQuantity = $movement['SalDetail']['quantity'] * $movement[$forPricesSubQuery][$currencyFieldPrefix.'fob_price'];
					$cifQuantity = $movement['SalDetail']['quantity'] * $movement[$forPricesSubQuery][$currencyFieldPrefix.'cif_price'];
					$saleQuantity = $movement['SalDetail']['quantity'] * $movement['SalDetail'][$currencyFieldPrefix.'sale_price']/*[$forPricesSubQuery][$currencyFieldPrefix.'sale_price']*/;
					$fobQuantityTotal = $fobQuantityTotal + $fobQuantity;
					$cifQuantityTotal = $cifQuantityTotal + $cifQuantity;
					$saleQuantityTotal = $saleQuantityTotal + $saleQuantity;
					$auxArray[$client['SalCustomer']['id']]['Movements'][$counter] = array(
						'code'=>$movement['SalSale']['code'],
						'doc_code'=>$movement['SalSale']['doc_code'],
						'note_code'=>$movement/*[$forPricesSubQuery]*/['SalSale']['note_code'],
						
						'item'=>$movement[$forPricesSubQuery]['itemcode'],
						
//						'customer'=>$movement[$forPricesSubQuery]['customer'],
						'salesman'=>$movement[$forPricesSubQuery]['salesman'],
						'quantity'=> $movement['SalDetail']['quantity'],
						'date'=>date("d/m/Y", strtotime($movement['SalSale']['date'])),
						'fob'=> $movement[$forPricesSubQuery][$currencyFieldPrefix.'fob_price'],
						'cif'=> $movement[$forPricesSubQuery][$currencyFieldPrefix.'cif_price'],
						'sale'=> $movement['SalDetail'][$currencyFieldPrefix.'sale_price']/*[$forPricesSubQuery][$currencyFieldPrefix.'sale_price']*/,
						'fobQuantity'=>$fobQuantity,
						'cifQuantity'=>$cifQuantity,
						'saleQuantity'=>$saleQuantity,
						'warehouse'=>$movement['SalDetail']['inv_warehouse_id']
					);
////					if(isset($movement['InvMovementType']['status'])){
////						$auxArray[$item['InvItem']['id']]['Movements'][$counter]['status']=$movement['InvMovementType']['status'];
////					}
					$counter++;
				}
			}
//			if($movements == array()){
				//Items
				$auxArray[ $client['SalCustomer']['id'] ]['SalCustomer']['name']=$client['SalCustomer']['name'];
	//			$auxArray[ $item['InvItem']['id'] ]['Item']['brand']=$item['InvBrand']['name'];
	//			$auxArray[ $item['InvItem']['id'] ]['Item']['category']=$item['InvCategory']['name'];
				$auxArray[ $client['SalCustomer']['id'] ]['SalCustomer']['id']=$client['SalCustomer']['id'];
//			} else{
//				//Items
//				$auxArray[ $client['SalCustomer']['id'] ]['SalCustomer']['name']=$movement[$forPricesSubQuery]['customer'];
//	//			$auxArray[ $item['InvItem']['id'] ]['Item']['brand']=$item['InvBrand']['name'];
//	//			$auxArray[ $item['InvItem']['id'] ]['Item']['category']=$item['InvCategory']['name'];
//				$auxArray[ $client['SalCustomer']['id'] ]['SalCustomer']['id']=$movement[$forPricesSubQuery]['customerid'];
//			}	
			//Totals
			$auxArray[ $client['SalCustomer']['id'] ]['TotalMovements']['fobQuantityTotal'] = $fobQuantityTotal;
			$auxArray[ $client['SalCustomer']['id'] ]['TotalMovements']['cifQuantityTotal'] = $cifQuantityTotal;
			$auxArray[ $client['SalCustomer']['id'] ]['TotalMovements']['saleQuantityTotal'] = $saleQuantityTotal;
			////I don't calculate total quantity here 'cause could vary in every report, it will be done in the report views
		}
		return $auxArray;
	}
	
	
	private function _generate_report_salesmen_movements($salesmenComplete, $movements, $currencyFieldPrefix){
//		debug($salesmenComplete);
		//I'll not calculate totals 'cause will be easier in the view and specially cleaner due the variation of calculation in every report
		$auxArray=array();
		foreach($salesmenComplete as $salesman){
			$fobQuantityTotal = 0;
			$cifQuantityTotal = 0;
			$saleQuantityTotal = 0;
			$counter = 0;
			
			$forPricesSubQuery = 0; //before 'InvMovementDetail'
//			debug($salesman);
			//movements
			foreach($movements as $movement){
				if($salesman['AdmProfile']['adm_user_id'] == $movement[$forPricesSubQuery]['salesmanid']){
					$fobQuantity = $movement['SalDetail']['quantity'] * $movement[$forPricesSubQuery][$currencyFieldPrefix.'fob_price'];
					$cifQuantity = $movement['SalDetail']['quantity'] * $movement[$forPricesSubQuery][$currencyFieldPrefix.'cif_price'];
					$saleQuantity = $movement['SalDetail']['quantity'] * $movement['SalDetail'][$currencyFieldPrefix.'sale_price']/*[$forPricesSubQuery][$currencyFieldPrefix.'sale_price']*/;
					$fobQuantityTotal = $fobQuantityTotal + $fobQuantity;
					$cifQuantityTotal = $cifQuantityTotal + $cifQuantity;
					$saleQuantityTotal = $saleQuantityTotal + $saleQuantity;
					$auxArray[$salesman['AdmProfile']['adm_user_id']]['Movements'][$counter] = array(
						'code'=>$movement['SalSale']['code'],
						'doc_code'=>$movement['SalSale']['doc_code'],
						'note_code'=>$movement/*[$forPricesSubQuery]*/['SalSale']['note_code'],
						
						'item'=>$movement[$forPricesSubQuery]['itemcode'],
						
						'customer'=>$movement[$forPricesSubQuery]['customer'],
						'quantity'=> $movement['SalDetail']['quantity'],
						'date'=>date("d/m/Y", strtotime($movement['SalSale']['date'])),
						'fob'=> $movement[$forPricesSubQuery][$currencyFieldPrefix.'fob_price'],
						'cif'=> $movement[$forPricesSubQuery][$currencyFieldPrefix.'cif_price'],
						'sale'=> $movement['SalDetail'][$currencyFieldPrefix.'sale_price']/*[$forPricesSubQuery][$currencyFieldPrefix.'sale_price']*/,
						'fobQuantity'=>$fobQuantity,
						'cifQuantity'=>$cifQuantity,
						'saleQuantity'=>$saleQuantity,
						'warehouse'=>$movement['SalDetail']['inv_warehouse_id']
					);
////					if(isset($movement['InvMovementType']['status'])){
////						$auxArray[$item['InvItem']['id']]['Movements'][$counter]['status']=$movement['InvMovementType']['status'];
////					}
					$counter++;
				}
			}
//			if($movements == array()){
				//Items
				$auxArray[ $salesman['AdmProfile']['adm_user_id'] ]['AdmProfile']['full_name']=$salesman['AdmProfile']['full_name'];
	//			$auxArray[ $item['InvItem']['id'] ]['Item']['brand']=$item['InvBrand']['name'];
	//			$auxArray[ $item['InvItem']['id'] ]['Item']['category']=$item['InvCategory']['name'];
				$auxArray[ $salesman['AdmProfile']['adm_user_id'] ]['AdmProfile']['adm_user_id']=$salesman['AdmProfile']['adm_user_id'];
//			} else{
//				//Items
//				$auxArray[ $client['SalCustomer']['id'] ]['SalCustomer']['name']=$movement[$forPricesSubQuery]['customer'];
//	//			$auxArray[ $item['InvItem']['id'] ]['Item']['brand']=$item['InvBrand']['name'];
//	//			$auxArray[ $item['InvItem']['id'] ]['Item']['category']=$item['InvCategory']['name'];
//				$auxArray[ $client['SalCustomer']['id'] ]['SalCustomer']['id']=$movement[$forPricesSubQuery]['customerid'];
//			}	
			//Totals
			$auxArray[ $salesman['AdmProfile']['adm_user_id'] ]['TotalMovements']['fobQuantityTotal'] = $fobQuantityTotal;
			$auxArray[ $salesman['AdmProfile']['adm_user_id'] ]['TotalMovements']['cifQuantityTotal'] = $cifQuantityTotal;
			$auxArray[ $salesman['AdmProfile']['adm_user_id'] ]['TotalMovements']['saleQuantityTotal'] = $saleQuantityTotal;
			////I don't calculate total quantity here 'cause could vary in every report, it will be done in the report views
		}
		return $auxArray;
		debug($auxArray);
	}
	
	private function _generate_report_settings($initialData){
		///////////////////VALUES, FIELDS, CONDITIONS////////////////////////
		$values = array();
		$conditions = array();
		$fields = array();
		$initialStocks=array();
				
		$values['startDate']=$initialData['startDate'];
		$values['finishDate']=$initialData['finishDate'];
		$warehouses = array(0=>$initialData['warehouse']);
		if($initialData['showByType'] == 1000){
			$customers = array(0=>$initialData['customerWO0']);
			$salesmen = array(0=>$initialData['salesman']);
		}elseif($initialData['showByType'] == 998){
			$salesmen = array(0=>$initialData['salesmanWO0']);
			$customers = array(0=>$initialData['customer']);
		}else{
			$customers = array(0=>$initialData['customer']);
			$salesmen = array(0=>$initialData['salesman']);
		}
		
		
		
		$employees = $this->SalSale->SalEmployee->find("list", array(
					"fields"=>array('SalEmployee.id'),
					"conditions"=>array('SalEmployee.sal_customer_id'=>$customers)
			));
//		debug($employees);
//		switch ($initialData['movementType']) {
//			case 998://TODAS LAS ENTRADAS
//				$conditions['InvMovement.inv_movement_type_id']=array(1,4,5,6);
//				break;
//			case 999://TODAS LAS SALIDAS
//				$conditions['InvMovement.inv_movement_type_id']=array(2,3,7);
//				break;
//			case 1000://ENTRADAS Y SALIDAS
//				$values['bindMovementType'] = 1;
//				$initialStocks = $this->_get_stocks($initialData['items'], $initialData['warehouse'], $initialData['startDate'], '<');//before starDate, 'cause it will be added or substracted with movements quantities
//				break;
//			case 1001://TRASPASOS ENTRE ALMACENES
//				$values['bindMovementType'] = 1;
//				$conditions['InvMovement.inv_movement_type_id']=array(3,4);
//				$warehouses[1]=$initialData['warehouse2'];
//				break;
//			default:
//				$conditions['InvMovement.inv_movement_type_id']=$initialData['movementType'];
//				break;
//		}
		if($warehouses[0] > 0){
			$conditions['SalDetail.inv_warehouse_id']=$warehouses;//necessary to be here
		}
		if($customers[0] > 0){
			$conditions['SalSale.sal_employee_id']=$employees;//necessary to be here
		}
		if($salesmen[0] > 0){
			$conditions['SalSale.salesman_id']=$salesmen;//necessary to be here
		}
		$values['items']=$initialData['items'];//just for order
		switch($initialData['currency']){
			case 'BOLIVIANOS':
				//$fields = array('InvMovementDetail.fob_price', 'InvMovementDetail.cif_price', 'InvMovementDetail.sale_price');
				$fields[]='(SELECT price FROM inv_prices where inv_item_id = "SalDetail"."inv_item_id" AND date <= "SalSale"."date" AND inv_price_type_id=1 order by date DESC, date_created DESC LIMIT 1) AS "fob_price"';
				$fields[]='(SELECT price FROM inv_prices where inv_item_id = "SalDetail"."inv_item_id" AND date <= "SalSale"."date" AND inv_price_type_id=8 order by date DESC, date_created DESC LIMIT 1) AS "cif_price"';
				$fields[]='(SELECT price FROM inv_prices where inv_item_id = "SalDetail"."inv_item_id" AND date <= "SalSale"."date" AND inv_price_type_id=9 order by date DESC, date_created DESC LIMIT 1) AS "sale_price"';
				break;
			case 'DOLARES':
				//$fields = array('InvMovementDetail.ex_fob_price', 'InvMovementDetail.ex_cif_price', 'InvMovementDetail.ex_sale_price');
				$fields[]='(SELECT ex_price FROM inv_prices where inv_item_id = "SalDetail"."inv_item_id" AND date <= "SalSale"."date" AND inv_price_type_id=1 order by date DESC, date_created DESC LIMIT 1) AS "ex_fob_price"';
				$fields[]='(SELECT ex_price FROM inv_prices where inv_item_id = "SalDetail"."inv_item_id" AND date <= "SalSale"."date" AND inv_price_type_id=8 order by date DESC, date_created DESC LIMIT 1) AS "ex_cif_price"';
				$fields[]='(SELECT ex_price FROM inv_prices where inv_item_id = "SalDetail"."inv_item_id" AND date <= "SalSale"."date" AND inv_price_type_id=9 order by date DESC, date_created DESC LIMIT 1) AS "ex_sale_price"';
				break;
		}
		
		return array('values'=>$values,'conditions'=>$conditions, 'fields'=>$fields, 'initialStocks'=>$initialStocks);
	}
	
	
	private function _generate_report_movements($values, $conditions, $fields){
		$staticFields = array(
			'SalSale.id',
			'SalSale.code',
			'SalSale.doc_code',
			'SalSale.note_code',
			'SalSale.date',
			'SalDetail.inv_warehouse_id',
			'SalDetail.inv_item_id',
			'SalDetail.quantity',
			'SalDetail.sale_price',
			'SalDetail.ex_sale_price'
			);
		
		
//		Field to get note_code from Sales and Purchases
		$staticFields[]= '(SELECT  inv_items.code FROM inv_items WHERE inv_items.id = "SalDetail"."inv_item_id") AS "itemcode"';
		/*$fieldNoteCode*/ $staticFields[]= '(SELECT  sal_customers.id FROM sal_customers LEFT JOIN sal_employees ON sal_customers.id = sal_employees.sal_customer_id WHERE sal_employees.id = "SalSale"."sal_employee_id") AS "customerid"';
		$staticFields[]= '(SELECT  sal_customers.name FROM sal_customers LEFT JOIN sal_employees ON sal_customers.id = sal_employees.sal_customer_id WHERE sal_employees.id = "SalSale"."sal_employee_id") AS "customer"';
		//$fieldNoteCode = '(SELECT adm_profiles.first_name FROM adm_profiles  JOIN adm_users ON adm_users.id = adm_profiles.adm_user_id WHERE adm_profiles.id = "SalSale"."salesman_id") AS "salesman"';
		/*$fieldNoteCode1*/ $staticFields[]= '(SELECT adm_profiles.adm_user_id FROM adm_profiles WHERE adm_profiles.adm_user_id = "SalSale"."salesman_id") AS "salesmanid"';
		$staticFields[]= '(SELECT adm_profiles.first_name FROM adm_profiles WHERE adm_profiles.adm_user_id = "SalSale"."salesman_id") AS "salesman"';
				
		//$staticFields[] = $fieldNoteCode;
	//	$staticFields[] = $fieldNoteCode1;
//		debug($fieldNoteCode);
//		if(isset($values['bindMovementType']) AND $values['bindMovementType'] == 1){
//			$this->InvMovement->InvMovementDetail->bindModel(array(
//				'hasOne'=>array(
//					'InvMovementType'=>array(
//						'foreignKey'=>false,
//						'conditions'=> array('InvMovement.inv_movement_type_id = InvMovementType.id')
//					)
//				)
//			));
//			$fields[] = 'InvMovementType.status'; 
//		}
		$this->SalSale->SalDetail->unbindModel(array('belongsTo' => array('InvItem')));
		return $this->SalSale->SalDetail->find('all', array(
					'conditions'=>array(
						'SalDetail.inv_item_id'=>$values['items'],
						'SalSale.lc_state'=>'SINVOICE_APPROVED',
						'SalSale.date BETWEEN ? AND ?' => array($values['startDate'], $values['finishDate']),
						$conditions
					),
					'fields'=>  array_merge($staticFields, $fields),
					'order'=>array('SalSale.date', 'SalDetail.id')
				));
	}
	
	
	private function _generate_report_items_complete($items){
		$this->loadModel('InvItem');
		$this->InvItem->unbindModel(array('hasMany' => array('InvMovementDetail', 'PurDetail', 'SalDetail', 'InvItemsSupplier', 'InvPrice')));
		return $this->InvItem->find('all', array(
			'fields'=>array('InvItem.id', 'InvItem.code', 'InvItem.name', 'InvBrand.name', 'InvCategory.name'),
			'conditions'=>array('InvItem.id'=>$items),
			'order'=>array('InvItem.code')
		));
	}
	
	private function _generate_report_clients_complete($clients){
//		debug($clientsDirty);
		$this->loadModel('SalCustomer');
		$this->SalCustomer->unbindModel(array('hasMany' => array('SalTaxNumber', 'SalEmployee')));
//		if($clientsDirty == 0){
//			$clients = $this->SalCustomer->find('list', array(
//				'fields'=>array('SalCustomer.id'),
//				//'conditions'=>array('SalCustomer.id'=>$clients),
//				//'order'=>array('SalCustomer.name')
//			));
////			debug($clients);
//			return $this->SalCustomer->find('all', array(
//				'fields'=>array('SalCustomer.id', 'SalCustomer.name'),
//				'conditions'=>array('SalCustomer.id'=>$clients),
//				'order'=>array('SalCustomer.name')
//			));
//		}else{
			return $this->SalCustomer->find('all', array(
				'fields'=>array('SalCustomer.id', 'SalCustomer.name'),
				'conditions'=>array('SalCustomer.id'=>$clients),
				'order'=>array('SalCustomer.name')
			));
//		}
//		$this->loadModel('SalCustomer');
//		$this->SalCustomer->unbindModel(array('hasMany' => array('SalTaxNumber', 'SalEmployee')));
		
	}
	
	private function _generate_report_salesmen_complete($salesmen){
//		debug($salesmen);
		$this->loadModel('AdmProfile');
//		$this->AdmProfile->unbindModel(array('hasMany' => array('SalTaxNumber', 'SalEmployee')));
//		if($clientsDirty == 0){
//			$clients = $this->SalCustomer->find('list', array(
//				'fields'=>array('SalCustomer.id'),
//				//'conditions'=>array('SalCustomer.id'=>$clients),
//				//'order'=>array('SalCustomer.name')
//			));
////			debug($clients);
//			return $this->SalCustomer->find('all', array(
//				'fields'=>array('SalCustomer.id', 'SalCustomer.name'),
//				'conditions'=>array('SalCustomer.id'=>$clients),
//				'order'=>array('SalCustomer.name')
//			));
//		}else{
			return $this->AdmProfile->find('all', array(
				'fields'=>array('AdmProfile.adm_user_id', 'AdmProfile.full_name'), //full_name
				'conditions'=>array('AdmProfile.adm_user_id'=>$salesmen),
				'order'=>array('AdmProfile.first_name')
			));
//		}
//		$this->loadModel('SalCustomer');
//		$this->SalCustomer->unbindModel(array('hasMany' => array('SalTaxNumber', 'SalEmployee')));
		
	}
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	public function vreport_generator_customers_debts(){
		$customers[0] = "TODOS";
		$this->loadModel("SalCustomer");
		$customersClean = $this->SalCustomer->find("list");
		foreach ($customersClean as $key => $value) {
			$customers[$key]=$value;
		}
		
		$this->set(compact("customers"));
	}
	
	public function  ajax_generate_report_customers_debts(){
		if($this->RequestHandler->isAjax()){
			//SETTING DATA
			$this->Session->write('ReportCustomersDebts.customer', $this->request->data['customer']);
			$this->Session->write('ReportCustomersDebts.customerName', $this->request->data['customerName']);
			$this->Session->write('ReportCustomersDebts.showType', $this->request->data['showType']);
			$this->Session->write('ReportCustomersDebts.currency', $this->request->data['currency']);
		///END AJAX
		}
	}
	
	public function vreport_customers_debts(){
		//special ctp template for printing due DOMPdf colapses generating too many pages
		$this->layout = 'print';
		
		//Check if session variables are set otherwise redirect
		if(!$this->Session->check('ReportCustomersDebts')){
			$this->redirect(array('action' => 'vreport_generator_customers_debts'));
		}
		
		//put session data sent data into variables
//		$initialData = $this->Session->read('ReportCustomersDebts');
//		
//		debug($initialData);
//		
//		
//		/////////////////////
//		$conditionCustomer = null;
//		if($initialData['customer'] > 0){
//			$conditionCustomer = array("SalCustomer.id"=>$initialData['customer']);
//		}
//		/////////////////////
//		$this->SalSale->SalDetail->bindModel(array(
//			'hasOne'=>array(
//				'SalEmployee'=>array(
//					'foreignKey'=>false,
//					'conditions'=> array('SalSale.sal_employee_id = SalEmployee.id')
//				),
//				'SalCustomer'=>array(
//					'foreignKey'=>false,
//					'conditions'=> array('SalEmployee.sal_customer_id = SalCustomer.id')
//				)
//			)
//		));
//		
//		$currencyField = "";
//		if(strtoupper($initialData["currency"]) == 'DOLARES'){ $currencyField = "ex_";}
		
//		$this->SalSale->SalDetail->unbindModel(array('belongsTo' => array('InvWarehouse')));
		$data = $this->SalSale->find("all", array(
			"fields"=>array(
//				'SUM("SalDetail"."quantity" * "SalDetail"."'.$currencyField.'sale_price") AS money',
//				'SUM("SalDetail"."quantity") AS quantity',
//				'SalSale.date',
				'SalSale.note_code'
			),
//			'group'=>array("SalCustomer.name", "SalCustomer.id"),
			"conditions"=>array(
//				"to_char(SalSale.date,'YYYY')"=>$initialData['year'],
//				"SalDetail.inv_item_id"=>$initialData['items'],
//				$conditionMonth
				"SalSale.id <"=>50
			),
//			"order"=>array("SalCustomer.name")
		));
		debug($data);
		die();
//		$this->loadModel("SalCustomer");
//		$customers = $this->SalCustomer->find("list", array("order"=>array("SalCustomer.name")));
//		//debug($data);
//		
//		//debug($customers);
//		$details = array();
//		
//		if($initialData["zero"] == "yes"){
//			$counter = 0;
//			foreach ($customers as $key => $customer) {
//				$details[$counter]['SalCustomer']['name'] = $customer;
//				$details[$counter][0]['money'] = 0;
//				$details[$counter][0]['quantity'] = 0;
//				foreach ($data as $key2 => $value) {
//					
//					if($key == $value['SalCustomer']['id']){
//						//debug($value[0]['money']);
//						$details[$counter][0]['money'] = $value[0]['money'];
//						$details[$counter][0]['quantity'] = $value[0]['quantity'];
//					}
//				}
//				$counter++;
//			}
//		}else{
//			$details = $data;
//		}
//		
//		//debug($details);
//		
//		//debug($details);
//		
//		//debug($details);
//		
//		//Now list items selected in order to get a reference
//		$group = array();
//			switch ($initialData['groupBy']) {
//				case 'category':
//					$this->loadModel("InvCategory");
//					$group = $this->InvCategory->find("list", array("order"=>array("InvCategory.name")));
//					$this->set('group', $group);
//					break;
//				case 'brand':
//					$this->loadModel("InvBrand");
//					$group = $this->InvBrand->find("list", array("order"=>array("InvBrand.name")));
//					$this->set('group', $group);
//					break;
//			}
//			$items = $this->_find_items($initialData['groupBy'], array_keys($group), $initialData['items']);
//		
//		
//			
//		$this->set(compact("details", "items"));
//		//debug($items);
//		$this->Session->delete('ReportPurchasesCustomers');
	}
	//////////////////////////////////////////// END - REPORT /////////////////////////////////////////////////
	
	

	//////////////////////////////////////////START-GRAPHICS//////////////////////////////////////////
	/*
	public function vgraphics(){
		$this->loadModel("AdmPeriod");
		$years = $this->AdmPeriod->find("list", array(
			"order"=>array("name"=>"desc"),
			"fields"=>array("name", "name")
			)
		);
		
		$this->loadModel("InvItem");
		
		$itemsClean = $this->InvItem->find("list", array('order'=>array('InvItem.code')));
		$items[0]="TODOS";
		foreach ($itemsClean as $key => $value) {
			$items[$key] = $value;
		}
		
		$this->set(compact("years", "items"));
		//debug($this->_get_bars_sales_and_time("2013", "0"));
	}
	*/
	
	public function vreport_generator_purchases_customers(){
		$this->loadModel("AdmPeriod");
		$years = $this->AdmPeriod->find("list", array(
			"order"=>array("name"=>"desc"),
			"fields"=>array("name", "name")
			)
		);
		$months = array(0=>"TODOS", 1=>"Enero", 2=>"Febrero", 3=>"Marzo", 4=>"Abril", 5=>"Mayo", 6=>"Junio", 7=>"Julio", 8=>"Agosto", 9=>"Septiembre", 10=>"Octubre", 11=>"Noviembre", 12=>"Diciembre");
		$item = $this->_find_items();
		$customers[0] = "TODOS";
		$this->loadModel("SalCustomer");
		$customersClean = $this->SalCustomer->find("list");
		foreach ($customersClean as $key => $value) {
			$customers[$key]=$value;
		}
		$this->set(compact("years", "months", "item", "customers"));
	}
	
	
	public function vreport_purchases_customers(){
		//special ctp template for printing due DOMPdf colapses generating too many pages
		$this->layout = 'print';
		
		//Check if session variables are set otherwise redirect
		if(!$this->Session->check('ReportPurchasesCustomers')){
			$this->redirect(array('action' => 'vreport_generator_purchases_customers'));
		}
		
		//put session data sent data into variables
		$initialData = $this->Session->read('ReportPurchasesCustomers');
		
		//debug($initialData);
		$this->set("initialData", $initialData);
		$conditionMonth = null;
		if($initialData['month'] > 0){
			if(count($initialData['month']) == 1){
				$conditionMonth = array("to_char(SalSale.date,'mm')" => "0".$initialData['month']);
			}else{
				$conditionMonth = array("to_char(SalSale.date,'mm')" => $initialData['month']);
			}
		}
		/////////////////////
		$conditionCustomer = null;
		if($initialData['customer'] > 0){
			$conditionCustomer = array("SalCustomer.id"=>$initialData['customer']);
		}
		/////////////////////
		$this->SalSale->SalDetail->bindModel(array(
			'hasOne'=>array(
				'SalEmployee'=>array(
					'foreignKey'=>false,
					'conditions'=> array('SalSale.sal_employee_id = SalEmployee.id')
				),
				'SalCustomer'=>array(
					'foreignKey'=>false,
					'conditions'=> array('SalEmployee.sal_customer_id = SalCustomer.id')
				)
			)
		));
		
		$currencyField = "";
		if(strtoupper($initialData["currency"]) == 'DOLARES'){ $currencyField = "ex_";}
		
		$this->SalSale->SalDetail->unbindModel(array('belongsTo' => array('InvWarehouse')));
		$data = $this->SalSale->SalDetail->find("all", array(
			"fields"=>array(
				'SUM("SalDetail"."quantity" * "SalDetail"."'.$currencyField.'sale_price") AS money',
				'SUM("SalDetail"."quantity") AS quantity',
				//'SalDetail.quantity',
				//'SalDetail.sale_price',
				//'SalDetail.id',
				//'SalSale.id',
				'SalCustomer.name',
				'SalCustomer.id',
			),
			'group'=>array("SalCustomer.name", "SalCustomer.id"),
			"conditions"=>array(
				//"SalCustomer.id"=>array(11,77,367),
				'SalSale.lc_state'=>'SINVOICE_APPROVED',
				"to_char(SalSale.date,'YYYY')"=>$initialData['year'],
				"SalDetail.inv_item_id"=>$initialData['items'],
				$conditionMonth,
				$conditionCustomer
			),
			"order"=>array('"money"'=>"DESC", "SalCustomer.name")
		));
		$this->loadModel("SalCustomer");
		$customers = $this->SalCustomer->find("list", array("order"=>array("SalCustomer.name")));
//		debug($data);
		
		//debug($customers);
		//$details = array();
		
		$details = $data;
		//debug($details);
		
		
		
		if($initialData["zero"] == "yes"){
			if($initialData["customer"] == 0){
				$counter = 0;
				foreach ($data as $key2 => $value) {
					foreach ($customers as $key => $customer) {
						if($key == $value['SalCustomer']['id']){
							//debug($key);
							$details[$counter]['SalCustomer']['name'] = $customer;
							$details[$counter][0]['money'] = $value[0]['money'];
							$details[$counter][0]['quantity'] = $value[0]['quantity'];
							unset($customers[$key]);
						}
					}
					$counter++;
				}
				foreach ($customers as $key => $customer) {
					$details[$counter]['SalCustomer']['name'] = $customer;
					$details[$counter][0]['money'] = 0;
					$details[$counter][0]['quantity'] = 0;
					$counter++;
				}
			}else{ // in case for just one customer
				if(count($details) == 0){
					$details[0]['SalCustomer']['name'] = $initialData['customerName'];
					$details[0][0]['money'] = 0;
					$details[0][0]['quantity'] = 0;
				}
			}
		}
		
		
		
		//debug($details);
		
		//debug($details);
		
		//debug($details);
		
		//Now list items selected in order to get a reference
		$group = array();
			switch ($initialData['groupBy']) {
				case 'category':
					$this->loadModel("InvCategory");
					$group = $this->InvCategory->find("list", array("order"=>array("InvCategory.name")));
					$this->set('group', $group);
					break;
				case 'brand':
					$this->loadModel("InvBrand");
					$group = $this->InvBrand->find("list", array("order"=>array("InvBrand.name")));
					$this->set('group', $group);
					break;
			}
			$items = $this->_find_items($initialData['groupBy'], array_keys($group), $initialData['items']);
		
		
		$this->set(compact("details", "items"));
		//debug($items);
		$this->Session->delete('ReportPurchasesCustomers');
	}
	
	
	public function  ajax_generate_report_purchases_customers(){
		if($this->RequestHandler->isAjax()){
			//SETTING DATA
			$this->Session->write('ReportPurchasesCustomers.year', $this->request->data['year']);
			$this->Session->write('ReportPurchasesCustomers.month', $this->request->data['month']);
			$this->Session->write('ReportPurchasesCustomers.monthName', $this->request->data['monthName']);
			$this->Session->write('ReportPurchasesCustomers.currency', $this->request->data['currency']);
			$this->Session->write('ReportPurchasesCustomers.zero', $this->request->data['zero']);
			$this->Session->write('ReportPurchasesCustomers.groupBy', $this->request->data['groupBy']);
			$this->Session->write('ReportPurchasesCustomers.customer', $this->request->data['customer']);
			$this->Session->write('ReportPurchasesCustomers.customerName', $this->request->data['customerName']);
			
			//array items
			$this->Session->write('ReportPurchasesCustomers.items', $this->request->data['items']);
			
		///END AJAX
		}
	}
	/////////////////////////////////////////////////////
	public function ajax_generate_report_items_utilities(){
		if($this->RequestHandler->isAjax()){
			$this->Session->write('ReportItemsUtilities.startDate', $this->request->data['startDate']);
			$this->Session->write('ReportItemsUtilities.finishDate', $this->request->data['finishDate']);
			$this->Session->write('ReportItemsUtilities.currency', $this->request->data['currency']);
			$this->Session->write('ReportItemsUtilities.items', $this->request->data['items']);
			
			$this->Session->write('ReportItemsUtilities.customer', $this->request->data['customer']);
			$this->Session->write('ReportItemsUtilities.customerName', $this->request->data['customerName']);
			
			$this->Session->write('ReportItemsUtilities.salesman', $this->request->data['salesman']);
			$this->Session->write('ReportItemsUtilities.salesmanName', $this->request->data['salesmanName']);
		}
	}
	
	public function vreport_items_utilities(){
		$this->layout = 'print';
		
		//Check if session variables are set otherwise redirect
		if(!$this->Session->check('ReportItemsUtilities')){
			$this->redirect(array('action' => 'vreport_items_utilities_generator'));
		}
		
		//put session data sent data into variables
		$initialData = $this->Session->read('ReportItemsUtilities');
		
		$conditionCustomer = null;
		if($initialData["customer"] > 0){
			$conditionCustomer = array("SalEmployee.sal_customer_id" => $initialData["customer"]);
		}
		$conditionSalesman = null;
		if($initialData["salesman"] > 0){
			$conditionSalesman = array("SalSale.salesman_id" => $initialData["salesman"]);
		}
		
		$currencyAbbr = "";
		if($initialData["currency"] == "DOLARES"){
			$currencyAbbr = "ex_";
		}
		
		//debug($initialData);
		$this->SalSale->SalDetail->bindModel(array(
			'hasOne'=>array(
				'SalEmployee'=>array(
					'foreignKey'=>false,
					'conditions'=> array('SalSale.sal_employee_id = SalEmployee.id')
				)
			)
		));
		$this->SalSale->SalDetail->unbindModel(array('belongsTo' => array('InvWarehouse')));
		
		$prices = $this->SalSale->SalDetail->find("all", array(
			"conditions"=>array(
				"InvItem.id"=>$initialData["items"]
				,'SalSale.lc_state'=>'SINVOICE_APPROVED'
				,'SalSale.date BETWEEN ? AND ?' => array($initialData['startDate'], $initialData['finishDate'])
				,$conditionCustomer
				,$conditionSalesman
			)
			,"fields"=>array(
				"InvItem.id"
				//,"SalSale.salesman_id"
				//,"SalEmployee.sal_customer_id"
				,"InvItem.code"
				,"InvItem.name"
				,'SUM(SalDetail.quantity) AS quantity'
				,'SUM("SalDetail"."'.$currencyAbbr.'sale_price" * "SalDetail"."quantity") AS sale'
				,'SUM((SELECT '.$currencyAbbr.'price FROM inv_prices where inv_item_id = "SalDetail"."inv_item_id" AND date <= "SalSale"."date" AND inv_price_type_id=8 order by date DESC, date_created DESC LIMIT 1) * "SalDetail"."quantity") AS "cif"'
			)
			,"group"=>array(
				"InvItem.id"
				,"InvItem.code"
				,"InvItem.name"
				//,"SalSale.salesman_id"
				//,"SalEmployee.sal_customer_id"
			)
			,"order"=>array('"quantity"' => 'DESC', 'InvItem.code')
		));
		
		
		$dataDetail = array();
		//debug($prices);
		$this->loadModel("InvItem");
		$this->InvItem->unbindModel(array('belongsTo' => array('InvPrice', 'InvMovementDetail', 'InvItemsSupplier')));
		
		
		$items = array();
		
	   foreach ($initialData["items"] as $value) {
		 $items[$value] = $value;
	   }

	   foreach($prices as $value){
			$index = $value['InvItem']['id'];
			$dataDetail[$index]["code"] = $value['InvItem']['code'];
			$dataDetail[$index]["name"] = $value['InvItem']['name'];
			$dataDetail[$index]["quantity"] = $value[0]['quantity'];
			$dataDetail[$index]["sale"] = $value[0]['sale'];
			$dataDetail[$index]["cif"] = $value[0]['cif'];
			
			$utility = $value[0]['sale'] - $value[0]['cif'];
			$dataDetail[$index]["utility"] = $utility;
			$dataDetail[$index]["margin"] = ($utility * 100) / $value[0]['sale'];
			unset($items[$index]);
		}
		
		$pricesZero = $this->InvItem->find("all", array(
			"conditions"=>array("InvItem.id"=>$items),
			"fields"=>array("InvItem.id", "InvItem.code", "InvItem.name"),
			"order"=>array("InvItem.code")
		));
		
		foreach($pricesZero as $keyItem => $item){
			$index = $item['InvItem']['id'];
			$dataDetail[$index]["code"] = $item['InvItem']['code'];
			$dataDetail[$index]["name"] = $item['InvItem']['name'];
			$dataDetail[$index]["quantity"] = 0;
			$dataDetail[$index]["sale"] = 0;
			$dataDetail[$index]["cif"] = 0;
			$dataDetail[$index]["utility"] = 0;
			$dataDetail[$index]["margin"] = 0;
		}
		 
		$this->set("data", $initialData);
		$this->set("dataDetails", $dataDetail);
		$this->Session->delete('ReportItemsUtilities');
	}
	
	
	public function vreport_items_utilities_generator(){
		$this->loadModel("AdmPeriod");
		$years = $this->AdmPeriod->find("list", array(
			"order"=>array("name"=>"desc"),
			"fields"=>array("name", "name")
			)
		);
		$item = $this->_find_items();
		
		$this->loadModel('AdmUser');
		$salesmanClean = $this->AdmUser->AdmProfile->find('list', array(
			"order"=>array("first_name"),
			"fields"=>array("adm_user_id", "full_name")
			)
		);
		$salesmen[0] = "TODOS";
		foreach ($salesmanClean as $key => $value) {
			$salesmen[$key] = $value;
		}
		
//		debug($salesmen);
		
		
		$customersClean = $this->SalSale->SalEmployee->SalCustomer->find('list', array("order"=>array("name")));
		$customers[0] = "TODOS";
		//debug($customer);
		foreach ($customersClean as $key => $value) {
			$customers[$key] = $value;
		}
		
		$this->set(compact("years", "item", "customers", "salesmen"));
	}
	////////////////////////////////////////////////////

	public function vgraphics_items_customers(){
		$clientsClean = $this->SalSale->SalEmployee->SalCustomer->find('list');
		$clients[0] = "TODOS";
		foreach ($clientsClean as $key => $value) {
			$clients[$key] = $value;
		}
		$this->loadModel("AdmPeriod");
		$years = $this->AdmPeriod->find("list", array(
			"order"=>array("name"=>"desc"),
			"fields"=>array("name", "name")
			)
		);
		$months = array(0=>"Todos", 1=>"Enero", 2=>"Febrero", 3=>"Marzo", 4=>"Abril", 5=>"Mayo", 6=>"Junio", 7=>"Julio", 8=>"Agosto", 9=>"Septiembre", 10=>"Octubre", 11=>"Noviembre", 12=>"Diciembre");
		$item = $this->_find_items();
		
		$this->set(compact("clients", "years", "months", "item"));
		
		//////////////////////////////////////////////////////////////
		/*
		$this->SalSale->SalDetail->bindModel(array(
			'hasOne'=>array(
				'SalEmployee'=>array(
					'foreignKey'=>false,
					'conditions'=> array('SalSale.sal_employee_id = SalEmployee.id')
				)
			)
		));
		$this->SalSale->SalDetail->unbindModel(array('belongsTo' => array('InvWarehouse')));
		$currencyType = "price";
		$data = $this->SalSale->SalDetail->find('all', array(
			"fields"=>array(
				//"InvItem.id",
				"InvItem.code",
				"InvItem.name",
				'SUM("SalDetail"."quantity" * (SELECT '.$currencyType.'  FROM inv_prices where inv_item_id = "SalDetail"."inv_item_id" AND date <= "SalSale"."date" AND inv_price_type_id=9 order by date DESC, date_created DESC LIMIT 1)) AS money',
				'SUM("SalDetail"."quantity") AS quantity'
			),
			'group'=>array(
				//"InvItem.id",
				"InvItem.code",
				"InvItem.name"
				),
			"conditions"=>array(
				"to_char(SalSale.date,'YYYY')"=>"2013",
				"SalSale.lc_state"=>"SINVOICE_APPROVED",
				//"SalDetail.inv_item_id" => $items,
				//$conditionPerson,
				//$conditionMonth
			),
			"order"=>array('"money"'=> 'desc')
		));
		
		debug($data);
		*/
	}
	
	
	public function vgraphics_items_salesmen(){
		$this->loadModel("AdmProfile");
		$salesmenClean = $this->AdmProfile->find('list', array("fields"=>array("AdmProfile.adm_user_id", "AdmProfile.full_name")));
		$salesmen[0] = "TODOS";
		foreach ($salesmenClean as $key => $value) {
			$salesmen[$key] = $value;
		}
		$this->loadModel("AdmPeriod");
		$years = $this->AdmPeriod->find("list", array(
			"order"=>array("name"=>"desc"),
			"fields"=>array("name", "name")
			)
		);
		$months = array(0=>"Todos", 1=>"Enero", 2=>"Febrero", 3=>"Marzo", 4=>"Abril", 5=>"Mayo", 6=>"Junio", 7=>"Julio", 8=>"Agosto", 9=>"Septiembre", 10=>"Octubre", 11=>"Noviembre", 12=>"Diciembre");
		$item = $this->_find_items();
		
		$this->set(compact("salesmen", "years", "months", "item"));
		
		//////////////////////////////////////////////////////////////
	}
	
	
	/////////////
	/*
	public function ajax_get_graphics_data(){
		if($this->RequestHandler->isAjax()){
			$year = $this->request->data['year'];
			$currency = $this->request->data['currency'];
			$item = $this->request->data['item'];
			$string = $this->_get_bars_sales_and_time($year, $item, $currency);
			echo $string;
		}
//		$string .= '30|54|12|114|64|100|98|80|10|50|169|222';
	}
	*/
	
	public function ajax_get_graphics_items_customers(){
		if($this->RequestHandler->isAjax()){
			$year = $this->request->data['year'];
			$month = $this->request->data['month'];
			$currency = $this->request->data['currency'];
			$items = $this->request->data['items'];
			$groupBy = $this->request->data['groupBy'];
			$customer = $this->request->data['customer'];
			
			$showMode = $this->request->data['showMode'];
			//$string = $this->_get_bars_sales_and_time($year, $items, $currency, $client);
			
			//$string = $this->_get_pie_items_quantity_and_type("entrada", $year, $warehouse, $item).",";
			//$string .= $this->_get_pie_items_quantity_and_type("salida", $year, $warehouse, $item).",";
			
			$barsData = $this->_get_bars_sales_and_time($year, $items, $currency, $customer, "customer");
			$piesData = $this->_get_pies_sales_and_time($year, $items, $currency, $month, $customer, "customer", $showMode);
			//debug($piesData);
			$string="";
			$string .= $barsData["quantity"].",";
			$string .= $barsData["money"].",";
			$string .= $piesData["quantity"].",";
			$string .= $piesData["money"].",";
			
			$string .= $piesData["topMoreQuantity"].",";
			$string .= $piesData["topMoreMoney"].",";
			$string .= $piesData["topLessQuantity"].",";
			$string .= $piesData["topLessMoney"].",";
			

			echo $string;
		}
	}
	
	
	public function ajax_get_graphics_items_salesmen(){
		if($this->RequestHandler->isAjax()){
			$year = $this->request->data['year'];
			$month = $this->request->data['month'];
			$currency = $this->request->data['currency'];
			$items = $this->request->data['items'];
			$groupBy = $this->request->data['groupBy'];
			$salesman = $this->request->data['salesman'];
			
			$showMode = $this->request->data['showMode'];
			//$string = $this->_get_bars_sales_and_time($year, $items, $currency, $client);
			
			//$string = $this->_get_pie_items_quantity_and_type("entrada", $year, $warehouse, $item).",";
			//$string .= $this->_get_pie_items_quantity_and_type("salida", $year, $warehouse, $item).",";
			
			$barsData = $this->_get_bars_sales_and_time($year, $items, $currency, $salesman, "salesman");
			$piesData = $this->_get_pies_sales_and_time($year, $items, $currency, $month, $salesman, "salesman", $showMode);
			//debug($piesData);
			$string="";
			$string .= $barsData["quantity"].",";
			$string .= $barsData["money"].",";
			$string .= $piesData["quantity"].",";
			$string .= $piesData["money"].",";
			
			$string .= $piesData["topMoreQuantity"].",";
			$string .= $piesData["topMoreMoney"].",";
			$string .= $piesData["topLessQuantity"].",";
			$string .= $piesData["topLessMoney"].",";
			

			echo $string;
		}
	}
	
	private function _get_bars_sales_and_time($year, $items, $currency, $person, $personType){
		$conditionPerson = null;
		$dataString = "";
		$dataString2 = "";
	/*	
		if($item > 0){
			$conditionItem = array("SalDetail.inv_item_id" => $item);
		}
	*/	
		if($person > 0){
			if($personType == "customer"){
				$conditionPerson = array("SalEmployee.sal_customer_id" => $person);
			}else{
				$conditionPerson = array("SalSale.salesman_id" => $person);
			}
		}
		$currencyType = "sale_price";
		if(strtoupper($currency) == "DOLARES"){
			$currencyType = "ex_sale_price";
		}
		
		//*****************************************************************************//
		$this->SalSale->SalDetail->bindModel(array(
			'hasOne'=>array(
				'SalEmployee'=>array(
					'foreignKey'=>false,
					'conditions'=> array('SalSale.sal_employee_id = SalEmployee.id')
				)
				//Not using this relation because on SalEmployee already exists a customer ID.
				/*,
				'SalCustomer'=>array(
					'foreignKey'=>false,
					'conditions'=> array('SalEmployee.sal_customer_id = SalCustomer.id')
				)*/
			)
		));
		$this->SalSale->SalDetail->unbindModel(array('belongsTo' => array('InvWarehouse')));
		$data = $this->SalSale->SalDetail->find('all', array(
			"fields"=>array(
				"to_char(\"SalSale\".\"date\",'mm') AS month",
				//'SUM("SalDetail"."quantity" * (SELECT '.$currencyType.'  FROM inv_prices where inv_item_id = "SalDetail"."inv_item_id" AND date <= "SalSale"."date" AND inv_price_type_id=9 order by date DESC, date_created DESC LIMIT 1)) AS money',
				'SUM("SalDetail"."quantity" * "SalDetail"."'.$currencyType.'") as money',
				'SUM("SalDetail"."quantity") AS quantity'
			),
			'group'=>array("to_char(SalSale.date,'mm')"),
			"conditions"=>array(
				"to_char(SalSale.date,'YYYY')"=>$year,
				"SalSale.lc_state"=>"SINVOICE_APPROVED",
				"SalDetail.inv_item_id" => $items,
				$conditionPerson
			)
		));
		//*****************************************************************************//
		
		
		//format data on string to response ajax request
		$months = array(1,2,3,4,5,6,7,8,9,10,11,12);
		
		foreach ($months as $month) {
			$exist = 0;
			foreach ($data as $value) {
				if($month == (int)$value[0]['month']){
					$dataString .= $value[0]['money']."|";
					$dataString2 .= $value[0]['quantity']."|";
					//debug($dataString);
					$exist++;
				}
			}
			if($exist == 0){
				$dataString .= "0|";
				$dataString2 .= "0|";
			}
		}
		
		return array("quantity"=>substr($dataString2, 0, -1), "money"=>substr($dataString, 0, -1));
	}
	
	
	private function _get_pies_sales_and_time($year, $items, $currency, $month, $person, $personType, $showMode){
		$conditionPerson = null;
		$conditionMonth = null;
		$dataString = "";
		$dataString2 = "";

		if($person > 0){
			if($personType == "customer"){
				$conditionPerson = array("SalEmployee.sal_customer_id" => $person);
			}else{
				$conditionPerson = array("SalSale.salesman_id" => $person);
			}
		}
		
		if($month > 0){
			if(count($month) == 1){
				$conditionMonth = array("to_char(SalSale.date,'mm')" => "0".$month);
			}else{
				$conditionMonth = array("to_char(SalSale.date,'mm')" => $month);
			}
			
		}
		$currencyType = "sale_price";
		if(strtoupper($currency) == "DOLARES"){
			$currencyType = "ex_sale_price";
		}
		
		//********************************************* ********************************//
		
		$this->SalSale->SalDetail->bindModel(array(
			'hasOne'=>array(
				'SalEmployee'=>array(
					'foreignKey'=>false,
					'conditions'=> array('SalSale.sal_employee_id = SalEmployee.id')
				)
			)
		));
		$this->SalSale->SalDetail->unbindModel(array('belongsTo' => array('InvWarehouse')));
		$data = $this->SalSale->SalDetail->find('all', array(
			"fields"=>array(
				//"InvItem.id",
				"InvItem.code",
				"InvItem.name",
				//'SUM("SalDetail"."quantity" * (SELECT '.$currencyType.'  FROM inv_prices where inv_item_id = "SalDetail"."inv_item_id" AND date <= "SalSale"."date" AND inv_price_type_id=9 order by date DESC, date_created DESC LIMIT 1)) AS money',
				'SUM("SalDetail"."quantity" * "SalDetail"."'.$currencyType.'") as money',
				'SUM("SalDetail"."quantity") AS quantity'
			),
			'group'=>array(
				//"InvItem.id",
				"InvItem.code",
				"InvItem.name"
				),
			"conditions"=>array(
				"to_char(SalSale.date,'YYYY')"=>$year,
				"SalSale.lc_state"=>"SINVOICE_APPROVED",
				"SalDetail.inv_item_id" => $items,
				$conditionPerson,
				$conditionMonth
			),
			"order"=>array('"money"'=> 'desc')
		));
		
		//*************************************JUST FOR TOP QUANTITIES************************************//
		$this->SalSale->SalDetail->bindModel(array(
			'hasOne'=>array(
				'SalEmployee'=>array(
					'foreignKey'=>false,
					'conditions'=> array('SalSale.sal_employee_id = SalEmployee.id')
				)
			)
		));
		$this->SalSale->SalDetail->unbindModel(array('belongsTo' => array('InvWarehouse')));
		$topQuantity = $this->SalSale->SalDetail->find('all', array(
			"fields"=>array(
				//"InvItem.id",
				"InvItem.code",
				"InvItem.name",
				'SUM("SalDetail"."quantity") AS quantity'
			),
			'group'=>array(
				"InvItem.code",
				"InvItem.name"
				),
			"conditions"=>array(
				"to_char(SalSale.date,'YYYY')"=>$year,
				"SalSale.lc_state"=>"SINVOICE_APPROVED",
				"SalDetail.inv_item_id" => $items,
				$conditionPerson,
				$conditionMonth
			),
			"order"=>array('"quantity"'=> 'desc')
		));
		
		//////////////////////////////////////////////////////////////////////////////////////////
		//debug($data);
		$limit = count($data);
		$dataString3 = "";
		$dataString4 = "";
		$dataString5 = "";
		$dataString6 = "";
		$counter = 1;
		//debug($limit);
		$limitbackwards = $limit - 10;
		$fullName = "";
		//debug($limitbackwards);
		$arrayForTopLessMoney = array();
		$arrayForTopLessQuantity = array();
		
	   foreach ($data as $value) {
		   $dataString .= $value['InvItem']['code'] ."==".$value['0']['money']."|";
		   $dataString2 .= $value['InvItem']['code'] ."==".$value['0']['quantity']."|";
		   $fullName = "[ ".$value['InvItem']['code']." ] ".$value['InvItem']['name'];
		   if($counter <= 10){
			   $dataString3 .= $fullName ."==".$value['0']['money']."|";
			   //debug($counter);
		   }
		   if($counter >= $limitbackwards ){
			   //$dataString5 .= $fullName ."==".$value['0']['money']."|";
			   $arrayForTopLessMoney[] = $fullName ."==".$value['0']['money'];
		   }
		   $counter++;
		   
	   }
	   //////////////////////////////////START - Show mode - when option show by groups is selected/////////////////////////////
	   if($showMode <> "items"){
		   $dataString = "";
		   $dataString2 = "";
		   
		   //$varGroupId ="InvItem.inv_brand_id";
		   $varGroupModel ="InvBrand";
		   $varGroupField ="name";
		   if($showMode == "category"){
			   //$varGroupId ="InvItem.inv_category_id";
			    $varGroupModel ="InvCategory";
				$varGroupField ="name";
		   }
		   
		   $this->SalSale->SalDetail->bindModel(array(
				'hasOne'=>array(
					'SalEmployee'=>array(
						'foreignKey'=>false,
						'conditions'=> array('SalSale.sal_employee_id = SalEmployee.id')
					),
					'InvBrand'=>array(
						'foreignKey'=>false,
						'conditions'=> array('InvItem.inv_brand_id = InvBrand.id')
					),
					'InvCategory'=>array(
						'foreignKey'=>false,
						'conditions'=> array('InvItem.inv_category_id = InvCategory.id')
					)
				)
			));
			$this->SalSale->SalDetail->unbindModel(array('belongsTo' => array('InvWarehouse')));
			$dataGroup = $this->SalSale->SalDetail->find('all', array(
				"fields"=>array(
					//"InvItem.code",
					//"InvItem.name",
					$varGroupModel.".".$varGroupField,
					'SUM("SalDetail"."quantity" * "SalDetail"."'.$currencyType.'") as money',
					'SUM("SalDetail"."quantity") AS quantity'
				),
				'group'=>array(
					//"InvItem.code",
					//"InvItem.name"
					$varGroupModel.".".$varGroupField
					),
				"conditions"=>array(
					"to_char(SalSale.date,'YYYY')"=>$year,
					"SalSale.lc_state"=>"SINVOICE_APPROVED",
					"SalDetail.inv_item_id" => $items,
					$conditionPerson,
					$conditionMonth
				),
				"order"=>array('"money"'=> 'desc')
			));
			//////////////////////////////////////////
			foreach ($dataGroup as $value) {
				$dataString .= $value[$varGroupModel][$varGroupField] ."==".$value['0']['money']."|";
				$dataString2 .= $value[$varGroupModel][$varGroupField] ."==".$value['0']['quantity']."|";
			}
		   
	   }
	   //////////////////////////////////END - Show mode - when option show by groups is selected/////////////////////////////
	   
	   $counter = 1;
	   foreach ($topQuantity as $value) {
		   $fullName = "[ ".$value['InvItem']['code']." ] ".$value['InvItem']['name'];
		   if($counter <= 10){
			  $dataString4 .= $fullName ."==".$value['0']['quantity']."|";
			   
		   }
		   if($counter >= $limitbackwards ){
			  //$dataString6 .= $fullName ."==".$value['0']['quantity']."|";
			  $arrayForTopLessQuantity[] = $fullName ."==".$value['0']['quantity'];
		   }
		   $counter++;
	   }
	   
	   //Now to revert order to get top less values
	   $limitTopLessMoney = count($arrayForTopLessMoney);
	   //debug($limitTopLessMoney);
	   if($limitTopLessMoney > 0){
		do{
			$limitTopLessMoney = $limitTopLessMoney  - 1;
			 $dataString5 .= $arrayForTopLessMoney[$limitTopLessMoney] . "|";
		}while($limitTopLessMoney > 1);
	   }
	   
	   $limitTopLessQuantity = count($arrayForTopLessQuantity);
	   //debug($limitTopLessQuantity);
	   if($limitTopLessQuantity > 0){
		do{
			 $limitTopLessQuantity = $limitTopLessQuantity  - 1;
			  $dataString6 .= $arrayForTopLessQuantity[$limitTopLessQuantity] . "|";
		 }while($limitTopLessQuantity > 1);
	   }
		return array(
			"quantity"=>substr($dataString2, 0, -1),
			"money"=>substr($dataString, 0, -1),
			"topMoreQuantity"=>substr($dataString4, 0, -1),
			"topMoreMoney"=>substr($dataString3, 0, -1),
			"topLessQuantity"=>substr($dataString6, 0, -1),
			"topLessMoney"=>substr($dataString5, 0, -1),
			);
	}
	
	
	
	//////////////////////////////////////////END-GRAPHICS//////////////////////////////////////////
	
	
	
	
	
	
	//////////////////////////////////////////// START - INDEX ///////////////////////////////////////////////
	
	public function index_order() {
		
		///////////////////////////////////////START - CREATING VARIABLES//////////////////////////////////////
		$filters = array();
		$doc_code = '';
		$note_code = '';
		$period = $this->Session->read('Period.name');
		///////////////////////////////////////END - CREATING VARIABLES////////////////////////////////////////
		
		////////////////////////////START - WHEN SEARCH IS SEND THROUGH POST//////////////////////////////////////
		if($this->request->is("post")) {
			$url = array('action'=>'index_order');
			$parameters = array();
			$empty=0;
			if(isset($this->request->data['SalSale']['doc_code']) && $this->request->data['SalSale']['doc_code']){
				$parameters['doc_code'] = trim(strip_tags($this->request->data['SalSale']['doc_code']));
			}else{
				$empty++;
			}
			if(isset($this->request->data['SalSale']['note_code']) && $this->request->data['SalSale']['note_code']){
				$parameters['note_code'] = trim(strip_tags($this->request->data['SalSale']['note_code']));
			}else{
				$empty++;
			}
			if($empty == 2){
				$parameters['search']='empty';
			}else{
				$parameters['search']='yes';
			}
			$this->redirect(array_merge($url,$parameters));
		}
		////////////////////////////END - WHEN SEARCH IS SEND THROUGH POST//////////////////////////////////////
		
		////////////////////////////START - SETTING URL FILTERS//////////////////////////////////////
		if(isset($this->passedArgs['doc_code'])){
			$filters['SalSale.doc_code LIKE'] = '%'.strtoupper($this->passedArgs['doc_code']).'%';
			$doc_code = $this->passedArgs['doc_code'];
		}
		if(isset($this->passedArgs['note_code'])){
			$filters['SalSale.note_code LIKE'] = '%'.strtoupper($this->passedArgs['note_code']).'%';
			$note_code = $this->passedArgs['note_code'];
		}
		////////////////////////////END - SETTING URL FILTERS//////////////////////////////////////
		
		////////////////////////////START - SETTING PAGINATING VARIABLES//////////////////////////////////////
		$this->SalSale->bindModel(array('hasOne'=>array('SalCustomer'=>array('foreignKey'=>false,'conditions'=> array('SalEmployee.sal_customer_id = SalCustomer.id')))));
		
		$this->paginate = array(
			"conditions"=>array(
				"SalSale.lc_state !="=>"NOTE_LOGIC_DELETED",
				'SalSale.lc_state LIKE'=> '%NOTE%',
				"to_char(SalSale.date,'YYYY')"=> $period,
				$filters
			 ),
			"recursive"=>0,
			"fields"=>array("SalSale.id", "SalSale.code", "SalSale.doc_code", "SalSale.date", "SalSale.note_code", "SalSale.sal_employee_id", "SalEmployee.name", "SalSale.lc_state", "SalCustomer.name"),
			"order"=> array("SalSale.id"=>"desc"),
			"limit" => 15,
		);
		////////////////////////////END - SETTING PAGINATING VARIABLES//////////////////////////////////////
		
		////////////////////////START - SETTING PAGINATE AND OTHER VARIABLES TO THE VIEW//////////////////
		$this->set('salSales', $this->paginate('SalSale'));
		$this->set('doc_code', $doc_code);
		$this->set('note_code', $note_code);
		////////////////////////END - SETTING PAGINATE AND OTHER VARIABLES TO THE VIEW//////////////////
		
		
		
//		$this->paginate = array(
//			'conditions' => array(
//				'SalSale.lc_state !='=>'NOTE_LOGIC_DELETED'
//				,'SalSale.lc_state LIKE'=> '%ORDER%'
//			),
//			'order' => array('SalSale.id' => 'desc'),
//			'limit' => 15
//		);
//		$this->SalSale->recursive = 0;
//		$this->set('salSales', $this->paginate());
	}
	
	public function index_invoice(){
		///////////////////////////////////////START - CREATING VARIABLES//////////////////////////////////////
		$filters = array();
		$doc_code = '';
		$note_code = '';
		$period = $this->Session->read('Period.name');
		///////////////////////////////////////END - CREATING VARIABLES////////////////////////////////////////
		
		////////////////////////////START - WHEN SEARCH IS SEND THROUGH POST//////////////////////////////////////
		if($this->request->is("post")) {
			$url = array('action'=>'index_invoice');
			$parameters = array();
			$empty=0;
			if(isset($this->request->data['SalSale']['doc_code']) && $this->request->data['SalSale']['doc_code']){
				$parameters['doc_code'] = trim(strip_tags($this->request->data['SalSale']['doc_code']));
			}else{
				$empty++;
			}
			if(isset($this->request->data['SalSale']['note_code']) && $this->request->data['SalSale']['note_code']){
				$parameters['note_code'] = trim(strip_tags($this->request->data['SalSale']['note_code']));
			}else{
				$empty++;
			}
			if($empty == 2){
				$parameters['search']='empty';
			}else{
				$parameters['search']='yes';
			}
			$this->redirect(array_merge($url,$parameters));
		}
		////////////////////////////END - WHEN SEARCH IS SEND THROUGH POST//////////////////////////////////////
		
		////////////////////////////START - SETTING URL FILTERS//////////////////////////////////////
		if(isset($this->passedArgs['doc_code'])){
			$filters['SalSale.doc_code LIKE'] = '%'.strtoupper($this->passedArgs['doc_code']).'%';
			$doc_code = $this->passedArgs['doc_code'];
		}
		if(isset($this->passedArgs['note_code'])){
			$filters['SalSale.note_code LIKE'] = '%'.strtoupper($this->passedArgs['note_code']).'%';
			$note_code = $this->passedArgs['note_code'];
		}
		////////////////////////////END - SETTING URL FILTERS//////////////////////////////////////
		
		////////////////////////////START - SETTING PAGINATING VARIABLES//////////////////////////////////////
		$this->SalSale->bindModel(array('hasOne'=>array('SalCustomer'=>array('foreignKey'=>false,'conditions'=> array('SalEmployee.sal_customer_id = SalCustomer.id')))));
		
		$this->paginate = array(
			"conditions"=>array(
				"SalSale.lc_state !="=>"SINVOICE_LOGIC_DELETED",
				'SalSale.lc_state LIKE'=> '%SINVOICE%',
				"to_char(SalSale.date,'YYYY')"=> $period,
				$filters
			 ),
			"recursive"=>0,
			"fields"=>array("SalSale.id", "SalSale.code", "SalSale.doc_code", "SalSale.date", "SalSale.note_code", "SalSale.sal_employee_id", "SalEmployee.name", "SalSale.lc_state", "SalCustomer.name"),
			"order"=> array("SalSale.id"=>"desc"),
			"limit" => 15,
		);
		////////////////////////////END - SETTING PAGINATING VARIABLES//////////////////////////////////////
		
		////////////////////////START - SETTING PAGINATE AND OTHER VARIABLES TO THE VIEW//////////////////
		$this->set('salSales', $this->paginate('SalSale'));
		$this->set('doc_code', $doc_code);
		$this->set('note_code', $note_code);
		////////////////////////END - SETTING PAGINATE AND OTHER VARIABLES TO THE VIEW//////////////////
	}
	
	///////////////////////////////////////////// END - INDEX ////////////////////////////////////////////////
	
	//////////////////////////////////////////// START - SAVE ///////////////////////////////////////////////
	
	public function save_order(){
		$id = '';
		if(isset($this->passedArgs['id'])){
			$id = $this->passedArgs['id'];
		}
		$this->loadModel('AdmParameter');
		$currency = $this->AdmParameter->AdmParameterDetail->find('first', array(
				'conditions'=>array(
					'AdmParameter.name'=>'Moneda',
					'AdmParameterDetail.par_char1'=>'Dolares'
				)
			)); 
		$currencyId = $currency['AdmParameterDetail']['id'];
		$this->loadModel('AdmUser');
		$salAdmUsers = $this->AdmUser->AdmProfile->find('list', array(
			'order'=>array('first_name'),
			'fields'=>array('adm_user_id','full_name')
			)); 
		//array_unshift($salAdmUsers,"Sin Vendedor"); //REVISAR ESTO ARRUINA EL CODIGO Q BOTA EL DROPDOWN
		$salCustomers = $this->SalSale->SalEmployee->SalCustomer->find('list'/*, array('conditions'=>array('SalCustomer.location'=>'COCHABAMBA'))*/);
		$customer = key($salCustomers);
		$salEmployees = $this->SalSale->SalEmployee->find('list', array('conditions'=>array('SalEmployee.sal_customer_id'=>$customer)));
		$salTaxNumbers = $this->SalSale->SalTaxNumber->find('list', array('conditions'=>array('SalTaxNumber.sal_customer_id'=>$customer)));
			
		$this->SalSale->recursive = -1;
		$this->request->data = $this->SalSale->read(null, $id);
	//	$date='';
		$date=date('d/m/Y');
		////////////////////////////to find the previous last currency value
		$this->loadModel('AdmExchangeRate');
		$rateDirty = $this->AdmExchangeRate->find('first', array(
				'fields'=>array('AdmExchangeRate.value'),
				'order' => array('AdmExchangeRate.date' => 'desc'),
				'conditions'=>array(
					'AdmExchangeRate.currency'=>$currencyId,
					'AdmExchangeRate.date <='=>$date
				),
				'recursive'=>-1
			)); 		
		if($rateDirty == array() || $rateDirty['AdmExchangeRate']['value'] == null){
			$exRate = '';
		}else{		
			$exRate = $rateDirty['AdmExchangeRate']['value'];	
		}
		////////////////////////////to find the previous last currency value
		$genericCode ='';
		$salDetails = array();
		$documentState = '';
		$customerId = '';
		$admUserId = '';
		$discount  = 0;
		if($id <> null){
			$date = date("d/m/Y", strtotime($this->request->data['SalSale']['date']));
			$salDetails = $this->_get_movements_details($id);
			$documentState =$this->request->data['SalSale']['lc_state'];
			$genericCode = $this->request->data['SalSale']['code'];
			
			$employeeId = $this->request->data['SalSale']['sal_employee_id'];
			$customerId = $this->SalSale->SalEmployee->find('list', array('fields'=>array('SalEmployee.sal_customer_id'),'conditions'=>array('SalEmployee.id'=>$employeeId)));
			$salEmployees = $this->SalSale->SalEmployee->find('list', array('conditions'=>array('SalEmployee.sal_customer_id'=>$customerId)));
			$salTaxNumbers = $this->SalSale->SalTaxNumber->find('list', array('conditions'=>array('SalTaxNumber.sal_customer_id'=>$customerId)));		
			
			$admUserId = $this->request->data['SalSale']['salesman_id'];
			$exRate = $this->request->data['SalSale']['ex_rate'];
			$discount = $this->request->data['SalSale']['discount'];
		}
		$this->set(compact('salCustomers','customerId', 'salTaxNumbers', 'salEmployees','employeeId', 'salAdmUsers', 'admUserId','id', 'date', 'salDetails', 'documentState', 'genericCode', 'exRate', 'discount'));
	}
	
	public function save_invoice(){
		$id = '';
		if(isset($this->passedArgs['id'])){
			$id = $this->passedArgs['id'];
		}
		$this->loadModel('AdmParameter');
		$currency = $this->AdmParameter->AdmParameterDetail->find('first', array(
				'conditions'=>array(
					'AdmParameter.name'=>'Moneda',
					'AdmParameterDetail.par_char1'=>'Dolares'
				)
			)); 
		$currencyId = $currency['AdmParameterDetail']['id'];
		$this->loadModel('AdmUser');
		$salAdmUsers = $this->AdmUser->AdmProfile->find('list', array(
			'order'=>array('first_name'),
			'fields'=>array('adm_user_id','full_name')
		)); 
	
		$salCustomers = $this->SalSale->SalEmployee->SalCustomer->find('list');
		$customer = key($salCustomers);
		$salEmployees = $this->SalSale->SalEmployee->find('list', array('conditions'=>array('SalEmployee.sal_customer_id'=>$customer)));
	//	$taxNumber = key($salCustomers);
		$salTaxNumbers = $this->SalSale->SalTaxNumber->find('list', array('conditions'=>array('SalTaxNumber.sal_customer_id'=>$customer)));
	
				
		$this->SalSale->recursive = -1;
		$this->request->data = $this->SalSale->read(null, $id);
		$date='';
		$genericCode ='';
		$originCode = '';
		$customerId = '';
		$admUserId = '';
		//debug($this->request->data);
		$salDetails = array();
		$salPayments = array();
//		$purPrices = array();
		$documentState = '';
		////////////////////////////to find the previous last currency value
		$this->loadModel('AdmExchangeRate');
		$rateDirty = $this->AdmExchangeRate->find('first', array(
				'fields'=>array('AdmExchangeRate.value'),
				'order' => array('AdmExchangeRate.date' => 'desc'),
				'conditions'=>array(
					'AdmExchangeRate.currency'=>$currencyId,
					'AdmExchangeRate.date <='=>$date
				),
				'recursive'=>-1
			)); 		
		if($rateDirty == array() || $rateDirty['AdmExchangeRate']['value'] == null){
			$exRate = '';
		}else{		
			$exRate = $rateDirty['AdmExchangeRate']['value'];	
		}
		////////////////////////////to find the previous last currency value
		$discount  = 0;
		if($id <> null){
			$date = date("d/m/Y", strtotime($this->request->data['SalSale']['date']));
			$salDetails = $this->_get_movements_details($id);
			$salPayments = $this->_get_pays_details($id);
			$documentState =$this->request->data['SalSale']['lc_state'];
			$genericCode = $this->request->data['SalSale']['code'];
			//buscar el codigo del documento origen
			$originDocCode = $this->SalSale->find('first', array(
				'fields'=>array('SalSale.doc_code'),
				'conditions'=>array(
					'SalSale.code'=>$genericCode,
					'SalSale.lc_state LIKE'=> '%NOTE%'
					)
			));
			$originCode = $originDocCode['SalSale']['doc_code'];
			$employeeId = $this->request->data['SalSale']['sal_employee_id'];
			$customerId = $this->SalSale->SalEmployee->find('list', array('fields'=>array('SalEmployee.sal_customer_id'),'conditions'=>array('SalEmployee.id'=>$employeeId)));
			$salEmployees = $this->SalSale->SalEmployee->find('list', array('conditions'=>array('SalEmployee.sal_customer_id'=>$customerId)));
			$salTaxNumbers = $this->SalSale->SalTaxNumber->find('list', array('conditions'=>array('SalTaxNumber.sal_customer_id'=>$customerId)));		
			
			$admUserId = $this->request->data['SalSale']['salesman_id'];
//			$admUserId = $this->AdmUser->AdmProfile->find('list', array('fields'=>array('AdmProfile.id'),'conditions'=>array('AdmProfile.adm_user_id'=>$admProfileId)));
			$exRate = $this->request->data['SalSale']['ex_rate'];
			$discount = $this->request->data['SalSale']['discount'];
		}
		
			
		$this->set(compact('salCustomers','customerId', 'salTaxNumbers', 'salEmployees','employeeId', 'salAdmUsers', 'admUserId','id', 'date', 'salDetails', 'salPayments', 'documentState', 'genericCode', 'originCode', 'exRate', 'discount'));
//debug($this->request->data);
	}
	
	//////////////////////////////////////////// END - SAVE /////////////////////////////////////////////////
	
	//////////////////////////////////////////// START - AJAX ///////////////////////////////////////////////
	
	public function ajax_initiate_modal_add_item_in(){
		if($this->RequestHandler->isAjax()){
						
			$itemsAlreadySaved = $this->request->data['itemsAlreadySaved'];
			$warehouseItemsAlreadySaved = $this->request->data['warehouseItemsAlreadySaved'];
			$date = $this->request->data['date'];
			
			$invWarehouses = $this->SalSale->SalDetail->InvItem->InvMovementDetail->InvMovement->InvWarehouse->find('list');
			
			$warehouse = key($invWarehouses);
			
			$itemsAlreadySavedInWarehouse = array();
			for($i=0; $i<count($itemsAlreadySaved); $i++){
				if($warehouseItemsAlreadySaved[$i] == $warehouse){
					$itemsAlreadySavedInWarehouse[] = $itemsAlreadySaved[$i];
				}	
			}
			
			$items = $this->SalSale->SalDetail->InvItem->find('list', array(
				'conditions'=>array(
					'NOT'=>array('InvItem.id'=>$itemsAlreadySavedInWarehouse)
				),
				'recursive'=>-1,
				'order'=>array('InvItem.code')
			));
			
			$firstItemListed = key($items);
			
			//$stock = $this->_find_stock($firstItemListed, $warehouse);
			$stocks = $this->_get_stocks($firstItemListed, $warehouse);
			$stock = $this->_find_item_stock($stocks, $firstItemListed);
			//////////////////////CAMBIAR POR EL ALGORITMO QUE SACA EL PRECIO PRORRATEADO////////////////
			$priceDirty = $this->SalSale->SalDetail->InvItem->InvPrice->find('first', array(
				'fields'=>array('InvPrice.price'),
				'order' => array('InvPrice.date' => 'desc'),
				'conditions'=>array(
					'InvPrice.inv_item_id'=>$firstItemListed
					,'InvPrice.inv_price_type_id'=>9
					,'InvPrice.date <='=>$date
					)
			));
//			debug($priceDirty);
			if($priceDirty == array() || $priceDirty['InvPrice']['price'] == null){
				$price ='';
			}  else {

				$price = $priceDirty['InvPrice']['price'];
			}
			//////////////////////CAMBIAR POR EL ALGORITMO QUE SACA EL PRECIO PRORRATEADO////////////////
				$this->set(compact('items', 'price', 'invWarehouses', 'stock', 'warehouse'));
			}
	}
	
	public function ajax_update_stock_modal(){
		if($this->RequestHandler->isAjax()){
			$item = $this->request->data['item'];
			$date = $this->request->data['date'];
			//////////////////////CAMBIAR POR EL ALGORITMO QUE SACA EL PRECIO PRORRATEADO////////////////
			$priceDirty = $this->SalSale->SalDetail->InvItem->InvPrice->find('first', array(
			'fields'=>array('InvPrice.price'),
			'order' => array('InvPrice.date_created' => 'desc'),
			'conditions'=>array(
				'InvPrice.inv_item_id'=>$item
				,'InvPrice.inv_price_type_id'=>9
				,'InvPrice.date <='=>$date
				)
			));
			if($priceDirty==array()){
			$price ='';
			}else{
			
			$price = $priceDirty['InvPrice']['price'];
			}
			//////////////////////CAMBIAR POR EL ALGORITMO QUE SACA EL PRECIO PRORRATEADO////////////////
			$this->set(compact('price'));
		}
	}
	
	
	public function ajax_update_stock_modal_1(){
		if($this->RequestHandler->isAjax()){
			$item = $this->request->data['item'];
			$warehouse = $this->request->data['warehouse'];
			
			$stock = $this->_find_stock($item, $warehouse);			
			
			$this->set(compact('stock'));
		}
	}
	
	public function ajax_list_controllers_inside(){
		if($this->RequestHandler->isAjax()){
			$customer = $this->request->data['customer']; //???????????????????
		//	print_r( $customer);
		//	$admControllers = $this->AdmMenu->AdmAction->AdmController->find('list', array('conditions'=>array('AdmController.adm_module_id'=>$module)));
			$salEmployees = $this->SalSale->SalEmployee->find('list', array('conditions'=>array('SalEmployee.sal_customer_id'=>$customer)));
			$salTaxNumbers = $this->SalSale->SalTaxNumber->find('list', array('conditions'=>array('SalTaxNumber.sal_customer_id'=>$customer)));

		//	print_r( $salEmployees);
		//	$controller = key($admControllers);
		//	$employee = key($salEmployees);
			//$admActions = $this->AdmMenu->AdmAction->find('list', array('conditions'=>array('AdmAction.adm_controller_id'=>$controller)));
		//	$admActions = $this->_list_action_inside($controller);
			$this->set(compact('salEmployees', 'salTaxNumbers'/*'admControllers','admActions'*/));			
		}else{
			$this->redirect($this->Auth->logout());
		}
	}	
	
	public function ajax_update_items_modal(){
		if($this->RequestHandler->isAjax()){
			$itemsAlreadySaved = $this->request->data['itemsAlreadySaved'];
			$warehouseItemsAlreadySaved = $this->request->data['warehouseItemsAlreadySaved'];
			$warehouse = $this->request->data['warehouse'];
			$date = $this->request->data['date'];
			
			$itemsAlreadySavedInWarehouse = array();
			for($i=0; $i<count($itemsAlreadySaved); $i++){
				if($warehouseItemsAlreadySaved[$i] == $warehouse){
					$itemsAlreadySavedInWarehouse[] = $itemsAlreadySaved[$i];
				}	
			}
			
			$items = $this->SalSale->SalDetail->InvItem->find('list', array(
				'conditions'=>array(
					'NOT'=>array('InvItem.id'=>$itemsAlreadySavedInWarehouse)
				),
				'recursive'=>-1,
				'order'=>array('InvItem.code')
			));
			
			$item = key($items);
			//////////////////////CAMBIAR POR EL ALGORITMO QUE SACA EL PRECIO PRORRATEADO////////////////
			$priceDirty = $this->SalSale->SalDetail->InvItem->InvPrice->find('first', array(
			'fields'=>array('InvPrice.price'),
			'order' => array('InvPrice.date_created' => 'desc'),
			'conditions'=>array(
				'InvPrice.inv_item_id'=>$item
				,'InvPrice.inv_price_type_id'=>9
				,'InvPrice.date <='=>$date
				)
			));
			if($priceDirty==array()){
			$price = '';
			}  else {
			
			$price = $priceDirty['InvPrice']['price'];
			}
			//////////////////////CAMBIAR POR EL ALGORITMO QUE SACA EL PRECIO PRORRATEADO////////////////
			//$stock = $this->_find_stock($item, $warehouse);
			$stocks = $this->_get_stocks($item, $warehouse);
			$stock = $this->_find_item_stock($stocks, $item);
			$this->set(compact('items', 'price', 'stock'));
		}
	}
	
	public function ajax_save_movement(){
		if($this->RequestHandler->isAjax()){
			////////////////////////////////////////////START - RECIEVE AJAX////////////////////////////////////////////////////////
			//For making algorithm
			$ACTION = $this->request->data['ACTION'];
			$OPERATION= $this->request->data['OPERATION'];
			$STATE = $this->request->data['STATE'];//also for Movement
			$OPERATION3 = $OPERATION;
			$OPERATION4 = $OPERATION;
			//Sale
			$purchaseId = $this->request->data['purchaseId'];
			$movementDocCode = $this->request->data['movementDocCode'];
			$movementCode = $this->request->data['movementCode'];
			$noteCode = $this->request->data['noteCode'];
			$date = $this->request->data['date'];
			$employee = $this->request->data['employee'];
			$taxNumber = $this->request->data['taxNumber'];
			$salesman = $this->request->data['salesman'];
			///////////////////////////////////////////////////////
//			$this->loadModel('AdmUser');
//			$admUserId = $this->AdmUser->AdmProfile->find('list', array(
//			'fields'=>array('AdmProfile.adm_user_id'),
//			'conditions'=>array('AdmProfile.id'=>$admProfileId)
//			));
//			
//			$salesman = key($this->AdmUser->find('list', array(
//			'conditions'=>array('AdmUser.id'=>$admUserId)
//			)));
			///////////////////////////////////////////////////////
			$description = $this->request->data['description'];
			$exRate = $this->request->data['exRate'];
			$discount = $this->request->data['discount'];
//			if ($discount == null){
//				$discount = 0;
//			}
//			debug($discount);
			//Sale Details
			$warehouseId = $this->request->data['warehouseId'];
			$itemId = $this->request->data['itemId'];
			$salePrice = $this->request->data['salePrice'];
			$quantity = $this->request->data['quantity'];
//			$cifPrice =  $this->_get_price($itemId, $date, 'CIF', 'bs');//$this->request->data['cifPrice'];
//			$exCifPrice = $this->_get_price($itemId, $date, 'CIF', 'dolar');//$this->request->data['exCifPrice'];
			//For prices IF DETAILS ARE PASSED / IF ACTION ADD OR EDIT
//			$exFobPrice =  $this->_get_price($itemId, $date, 'FOB', 'dolar');
//			$fobPrice =  $exFobPrice * $exRate;//$this->_get_price($itemId, $date, 'FOB', 'bs');
			$exSalePrice = $salePrice / $exRate;
			if ($ACTION == 'save_invoice' && $STATE == 'SINVOICE_APPROVED'){
				$arrayItemsDetails = $this->request->data['arrayItemsDetails'];	
			}
			if (($ACTION == 'save_invoice' && $OPERATION == 'ADD_PAY') || ($ACTION == 'save_invoice' && $OPERATION == 'EDIT_PAY') || ($ACTION == 'save_invoice' && $OPERATION == 'DELETE_PAY')) {
//				$dateId = $this->request->data['dateId'];
				$payDate = $this->request->data['payDate'];
				$payAmount = $this->request->data['payAmount'];
				$payDescription = $this->request->data['payDescription'];
			}
			//For validate before approve OUT or cancelled IN
			$arrayForValidate = array();
			if(isset($this->request->data['arrayForValidate'])){$arrayForValidate = $this->request->data['arrayForValidate'];}
			//Internal variables
			$error=0;
			$movementDocCode3 = '';
			$movementDocCode4 = '';
			////////////////////////////////////////////END - RECIEVE AJAX////////////////////////////////////////////////////////
			
			////////////////////////////////////////////////START - SET DATA/////////////////////////////////////////////////////
			$arrayMovement['note_code']=$noteCode;
			$arrayMovement['date']=$date;
			$arrayMovement['sal_employee_id']=$employee;
			$arrayMovement['sal_tax_number_id']=$taxNumber;
			$arrayMovement['salesman_id']=$salesman;
			$arrayMovement['description']=$description;
			$arrayMovement['ex_rate']=$exRate;
			$arrayMovement['discount']=$discount;
			$arrayMovement['lc_state']=$STATE;
			if ($ACTION == 'save_order'){
				//header for invoice
				$arrayMovement2['note_code']=$noteCode;
				$arrayMovement2['date']=$date;
				$arrayMovement2['sal_employee_id']=$employee;
				$arrayMovement2['sal_tax_number_id']=$taxNumber;
				$arrayMovement2['salesman_id']=$salesman;
				$arrayMovement2['description']=$description;
				$arrayMovement2['ex_rate']=$exRate;
				$arrayMovement2['discount']=$discount;
				//header for movement
				$arrayMovement3['date']=$date;
				$arrayMovement3['inv_warehouse_id']=$warehouseId;
				$arrayMovement3['inv_movement_type_id']=2;
				$arrayMovement3['description']=$description;
				
				if ($STATE == 'NOTE_APPROVED') {
					$arrayMovement2['lc_state']='SINVOICE_PENDANT';
				}elseif ($STATE == 'NOTE_PENDANT') {
					$arrayMovement2['lc_state']='DRAFT';
					$arrayMovement3['lc_state']='DRAFT';
					$arrayMovement4['lc_state']='DRAFT';
//					debug($arrayMovement3['lc_state']);
				}
			}elseif(($ACTION == 'save_invoice' && $OPERATION == 'ADD_PAY') || ($ACTION == 'save_invoice' && $OPERATION == 'EDIT_PAY') || ($ACTION == 'save_invoice' && $OPERATION == 'DELETE_PAY')){
				$arrayPayDetails = array('sal_payment_type_id'=>1, 
										'date'=>$payDate,
										//'description'=>"'$payDescription'",
										'description'=>$payDescription,
										'amount'=>$payAmount, 'ex_amount'=>($payAmount / $exRate)
										);
			}elseif ($ACTION == 'save_invoice') {
				//header for movement
				$arrayMovement3['date']=$date;
				$arrayMovement3['inv_warehouse_id']=$warehouseId;
				$arrayMovement3['inv_movement_type_id']=2;
				$arrayMovement3['description']=$description;
				if ($STATE == 'SINVOICE_PENDANT') {
					$arrayMovement3['lc_state']='PENDANT';//ESTO ESTA SOBREESCRITO POR LO Q DIGA $arrayMovement5
					$arrayMovement4['lc_state']='PENDANT';//ESTO ESTA SOBREESCRITO POR LO Q DIGA $arrayMovement5
				}
			}			
			$arrayMovementDetails = array('inv_warehouse_id'=>$warehouseId, 
										'inv_item_id'=>$itemId,
										'sale_price'=>$salePrice, 'ex_sale_price'=>$exSalePrice,
										'quantity'=>$quantity, 
										/*'cif_price'=>$cifPrice, 'ex_cif_price'=>$exCifPrice, 
										'fob_price'=>$fobPrice, 'ex_fob_price'=>$exFobPrice*/);
			if ($ACTION == 'save_order'){
				$stocks = $this->_get_stocks($itemId, $warehouseId);
				$stock = $this->_find_item_stock($stocks, $itemId);
				$arrayMovement3['type']=1;

				$arrayMovement4['date']=$date;
				$arrayMovement4['inv_warehouse_id']=$warehouseId;
				$arrayMovement4['inv_movement_type_id']=2;
				$arrayMovement4['description']=$description;
				$arrayMovement4['type']=2;
				$surplus = $quantity - $stock;
				if($quantity > $stock){
					$arrayMovementDetails3 = array('inv_item_id'=>$itemId, 'quantity'=>$stock);
					if($stock !== 0){
						$OPERATION4 = 'ADD';
					}
				}else{
					$arrayMovementDetails3 = array('inv_item_id'=>$itemId, 'quantity'=>$quantity);
				}	
				$arrayMovementDetails4 = array('inv_item_id'=>$itemId, 'quantity'=>$surplus);
			}elseif ($ACTION == 'save_invoice' && $OPERATION != 'ADD_PAY' && $OPERATION != 'EDIT_PAY' && $OPERATION != 'DELETE_PAY') {
				$stocks = $this->_get_stocks($itemId, $warehouseId);
				$stock = $this->_find_item_stock($stocks, $itemId);
				$arrayMovement3['type']=1;
				$arrayMovement4['date']=$date;
				$arrayMovement4['inv_warehouse_id']=$warehouseId;
				$arrayMovement4['inv_movement_type_id']=2;
				$arrayMovement4['description']=$description;
				$arrayMovement4['type']=2;
				$surplus = $quantity - $stock;
				if($quantity > $stock){
					$arrayMovementDetails3 = array('inv_item_id'=>$itemId, 'quantity'=>$stock);
					if($stock !== 0){
						$OPERATION4 = 'ADD';
					}	
				}else{
					$arrayMovementDetails3 = array('inv_item_id'=>$itemId, 'quantity'=>$quantity);
				}	
				$arrayMovementDetails4 = array('inv_item_id'=>$itemId, 'quantity'=>$surplus);
			}
			//INSERT OR UPDATE
			if($purchaseId == ''){//INSERT
				switch ($ACTION) {
					case 'save_order':
						//SALES NOTE
						$movementCode = $this->_generate_code('VEN');
						$movementDocCode = $this->_generate_doc_code('NOT');
						$arrayMovement['code'] = $movementCode;
						$arrayMovement['doc_code'] = $movementDocCode;
						//SALES INVOICE
						$movementDocCode2 = 'NO';
						$arrayMovement2['code'] = $movementCode;
						$arrayMovement2['doc_code'] = $movementDocCode2;
						//MOVEMENT type 1(hay stock)
						$arrayMovement3['document_code'] = $movementCode;
						$arrayMovement3['code'] = $movementDocCode2;
						//MOVEMENT type 2(NO hay stock)
						$arrayMovement4['document_code'] = $movementCode;
						$arrayMovement4['code'] = $movementDocCode2;
						break;
				}
				if($movementCode == 'error'){$error++;}
				if($movementDocCode == 'error'){$error++;}
				if($movementDocCode2 == 'error'){$error++;}
			}else{//UPDATE
				//sale note id
				$arrayMovement['id'] = $purchaseId;
				if ($ACTION == 'save_order'){
					//sale invoice id
					$arrayMovement2['id'] = $this->_get_doc_id($purchaseId, $movementCode, null, null);
					//movement id type 1(hay stock)
					$arrayMovement3['id'] = $this->_get_doc_id(null, $movementCode, 1, $warehouseId);
					if($arrayMovement3['id'] === null){
						$arrayMovement3['document_code'] = $movementCode;
						$arrayMovement3['code'] = 'NO';
					}
					//movement id type 2(NO hay stock)
					$arrayMovement4['id'] = $this->_get_doc_id(null, $movementCode, 2, $warehouseId);
					if(($arrayMovement4['id'] === null) && ($quantity > $stock)){
						$arrayMovement4['document_code'] = $movementCode;
						$arrayMovement4['code'] = 'NO';
					}
					if($quantity > $stock){//CHEKAR BIEN ESTO, CREO Q YA NO VA!!!
						$arrayMovement4['document_code'] = $movementCode;
						$arrayMovement4['code'] = 'NO';
					}
//					Para eliminar el detalle que ocupaba la HEAD type 2 					
					if(($arrayMovement4['id'] <> null) && ($quantity <= $stock)){
						$OPERATION4 = 'DELETE';
					}
					if ($STATE == 'NOTE_APPROVED') {
						//FOR INVOICE
						$movementDocCode2 = $this->_generate_doc_code('VFA');
						$arrayMovement2['doc_code'] = $movementDocCode2;
					}
				}elseif ($ACTION == 'save_invoice' && $OPERATION != 'ADD_PAY' && $OPERATION != 'EDIT_PAY' && $OPERATION != 'DELETE_PAY') {
					//movement id type 1(hay stock)
					$arrayMovement3['id'] = $this->_get_doc_id(null, $movementCode, 1, $warehouseId);
					if($arrayMovement3['id'] === null){//SI NO HAY EL DOCUMENTO (CON STOCK) SE CREA
						$arrayMovement3['document_code'] = $movementCode;
						$movementDocCode3 = $this->_generate_movement_code('SAL',null);
						$arrayMovement3['code'] = $movementDocCode3;//'NO';
					}
					//movement id type 2(NO hay stock)
					$arrayMovement4['id'] = $this->_get_doc_id(null, $movementCode, 2, $warehouseId);
					if(($arrayMovement4['id'] === null) && ($quantity > $stock)){//SI NO HAY EL DOCUMENTO (SIN STOCK), Y LA CANTIDAD SOBREPASA EL STOCK SE CREA
						$arrayMovement4['document_code'] = $movementCode;
						$movementDocCode4 = $this->_generate_movement_code('SAL',null);
						$arrayMovement4['code'] = $movementDocCode4;//'NO';
					}
//					if($quantity > $stock){
//						$arrayMovement4['document_code'] = $movementCode;
//						$movementDocCode4 = $this->_generate_movement_code('SAL',null);
//						$arrayMovement4['code'] = $movementDocCode4;//'NO';
//					}
//					Para eliminar el detalle que ocupaba la HEAD type 2
					if(($arrayMovement4['id'] <> null) && ($quantity <= $stock)){
						$OPERATION4 = 'DELETE';
					}
				}
				if($movementDocCode3 == 'error'){$error++;}
				if($movementDocCode4 == 'error'){$error++;}
			}
			//-------------------------FOR DELETING HEAD ON MOVEMENTS RELATED ON save_order--------------------------------
//			if(($ACTION == 'save_order' && $OPERATION3 == 'DELETE') || ($ACTION == 'save_order' && $OPERATION4 == 'DELETE')){	
			$arrayMovement6 = null;	
			$rest3 = null;
			$rest4 = null;																		//VER SI ESTA V RESTRICCION NO INCLUYE OTRAS OPERACIONES MAS ??????????					
			if(($ACTION == 'save_order' && $OPERATION3 == 'DELETE' && $OPERATION4 == 'DELETE')||($ACTION == 'save_order' && $OPERATION3 == 'EDIT' && $OPERATION4 == 'DELETE')){//TOMANDO EN CUENTA QUE SIEMPRE QUE $OPERATION3 == 'DELETE' TAMBIEN $OPERATION4 == 'DELETE' Y VICEVERSA
				if (($arrayMovement3['id'] !== null && $arrayMovementDetails3['inv_item_id'] !== null && $OPERATION3 == 'DELETE') ){
					$rest3 = $this->InvMovement->InvMovementDetail->find('count', array(
						'conditions'=>array(
							'NOT'=>array(
								'AND'=>array(
									'InvMovementDetail.inv_movement_id'=>$arrayMovement3['id']
									,'InvMovementDetail.inv_item_id'=>$arrayMovementDetails3['inv_item_id']
									)
								)
							,'InvMovementDetail.inv_movement_id'=>$arrayMovement3['id']
							),
						'recursive'=>0
					));
				}
				if (($arrayMovement4['id'] !== null && $arrayMovementDetails4['inv_item_id'] !== null && $OPERATION4 == 'DELETE')){
					$rest4 = $this->InvMovement->InvMovementDetail->find('count', array(
						'conditions'=>array(
							'NOT'=>array(
								'AND'=>array(
									'InvMovementDetail.inv_movement_id'=>$arrayMovement4['id']
									,'InvMovementDetail.inv_item_id'=>$arrayMovementDetails4['inv_item_id']
									)
								)
							,'InvMovementDetail.inv_movement_id'=>$arrayMovement4['id']
							),
						'recursive'=>0
					));
				}	
				if(($rest3 === 0) && ($rest4 === 0) && ($arrayMovement3['id'] !== null) && ($arrayMovement4['id'] !== null)){
					$arrayMovement6 = array(
						array('InvMovement.id' => array($arrayMovement3['id'],$arrayMovement4['id']))
					);
				}elseif(($rest3 === 0) && ($arrayMovement3['id'] !== null)){
					$arrayMovement6 = array(
						array('InvMovement.id' => $arrayMovement3['id'])
					);
				}elseif(($rest4 === 0) && ($arrayMovement4['id'] !== null)){
					$arrayMovement6 = array(
						 array('InvMovement.id' => $arrayMovement4['id'])
					);
				}
//				else{
//					$arrayMovement6 = null;
//				}
			}
			//---------------------------FOR DELETING HEAD ON MOVEMENTS RELATED ON save_order------------------------------
//			-------------------------FOR UPDATING HEAD ON DELETED MOVEMENTS ON save_invoice--------------------------------
//			if(($ACTION == 'save_invoice' && $OPERATION3 == 'DELETE') || ($ACTION == 'save_invoice' && $OPERATION4 == 'DELETE')){	
			$draftId3 = null;
			$draftId4 = null;																		//VER SI ESTA V RESTRICCION NO INCLUYE OTRAS OPERACIONES MAS ??????????			
			if(($ACTION == 'save_invoice' && $OPERATION3 == 'DELETE' && $OPERATION4 == 'DELETE')||($ACTION == 'save_invoice' && $OPERATION3 == 'EDIT' && $OPERATION4 == 'DELETE')){//TOMANDO EN CUENTA QUE SIEMPRE QUE $OPERATION3 == 'DELETE' TAMBIEN $OPERATION4 == 'DELETE' Y VICEVERSA
				if (($arrayMovement3['id'] !== null && $arrayMovementDetails3['inv_item_id'] !== null  && $OPERATION3 == 'DELETE')){
					$rest3 = $this->InvMovement->InvMovementDetail->find('count', array(
						'conditions'=>array(
							'NOT'=>array(
								'AND'=>array(
									'InvMovementDetail.inv_movement_id'=>$arrayMovement3['id']
									,'InvMovementDetail.inv_item_id'=>$arrayMovementDetails3['inv_item_id']
									)
								)
							,'InvMovementDetail.inv_movement_id'=>$arrayMovement3['id']
							),
						'recursive'=>0
					));
				}
				if (($arrayMovement4['id'] !== null && $arrayMovementDetails4['inv_item_id'] !== null && $OPERATION4 == 'DELETE')){
					$rest4 = $this->InvMovement->InvMovementDetail->find('count', array(
						'conditions'=>array(
							'NOT'=>array(
								'AND'=>array(
									'InvMovementDetail.inv_movement_id'=>$arrayMovement4['id']
									,'InvMovementDetail.inv_item_id'=>$arrayMovementDetails4['inv_item_id']
									)
								)
							,'InvMovementDetail.inv_movement_id'=>$arrayMovement4['id']
							),
						'recursive'=>0
					));
				}	
				if(($rest3 === 0) && ($rest4 === 0) && ($arrayMovement3['id'] !== null) && ($arrayMovement4['id'] !== null)){
					$draftId3 = $arrayMovement3['id'];
					$draftId4 = $arrayMovement4['id'];
//					echo "<br>1<br>";
//					debug($draftId3);
//					debug($draftId4);
				}elseif(($rest3 === 0) && ($arrayMovement3['id'] !== null)){
					$draftId3 = $arrayMovement3['id'];
//					$draftId4 = null;
//					echo "<br>2<br>";
//					debug($draftId3);
				}elseif(($rest4 === 0) && ($arrayMovement4['id'] !== null)){
					$draftId4 = $arrayMovement4['id'];
//					$draftId3 = null;
//					echo "<br>3<br>";
//					debug($draftId4);
				}
//				else{
//					$draftId3 = null;
//					$draftId4 = null;
//				}
			}
//			---------------------------FOR UPDATING HEAD ON DELETED MOVEMENTS ON save_invoice------------------------------
			//*********************************************************MAKE AN IF WHEN $STATE == DEFAULT
			$this->loadModel('InvMovement');
			$arrayMovement5 = $this->InvMovement->find('all', array(
				'fields'=>array(
					'InvMovement.id'
//					,'InvMovement.date'
//					,'InvMovement.description'
//					,'InvMovement.lc_state'
//					,'InvMovement.inv_warehouse_id'
					),
				'conditions'=>array(
						'InvMovement.document_code'=>$movementCode
					)
				,'order' => array('InvMovement.id' => 'ASC')
				,'recursive'=>0
			));
			if(($arrayMovement5 <> null)&&($STATE == 'NOTE_CANCELLED')){
				for($i=0;$i<count($arrayMovement5);$i++){
					$arrayMovement5[$i]['InvMovement']['lc_state'] = 'DRAFT';
				}
			}elseif(($arrayMovement5 <> null)&&($STATE == 'NOTE_APPROVED')) {
				for($i=0;$i<count($arrayMovement5);$i++){
					$movementDocCode5 = $this->_generate_movement_code('SAL','inc');
					$arrayMovement5[$i]['InvMovement']['lc_state']='PENDANT';
					$arrayMovement5[$i]['InvMovement']['code'] = $movementDocCode5;
					$arrayMovement5[$i]['InvMovement']['date'] = $date;
					$arrayMovement5[$i]['InvMovement']['description'] = $description;
				}
			}elseif($arrayMovement5 <> null){
				for($i=0;$i<count($arrayMovement5);$i++){
					$arrayMovement5[$i]['InvMovement']['date'] = $date;
					$arrayMovement5[$i]['InvMovement']['description'] = $description;
					/////////////////////////////////////////////////////////////////
					if(($ACTION == 'save_invoice' && $OPERATION3 == 'DELETE') || ($ACTION == 'save_invoice' && $OPERATION4 == 'DELETE')){		
						if($arrayMovement5[$i]['InvMovement']['id'] === $draftId3){
							$arrayMovement5[$i]['InvMovement']['lc_state']='DRAFT';
						}
						if($arrayMovement5[$i]['InvMovement']['id'] === $draftId4){
							$arrayMovement5[$i]['InvMovement']['lc_state']='DRAFT';
						}
					}	
					/////////////////////////////////////////////////////////////////
				}
			}
			//*********************************************************
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//			if ($ACTION == 'save_invoice' && $STATE == 'SINVOICE_PENDANT'){
//				if($draftId3 == null){
//					$arrayMovement3['lc_state']='PENDANT';
//				}
//				if($draftId4 == null){
//					$arrayMovement4['lc_state']='PENDANT';
//				}
//			}
			//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			$dataMovement[0] = array('SalSale'=>$arrayMovement);
			if ($ACTION == 'save_order'){
				$this->loadModel('InvMovement');
				//for invoice
//				$dataMovement[1] = array('SalSale'=>$arrayMovement2);
				//for movement
				$dataMovement3 = array('InvMovement'=>$arrayMovement3);
				$dataMovementDetail3 = array('InvMovementDetail'=> $arrayMovementDetails3);
				$dataMovement4 = array('InvMovement'=>$arrayMovement4);
				$dataMovementDetail4 = array('InvMovementDetail'=> $arrayMovementDetails4);
				if($arrayMovement5 <> null){
					$dataMovement5 = $arrayMovement5;
				}	
				if((($ACTION == 'save_order' && $OPERATION3 == 'DELETE' && $arrayMovement6 <> null) || ($ACTION == 'save_order' && $OPERATION4 == 'DELETE' && $arrayMovement6 <> null)) ){
					$dataMovement6 = $arrayMovement6;
				}	
				$dataPayDetail = null;
			}elseif (($ACTION == 'save_invoice' && $OPERATION == 'ADD_PAY') || ($ACTION == 'save_invoice' && $OPERATION == 'EDIT_PAY') || ($ACTION == 'save_invoice' && $OPERATION == 'DELETE_PAY')) {
				$dataPayDetail = array('SalPayment'=> $arrayPayDetails);
				if($arrayMovement5 <> null){
					$dataMovement5 = $arrayMovement5;
				}	
			}elseif ($ACTION == 'save_invoice') {
				$this->loadModel('InvMovement');
				//for movement
				$dataMovement3 = array('InvMovement'=>$arrayMovement3);
				$dataMovementDetail3 = array('InvMovementDetail'=> $arrayMovementDetails3);
				$dataMovement4 = array('InvMovement'=>$arrayMovement4);
				$dataMovementDetail4 = array('InvMovementDetail'=> $arrayMovementDetails4);
				if($arrayMovement5 <> null){
					$dataMovement5 = $arrayMovement5;
				}	
				if((($ACTION == 'save_order' && $OPERATION3 == 'DELETE' && $arrayMovement6 <> null) || ($ACTION == 'save_order' && $OPERATION4 == 'DELETE' && $arrayMovement6 <> null)) ){
					$dataMovement6 = $arrayMovement6;
				}	
				$dataPayDetail = null;
			}
			$dataMovementDetail[0] = array('SalDetail'=> $arrayMovementDetails);
//			if ($ACTION == 'save_order'){
//				$dataMovementDetail[1] = array('SalDetail'=> $arrayMovementDetails);
//			}
			////////////////////////////////////////////////END - SET DATA//////////////////////////////////////////////////////
			
			$validation['error'] = 0;
			$strItemsStock = '';
			////////////////////////////////////////////START- CORE SAVE////////////////////////////////////////////////////////
			if($error === 0){
				/////////////////////START - SAVE/////////////////////////////	
//				echo 'OPERATION';
//					debug($OPERATION);
//				echo 'ACTION';
//					debug($ACTION);
//				echo '$dataMovement';	
//					print_r($dataMovement);
//				echo '$dataMovementDetail';	
//					print_r($dataMovementDetail);
//				echo '------------------------------------------------ <br>';
//				echo 'OPERATION2';
//					debug($OPERATION);
//				echo 'ACTION';
//					debug($ACTION);
//				echo '$dataMovement2';	
//					debug($dataMovement2);
//				echo '$dataMovementDetail2';	
//					debug($dataMovementDetail);
//				echo '------------------------------------------------ <br>';
//				echo 'STOCK';
//					debug($stock);
//				echo 'OPERATION3';
//					debug($OPERATION3);
//				echo '$dataMovement3';	
//					debug($dataMovement3);
//				echo '$dataMovementDetail3';	
//					debug($dataMovementDetail3);
//				echo '------------------------------------------------ <br>';	
//				echo 'QUANTITY';
//					debug($quantity);
//				echo 'OPERATION4';
//					debug($OPERATION4);
//				echo '$dataMovement4';	
//					debug($dataMovement4);
//				echo '$dataMovementDetail4';	
//					debug($dataMovementDetail4);
//				echo '------------------------------------------------ <br>';	
//				echo '$arrayMovement5';	
//					debug($arrayMovement5);
//				echo '------------------------------------------------ <br>';	
//				echo '$arrayMovement6';	
//				debug($arrayMovement6);
//				debug($dataMovement6);
//				echo '------------------------------------------------ <br>';
//				echo '$dataPayDetail';
//				debug($dataPayDetail);
//				debug($arrayPayDetails);
//				debug($dataPayDetail);
//				echo '$rest3<br>';
//				debug($rest3);
//				echo 'id3<br>';
//				debug($arrayMovement3['id']);
//				echo '<br>$rest4<br>';
//				debug($rest4);
//				echo 'id4<br>';
//				debug($arrayMovement4['id']);
				$arraySalePrices = array();
				if ($ACTION == 'save_invoice' && $STATE == 'SINVOICE_APPROVED'){
					$this->loadModel('InvPrice');
					$prices = $this->InvPrice->find('all', array(
						'fields'=>array(
							'InvPrice.inv_item_id'
							,'InvPrice.inv_price_type_id'
							,'InvPrice.price'
							),
						'conditions'=>array(
							'InvPrice.date <='=>$date
							),
						'recursive'=>-1
					));
//					debug($prices);
//					$arraySalePrices = array();
					for($i=0;$i<count($arrayItemsDetails);$i++){
						$contSale = 0; 
						for($j=0;$j<count($prices);$j++){
							if($prices[$j]['InvPrice']['inv_item_id'] == $arrayItemsDetails[$i]['inv_item_id'] && $prices[$j]['InvPrice']['inv_price_type_id'] == 9 && $prices[$j]['InvPrice']['price'] == $arrayItemsDetails[$i]['sale_price']){	
								$contSale += 1;
							}							
						}
						if($contSale === 0){	
							$arraySalePrices[$i]['inv_item_id'] = $arrayItemsDetails[$i]['inv_item_id'];
							$arraySalePrices[$i]['inv_price_type_id'] = 9;//or better relate by name VENTA
							$arraySalePrices[$i]['price'] = $arrayItemsDetails[$i]['sale_price'];
							$arraySalePrices[$i]['ex_price'] = $arrayItemsDetails[$i]['ex_sale_price'];
							$arraySalePrices[$i]['description'] = "Precio de Venta del ".$date." de la compra ".$noteCode; 
							$arraySalePrices[$i]['date'] = $date;
						}	
					}	
				}
					if($validation['error'] === 0){
						
							$res = $this->SalSale->saveMovement($dataMovement, $dataMovementDetail, $OPERATION, $ACTION, $STATE, $movementDocCode, $dataPayDetail, $arraySalePrices);
							
//							if ($ACTION == 'save_invoice' && $STATE == 'SINVOICE_APPROVED'){
//									$this->loadModel('InvPrice');
//									$this->InvPrice->saveAll($arraySalePrices);
//							}
//							if ($ACTION == 'save_order'){
//								$res2 = $this->SalSale->saveMovement($dataMovement2, $dataMovementDetail, $OPERATION, $ACTION, $movementDocCode, null);
//								if(($stock != 0)||(($OPERATION3 == 'DELETE')&&($arrayMovement3['id']!==null))){
//									//used to insert/update type 1 detail movements 
//									//used to delete movement details type 1
////									echo "ini3";
//									$res3 = $this->InvMovement->saveMovement($dataMovement3, $dataMovementDetail3, $OPERATION3, 'save_in', null, $movementDocCode3);
////									echo "fin3";
//								}
//								if(($quantity > $stock)||(($OPERATION4 == 'DELETE')&&($arrayMovement4['id']!==null))){	//($quantity > $stock) doesn't work when stock changes
//									//used to insert/update type 2 detail movements									
//									//used to delete movement details type 2
////									echo "ini4";
//									$res4 = $this->InvMovement->saveMovement($dataMovement4, $dataMovementDetail4, $OPERATION4, 'save_in', null, $movementDocCode4);
////									echo "fin4";
//								}	
//								if($arrayMovement5 <> null){
//									//used to update movements head
////									echo "ini5";
//									$res5 = $this->InvMovement->saveMovement($dataMovement5, null, 'UPDATEHEAD', null, null, null);
////									echo "fin5";
//								}
//								if((($ACTION == 'save_order' && $OPERATION3 == 'DELETE' && $arrayMovement6 <> null) || ($ACTION == 'save_order' && $OPERATION4 == 'DELETE' && $arrayMovement6 <> null)) ){
////									echo "ini6";
//									$res6 = $this->InvMovement->saveMovement($dataMovement6, null, 'DELETEHEAD', null, null, null);
////									echo "fin6";
//								}
//								
//							}elseif ($ACTION == 'save_invoice' && $OPERATION != 'ADD_PAY' && $OPERATION != 'EDIT_PAY' && $OPERATION != 'DELETE_PAY') {
//								if(($stock != 0)||(($OPERATION3 == 'DELETE')&&($arrayMovement3['id']!==null))){
//									//used to insert/update type 1 detail movements 
//									//used to delete movement details type 1
////									echo "ini3";
//									$res3 = $this->InvMovement->saveMovement($dataMovement3, $dataMovementDetail3, $OPERATION3, 'save_in', null, $movementDocCode3);
////									echo "fin3";
//								}							//VER SI v ESTA CONDICION DEJA ENTRAR LO NECESARIO										//VER SI v ESTA OTRA CONDICION DEJA ENTRAR LO NECESARIO 
//								if(($quantity > $stock)||(($OPERATION3 == 'EDIT')&&($OPERATION4 == 'DELETE')&&($arrayMovement4['id']!==null))||(($OPERATION3 == 'DELETE')&&($OPERATION4 == 'DELETE')&&($arrayMovement4['id']!==null))){	//($quantity > $stock) doesn't work when stock changes
////								if(($quantity > $stock)||(($OPERATION4 == 'DELETE')&&($arrayMovement4['id']!==null))){
//									//used to insert/update type 2 detail movements									
//									//used to delete movement details type 2
////									echo "ini4";
//									$res4 = $this->InvMovement->saveMovement($dataMovement4, $dataMovementDetail4, $OPERATION4, 'save_in', null, $movementDocCode4);
////									echo "fin4";
//								}	
//								if($arrayMovement5 <> null){
//									//used to update movements head
//									//LO QUE ENTRE AQUI SOBREESCRIBE LA CABECERA DE $dataMovement3 y $dataMovement4
////									echo "ini5";
//									$res5 = $this->InvMovement->saveMovement($dataMovement5, null, 'UPDATEHEAD', null, null, null);
////									echo "fin5";
//								}
////								if((($OPERATION3 == 'DELETE' || $OPERATION4 == 'DELETE') && $arrayMovement6 <> null)){
////									$res6 = $this->InvMovement->saveMovement($dataMovement6, null, 'DELETEHEAD', null);
////								}
//							}elseif(($ACTION == 'save_invoice' && $OPERATION == 'ADD_PAY') || ($ACTION == 'save_invoice' && $OPERATION == 'EDIT_PAY') || ($ACTION == 'save_invoice' && $OPERATION == 'DELETE_PAY')){
//								if($arrayMovement5 <> null){
//									//used to update movements head
//									$res5 = $this->InvMovement->saveMovement($dataMovement5, null, 'UPDATEHEAD', null, null, null);
//								}
//							}
						if(($res <> 'error')/*||($res2 <> 'error')*/){
							$movementIdSaved = $res;	//sal_sales NOTE id
//							if ($ACTION == 'save_order'){
//								$movementIdSaved2 = $res2;	//sal_sales INVOICE id
//							}
							$strItemsStockDestination = '';
							echo $STATE.'|'.$movementIdSaved.'|'.$movementDocCode.'|'.$movementCode.'|'.$strItemsStock.$strItemsStockDestination;
						}else{
							echo 'ERROR|onSaving';
						}
					}else{
							echo 'VALIDATION|'.$validation['itemsStocks'].$strItemsStock;
					}

				/////////////////////END - SAVE////////////////////////////////	
			}else{
				echo 'ERROR|onGeneratingParameters';
			}
			////////////////////////////////////////////END-CORE SAVE////////////////////////////////////////////////////////
		}
	}
	
	public function ajax_logic_delete(){
		if($this->RequestHandler->isAjax()){
			$purchaseId = $this->request->data['purchaseId'];
			$type = $this->request->data['type'];	
			$genCode = $this->request->data['genCode'];
				if($this->SalSale->updateAll(array('SalSale.lc_state'=>"'$type'", 'SalSale.lc_transaction'=>"'MODIFY'"), array('SalSale.id'=>$purchaseId)) 
						){
					echo 'success';
				}
				if($type === 'SINVOICE_LOGIC_DELETED'){
					$this->loadModel('InvMovement');
					$arrayMovement5 = $this->InvMovement->find('all', array(
						'fields'=>array(
							'InvMovement.id',
//							,'InvMovement.date'
//							,'InvMovement.description'
							'InvMovement.inv_warehouse_id'
							),
						'conditions'=>array(
								'InvMovement.document_code'=>$genCode
							)
						,'order' => array('InvMovement.id' => 'ASC')
						,'recursive'=>0
					));
					if($arrayMovement5 <> null){
						for($i=0;$i<count($arrayMovement5);$i++){
							$arrayMovement5[$i]['InvMovement']['lc_state'] = 'DRAFT';
//							$arrayMovement5[$i]['InvMovement']['code'] = 'NO'; //not sure to put this
						}
					}
					if($arrayMovement5 <> null){
						$dataMovement5 = $arrayMovement5;
					}
					if($arrayMovement5 <> null){
						$res5 = $this->InvMovement->saveMovement($dataMovement5, null, 'UPDATEHEAD', null, null, null);
					}
				}
		}
	}
	
	public function ajax_initiate_modal_add_pay(){
		if($this->RequestHandler->isAjax()){
			$paysAlreadySaved = $this->request->data['paysAlreadySaved'];
			$payDebt = $this->request->data['payDebt'];
//			debug($payDebt);
			$datePay = $this->request->data['date']; //temporal date that shows in the payment modal
			//$datePay=date('d/m/Y');
//			$discount = $this->request->data['discount'];
//			debug($discount);
//			$debt = $this->SalSale->SalPayment->find('list', array(
//					'fields'=>array('SalPayment.amount'),
//					'conditions'=>array(
//						'SalPayment.date'=>$paysAlreadySaved
//				),
//				'recursive'=>-1
//			));
//	debug($debt);
//			$warehouse = $this->request->data['warehouse'];
		//	$supplier = $this->request->data['supplier'];
//	//		$itemsBySupplier = $this->PurPurchase->InvSupplier->InvItemsSupplier->find('list', array(
//				'fields'=>array('InvItemsSupplier.inv_item_id'),
//				'conditions'=>array(
//					'InvItemsSupplier.inv_supplier_id'=>$supplier
//				),
//				'recursive'=>-1
//			)); 
//debug($itemsBySupplier);	
//			if ($discount != 0){
//				$payDebtVar = $payDebt-($payDebt*($discount/100));
//				$payDebt = number_format($payDebtVar, 2, '.', '');
//			}
//			debug($payDebt);
			$pays = $this->SalSale->SalPayment->SalPaymentType->find('list', array(
					'fields'=>array('SalPaymentType.name'),
					'conditions'=>array(
//						'NOT'=>array('InvPriceType.id'=>$paysAlreadySaved) /*aca se hace la discriminacion de items seleccionados*/
				),
				
				'recursive'=>-1
				//'fields'=>array('InvItem.id', 'CONCAT(InvItem.code, '-', InvItem.name)')
			));
//debug($payDebt);		
//debug($items);
//debug($this->request->data);
		// gets the first price in the list of the item prices
//		$firstItemListed = key($items);
//		$priceDirty = $this->PurPurchase->PurDetail->InvItem->InvPrice->find('first', array(
//			'fields'=>array('InvPrice.price'),
//			'order' => array('InvPrice.date_created' => 'desc'),
//			'conditions'=>array(
//				'InvPrice.inv_item_id'=>$firstItemListed
//				)
//		));
////debug($priceDirty);
//		if($priceDirty==array()){
//			$price = 0;
//		}  else {
//			
//			$price = $priceDirty['InvPrice']['price'];
//		}
//			$amountDirty = $this->PurPurchase->PurPrice->find('first', array(
//			'fields'=>array('PurPrice.amount'),
//	//		'order' => array('rice.date_created' => 'desc'),
//			'conditions'=>array(
//				'PurPrice.inv_price_type_id'=>$costsAlreadySaved
//				)
//			));
//			if($amountDirty==array()){
//			$amount = 0;
//		}  else {
//			
//			$amount = $amountDirty['PurPrice']['amount'];
//		}
				
			$this->set(compact('pays', 'datePay', 'payDebt'/*, 'amount'*/));
		}
	}
	
	
	public function ajax_update_ex_rate(){
		if($this->RequestHandler->isAjax()){
			$date = $this->request->data['date']; 
			
			$this->loadModel('AdmParameter');
			$currency = $this->AdmParameter->AdmParameterDetail->find('first', array(
					'conditions'=>array(
						'AdmParameter.name'=>'Moneda',
						'AdmParameterDetail.par_char1'=>'Dolares'
					)
				)); 
			$currencyId = $currency['AdmParameterDetail']['id'];
			$this->loadModel('AdmExchangeRate');
			$xxxRate = $this->AdmExchangeRate->find('first', array(
					'fields'=>array('AdmExchangeRate.value'),
					'conditions'=>array(
						'AdmExchangeRate.currency'=>$currencyId,
						'AdmExchangeRate.date'=>$date
					),
					'recursive'=>-1
				)); 		
			if ($xxxRate == array()){
				$exRate = '';
			}else{
				$exRate = $xxxRate['AdmExchangeRate']['value'];
			}
		
			$this->set(compact('exRate'));			
		}else{
			$this->redirect($this->Auth->logout());
		}
	}
	
	//////////////////////////////////////////// END - AJAX /////////////////////////////////////////////////
	
	//////////////////////////////////////////// START - PRIVATE ///////////////////////////////////////////////
		
	public function _get_movements_details($idMovement){
		$movementDetails = $this->SalSale->SalDetail->find('all', array(
			'conditions'=>array(
				'SalDetail.sal_sale_id'=>$idMovement
				),																									                             /*REVISAR ESTO V*/
			'fields'=>array('InvItem.name', 'InvItem.code', 'SalDetail.sale_price', 'SalDetail.quantity','SalDetail.inv_warehouse_id', 'InvItem.id', 'InvWarehouse.name','InvWarehouse.id', 'InvItem.id')
			));
		
		$formatedMovementDetails = array();
		foreach ($movementDetails as $key => $value) {
			// gets the first price in the list of the item prices
//			$priceDirty = $this->PurPurchase->PurDetail->InvItem->InvPrice->find('first', array(
//					'fields'=>array('InvPrice.price'),
//					'order' => array('InvPrice.date_created' => 'desc'),
//					'conditions'=>array(
//						'InvPrice.inv_item_id'=>$value['InvItem']['id']
//						)
//				));
				//$price = $priceDirty['InvPrice']['price'];
			
			$formatedMovementDetails[$key] = array(
				'itemId'=>$value['InvItem']['id'],
				'item'=>'[ '. $value['InvItem']['code'].' ] '.$value['InvItem']['name'],
				'salePrice'=>$value['SalDetail']['sale_price'],//llamar precio
				'cantidad'=>$value['SalDetail']['quantity'],//llamar cantidad
				'warehouseId'=>$value['InvWarehouse']['id'],
				'warehouse'=>$value['InvWarehouse']['name'],//llamar almacen
//	'cifPrice'=>$value['SalDetail']['cif_price'],
//	'exCifPrice'=>$value['SalDetail']['ex_cif_price'],
				'stock'=> $this->_find_stock($value['InvItem']['id'], $value['SalDetail']['inv_warehouse_id'])
				
				);
		}
//debug($formatedMovementDetails);		
		return $formatedMovementDetails;
	}
	
	public function _get_pays_details($idMovement){
		$paymentDetails = $this->SalSale->SalPayment->find('all', array(
			'conditions'=>array(
				'SalPayment.sal_sale_id'=>$idMovement
				),																									                            
			'fields'=>array('SalPayment.date', 'SalPayment.amount','SalPayment.description')
			));
		
		$formatedPaymentDetails = array();
		foreach ($paymentDetails as $key => $value) {
			$formatedPaymentDetails[$key] = array(
				'dateId'=>$value['SalPayment']['date'],//llamar precio
				//'payDate'=>strftime("%A, %d de %B de %Y", strtotime($value['SalPayment']['date'])),
				'payDate'=>strftime("%d/%m/%Y", strtotime($value['SalPayment']['date'])),
				'payAmount'=>$value['SalPayment']['amount'],//llamar cantidad
				'payDescription'=>$value['SalPayment']['description']
				);
		}
//debug($formatedPaymentDetails);		strftime("%A, %d de %B de %Y", $value['SalPayment']['date'])
		return $formatedPaymentDetails;
	}
	
	
	
	private function _get_price($itemId, $date, $type, $currType){
		$this->loadModel('InvPrice');
		//To change UK date format to US date format
		$bits = explode('/',$date);
		$date = $bits[1].'/'.$bits[0].'/'.$bits[2];
		//To get id of the price type
		$typeId = $this->InvPrice->InvPriceType->find('list', array(
			'fields'=>array(
				'InvPriceType.id'
				),
			'conditions'=>array(
				'InvPriceType.name'=>$type
				)
			));
		//To get the history of prices
		$prices = $this->InvPrice->find('list', array(
			'fields'=>array(
				'InvPrice.id',
				'InvPrice.date'
				),
			'conditions'=>array(
				'InvPrice.inv_item_id'=>$itemId,
				'InvPrice.inv_price_type_id'=>$typeId//'InvPrice.inv_price_type_id'=>1
				)
			));
		if($prices <> null){
			//To get the list of subtracted dates in unix time format
			foreach($prices as $id => $day){
				$interval[$id] = abs(strtotime($date) - strtotime($day));
			}
			asort($interval);
			$closest = key($interval);
			//To get the price
			if($currType == 'dolar'){
				$priceField = $this->InvPrice->find('first', array(
				'fields'=>array(
					'InvPrice.ex_price'
					),
				'conditions'=>array(
					'InvPrice.id'=>$closest
					)
				));
				$price = $priceField['InvPrice']['ex_price'];
			}else{
				$priceField = $this->InvPrice->find('first', array(
				'fields'=>array(
					'InvPrice.price'
					),
				'conditions'=>array(
					'InvPrice.id'=>$closest
					)
				));
				$price = $priceField['InvPrice']['price'];
			}
			if ($price === null){
				$price = 0;
			}
		}else{
			$price = 0;
		}
		//debug($price);
		return $price;
	}
		
	private function _get_doc_id($purchaseId, $movementCode, $type, $warehouseId){
		if ($purchaseId <> null) {
			$invoiceId = $this->SalSale->find('list', array(
				'fields'=>array('SalSale.id'),
				'conditions'=>array(
					'SalSale.code'=>$movementCode,
					"SalSale.id !="=>$purchaseId
					)
			));
			$docId = key($invoiceId);
		}else{
			$this->loadModel('InvMovement');
			$movementId = $this->InvMovement->find('list', array(
				'fields'=>array('InvMovement.id'),
				'conditions'=>array(
					'InvMovement.document_code'=>$movementCode,
					'InvMovement.type'=>$type,
					'InvMovement.inv_warehouse_id'=>$warehouseId,
					)
			));
			$docId = key($movementId);
		}
		return $docId;
	}	
		
	private function _get_stocks($items, $warehouse, $limitDate = '', $dateOperator = '<='){
		$this->loadModel('InvMovement');
		$this->InvMovement->InvMovementDetail->unbindModel(array('belongsTo' => array('InvItem')));
		$this->InvMovement->InvMovementDetail->bindModel(array(
			'hasOne'=>array(
				'InvMovementType'=>array(
					'foreignKey'=>false,
					'conditions'=> array('InvMovement.inv_movement_type_id = InvMovementType.id')
				)
				
			)
		));
		$dateRanges = array();
		if($limitDate <> ''){
			$dateRanges = array('InvMovement.date '.$dateOperator => $limitDate);
		}
		
		$movements = $this->InvMovement->InvMovementDetail->find('all', array(
			'fields'=>array(
				"InvMovementDetail.inv_item_id", 
				"(SUM(CASE WHEN \"InvMovementType\".\"status\" = 'entrada' AND \"InvMovement\".\"lc_state\" = 'APPROVED' THEN \"InvMovementDetail\".\"quantity\" ELSE 0 END))-
				(SUM(CASE WHEN \"InvMovementType\".\"status\" = 'salida' AND \"InvMovement\".\"lc_state\" = 'APPROVED' THEN \"InvMovementDetail\".\"quantity\" ELSE 0 END)) AS stock"
				),
			'conditions'=>array(
				'InvMovement.inv_warehouse_id'=>$warehouse,
				'InvMovementDetail.inv_item_id'=>$items,
				$dateRanges
				),
			'group'=>array('InvMovementDetail.inv_item_id'),
			'order'=>array('InvMovementDetail.inv_item_id')
		));
		//the array format is like this:
		/*
		array(
			(int) 0 => array(
				'InvMovementDetail' => array(
					'inv_item_id' => (int) 9
				),
				(int) 0 => array(
					'stock' => '20'
				)
			),...etc,etc
		)	*/
		return $movements;
	}
	
	private function _find_item_stock($stocks, $item){
		foreach($stocks as $stock){//find required stock inside stocks array 
			if($item == $stock['InvMovementDetail']['inv_item_id']){
				return $stock[0]['stock'];
			}
		}
		//this fixes in case there isn't any item inside movement_details yet with a determinated warehouse
		return 0;
	}
	
	private function _generate_code($keyword){
		$period = $this->Session->read('Period.name');
		if($period <> ''){
			try{
				$movements = $this->SalSale->find('count', array(
					'conditions'=>array('SalSale.lc_state'=>array('NOTE_PENDANT','NOTE_APPROVED','NOTE_CANCELLED','NOTE_LOGIC_DELETED'))
				));
			}catch(Exception $e){
				return 'error';
			}
		}else{
			return 'error';
		}
		
		$quantity = $movements + 1; 
		$code = $keyword.'-'.$period.'-'.$quantity;
		return $code;
	}
	
	private function _generate_doc_code($keyword){
		$period = $this->Session->read('Period.name');
		if($period <> ''){
			try{
				if ($keyword == 'NOT'){
					$movements = $this->SalSale->find('count', array(
						'conditions'=>array('SalSale.lc_state'=>array('NOTE_PENDANT','NOTE_APPROVED','NOTE_CANCELLED','NOTE_LOGIC_DELETED'))
					)); 
				}elseif ($keyword == 'VFA'){
					$movements = $this->SalSale->find('count', array(
						'conditions'=>array('SalSale.lc_state'=>array('SINVOICE_PENDANT','SINVOICE_APPROVED','SINVOICE_CANCELLED','SINVOICE_LOGIC_DELETED'))
					));
				}
			}catch(Exception $e){
				return 'error';
			}
		}else{
			return 'error';
		}
		
		$quantity = $movements + 1; 
		$docCode = $keyword.'-'.$period.'-'.$quantity;
		return $docCode;
	}
	
	private function _generate_movement_code($keyword, $type){
		$this->loadModel('InvMovement');
		$period = $this->Session->read('Period.name');
		$movementType = '';
		if($keyword == 'ENT'){$movementType = 'entrada';}
		if($keyword == 'SAL'){$movementType = 'salida';}
		if($period <> ''){
			try{
				$movements = $this->InvMovement->find('count', array(
					'conditions'=>array(
						'InvMovementType.status'=>$movementType
						,'InvMovement.code !='=>'NO'
					//	,'InvMovement.lc_state !='=>'DRAFT'
						)
				)); 
			}catch(Exception $e){
				return 'error';
			}
			
//			$movementss = $this->InvMovement->find('all', array(
//					'conditions'=>array('InvMovementType.status'=>$movementType)
//				)); 
//		echo '------------------------------------------------ <br>';		
//		echo '---movements count--- <br>';	
//		debug($movements);
//		echo '---movements --- <br>';	
//		debug($movementss);
//		echo '----movement type------- <br>';
//		debug($movementType);
//		echo '------------------------------------------------ <br>';
			
		}else{
			return 'error';
		}
		if($type == 'inc'){
			static $inc = 0;
			$quantity = $movements + 1 + $inc;
			$inc++;
		}else{
			$quantity = $movements + 1; 
		}
		$code = $keyword.'-'.$period.'-'.$quantity;
		return $code;
	}
	
        private function _generate_movement_code_id($keyword, $type, $idsToDelete){
		$this->loadModel('InvMovement');
		$period = $this->Session->read('Period.name');
		$movementType = '';
		if($keyword == 'ENT'){$movementType = 'entrada';}
		if($keyword == 'SAL'){$movementType = 'salida';}
		if($period <> ''){
			try{
				$movements = $this->InvMovement->find('count', array(
					'conditions'=>array(
						'InvMovementType.status'=>$movementType
						,'InvMovement.code !='=>'NO'
						,'InvMovement.id !='=>$idsToDelete
						)
				)); 
			}catch(Exception $e){
				return 'error';
			}
		}else{
			return 'error';
		}
		if($type == 'inc'){
			static $inc = 0;
			$quantity = $movements + 1 + $inc;
			$inc++;
		}else{
			$quantity = $movements + 1; 
		}
		$code = $keyword.'-'.$period.'-'.$quantity;
		return $code;
	}
        
	private function _find_stock($idItem, $idWarehouse){		
		$movementsIn = $this->_get_quantity_movements_item($idItem, $idWarehouse, 'entrada');
		$movementsOut = $this->_get_quantity_movements_item($idItem, $idWarehouse, 'salida');
		$add = array_sum($movementsIn);
		$sub = array_sum($movementsOut);
		$stock = $add - $sub;
		return $stock;
	}
	
	private function _get_quantity_movements_item($idItem, $idWarehouse, $status){
		//******************************************************************************//
		//unbind for perfomance InvItem 'cause it isn't needed
//		$this->InvMovement->InvMovementDetail->unbindModel(array(
//			'belongsTo' => array('InvItem')
//		));
//		//Add association for InvMovementType
		$this->SalSale->SalDetail->InvItem->InvMovementDetail->bindModel(array(
			'hasOne'=>array(
				'InvMovementType'=>array(
					'foreignKey'=>false,
					'conditions'=> array('InvMovement.inv_movement_type_id = InvMovementType.id')
				)
				
			)
		));
		//******************************************************************************//
		//Movements
//		$movs = $this->SalSale->SalDetail->InvItem->InvMovementDetail->InvMovement->find('all', array(	
//			'fields'=>array('InvMovement.inv_warehouse_id', 'InvMovement.lc_state'),
//			'conditions'=>array(
//				'InvMovement.inv_warehouse_id'=>$idWarehouse,
//				'InvMovementDetail.inv_item_id'=>$idItem,
//				'InvMovementType.status'=>$status,
//				'InvMovement.lc_state'=>'APPROVED',
//				)
//		));
		
	//	$movements = $this->InvMovement->InvMovementDetail->find('all', array(
		$movements = $this->SalSale->SalDetail->InvItem->InvMovementDetail->find('all', array(	
			'fields'=>array('InvMovementDetail.inv_movement_id', 'InvMovementDetail.quantity'),
			'conditions'=>array(
				'InvMovement.inv_warehouse_id'=>$idWarehouse,
				'InvMovementDetail.inv_item_id'=>$idItem,
				'InvMovementType.status'=>$status,
				'InvMovement.lc_state'=>'APPROVED',
				)
		));
		//Give format to nested array movements
		$movementsCleaned = $this->_clean_nested_arrays($movements);
		return $movementsCleaned;
	}
	
	private function _clean_nested_arrays($array){
		$clean = array();
		foreach ($array as $key => $value) {
			$clean[$key] = $value['InvMovementDetail']['quantity'];
		}
		return $clean;
	}
	
	//////////////////////////////////////////// END - PRIVATE /////////////////////////////////////////////////
	
	//*******************************************************************************************************//
	/////////////////////////////////////////// END - CLASS ///////////////////////////////////////////////
	//*******************************************************************************************************//
	
/*********************************************************************************************************************/
/***********************************************************************************************************************/
/*********************************************************************************************************************/
/***********************************************************************************************************************/
/*********************************************************************************************************************/
/***********************************************************************************************************************/
	
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
	//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

	
	public function ajax_cancell_all(){
		if($this->RequestHandler->isAjax()){
			$purchaseId = $this->request->data['purchaseId'];
			$type = $this->request->data['type'];	
			$genCode = $this->request->data['genCode'];
			$purchaseId2 = $this->_get_doc_id($purchaseId, $genCode, null, null);

				if($this->SalSale->updateAll(array('SalSale.lc_state'=>"'$type'", 'SalSale.lc_transaction'=>"'MODIFY'"), array('SalSale.id'=>$purchaseId)) 
						){
					if($this->SalSale->updateAll(array('SalSale.lc_state'=>"'SINVOICE_CANCELLED'", 'SalSale.lc_transaction'=>"'MODIFY'"), array('SalSale.id'=>$purchaseId2)) 
						){
					echo 'success';
				}
				}
				
				if($type === 'NOTE_CANCELLED'){
					$this->loadModel('InvMovement');
					$arrayMovement5 = $this->InvMovement->find('all', array(
						'fields'=>array(
							'InvMovement.id',
//							,'InvMovement.date'
//							,'InvMovement.description'
							'InvMovement.inv_warehouse_id'
							),
						'conditions'=>array(
								'InvMovement.document_code'=>$genCode
							)
						,'order' => array('InvMovement.id' => 'ASC')
						,'recursive'=>0
					));
					if($arrayMovement5 <> null){
						for($i=0;$i<count($arrayMovement5);$i++){
							$arrayMovement5[$i]['InvMovement']['lc_state'] = 'CANCELLED';
//							$arrayMovement5[$i]['InvMovement']['code'] = 'NO'; //not sure to put this
						}
					}
					if($arrayMovement5 <> null){
						$dataMovement5 = $arrayMovement5;
					}
					if($arrayMovement5 <> null){
						$res5 = $this->InvMovement->saveMovement($dataMovement5, null, 'UPDATEHEAD', null, null, null);
					}
				}
		}
	}
	//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
	//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
	public function ajax_generate_movements(){
		if($this->RequestHandler->isAjax()){
			////////////////////////////////////////////INICIO-CAPTURAR AJAX/////////////////////////////////////////////////////
			$arrayItemsDetails = $this->request->data['arrayItemsDetails'];		
			$date = $this->request->data['date'];
			$description = $this->request->data['description'];
//			$note_code = $this->request->data['note_code'];
			$genericCode = $this->request->data['genericCode'];
//			$originCode = $this->request->data['originCode'];
			$error=0;
			$movementDocCode = '';
//print_r($arrayItemsDetails);
			$this->loadModel('InvMovement');
			$ids = $this->InvMovement->find('list', array(
				'fields'=>array('InvMovement.id'),
				'conditions'=>array('InvMovement.document_code'=>$genericCode)
			));
			$idsOrdered = array_values($ids);
			
			
			foreach($arrayItemsDetails as $val) {
				$arrayWarehouses[] = $val['inv_warehouse_id'];
			}
//			debug($arrayWarehouses);
			$arrayWarehousesList = array_values(array_unique($arrayWarehouses));
//			sort($arrayWarehousesList);
//			debug($arrayWarehousesList);
			
			for($i=0;$i<count($arrayWarehousesList);$i++){
				$arrayMovement = array();
				$arrayMovementDetails = array();
				$arrayBOMovement = array();
				$arrayBOMovementDetails = array();
				$cont = 0;
				$movementDocCode = '';
				$movementBODocCode = '';
				for($j=0;$j<count($arrayItemsDetails);$j++){
					if($arrayItemsDetails[$j]['inv_warehouse_id'] == $arrayWarehousesList[$i]){
						$itemId = $arrayItemsDetails[$j]['inv_item_id'];
						$quantity = $arrayItemsDetails[$j]['quantity'];
						$warehouseId = $arrayItemsDetails[$j]['inv_warehouse_id'];
						$stocks = $this->_get_stocks($itemId, $warehouseId);
						$stock = $this->_find_item_stock($stocks, $itemId);
//						debug($itemId.'+'.$warehouseId.'=>'.$quantity.'@'.$stock);
						if ($quantity > $stock) {
							$cont++;
							if ($stock > 0){
								$arrayMovementDetails[] = array('inv_item_id'=>$itemId, 'quantity'=>$stock);
							}
							if ($stock >= 0){
								$arrayBOMovementDetails[] = array('inv_item_id'=>$itemId, 'quantity'=>($quantity-$stock));
							}else{
								$arrayBOMovementDetails[] = array('inv_item_id'=>$itemId, 'quantity'=>$quantity);
							}
						} else {
							$arrayMovementDetails[] = array('inv_item_id'=>$itemId, 'quantity'=>$quantity);
						}
					}
				}
				if (($cont > 0)&&($arrayMovementDetails != array())){
					$movementDocCode = $this->_generate_movement_code_id('SAL','inc', $idsOrdered);
					$movementBODocCode = $this->_generate_movement_code_id('SAL','inc', $idsOrdered);
					$arrayMovement = array('type'=>1, 'date'=>$date, 'inv_warehouse_id'=>$arrayWarehousesList[$i], 'inv_movement_type_id'=>2, 'description'=>$description, 'code'=>$movementDocCode, 'document_code'=>$genericCode, 'lc_state'=>'PENDANT');
					$arrayBOMovement = array('type'=>2, 'date'=>$date, 'inv_warehouse_id'=>$arrayWarehousesList[$i], 'inv_movement_type_id'=>2, 'description'=>$description, 'code'=>$movementBODocCode, 'document_code'=>$genericCode, 'lc_state'=>'PENDANT');
					$data[] = array('InvMovement'=>$arrayMovement, 'InvMovementDetail'=>$arrayMovementDetails);
					$data[] = array('InvMovement'=>$arrayBOMovement, 'InvMovementDetail'=>$arrayBOMovementDetails);
				} elseif (($cont > 0)&&($arrayMovementDetails == array())) {
					$movementBODocCode = $this->_generate_movement_code_id('SAL','inc', $idsOrdered);
					$arrayBOMovement = array('type'=>2, 'date'=>$date, 'inv_warehouse_id'=>$arrayWarehousesList[$i], 'inv_movement_type_id'=>2, 'description'=>$description, 'code'=>$movementBODocCode, 'document_code'=>$genericCode, 'lc_state'=>'PENDANT');
					$data[] = array('InvMovement'=>$arrayBOMovement, 'InvMovementDetail'=>$arrayBOMovementDetails);
				} else {
					$movementDocCode = $this->_generate_movement_code_id('SAL','inc', $idsOrdered);
					$arrayMovement = array('type'=>1, 'date'=>$date, 'inv_warehouse_id'=>$arrayWarehousesList[$i], 'inv_movement_type_id'=>2, 'description'=>$description, 'code'=>$movementDocCode, 'document_code'=>$genericCode, 'lc_state'=>'PENDANT');
					$data[] = array('InvMovement'=>$arrayMovement, 'InvMovementDetail'=>$arrayMovementDetails);
				}
				if($movementDocCode == 'error'){$error++;}
				if($movementBODocCode == 'error'){$error++;}
			}
//			print_r($data);
			
//			$idsToDelete = array('InvMovement.id'=>$idsOrdered);
			
			if($error == 0){
				$res = $this->SalSale->saveGeneratedMovements(/*$idsToDelete,*/ $data);
				
				switch ($res[0]) {
					case 'SUCCESS':
						echo 'creado|'.$res[1];
						break;
					case 'ERROR':
						echo 'ERROR|onSaving';
						break;
				}
				
			}else{
				echo 'ERROR|onGeneratingParameters';
			}
		}
		
	}
	//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
	//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
	
	public function ajax_change_state_approved_movement_in_full(){
		if($this->RequestHandler->isAjax()){
			////////////////////////////////////////////INICIO-CAPTURAR AJAX/////////////////////////////////////////////////////
			$arrayItemsDetails = $this->request->data['arrayItemsDetails'];		
			$purchaseId = $this->request->data['purchaseId'];
	
			$this->loadModel('AdmUser');
			
			$date = $this->request->data['date'];
			$employee = $this->request->data['employee'];
			$taxNumber = $this->request->data['taxNumber'];
			$salesman = $this->request->data['salesman'];
			$description = $this->request->data['description'];
			$exRate = $this->request->data['exRate'];
			$discount = $this->request->data['discount'];
			$note_code = $this->request->data['note_code'];
	
//			$admUserId = $this->AdmUser->AdmProfile->find('list', array(
//			'fields'=>array('AdmProfile.adm_user_id'),
//			'conditions'=>array('AdmProfile.id'=>$admProfileId)
//			));
//			
//			$salesman = key($this->AdmUser->find('list', array(
//			'conditions'=>array('AdmUser.id'=>$admUserId)
//			)));
			
			$generalCode = $this->request->data['genericCode'];
			////////////////////////////////////////////FIN-CAPTURAR AJAX/////////////////////////////////////////////////////
			
			////////////////////////////////////////////INICIO-CREAR PARAMETROS////////////////////////////////////////////////////////
//			$arrayMovement = array('date'=>$date, 'sal_employee_id'=>$employee,'sal_tax_number_id'=>$taxNumber,'salesman_id'=>$salesman, 'description'=>$description, 'note_code'=>$note_code, 'ex_rate'=>$exRate);
//			$arrayMovement['lc_state'] = 'SINVOICE_PENDANT';
//			$arrayMovement['id'] = $purchaseId;
			$arrayNote = array('id' => $purchaseId, 'lc_state'=>'NOTE_APPROVED');
			$arrayInvoice = array('date'=>$date, 'sal_employee_id'=>$employee,'sal_tax_number_id'=>$taxNumber,'salesman_id'=>$salesman, 'description'=>$description, 'note_code'=>$note_code, 'ex_rate'=>$exRate, 'discount'=>$discount);		
			$movementDocCode = $this->_generate_doc_code('VFA');
			$arrayInvoice['lc_state'] = 'SINVOICE_PENDANT';
//			$arrayInvoice['lc_transaction'] = 'CREATE';
			$arrayInvoice['code'] = $generalCode;
			$arrayInvoice['doc_code'] = $movementDocCode;
//			$arrayInvoice['inv_supplier_id'] = $supplier;
			//*********************************************
				
			$cont1 = 0;
			$cont2 = 0;
			$arrayMovement1 = array();
			$arrayMovement2 = array();
			$arrayMovementDetails1 = array();
			$arrayMovementDetails2 = array();
			for($i=0;$i<count($arrayItemsDetails);$i++){
				if ($arrayItemsDetails[$i]['inv_warehouse_id'] == 1){
					$arrayMovementDetails1[$i]['inv_item_id'] = $arrayItemsDetails[$i]['inv_item_id'];
					$arrayMovementDetails1[$i]['quantity'] = $arrayItemsDetails[$i]['quantity'];
					
					$cont1 += 1;
				} elseif ($arrayItemsDetails[$i]['inv_warehouse_id'] == 2) {
					$arrayMovementDetails2[$i]['inv_item_id'] = $arrayItemsDetails[$i]['inv_item_id'];
					$arrayMovementDetails2[$i]['quantity'] = $arrayItemsDetails[$i]['quantity'];
					
					$cont2 += 1;
				}
			}
			
			$data1 = array();
			$data2 = array();
			if ($cont1 > 0 && $cont2 == 0){
				$arrayMovement1['date']=$date;
				$arrayMovement1['inv_warehouse_id']=1;
				$arrayMovement1['inv_movement_type_id']=2;
				$arrayMovement1['description']=$description;
				$arrayMovement1['document_code'] = $generalCode;
				$arrayMovement1['type']=1;
				$arrayMovement1['lc_state']='PENDANT';
				$arrayMovement1['code'] = $this->_generate_movement_code('SAL',null);
				
				$data1 = array('InvMovement'=>$arrayMovement1, 'InvMovementDetail'=>$arrayMovementDetails1);
			}elseif($cont2 > 0 && $cont1 == 0){
				$arrayMovement2['date']=$date;
				$arrayMovement2['inv_warehouse_id']=2;
				$arrayMovement2['inv_movement_type_id']=2;
				$arrayMovement2['description']=$description;
				$arrayMovement2['document_code'] = $generalCode;
				$arrayMovement2['type']=1;
				$arrayMovement2['lc_state']='PENDANT';
				$arrayMovement2['code'] = $this->_generate_movement_code('SAL',null);
				
				$data2 = array('InvMovement'=>$arrayMovement2, 'InvMovementDetail'=>$arrayMovementDetails2);
			}elseif($cont1 > 0 && $cont2 > 0){
				$arrayMovement1['date']=$date;
				$arrayMovement1['inv_warehouse_id']=1;
				$arrayMovement1['inv_movement_type_id']=2;
				$arrayMovement1['description']=$description;
				$arrayMovement1['document_code'] = $generalCode;
				$arrayMovement1['type']=1;
				$arrayMovement1['lc_state']='PENDANT';
				$arrayMovement1['code'] = $this->_generate_movement_code('SAL','inc');
				
				$arrayMovement2['date']=$date;
				$arrayMovement2['inv_warehouse_id']=2;
				$arrayMovement2['inv_movement_type_id']=2;
				$arrayMovement2['description']=$description;
				$arrayMovement2['document_code'] = $generalCode;
				$arrayMovement2['type']=1;
				$arrayMovement2['lc_state']='PENDANT';
				$arrayMovement2['code'] = $this->_generate_movement_code('SAL','inc');
				
				$data1 = array('InvMovement'=>$arrayMovement1, 'InvMovementDetail'=>$arrayMovementDetails1);
				$data2 = array('InvMovement'=>$arrayMovement2, 'InvMovementDetail'=>$arrayMovementDetails2);
			}
			
			$dataNot = array('SalSale'=>$arrayNote);		
			$dataInv = array('SalSale'=>$arrayInvoice, 'SalDetail'=>$arrayItemsDetails);
			
			
//			print_r($dataInv);
//			print_r($data1);
//			print_r($data2);
			
			////////////////////////////////////////////FIN-CREAR PARAMETROS////////////////////////////////////////////////////////
			////////////////////////////////////////////INICIO-CREAR PARAMETROS////////////////////////////////////////////////////////

			////////////////////////////////////////////FIN-CREAR PARAMETROS////////////////////////////////////////////////////////
//			if ($data2 == array()){
//				echo "DATA2 VACIO";
//			}
			//print_r($code);
//			print_r($data2);
//			print_r($dataInv);
			////////////////////////////////////////////INICIO-SAVE////////////////////////////////////////////////////////
//			if($purchaseId <> ''){//update
//				if($this->SalSale->SalDetail->deleteAll(array('SalDetail.sal_sale_id'=>$purchaseId))){
				$this->loadModel('InvMovement');
					if($data2===array()){
						if(($this->SalSale->saveAll($dataNot))&&($this->SalSale->saveAssociated($dataInv))&&($this->InvMovement->saveAssociated($data1))){
							echo 'aprobado|first';
						}
					}elseif($data1===array()){
						if(($this->SalSale->saveAll($dataNot))&&($this->SalSale->saveAssociated($dataInv))&&($this->InvMovement->saveAssociated($data2))){
							echo 'aprobado|sec';
						}
					}else{
						
						if(($this->SalSale->saveAll($dataNot))&&($this->SalSale->saveAssociated($dataInv))&&($this->InvMovement->saveAssociated($data1))&&($this->InvMovement->saveAssociated($data2))){
							echo 'aprobado|both';
						}
					}
//				}$this->saveAll($dataNot)
//			}
			////////////////////////////////////////////FIN-SAVE////////////////////////////////////////////////////////
		}
	}
	
	//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
	//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
}
