<?php
if(Yii::app()->user->hasFlash('success')):?>
    <div class="alert alert-danger">
        <?php echo Yii::app()->user->getFlash('success'); ?>
    </div>
<?php endif; ?>

<?php
/* @var $this AdvanceMoneyController */
/* @var $model AdvanceMoneyModel */
/* @var $form CActiveForm */
?>
<script type='text/javascript'>
//function sum() added by Doris Heryanto
//Date Time : Wed, Jan 27, 2016 at 12:07 AM
//Modified Date : Fri, Jan 29, 2016 at 1:29 AM
//Purpose : To get sum calculation : cat 2 + cat 3 + cat 5
function sumCalculate() {
    var amount_pum = document.getElementById('amount').value;
    var others_pum = document.getElementById('others').value;
    
    if(parseInt(others_pum) < 0 || others_pum == ''){
        others_pum = 0;
    }

    var result = parseInt(amount_pum) + parseInt(others_pum);
    if (!isNaN(result)) {
        document.getElementById('total').value = result;
    }
    
    document.getElementById('others').value = parseInt(others_pum);
}

$(document).ready(function() {

    // SEND BACK
    $('#send_back').click(function() {
        var response_text = $("#response_text").val();
        if(response_text == "" || response_text.length == 0 ){
            alert("Please input your response first ! ");
            return false;
        }else{
            $("#modalSendBack").modal();
        }
    });
    
    $('#confirm_send_back').click(function() {
        var adv_mon_id =$("#AdvanceMoneyModel_adv_mon_id").val(); 
        var response_text = $("#response_text").val();
        //alert(id_doc+" "+repair_date+" "+doc_date);
        var baseUrl = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=advancemoney/sendback';

        $.ajax({
            type: 'POST',
            url: baseUrl,
            data:"id="+adv_mon_id+"&response_text="+response_text,
            beforeSend: function(data)
            {
                $('#loading').modal('show');
            },
            success:function(data){
                $('#loading').modal('hide');
                alert("PUM ("+adv_mon_id+") successfully has been revised !");
                window.location = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=site/ListPumApproval';
            },
            error: function(data) { // if error occured
                 alert("Error occured.please try again");
                 alert(data);
            },
            dataType:'html'
        });
    });
    
    // REJECT
    $('#reject').click(function() {
        var response_text = $("#response_text").val();
        if(response_text == "" || response_text.length == 0 ){
            alert("Please input your response first ! ");
            return false;
        }else{
            $("#modalReject").modal();
        }
    });
    
    $('#confirm_reject').click(function() {
        var adv_mon_id =$("#AdvanceMoneyModel_adv_mon_id").val();
        var response_text = $("#response_text").val();
        //alert(id_doc+" "+repair_date+" "+doc_date);
        var baseUrl = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=advancemoney/reject';

        $.ajax({
            type: 'POST',
            url: baseUrl,
            data:"id="+adv_mon_id+"&response_text="+response_text,
            beforeSend: function(data)
            {
                $('#loading').modal('show');
            },
            success:function(data){
                $('#loading').modal('hide');
                alert("PUM ("+adv_mon_id+") successfully has been rejected !");
                window.location = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=site/ListPumHistory';
            },
            error: function(data) { // if error occured
                 alert("Error occured.please try again");
                 alert(data);
            },
            dataType:'html'
        });
    });
    
    // SEND To APPROVER
    $('#send_to_approver').click(function() {
        var response_text = $("#response_text").val();
        if (confirm('Are you sure want to Send this PUM ?')) {
            send_to_approver();
        }else{
            return false;
        }
    });
    
    function send_to_approver(){

        var adv_mon_id =$("#AdvanceMoneyModel_adv_mon_id").val();
        var response_text = $("#response_text").val();
        var remark = $("#AdvanceMoneyModel_remark").val();
        var towards = $("#AdvanceMoneyModel_towards").val();
        var on_date = $("#AdvanceMoneyModel_on_date").val();
        var budget_code = $("#AdvanceMoneyModel_budget_code").val();
        var others = $("#others").val();
        
        // alert(adv_mon_id+" "+remark+" "+towards+" "+on_date+" "+budget_code+" "+others);
        
        var baseUrl = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=advancemoney/sendtoapprover';
        $.ajax({
            type: 'POST',
            url: baseUrl,
            data:"id="+adv_mon_id+"&response_text="+response_text+"&remark="+remark+"&towards="+towards+"&on_date="+on_date+"&others="+others+"&budget_code="+budget_code,
            beforeSend: function(data)
            {
                <?php /* ?>
                $('.loadingx').hide().html("<img src='<?php echo Yii::app()->request->baseUrl;?>/image/ajax-loader.gif' />").fadeIn('slow');
                <? */ ?>
                $('#loading').modal('show');
        
            },
            success:function(data){
                $('#loading').modal('hide');
                try {
                    var resp = JSON.parse(data);
                    // console.log(resp.error);
                    if (typeof resp.error !== 'undefined') {
                        alert("Please input your Destination first !");
                    }  
                }
                catch(err) {
                    // alert("Success, Your request already sent to approver !");
                    $('.loadingx').hide().html('loading...');
                    window.location = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=advancemoney/update&id='+adv_mon_id+' ';   
                }
            },
            error: function(data) { // if error occured
                alert("Error occured.please try again");
                console.log(JSON.stringify(data));
            },
            dataType:'html'
        });

    }
    
    $('#send_response').click(function() {
        var response_text = $("#response_text").val();
           
        if (confirm('Are you sure ?')) {
            if(response_text == "" || response_text.length == 0 ){
                alert("Input response harus diisi ! ");
                return false;
            }else{
                send_response();
            }
        }
    });

    function send_response(){

      var adv_mon_id =$("#AdvanceMoneyModel_adv_mon_id").val(); 
      var response_text = $("#response_text").val();
      //alert(id_doc+" "+repair_date+" "+doc_date);
      var baseUrl = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=advancemoney/sendresponse';

      $.ajax({
           type: 'POST',
           url: baseUrl,
           data:"adv_mon_id="+adv_mon_id+"&response_text="+response_text,
           beforeSend: function(data)
            {
                <?php /* ?>
                $('.loadingx').hide().html("<img src='<?php echo Yii::app()->request->baseUrl;?>/image/ajax-loader.gif' />").fadeIn('slow');
                <? */ ?>
                $('#loading').modal('show');
                
            },
           success:function(data){
                $('#loading').modal('hide');
                alert("Success, sending response !"); 
                $('.loadingx').hide().html('loading...');
                window.location = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=advancemoney/update&id='+adv_mon_id+' ';
           },
           error: function(data) { // if error occured
                 alert("Error occured.please try again");
                 alert(data);
            },
            dataType:'html'
      });

    }

    function send_back(){

      var adv_mon_id =$("#AdvanceMoneyModel_adv_mon_id").val(); 
      var response_text = $("#response_text").val();
      //alert(id_doc+" "+repair_date+" "+doc_date);
      var baseUrl = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=advancemoney/sendback';

      $.ajax({
           type: 'POST',
           url: baseUrl,
           data:"adv_mon_id="+adv_mon_id+"&response_text="+response_text,
           beforeSend: function(data)
            {
                <?php /* ?>
                $('.loadingx').hide().html("<img src='<?php echo Yii::app()->request->baseUrl;?>/image/ajax-loader.gif' />").fadeIn('slow');
                <? */ ?>
                $('#loading').modal('show');
                
            },
           success:function(data){
                $('#loading').modal('hide');
                alert("Success, sending response !"); 
                $('.loadingx').hide().html('loading...');
                window.location = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=advancemoney/update&id='+adv_mon_id+' ';
           },
           error: function(data) { // if error occured
                 alert("Error occured.please try again");
                 alert(data);
            },
            dataType:'html'
      });

    }
    
    // ADDING CUSTOM VALIDATION
    document.getElementById('advance-money-model-form').addEventListener('submit', function(e) {
        const input = document.getElementById('AdvanceMoneyModel_budget_code');
        const value = input.value.trim();
    
        if (value === '') {
            e.preventDefault(); // stop form submission
            alert('Budget Code Field is required!');
            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
            input.style.border = '1px solid #D0372D';
            setTimeout(() => input.focus(), 300);
        }
    });

});

