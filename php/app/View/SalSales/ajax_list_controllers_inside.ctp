<?php
echo $this->BootstrapForm->input('sal_employee_id', array(
					'required' => 'required',
					'label' => 'Encargado:',
					'class'=>'input-xlarge',
					'id'=>'cbxEmployees'
				));


echo $this->BootstrapForm->input('sal_tax_number_id', array(
					'required' => 'required',
					'label' => 'NIT - Nombre:',
					'class'=>'input-xlarge',
					'id'=>'cbxTaxNumbers'
				));
?>