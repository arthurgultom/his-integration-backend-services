<?php
$this->breadcrumbs = array(
    Yii::t('menu','sAdvancedMoney') => array('index'),
    Yii::t('menu','subApproval'),
);
?>

<div class="page-header"><h3><?php echo Yii::t('menu','sAdvancedMoney'); ?> - <?php echo Yii::t('menu','subApproval');?></h3></div>

<div style="overflow:auto">
<?php
$this->widget('zii.widgets.grid.CGridView', array(
	'id'=>'pum-approval-grid',
	'dataProvider'=>$model->searchNeedApproval(Yii::app()->user->id),
	'filter'=>$model,
    // 'htmlOptions' => array('class' => 'table-responsive'),
    'itemsCssClass' => 'table table-striped table-bordered table-hover',
    'rowCssClassExpression' => '$row%2?"success":"even"',
    'pager' => array('class' => 'CLinkPager', 'header' => ''),
    'pagerCssClass' => 'pagination',
    'summaryCssClass' => 'dataTables_info',
    'afterAjaxUpdate' => 'function(id, data) {
        // Reinitialize the date picker after AJAX update
        $.datepicker.setDefaults($.datepicker.regional["en-GB"]);
        $("#'.CHtml::activeId($model, 'adv_mon_date').'").datepicker({
            dateFormat: "yy-mm-dd",
            changeYear: true,
            changeMonth: true,
            showButtonPanel: true,
        });
        $("#'.CHtml::activeId($model, 'on_date').'").datepicker({
            dateFormat: "yy-mm-dd",
            changeYear: true,
            changeMonth: true,
            showButtonPanel: true,
        });
    }',
    'columns'=>array(
	    array(
            'class' => 'CButtonColumn',
            'htmlOptions' => array('width' => '75px', 'valign' => 'middle'),
            'template' => '{update}',
            'buttons' => array(
                'update' => array(
                    'imageUrl' => false,
                    'label' => '  View',
                    'options' => array('class' => 'fa fa-edit btn btn-info btn-xs'),
                    'url'=>'Yii::app()->createUrl("advancemoney/update", array("id"=>$data["adv_mon_id"], "approval_id"=>Yii::app()->user->id, "read"=>"1"))',
                    'visible'=>'($data->status != 2)',
                )
            ),
        ),
        array(
            'name'=>'search_status',
            'type'=>'html',
            'filter'=>CHtml::activeDropDownList($model, 'search_status', array('Draft', 'In Progress', 'Approved', 'Rejected'), array('prompt' => '', 'class' => 'form-control input-sm')),
            'htmlOptions' => array('width' => '10%'),
            'value'=>function($data){
                if($data->rel_status->status == 'Draft'){
                    $color = 'grey';
                }else if($data->rel_status->status == 'In Progress'){
                    $color = 'blue';
                }else if($data->rel_status->status == 'Approved'){
                    $color = 'green';
                }else{
                    $color = 'red';
                }
                return '<span style="color: '.$color.';font-weight: bold;">' . CHtml::encode($data->rel_status->status) . '</span>';
            }
        ),
        array(
            'name'=>'adv_mon_id',
            'type'=>'html',
            'filter'=>CHtml::activeTextField($model, 'adv_mon_id', array('class' => 'form-control input-sm')),
            'htmlOptions' => array('width' => '15%'),
        ),
        array(
            'name' => 'sppd_id',
            'type' => 'html',
            'header' => 'SPPD ID',
            'value' => function ($data) {
                return '<a style="color: blue !important;font-weight: bold;" href="' . Yii::app()->baseUrl . '/index.php?r=businesstravel/view&id=' . $data->sppd_id . '">' . $data->sppd_id . '</a>';
            },
            'filter' => CHtml::activeTextField($model, 'sppd_id', array('class' => 'form-control input-sm')),
            'htmlOptions' => array('width' => '10%'),
        ),
        array(
            'name' => 'emp_no',
            'value' => '$data->emp_no',
            'filter' => CHtml::activeTextField($model, 'emp_no', array('class' => 'form-control input-sm')),
            'htmlOptions' => array('width' => '7%'),
        ),
        array(
            'name' => 'search_emp_name',
            'value' => '$data->rel_emp->emp_name',
            'header' => 'Name',
            'filter' => CHtml::activeTextField($model, 'search_emp_name', array('class' => 'form-control input-sm')),
            'htmlOptions' => array('width' => '15%'),
        ),
        array(
            'name' => 'division_id',
            'header' => 'Division',
            'value' => '$data->getDivisionName()',
            'filter' => CHtml::activeTextField($model, 'division_id', array('class' => 'form-control input-sm')),
            'htmlOptions' => array('width' => '7%'),
        ),
        array(
            'name' => 'adv_mon_date',
            'header' => 'PUM Date',
            'filter' => $this->widget('zii.widgets.jui.CJuiDatePicker', array(
                'model' => $model,
                'attribute' => 'adv_mon_date',
                'language' => 'en-GB',
                'options' => array(
                    'dateFormat' => 'yy-mm-dd',
                    'changeYear' => true,
                    'changeMonth' => true,
                    'showButtonPanel' => true,
                ),
                'htmlOptions' => array(
                    'class' => 'form-control input-sm',
                ),
            ), true),
            'htmlOptions' => array('width' => '10%'),
        ),
        array(
            'header' => 'Travel Date',
            'value' => 'isset($data->rel_sppd) ? $data->rel_sppd->departure_date . " - " . $data->rel_sppd->arrival_date : "-"',
            'htmlOptions' => array('width' => '15%'),
        ),
        array(
            'header' => 'Trip',
            'value' => function($data) {
                $from = '';
                $to = '';
                if(isset($data->sppd_id)){
                     $destinations = BusinessTravelDestinationModel::model()->findAllByAttributes(array('sppd_id'=>$data->sppd_id), array('order'=>'dest_id ASC'));
                     if($destinations){
                         $first = $destinations[0];
                         $last = end($destinations);
                         if(isset($first->rel_from)) $from = $first->rel_from->city_name;
                         if(isset($last->rel_to)) $to = $last->rel_to->city_name;
                     }
                }
                if($from == '' && $to == '') return '-';
                return $from . ' - ' . $to;
            },
            'htmlOptions' => array('width' => '15%'),
        ),
        array(
            'header' => 'PUM Amount',
            'name' => 'amount',
            'value' => '$data->currency_id." ".number_format($data->grand_total, 0, ",", ".")',
            'htmlOptions' => array('width' => '10%', 'style'=>'text-align:right'),
        ),
        array(
            'name' => 'on_date',
            'header' => 'Prepare Date',
            'filter' => $this->widget('zii.widgets.jui.CJuiDatePicker', array(
                'model' => $model,
                'attribute' => 'on_date',
                'language' => 'en-GB',
                'options' => array(
                    'dateFormat' => 'yy-mm-dd',
                    'changeYear' => true,
                    'changeMonth' => true,
                    'showButtonPanel' => true,
                ),
                'htmlOptions' => array(
                    'class' => 'form-control input-sm',
                ),
            ), true),
            'htmlOptions' => array('width' => '10%'),
        ),
        array(
            'name' => 'transfer_status',
            'type' => 'html',
            'header' => 'Transfer',
            'value' => function ($data) {
                if ($data->transfer_status == 1) {
                    $color = 'green';
                    $label = 'Done';
                } else {
                    $color = 'red';
                    $label = 'Not Yet';
                }
                return '<span style="color: ' . $color . ';font-weight: bold;">' . $label . '</span>';
            },
            'filter' => CHtml::activeDropDownList($model, 'transfer_status', array('Not Yet', 'Done'), array('prompt' => '', 'class' => 'form-control input-sm')),
            'htmlOptions' => array('width' => '10%'),
        ),
        array(
            'name' => 'paid_status',
            'type' => 'html',
            'header' => 'Settlement',
            'value' => function ($data) {
                if ($data->paid_status == 1) {
                    $color = 'green';
                    $label = 'Paid';
                } else {
                    $color = 'red';
                    $label = 'Unpaid';
                }
                return '<span style="color: ' . $color . ';font-weight: bold;">' . $label . '</span>';
            },
            'filter' => CHtml::activeDropDownList($model, 'paid_status', array('Unpaid', 'Paid'), array('prompt' => '', 'class' => 'form-control input-sm')),
            'htmlOptions' => array('width' => '10%'),
        ),
    ),
));
?>
</div>