</script>

<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'advance-money-model-form',
	// Please note: When you enable ajax validation, make sure the corresponding
	// controller action is handling ajax validation correctly.
	// There is a call to performAjaxValidation() commented in generated controller code.
	// See class documentation of CActiveForm for details on this.
	'enableAjaxValidation'=>false,
	'enableClientValidation' => true,
)); ?>

<?php if (!$model->isNewRecord) { ?>
    <?php
    $color = "";
    if ($model->status == '0') {
        $color = "warning";
        $icon = "fa-list";
    } elseif ($model->status == '1') {
        $color = "info";
        $icon = "fa-send ";
    } elseif ($model->status == '2') {
        $color = "success";
        $icon = "fa-check";
    } elseif ($model->status == '3') {
        $color = "danger";
        $icon = "fa-times";
    } elseif ($model->status == '4') {
        $color = "success";
        $icon = "fa-check";
    }

    if($model->status < 1){
        $visible_status = true;
    }else{
        $visible_status = false;
    }
    ?>
    <center><div class='loadingx'></div></center>
    <div class="alert alert-<?php echo $color; ?>">
        <button class="btn btn-<?php echo $color; ?> btn-circle btn-sm" type="button"><i class="fa <?php echo $icon; ?>"></i></button>
        Your Current Status is <b><?php echo $model->rel_status->status; ?></b>
    </div>
<?php } ?>

