<?php echo $this->Html->script('modules/SalSales', FALSE); ?>

<!-- ************************************************************************************************************************ -->
<div class="span12"><!-- START CONTAINER FLUID/ROW FLUID/SPAN12 - FORMATO DE #UNICORN -->
<!-- ************************************************************************************************************************ -->
	<!-- //******************************** START - #UNICORN  WRAP FORM BOX PART 1/2 *************************************** -->
	<?php
		switch ($documentState){
			case '':
				$documentStateColor = '';
				$documentStateName = 'SIN ESTADO';
				break;
			case 'SINVOICE_PENDANT':
				$documentStateColor = 'label-warning';
				$documentStateName = 'FACTURA PENDIENTE';
				break;
			case 'SINVOICE_APPROVED':
				$documentStateColor = 'label-success';
				$documentStateName = 'FACTURA APROBADA';
				break;
			case 'SINVOICE_CANCELLED':
				$documentStateColor = 'label-important';
				$documentStateName = 'FACTURA CANCELADA';
				break;
		}
	?>
	<!-- //////////////////////////// Start - buttons /////////////////////////////////-->
	<div class="widget-box">
		<div class="widget-content nopadding">
			<?php 
				/////////////////START - SETTINGS BUTTON CANCEL /////////////////
				$url=array('action'=>'index_invoice');
				$parameters = $this->passedArgs;
				if(!isset($parameters['search'])){
//					unset($parameters['document_code']);
					unset($parameters['code']);
				}
				unset($parameters['id']);
				echo $this->Html->link('<i class=" icon-arrow-left"></i> Volver', array_merge($url,$parameters), array('class'=>'btn', 'escape'=>false)).' ';
				//////////////////END - SETTINGS BUTTON CANCEL /////////////////
			?>

			<?php 
				switch ($documentState){
							case '':
								$displayApproved = 'none';
								$displayCancelled = 'none';
								break;
							case 'SINVOICE_PENDANT':
								$displayApproved = 'inline';
								$displayCancelled = 'none';
								break;
							case 'SINVOICE_APPROVED':
								$displayApproved = 'none';
								$displayCancelled = 'inline';
								break;
							case 'SINVOICE_CANCELLED':
								$displayApproved = 'none';
								$displayCancelled = 'none';
								break;
						}
			?>
			<?php
			if($documentState == 'SINVOICE_PENDANT' OR $documentState == ''){
				echo $this->BootstrapForm->submit('Guardar Cambios',array('class'=>'btn btn-primary','div'=>false, 'id'=>'btnSaveAll'));	
			}
			?>
			<a href="#" id="btnApproveState" class="btn btn-success" style="display:<?php echo $displayApproved;?>"> Aprobar Factura de Compra</a>
			<a href="#" id="btnLogicDeleteState" class="btn btn-danger" style="display:<?php echo $displayApproved;?>"><i class=" icon-trash icon-white"></i> Eliminar</a>
			<a href="#" id="btnCancellState" class="btn btn-danger" style="display:<?php echo $displayCancelled;?>"> Cancelar Factura de Compra</a>
			<?php
				$displayPrint = 'none';
				if($id <> ''){
					$displayPrint = 'inline';
				}
				echo $this->Html->link('<i class="icon-print icon-white"></i> Imprimir', array('action' => 'view_document_movement_pdf', $id.'.pdf'), array('class'=>'btn btn-primary','style'=>'display:'.$displayPrint, 'escape'=>false, 'title'=>'Nuevo', 'id'=>'btnPrint', 'target'=>'_blank')); 

			?>
			<a href="#" id="btnGenerateMovements" class="btn btn-inverse" style="display:<?php echo $displayApproved;?>"> Ver Movimientos</a> 			
		
			
			
			
		</div>
	</div>
	<!-- //////////////////////////// End - buttons /////////////////////////////////-->

	<div class="widget-box">
		<div class="widget-title">
			<span class="icon">
				<i class="icon-edit"></i>								
			</span>
			<h5>Factura de Venta</h5>
			<span id="documentState" class="label <?php echo $documentStateColor;?>"><?php echo $documentStateName;?></span>
		</div>
		<div class="widget-content nopadding">
			
	<!-- //******************************** END - #UNICORN  WRAP FORM BOX PART 1/2 *************************************** -->
	
	<!-- ////////////////////////////////// START - FORM STARTS ///////////////////////////////////// -->
		<?php echo $this->BootstrapForm->create('SalSale', array('class' => 'form-horizontal'));?>
		<fieldset>
	<!-- ////////////////////////////////// END - FORM ENDS /////////////////////////////////////// -->			
				
				
				<!-- ////////////////////////////////// START FORM INVOICE FIELDS /////////////////////////////////////// -->
				<?php
				//////////////////////////////////START - block when APPROVED or CANCELLED///////////////////////////////////////////////////
				$disable = 'disabled';
				
				if($documentState == 'SINVOICE_PENDANT' OR $documentState == ''){
					$disable = 'enabled';
				}
				
				//////////////////////////////////END - block when APPROVED or CANCELLED///////////////////////////////////////////////////
				
				echo $this->BootstrapForm->input('purchase_hidden', array(
					'id'=>'txtPurchaseIdHidden',
					'value'=>$id,
					'type'=>'hidden'
				));
							
				echo $this->BootstrapForm->input('doc_code', array(
					'id'=>'txtCode',
					'label'=>'Código:',
					'style'=>'background-color:#EEEEEE',
					'disabled'=>$disable,
					'placeholder'=>'El sistema generará el código'
				));
				
				echo $this->BootstrapForm->input('origin_code', array(
					'id'=>'txtOriginCode',
					'label'=>'Documento Origen:',
					'style'=>'background-color:#EEEEEE',
					'disabled'=>$disable,
					'value'=>$originCode
				));
				
				echo $this->BootstrapForm->input('generic_code', array(
					'id'=>'txtGenericCode',
					'value'=>$genericCode,
					'type'=>'hidden'
				));
				
				echo $this->BootstrapForm->input('note_code', array(
					'id'=>'txtNoteCode',
					'label' => 'No. Nota de Remision:',
					'disabled'=>$disable
				));
				
				echo $this->BootstrapForm->input('date_in', array(
					'required' => 'required',
					'label' => 'Fecha:',
					'id'=>'txtDate',
					'value'=>$date,
					'disabled'=>$disable,
					'maxlength'=>'0',
					'class'=>'input-date-type'
				));				
				
				echo $this->BootstrapForm->input('sal_customer_id', array(
					'required' => 'required',
					'label' => 'Cliente:',
					'id'=>'cbxCustomers',
					'selected' => $customerId,
					'class'=>'input-xlarge',
					'disabled'=>$disable
				));
				
				echo '<div id="boxControllers">';
					echo $this->BootstrapForm->input('sal_employee_id', array(
						'required' => 'required',
						'label' => 'Encargado:',
						'id'=>'cbxEmployees',
						'class'=>'input-xlarge',
						'disabled'=>$disable
					));

					echo $this->BootstrapForm->input('sal_tax_number_id', array(
						'required' => 'required',
						'label' => 'NIT - Nombre:',
						'id'=>'cbxTaxNumbers',
						'class'=>'input-xlarge',
						'disabled'=>$disable
					));
				echo '</div>';
			
				echo $this->BootstrapForm->input('sal_adm_user_id', array(
					'required' => 'required',
					'label' => 'Vendedor:',
					'id'=>'cbxSalesman',
					'selected' => $admUserId,
					'class'=>'input-xlarge',
					'disabled'=>$disable
				));

				echo $this->BootstrapForm->input('description', array(
					'rows' => 2,
					'label' => 'Descripción:',
					'disabled'=>$disable,
					'id'=>'txtDescription'
				));
				
				echo $this->BootstrapForm->input('discount', array(
					'label' => 'Descuento:',
					'disabled'=>$disable,
					'id'=>'txtDiscount',
					'value'=>$discount,
					'type'=>'text'
				));
				
				echo '<div id="boxExRate">';
					echo $this->BootstrapForm->input('ex_rate', array(
						'label' => 'Tipo de Cambio:',
						'value'=>$exRate,
						'disabled'=>'disabled',
						'id'=>'txtExRate',
						'type'=>'text'
					));
				echo '</div>';
				?>
				<!-- ////////////////////////////////// END FORM INVOICE FIELDS /////////////////////////////////////// -->
				
					<!-- ////////////////////////////////// START MESSAGES /////////////////////////////////////// -->
					<div id="boxMessage"></div>
					<div id="processing"></div>
					<!-- ////////////////////////////////// END MESSAGES /////////////////////////////////////// -->
					
	<!-- ////////////////////////////////// START - END FORM ///////////////////////////////////// -->		
	</fieldset>
	<?php echo $this->BootstrapForm->end();?>
	<!-- ////////////////////////////////// END - END FORM ///////////////////////////////////// -->			
				
	<!-- //******************************** START - #UNICORN  WRAP FORM BOX PART 2/2 *************************************** -->
		</div> <!-- Belongs to: <div class="widget-content nopadding"> -->
	</div> <!-- Belongs to: <div class="widget-box"> -->
	<!-- //******************************** END - #UNICORN  WRAP FORM BOX PART 2/2 *************************************** -->
	
	<!-- ////////////////////////////////// START - INVOICE DETAILS /////////////////////////////////////// -->
	
	<div class="widget-box">
		<div class="widget-title">
			<ul class="nav nav-tabs">
				<li class="active"><a data-toggle="tab" href="#tab1">Items</a></li>
				<li><a data-toggle="tab" href="#tab2">Pagos</a></li>
			</ul>
		</div>
		<div class="widget-content tab-content">
			<div id="tab1" class="tab-pane active">
				<!-- ////////////////////////////////// START - INVOICE ITEMS DETAILS /////////////////////////////////////// -->
				<?php if($documentState == 'SINVOICE_PENDANT' OR $documentState == ''){ ?>
					<a class="btn btn-primary" href='#' id="btnAddItem" title="Adicionar Item"><i class="icon-plus icon-white"></i></a>
				<?php } ?>
						<?php $limit = count($salDetails); $counter = $limit;?>
						<table class="table table-bordered table-hover data-table" id="tablaItems">
							<thead>
								<tr>
									<th>Item ( <span id="countItems"><?php echo $limit;?> </span> )</th>
									<th>Precio Unitario</th>
									<th>Cantidad</th>
									<th>Almacen</th>
									<th>Stock</th>
									<th>Subtotal</th>
									<?php if($documentState == 'SINVOICE_PENDANT' OR $documentState == ''){ ?>
									<th class="columnItemsButtons"></th>
									<?php }?>
								</tr>
							</thead>
							<tbody>
								<?php
								$total = '0.00';
								for($i=0; $i<$limit; $i++){
									$subtotal = ($salDetails[$i]['cantidad'])*($salDetails[$i]['salePrice']);
									echo '<tr id="itemRow'.$salDetails[$i]['itemId'].'w'.$salDetails[$i]['warehouseId'].'">';	//REVISAR SI NECESITA O NO WAREHOUSEId																						//type="hidden" txtWarehouseId
										echo '<td><span id="spaItemName'.$salDetails[$i]['itemId'].'">'.$salDetails[$i]['item'].'</span><input type="hidden" value="'.$salDetails[$i]['itemId'].'" id="txtItemId" ></td>';
										echo '<td><span id="spaSalePrice'.$salDetails[$i]['itemId'].'w'.$salDetails[$i]['warehouseId'].'">'.$salDetails[$i]['salePrice'].'</span></td>';
										echo '<td><span id="spaQuantity'.$salDetails[$i]['itemId'].'w'.$salDetails[$i]['warehouseId'].'">'.$salDetails[$i]['cantidad'].'</span></td>';
										echo '<td><span id="spaWarehouse'.$salDetails[$i]['itemId'].'">'.$salDetails[$i]['warehouse'].'</span><input type="hidden" value="'.$salDetails[$i]['warehouseId'].'" id="txtWarehouseId'.$salDetails[$i]['itemId'].'" ></td>';
										echo '<td><span id="spaStock'.$salDetails[$i]['itemId'].'">'.$salDetails[$i]['stock'].'</span></td>';
										echo '<td><span id="spaSubtotal'.$salDetails[$i]['itemId'].'w'.$salDetails[$i]['warehouseId'].'">'.number_format($subtotal, 2, '.', '').'</span></td>';
										
										if($documentState == 'SINVOICE_PENDANT' OR $documentState == ''){
											echo '<td class="columnItemsButtons">';
											echo '<a class="btn btn-primary" href="#" id="btnEditItem'.$salDetails[$i]['itemId'].'w'.$salDetails[$i]['warehouseId'].'" title="Editar"><i class="icon-pencil icon-white"></i></a>
												
												<a class="btn btn-danger" href="#" id="btnDeleteItem'.$salDetails[$i]['itemId'].'w'.$salDetails[$i]['warehouseId'].'" title="Eliminar"><i class="icon-trash icon-white"></i></a>';
											echo '</td>';
										}
									echo '</tr>';	
									$total += $subtotal;
								}?>
							</tbody>
						</table>
					
				<div class="row-fluid"> <!-- vers si borrar este row-fluid creo q si -->
					
					<?php if($documentState == 'SINVOICE_APPROVED'){ ?>
						<div class="span10">	</div>
						<div class="span1">
							<h4>Total:</h4>	
						</div>
						<div class="span1">
							<h4 id="total" ><?php echo number_format($total, 2, '.', '').' Bs.'; ?></h4>
						</div>
					<?php }  else { ?>
						<div class="span8">	</div>
						<div class="span1">
							<h4>Total:</h4>	
						</div>
						<div class="span3">
							<h4 id="total" ><?php echo number_format($total, 2, '.', '').' Bs.'; ?></h4>
						</div>
					<?php }?>
					
				</div>
				<!-- ////////////////////////////////// END INVOICE ITEMS DETAILS /////////////////////////////////////// -->
			</div>
			<div id="tab2" class="tab-pane">
				<!-- ////////////////////////////////// START - INVOICE PAY DETAILS /////////////////////////////////////// -->
				<?php if($documentState == 'SINVOICE_PENDANT' OR $documentState == ''){ ?>
					<a class="btn btn-primary" href='#' id="btnAddPay" title="Adicionar Pago"><i class="icon-plus icon-white"></i></a>
				<?php } ?>
						<?php $limit2 = count($salPayments); $counter2 = $limit2;?>
						<table class="table table-bordered table-hover data-table" id="tablaPays">
							<thead>
								<tr>
									<th>Fecha Pago</th>
									<th>Monto</th>
									<th>Descripcion</th>
									<?php if($documentState == 'SINVOICE_PENDANT' OR $documentState == ''){ ?>
									<th class="columnPaysButtons"></th>
									<?php }?>
								</tr>
							</thead>
							<tbody>
								<?php
								$total2 = '0.00';
								for($i=0; $i<$limit2; $i++){
									echo '<tr id="payRow'.$salPayments[$i]['dateId'].'" >';
										echo '<td><span id="spaPayDate'.$salPayments[$i]['dateId'].'">'.$salPayments[$i]['payDate'].'</span><input type="hidden" value="'.$salPayments[$i]['dateId'].'" id="txtPayDate" ></td>';
										echo '<td><span id="spaPayAmount'.$salPayments[$i]['dateId'].'">'.$salPayments[$i]['payAmount'].'</span></td>';
										echo '<td><span id="spaPayDescription'.$salPayments[$i]['dateId'].'">'.$salPayments[$i]['payDescription'].'</span></td>';
										
										if($documentState == 'SINVOICE_PENDANT' OR $documentState == ''){
											echo '<td class="columnPaysButtons">';
											echo '<a class="btn btn-primary" href="#" id="btnEditPay'.$salPayments[$i]['dateId'].'" title="Editar"><i class="icon-pencil icon-white"></i></a>
												
												<a class="btn btn-danger" href="#" id="btnDeletePay'.$salPayments[$i]['dateId'].'" title="Eliminar"><i class="icon-trash icon-white"></i></a>';
											echo '</td>';
										}
									echo '</tr>';	
									$total2 += $salPayments[$i]['payAmount'];
								}?>
							</tbody>
						</table>
					
				<div class="row-fluid"> <!-- vers si borrar este row-fluid creo q si -->
					
					<?php if($documentState == 'SINVOICE_APPROVED'){ ?>
						<div class="span10">	</div>
						<div class="span1">
							<h4>Total:</h4>	
						</div>
						<div class="span1">
							<h4 id="total2" ><?php echo number_format($total2, 2, '.', '').' Bs.'; ?></h4>
						</div>
					<?php }  else { ?>
						<div class="span8">	</div>
						<div class="span1">
							<h4>Total:</h4>	
						</div>
						<div class="span3">
							<h4 id="total2" ><?php echo number_format($total2, 2, '.', '').' Bs.'; ?></h4>
						</div>
					<?php }?>
					
				</div>
				<!-- ////////////////////////////////// END INVOICE PAY DETAILS /////////////////////////////////////// -->
			</div>
		</div>                            
	</div>
								
	<!-- ////////////////////////////////// END INVOICE DETAILS /////////////////////////////////////// -->
	
