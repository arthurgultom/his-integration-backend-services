<?php if(Yii::app()->user->hasFlash('success')): ?>
    <div class="alert alert-danger">
        <?php echo Yii::app()->user->getFlash('success'); ?>
    </div>
<?php endif; ?>

<?php
/* @var $this BusinessTravelController */
/* @var $model BusinessTravelModel */
/* @var $form CActiveForm */
?>

<script type='text/javascript'>
$(document).ready(function() {
    // $('#loading').modal('show');
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
        var sppd_id =$("#BusinessTravelModel_sppd_id").val();
        var response_text = $("#response_text").val();
        //alert(id_doc+" "+repair_date+" "+doc_date);
        var baseUrl = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=businessTravel/sendback';

        $.ajax({
            type: 'POST',
            url: baseUrl,
            data:"sppd_id="+sppd_id+"&response_text="+response_text,
            beforeSend: function(data)
            {
                $('#loading').modal('show');
            },
            success:function(data){
                $('#loading').modal('hide');
                alert("SPPD ("+sppd_id+") successfully has been revised !");
                window.location = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=site/ListSppdApproval';
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
        
        // - SPPD Reject : Check PUM Status : if except draft show warning, if draft reject too
        var sppd_id = $("#BusinessTravelModel_sppd_id").val();
        // console.log(sppd_id)
        var baseUrl = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=businessTravel/CheckPum';
        
        $.ajax({
            type: 'POST',
            url: baseUrl,
            data: "sppd_id="+sppd_id,
            beforeSend: function()
            {
                $('#loading').modal('show');
            },
            success:function(resp){
                $('#loading').modal('hide');
                var resp = JSON.parse(resp);
                
                if(resp.total > 0){
                    alert("SPPD can not be reject because have PUM that still In Progress ! ");
                    return false;
                }else{
                
                    var response_text = $("#response_text").val();
                    if(response_text == "" || response_text.length == 0 ){
                        alert("Please input your response first ! ");
                        return false;
                    }else{
                        $("#modalReject").modal();
                    } 
                   
                }
            },
            error: function(err) {
                alert("Error occured.please try again");
            },
            dataType:'html'
        });
    });
    
    $('#confirm_reject').click(function() {
        var sppd_id =$("#BusinessTravelModel_sppd_id").val();
        var response_text = $("#response_text").val();
        //alert(id_doc+" "+repair_date+" "+doc_date);
        var baseUrl = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=businessTravel/reject';

        $.ajax({
            type: 'POST',
            url: baseUrl,
            data:"sppd_id="+sppd_id+"&response_text="+response_text,
            beforeSend: function(data)
            {
                $('#loading').modal('show');
            },
            success:function(data){
                $('#loading').modal('hide');
                alert("SPPD ("+sppd_id+") successfully has been rejected !");
                window.location = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=site/ListSppdHistory';
            },
            error: function(data) { // if error occured
                //alert("Error occured.please try again");
                alert(data);
            },
            dataType:'html'
        });
    });

    $('#send_to_approver').click(function() {
        var response_text = $("#response_text").val();
        if (confirm('Are you sure want to Send this SPPD ?')) {
            send_to_approver();
        }else{
            return false;
        }
    });
    
    // DELETE DESTINATION
    $(document).on('click','#business-travel-destination-model-grid a.fa.fa-trash-o.btn.btn-danger.btn-xs', function() {
        $("#modalDeleteDestination").modal();
        $("#delete_dest_url").val($(this).attr('href'));
        return false;
    });
    
    $('#confirm_delete_destination').click(function() {
        var url_del = $("#delete_dest_url").val();

        $.ajax({
            type: 'POST',
            url: url_del,
            beforeSend: function(data)
            {
                $('#loading').modal('show');
            },
            success:function(data){
                $('#loading').modal('hide');
                // alert("Destination successfully has been deleted !");
                $('#business-travel-destination-model-grid').yiiGridView('update');
                $('#modalDeleteDestination').modal('hide');
                $("#delete_dest_url").val('');
            },
            error: function(data) { // if error occured
                alert("Error occured.please try again");
                console.log(JSON.stringify(data));
            }
        });
    });

    function send_response(){
        var sppd_id =$("#BusinessTravelModel_sppd_id").val();
        var response_text = $("#response_text").val();
        //alert(id_doc+" "+repair_date+" "+doc_date);
        var baseUrl = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=businessTravel/sendresponse';

        $.ajax({
           type: 'POST',
           url: baseUrl,
           data:"sppd_id="+sppd_id+"&response_text="+response_text,
           beforeSend: function(data)
            {
                $('#loading').modal('show');
            },
            success:function(data){
                $('#loading').modal('hide');
                // alert("Success, sending response !");
                $('.loadingx').hide().html('loading...');
                window.location = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=businessTravel/update&id='+sppd_id+' ';
            },
            error: function(data) { // if error occured
                alert("Error occured.please try again");
                console.log(JSON.stringify(data));
            },
            dataType:'html'
        });
    }

    function send_to_approver(){

      var sppd_id =$("#BusinessTravelModel_sppd_id").val();
      var response_text = $("#response_text").val();
      //alert(id_doc+" "+repair_date+" "+doc_date);
      var baseUrl = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=businessTravel/sendtoapprover';

      $.ajax({
           type: 'POST',
           url: baseUrl,
           data:"sppd_id="+sppd_id+"&response_text="+response_text,
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
                    window.location = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=businessTravel/update&id='+sppd_id+' ';   
                }
           },
           error: function(data) { // if error occured
                 alert("Error occured.please try again");
                 console.log(JSON.stringify(data));
            },
            dataType:'html'
      });

    }

    function send_to_approve_and_create_pum(){

      var sppd_id =$("#BusinessTravelModel_sppd_id").val();
      var response_text = $("#response_text").val();
      //alert(id_doc+" "+repair_date+" "+doc_date);
      var baseUrl = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=businessTravel/sendtoapprover';

      $.ajax({
           type: 'POST',
           url: baseUrl,
           data:"sppd_id="+sppd_id+"&response_text="+response_text,
           beforeSend: function(data)
            {
                //$('.loadingx').hide().html("<img src='<?php echo Yii::app()->request->baseUrl;?>/image/ajax-loader.gif' />").fadeIn('slow');;
                $('#loading').modal('show');

            },
           success:function(data){
                $('#loading').modal('hide');
                // alert("Success, Your request already sent to approver !");
                $('.loadingx').hide().html('loading...');
                window.location = '<?php echo Yii::app()->request->baseUrl; ?>/index.php?r=advancemoney/requestpum&sppd_id='+sppd_id+' ';
           },
           error: function(data) { // if error occured
                 alert("Error occured.please try again");
                 console.log(JSON.stringify(data));
            },
            dataType:'html'
      });

    }

});