<div class="panel panel-success">
    <div class="panel-heading"><p class="note">Fields with <span class="required">*</span> are required.</p></div>
    <div class="panel-body">

        <?php echo $form->errorSummary($model, '', '', array('class' => 'alert alert-danger alert-dismissable')); ?>

        <!-- Header Section -->
        <div class="panel panel-default">
        <div class="panel-heading">Header Information</div>
        <div id="section4" class="panel-expand expand">
            <div class="panel-body">
                <div class="table-responsive">

                    <div class="table-responsive">
        				<div class='col-lg-6 col-md-6 col-sm-12'>
                            <table style="width: 100%;">
								<tr>
			                        <td>
			                            <?php echo $form->labelEx($model, 'adv_mon_id'); ?>
			                        </td>
			                        <td>
			                            <?php echo $form->textField($model, 'adv_mon_id', array('size' => 20, 'maxlength' => 20, 'class' => 'form-control input-sm', 'tab_index' => 1, 'readOnly' => true)); ?>                                       
			                        </td>
			                    </tr>
			                    <tr>
			                        <td>
			                            <?php echo $form->labelEx($model, 'adv_mon_date'); ?>
			                        </td>
			                        <td>
			                            <?php echo $form->textField($model, 'adv_mon_date', array('class' => 'form-control input-sm', 'tab_index' => 2, 'readOnly' => true)); ?>
			                        </td>
			                    </tr>
								<tr>
									<td><?php echo $form->labelEx($model,'adv_prepare'); ?></td>
									<td>
										<?php
										echo $form->dropDownList($model,'adv_prepare', array('Money'=>'Money', 'Cheque'=>'Cheque', 'Giro'=>'Giro'),array('class'=>'form-control', 'disabled' => ($model->status == 0)?'':'disabled'));?>
									
									</td>
								</tr>
								<tr>
			                        <td>
			                            <?php echo $form->labelEx($model, 'emp_no'); ?>
			                        </td>
			                        <td>
			                            <?php echo $form->textField($model_employee, 'emp_no', array('class' => 'form-control input-sm', 'tab_index' => 2, 'readOnly' => true)); ?>
			                        </td>
			                    </tr>
								<tr>
			                        <td>
			                            <?php echo $form->labelEx($model, 'emp_name'); ?>
			                        </td>
			                        <td>
			                            <?php echo $form->textField($model_employee, 'emp_name', array('class' => 'form-control input-sm', 'tab_index' => 2, 'readOnly' => true)); ?>
			                        </td>
			                    </tr>
								<tr>
									<td style="vertical-align: text-top;"><?php echo $form->labelEx($model,'towards'); ?></td>
									<td><?php echo $form->textArea($model,'towards',array('rows'=>6, 'cols'=>50,'class'=>'form-control input-sm', 'disabled' => ($model->status == 0)?'':'disabled')); ?></td>
								</tr>

							</table>
				
						</div>
						
        				<div class='col-lg-6 col-md-6 col-sm-12'>
                            <table style="width: 100%;">
								<tr>
									<td>
									    <?php
                                        $yesterday = date('Y-m-d', strtotime($model_businessTravel->departure_date . " - 1 day"));
                                        echo $form->labelEx($model,'on_date'); 
                                        ?>
                                    </td>
									<td>
									    <?php $this->widget('zii.widgets.jui.CJuiDatePicker', array(
                                            'model' => $model,
                                            'attribute' => 'on_date',
                                            'options' => array(
                                                'dateFormat' => 'yy-mm-dd',
                                                'minDate'=>0,
                                            ),
                                            'htmlOptions' => array(
                                                'class' => 'form-control input-sm',
                                                'disabled' => ($yesterday == date('Y-m-d') OR $model->status != 0)?'disabled':'',
                                            )
                                        ));
                                        ?> 
                                    </td>
								</tr>
								<?php /*
								<tr>
									<td><?php echo $form->labelEx($model,'currency_id'); ?></td>
									<td><?php echo $form->dropDownList($model, 'currency_id', $currencylist, array('prompt' => 'Please select option', 'class' => 'form-control input-sm', 'disabled' => ($model->status == 0)?'':'disabled')); ?></td>
								</tr>
								<tr>
									<!--<td><?php echo $form->labelEx($model,'amount'); ?></td>-->
									<td></td>
								</tr>
								*/ ?>
								<?php echo $form->hiddenField($model,'amount',array('type'=>"hidden", 'size'=>10,'maxlength'=>10,'class' => 'form-control input-sm', 'id'=>'amount', 'onkeyup'=>'sumCalculate()', 'readOnly' => true, 'disabled' => ($model->status == 0)?'':'disabled')); ?>
                                <tr>
                                    <td><?php echo $form->labelEx($model,'others'); ?> (<?php echo $model->currency_id; ?>)</td>
                                    <td>
                                        <?php 
                                        //$model->others = number_format($model->others);
                                        echo $form->textField($model, 'others', array(
                                                'size' => 10,
                                                'maxlength' => 10,
                                                'class' => 'form-control input-sm', 
                                                'id' => 'others', 
                                                'onkeyup' => 'sumCalculate()', 
                                                'disabled' => ($model->status == 0)?'':'disabled'
                                            )
                                        ); 
                                        ?>
                                    </td>
                                </tr>
                                <tr>
			                        <td style="vertical-align: text-top;padding-top: 15px;">
			                            <?php echo $form->labelEx($model, 'nomor_rekening'); ?>
			                        </td>
			                        <td>
			                            <?php 
			                            echo $form->textField($model, 'bank_acc', array(
			                                'class' => 'form-control input-sm', 
			                                'tab_index' => 2, 
			                                'readOnly' => true, 
			                                'value' => $model_employee->nomor_rekening
		                                )); 
		                                
		                                /*
		                                if(($model_employee->nomor_rekening == '' or $model_employee->nomor_rekening == null) and $model->created_by == Yii::app()->user->id and $model->status==0){
		                                ?>
		                                <div style="font-style: italic;padding: 4px;margin-top: 10px;">
		                                    Please update your Bank Account Number <?php echo CHtml::link(
                						        Yii::t('component', 'Here'), 
                						        array('employee/updateBank', 'id'=>Yii::app()->user->id), 
                						        array('class'=>'btm btn-sm btn-warning block')
                					        ); ?>
		                                </div>
		                                <?php
		                                }
		                                */
		                                ?>
			                        </td>
			                    </tr>
								
								<!--nama rekening-->
								<tr>
									<td>
										<?php echo $form->labelEx($model, 'nama_rekening');?>
									</td>
									<td>
										<?php 
										echo $form->textField($model, 'nama_rekening', array(
										    'class' => 'form-control input-sm', 
										    'disabled' => ($model->status == 0)?'':'disabled',
										    'value' => $model_employee->emp_name
									    ));?>
									</td>
								</tr>
                                <tr>
                                    <td><?php echo $form->labelEx($model,'budget_code'); ?></td>
                                    <td>
										<?php 
										if($model_employee->division_id == 'MGT'){
    										echo $form->textField($model, 'budget_code', array(
    										    'class'=>'form-control input-sm', 
    										    'value'=>'J00168',
    										    'readonly'=>true,
    										    'disabled'=> ($model->status == 0) ? '' : 'disabled')
    									    );
										}else{
										    echo $form->textField($model, 'budget_code', array(
    										    'class'=>'form-control input-sm',
    										    'disabled'=> ($model->status == 0) ? '' : 'disabled')
    									    );
										}
									    ?>
									</td>
                                </tr>
								<!--nama rekening-->
								
                                <tr>
                                    <td style="vertical-align: text-top;"><?php echo $form->labelEx($model,'remark'); ?></td>
                                    <td><?php echo $form->textArea($model,'remark',array('rows'=>6, 'cols'=>50,'class'=>'form-control input-sm', 'disabled' => ($model->status == 0)?'':'disabled')); ?></td>
                                </tr>
                                <!--<tr>
                                    <td><?php echo $form->labelEx($model,'bank_acc'); ?></td>
                                    <td><?php echo $form->textField($model,'bank_acc',array('size'=>10,'maxlength'=>30,'class' => 'form-control input-sm', 'disabled' => ($model->status == 0)?'':'disabled')); ?></td>
                                </tr>-->
							</table>
						</div>
                    </div>

                </div>  
            </div>
        </div>
        </div>
        <!-- Header Section -->

        <!-- Approval List Section -->
        <?php if($model->status >= 1){ ?>
        <div class="panel panel-default">
            <div class="panel-heading">Approval List</div>
            <div id="section4" class="panel-expand expand">
                <div class="panel-body">
                    <div align="center" class="table-responsive">

                        <table class='table table-striped table-bordered table-hover'>
                            <tr>
                                <th>Name</th>
                                <th>Approval Date</th>
                                <th>Approval Comment</th>
                                <th>Status Approval</th>
                            </tr>
                            <?php
                            foreach ($list_approval as $row) {

                                $app_approval = "";

                                if($row['approver_flag'] == '2'){
                                    $app_approval = "Approved";
                                }elseif($row['approver_flag'] == '3'){
                                    $app_approval = "Rejected";
                                }else{
                                    $app_approval = "-";
                                }

                                $delegated_by = '';

                                if(!empty($row['approver_delegate_id'])){
                                    $delegated_by = "<b style='color:blue;'>( Delegated By : ".$row['approver_delegate_id']." )</b>";
                                }else{
                                    $delegated_by = '';
                                }
                            ?>
                             <tr>
                                <td><?php  echo $row['emp_name']; ?> <?php echo $delegated_by;?></td>
                                <td><?php  echo $app_date = (empty($row['approver_date']))?"-":$row['approver_date'];?></td>
                                <td><?php  echo $app_comment = (empty($row['approver_comment']))?"-":$row['approver_comment'];?></td>
                                <td><?php  echo $app_approval; ?></td>
                                
                             </tr>
                            <?php
                            }
                            ?>
                        </table>

                    </div>  
                </div>
            </div>
        </div>
        <?php } ?>
        <!-- Approval List Section -->

        <!-- Outstanding PUM List Section -->
        <?php if(count($outstanding_pum) > 0){ ?>
        <div class="panel panel-danger">
            <div class="panel-heading">Outstanding PUM List</div>
            <div id="section4" class="panel-expand expand">
                <div class="panel-body">
                    <div align="center" class="table-responsive">

                        <table class='table table-striped table-bordered table-hover'>
                            <tr>
                                <th>Document No</th>
                                <th>Document Date</th>
                                <th>Amount</th>
                                <th>Towards</th>
                            </tr>
                            <?php
                            foreach ($outstanding_pum as $row) {
                            ?>
                                 <tr>
                                    <td><?php  echo CHtml::link($row['adv_mon_id'], Yii::app()->baseUrl.'/index.php?r=advancemoney/view&id='.$row['adv_mon_id'], array('target'=>'_blank')); ?></td>
                                    <td><?php  echo $row['adv_mon_date']; ?></td>
                                    <td><?php  echo $row['grand_total']; ?></td>
                                    <td><?php  echo $row['towards']; ?></td>
                                 </tr>

                            <?php
                            }
                            ?>
                        </table>

                    </div>  
                </div>
            </div>
        </div>
        <?php } ?>
        <!-- Outstanding PUM List Section -->

        <?php if(!empty($model->sppd_id)){ ?>    
        <!-- Business Travel Information Section -->
        <div class="panel panel-info">
            <div class="panel-heading">Business Travel Information</div>
            <div id="section4" class="panel-expand expand">
                <div class="panel-body">
                    
                    <div class="row table-responsive" style="margin-bottom: 15px;">
        				<div class="col-sm-6">
                            <table style="width:100%;">
                                <tr>
                                    <td style="width: 33%;">SPPD ID</td>
                                    <td style="width: 2%;text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo $sppd_data['sppd_id']; ?>" /></td>
                                </tr>
                                <tr>
                                    <td>SPPD Date</td>
                                    <td style="text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo $sppd_data['sppd_date']; ?>" /></td>
                                </tr>
                                <tr>
                                    <td>NIK</td>
                                    <td style="text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo $sppd_data['emp_no']; ?>" /></td>
                                </tr>
                                <tr>
                                    <td>Name</td>
                                    <td style="text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo Yii::app()->globalFunction->get_user_name($sppd_data['emp_no']); ?>" /></td>
                                </tr>
                                <tr>
                                    <td>Divisi</td>
                                    <td style="text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo Yii::app()->globalFunction->get_user_division_name($sppd_data['emp_no']); ?>" /></td>
                                </tr>
                            </table>
                        </div>
                        <div class='col-sm-6'>
                            <table style="width:100%;">
                                <tr>
                                    <td style="width: 33%;">Trip Type</td>
                                    <td style="width: 2%;text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo Yii::app()->globalFunction->get_trip_type_name($sppd_data['trip_id']); ?>" /></td>
                                </tr>
                                <tr>
                                    <td>Instructed By</td>
                                    <td style="text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo Yii::app()->globalFunction->get_user_name($sppd_data['instructed_by']); ?>" /></td>
                                </tr>
                                <tr>
                                    <td>Location Name</td>
                                    <td style="text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo Yii::app()->globalFunction->get_user_name($sppd_data['emp_no']); ?>" /></td>
                                </tr>
                                <tr>
                                    <td style="vertical-align: text-top;">Purpose</td>
                                    <td style="vertical-align: text-top;text-align: right;">:</td>
                                    <td>
                                        <textarea class="form-control input-sm" readonly="readonly" rows="4"><?php echo $sppd_data['purpose']; ?></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="panel panel-info">
                        <div class="panel-heading">Destination List</div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class='table table-striped table-bordered table-hover'>
                                    <tr>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Start Trip</th>
                                        <th>End Trip</th>
                                        <th>Days</th>
                                        <th>Currency</th>
                                        <th>Meal</th>
                                        <th>Allowance</th>
                                        <th>Hotel</th>
                                        <th>Total</th>
                                    </tr>
                                    <?php
                                    $no = 0;
                                    foreach ($sppd_list as $row) {
                                        $no++;
                                    ?>
                                        <?php //echo CHtml::link($row['sppd_id'], Yii::app()->baseUrl.'/index.php?r=businesstravel/view&id='.$row['sppd_id'], array('target'=>'_blank')); ?>
                                         <tr>
                                            <td><?php echo Yii::app()->globalFunction->get_city_name($row['from']); ?></td>
                                            <td><?php echo Yii::app()->globalFunction->get_city_name($row['to']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($row['departure_date'])).' '.$row['departure_time']; ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($row['arrival_date'])).' '.$row['arrival_time']; ?></td>
                                            <td><?php echo $no == 1 ? $row['days']+1 : $row['days']; ?></td>
                                            <td><?php echo $model->currency_id; ?></td>
                                            <td style="text-align: right;"><?php  echo number_format($row['meal_amount'],0,',','.'); ?></td>
                                            <td style="text-align: right;"><?php  echo number_format($row['allowance_amount'],0,',','.'); ?></td>
                                            <td style="text-align: right;"><?php  echo number_format($row['hotel_amount'],0,',','.'); ?></td>
                                            <td style="text-align: right;"><input type="text" value="<?php echo number_format($row['total_amount'],0,',','.'); ?>" readonly></td>
                                         </tr>
                                    <?php
                                    }
                                    ?>
                                    <tr width="100%">
                                        <td colspan="9" style="text-align: right;"><p align="right">Grand Total (Total + Others)</p></td>
                                        <td style="text-align: right;">
                                            <?php echo $model->currency_id; ?>&nbsp;&nbsp;<input type=text id='total' value="<?php echo number_format($grand_total,0,',','.'); ?>" readonly>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="panel panel-info">
                        <div class="panel-heading">SPPD Approval</div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class='table table-striped table-bordered table-hover'>
                					<tr>
                						<th>Name</th>
                						<th>Approval Date</th>
                						<th>Approval Comment</th>
                						<th>Status Approval</th>
                					</tr>
                					<?php
                					foreach ($sppd_approval as $row) {
                						$app_approval = "";
                						if($row['approver_flag'] == '2'){
                							$app_approval = "Approved";
                						}elseif($row['approver_flag'] == '3'){
                							$app_approval = "Rejected";
                						}else{
                							$app_approval = "-";
                						}
                    					?>
                    					<tr>
                    						<td><?php echo Yii::app()->globalFunction->get_user_name($row['approver_id']); ?></td>
                    						<td><?php echo $app_date = (empty($row['approver_date']))?"-":$row['approver_date'];?></td>
                    						<td><?php echo $app_comment = (empty($row['approver_comment']))?"-":$row['approver_comment'];?></td>
                    						<td><?php echo $app_approval; ?></td>	
                    					</tr>
                    					<?php
                					}
                					?>
                				</table>
                			</div>
            			</div>
        			</div>
                    
                </div>
            </div>
        </div>
        <!-- Business Travel Information Section -->
        <?php } ?>
        
        <!-- Attachment List Section -->
        <div class="panel panel-default">
            <div class="panel-heading">Attachment List</div>
            <div id="section4" class="panel-expand expand">
                <div class="panel-body">
                    <div align="center" class="table-responsive">

                        <?php if ($model->isNewRecord) { ?>
                            <div class="alert alert-warning"><strong>Please save this document before you create the destination list</strong></div>
                        <?php } else { ?> 
                            <br />
                            <div style="float:right;">
                                <?php echo CHtml::button('Create Attachment', array('class' => 'btn btn-info btn-sm', 'disabled' => ($model->status == 0 || $model->status == 1)?'':'disabled', 'onclick' => 'javascript:window.location="' . Yii::app()->createUrl('AdvanceMoneyAttachment/create', array('adv_mon_id' => $model->adv_mon_id, 'type' => '1')) . '"')); ?>&nbsp;
                            </div>
                            <br />
                            <br />
                            <?php
                            //print_r($model_destination->searchBySppd($model->sppd_id));
                            $this->widget('zii.widgets.grid.CGridView', array(
                                'id' => 'business-travel-destination-model-grid',
                                'dataProvider'=>$model_attachment->searchByEntertain($model->adv_mon_id),
                                'itemsCssClass' => 'table table-striped table-bordered table-hover',
                                'rowCssClassExpression' => '$row%2?"success":"even"',
                                'pager' => array('class' => 'CLinkPager', 'header' => ''),
                                'pagerCssClass' => 'pagination',
                                'summaryCssClass' => 'dataTables_info',
                                'summaryText' => '',
                                'columns' => array(
                                    //'id',
                                    array(
                                        'name'=>'filename',
                                        'type'=>'raw',
                                        'value'=>'CHtml::link($data->filename, Yii::app()->request->baseUrl."/protected/attachment/advance_money/".$data->filename, array("target"=>"_blank"))',
                                    ),
                                    'description',
                                    array(
                                        'class' => 'CButtonColumn',
                                        'htmlOptions' => array('width' => '75px', 'style' => 'vertical-align:middle'),
                                        'template' => '{delete}',
                                        'buttons' => array(
                                            'delete' => array(
                                                'imageUrl' => false,
                                                'label' => '  Delete',
                                                'url' => 'Yii::app()->createUrl("AdvanceMoneyAttachment/delete", array("id"=>"$data->id"))',
                                                'options' => array('class' => 'fa fa-recycle btn btn-danger btn-xs'),
                                                'visible' => "'".$visible_status."'",
                                            //'icon'=>'fa fa-plus',
                                            ),
                                        ),
                                    ),
                                ),
                            ));
                            ?>
                        <?php } ?>

                    </div>  
                </div>
            </div>
        </div>
        <!-- Attachment List Section -->

        <?php if ($model->status == 2) { ?>
        <!-- Attachment List Section -->
        <div class="panel panel-default">
            <div class="panel-heading">Attachment of Transfer Receipt</div>
            <div id="section4" class="panel-expand expand">
                <div class="panel-body">
                    <div align="center" class="table-responsive">
                            <br />
                            <?php if($role == 24 or $role == 25) {?>
                            <div style="float:right;">
                                <?php echo CHtml::button('Create Attachment', array('class' => 'btn btn-info btn-sm', 'disabled' => ($model->transfer_status == 0)?'':'disabled', 'onclick' => 'javascript:window.location="' . Yii::app()->createUrl('AdvanceMoneyAttachment/create', array('adv_mon_id' => $model->adv_mon_id, 'type' => '2')) . '"')); ?>&nbsp;
                            </div>
                            <?php } ?>
                            <br />
                            <br />
                            <?php
                            //print_r($model_destination->searchBySppd($model->sppd_id));
                            $this->widget('zii.widgets.grid.CGridView', array(
                                'id' => 'advance-money-destination-model-grid',
                                'dataProvider'=>$model_attachment->searchByPUM($model->adv_mon_id),
                                'itemsCssClass' => 'table table-striped table-bordered table-hover',
                                'rowCssClassExpression' => '$row%2?"success":"even"',
                                'pager' => array('class' => 'CLinkPager', 'header' => ''),
                                'pagerCssClass' => 'pagination',
                                'summaryCssClass' => 'dataTables_info',
                                'summaryText' => '',
                                'columns' => array(
                                    //'id',
                                    array(
                                        'name'=>'filename',
                                        'type'=>'raw',
                                        'value'=>'CHtml::link($data->filename, Yii::app()->request->baseUrl."/protected/attachment/advance_money/".$data->filename, array("target"=>"_blank"))',
                                    ),
                                    'description',
                                    array(
                                        'class' => 'CButtonColumn',
                                        'htmlOptions' => array('width' => '75px', 'style' => 'vertical-align:middle'),
                                        'template' => '{delete}',
                                        'buttons' => array(
                                            'delete' => array(
                                                'imageUrl' => false,
                                                'label' => '  Delete',
                                                'url' => 'Yii::app()->createUrl("AdvanceMoneyAttachment/delete", array("id"=>"$data->id"))',
                                                'options' => array('class' => 'fa fa-recycle btn btn-danger btn-xs'),
                                                'visible' => "'".$visible_status."'",
                                            //'icon'=>'fa fa-plus',
                                            ),
                                        ),
                                    ),
                                ),
                            ));
                            ?>
                    </div>  
                </div>
            </div>
        </div>
        <?php } ?>                        
        <!-- Attachment List Section -->
                        
        <?php if (!$model->isNewRecord && $approver_flag != 2 && $model->status==1 && $model->created_by <> Yii::app()->user->id) { ?>
      
        <h4>Responses</h4>
        <textarea rows="5" class="form-control" id='response_text' name="response_text"></textarea>
        <?php } ?>
        
        <?php echo $this->renderPartial('_view_response', array('model_response'=>$model_response)); ?>
        
        <?php /* if($role == 24 or $role == 25){ ?>
            <div class="alert alert-warning">
                <b>Notes :</b><br>
                1. Action Transfer can be used if the Status Data of PUM Request was Fully Approved<br>
                2. Action Settlement can be used if the Transfer Status of PUM Request was Done / Transfered
            </div>
        <?php } */ ?>