<!-- ************************************************************************************************************************ -->
</div><!-- END CONTAINER FLUID/ROW FLUID/SPAN12 - MAIN Template #UNICORN -->
<!-- ************************************************************************************************************************ --> 





<!-- ////////////////////////////////// START MODAL (Esta fuera del span9 pero sigue pertenciendo al template principal CONTAINER FLUID/ROW FLUID) ////////////////////////////// -->
			<div id="modalAddItem" class="modal hide fade ">
				  
				  <div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
					<h3 id="myModalLabel">Cantidad Item</h3>
				  </div>
				  
				  <div class="modal-body">
					<!--<p>One fine body…</p>-->
					<?php
					echo '<div id="boxModalInitiateItemPrice">';
						//////////////////////////////////////
						echo $this->BootstrapForm->input('inv_warehouse_id', array(				
						'label' => 'Almacén:',
						'id'=>'cbxModalWarehouses',
						'class'=>'span6'
						));
					
						echo '<div id="boxModalItemPriceStock">';
							//////////////////////////////////////
							echo $this->BootstrapForm->input('items_id', array(				
							'label' => 'Item:',
							'id'=>'cbxModalItems',
							'class'=>'span12'
							));	
							echo '<div id="boxModalPrice">';
								$price='';
								echo $this->BootstrapForm->input('sale_price', array(				
								'label' => 'Precio Unitario:',
								'id'=>'txtModalPrice',
								'value'=>$price,
								'class'=>'span3',
								'maxlength'=>'15'
								));
							echo '</div>';	

							echo '<div id="boxModalStock">';
								$stock='';
								echo $this->BootstrapForm->input('stock', array(				
								'label' => 'Stock:',
								'id'=>'txtModalStock',
								'value'=>$stock,
								'disabled'=>'disabled',
								'style'=>'background-color:#EEEEEE',
								'class'=>'span3',
								'maxlength'=>'15'
								));
							echo '</div>';	
							//////////////////////////////////////
						echo '</div>';
						//////////////////////////////////////
					echo '</div>';

					echo $this->BootstrapForm->input('quantity', array(				
					'label' => 'Cantidad:',
					'id'=>'txtModalQuantity',
					'class'=>'span3',
					'maxlength'=>'10'
					));
					?>
					  <div id="boxModalValidateItem" class="alert-error"></div> 
				  </div>
				  
				  <div class="modal-footer">
					<a href='#' class="btn btn-primary" id="btnModalAddItem">Guardar</a>
					<a href='#' class="btn btn-primary" id="btnModalEditItem">Guardar</a>
					<button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
					
				  </div>
					
			</div>
