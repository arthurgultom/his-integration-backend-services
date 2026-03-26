<?php
/* @var $this AdvanceMoneyController */
/* @var $model AdvanceMoneyModel */

$this->breadcrumbs = array(
	'Advance Money Models' => array('index'),
	'Create',
);

?>

<?php if ($this->isAllowed(Yii::app()->user->id, 'create')) { ?>

	<div class="page-header">
		<h3>Create Advance Money Request Form</h3>
	</div>

	<?php $this->renderPartial('_form', array(
		'model' => $model,
		'emplist' => $emplist,
		'transportationlist' => $transportationlist,
		'currencylist' => $currencylist,
		'model_detail' => $model_detail,
		'classlist' => $classlist,
		'outstanding_pum' => $outstanding_pum,
		'model_employee' => $model_employee
	)); ?>

<?php

} else {

?>

	<div class="alert alert-danger alert-dismissable">You are not authorized to access this page</div>

<?php } ?>