</div>
<div class="panel-footer">
        <!-- Panel Footer Start -->
        <div class="row buttons">
        
            &nbsp;&nbsp;&nbsp;<a href="javascript:history.back()" class="btn btn-warning btn-sm"><i class="fa fa-arrow-left"></i>&nbsp;&nbsp;Cancel</a>
            
            <?php if($model->status==0 && $model->created_by == Yii::app()->user->id){ ?>
                &nbsp;&nbsp;&nbsp;<?php echo CHtml::submitButton('Save', array('class' => 'btn btn-primary btn-sm')); 
            } ?>
            
            <?php if(!$model->isNewRecord && $model->status==0 && $model->budget_code != '' && $model->created_by == Yii::app()->user->id){ ?>
                &nbsp;&nbsp;
                <?php echo CHtml::link('<i class="fa fa-send"></i>&nbsp;Send To Approver', '', array('id' => 'send_to_approver', 'class' => 'btn btn-info btn-sm ', 'disabled' => ($model->status == 0 && $model->budget_code != '')?'':'disabled'));
            } ?>
            
			<?php if($model->status == 1 && $is_permit == Yii::app()->user->id && $approval_date == '' && $approver_flag == 1){ ?>
                &nbsp;&nbsp;
                <?php echo CHtml::link('<i class="fa fa-remove"></i>&nbsp;Reject', '', array('id' => 'reject', 'class' => 'btn btn-danger btn-sm ', 'disabled' => ($this->isAllowed(Yii::app()->user->id, 'approve') == true)?'':'disabled')); ?>			
                &nbsp;&nbsp;
                <?php echo CHtml::link('<i class="fa fa-remove"></i>&nbsp;Revise', '', array('id' => 'send_back', 'class' => 'btn btn-danger btn-sm ', 'disabled' => ($this->isAllowed(Yii::app()->user->id, 'approve') == true)?'':'disabled')); ?>
				&nbsp;&nbsp;
				<?php echo CHtml::link('<i class="fa fa-check"></i>&nbsp;Approve', array('advancemoney/approve', 'id' => $model->adv_mon_id), array('class' => 'btn btn-success btn-sm ', 'disabled' => ($this->isAllowed(Yii::app()->user->id, 'approve') == true)?'':'disabled')); ?>
			<?php } ?>
			
			<!--Transfer-->
			<?php 
			if($model->status == 2 && $model->transfer_status == 0 && ($role == 24 or $role == 25)){                 
				if($get_attach != NULL){
			    ?>
                &nbsp;&nbsp;

                <?php echo CHtml::link('<i class="fa fa-chevron-up"></i>&nbsp;Transfer..', array('advancemoney/transfer', 'id' => $model->adv_mon_id), array('class' => 'btn btn-success btn-sm ', 'disabled' => ($model->transfer_status == 0)?'':'disabled'));
                } else{ ?>
				&nbsp;&nbsp;
				<?php echo CHtml::link('<i class="fa fa-chevron-up"></i>&nbsp;Transfer..', array('advancemoney/update', 'id' => $model->adv_mon_id), array('class' => 'btn btn-success btn-sm ', 'confirm'=>'Anda belum melampirkan bukti transfer', 'disabled' => ($model->transfer_status == 0)?'':'disabled')); ?>

                <?php 
                }
			}
			?>

            <?php if(($role == 24 or $role == 25) && $model->status == 2 && $model->paid_status == 0){ ?>
                &nbsp;&nbsp;
                <?php echo CHtml::link('<i class="fa fa-chevron-down"></i>&nbsp;Settlement', array('advancemoney/paid', 'id' => $model->adv_mon_id), array('class' => 'btn btn-primary btn-sm ', 'disabled' => ($model->paid_status == 0)?'':'disabled')); ?>
            <?php } ?>

        </div>
        <!-- Panel Footer End -->
    </div>
