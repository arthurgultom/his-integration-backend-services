<?php
/* @var $this SiteController */
$this->pageTitle=Yii::app()->name;
?>

<?php if(!Yii::app()->user->isGuest){ ?>
    <h3 style="margin-top: 0px;">Dashboard</h3>
    </br>
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <strong>USER INFORMATION</strong>
                </div>
                <div class="panel-body" style="padding: 0px;">
                    <div class="row" style="display: contents;">
                        <div class="col-lg-12 col-md-12 col-sm-12" style="margin: 0px;padding: 0px;">
                            <table class="table table-condensed table-hover table-striped">
                                <tr>
                                    <td style="width: 35%;">Employee ID</td>
                                    <td style="color: #000;font-family: sans-serif;">: <?php echo Yii::app()->user->id;?></td>
                                </tr>
                                <tr>
                                    <td>Full Name</td>
                                    <td style="color: #000;font-family: sans-serif;">: <?php echo Yii::app()->globalFunction->get_user_name(Yii::app()->user->id);?></td>
                                </tr>
                                <?php if(Yii::app()->globalFunction->get_user_role_id(Yii::app()->user->id) != 20){ ?>
                                <tr>
                                    <td>Email Address</td>
                                    <td style="color: #000;font-family: sans-serif;">: <?php echo Yii::app()->globalFunction->get_user_email_address(Yii::app()->user->id);?></td>
                                </tr>
                                <?php } ?>
                                <tr>
                                    <td style="width: 35%;">Division</td>
                                    <td style="color: #000;font-family: sans-serif;">: <?php echo Yii::app()->globalFunction->get_user_division_name(Yii::app()->user->id);?></td>
                                </tr>
                                <tr>
                                    <td>Department</td>
                                    <td style="color: #000;font-family: sans-serif;">: <?php echo Yii::app()->globalFunction->get_user_departemen_name(Yii::app()->user->id);?></td>
                                </tr>
                                <tr>
                                    <td>Location</td>
                                    <td style="color: #000;font-family: sans-serif;">: <?php echo Yii::app()->globalFunction->get_user_location_name(Yii::app()->user->id);?></td>
                                </tr>
                                <?php if(Yii::app()->globalFunction->get_user_role_id(Yii::app()->user->id) != 20){ ?>
                                <tr>
                                    <td style="width: 35%;">Bank Account Number</td>
                                    <td style="color: #000;font-family: sans-serif;">: 
                                        <?php 
                                        $bankAccount = Yii::app()->globalFunction->get_bank_account(Yii::app()->user->id);
                                        $notifBA = '<span style="font-style: italic;color: orange;">(Please update your Bank Account Number)</span>';
                                        echo ($bankAccount == null or $bankAccount == '') ? $notifBA : $bankAccount; 
                                        ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </table>
                        </div>
                    </div>
                    <?php if(Yii::app()->globalFunction->get_user_role_id(Yii::app()->user->id) != 20){ ?>
                    <div class="row" style="display: contents;">
						<div class="col-lg-12 col-md-12 col-sm-12" style="margin: 10px;padding: 0px;">
						    <?php 
						    echo CHtml::link(
						        Yii::t('component', 'Change Password'), 
						        array('user/changePassword', 'userid'=>Yii::app()->user->id), 
						        array('class'=>'btm btn-sm btn-success block')
					        );
					        ?>
					        <?php 
						    echo CHtml::link(
						        Yii::t('component', 'Update Bank Account'), 
						        array('employee/updateBank', 'id'=>Yii::app()->user->id), 
						        array('class'=>'btm btn-sm btn-warning block')
					        );
					        ?>
						</div>
                    </div>
                    <?php } ?>
                 </div>
            </div>
        </div>
    </div>
    
    
    <?php /*
    <td colspan="2">
        <a href="<?php //echo Yii::app()->createUrl('site/profile'); ?>">
            <i class="fa fa-fw fa-user"></i> Profile
        </a>
    </td>
    <td colspan="2">
        <a href="<?php //echo Yii::app()->createUrl('user/update',array('id'=>Yii::app()->user->id)); ?>">
            <i class="fa fa-fw fa-gear"></i> Settings
        </a>
    </td>
    */ ?>
    
    <?php 
    $user_role = Usersmodel::model()->find('user_id=?', array(Yii::app()->user->id)); 
    if ($user_role->division_id == 'ITD' OR 'FAD' OR 'IAD' OR 'HRD') {
    ?>
    <!-- Attachment List Section -->
    <?php /*
    <div class="panel panel-default">
        <div class="panel-heading">Download APK Epay Slip</div>
        <div id="section4" class="panel-expand expand">
            <div class="panel-body">
                <div align="center" class="table-responsive">
                <?php
                //print_r($model_destination->searchBySppd($model->sppd_id));
                $this->widget('zii.widgets.grid.CGridView', array(
                    'id' => 'advance-money-destination-model-grid',
                    'dataProvider'=>$model_attachment->downloadEpaySLip(),
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
                            'value'=>'CHtml::link($data->filename, Yii::app()->request->baseUrl."/protected/attachment/".$data->filename, array("target"=>"_blank"))',
                        ),
                        'description',
                    ),
                ));
                ?>
                </div>  
            </div>
        </div>
    </div>
    <!-- Attachment List Section -->
    */ ?>
    <?php } ?>
<?php } ?>

<?php 
// FOR Admin Secretery Management
if(Yii::app()->globalFunction->is_admin_user(Yii::app()->user->id)) {
    ?>
    <div class='row'>
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="panel panel-yellow">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-tasks fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo Yii::app()->globalFunction->total_sppd_management(Yii::app()->user->id);?></div>
                            <div>New SPPD Management</div>
                        </div>
                    </div>
                </div>
                <?php 
                echo CHtml::link('<div class="panel-footer"><span class="pull-left">View Detail</span><span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span><div class="clearfix"></div></div>',array('businesstravel/ListSppdHead'));
                ?>
            </div>
        </div>
    
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="panel panel-green">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-tasks fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo Yii::app()->globalFunction->total_pum_management(Yii::app()->user->id);?></div>
                            <div>New PUM Management</div>
                        </div>
                    </div>
                </div>
                <?php 
                echo CHtml::link('<div class="panel-footer"><span class="pull-left">View Detail</span><span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span><div class="clearfix"></div></div>',array('ListPumHead'));
                ?>
            </div>
        </div>
    </div>
    <?php
}

// Normal User
if(Yii::app()->globalFunction->isAllowed_Global(Yii::app()->user->id, 'show','ebt_dashboard') and Yii::app()->globalFunction->get_user_role_id(Yii::app()->user->id) != 20){
    if ($user_role->division_id == 'MGT' or Yii::app()->user->id == '39227'  or Yii::app()->user->id == '38820'  or Yii::app()->user->id == '39098'  or Yii::app()->user->id == '39321') {
    ?>
    <div class='row'>
        <div class="col-lg-3 col-md-3 col-sm-12">
            <div class="panel panel-yellow">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-tasks fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo $reader_notif_sppd=(empty($reader_notif_sppd))?'0':$reader_notif_sppd;?></div>
                            <div>New Approval SPPD Document!</div>
                        </div>
                    </div>
                </div>
                <?php 
                echo CHtml::link('<div class="panel-footer"><span class="pull-left">View Detail</span><span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span><div class="clearfix"></div></div>',array('ListSppdApproval'));
                ?>
            </div>
        </div>
        <div class="col-lg-3 col-md-3 col-sm-12">
            <div class="panel" style="background-color: #428bca;color: #fff;">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-tasks fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo Yii::app()->globalFunction->total_sppd_draft(Yii::app()->user->id);?></div>
                            <div>Draft SPPD Document!</div>
                        </div>
                    </div>
                </div>
                <?php 
                echo CHtml::link('<div class="panel-footer"><span class="pull-left">View Detail</span><span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span><div class="clearfix"></div></div>',array('businesstravel/index'));
                ?>
            </div>
        </div>
    
        <div class="col-lg-3 col-md-3 col-sm-12">
            <div class="panel panel-green">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-tasks fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo $reader_notif_pum=(empty($reader_notif_pum))?'0':$reader_notif_pum;?></div>
                            <div>New Approval PUM Document!</div>
                        </div>
                    </div>
                </div>
                <?php 
                echo CHtml::link('<div class="panel-footer"><span class="pull-left">View Detail</span><span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span><div class="clearfix"></div></div>',array('ListPumApproval'));
                ?>
            </div>
        </div>
        <div class="col-lg-3 col-md-3 col-sm-12">
            <div class="panel" style="background-color: #428bca;color: #fff;">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-tasks fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo Yii::app()->globalFunction->total_pum_draft(Yii::app()->user->id);?></div>
                            <div>Draft PUM Document!</div>
                        </div>
                    </div>
                </div>
                <?php 
                echo CHtml::link('<div class="panel-footer"><span class="pull-left">View Detail</span><span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span><div class="clearfix"></div></div>',array('advancemoney/index'));
                ?>
            </div>
        </div>
    </div>
    <?php 
    }
    else if((Yii::app()->globalFunction->get_position_emp(Yii::app()->user->id) != 3 and Yii::app()->globalFunction->get_position_emp(Yii::app()->user->id) != 4) or Yii::app()->globalFunction->get_user_role(Yii::app()->user->id) == 25){
    ?>
    <div class='row'>
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="panel panel-yellow">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-tasks fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo $reader_notif_sppd=(empty($reader_notif_sppd))?'0':$reader_notif_sppd;?></div>
                            <div>New Approval SPPD Document!</div>
                        </div>
                    </div>
                </div>
                <?php 
                echo CHtml::link('<div class="panel-footer"><span class="pull-left">View Detail</span><span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span><div class="clearfix"></div></div>',array('ListSppdApproval'));
                ?>
            </div>
        </div>
    
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="panel panel-green">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-tasks fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge"><?php echo $reader_notif_pum=(empty($reader_notif_pum))?'0':$reader_notif_pum;?></div>
                            <div>New Approval PUM Document!</div>
                        </div>
                    </div>
                </div>
                <?php 
                echo CHtml::link('<div class="panel-footer"><span class="pull-left">View Detail</span><span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span><div class="clearfix"></div></div>',array('ListPumApproval'));
                ?>
            </div>
        </div>
    </div>
    <?php 
    }
}
?>
<!-- End Notif for user approval EBTA-->

<script type='text/javascript'>
$(document).ready(function() {
    // $('#myModal').modal('show');
});
</script>

<?php /*
<div align="center">
    <img width='100%' src="<?php echo Yii::app()->request->baseUrl . '/images/gambar_depan.png'; ?>" />
    <div class="panel panel-primary">
        <div class="panel-heading">
            <strong>INFORMATION PANEL</strong>
        </div>
        <!-- /.panel-heading -->
        <div class="panel-body">
			<table class="table table-striped">
                <tr class='success'>
                    <td><h5><strong>KATEGORI 2</strong></h5></td>
                    <td><h5><strong>MODEL</strong></h5></td>
                    <td><h5><strong>WARRANTY</strong></h5></td>
                </tr>
                <tr>
                    <td><img src="<?php echo Yii::app()->request->baseUrl . '/image/Picture1.png'; ?>" /></td>
                    <td>DUTRO Semua Model</td>
                    <td>KM 100.000 ATAU Periode 36 BULAN mana yang tercapai terlebih dahulu, terhitung mulai unit diserahkan ke customer</td>
                </tr>
                <tr>
                    <td align='center'><img src="<?php echo Yii::app()->request->baseUrl . '/image/fb130.jpg'; ?>" height='100px' width='150px' /></td>
                    <td>FB2WGLZ</td>
                    <td>KM 50.000 ATAU Periode 12 BULAN mana yang tercapai terlebih dahulu, terhitung mulai unit diserahkan ke customer</td>
                </tr>
                <tr class='success'>
                    <td><h5><strong>KATEGORI 3</strong></h5></td>
                    <td><h5><strong>MODEL</strong></h5></td>
                    <td><h5><strong>WARRANTY</strong></h5></td>
                </tr>
            	<tr>
            		<td rowspan="2"><img src="<?php echo Yii::app()->request->baseUrl . '/image/cat3.png'; ?>" height='200px' width='280px'/></td>
            		<td>Semua model kecuali FM8JNKD</td>
            		<td>KM 50.000 ATAU Periode 12 BULAN mana yang tercapai terlebih dahulu, terhitung mulai unit diserahkan ke customer</td>
            	</tr>
            	<tr>
            		<td>FM8JNKD</td>
            		<td>KM 100.000 ATAU Periode 12 BULAN, mana yang tercapai terlebih dahulu, terhitung mulai unit diserahkan ke customer</td>
            	</tr>
            	
            	<tr class='success'>
                    <td><h5><strong>KATEGORI 5</strong></h5></td>
                    <td><h5><strong>MODEL</strong></h5></td>
                    <td><h5><strong>WARRANTY</strong></h5></td>
                </tr>
            	<tr >
            		<td><img src="<?php echo Yii::app()->request->baseUrl . '/image/Picture5.jpg'; ?>" /></td>
            		<td valign='bottom'>Semua Model 700 SERIES</td>
            		<td>KM 200.000 ATAU Periode 12 BULAN mana yang tercapai terlebih dahulu, terhitung mulai unit diserahkan ke customer</td>
            	</tr>
            </table>
        </div>
        <!-- /.panel-body -->
    </div>
</div>
*/ ?>

<!-- Modal -->
<?php /* if(!Yii::app()->user->isGuest){ ?>
<div class="modal"  id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><b>INFORMATION</b></h4>
            </div>
            <div class="modal-body" style="height:370px;overflow:scroll;">
            <?php foreach ($pop_up_list as $row) { ?>
                <h4><b><u><?php echo $row['title'];?></u></b></h4>
                <br/>
                <p><?php echo $row['information_content'];?></p>
                    
                <?php
                $list_attachment = $this->list_attachment($row['id']);
                foreach ($list_attachment as $row2) {
                ?>
                    <a  href='<?php echo Yii::app()->request->baseUrl."/protected/attachment/information_media/".$row2['file_name']; ?>' class='btn btn-success'>
                        <i class='fa fa-download'></i> <?php echo $row2['description'];?>
                    </a>
                    <br/>
                    <br/>
                <?php
                }
                ?>
                <hr/>
            <?php } ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php } */ ?>