</script>

<div class="form">

    <?php
    $form = $this->beginWidget('CActiveForm', array(
        'id' => 'business-travel-model-form',
        // Please note: When you enable ajax validation, make sure the corresponding
        // controller action is handling ajax validation correctly.
        // There is a call to performAjaxValidation() commented in generated controller code.
        // See class documentation of CActiveForm for details on this.
        'enableAjaxValidation' => false,
    ));
    ?>

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

        if($model->status < 1 && $model->created_by == Yii::app()->user->id){
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
        <div class="panel-heading"><p class="note"><strong>Business Trip Instruction Form</strong></p></div>
        <div class="panel-body">

            <?php echo $form->errorSummary($model, '', '', array('class' => 'alert alert-danger alert-dismissable')); ?>

            <!-- Panel Body Start -->

            <!-- Header Section -->
            <div class="panel panel-default">
            <div class="panel-heading">Header Information</div>
            <div id="section4" class="panel-expand expand">
                <div class="panel-body">
                    <div align="center" class="table-responsive">
                        <div class="table-responsive">
            				<div class='col-lg-6 col-md-6 col-sm-12'>
                                <table style="width: 100%;">
                                    <tr>
                                        <td>
                                            <?php echo $form->labelEx($model, 'sppd_id'); ?>
                                        </td>
                                        <td>
                                            <?php echo $form->textField($model, 'sppd_id', array('size' => 20, 'maxlength' => 20, 'class' => 'form-control input-sm', 'tab_index' => 1, 'readOnly' => true, 'placeholder' => 'Auto Generate')); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $form->labelEx($model, 'sppd_date'); ?>
                                        </td>
                                        <td>
                                            <?php echo $form->textField($model, 'sppd_date', array('class' => 'form-control input-sm', 'tab_index' => 2, 'readOnly' => true, 'value' => date('Y-m-d'))); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $form->labelEx($model, 'emp_no'); ?>
                                        </td>
                                        <td>
                                            <?php //echo $form->textField($model, 'emp_no', array('size' => 20, 'maxlength' => 20, 'class' => 'form-control input-sm')); ?>
                							<?php if($model->isNewRecord){ ?>
                                                <?php //echo $form->dropDownList($model, 'emp_no', $emplist, array('class' => 'form-control input-sm', 'tab_index' => 3, 'disabled' => ($model->status == 0)?'':'disabled', 'options'=>array(Yii::app()->user->id=>array('selected'=>true))));
                                                echo $form->textField($model_employee, 'emp_no', array('class' => 'form-control input-sm', 'tab_index' => 2, 'readOnly' => true));
                                                ?>
                                            <?php }else{ ?>
                                                <?php //echo $form->textField($model, 'sppd_date', array('class' => 'form-control input-sm', 'tab_index' => 2, 'readOnly' => true)); ?>
                                                <?php //echo $form->dropDownList($model, 'emp_no', $emplist, array('class' => 'form-control input-sm', 'tab_index' => 3, 'disabled' => ($model->status == 0)?'':'disabled')); ?>
                                                <?php echo $form->textField($model_employee, 'emp_no', array('class' => 'form-control input-sm', 'tab_index' => 2, 'readOnly' => true)); ?>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $form->labelEx($model, 'emp_name'); ?>
                                        </td>
                                        <td>
                                            <?php //echo $form->textField($model, 'emp_no', array('size' => 20, 'maxlength' => 20, 'class' => 'form-control input-sm')); ?>
                							<?php if($model->isNewRecord){ ?>
                                                <?php 
                                                echo $form->dropDownList($model, 'emp_no', $emplist, array('class' => 'form-control input-sm', 'tab_index' => 3, 'disabled' => ($model->status == 0)?'':'disabled', 'options'=>array(Yii::app()->user->id=>array('selected'=>true))));
    											/*
    											if(Yii::app()->user->id == '38480') {
                                                    echo $form->dropDownList($model, 'emp_no', $emplistmech, array('class' => 'form-control input-sm', 'tab_index' => 3, 'disabled' => ($model->status == 0)?'':'disabled', 'options'=>array(Yii::app()->user->id=>array('selected'=>true))));
                                                } elseif(Yii::app()->user->id == '36805') {
                                                    echo $form->dropDownList($model, 'emp_no', $emplistbanjarmasin, array('class' => 'form-control input-sm', 'tab_index' => 3, 'disabled' => ($model->status == 0)?'':'disabled', 'options'=>array(Yii::app()->user->id=>array('selected'=>true))));
                                                } elseif(Yii::app()->user->id == '37985') {
                                                    echo $form->dropDownList($model, 'emp_no', $emplistsby, array('class' => 'form-control input-sm', 'tab_index' => 3, 'disabled' => ($model->status == 0)?'':'disabled', 'options'=>array(Yii::app()->user->id=>array('selected'=>true))));
                                                } elseif(Yii::app()->user->id == '38284') {
                                                    echo $form->dropDownList($model, 'emp_no', $emplistmedan, array('class' => 'form-control input-sm', 'tab_index' => 3, 'disabled' => ($model->status == 0)?'':'disabled', 'options'=>array(Yii::app()->user->id=>array('selected'=>true))));
                                                } elseif(Yii::app()->user->id == '38013') {
                                                    echo $form->dropDownList($model, 'emp_no', $emplistBalikpapan, array('class' => 'form-control input-sm', 'tab_index' => 3, 'disabled' => ($model->status == 0)?'':'disabled', 'options'=>array(Yii::app()->user->id=>array('selected'=>true))));
                                                } else {
                                                    echo $form->dropDownList($model, 'emp_no', $emplist, array('class' => 'form-control input-sm', 'tab_index' => 3, 'disabled' => ($model->status == 0)?'':'disabled', 'options'=>array(Yii::app()->user->id=>array('selected'=>true))));
                                                }
                                                */
    											?>
    										<?php }else{ ?>
                                                <?php //echo $form->textField($model, 'sppd_date', array('class' => 'form-control input-sm', 'tab_index' => 2, 'readOnly' => true)); ?>
                                                <?php //echo $form->dropDownList($model, 'emp_no', $emplist, array('class' => 'form-control input-sm', 'tab_index' => 3, 'disabled' => ($model->status == 0)?'':'disabled')); ?>
                                                <?php echo $form->textField($model_employee, 'emp_name', array('class' => 'form-control input-sm', 'tab_index' => 2, 'readOnly' => true)); ?>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php if(!$model->isNewRecord){ ?>
                                    <tr>
                                        <td>
                                            <?php echo $form->labelEx($model_level, 'level_name'); ?>
                                        </td>
                                        <td>
                                            <?php //echo $form->textField($model, 'emp_no', array('size' => 20, 'maxlength' => 20, 'class' => 'form-control input-sm')); ?>
                                            <?php echo $form->textField($model_level, 'level_name', array('class' => 'form-control input-sm', 'tab_index' => 2, 'readOnly' => true)); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $form->labelEx($model_divisi, 'division_name'); ?>
                                        </td>
                                        <td>
                                            <?php //echo $form->textField($model, 'emp_no', array('size' => 20, 'maxlength' => 20, 'class' => 'form-control input-sm')); ?>
                                            <?php echo $form->textField($model_divisi, 'division_name', array('class' => 'form-control input-sm', 'tab_index' => 2, 'readOnly' => true)); ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                    <tr>
                                        <td style="vertical-align: text-top;">
                                            <?php echo $form->labelEx($model, 'purpose'); ?>
                                        </td>
                                        <td>
                                            <?php echo $form->textArea($model, 'purpose', array('rows' => 6, 'cols' => 50, 'tab_index' => 4, 'class' => 'form-control input-sm','disabled' => ($model->status == 0)?'':'disabled')); ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class='col-lg-6 col-md-6 col-sm-12'>
                                <table style="width: 100%;">
                                    <tr>
                                        <td>
                                            <?php echo $form->labelEx($model, 'trip_id'); ?>
                                        </td>
                                        <td>
                                            <?php //echo $form->textField($model, 'trip_id', array('class' => 'form-control input-sm')); ?>
                                            <?php echo $form->dropDownList($model, 'trip_id', $triplist, array('prompt' => 'Please select option', 'tab_index' => 5, 'class' => 'form-control input-sm', 'disabled' => ($model->status == 0)?'':'disabled')); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $form->labelEx($model, 'instructed_by'); ?>
                                        </td>
                                        <td>
                                            <?php if($model->isNewRecord){ ?>
                                            <?php 
                                            //echo $form->dropDownList($model, 'instructed_by', $instructlist, array('prompt' => 'Please select option', 'tab_index' => 6, 'class' => 'form-control input-sm', 'disabled' => ($model->status == 0)?'':'disabled')); 
                                            ?>
                                            <?php echo CHtml::activeHiddenField($model, 'instructed_by', array('value' => $instructedby['emp_no'])); ?>
                                            <input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo $instructedby['emp_name'];?>" />
                                            
                                            <?php } else { ?>
                                            <input type="text" class="form-control input-sm" name="instructed_by" id="instructed_by" value="<?php echo isset($instructed_name) ? $instructed_name : ''; ?>" disabled="disabled" />
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php if(!$model->isNewRecord){ ?>
                                    <tr>
                                        <td>
                                            <?php echo $form->labelEx($model_location, 'location_name'); ?>
                                        </td>
                                        <td>
                                            <?php //echo $form->textField($model, 'emp_no', array('size' => 20, 'maxlength' => 20, 'class' => 'form-control input-sm')); ?>
                                            <?php echo $form->textField($model_location, 'location_name', array('class' => 'form-control input-sm', 'tab_index' => 2, 'readOnly' => true)); ?>
                                        </td>
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
            <!-- Header Section -->

            <!-- Allowance List Section -->
            <div class="panel panel-default" style="display:none">
                <div class="panel-heading">Allowance List (*The data will be used for advance money request)</div>
                <div id="section4" class="panel-expand expand">
                    <div class="panel-body">
                        <div align="center" class="table-responsive">
                            <div class="table-responsive">
                                <div class="col-sm-6">
                                <table>
                                    <tr>
                                        <td>
                                            <?php echo $form->labelEx($model, 'meal_amount'); ?>
                                        </td>
                                        <td>
                                            <?php echo $form->textField($model, 'meal_amount', array('size' => 20, 'maxlength' => 11, 'class' => 'form-control input-sm', 'tab_index' => 9)); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $form->labelEx($model, 'allowance_amount'); ?>
                                        </td>
                                        <td>
                                            <?php echo $form->textField($model, 'allowance_amount', array('size' => 20, 'maxlength' => 11, 'class' => 'form-control input-sm', 'tab_index' => 10, 'readOnly' => true)); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $form->labelEx($model, 'hotel_amount'); ?>
                                        </td>
                                        <td>
                                            <?php echo $form->textField($model, 'hotel_amount', array('size' => 20, 'maxlength' => 11, 'class' => 'form-control input-sm', 'tab_index' => 11, 'readOnly' => true)); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $form->labelEx($model, 'transport_amount'); ?>
                                        </td>
                                        <td>
                                            <?php echo $form->textField($model, 'transport_amount', array('size' => 20, 'maxlength' => 11, 'class' => 'form-control input-sm', 'tab_index' => 12)); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $form->labelEx($model, 'others_amount'); ?>
                                        </td>
                                        <td>
                                            <?php echo $form->textField($model, 'others_amount', array('size' => 20, 'maxlength' => 11, 'class' => 'form-control input-sm', 'tab_index' => 13)); ?>
                                        </td>
                                    </tr>
                                </table>
                                </div>
                                <div class='col-sm-6'>
                                    <table>
                                        <tr>
                                            <td>
                                                <?php echo $form->labelEx($model, 'days'); ?>
                                            </td>
                                            <td>
                                                <?php echo $form->textField($model, 'days', array('size' => 20, 'maxlength' => 11, 'class' => 'form-control input-sm', 'tab_index' => 14, 'readOnly' => true)); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php echo $form->labelEx($model, 'total_amount'); ?>
                                            </td>
                                            <td>
                                                <?php echo $form->textField($model, 'total_amount', array('size' => 20, 'maxlength' => 11, 'class' => 'form-control input-sm', 'tab_index' => 15, 'readOnly' => true)); ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Allowance List Section -->

            <!-- Destination List Section -->
            <div class="panel panel-default">
                <div class="panel-heading">Destination List</div>
                <div id="section4" class="panel-expand expand">
                    <div class="panel-body">
                        <div align="center" class="table-responsive">
                            <?php if ($model->isNewRecord) { ?>
                                <div class="alert alert-warning"><strong>Please save this document before you add the destination list</strong></div>
                            <?php } else { ?>
                                <br />
                                <div style="float:right;">
                                    <?php 
                                    //if ($model->created_by == Yii::app()->user->id) { 
                                        echo CHtml::button('Create Destination', array('class' => 'btn btn-info btn-sm', 'disabled' => ($model->status == 0)?'':'disabled', 'onclick' => 'javascript:window.location="' . Yii::app()->createUrl('businessTravelDestination/create', array('sppd_id' => $model->sppd_id)) . '"')); 
                                    //} ?>
                                    &nbsp;
                                </div>
                                <br />
                                <br />
                            <?php
                                $this->widget('zii.widgets.grid.CGridView', array(
                                    'id' => 'business-travel-destination-model-grid',
                                    'dataProvider'=>$model_destination->searchBySppd($model->sppd_id),
                                    'filter' => $model_destination,
                                    'itemsCssClass' => 'table table-striped table-bordered table-hover',
                                    'rowCssClassExpression' => '$row%2?"success":"even"',
                                    'pager' => array('class' => 'CLinkPager', 'header' => ''),
                                    'pagerCssClass' => 'pagination',
                                    'summaryCssClass' => 'dataTables_info',
                                    'summaryText' => '',
                                    'columns' => array(
                                        array(
                                            'name'=>'search_from',
                                            'value'=>'$data->rel_from->city_name',
                                        ),
                                        array(
                                            'name'=>'search_to',
                                            'value'=>'$data->rel_to->city_name',
                                        ),
                                        'departure_date',
                                        'departure_time',
                                        'arrival_date',
                                        'arrival_time',
                                        array(
                                            'class' => 'CButtonColumn',
                                            'htmlOptions' => array('width' => '130px'),
                                            'template' => '{update} {delete_destination}',
                                            'buttons' => array(
                                                'update' => array(
                                                    'imageUrl' => false,
                                                    'label' => '  Edit',
                                                    'url' => 'Yii::app()->createUrl("businessTravelDestination/update", array("id"=>"$data->dest_id","sppd_id" => "'.$model->sppd_id.'"))',
                                                    'options' => array('class' => 'fa fa-edit btn btn-warning btn-xs'),
                                                    'visible' => "'".($model->status == 0)."'",
                                                ),
                                                'delete_destination' => array(
                                                    'imageUrl' => false,
                                                    'label' => '  Delete',
                                                    'url' => 'Yii::app()->createUrl("businessTravelDestination/delete", array("id"=>"$data->dest_id"))',
                                                    'options' => array(
                                                        'class' => 'fa fa-trash-o btn btn-danger btn-xs delete-destination'
                                                    ),
                                                    'visible' => "'".($model->status == 0 && $totalDest > 1)."'",
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
            <!-- Destination List Section -->

            <!-- Attachment List Section -->
            <div class="panel panel-default">
                <div class="panel-heading">Attachment List</div>
                <div id="section4" class="panel-expand expand">
                    <div class="panel-body">
                        <div align="center" class="table-responsive">
                            <?php if ($model->isNewRecord) { ?>
                                <div class="alert alert-warning"><strong>Please save this document before you add the attachment list</strong></div>
                            <?php } else { ?>
                                <br />
                                <div style="float:right;">
                                    <?php  if ($model->created_by == Yii::app()->user->id || Yii::app()->globalFunction->is_admin_user(Yii::app()->user->id)) { echo CHtml::button('Create Attachment', array('class' => 'btn btn-info btn-sm', 'disabled' => ($model->status == 0)?'':'disabled', 'onclick' => 'javascript:window.location="' . Yii::app()->createUrl('businessTravelAttachment/create', array('sppd_id' => $model->sppd_id)) . '"')); } ?>&nbsp;
                                </div>
                                <br />
                                <br />
                                <?php
                                //print_r($model_destination->searchBySppd($model->sppd_id));
                                $this->widget('zii.widgets.grid.CGridView', array(
                                    'id' => 'business-travel-attachment-model-grid',
                                    'dataProvider'=>$model_attachment->searchBySppd($model->sppd_id),
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
                                            'value'=>'CHtml::link($data->filename, Yii::app()->request->baseUrl."/protected/attachment/business_travel/".$data->filename, array("target"=>"_blank"))',
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
                                                    'url' => 'Yii::app()->createUrl("businessTravelAttachment/delete", array("id"=>"$data->id"))',
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
            
            <?php if($model->advance_money > 0){ ?>
            <div class="panel panel-success" style="font-family: sans-serif;font-size: 12px;margin-top: 15px;">
                <div class="panel-heading">Advance Money Information (PUM Request)</div>
                <div class="panel-body" style="font-family: sans-serif;font-size: 12px;">
                    
                    <div class="row table-responsive" style="margin-bottom: 15px;">
        				<div class="col-sm-6">
                            <table style="width:100%;">
                                <tr>
                                    <td style="width: 33%;">PUM ID</td>
                                    <td style="width: 2%;text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo $pum_data['adv_mon_id']; ?>" /></td>
                                </tr>
                                <tr>
                                    <td>Document Date</td>
                                    <td style="text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo $pum_data['adv_mon_date']; ?>" /></td>
                                </tr>
                                <tr>
                                    <td>NIK</td>
                                    <td style="text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo $pum_data['emp_no']; ?>" /></td>
                                </tr>
                                <tr>
                                    <td>Name</td>
                                    <td style="text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo Yii::app()->globalFunction->get_user_name($pum_data['emp_no']); ?>" /></td>
                                </tr>
                                <tr>
                                    <td>Towards</td>
                                    <td style="text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo $pum_data['towards']; ?>" /></td>
                                </tr>
                            </table>
                        </div>
                        <div class='col-sm-6'>
                            <table style="width:100%;">
                                <tr>
                                    <td style="width: 33%;">Please Prepare On</td>
                                    <td style="width: 2%;text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo $pum_data['on_date']; ?>" /></td>
                                </tr>
                                <tr>
                                    <td>Budget Code</td>
                                    <td style="text-align: right;">:</td>
                                    <td><input type="text" class="form-control input-sm" readonly="readonly" value="<?php echo $pum_data['budget_code']; ?>" /></td>
                                </tr>
                                <tr>
                                    <td style="vertical-align: text-top;">Remarks</td>
                                    <td style="vertical-align: text-top;text-align: right;">:</td>
                                    <td>
                                        <textarea class="form-control input-sm" readonly="readonly" rows="4"><?php echo $pum_data['remark']; ?></textarea>
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
                                            <td><?php echo $pum_data['currency_id']; ?></td>
                                            <td style="text-align: right;"><?php echo number_format($row['meal_amount'],0,',','.'); ?></td>
                                            <td style="text-align: right;"><?php echo number_format($row['allowance_amount'],0,',','.'); ?></td>
                                            <td style="text-align: right;"><?php echo number_format($row['hotel_amount'],0,',','.'); ?></td>
                                            <td style="text-align: right;"><?php echo number_format($row['total_amount'],0,',','.'); ?></td>
                                         </tr>
                                    <?php
                                    }
                                    ?>
                                    <tr width="100%">
                                        <td colspan="9" style="text-align: right;vertical-align: middle;"><span align="right">Others</span></td>
                                        <td style="text-align: right;vertical-align: middle;">
                                            <?php echo $pum_data['currency_id']; ?>&nbsp;&nbsp;<b><?php echo number_format($pum_data['others'],0,',','.'); ?></b>
                                        </td>
                                    </tr>
                                    <tr width="100%">
                                        <td colspan="9" style="text-align: right;vertical-align: middle;"><span align="right">Grand Total</span></td>
                                        <td style="text-align: right;vertical-align: middle;">
                                            <?php echo $pum_data['currency_id']; ?>&nbsp;&nbsp;<b style="font-size: 18px;"><?php echo number_format($pum_data['amount']+$pum_data['others'],0,',','.'); ?></b>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="panel panel-info">
                        <div class="panel-heading">PUM Approval</div>
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
                					foreach ($pum_approval as $row) {
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
            <?php } ?>
            
            <!-- Approval List Section -->
            <?php if($model->status >= 1){ ?>
            <div class="panel panel-default">
                <div class="panel-heading">SPPD Approval List</div>
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

            <?php if (!$model->isNewRecord && $approver_flag != 2 && $model->status==1 && $model->created_by <> Yii::app()->user->id && !Yii::app()->globalFunction->is_admin_user(Yii::app()->user->id)) { ?>
            <h4>Responses</h4>
            <textarea rows="5" class="form-control" id='response_text' name="response_text"></textarea>
            <?php } ?>
            
            <?php echo $this->renderPartial('_view_response', array('model_response'=>$model_response, 'totalResp' => $totalResp)); ?>
            <!-- Panel Body End -->
        </div>

        <div class="panel-footer">
            <!-- Panel Footer Start -->
            <div class="row buttons">

                &nbsp;&nbsp;&nbsp;<a href="javascript:history.back()" class="btn btn-warning btn-sm"><i class="fa fa-arrow-left"></i>&nbsp;&nbsp;Cancel</a>
                &nbsp;&nbsp;&nbsp;<?php echo CHtml::submitButton('Save', array('class' => 'btn btn-primary btn-sm', 'disabled' => ($model->status == 0)?'':'disabled')); ?>

                <?php 
                if(!$model->isNewRecord && $approver_flag == 2 && $model->status==2 && $model->created_by <> Yii::app()->user->id){
                    echo CHtml::link('<i class="fa fa-remove"></i>&nbsp;Revise SPPD', array('businesstravel/ReviseSPPD', 'id' => $model->sppd_id), array('class' => 'btn btn-danger btn-sm ', 'disabled' => ($this->isAllowed(Yii::app()->user->id, 'approve') == true)?'':'disabled'));
                };
                ?>

                <?php if(!$model->isNewRecord && $approver_flag != 2 && $model->status==1 && $model->created_by <> Yii::app()->user->id && !Yii::app()->globalFunction->is_admin_user(Yii::app()->user->id)){ ?>
                    &nbsp;&nbsp;<?php echo CHtml::link('<i class="fa fa-remove"></i>&nbsp;Reject', '', array('id' => 'reject', 'class' => 'btn btn-danger btn-sm ', 'disabled' => ($this->isAllowed(Yii::app()->user->id, 'approve') == true)?'':'disabled')); ?>
                    &nbsp;&nbsp;<?php echo CHtml::link('<i class="fa fa-remove"></i>&nbsp;Revise', '', array('id' => 'send_back', 'class' => 'btn btn-danger btn-sm ', 'disabled' => ($this->isAllowed(Yii::app()->user->id, 'approve') == true)?'':'disabled')); ?>
                <?php } ?>
                <?php 
                if(!$model->isNewRecord && $model->status==0 && $model->created_by == Yii::app()->user->id){ ?>
                    &nbsp;&nbsp;<?php echo CHtml::link('<i class="fa fa-send"></i>&nbsp;Send To Approver', '', array('id'=>'send_to_approver',' class' => 'btn btn-info btn-sm ', 'disabled' => ($model->status == 0)?'':'disabled')); ?>
                <?php
                }
                ?>

                <?php /*
                if($model->rel_ebt_dest->types_of_trip == 'Two Ways') {
                    // ($model->booking_ticket == 0)?'':'disabled'
                    if(($model->status == 1 OR $model->status == 2) && $model->created_by == Yii::app()->user->id && $model->booking_ticket == 0){ ?>
                        &nbsp;&nbsp;
                        <?php echo CHtml::link('Ticket Request From', array('ticketReservation/requestticket2', 'sppd_id' => $model->sppd_id), array('class' => 'btn btn-primary btn-sm ', 'disabled' => 'disabled')); 
                    } 
                    // ($model->booking_ticket == 1)?'':'disabled'
                    if(($model->status == 1 OR $model->status == 2) && $model->created_by == Yii::app()->user->id && $model->booking_ticket == 1){ ?>
                        &nbsp;&nbsp;
                        <?php echo CHtml::link('Ticket Request To', array('ticketReservation/requestticket3', 'sppd_id' => $model->sppd_id), array('class' => 'btn btn-primary btn-sm ', 'disabled' => 'disabled'));
                    }
                } else {
                    // ($model->booking_ticket == 0)?'':'disabled'
                    if(($model->status == 1 OR $model->status == 2) && $model->created_by == Yii::app()->user->id && $model->booking_ticket == 0){ ?>
                        &nbsp;&nbsp;
                        <?php echo CHtml::link('Revise Date of SPPD', array('ticketReservation/requestticket', 'sppd_id' => $model->sppd_id), array('class' => 'btn btn-primary btn-sm ', 'disabled' => 'disabled')); ?>
                        &nbsp;&nbsp;
                        <?php echo CHtml::link('Cancel SPPD', array('ticketReservation/requestticket', 'sppd_id' => $model->sppd_id), array('class' => 'btn btn-primary btn-sm ', 'disabled' => 'disabled')); ?>
                        &nbsp;&nbsp;
                        <?php echo CHtml::link('Ticket Request', array('ticketReservation/requestticket', 'sppd_id' => $model->sppd_id), array('class' => 'btn btn-primary btn-sm ', 'disabled' => 'disabled')); ?>
                    <?php }
                } */
                ?>
                
                <?php //if($model->trip_id != 5 and $model->trip_id != 7){ ?>
                    <?php if(($model->status == 1 OR $model->status == 2) && $countPum == 0 && ($model->created_by == Yii::app()->user->id || Yii::app()->globalFunction->is_admin_user(Yii::app()->user->id))){ ?>
                        &nbsp;&nbsp;<?php echo CHtml::link('PUM Request', array('advancemoney/requestpum', 'sppd_id' => $model->sppd_id), array('class' => 'btn btn-primary btn-sm', 'disabled' => $countPum > 0 ? 'disabled' : '')); ?>
                    <?php } ?>
                <?php //} ?>
                <?php if($model->status == 1 && $is_permit == Yii::app()->user->id && $approval_date == '' && $approver_flag == 1){ ?>
                    &nbsp;&nbsp;<?php echo CHtml::link('<i class="fa fa-check"></i>&nbsp;Approve', array('businessTravel/approve', 'id' => $model->sppd_id), array('class' => 'btn btn-success btn-sm', 'disabled' => ($this->isAllowed(Yii::app()->user->id, 'approve') == TRUE)?'':'disabled')); ?>
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
        <h4>Are you sure want to <b>Revise</b> this SPPD Document ?</h4>
        <p><i>* this action will remove all user approver, send back to the user that created this document and change the status to draft again.</i></p>
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
        <h4>Are you sure want to <b>Reject</b> this SPPD Document ?</h4>
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
        <h4>Are you sure want to <b>Send To Approver</b> this SPPD Document ?</h4>
        <p><i>* this action will continue send your document to all the user approvers and change the status to progress.</i></p>
    </div>
    
    <div class="modal-footer" style="margin-top: 0px;">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <?php echo CHtml::link('<i class="fa fa-remove"></i>&nbsp;Yes', '', array('id' => 'confirm_send_to_approver', 'class' => 'btn btn-danger')); ?>
    </div>
    
<?php $this->endWidget(); ?>

<?php $this->beginWidget('bootstrap.widgets.TbModal', array('id'=>'modalDeleteDestination')); ?>

    <div class="modal-header">
        <h4>Confirmation</h4>
    </div>
    
    <div class="modal-body text-center">
        <input type="hidden" id="delete_dest_url" />
        <h4>Are you sure want to Delete this data ?</h4>
    </div>
    
    <div class="modal-footer" style="margin-top: 0px;">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <?php echo CHtml::link('<i class="fa fa-remove"></i>&nbsp;Yes', '', array('id' => 'confirm_delete_destination', 'class' => 'btn btn-danger')); ?>
    </div>
    
<?php $this->endWidget(); ?>