</div>

<?php $this->endWidget(); ?>

</div><!-- form -->

<!-- View Popup  -->
<?php $this->beginWidget('bootstrap.widgets.TbModal', array('id'=>'modalSendBack')); ?>

    <div class="modal-header">
        <h4>Confirmation</h4>
    </div>
    
    <div class="modal-body text-center">
        <h4>Are you sure want to <b>Revise</b> this PUM Document ?</h4>
        <p><i>* this action will remove all user approver and change the status to draft again.</i></p>
    </div>
    
    <div class="modal-footer" style="margin-top: 0px;">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <?php echo CHtml::link('<i class="fa fa-remove"></i>&nbsp;Yes', '', array('id' => 'confirm_send_back', 'class' => 'btn btn-danger')); ?>
    </div>
    
<?php $this->endWidget(); ?>

<?php $this->beginWidget('bootstrap.widgets.TbModal', array('id'=>'modalReject')); ?>

    <div class="modal-header">
        <h4>Confirmation</h4>
    </div>
    
    <div class="modal-body text-center">
        <h4>Are you sure want to <b>Reject</b> this PUM Document ?</h4>
        <p><i>* this action will stop all the steps and change the status to reject.</i></p>
    </div>
    
    <div class="modal-footer" style="margin-top: 0px;">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <?php echo CHtml::link('<i class="fa fa-remove"></i>&nbsp;Yes', '', array('id' => 'confirm_reject', 'class' => 'btn btn-danger')); ?>
    </div>
    
<?php $this->endWidget(); ?>

<?php $this->beginWidget('bootstrap.widgets.TbModal', array('id'=>'modalSendToApprover')); ?>

    <div class="modal-header">
        <h4>Confirmation</h4>
    </div>
    
    <div class="modal-body text-center">
        <h4>Are you sure want to <b>Send To Approver</b> this PUM Document ?</h4>
        <p><i>* this action will continue send your document to all the user approvers and change the status to progress.</i></p>
    </div>
    
    <div class="modal-footer" style="margin-top: 0px;">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <?php echo CHtml::link('<i class="fa fa-remove"></i>&nbsp;Yes', '', array('id' => 'confirm_send_to_approver', 'class' => 'btn btn-danger')); ?>
    </div>
    
<?php $this->endWidget(); ?>