<!-- ////////////////////////////////// FIN MODAL (Esta fuera del span9 pero sigue pertenciendo al template principal CONTAINER FLUID/ROW FLUID) ////////////////////////////// -->



<!-- ////////////////////////////////// INICIO MODAL PAYS(Esta fuera del span9 pero sigue pertenciendo al template principal CONTAINER FLUID/ROW FLUID) ////////////////////////////// -->
			<div id="modalAddPay" class="modal hide fade ">
				  
				  <div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
					<h3 id="myModalLabel">Pagos</h3>
				  </div>
				  
				  <div class="modal-body">
					<!-- class="control-group"--> 
					<?php
					echo '<div id="boxModalInitiatePay">';
						$datePay = '';
						echo $this->BootstrapForm->input('date', array(	
								'label' => 'Fecha:',
								'id'=>'txtModalDate',
								'value'=>$datePay,
								'class'=>'span3',
								'maxlength'=>'15'
								));
//					
						$payDebt = '';
						echo $this->BootstrapForm->input('amount', array(				
								'label' => 'Monto a Pagar:',
								'id'=>'txtModalPaidAmount',
								'value'=>$payDebt,
								'class'=>'span3',
								'maxlength'=>'15'
								));
					echo '</div>';
					
					echo $this->BootstrapForm->input('description', array(				
							'label' => 'Descripcion:',
							'id'=>'txtModalDescription',
							'class'=>'span9',
							'rows' => 2
							));
					
					echo $this->BootstrapForm->input('amount_hidden', array(				
							'id'=>'txtModalAmountHidden',
							'type'=>'hidden'
							));

					?>
					  <div id="boxModalValidatePay" class="alert-error"></div> 
				  </div>
				  
				  <div class="modal-footer">
					 <!-- Ztep 0 Save button from modal triggers btnModalAddItem -->
					<a href='#' class="btn btn-primary" id="btnModalAddPay">Guardar</a>
					<a href='#' class="btn btn-primary" id="btnModalEditPay">Guardar</a>
					<button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
					
				  </div>
					
			</div>
<!-- ////////////////////////////////// FIN MODAL PAYS (Esta fuera del span9 pero sigue pertenciendo al template principal CONTAINER FLUID/ROW FLUID) ////////////////////////////// -->