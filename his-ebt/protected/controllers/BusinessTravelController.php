<?php
class BusinessTravelController extends Controller {

    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/column2';

    /**
     * @return array action filters
     */
    public function filters() {
        return array(
            'accessControl', // perform access control for CRUD operations
            'postOnly + delete', // we only allow deletion via POST request
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array(
			 array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array('index', 'create', 'view', 'update', 'delete', 'sendtoapprover', 'requestticket', 'requestpum', 'approve', 'reject', 'sendresponse','print' ,'PrintSPPD' ,'TestEmailNew','sendback','ReviseSPPD','Export', 'redirectCreate', 'ListSppdHead', 'ListSppdDiv', 'AdminCreate', 'UpdateEmpNo', 'CheckPum'),
                'users' => array('@'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id) {
		$model = $this->loadModel($id);
		$model_employee = EmployeeModel::model()->findByPk(Yii::app()->user->id);
        $model_destination = new BusinessTravelDestinationModel('search');
        $model_attachment = new BusinessTravelAttachmentModel('search');
		$level_name = BusinessTravelModel::model()->getLevelName($model->emp_no);
		$division_name = BusinessTravelModel::model()->getDivisionName($model->division_id);
		$location_name = BusinessTravelModel::model()->getLocationName($model_employee->emp_no,$model_employee->location_id);

		$statement_app = "SELECT * FROM ebt_trs_approval a LEFT JOIN hgs_mst_employee b ON b.emp_no = a.approver_id WHERE doc_no = '".$id."'";
        $command_app = Yii::app()->db->createCommand($statement_app);
        $list_approval = $command_app->queryAll();

		$model_ticketDest = new TicketReservationDestinationModel;
		
		// List Responses
        $command = Yii::app()->db->createCommand("SELECT * FROM ebt_trs_sppd_response WHERE sppd_id = '$id'");
        $model_response = $command->queryAll();
        
        $cek_resp = "SELECT COUNT(*) as total_data FROM ebt_trs_sppd_response WHERE sppd_id = '$id'";
        $command_cek_resp = Yii::app()->db->createCommand($cek_resp);
        $row_cek_resp = $command_cek_resp->queryRow();
        $totalResp = $row_cek_resp['total_data'];
        
        $pum_data = '';
        $sppd_list = '';
        $pum_approval = '';

        if($model->advance_money > 0){
            
            $statement = "SELECT * FROM fin_trs_advance_money WHERE sppd_id = '".$model->sppd_id."'";
            $command = Yii::app()->db->createCommand($statement); 
            $pum_data = $command->queryRow();
            
            $statement = "SELECT * FROM ebt_trs_sppd_destination WHERE sppd_id = '".$model->sppd_id."'";
            $command = Yii::app()->db->createCommand($statement); 
            $sppd_list = $command->queryAll();
            
            $statement = "SELECT * FROM fin_trs_approval WHERE doc_no = '".$pum_data->adv_mon_id."' order by order_approval";
            $command = Yii::app()->db->createCommand($statement); 
            $pum_approval = $command->queryAll();

        }

        $this->render('view', array(
            'model'=>$model,
            'model_destination'=>$model_destination,
            'model_attachment'=>$model_attachment,
			'model_ticketDest'=>$model_ticketDest,
			'level_name' => $level_name,
			'division_name' => $division_name,
			'location_name' => $location_name,
			'list_approval' => $list_approval,
			'model_response'=>$model_response,
            'totalResp' => $totalResp,
            'pum_data' => $pum_data,
            'sppd_list' => $sppd_list,
            'pum_approval' => $pum_approval
        ));
    }

    public function actionPrintSPPD($id) {
        $model_destination = new BusinessTravelDestinationModel('search');
        $model_attachment = new BusinessTravelAttachmentModel('search');

        //glory 08 03 2016
        $model_ticketDest = new TicketReservationDestinationModel;
        //08 03 2016

        $this->render('_report_sppd', array(
            'model'=>$this->loadModel($id),
            'model_destination'=>$model_destination,
            'model_attachment'=>$model_attachment,
            'model_ticketDest'=>$model_ticketDest,
        ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate() {
        $model = new BusinessTravelModel;
        $emplist = EmployeeModel::model()->showListDivEmployeeName(Yii::app()->user->id);
        $emplistmech = EmployeeModel::model()->showListMechanic(Yii::app()->user->id);
		$emplistbanjarmasin = EmployeeModel::model()->showListBanjarmasin(Yii::app()->user->id);
		$emplistsby = EmployeeModel::model()->showListSBY(Yii::app()->user->id);
        $emplistmedan = EmployeeModel::model()->showListMedan(Yii::app()->user->id);
		$emplistBalikpapan = EmployeeModel::model()->showListBalikpapan(Yii::app()->user->id);
        //$instructlist = EmployeeModel::model()->showInstructListDivision(Yii::app()->user->id);
        $instructlist = EmployeeModel::model()->showApprovalUser(Yii::app()->user->id, 'business_travel');
        $instructedby = EmployeeModel::model()->showInstructedBy(Yii::app()->user->id, 'business_travel');
		$triplist = TripTypeModel::model()->showList();
        $model_employee = EmployeeModel::model()->findByPk(Yii::app()->user->id);
        
        // List Responses
        $command = Yii::app()->db->createCommand("SELECT * FROM ebt_trs_sppd_response WHERE sppd_id = '$id'");
        $model_response = $command->queryAll();

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);
        
        $model->trip_id = 4;

        if (isset($_POST['BusinessTravelModel'])) {

            $model->attributes = $_POST['BusinessTravelModel'];

            $days = Yii::app()->globalFunction->getDeviationDateDays($model->departure_date,$model->arrival_date);
            $meal_amount = (int) $model->meal_amount * $days;
            $allowance_amount = (int) $model->allowance_amount;
            $hotel_amount = (int) $model->hotel_amount;
            $transport_amount = (int) $model->transport_amount;
            $others_amount = (int) $model->others_amount;
            $total_amount = $meal_amount + $allowance_amount + $hotel_amount + $transport_amount + $others_amount;

            $model->days = $days;
            $model->total_amount = $total_amount;
            $model->serial_no = $this->setRecordNo();
            $model->created_date = date('c');
            $model->created_by = Yii::app()->user->id;
            $model->modified_date = date('c');
            $model->modified_by = Yii::app()->user->id;
            $model->sppd_id = $this->setDocumentNo();
            $model->sppd_date = date('Y-m-d');
            $model->division_id = Yii::app()->globalFunction->get_division_emp(Yii::app()->user->id);
            //$model->emp_no = Yii::app()->user->id;

            if ($model->save())
                $this->redirect(array('update','id'=>$model->sppd_id));
        }

        $this->render('create', array(
            'model' => $model,
			'emplist' => $emplist,
			'emplistbanjarmasin' => $emplistbanjarmasin,
			'emplistsby' => $emplistsby,
			'emplistmedan' => $emplistmedan,
			'emplistBalikpapan'=> $emplistBalikpapan,
            'instructlist' => $instructlist,
            'instructedby' => $instructedby,
			'triplist' => $triplist,
			'model_employee' => $model_employee,
			'model_response' => $model_response,
        ));
    }
    
    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionUpdateEmpNo() {
        $employee = EmployeeModel::model()->findByPk($_POST['BusinessTravelModel']['emp_no']);
        $instructedby = EmployeeModel::model()->showInstructedBy($_POST['BusinessTravelModel']['emp_no'], 'business_travel');
        
        $result = array(
            'division_id' => $employee->division_id,
            'emp_no' => $employee->emp_no,
            'emp_name' => $employee->emp_name,
            'instructedby' => $instructedby
        );
        
        echo json_encode($result);
    }
    
    public function actionAdminCreate() {
        $model = new BusinessTravelModel;
        //$emplist = EmployeeModel::model()->showListDivEmployeeName(Yii::app()->user->id);
        $instructlist = EmployeeModel::model()->showApprovalUser(Yii::app()->user->id, 'business_travel');
        $instructedby = EmployeeModel::model()->showInstructedBy(Yii::app()->user->id, 'business_travel');
		$triplist = TripTypeModel::model()->showList();
        $model_employee = EmployeeModel::model()->findByPk(Yii::app()->user->id);
        
        // List Responses
        $command = Yii::app()->db->createCommand("SELECT * FROM ebt_trs_sppd_response WHERE sppd_id = '$id'");
        $model_response = $command->queryAll();
        
        $model->trip_id = 4;
        
        // List User for Admin
        $_sql = "SELECT 
                hgs_mst_admin.head_id, 
                hgs_mst_employee.emp_name 
                FROM 
                hgs_mst_admin 
                INNER JOIN hgs_mst_employee ON hgs_mst_employee.emp_no = hgs_mst_admin.head_id 
                WHERE hgs_mst_admin.user_id = '".Yii::app()->user->id."'";
        $_command = Yii::app()->db->createCommand($_sql);
        $_reader = $_command->queryAll();
        $emplist = CHtml::listData($_reader,'head_id', 'emp_name');

        if (isset($_POST['BusinessTravelModel'])) {

            $model->attributes = $_POST['BusinessTravelModel'];

            $days = Yii::app()->globalFunction->getDeviationDateDays($model->departure_date,$model->arrival_date);
            $meal_amount = (int) $model->meal_amount * $days;
            $allowance_amount = (int) $model->allowance_amount;
            $hotel_amount = (int) $model->hotel_amount;
            $transport_amount = (int) $model->transport_amount;
            $others_amount = (int) $model->others_amount;
            $total_amount = $meal_amount + $allowance_amount + $hotel_amount + $transport_amount + $others_amount;

            $model->days = $days;
            $model->total_amount = $total_amount;
            $model->serial_no = $this->setRecordNoHead($_POST['BusinessTravelModel']['emp_no']);
            $model->created_date = date('c');
            $model->modified_date = date('c');
            $model->sppd_id = $this->setDocumentNoHead($_POST['BusinessTravelModel']['emp_no']);
            $model->sppd_date = date('Y-m-d');

            if ($model->save())
                $this->redirect(array('update','id'=>$model->sppd_id));
        }

        $this->render('admin_create', array(
            'model' => $model,
			'emplist' => $emplist,
			'instructlist' => $instructlist,
            'instructedby' => $instructedby,
			'triplist' => $triplist,
			'model_employee' => $model_employee,
			'model_response' => $model_response,
        ));
    }

	public function actionRedirectCreate() {
		//create sppd id, save it, then redirect to actionCreate

		date_default_timezone_set('Asia/Jakarta');
		$emp_no = Yii::app()->user->id;
		$sppd_id = $this->setDocumentNo();
		$sppd_date = date('Y-m-d');
		$serial_no = $this->setRecordNo();
		$division_id = Yii::app()->globalFunction->get_division_emp(Yii::app()->user->id);

		//$ins = "INSERT INTO ebt_trs_sppd (sppd_id) VALUES ".$sppd_id;
		$ins = "INSERT INTO ebt_trs_sppd (sppd_id, sppd_date, division_id, emp_no, serial_no) VALUES ('".$sppd_id."', '".$sppd_date."', '".$division_id."', '".$emp_no."', '".$serial_no."')";
		$comm_ins = Yii::app()->db->createCommand($ins);
        $comm_ins->execute();

		$this->redirect(array('update', 'id'=>$sppd_id));
	}

    public function actionUpdate($id) {

        $model = $this->loadModel($id);
        $model_destination = new BusinessTravelDestinationModel('search');
        $model_attachment = new BusinessTravelAttachmentModel('search');
        // $model_response = new BusinessTravelResponseModel('search');
		$emplist = EmployeeModel::model()->showListDivision(Yii::app()->user->id);
        //$instructlist = EmployeeModel::model()->showInstructListDivision(Yii::app()->user->id);
		//$instructlist = EmployeeModel::model()->showInstructListDivision($model->emp_no);
        $instructlist = EmployeeModel::model()->showApprovalUser(Yii::app()->user->id, 'business_travel');
		$triplist = TripTypeModel::model()->showList();
        $model_employee = EmployeeModel::model()->findByPk($model->emp_no);
        $model_level = JobLevelModel::model()->find('level_id=?', array($model_employee->level_id));
        $model_divisi = DivisionModel::model()->find('division_id=?', array($model_employee->division_id));
        $model_location = LocationModel::model()->find('location_id=?', array($model_employee->location_id));
        
        $instructed_data = EmployeeModel::model()->findByPk($model->instructed_by);
        $instructed_name = $instructed_data->emp_name;

        // List Responses
        $command = Yii::app()->db->createCommand("SELECT * FROM ebt_trs_sppd_response WHERE sppd_id = '$id'");
        $model_response = $command->queryAll();
        
        $cek_resp = "SELECT COUNT(*) as total_data FROM ebt_trs_sppd_response WHERE sppd_id = '$id'";
        $command_cek_resp = Yii::app()->db->createCommand($cek_resp);
        $row_cek_resp = $command_cek_resp->queryRow();
        $totalResp = $row_cek_resp['total_data'];
        
        $cek_dest = "SELECT COUNT(*) as total_data FROM ebt_trs_sppd_destination WHERE sppd_id = '$id'";
        $command_cek_dest = Yii::app()->db->createCommand($cek_dest);
        $row_cek_dest = $command_cek_dest->queryRow();
        $totalDest = $row_cek_dest['total_data'];

        $statement_app = "SELECT * FROM ebt_trs_approval a LEFT JOIN hgs_mst_employee b ON b.emp_no = a.approver_id WHERE doc_no = '".$id."'";
        $command_app = Yii::app()->db->createCommand($statement_app);
        $list_approval = $command_app->queryAll();

        $statement_permit = "SELECT * FROM ebt_trs_approval a LEFT JOIN hgs_mst_employee b ON b.emp_no = a.approver_id WHERE doc_no = '".$id."' AND a.approver_id = '".Yii::app()->user->id."'";
        $command_permit = Yii::app()->db->createCommand($statement_permit);
        $list_permit = $command_permit->queryRow();
        $is_permit = $list_permit['approver_id'];
        $approver_flag = $list_permit['approver_flag'];
        $approval_date = $list_permit['approver_date'];

        // Untuk merubah status response menjadi sudah terbaca
        if(isset($_REQUEST['response_id'])){
            if(!empty($_REQUEST['response_id'])){
                $statement = "UPDATE ebt_trs_sppd_response SET status_read = '1' WHERE id = '".$_REQUEST['response_id']."'";
                $command = Yii::app()->db->createCommand($statement);
                $command->execute();
            }
        }

        // Untuk merubah status approval menjadi sudah terbaca
        if(isset($_REQUEST['approver_id'])){
            if(!empty($_REQUEST['approver_id'])){
                $statement = "UPDATE ebt_trs_approval SET status_read = '1' WHERE id = '".$_REQUEST['approver_id']."' AND approver_id = '".Yii::app()->user->id."'";
                $command = Yii::app()->db->createCommand($statement);
                $command->execute();
            }
        }

        if (isset($_POST['BusinessTravelModel'])) {

            $model->attributes = $_POST['BusinessTravelModel'];

            $days = Yii::app()->globalFunction->getDeviationDateDays($model->departure_date,$model->arrival_date);
            
            // SUMMARIZE TOTAL
            $sqlSum = "SELECT 
                SUM(meal_amount) sum_meal, 
                SUM(allowance_amount) sum_allowance, 
                SUM(hotel_amount) sum_hotel, 
                SUM(total_amount) sum_total
                FROM ebt_trs_sppd_destination 
                WHERE sppd_id = '".$model->sppd_id."'";
            $dataSum = Yii::app()->db->createCommand($sqlSum)->queryRow();

            $model->days = $days;
            $model->meal_amount = $dataSum['sum_meal'];
            $model->allowance_amount = $dataSum['sum_allowance'];
            $model->hotel_amount = $dataSum['sum_hotel'];
            $model->total_amount = $dataSum['sum_total'];
            $model->modified_date = date('c');
            $model->modified_by = Yii::app()->user->id;

            if ($model->save())
                $this->redirect(array('update','id'=>$model->sppd_id));
        }
        
        $sqlPum = "SELECT COUNT(*) FROM fin_trs_advance_money WHERE sppd_id='".$id."'";
		$countPum = Yii::app()->db->createCommand($sqlPum)->queryScalar();
		
		$pum_data = '';
        $sppd_list = '';
        $pum_approval = '';

        if($model->advance_money > 0){
            
            $statement = "SELECT * FROM fin_trs_advance_money WHERE sppd_id = '".$model->sppd_id."'";
            $command = Yii::app()->db->createCommand($statement); 
            $pum_data = $command->queryRow();
            
            $statement = "SELECT * FROM ebt_trs_sppd_destination WHERE sppd_id = '".$model->sppd_id."'";
            $command = Yii::app()->db->createCommand($statement); 
            $sppd_list = $command->queryAll();
            
            $statement = "SELECT * FROM fin_trs_approval WHERE doc_no = '".$pum_data['adv_mon_id']."' order by order_approval";
            $command = Yii::app()->db->createCommand($statement); 
            $pum_approval = $command->queryAll();

        }

        $this->render('update', array(
            'model' => $model,
            'model_destination' => $model_destination,
            'model_attachment' => $model_attachment,
            'model_response' => $model_response,
			'emplist' => $emplist,
            'instructlist' => $instructlist,
			'triplist' => $triplist,
            'list_approval' => $list_approval,
            'is_permit' => $is_permit,
            'approver_flag' => $approver_flag,
            'approval_date'=>$approval_date,
            'model_employee' => $model_employee,
            'model_level' => $model_level,
            'model_divisi' => $model_divisi,
            'model_location' => $model_location,
            'instructed_name' => $instructed_name,
            'countPum' => $countPum,
            'totalResp' => $totalResp,
            'totalDest' => $totalDest,
            'pum_data' => $pum_data,
            'sppd_list' => $sppd_list,
            'pum_approval' => $pum_approval
        ));

    }

	public function actionExport(){
        $model = BusinessTravelModel::model()->getAllDataStatus_0();\
		
		Yii:: app()->request->sendFile('sppd.xls',
			$this->renderPartial('excel',array(
				'model'=>$model,
			),true)
		);
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id) {
        $this->loadModel($id)->delete();

        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
    }

    /**
     * Lists all models.
     */
    public function actionIndex() {
        $model = new BusinessTravelModel('search');
        $model->unsetAttributes();  // clear any default values
        
        $_user = UsersModel::model()->find('user_id=?', array(Yii::app()->user->id)); //added by doris on Nov 17, 2015 2:46 pm
        $level_emp = EmployeeModel::model()->find('emp_no=?', array(Yii::app()->user->id)); //added by doris on Dec 26, 2016 1:55 pm

		//variable untuk keperluan dropdown search status
		$status='';
		$status_dropdown = array();
		$connection = Yii::app()->db;
		$sqlStatement = "SELECT id, status FROM tws_mst_status where id in ('0','1','2','3')";
		$command=Yii::app()->db->createCommand($sqlStatement);
		$command->execute();
		$reader=$command->query();

		foreach($reader as $row)
		{
			$status_dropdown[$row['id']] = $row['status'];
		}

		if(isset($_GET['status']))
		{
			$status = $_GET['status'];
		}

        if (isset($_GET['BusinessTravelModel']))
            $model->attributes = $_GET['BusinessTravelModel'];

        $this->render('index', array(
            'model' => $model,
            'role' => $_user->role_id,
            'position' => $level_emp->position_id,
			'status'=>$status,
			'status_dropdown'=>$status_dropdown,
        ));
    }
    
    /**
     * Lists admin created.
     */
    public function actionListSppdHead() {
        $model = new BusinessTravelModel('search');
        $model->unsetAttributes();  // clear any default values
        
        $_user = UsersModel::model()->find('user_id=?', array(Yii::app()->user->id));
        $level_emp = EmployeeModel::model()->find('emp_no=?', array(Yii::app()->user->id));

		//variable untuk keperluan dropdown search status
		$status='';
		$status_dropdown = array();
		$connection = Yii::app()->db;
		$sqlStatement = "SELECT id, status FROM tws_mst_status where id in ('0','1','2','3')";
		$command=Yii::app()->db->createCommand($sqlStatement);
		$command->execute();
		$reader=$command->query();

		foreach($reader as $row)
		{
			$status_dropdown[$row['id']] = $row['status'];
		}

		if(isset($_GET['status']))
		{
			$status = $_GET['status'];
		}

        if (isset($_GET['BusinessTravelModel']))
            $model->attributes = $_GET['BusinessTravelModel'];

        $this->render('list_sppd_head', array(
            'model' => $model,
            'role' => $_user->role_id,
            'position' => $level_emp->position_id,
			'status'=>$status,
			'status_dropdown'=>$status_dropdown,
        ));
    }
    
    /**
     * Lists admin created.
     */
    public function actionListSppdDiv() {
        $model = new BusinessTravelModel('search');
        $model->unsetAttributes();  // clear any default values
        
        $_user = UsersModel::model()->find('user_id=?', array(Yii::app()->user->id));
        $level_emp = EmployeeModel::model()->find('emp_no=?', array(Yii::app()->user->id));

		//variable untuk keperluan dropdown search status
		$status='';
		$status_dropdown = array();
		$connection = Yii::app()->db;
		$sqlStatement = "SELECT id, status FROM tws_mst_status where id in ('0','1','2','3')";
		$command=Yii::app()->db->createCommand($sqlStatement);
		$command->execute();
		$reader=$command->query();

		foreach($reader as $row)
		{
			$status_dropdown[$row['id']] = $row['status'];
		}

		if(isset($_GET['status']))
		{
			$status = $_GET['status'];
		}

        if (isset($_GET['BusinessTravelModel']))
            $model->attributes = $_GET['BusinessTravelModel'];

        $this->render('list_sppd_div', array(
            'model' => $model,
            'role' => $_user->role_id,
            'position' => $level_emp->position_id,
			'status'=>$status,
			'status_dropdown'=>$status_dropdown,
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {

        $model = new BusinessTravelModel('search');
        $model->unsetAttributes();  // clear any default values

        if (isset($_GET['BusinessTravelModel']))
            $model->attributes = $_GET['BusinessTravelModel'];

        $this->render('admin', array(
            'model' => $model,
        ));
    }
    
    public function actionSendToApprover(){

        $tanggal_kirim = date('c');
        $nama_user = Yii::app()->user->id;
        $date_now = date('Y-m-d');
        $id = $_REQUEST['sppd_id'];
        
        // Cek Destination
        $sqlDest = "SELECT COUNT(*) FROM ebt_trs_sppd_destination WHERE sppd_id='".$id."'";
		$countDest = Yii::app()->db->createCommand($sqlDest)->queryScalar();
		if($countDest == 0){
		    $data_resp->error = "destination";
            echo json_encode( $data_resp );
            die();
		}
        
        $sppd_model = $this->loadModel($id);
        $model_employee = EmployeeModel::model()->findByPk($sppd_model->emp_no);

        $div_id = Yii::app()->globalFunction->get_division_emp($sppd_model->emp_no);
        $dept_id = Yii::app()->globalFunction->get_dept_emp($sppd_model->emp_no);
        
        // START APPROVER-DIVISI
        $statement_permit = "SELECT order_id 
                            FROM hgs_mst_approval 
                            WHERE function_id = 'business_travel' 
                            AND division_id = '".$model_employee->division_id."'
                            AND dept_id = '".$model_employee->dept_id."'
                            AND user_id = '".$sppd_model->emp_no."'";
        $command_permit = Yii::app()->db->createCommand($statement_permit);
        $list_permit = $command_permit->queryRow();
        $is_permit = $list_permit['order_id'];
        $where_order1 = "";
        if($is_permit !== NULL){
            $where_order1 = "AND order_id > ".$is_permit." ";
        }
        
        // Special Case Div : FAD/107 & Pos : 3/4 -> Approval AOKI(39227) Skip
        $whereSpecialFAD = "";
        if(($model_employee->division_id == 'FAD' or $model_employee->division_id == '107') and ($model_employee->position_id == '3' or $model_employee->position_id == '4')){
            $whereSpecialFAD = "AND user_id != '39227'";
        }
        
        // Special Case SHINO SAKAI
        $whereSpecialShingo = "";
        if($sppd_model->trip_id == 4){
            $whereSpecialShingo = "AND user_id != '39365'";
        }
        
        $queryRun = "SELECT user_id FROM hgs_mst_approval
                    WHERE division_id = '".$model_employee->division_id."'
                    AND dept_id = '".$model_employee->dept_id."' 
                    AND function_id = 'business_travel'
                    ".$where_order1."
                    ".$whereSpecialFAD."
                    ".$whereSpecialShingo."
                    ORDER BY order_id ASC
                    LIMIT 2";
        
        $command= Yii::app()->db->createCommand($queryRun);
		$reader = $command->queryAll();

        $first_record = true;
        $user_approver = '';
        $user_delegate = '';
        $order_id = 1;
        
        $countdiv = 0;

        foreach($reader as $rows){
            $need_approver = '0';
            if($first_record == true){
                $need_approver = '1';
                $first_record = false;
            }
            
            $user_approver = $rows['user_id'];
            
            if($user_approver != Yii::app()->user->id){
            
                $cek_redundant = "select count(*) as total_data from ebt_trs_approval where doc_no = '" . $sppd_model->sppd_id . "' AND approver_id = '" . $user_approver . "' ";
                $command_cek_redundant = Yii::app()->db->createCommand($cek_redundant);
                $row_cek_redundant = $command_cek_redundant->queryRow();
    
                if($row_cek_redundant['total_data'] == 0){
                    $statement_app = "INSERT INTO ebt_trs_approval(
                                    doc_no,
                                    doc_date,
                                    doc_base_url,
                                    approver_id,
                                    order_approval,
                                    approver_delegate_id,
                                    approver_flag
                                )
                        VALUES  ('".$sppd_model->sppd_id."',
                                 '".$sppd_model->sppd_date."',
                                 'businesstravel',
                                 '".$user_approver."',
                                 '".$order_id."',
                                 '".$user_delegate."',
                                 '".$need_approver."'
                                )";
        
                    $command_app = Yii::app()->db->createCommand($statement_app);
                    $command_app->execute();
                    $last_order = (int) $order_id; // added by doris on Dec 14, 2014 09:50 AM
                    $order_id++;
                    $countdiv++;
                }
            }
        }
        // END APPROVER-DIVISI
        
        // APPROVER-OVERSEAS	
        $countos = 0;	
		if($model_employee->division_id != 'ITT' and ($sppd_model->trip_id == '5' or $sppd_model->trip_id == '7')){
            $statement = "SELECT * FROM hgs_mst_approval_additional 
                          WHERE function_id = 'business_travel' 
                          AND type = 'overseas' 
                          AND deleteable = 0 
                          ORDER BY id ASC";
            $command = Yii::app()->db->createCommand($statement);
            $reader = $command->queryAll();

            $user_approver = '';
            $user_delegate = '';

            foreach($reader as $rows){

                $user_approver = $rows['user_id'];
            
                if($user_approver != $sppd_model->emp_no){
    
                    $cek_redundant = "select count(*) as total_data from ebt_trs_approval where doc_no = '" . $sppd_model->sppd_id . "' AND approver_id = '" . $user_approver . "' ";
                    $command_cek_redundant = Yii::app()->db->createCommand($cek_redundant);
                    $row_cek_redundant = $command_cek_redundant->queryRow();
        
                    if($row_cek_redundant['total_data'] == 0){
                        $last_order += 1;
                        $statement_app = "INSERT INTO ebt_trs_approval(
                                            doc_no,
                                            doc_date,
                                            doc_base_url,
                                            approver_id,
                                            order_approval,
                                            approver_delegate_id,
                                            approver_flag
                                        )
                                        VALUES  ('".$sppd_model->sppd_id."',
                                            '".$sppd_model->sppd_date."',
                                            'businesstravel',
                                            '".$user_approver."',
                                            '".$last_order."',
                                            '".$user_delegate."',
                                            '0'
                                        )";
    
                        $command_app = Yii::app()->db->createCommand($statement_app);
                        $command_app->execute();
                        $countos++;
                    }
                }
            }
        }

        // START APPROVER-HC
        if($model_employee->division_id != 'GAD' and $model_employee->division_id != 'ITT'){
            $statement = "SELECT * FROM hgs_mst_approval_additional 
                          WHERE function_id = 'business_travel' 
                          AND level_id = '".$model_employee->level_id."' 
                          AND deleteable = 0 
                          ORDER BY id ASC";
            $command = Yii::app()->db->createCommand($statement);
            $reader = $command->queryAll();
    
            $user_approver = '';
            $user_delegate = '';
    
            foreach($reader as $rows){
    
                $user_approver = $rows['user_id'];
                
                if($user_approver != $sppd_model->emp_no){
    
                    $cek_redundant = "select count(*) as total_data from ebt_trs_approval where doc_no = '" . $sppd_model->sppd_id . "' AND approver_id = '" . $user_approver . "' ";
                    $command_cek_redundant = Yii::app()->db->createCommand($cek_redundant);
                    $row_cek_redundant = $command_cek_redundant->queryRow();
        
                    if($row_cek_redundant['total_data'] == 0){
                        $last_order += 1;
                        $appflag = 0;
                        if($countdiv == 0 && $countos == 0){
                            $appflag = 1;
                        }
                        $statement_app = "INSERT INTO ebt_trs_approval(
                                            doc_no,
                                            doc_date,
                                            doc_base_url,
                                            approver_id,
                                            order_approval,
                                            approver_delegate_id,
                                            approver_flag
                                        )
                                        VALUES  ('".$sppd_model->sppd_id."',
                                            '".$sppd_model->sppd_date."',
                                            'businesstravel',
                                            '".$user_approver."',
                                            '".$last_order."',
                                            '".$user_delegate."',
                                            '".$appflag."'
                                        )";
        
                        $command_app = Yii::app()->db->createCommand($statement_app);
                        $command_app->execute();
                    }
                }
            }
        }
        // END APPROVER-HC
        
        $statement_sppd = "UPDATE ebt_trs_sppd SET status = '1' WHERE sppd_id = '".$id."'";
        $command_sppd = Yii::app()->db->createCommand($statement_sppd);
        $command_sppd->execute();
        
        // DESTINATION START
        $_sql = "SELECT 
                    a.departure_time, 
                    a.arrival_time, 
                    a.departure_date, 
                    a.arrival_date, 
                    b.transportation_name, 
                    c.status
                FROM 
                    ebt_trs_sppd_destination a, 
                    ebt_mst_transportation_type b, 
                    itf_mst_status c, 
                    ebt_trs_sppd d
                WHERE 
                    a.transportation_id=b.transportation_id AND 
                    c.id=d.status AND a.sppd_id=d.sppd_id AND
                    d.sppd_id = '".$sppd_model->sppd_id."'
                ORDER BY a.dest_id ASC
                LIMIT 1";
        $_command = Yii::app()->db->createCommand($_sql);
        $_reader = $_command->queryRow();
        
        $departure_date = $_reader['departure_date'];
        $departure_time = $_reader['departure_time'];
        
        // DESTINATION END
        $_sql = "SELECT 
                    a.departure_time, 
                    a.arrival_time, 
                    a.departure_date, 
                    a.arrival_date, 
                    b.transportation_name, 
                    c.status
                FROM 
                    ebt_trs_sppd_destination a, 
                    ebt_mst_transportation_type b, 
                    itf_mst_status c, 
                    ebt_trs_sppd d
                WHERE 
                    a.transportation_id=b.transportation_id AND 
                    c.id=d.status AND a.sppd_id=d.sppd_id AND
                    d.sppd_id = '".$sppd_model->sppd_id."'
                ORDER BY a.dest_id DESC
                LIMIT 1";
        $_command = Yii::app()->db->createCommand($_sql);
        $_reader = $_command->queryRow();
        
        $arrival_date = $_reader['arrival_date'];
        $arrival_time = $_reader['arrival_time'];
        
        $transportation_name = $_reader['transportation_name'];
        $status = $_reader['status'];
        
        // ALL CITY DESTINATION
        $sqlAllDest = "SELECT 
                    (select d.city_name from hgs_mst_city d where a.from=d.city_id) as city_name_from,
                    (select e.city_name from hgs_mst_city e where a.to=e.city_id) as city_name_to
                FROM 
                    ebt_trs_sppd_destination a, 
                    ebt_trs_sppd b
                WHERE 
                    a.sppd_id = b.sppd_id AND
                    a.sppd_id = '".$sppd_model->sppd_id."'
                ORDER BY a.dest_id ASC";
        $commandAllDest = Yii::app()->db->createCommand($sqlAllDest);
        $readerAllDest = $commandAllDest->queryAll();
        $city_destination = '';
        $totalDest = 0;
        foreach($readerAllDest as $rowAllDest){
            $totalDest++;
            if($totalDest == 1){
                $city_destination = $rowAllDest['city_name_to'];
            }else{
                $city_destination = $city_destination." - ".$rowAllDest['city_name_to'];
            }
        }

        $days_trip = Yii::app()->globalFunction->getDeviationDateDays($departure_date, $arrival_date);

        $statement_sppd = "UPDATE ebt_trs_sppd SET days = '".$days_trip."', departure_date = '".$departure_date."', arrival_date = '".$arrival_date."' WHERE sppd_id = '".$id."'";
        $command_sppd = Yii::app()->db->createCommand($statement_sppd);
        $command_sppd->execute();
        
        // FIRST APPROVER
        $sqlFA = "SELECT 
                    a.*, 
                    b.mail_address, 
                    b.full_name
                FROM 
                    ebt_trs_approval a
                INNER JOIN hgs_mst_user b 
                    ON b.user_id=a.approver_id
                WHERE 
                    a.doc_no = '".$sppd_model->sppd_id."' AND 
                    a.approver_flag = '1'";
        $commandFA = Yii::app()->db->createCommand($sqlFA);
        $readerFA = $commandFA->queryRow();
        
        $list_email = strtolower($readerFA['mail_address']);
        // "Eka.Nisa@hino.co.id",
        // "Yuanna.Fatmawati@hino.co.id",
        // "Meutya.Societa@hino.co.id",
        // "Internship.HC@hino.co.id",
        // "andito.lutfi@hino.co.id"
        
        $link_explode = explode('/',$_SERVER[REQUEST_URI]);
        $app_url = "https://".$_SERVER[HTTP_HOST]."/".$link_explode[1];
        
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://his-be-notification.hino.co.id/email-services/ebt-sppd-approval',
                CURLOPT_SSL_VERIFYHOST => false, //there must be this param
                CURLOPT_SSL_VERIFYPEER => false, //there must be this param
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'{
                    "email": [
                        "'.$list_email.'"
                    ],
                    "body": {
                        "approver_hr": "'.$readerFA['full_name'].'", 
                        "sppd_id": "'.$sppd_model->sppd_id.'", 
                        "emp_name": "'.$model_employee->emp_name.'", 
                        "emp_no": "'.$model_employee->emp_no.'", 
                        "division_id": "'.$sppd_model->division_id.'", 
                        "city_destination": "'.$city_destination.'", 
                        "departure_date": "'.$departure_date.'", 
                        "departure_time": "'.$departure_time.'", 
                        "arrival_date": "'.$arrival_date.'", 
                        "arrival_time": "'.$arrival_time.'", 
                        "status": "'.$status.'", 
                        "transportation_name": "'.$transportation_name.'",
                        "app_url": "'.$app_url.'"
                    }
                }',
                CURLOPT_HTTPHEADER => array(
                    'accept: application/json',
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
        
            $result = json_decode($response, true);
            if(isset($result['message']) and $result['message']=='email has been sent'){
                $statement_sppd = "UPDATE ebt_trs_approval SET email_status = 1, email_response = '".$result['message']."' WHERE id = '".$readerFA['id']."'";
                $command_sppd = Yii::app()->db->createCommand($statement_sppd);
                $command_sppd->execute();
            }else{
                $statement_sppd = "UPDATE ebt_trs_approval SET email_response = '".$response."' WHERE id = '".$readerFA['id']."'";
                $command_sppd = Yii::app()->db->createCommand($statement_sppd);
                $command_sppd->execute();
            }
        }
        
        catch(Exception $e) {
            $statement_sppd = "UPDATE ebt_trs_approval SET email_response = '".$e->getMessage()."' WHERE id = '".$readerFA['id']."'";
            $command_sppd = Yii::app()->db->createCommand($statement_sppd);
            $command_sppd->execute();
        }
        
        $this->redirect(array('update','id'=>$id));
    }

    private function getFirstApproverEbt($div_id, $dept_id){
        $sql2 = "SELECT usr.mail_address 
                FROM hgs_mst_approval app, hgs_mst_user usr 
                WHERE app.user_id=usr.user_id AND app.function_id = 'business_travel' AND app.division_id = '".$div_id."' AND app.dept_id = '".$dept_id."' AND app.order_id=1 
                ORDER BY app.order_id ASC";
        $command2 = Yii::app()->db->createCommand($sql2);
        $reader2 = $command2->query();

        return $reader2;
    }

    private function getFirstApproverSPPD($doc_no){
        $sql2 = "SELECT
                    usr.mail_address
                FROM
                    ebt_trs_approval app,
                    hgs_mst_user usr
                WHERE
                    app.approver_id = usr.user_id
                    AND app.doc_no = '".$doc_no."'
                    AND app.order_approval = 1
                ORDER BY app.order_approval ASC";
        $command2 = Yii::app()->db->createCommand($sql2);
        $reader2 = $command2->query();

        return $reader2;
    }

    // Fungsi actionApprove ini digunakan untuk melakukan proses permintaan persetujuan dokumen
    // @param string $id adalah nomer dokumen SPPD yang akan diproses
    // @return akan mengganti status dokumen menjadi status 2 / approved
    public function actionApprove(){
        $id = $_REQUEST['id'];
        $user_id = Yii::app()->user->id;

        $sppd_model = $this->loadModel($id);
        $model_employee = EmployeeModel::model()->findByPk($sppd_model->emp_no);
        
        // INSERT RESPONSE IF EXIST
        if($_REQUEST['response_text'] != ''){
            $tanggal_kirim = date('c');
            $isi_pesan = "Approved - ".$_REQUEST['response_text'];
            $ins = "INSERT INTO ebt_trs_sppd_response (sppd_id, tanggal_kirim, nama_user, isi_pesan) 
                    VALUES ('".$id."', '".$tanggal_kirim."', '(".$user_id.") ".$model_employee->emp_name."', '".$isi_pesan."')";
    		$comm_ins = Yii::app()->db->createCommand($ins);
            $comm_ins->execute();
        }
        
        // UPDATE EXISTING APPROVAL FLAG TO APPROVE
        $statement = "UPDATE ebt_trs_approval 
                    SET approver_flag = '2', approver_date = '".date('c')."' 
                    WHERE doc_no = '".$id."' AND approver_id = '".Yii::app()->user->id."'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();
        
        // GET DATA EXISTING APPROVAL
        $sql_first = "SELECT * FROM ebt_trs_approval WHERE doc_no = '".$id."' AND approver_id = '".$user_id."'";
        $command_first = Yii::app()->db->createCommand($sql_first);
        $reader_first = $command_first->queryRow();
        
        $next_order_approval = $reader_first['order_approval'] + 1;
        
        $sql_cek_count = "SELECT COUNT(*) FROM ebt_trs_approval WHERE doc_no = '".$id."' AND order_approval = '".$next_order_approval."'";
        $count = Yii::app()->db->createCommand($sql_cek_count)->queryScalar();
        
        if($count > 0){
        
            // UPDATE NEXT APPROVAL FLAG TO ON PROGRESS
            $statement = "UPDATE ebt_trs_approval SET approver_flag = '1' WHERE doc_no = '".$id."' AND order_approval = '".$next_order_approval."'";
            $command = Yii::app()->db->createCommand($statement);
            $command->execute();
            
            // GET DATA DETAIL SPPD FOR EMAIL
            $sql_app = "SELECT b.full_name, b.mail_address FROM ebt_trs_approval a, hgs_mst_user b 
                        WHERE a.approver_id = b.user_id 
                        AND a.doc_no = '".$id."' 
                        AND a.order_approval = '".$next_order_approval."'";
            $command_app = Yii::app()->db->createCommand($sql_app);
            $reader_app = $command_app->queryRow();
            // die($sql_app);
            $next_approver_name = $reader_app['full_name'];
            $next_approver_email = strtolower($reader_app['mail_address']);
        
            $_sql = "SELECT a.departure_time, a.arrival_time, a.departure_date, a.arrival_date, b.transportation_name, c.status, e.emp_name,
                (select g.city_name from hgs_mst_city g where a.to=g.city_id) as city_name_to, f.mail_address
                FROM ebt_trs_sppd_destination a, ebt_mst_transportation_type b,
                itf_mst_status c, ebt_trs_sppd d, hgs_mst_employee e, hgs_mst_user f
                WHERE a.transportation_id=b.transportation_id AND c.id=d.status AND a.sppd_id=d.sppd_id AND d.emp_no=e.emp_no AND
                f.user_id=e.emp_no AND d.sppd_id = '".$sppd_model->sppd_id."'";
            //SELECT b.emp_name FROM ebt_trs_approval a, hgs_mst_employee b WHERE a.appover_id=b.emp_no AND order_approval=1 AND doc_no = ";
            $_command = Yii::app()->db->createCommand($_sql);
            $_reader = $_command->queryAll();

            foreach ($_reader as $rows) {
                $emp_name = $rows['emp_name'];
                $departure_time = $rows['departure_time'];
                $departure_date = $rows['departure_date'];
                $arrival_time = $rows['arrival_time'];
                $arrival_date = $rows['arrival_date'];
                $transportation_name = $rows['transportation_name'];
                $status = $rows['status'];
                $city_destination = $rows['city_name_to'];
                $employee_mail_address = $rows['mail_address'];
            }
            
            // "Eka.Nisa@hino.co.id",
            // "Yuanna.Fatmawati@hino.co.id",
            // "Meutya.Societa@hino.co.id",
            // "Internship.HC@hino.co.id",
            // "andito.lutfi@hino.co.id"
            
            $link_explode = explode('/',$_SERVER[REQUEST_URI]);
            $app_url = "https://".$_SERVER[HTTP_HOST]."/".$link_explode[1];

            // ENDPOINT SEND EMAIL NEXT APPROVAL
    	    $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://his-be-notification.hino.co.id/email-services/ebt-sppd-approve',
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'{
                    "email": [
                        "'.$next_approver_email.'"
                    ],
                    "body": {
                        "superior_emp_name": "'.$next_approver_name.'",
                        "sppd_id": "'.$sppd_model->sppd_id.'",
                        "emp_name": "'.$model_employee->emp_name.'", 
                        "emp_no": "'.$model_employee->emp_no.'", 
                        "division_id": "'.$sppd_model->division_id.'", 
                        "city_destination": "'.$city_destination.'", 
                        "departure_date": "'.$departure_date.'", 
                        "departure_time": "'.$departure_time.'", 
                        "arrival_date": "'.$arrival_date.'", 
                        "arrival_time": "'.$arrival_time.'", 
                        "status": "'.$status.'", 
                        "transportation_name": "'.$transportation_name.'",
                        "app_url": "'.$app_url.'"
                    }
                }',
                CURLOPT_HTTPHEADER => array(
                    'accept: application/json',
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            
            $result = json_decode($response, true);
            if(isset($result['message']) and $result['message']=='email has been sent'){
                $statement_sppd = "UPDATE ebt_trs_approval SET email_status = 1, email_response = '".$result['message']."' 
                                    WHERE doc_no = '".$id."' AND order_approval = '".$next_order_approval."'";
                $command_sppd = Yii::app()->db->createCommand($statement_sppd);
                $command_sppd->execute();
            }else{
                $statement_sppd = "UPDATE ebt_trs_approval SET email_response = '".$response."' 
                                    WHERE doc_no = '".$id."' AND order_approval = '".$next_order_approval."'";
                $command_sppd = Yii::app()->db->createCommand($statement_sppd);
                $command_sppd->execute();
            }
        
        }
        
        // CEK FULL APPROVED OR NOT
        $sql_count = "SELECT COUNT(approver_id) AS app_count FROM ebt_trs_approval WHERE doc_no = '".$id."'";
        $command_count = Yii::app()->db->createCommand($sql_count);
        $reader_count = $command_count->queryRow();
        $app_count = $reader_count['app_count'];

        $sql_app = "SELECT COUNT(approver_id) AS app_count FROM ebt_trs_approval WHERE doc_no = '".$id."' AND approver_flag = '2'";
        $command_app = Yii::app()->db->createCommand($sql_app);
        $reader_app = $command_app->queryRow();
        $app_order = $reader_app['app_count'];

        // IF FULL APPROVED
        if($app_count == $app_order){
            // UPDATE SPPD STATUS TO APPROVED
            $statement = "UPDATE ebt_trs_sppd SET status = '2' WHERE sppd_id = '".$id."'";
            $command = Yii::app()->db->createCommand($statement);
            $command->execute();

            // APPROVER 1
            $sql_approver1="SELECT a.approver_date, b.full_name, b.mail_address FROM ebt_trs_approval a, hgs_mst_user b 
                            WHERE a.approver_id = b.user_id AND a.doc_no = '".$id."' AND a.order_approval = '1' 
                            ORDER BY a.order_approval ASC";
            $command_approver1 = Yii::app()->db->createCommand($sql_approver1);
            $reader_approver1 = $command_approver1->queryRow();
            $approver_name_1 = isset($reader_approver1['full_name']) ? $reader_approver1['full_name'] : '';
            $approver_date_1 = isset($reader_approver1['approver_date']) ? $reader_approver1['approver_date'] : '';
            
            // APPROVER 2
            if ($app_count == 3) {
                $sql_approver2="SELECT a.approver_date, b.full_name, b.mail_address FROM ebt_trs_approval a, hgs_mst_user b 
                                WHERE a.approver_id = b.user_id AND a.doc_no = '".$id."' AND a.order_approval = '2' 
                                ORDER BY a.order_approval ASC";
                $command_approver2 = Yii::app()->db->createCommand($sql_approver2);
                $reader_approver2 = $command_approver2->queryRow();
                $approver_name_2 = isset($reader_approver2['full_name']) ? $reader_approver2['full_name'] : '';
                $approver_date_2 = isset($reader_approver2['approver_date']) ? $reader_approver2['approver_date'] : '';
            }else{
                $approver_name_2 = '';
                $approver_date_2 = '';
            }

            // APPROVER HC
            if($model_employee->division_id != 'GAD'){
            $sql_approverhc="SELECT a.approver_date, b.full_name, b.mail_address FROM ebt_trs_approval a, hgs_mst_user b 
                            WHERE a.approver_id = b.user_id AND a.doc_no = '".$id."' AND a.order_approval = '".$app_count."' 
                            ORDER BY a.order_approval ASC";
            $command_approverhc = Yii::app()->db->createCommand($sql_approverhc);
            $reader_approverhc = $command_approverhc->queryRow();
            $approver_name_hc = isset($reader_approverhc['full_name']) ? $reader_approverhc['full_name'] : '';
            $approver_date_hc = isset($reader_approverhc['approver_date']) ? $reader_approverhc['approver_date'] : '';
            }
            
            // GET EMAIL USER THAT CREATE SPPD
            $sql_creator = "SELECT mail_address FROM hgs_mst_user WHERE user_id = '".$model_employee->emp_no."'";
            $command_creator = Yii::app()->db->createCommand($sql_creator);
            $reader_creator = $command_creator->queryRow();
            $creator_mail = strtolower($reader_creator['mail_address']);

            // GET UPDATE DATA SPPD STATUS
            $sql_after_update = "SELECT b.status FROM ebt_trs_sppd a 
                INNER JOIN itf_mst_status b ON b.id = a.status
                WHERE a.sppd_id = '".$sppd_model->sppd_id."'";
            $command_after_update = Yii::app()->db->createCommand($sql_after_update);
            $reader_after_update = $command_after_update->queryRow();
            
            $status_after_update = $reader_after_update['status'];
            
            // DESTINATION START
            $_sql = "SELECT 
                        a.departure_time, 
                        a.arrival_time, 
                        a.departure_date, 
                        a.arrival_date, 
                        b.transportation_name, 
                        c.status
                    FROM 
                        ebt_trs_sppd_destination a, 
                        ebt_mst_transportation_type b, 
                        itf_mst_status c, 
                        ebt_trs_sppd d
                    WHERE 
                        a.transportation_id=b.transportation_id AND 
                        c.id=d.status AND a.sppd_id=d.sppd_id AND
                        d.sppd_id = '".$sppd_model->sppd_id."'
                    ORDER BY a.dest_id ASC
                    LIMIT 1";
            $_command = Yii::app()->db->createCommand($_sql);
            $_reader = $_command->queryRow();
            
            $departure_date = $_reader['departure_date'];
            $departure_time = $_reader['departure_time'];
            
            // DESTINATION END
            $_sql = "SELECT 
                        a.departure_time, 
                        a.arrival_time, 
                        a.departure_date, 
                        a.arrival_date, 
                        b.transportation_name, 
                        c.status
                    FROM 
                        ebt_trs_sppd_destination a, 
                        ebt_mst_transportation_type b, 
                        itf_mst_status c, 
                        ebt_trs_sppd d
                    WHERE 
                        a.transportation_id=b.transportation_id AND 
                        c.id=d.status AND a.sppd_id=d.sppd_id AND
                        d.sppd_id = '".$sppd_model->sppd_id."'
                    ORDER BY a.dest_id DESC
                    LIMIT 1";
            $_command = Yii::app()->db->createCommand($_sql);
            $_reader = $_command->queryRow();
            
            $arrival_date = $_reader['arrival_date'];
            $arrival_time = $_reader['arrival_time'];
            
            $transportation_name = $_reader['transportation_name'];
            $status = $_reader['status'];
            
            // ALL CITY DESTINATION
            $sqlAllDest = "SELECT 
                        (select d.city_name from hgs_mst_city d where a.from=d.city_id) as city_name_from,
                        (select e.city_name from hgs_mst_city e where a.to=e.city_id) as city_name_to
                    FROM 
                        ebt_trs_sppd_destination a, 
                        ebt_trs_sppd b
                    WHERE 
                        a.sppd_id = b.sppd_id AND
                        a.sppd_id = '".$sppd_model->sppd_id."'
                    ORDER BY a.dest_id ASC";
            $commandAllDest = Yii::app()->db->createCommand($sqlAllDest);
            $readerAllDest = $commandAllDest->queryAll();
            $city_destination = '';
            $totalDest = 0;
            foreach($readerAllDest as $rowAllDest){
                $totalDest++;
                if($totalDest == 1){
                    $city_destination = $rowAllDest['city_name_to'];
                }else{
                    $city_destination = $city_destination." - ".$rowAllDest['city_name_to'];
                }
            }
            
            // "Eka.Nisa@hino.co.id",
            // "Yuanna.Fatmawati@hino.co.id",
            // "Meutya.Societa@hino.co.id",
            // "Internship.HC@hino.co.id",
            // "andito.lutfi@hino.co.id"
            
            $link_explode = explode('/',$_SERVER[REQUEST_URI]);
            $app_url = "https://".$_SERVER[HTTP_HOST]."/".$link_explode[1];
            
            // EMAIL TO USER THAT CREATE SPPD
			$curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://his-be-notification.hino.co.id/email-services/ebt-sppd-approved',
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>'{
                    "email": [
                        "'.$creator_mail.'"
                    ],
                    "body": {
                        "sppd_id": "'.$sppd_model->sppd_id.'", 
                        "emp_name": "'.$model_employee->emp_name.'", 
                        "emp_no": "'.$model_employee->emp_no.'", 
                        "division_id": "'.$sppd_model->division_id.'", 
                        "city_destination": "'.$city_destination.'", 
                        "departure_date": "'.$departure_date.'", 
                        "departure_time": "'.$departure_time.'", 
                        "arrival_date": "'.$arrival_date.'", 
                        "arrival_time": "'.$arrival_time.'", 
                        "status": "'.$status_after_update.'", 
                        "transportation_name": "'.$transportation_name.'",
                        "approver_name_1": "'.$approver_name_1.'",
                        "approver_name_2": "'.$approver_name_2.'",
                        "approver_date_1": "'.$approver_date_1.'",
                        "approver_date_2": "'.$approver_date_2.'",
                        "approver_name_hr": "'.$approver_name_hc.'",
                        "approver_date_hr": "'.$approver_date_hc.'",
                        "app_url": "'.$app_url.'"
                    }
                }',
                CURLOPT_HTTPHEADER => array(
                    'accept: application/json',
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            
            $result = json_decode($response, true);
            if(isset($result['message']) and $result['message']=='email has been sent'){
                $statement_sppd = "UPDATE ebt_trs_sppd SET email_status = 1, email_response = '".$result['message']."' 
                                    WHERE sppd_id = '".$id."'";
                $command_sppd = Yii::app()->db->createCommand($statement_sppd);
                $command_sppd->execute();
            }else{
                $statement_sppd = "UPDATE ebt_trs_sppd SET email_response = '".$response."' 
                                    WHERE sppd_id = '".$id."'";
                $command_sppd = Yii::app()->db->createCommand($statement_sppd);
                $command_sppd->execute();
            }
        }

        // $this->redirect(array('site/ListSppdApproval','id'=>$id));
        $this->redirect(array('update','id'=>$id));
    }

    public function actionCheckPum(){

        $sppd_id = $_POST['sppd_id'];
        
        $statement = "SELECT COUNT(*) total FROM fin_trs_advance_money WHERE sppd_id = '".$sppd_id."' AND status = 1";
        $command = Yii::app()->db->createCommand($statement); 
        $pum_data = $command->queryRow();

        echo json_encode($pum_data);
    }
    
    public function actionReject(){

        $tanggal_kirim = date('c');
        $nama_user = Yii::app()->user->id;
        $sppd_id = $_POST['sppd_id'];
        $isi_pesan = "Reject - ".$_POST['response_text'];
        $date_now = date('Y-m-d');

        $sppd_model = $this->loadModel($sppd_id);
        $emp_model = EmployeeModel::model()->findByPk($sppd_model->emp_no);
        $full_name = Yii::app()->globalFunction->get_user_name($nama_user);

        $ins = "INSERT INTO ebt_trs_sppd_response (sppd_id, tanggal_kirim, nama_user, isi_pesan) 
                VALUES ('".$sppd_id."', '".$tanggal_kirim."', '(".$nama_user.") ".$full_name."', '".$isi_pesan."')";
		$comm_ins = Yii::app()->db->createCommand($ins);
        $comm_ins->execute();
        
        $statement = "UPDATE ebt_trs_approval SET approver_flag = '3', approver_comment = '".$_POST['response_text']."', approver_date = '".$tanggal_kirim."' WHERE doc_no = '".$sppd_id."' AND approver_id = '".$nama_user."'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        $statement = "UPDATE ebt_trs_sppd SET status = '3' WHERE sppd_id = '".$sppd_id."'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();
        
        $statement = "UPDATE fin_trs_advance_money SET status = '3' WHERE sppd_id = '".$sppd_id."'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        // $this->redirect(array('update','id'=>$id));
        return $sppd_id;
    }

    // Fungsi actionSendBack ini digunakan untuk melakukan proses pengiriman balik dokumen
    // @param string $id adalah nomer dokumen SPPD yang akan diproses
    // @return status dokumen akan berubah menjadi draft kembali
    public function actionSendBack(){

        $tanggal_kirim = date('c');
        $nama_user = Yii::app()->user->id;
        $sppd_id = $_POST['sppd_id'];
        $isi_pesan = "Revise - ".$_POST['response_text'];
        $date_now = date('Y-m-d');

        $sppd_model = $this->loadModel($sppd_id);
        $emp_model = EmployeeModel::model()->findByPk($sppd_model->emp_no);
        $full_name = Yii::app()->globalFunction->get_user_name($nama_user);

        $ins = "INSERT INTO ebt_trs_sppd_response (sppd_id, tanggal_kirim, nama_user, isi_pesan) 
                VALUES ('".$sppd_id."', '".$tanggal_kirim."', '(".$nama_user.") ".$full_name."', '".$isi_pesan."')";
		$comm_ins = Yii::app()->db->createCommand($ins);
        $comm_ins->execute();

        $statement = "SELECT c.doc_no 
            FROM ebt_trs_sppd a, fin_trs_advance_money b, fin_trs_approval c
            WHERE 
                a.sppd_id=b.sppd_id AND 
                b.adv_mon_id = c.doc_no AND 
                a.sppd_id = '".$sppd_id."'";
        $connection = Yii::app()->db;
        $command = Yii::app()->db->createCommand($statement);
        $reader = $command->queryRow();
        $doc_no = $reader['doc_no'];

        $statement = "DELETE FROM fin_trs_approval WHERE doc_no = '".$doc_no."'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        $statement = "DELETE FROM fin_trs_advance_money WHERE sppd_id = '".$sppd_id."'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        $statement = "DELETE FROM ebt_trs_approval WHERE doc_no = '".$sppd_id."'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        // $statement = "DELETE FROM ebt_trs_sppd_destination WHERE sppd_id = '".$sppd_id."'";
        // $command = Yii::app()->db->createCommand($statement);
        // $command->execute();

        $statement = "UPDATE ebt_trs_sppd SET days = '0', departure_date = NULL, arrival_date = NULL, meal_amount = '0', allowance_amount = '0', hotel_amount = '0', transport_amount = '0', others_amount = '0',  total_amount = '0', status = '0', advance_money = '0' WHERE sppd_id = '".$sppd_id."'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        // $this->redirect(array('update','id'=>$sppd_id));
        return $sppd_id;
    }

    public function actionReviseSPPD($id){

        $tanggal_kirim = date('c');
        $nama_user = Yii::app()->user->id;
        $sppd_id = $id;
        $isi_pesan = $_POST['response_text'];
        $date_now = date('Y-m-d');

        $model = $this->loadModel($sppd_id);

        $statement = "UPDATE ebt_trs_sppd SET status = '1' WHERE sppd_id = '".$sppd_id."'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        $statement = "UPDATE ebt_trs_approval SET approver_date = NULL, approver_flag=1 WHERE approver_id='".Yii::app()->user->id."' and doc_no = '".$sppd_id."'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        $this->redirect(array('update','id'=>$sppd_id));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return BusinessTravelModel the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {

        $model = BusinessTravelModel::model()->findByPk($id);

        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param BusinessTravelModel $model the model to be validated
     */
    protected function performAjaxValidation($model) {

        if (isset($_POST['ajax']) && $_POST['ajax'] === 'business-travel-model-form') {

            echo CActiveForm::validate($model);
            Yii::app()->end();

        }
    }

    private function setDocumentNo() {

        $_user = UsersModel::model()->find('user_id=?', array(Yii::app()->user->id));
        $_prefix_division = EmployeeModel::model()->find('emp_no=?',array(Yii::app()->user->id));

        $_sql = "SELECT * FROM ebt_trs_sppd WHERE division_id = '" . $_prefix_division->division_id . "' AND sppd_date LIKE '" . date('Y-m') . "%' ORDER BY serial_no DESC LIMIT 0,1";
        $_command = Yii::app()->db->createCommand($_sql);
        $_reader = $_command->queryAll();
        $_temp = '';

        foreach ($_reader as $rows) {

            $_temp = (int) $rows['serial_no'];
        }

        $_temp = $_temp + 1;
        $_doc_no = 'SPPD/' . $_prefix_division->division_id.'/'.date('m').'/'.date('Y').'/'.sprintf("%03d", $_temp);

        return $_doc_no;
    }
    
    private function setDocumentNoHead($emp_no) {

        $_user = UsersModel::model()->find('user_id=?', array($emp_no));
        $_prefix_division = EmployeeModel::model()->find('emp_no=?',array($emp_no));

        $_sql = "SELECT * FROM ebt_trs_sppd WHERE division_id = '" . $_prefix_division->division_id . "' AND sppd_date LIKE '" . date('Y-m') . "%' ORDER BY serial_no DESC LIMIT 0,1";
        $_command = Yii::app()->db->createCommand($_sql);
        $_reader = $_command->queryAll();
        $_temp = '';

        foreach ($_reader as $rows) {

            $_temp = (int) $rows['serial_no'];
        }

        $_temp = $_temp + 1;
        $_doc_no = 'SPPD/' . $_prefix_division->division_id.'/'.date('m').'/'.date('Y').'/'.sprintf("%03d", $_temp);

        return $_doc_no;
    }

    private function setRecordNo() {

        $_user = UsersModel::model()->find('user_id=?', array(Yii::app()->user->id));
        $_prefix_division = EmployeeModel::model()->find('emp_no=?',array(Yii::app()->user->id));

        $_sql = "SELECT * FROM ebt_trs_sppd WHERE division_id = '" . $_prefix_division->division_id . "' AND sppd_date LIKE '" . date('Y-m') . "%' ORDER BY serial_no DESC LIMIT 0,1";
        $_command = Yii::app()->db->createCommand($_sql);
        $_reader = $_command->queryAll();
        $_temp = '';

        foreach ($_reader as $rows) {

            $_temp = (int) $rows['serial_no'];
        }

        $_temp = $_temp + 1;

        return $_temp;
    }

    private function setRecordNoHead($empno) {

        $_user = UsersModel::model()->find('user_id=?', array($empno));
        $_prefix_division = EmployeeModel::model()->find('emp_no=?',array($empno));

        $_sql = "SELECT * FROM ebt_trs_sppd WHERE division_id = '" . $_prefix_division->division_id . "' AND sppd_date LIKE '" . date('Y-m') . "%' ORDER BY serial_no DESC LIMIT 0,1";
        $_command = Yii::app()->db->createCommand($_sql);
        $_reader = $_command->queryAll();
        $_temp = '';

        foreach ($_reader as $rows) {

            $_temp = (int) $rows['serial_no'];
        }

        $_temp = $_temp + 1;

        return $_temp;
    }

    public function isAllowed($user, $access) {

        //print_r(Yii::app()->user->id);
        //die;
        $_user = UsersModel::model()->find('user_id=?', array(Yii::app()->user->id));
        $_access = AccessModel::model()->find('role_id=:role_id AND access_id = :access_id', array(':role_id' => $_user->role_id, 'access_id' => 'business_travel'));

        $_allowed = false;

        if ($access == 'index') {

            $_allowed = $_access->indexs;

        } elseif ($access == 'create') {

            $_allowed = $_access->creates;

        } elseif ($access == 'view') {

            $_allowed = $_access->views;

        } elseif ($access == 'update') {

            $_allowed = $_access->updates;

        } elseif ($access == 'delete') {

            $_allowed = $_access->deletes;

        } elseif($access == 'approve') {

            $_allowed = $_access->approve;

        } else {

            $_allowed = false;
        }

        return $_allowed;
    }

}
