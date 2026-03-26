<?php
class AdvanceMoneyController extends Controller
{
    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/column2';

    /**
     * @return array action filters
     */
    public function filters()
    {
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
    public function accessRules()
    {
        return array(
            array(
                'allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array('index', 'create', 'view', 'update', 'delete', 'sendtoapprover', 'SendToOutstanding', 'requestpum', 'approve', 'reject', 'sendresponse', 'print', 'PrintPUM', 'paid', 'transfer', 'sendback', 'Export', 'ListOutstanding', 'settlement', 'TestEmailNew', 'ExportIAD'),
                'users' => array('@'),
            ),
            array(
                'deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id)
    {
        $model = $this->loadModel($id);
        $model_attachment = new AdvanceMoneyAttachmentModel('search');
        $model_detail = new TicketReservationDestinationModel;
        $model_destination = new BusinessTravelDestinationModel('search');
        $no_rek = AdvanceMoneyModel::model()->getNoRek($model->emp_no);

        // List Responses
        $command = Yii::app()->db->createCommand("SELECT * FROM fin_trs_advance_money_response WHERE adv_mon_id = '$id'");
        $model_response = $command->queryAll();

        //glory 13 jan 2016
        $statement_app = "SELECT * FROM fin_trs_approval a LEFT JOIN hgs_mst_employee b ON b.emp_no = a.approver_id WHERE doc_no = '" . $id . "'";
        $command_app = Yii::app()->db->createCommand($statement_app);
        $list_approval = $command_app->queryAll();

        $statement_outstanding = "SELECT * FROM fin_trs_advance_money WHERE emp_no = '" . $model->emp_no . "' AND status = 2 AND paid_status = 0 ORDER BY adv_mon_id ASC ";
        $command_outstanding = Yii::app()->db->createCommand($statement_outstanding);
        $outstanding_pum = $command_outstanding->queryAll();
        //finish

        $sppd_data = '';
        $sppd_list = '';
        $sppd_approval = '';

        if ($model->sppd_id != '') {

            $statement = "SELECT * FROM ebt_trs_sppd WHERE sppd_id = '" . $model->sppd_id . "'";
            $command = Yii::app()->db->createCommand($statement);
            $sppd_data = $command->queryRow();

            $statement = "SELECT * FROM ebt_trs_sppd_destination WHERE sppd_id = '" . $model->sppd_id . "'";
            $command = Yii::app()->db->createCommand($statement);
            $sppd_list = $command->queryAll();

            $statement = "SELECT * FROM ebt_trs_approval WHERE doc_no = '" . $model->sppd_id . "' order by order_approval";
            $command = Yii::app()->db->createCommand($statement);
            $sppd_approval = $command->queryAll();
        }

        $this->render('view', array(
            'model' => $model,
            'model_attachment' => $model_attachment,
            'model_detail' => $model_detail,
            'model_destination' => $model_destination,
            'no_rek' => $no_rek,
            'list_approval' => $list_approval,
            'outstanding_pum' => $outstanding_pum,
            'sppd_data' => $sppd_data,
            'sppd_list' => $sppd_list,
            'sppd_approval' => $sppd_approval,
            'model_response' => $model_response,
        ));
    }

    public function actionPrintPUM($id)
    {
        //glory - 13 jan 2016 - nambahin getNoRek dan di modelnya
        $model = $this->loadModel($id);
        $model_attachment = new AdvanceMoneyAttachmentModel('search');
        $model_detail = new TicketReservationDestinationModel;
        $model_destination = new BusinessTravelDestinationModel('search');
        $no_rek = AdvanceMoneyModel::model()->getNoRek($model->emp_no);

        //glory 13 jan 2016
        $statement_app = "SELECT * FROM fin_trs_approval a LEFT JOIN hgs_mst_employee b ON b.emp_no = a.approver_id WHERE doc_no = '" . $id . "'";
        $command_app = Yii::app()->db->createCommand($statement_app);
        $list_approval = $command_app->queryAll();

        $statement_outstanding = "SELECT * FROM fin_trs_advance_money WHERE emp_no = '" . $model->emp_no . "' AND status = 2 AND paid_status = 0 ORDER BY adv_mon_id ASC ";
        $command_outstanding = Yii::app()->db->createCommand($statement_outstanding);
        $outstanding_pum = $command_outstanding->queryAll();

        $statement_sppd = "SELECT a.*, b.* FROM ebt_trs_sppd a, fin_trs_advance_money b WHERE a.sppd_id=b.sppd_id AND a.sppd_id = '" . $model->sppd_id . "'";
        $command_sppd = Yii::app()->db->createCommand($statement_sppd);
        $sppd_list = $command_sppd->queryAll();
        //finish

        $this->render('_report_pum', array(
            'model' => $model,
            'model_attachment' => $model_attachment,
            'model_detail' => $model_detail,
            'model_destination' => $model_destination,
            'no_rek' => $no_rek,
            'list_approval' => $list_approval,
            'outstanding_pum' => $outstanding_pum,
            'sppd_list' => $sppd_list,
        ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate()
    {
        $model = new AdvanceMoneyModel;

        $currencylist = CurrencyModel::model()->showList();
        $model_employee = EmployeeModel::model()->find('emp_no=?', array(Yii::app()->user->id));

        // Untuk mengambil informasi permintaan uang muka yang masih outstanding
        $statement = "SELECT * FROM fin_trs_advance_money WHERE emp_no = '" . $model->emp_no . "' AND status = 2 AND paid_status = 0 ORDER BY adv_mon_id ASC";
        $command = Yii::app()->db->createCommand($statement);
        $outstanding_pum = $command->queryAll();

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);
        $count = AdvanceMoneyModel::model()->countByAttributes(array(
            'emp_no' => $model->emp_no,
            'status' => 2,
            'paid_status' => 0,
        ));

        if ($count >= 2) {
            Yii::app()->user->setFlash('success', '<b>Saat ini Anda tidak dapat mengajukan uang muka. Mohon agar segera menyelesaikan Outstanding.</b>');
            $this->redirect(array('index'));
        } else {
            if (isset($_POST['AdvanceMoneyModel'])) {
                $model->attributes = $_POST['AdvanceMoneyModel'];

                $model->created_date = date('c');
                $model->created_by = Yii::app()->user->id;
                $model->modified_date = date('c');
                $model->modified_by = Yii::app()->user->id;
                $model->adv_mon_id = $this->setDocumentNo();
                $model->adv_mon_date = date('Y-m-d');
                $model->division_id = Yii::app()->globalFunction->get_division_emp(Yii::app()->user->id);
                $model->dept_id = Yii::app()->globalFunction->get_dept_emp(Yii::app()->user->id);
                $model->emp_no = Yii::app()->user->id;
                $model->serial_no = $this->setRecordNo();

                if ($model->save())
                    $this->redirect(array('update', 'id' => $model->adv_mon_id));
            }
        }
        $this->render('create', array(
            'model' => $model,
            'currencylist' => $currencylist,
            'outstanding_pum' => $outstanding_pum,
            'model_employee' => $model_employee
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id)
    {
        $model = $this->loadModel($id);
        $user_login = $model->emp_no;
        $currencylist = CurrencyModel::model()->showList();
        $model_employee = EmployeeModel::model()->findByPk($model->emp_no);
        $model_attachment = new AdvanceMoneyAttachmentModel('search');
        $model_attach = AdvanceMoneyAttachmentModel::model()->find('adv_mon_id=?', array($id));
        $_user = UsersModel::model()->find('user_id=?', array(Yii::app()->user->id));
        $role = $_user->role_id;

        $connection = Yii::app()->db;

        // List Responses
        $command = $connection->createCommand("SELECT * FROM fin_trs_advance_money_response WHERE adv_mon_id = '$id'");
        $model_response = $command->queryAll();

        //added by doris heryanto on Friday Jan 29, 2016 at 1:26 am start ***~~~~~
        $sqlStatement2 = "select amount, others from fin_trs_advance_money where adv_mon_id='$id'";
        //print_r($sqlStatement2);die;
        $command2 = $connection->createCommand($sqlStatement2);
        $command2->execute();
        $reader2 = $command2->query();
        foreach ($reader2 as $row2) {
            $amount = $row2['amount'];
            $others = $row2['others'];
        }

        //to get value Sales Composition
        $total_amount = $amount;
        $grand_total = $amount + $others;
        //print_r($total_amount);die;
        //~~~~~***finish

        //get SPPD ID by doris on Dec 7, 2015 11:24 AM. start ~~~~~~
        $statement_getSPPD = "SELECT sppd_id FROM fin_trs_advance_money a WHERE adv_mon_id = '" . $id . "'";
        $command_getSPPD = Yii::app()->db->createCommand($statement_getSPPD);
        $list_getSPPD = $command_getSPPD->queryRow();
        $is_getSPPD = $list_getSPPD['sppd_id'];
        /*~~~~~~finish*/

        if (isset($is_getSPPD)) $model_businessTravel = BusinessTravelModel::model()->find('sppd_id=?', $is_getSPPD);

        $model_employee = EmployeeModel::model()->find('emp_no=?', array($model->emp_no));

        // Untuk mengambil informasi permintaan uang muka yang masih outstanding
        $statement = "SELECT * FROM fin_trs_advance_money WHERE paid_status = 0 AND 
            status = 2 AND emp_no = '" . $model->emp_no . "' ORDER BY adv_mon_id ASC";
        $command = Yii::app()->db->createCommand($statement);
        $outstanding_pum = $command->queryAll();

        $statement_app = "SELECT * FROM fin_trs_approval a LEFT JOIN hgs_mst_employee b ON b.emp_no = a.approver_id WHERE doc_no = '" . $id . "'";
        $command_app = Yii::app()->db->createCommand($statement_app);
        $list_approval = $command_app->queryAll();

        $statement_permit = "SELECT * FROM fin_trs_approval a LEFT JOIN hgs_mst_employee b ON b.emp_no = a.approver_id WHERE doc_no = '" . $id . "' AND a.approver_id = '" . Yii::app()->user->id . "'";
        $command_permit = Yii::app()->db->createCommand($statement_permit);
        $list_permit = $command_permit->queryRow();
        $is_permit = $list_permit['approver_id'];
        $approver_flag = $list_permit['approver_flag'];
        $approval_date = $list_permit['approver_date'];

        $permit_fad = "SELECT * FROM fin_mst_approval_additional WHERE function_id = 'advance_money' AND division_id='FAD'";
        $command_permit_fad = Yii::app()->db->createCommand($permit_fad);
        $list_permit_fad = $command_permit_fad->queryRow();
        $button_fad = $list_permit_fad['user_id'];

        $sppd_data = '';
        $sppd_list = '';

        if ($model->sppd_id != '') {

            $statement = "SELECT * FROM ebt_trs_sppd_destination WHERE sppd_id = '" . $model->sppd_id . "' ORDER BY departure_date ASC";
            $command = Yii::app()->db->createCommand($statement);
            $sppd_list = $command->queryAll();

            $dest_index = 0;
            foreach ($sppd_list as $dataDest) {
                $dest_index++;
                if ($dest_index == 1) {
                    $departdate = new DateTime($dataDest['departure_date']);
                    $departdate->modify('-3 days');

                    $today = new DateTime();
                    if ($departdate < $today) {
                        $ondate = $today->format('Y-m-d');
                    } else {
                        $ondate = $departdate->format('Y-m-d');
                    }

                    $model->on_date = $ondate;
                }
            }

            $statement = "SELECT * FROM ebt_trs_sppd WHERE sppd_id = '" . $model->sppd_id . "'";
            $command = Yii::app()->db->createCommand($statement);
            $sppd_data = $command->queryRow();

            $statement = "SELECT * FROM ebt_trs_approval WHERE doc_no = '" . $model->sppd_id . "' order by order_approval";
            $command = Yii::app()->db->createCommand($statement);
            $sppd_approval = $command->queryAll();
        }

        //glory 11 05 2016
        $result_approve_sppd = AdvanceMoneyModel::model()->getApproverDateSppd($model->sppd_id, $user_login);
        $get_attach = AdvanceMoneyModel::model()->getAttachment($model->adv_mon_id);

        // Untuk merubah status response menjadi sudah terbaca
        if (isset($_REQUEST['response_id'])) {
            if (!empty($_REQUEST['response_id'])) {
                $statement = "UPDATE fin_trs_advance_money_response SET status_read = '1' WHERE id = '" . $_REQUEST['response_id'] . "'";
                $command = Yii::app()->db->createCommand($statement);
                $command->execute();
            }
        }

        // Untuk merubah status approval menjadi sudah terbaca
        if (isset($_REQUEST['approver_id'])) {
            if (!empty($_REQUEST['approver_id'])) {
                $statement = "UPDATE fin_trs_approval SET status_read = '1' WHERE id = '" . $_REQUEST['approver_id'] . "' AND approver_id = '" . $user_login . "'";
                $command = Yii::app()->db->createCommand($statement);
                $command->execute();
            }
        }

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['AdvanceMoneyModel'])) {
            $model->attributes = $_POST['AdvanceMoneyModel'];

            $model->modified_date = date('c');
            $model->modified_by = $user_login;
            $model->remark = $_POST['AdvanceMoneyModel']['remark'];
            $model->save();

            $model->grand_total = $model->amount + $model->others;
            $model->save();

            // var_dump($_POST['AdvanceMoneyModel']);

            $this->redirect(array('update', 'id' => $model->adv_mon_id));
        }

        $this->render('update', array(
            'model' => $model,
            'model_response' => $model_response,
            'currencylist' => $currencylist,
            'list_approval' => $list_approval,
            'is_permit' => $is_permit,
            'approver_flag' => $approver_flag,
            'button_fad' => $button_fad,
            'approval_date' => $approval_date,
            'sppd_data' => $sppd_data,
            'sppd_list' => $sppd_list,
            'sppd_approval' => $sppd_approval,
            'outstanding_pum' => $outstanding_pum,
            'model_businessTravel' => $model_businessTravel,
            'model_employee' => $model_employee,
            'total_amount' => $total_amount,
            'grand_total' => $grand_total,
            'model_employee' => $model_employee,
            'model_attachment' => $model_attachment,
            'model_attach' => $model_attach->adv_mon_id,
            'result_approve_sppd' => $result_approve_sppd,
            'get_attach' => $get_attach,
            'role' => $role
        ));
    }

    public function actionlistOutstanding()
    {
        $query = "
			SELECT
					*
				FROM
					fin_trs_advance_money,
					hgs_mst_employee
				WHERE
					fin_trs_advance_money.emp_no = hgs_mst_employee.emp_no
				AND paid_status = 0
				AND fin_trs_advance_money.`status` = 2 ORDER BY adv_mon_date ASC;";
        $_command = Yii::app()->db->createCommand($query);
        $_reader = $_command->queryAll();

        $this->render('_list_outstanding', array(
            'list_outstanding' => $_reader,
        ));
    }

    public function actionSettlement($id)
    {
        $statement2 = "update fin_trs_advance_money SET paid_status='1' where adv_mon_id='$id'";
        $command2 = Yii::app()->db->createCommand($statement2);
        if ($command2->execute()) {
            Yii::app()->user->setFlash('message_diff', 'PUM has been settled already.! ' . $id);

            //added by doris on Oct 23, 2015 1:29 PM start ~~~~
            $statement = "INSERT INTO fin_export_excel_log VALUES 
				(NULL, '" . $id . "', '" . Yii::app()->user->id . " melakukan Settlement pada " . date('Y-m-d H:i:s') . "', 
				'" . Yii::app()->user->id . "','" . date('Y-m-d H:i:s') . "')";
            $command = Yii::app()->db->createCommand($statement);
            $command->execute();
            //~~~~ finish

            $this->redirect(array('index'));
        }
    }

    public function actionExport()
    {
        $model = AdvanceMoneyModel::model()->getAllDataStatus_0();

        $connection = Yii::app()->db;
        $sqlStatement2 = "SELECT adv_mon_id FROM fin_trs_advance_money WHERE status=2 AND export_status=0";
        $command2 = $connection->createCommand($sqlStatement2);
        $reader2 = $command2->query();

        foreach ($reader2 as $row2) {
            $statement_adv = "UPDATE fin_trs_advance_money SET export_status = '1' WHERE adv_mon_id = '" . $row2['adv_mon_id'] . "'";
            $command_adv = $connection->createCommand($statement_adv);
            $command_adv->execute();

            //added by doris on Oct 23, 2015 1:29 PM start ~~~~
            $statement = "INSERT INTO fin_export_excel_log VALUES 
				(NULL, '" . $row2['adv_mon_id'] . "', '" . Yii::app()->user->id . " melakukan export pada " . date('Y-m-d H:i:s') . "', 
				'" . Yii::app()->user->id . "','" . date('Y-m-d H:i:s') . "')";
            $command = Yii::app()->db->createCommand($statement);
            $command->execute();
            //~~~~ finish
        }

        Yii::app()->request->sendFile(
            'pum.xls',
            $this->renderPartial('excel', array(
                'model' => $model,
            ), true)
        );
    }

    public function actionExportIAD()
    {
        $model = AdvanceMoneyModel::model()->getAllDataIAD();

        $statement = "INSERT INTO fin_export_excel_log VALUES 
			(NULL, '', '" . Yii::app()->user->id . " melakukan export pada " . date('Y-m-d H:i:s') . "', 
			'" . Yii::app()->user->id . "','" . date('Y-m-d H:i:s') . "')";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        Yii::app()->request->sendFile(
            'Export Data EBTA ' . date('Y-m-d') . '.xls',
            $this->renderPartial('excel_iad', array(
                'model' => $model,
            ), true)
        );
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id)
    {
        /*
		$this->loadModel($id)->delete();

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
		*/

        // Arie : 14 Sep 2016
        // Function for when user delete the data system will automatically update advance money status on sppd data 

        $data = $this->loadModel($id);

        if ($data->sppd_id != "") {
            $command = Yii::app()->db->createCommand("UPDATE " . BusinessTravelModel::model()->tableName . " SET advance_money = '0' WHERE sppd_id = '" . $data->sppd_id . "';");
            $command->execute();
        }

        // Delete data after update advance money status on sppd table
        $data->delete();

        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
    }

    /**
     * Lists all models.
     */
    public function actionIndex()
    {
        $model = new AdvanceMoneyModel('search');
        $model->unsetAttributes();  // clear any default values

        $_user = UsersModel::model()->find('user_id=?', array(Yii::app()->user->id));

        if (isset($_GET['AdvanceMoneyModel']))
            $model->attributes = $_GET['AdvanceMoneyModel'];

        $this->render('index', array(
            'model' => $model,
            'role' => $_user->role_id
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin()
    {
        $model = new AdvanceMoneyModel('search');
        $model->unsetAttributes();  // clear any default values

        if (isset($_GET['AdvanceMoneyModel']))
            $model->attributes = $_GET['AdvanceMoneyModel'];

        $this->render('admin', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return AdvanceMoneyModel the loaded model
     * @throws CHttpException
     */
    public function loadModel($id)
    {
        $model = AdvanceMoneyModel::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param AdvanceMoneyModel $model the model to be validated
     */
    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'advance-money-model-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    // Fungsi actionSendToApprover ini digunakan untuk melakukan proses permintaan persetujuan dokumen
    // @param string $id adalah nomer dokumen SPPD yang akan diproses
    // @param string $div_id adalah kode divisi yang diambil dari user yang login
    // @param string $dept_id adalah kode department yang diambil dari user yang login
    // @return akan mengganti status dokumen menjadi status 1 / on progress 
    public function actionSendToApprover()
    {
        $id = $_REQUEST['id'];
        $adv_mon_model = $this->loadModel($id);

        // CHECK VALIDASI SPPD FULLY APPROVED
        $cek_validasi_total = "select count(*) as total_data from ebt_trs_approval where doc_no = '" . $adv_mon_model->sppd_id . "'";
        $command_cek_validasi_total = Yii::app()->db->createCommand($cek_validasi_total);
        $row_cek_validasi_total = $command_cek_validasi_total->queryRow();

        $cek_validasi_approval = "select count(*) as total_data from ebt_trs_approval where doc_no = '" . $adv_mon_model->sppd_id . "' and approver_flag = '2'";
        $command_cek_validasi_approval = Yii::app()->db->createCommand($cek_validasi_approval);
        $row_cek_validasi_approval = $command_cek_validasi_approval->queryRow();

        if ($adv_mon_model->budget_code == '') {
            Yii::app()->user->setFlash('success', '<b>Please complete the Budget Code fields, then click the Save button, and finally click the Send to Approver button.</b>');
            $this->redirect(array('update', 'id' => $id));
            // } else if($row_cek_validasi_total['total_data'] != $row_cek_validasi_approval['total_data']) {
            //     Yii::app()->user->setFlash('success', "<b>Can't progress PUM because your SPPD Number '.$adv_mon_model->sppd_id.' not yet fully approved.</b>");
            //     $this->redirect(array('update','id'=>$id));
        } else {

            // NEW UPDATE HEADER DATA
            $statement = "UPDATE fin_trs_advance_money SET 
                remark = :remark, 
                towards = :towards, 
                on_date = :on_date, 
                others = :others, 
                budget_code = :budget_code 
                WHERE adv_mon_id = :id";
            $command = Yii::app()->db->createCommand($statement);
            $command->bindParam(":remark", $_POST['remark']);
            $command->bindParam(":towards", $_POST['towards']);
            $command->bindParam(":on_date", $_POST['on_date']);
            $command->bindParam(":others", $_POST['others']);
            $command->bindParam(":budget_code", $_POST['budget_code']);
            $command->bindParam(":id", $id);
            $command->execute();

            $sppd_model = BusinessTravelModel::model()->findByPk($adv_mon_model->sppd_id);
            $sppd_dest_model = BusinessTravelDestinationModel::model()->findByPk($sppd_model->sppd_id);
            $model_employee = EmployeeModel::model()->findByPk($sppd_model->emp_no);
            $div_id = Yii::app()->globalFunction->get_division_emp(Yii::app()->user->id);
            $dept_id = Yii::app()->globalFunction->get_dept_emp(Yii::app()->user->id);

            $_user = UsersModel::model()->find('user_id=?', array(Yii::app()->user->id));

            // START APPROVER-DIVISI
            $statement_permit = "SELECT order_id 
                                FROM hgs_mst_approval 
                                WHERE function_id = 'advance_money' 
                                AND division_id = '" . $model_employee->division_id . "'
                                AND dept_id = '" . $model_employee->dept_id . "'
                                AND user_id = '" . Yii::app()->user->id . "'";
            $command_permit = Yii::app()->db->createCommand($statement_permit);
            $list_permit = $command_permit->queryRow();
            $is_permit = $list_permit['order_id'];
            $where_order1 = "";
            if ($is_permit !== NULL) {
                $where_order1 = "AND order_id > " . $is_permit . " ";
            }

            // Special Case Div : FAD/107 & Pos : 3/4 -> Approval AOKI(39227) Skip
            $whereSpecialFAD = "";
            if (($model_employee->division_id == 'FAD' or $model_employee->division_id == '107') and ($model_employee->position_id == '3' or $model_employee->position_id == '4')) {
                $whereSpecialFAD = "AND user_id != '39227'";
            }

            // Special Case SHINO SAKAI
            $whereSpecialShingo = "";
            if ($sppd_model->trip_id == 4) {
                $whereSpecialShingo = "AND user_id != '39365'";
            }

            $queryRun = "SELECT user_id FROM hgs_mst_approval
                        WHERE function_id = 'advance_money' 
                        AND division_id = '" . $model_employee->division_id . "'
                        AND dept_id = '" . $model_employee->dept_id . "' 
                        " . $where_order1 . "
                        " . $whereSpecialFAD . "
                        " . $whereSpecialShingo . "
                        ORDER BY order_id ASC
                        LIMIT 2";

            //return $queryRun." ------- ".$is_permit;

            $command = Yii::app()->db->createCommand($queryRun);
            $reader = $command->queryAll();

            $first_record = true;
            $user_approver = '';
            $user_delegate = '';
            $order_id = 1;

            foreach ($reader as $rows) {
                $need_approver = '0';
                if ($first_record == true) {
                    $need_approver = '1';
                    $first_record = false;
                }

                $user_approver = $rows['user_id'];

                if (Yii::app()->user->id != $user_approver) {
                    $cek_redundant = "select count(*) as total_data from fin_trs_approval where doc_no = '" . $adv_mon_model->adv_mon_id . "' AND approver_id = '" . $user_approver . "' ";
                    $command_cek_redundant = Yii::app()->db->createCommand($cek_redundant);
                    $row_cek_redundant = $command_cek_redundant->queryRow();
                    if ($row_cek_redundant['total_data'] == 0) {
                        $statement_app = "INSERT INTO fin_trs_approval(
                                        doc_no,
                                        doc_date,
                                        doc_base_url,
                                        approver_id,
                                        order_approval,
                                        approver_delegate_id,
                                        approver_flag
                                    )
                            VALUES  ('" . $adv_mon_model->adv_mon_id . "',
                                     '" . $adv_mon_model->adv_mon_date . "',
                                     'advancemoney',
                                     '" . $user_approver . "',
                                     '" . $order_id . "',
                                     '" . $user_delegate . "',
                                     '" . $need_approver . "'
                                    )";

                        $command_app = Yii::app()->db->createCommand($statement_app);
                        $command_app->execute();
                        $last_order = (int) $order_id;
                        $order_id++;
                    }
                }
            }
            // END APPROVER-DIVISI

            // START APPROVER-HC
            if ($model_employee->division_id != 'GAD' and $model_employee->division_id != 'ITT') {
                $statement = "SELECT * FROM hgs_mst_approval_additional 
                              WHERE function_id = 'business_travel' 
                              AND level_id = '" . $model_employee->level_id . "' 
                              AND deleteable = 0 
                              ORDER BY id ASC";
                $command = Yii::app()->db->createCommand($statement);
                $reader = $command->queryAll();

                $user_approver = '';
                $user_delegate = '';

                foreach ($reader as $rows) {

                    $user_approver = $rows['user_id'];

                    if (Yii::app()->user->id != $user_approver) {

                        $cek_redundant = "select count(*) as total_data from fin_trs_approval where doc_no = '" . $adv_mon_model->adv_mon_id . "' AND approver_id = '" . $user_approver . "' ";
                        $command_cek_redundant = Yii::app()->db->createCommand($cek_redundant);
                        $row_cek_redundant = $command_cek_redundant->queryRow();

                        if ($row_cek_redundant['total_data'] == 0) {
                            $last_order += 1;
                            $statement_app = "INSERT INTO fin_trs_approval(
                                            doc_no,
                                            doc_date,
                                            doc_base_url,
                                            approver_id,
                                            order_approval,
                                            approver_delegate_id,
                                            approver_flag
                                        )
                                VALUES  ('" . $adv_mon_model->adv_mon_id . "',
                                         '" . $adv_mon_model->adv_mon_date . "',
                                         'advancemoney',
                                         '" . $user_approver . "',
                                         '" . $last_order . "',
                                         '" . $user_delegate . "',
                                         '0'
                                        )";

                            $command_app = Yii::app()->db->createCommand($statement_app);
                            $command_app->execute();
                        }
                    }
                }
            }
            // END APPROVER-HC

            // START APPROVER-FAD
            if ($model_employee->division_id != 'ITT') {
                $statement = "SELECT * FROM hgs_mst_approval_additional 
                              WHERE function_id = 'advance_money' 
                              AND deleteable = 0 
                              ORDER BY id ASC";
                $command = Yii::app()->db->createCommand($statement);
                $reader = $command->queryAll();

                $user_approver = '';
                $user_delegate = '';

                foreach ($reader as $rows) {

                    $user_approver = $rows['user_id'];

                    if (Yii::app()->user->id != $user_approver) {

                        $cek_redundant = "select count(*) as total_data from fin_trs_approval where doc_no = '" . $adv_mon_model->adv_mon_id . "' AND approver_id = '" . $user_approver . "' ";
                        $command_cek_redundant = Yii::app()->db->createCommand($cek_redundant);
                        $row_cek_redundant = $command_cek_redundant->queryRow();

                        if ($row_cek_redundant['total_data'] == 0) {
                            $last_order += 1;
                            $statement_app = "INSERT INTO fin_trs_approval(
                                            doc_no,
                                            doc_date,
                                            doc_base_url,
                                            approver_id,
                                            order_approval,
                                            approver_delegate_id,
                                            approver_flag
                                        )
                                VALUES  ('" . $adv_mon_model->adv_mon_id . "',
                                         '" . $adv_mon_model->adv_mon_date . "',
                                         'advancemoney',
                                         '" . $user_approver . "',
                                         '" . $last_order . "',
                                         '" . $user_delegate . "',
                                         '0'
                                        )";

                            $command_app = Yii::app()->db->createCommand($statement_app);
                            $command_app->execute();
                        }
                    }
                }
            }
            // END APPROVER-FAD

            // GET DETAIL PUM FOR EMAIL
            $_sql = "SELECT 
                        a.departure_time, 
                        a.arrival_time, 
                        d.departure_date, 
                        d.arrival_date, 
                        b.transportation_name, 
                        c.status, 
                        d.meal_amount, 
                        d.hotel_amount, 
                        d.allowance_amount, 
                        e.others, 
                        e.remark, 
                        (select f.city_name from hgs_mst_city f where a.to=f.city_id) as city_name_to
                    FROM 
                        ebt_trs_sppd_destination a, 
                        ebt_mst_transportation_type b, 
                        itf_mst_status c, 
                        ebt_trs_sppd d, 
                        fin_trs_advance_money e 
                    WHERE 
                        a.transportation_id=b.transportation_id AND 
                        c.id=d.status AND 
                        a.sppd_id=d.sppd_id AND 
                        d.sppd_id=e.sppd_id AND 
                        d.sppd_id = '" . $sppd_model->sppd_id . "'";
            $_command = Yii::app()->db->createCommand($_sql);
            $_reader = $_command->queryRow();

            $dest_id = $_reader['dest_id'];
            $transportation_name = $_reader['transportation_name'];
            $status = $_reader['status'];
            $remark = $_reader['remark'];
            $meal_amount = (int) $_reader['meal_amount'];
            $hotel_amount = (int) $_reader['hotel_amount'];
            $allowance_amount = (int) $_reader['allowance_amount'];
            $others_amount = (int) $_reader['others'];
            $days = Yii::app()->globalFunction->getDeviationDateDays($_reader['departure_date'], $_reader['arrival_date']);
            $meal_allowance_amount = $meal_amount + $allowance_amount;
            $meal_allowance_amount_by_days = $meal_allowance_amount / ($days + 1);

            if ($days == 0) {
                $hotel_amount_by_days = 0;
            } else {
                $hotel_amount_by_days = $hotel_amount / $days;
            }

            $grand_total = $meal_allowance_amount + $hotel_amount + $others_amount;

            $statement_adv = "UPDATE fin_trs_advance_money SET grand_total = '" . $grand_total . "', status = '1' WHERE adv_mon_id = '" . $id . "'";
            $command_adv = Yii::app()->db->createCommand($statement_adv);
            $command_adv->execute();

            // FIRST APPROVER
            $sqlFA = "SELECT 
                        a.*, 
                        b.mail_address, 
                        b.full_name
                    FROM 
                        fin_trs_approval a
                    INNER JOIN hgs_mst_user b 
                        ON b.user_id=a.approver_id
                    WHERE 
                        a.doc_no = '" . $id . "' AND 
                        a.approver_flag = '1'";
            $commandFA = Yii::app()->db->createCommand($sqlFA);
            $readerFA = $commandFA->queryRow();

            $list_email = $readerFA['mail_address'];
            $_approver_hr = $readerFA['full_name'];

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
                        d.sppd_id = '" . $sppd_model->sppd_id . "'
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
                        d.sppd_id = '" . $sppd_model->sppd_id . "'
                    ORDER BY a.dest_id DESC
                    LIMIT 1";
            $_command = Yii::app()->db->createCommand($_sql);
            $_reader = $_command->queryRow();

            $arrival_date = $_reader['arrival_date'];
            $arrival_time = $_reader['arrival_time'];

            // ALL CITY DESTINATION
            $sqlAllDest = "SELECT 
                        (select d.city_name from hgs_mst_city d where a.from=d.city_id) as city_name_from,
                        (select e.city_name from hgs_mst_city e where a.to=e.city_id) as city_name_to
                    FROM 
                        ebt_trs_sppd_destination a, 
                        ebt_trs_sppd b
                    WHERE 
                        a.sppd_id = b.sppd_id AND
                        a.sppd_id = '" . $sppd_model->sppd_id . "'
                    ORDER BY a.dest_id ASC";
            $commandAllDest = Yii::app()->db->createCommand($sqlAllDest);
            $readerAllDest = $commandAllDest->queryAll();
            $city_destination = '';
            $totalDest = 0;
            foreach ($readerAllDest as $rowAllDest) {
                $totalDest++;
                if ($totalDest == 1) {
                    $city_destination = $rowAllDest['city_name_to'];
                } else {
                    $city_destination = $city_destination . " - " . $rowAllDest['city_name_to'];
                }
            }

            // "Eka.Nisa@hino.co.id",
            // "Yuanna.Fatmawati@hino.co.id",
            // "Meutya.Societa@hino.co.id",
            // "Internship.HC@hino.co.id",
            // "andito.lutfi@hino.co.id"

            $link_explode = explode('/', $_SERVER['REQUEST_URI']);
            $app_url = "https://" . $_SERVER['HTTP_HOST'] . "/" . $link_explode[1];

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://his-be-notification.hino.co.id/email-services/ebt-pum-approver',
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                    "email": [
                        "' . $list_email . '"
                    ],
                    "body": {
                        "approver_hr": "' . $_approver_hr . '", 
                        "adv_mon_id": "' . $adv_mon_model->adv_mon_id . '", 
                        "currency": "' . $adv_mon_model->currency_id . '", 
                        "emp_name": "' . $model_employee->emp_name . '",
                        "emp_no": "' . $model_employee->emp_no . '",
                        "division_id": "' . $sppd_model->division_id . '",
                        "city_destination": "' . $city_destination . '",
                        "departure_date": "' . $departure_date . '",
                        "departure_time": "' . $departure_time . '",
                        "arrival_date": "' . $arrival_date . '",
                        "arrival_time": "' . $arrival_time . '",
                        "status": "' . $status . '",
                        "transportation_name": "' . $transportation_name . '",
                        "meal_allowance_amount_by_days": "' . number_format($meal_allowance_amount_by_days) . '",
                        "hotel_amount_by_days": "' . number_format($hotel_amount_by_days) . '",
                        "days1": "' . ($days + 1) . '",
                        "days2": "' . ($days + 1) . '",
                        "days3": "' . ($days + 1) . '",
                        "days4": "' . $days . '",
                        "meal_allowance_amount": "' . number_format($meal_allowance_amount) . '",
                        "hotel_amount": "' . number_format($hotel_amount) . '",
                        "others_amount": "' . number_format($others_amount) . '",
                        "remark": "' . $remark . '",
                        "grand_total": "' . number_format($grand_total) . '",
                        "app_url": "' . $app_url . '"
                    }
                }',
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/json'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);

            $result = json_decode($response, true);
            if (isset($result['message']) and $result['message'] == 'email has been sent') {
                $statement_sppd = "UPDATE fin_trs_approval SET email_status = 1, email_response = '" . $result['message'] . "' WHERE id = '" . $readerFA['id'] . "'";
                $command_sppd = Yii::app()->db->createCommand($statement_sppd);
                $command_sppd->execute();
            } else {
                $statement_sppd = "UPDATE fin_trs_approval SET email_response = '" . $response . "' WHERE id = '" . $readerFA['id'] . "'";
                $command_sppd = Yii::app()->db->createCommand($statement_sppd);
                $command_sppd->execute();
            }

            $this->redirect(array('update', 'id' => $id));
        }
    }

    public function actionSendToOutstanding()
    {
        $_sql =
            "
        SELECT
            e.adv_mon_id,
            a.departure_time,
            a.arrival_time,
            d.departure_date,
            d.arrival_date,
            d.meal_amount,
            d.hotel_amount,
            d.allowance_amount,
            d.division_id,
            e.others,
            e.remark,
            f.full_name,
            f.user_id,
            f.mail_address,
            (
                SELECT
                    f.city_name
                FROM
                    hgs_mst_city f
                WHERE
                    a.`TO` = f.city_id
            ) AS city_name_to
        FROM
            ebt_trs_sppd_destination a,
            ebt_mst_transportation_type b,
            ebt_trs_sppd d,
            fin_trs_advance_money e,
            hgs_mst_user f
        WHERE
            a.transportation_id = b.transportation_id
        AND a.sppd_id = d.sppd_id
        AND d.sppd_id = e.sppd_id
        AND d.`status` = 2
        AND e.`status` = 2
        AND e.transfer_status = 0
        AND e.paid_status = 0
        AND e.export_status = 1
        AND d.emp_no=f.user_id
        AND d.arrival_date <= NOW() - INTERVAL 10 DAY
        ";

        $_command = Yii::app()->db->createCommand($_sql);
        $_reader = $_command->query();

        foreach ($_reader as $row) {
            $adv_mon_id = $row['adv_mon_id'];
            $emp_name = $row['full_name'];
            $emp_no = $row['user_id'];
            $mail_address = $row['mail_address'] . ',nancy.raisa@hino.co.id, yanti@hino.co.id, Mirfat@hino.co.id, desi.arisanti@hino.co.id, doris.heryanto@hino.co.id';
            $division_id = $row['division_id'];
            $departure_date = $row['departure_date'];
            $departure_time = $row['departure_time'];
            $arrival_date = $row['arrival_date'];
            $arrival_time = $row['arrival_time'];
            $remark = $row['remark'];
            $city_destination = $row['city_name_to'];
            $meal_amount = (int) $row['meal_amount'];
            $hotel_amount = (int) $row['hotel_amount'];
            $allowance_amount = (int) $row['allowance_amount'];
            $others_amount = (int) $row['others'];
            $days = Yii::app()->globalFunction->getDeviationDateDays($departure_date, $arrival_date);
            $meal_allowance_amount = $meal_amount + $allowance_amount;
            $meal_allowance_amount_by_days = $meal_allowance_amount / ($days + 1);

            if ($days == 0)
                $hotel_amount_by_days = 0;
            else
                $hotel_amount_by_days = $hotel_amount / $days;

            $grand_total = $meal_allowance_amount + $hotel_amount + $others_amount;

            $message = "<font face='Arial, Helvetica, sans-serif' style='font-size:16px'>Dear Bapak/Ibu <b>$emp_name</b>,<br/><br/>" .
                "Dengan ini kami informasikan bahwa pengajuan perjalanan dinas / penempatan sementara <b>Anda telah melewati 10 hari dari tanggal kepulangan dan belum ada penyelesaian. Mohon segera diselesaikan !</b> <br/><br/></font>" .
                "<style type='text/css'>
            table {
            color: #333; /* Lighten up font color */
            font-family: Helvetica, Arial, sans-serif; /* Nicer font */
            width: 640px;
            border-collapse:
            collapse; border-spacing: 0;
            }

            td, th { border: 1px solid #CCC; height: 30px; } /* Make cells a bit taller */

            th {
            background: #ADD8E6; /* Light grey background */
            font-weight: bold; /* Make sure they're bold */
            text-align: left;
            font-size: 10px;
            }

            td {
            background: #FAFAFA; /* Lighter grey background */
            text-align: left; /* Center our text */
            font-size: 10px;
            }

            td.first {
            background: #FAFAFA; /* Lighter grey background */
            text-align: center; /* Center our text */
            }

            /* Cells in even rows (2,4,6...) are one color */
            tr:nth-child(even) td { background: #F1F1F1; }  


            </style>
            <table>
            <tr>
                <th colspan=4>Rincian Perjalanan Dinas<br />
                <i>Detail of Business Trip</i></th>
            </tr>
            <tr>
                <td>PUM No</td>
                <td colspan=3>$adv_mon_id</td>
            </tr>
            <tr>
                <td>NIK / Nama Karyawan<br />
                <i>Employee ID / Employee Name</i>
            </td>
                <td colspan=3>$emp_name ($emp_no)</td>
            </tr>
            <tr>
                <td>Divisi/Dept.<br>
                <i>Division/Dept.</i></td>
                <td colspan=3>$division_id</td>
            </tr>
            <tr>
                <td>Tujuan<br>
                <i>Destination</i></td>
                <td colspan=3>$city_destination</td>
            </tr>
            <tr>
                <td>Jadwal Keberangkatan<br>
                <i>Departure Schedule</i>
            </td>
                <td colspan=3>$departure_date&nbsp;Jam:&nbsp;$departure_time</td>
            </tr>
            <tr>
                <td>Jadwal Kepulangan<br>
                <i>Arrival Schedule</i></td>
                <td colspan=3>$arrival_date&nbsp;Jam:&nbsp;$arrival_time</td>
            </tr>
            <tr>
                <th colspan=4>Rincian Pengajuan PUM<br />
                <i>Detail of Business Trip</i></th>
            </tr>
            <tr>
                <td>keterangan<br>
                <i>Remarks</i></td>
                <td class='first'>Biaya<br>
                <i>Cost</i></td>
                <td class='first'>Satuan<br>
                <i>Unit</i></td>
                <td class='first'>Total Biaya<br>
                <i>Total Cost</i></td>
            </tr>
            <tr>
                <td>Tunjangan Perjalanan Dinas<br>
                <i>Business Trip Allowance</i></td>
                <td class='first'>" . number_format($meal_allowance_amount_by_days) . "</td>
                <td class='first'>" . ($days + 1) . " hari</td>
                <td class='first'>" . number_format($meal_allowance_amount) . "</td>
            </tr>
            <tr>
                <td>Biaya Hotel<br>
                <i>Hotel Cost</i></td>
                <td class='first'>" . number_format($hotel_amount_by_days) . "</td>
                <td class='first'>" . ($days + 1) . " Hari (" . ($days + 1) . " Hari $days Malam)</td>
                <td class='first'>" . number_format($hotel_amount) . "</td>
            </tr>
            <tr>
                <td>Biaya Lainnya<br>
                <i>Other Cost</i></td>
                <td class='first'></td>
                <td class='first'></td>
                <td class='first'>" . number_format($others_amount) . "</td>
            </tr>
            <tr>
                <td>Keterangan Biaya Lainnya<br>
                <i>Remarks for Other Cost</i></td>
                <td colspan=3>$remark</td>
            </tr>              
            <tr>
                <td>Total Pengajuan uang Muka<br>
                <i>Total Advance Money</i></td>
                <td colspan=2></td>
                <td>" . number_format($grand_total) . "</td>
            </tr>
                    </table><br/>Untuk link EBTA bisa langsung klik : http://global.hino.co.id/his_uat/<hr>

            <font face='Arial, Helvetica, sans-serif' style='font-size:13px'><b><u>Keterangan:</u><br>Note</b>
                </font>
                <ol>
                    <li>
                        Pengajuan permintaan uang muka Anda telah disetujui oleh pihak terkait.<br>
                        <i>Please be informed that your advance money proposal have been approved by respective parties.</i>
                    </li>
                    <li>
                        Karyawan diwajibkan untuk melakukan proses pengembalian uang muka dilengkapi dengan dokumen pendukung ke FAD 
                        maksimal 10 hari kerja terhitung dari tanggal kepulangan perjlanan dinas.<br>
                        <i>Employee obligated to return advance money to FAD within 10 working days after arrival from business trip / temporary assignement.</i>
                    </li>
                </ol>  

                <font face='Arial, Helvetica, sans-serif' style='font-size:12px'>
                    Demikian informasi yang dapat kami sampaikan<br/>Terima kasih<br/><br/>
                    @Powered by HIS
                </font>";

            //Yii::app()->globalFunction->sendMail($mail_address,'[Outstanding] : Penyelesaian PUM',$message); 16 sept 2022
        }

        $this->redirect(array('index'));
    }

    //added by Doris on Aug 13, 2015 to get first approver by division and department
    private function getFirstApproverEbt($div_id, $dept_id)
    {
        $sql2 = "SELECT usr.mail_address FROM hgs_mst_approval app, hgs_mst_user usr WHERE app.user_id=usr.user_id AND 
            app.function_id = 'advance_money' AND app.division_id = '" . $div_id . "' AND app.dept_id = '" . $dept_id . "' 
            AND app.order_id=1 ORDER BY app.order_id ASC";
        $command2 = Yii::app()->db->createCommand($sql2);
        $reader2 = $command2->query();

        return $reader2;
    }

    //added by Doris on Aug 13, 2015 to get first approver by division and department
    private function getFirstApproverLevel4($div_id)
    {
        $sql2 = "SELECT usr.mail_address FROM hgs_mst_approval app, hgs_mst_user usr WHERE app.user_id=usr.user_id AND 
            app.function_id = 'business_travel' AND app.division_id = '" . $div_id . "' AND app.order_id=3 ORDER BY app.order_id ASC";
        $command2 = Yii::app()->db->createCommand($sql2);
        $reader2 = $command2->query();

        return $reader2;
    }

    //added by Doris Heryanto on Dec 2, 2016
    private function getFirstApproverBooker($div_id)
    {
        $sql2 = "SELECT 
                    usr.mail_address 
                FROM 
                    hgs_mst_approval app, 
                    hgs_mst_user usr 
                WHERE 
                    app.user_id=usr.user_id AND 
                    app.function_id = 'advance_money' AND 
                    app.division_id = '" . $div_id . "' AND 
                    app.order_id=3 
                ORDER BY 
                    app.order_id ASC";
        $command2 = Yii::app()->db->createCommand($sql2);
        $reader2 = $command2->query();

        return $reader2;
    }

    //added by Doris Heryanto on Dec 2, 2016
    private function getFirstApproverEbtDiv($div_id)
    {
        $sql2 = "SELECT usr.mail_address FROM hgs_mst_approval app, hgs_mst_user usr WHERE app.user_id=usr.user_id AND 
				app.function_id = 'advance_money' AND app.division_id = '" . $div_id . "' AND app.order_id=3 ORDER BY app.order_id ASC";
        $command2 = Yii::app()->db->createCommand($sql2);
        $reader2 = $command2->query();

        return $reader2;
    }

    private function getFirstApproverPUM($doc_no)
    {
        $sql2 = "SELECT 
                    usr.mail_address
                FROM
                    fin_trs_approval app,
                    hgs_mst_user usr
                WHERE
                    app.approver_id = usr.user_id
                    AND app.doc_no = '" . $doc_no . "'
                    AND app.order_approval = 1
                ORDER BY app.order_approval ASC";
        $command2 = Yii::app()->db->createCommand($sql2);
        $reader2 = $command2->query();

        return $reader2;
    }

    //added by Doris on Feb 15, 2016 to get second approver by division and department
    //update by Doris Heryanto on Dec 2, 2016
    private function getSecondApproverEbt($doc_no)
    {
        $sql2 = "SELECT 
                    usr.mail_address
                FROM
                    fin_trs_approval app,
                    hgs_mst_user usr
                WHERE
                    app.approver_id = usr.user_id
                    AND app.doc_no = '" . $doc_no . "'
                    AND app.order_approval = 2
                ORDER BY app.order_approval ASC";
        $command2 = Yii::app()->db->createCommand($sql2);
        $reader2 = $command2->query();

        return $reader2;
    }

    //added by Doris on Feb 15, 2016 to get second approver by division and department
    private function getHRApproverPUM()
    {
        $sql2 = "SELECT b.mail_address FROM hgs_mst_approval a, hgs_mst_user b
                WHERE a.user_id=b.user_id AND a.function_id='advance_money' AND a.division_id = 'HRD' 
                AND a.dept_id = 'HR-RQ' AND role_id='25'";

        $command2 = Yii::app()->db->createCommand($sql2);
        $reader2 = $command2->query();

        return $reader2;
    }

    //added by Doris on Feb 16, 2016 to get second approver by division and department
    private function getFADApproverPUM()
    {
        $sql2 = "SELECT b.mail_address FROM hgs_mst_approval a, hgs_mst_user b
                WHERE a.user_id=b.user_id AND a.function_id='advance_money' AND a.division_id = 'FAD' 
                AND a.dept_id = 'FIN' AND role_id='25'";

        $command2 = Yii::app()->db->createCommand($sql2);
        $reader2 = $command2->query();

        return $reader2;
    }

    // Fungsi actionApprove ini digunakan untuk melakukan proses permintaan persetujuan dokumen
    // @param string $id adalah nomer dokumen PUM yang akan diproses
    public function actionApprove($id)
    {
        $user_id = Yii::app()->user->id;
        $pum_model = $this->loadModel($id);
        $sppd_model = BusinessTravelModel::model()->findByPk($pum_model->sppd_id);
        $model_employee = EmployeeModel::model()->findByPk($sppd_model->emp_no);

        $link_explode = explode('/', $_SERVER['REQUEST_URI']);
        $app_url = "https://" . $_SERVER['HTTP_HOST'] . "/" . $link_explode[1];

        // VALIDASI CHECK SPPD APPROVER
        $cek_validasi_approval = "select approver_flag from ebt_trs_approval where doc_no = '" . $pum_model->sppd_id . "' and approver_id = '" . Yii::app()->user->id . "'";
        $command_cek_validasi_approval = Yii::app()->db->createCommand($cek_validasi_approval);
        $row_cek_validasi_approval = $command_cek_validasi_approval->queryRow();

        if ($row_cek_validasi_approval['approver_flag'] == 1) {

            Yii::app()->user->setFlash('success', 'Please approve SPPD Number <a href="' . $app_url . '/index.php?r=businesstravel/update&id=' . $pum_model->sppd_id . '"><b>' . $pum_model->sppd_id . '</b></a> first to approve this PUM Request.<br><i>Click the SPPD Number for check the SPPD.</i>');
            $this->redirect(array('update', 'id' => $id));
        } else {

            // CHECK IF APPROVAL DATE IS PAST TRIP END DATE
            $current_date = date('Y-m-d');
            $trip_end_date = $sppd_model->arrival_date;

            if ($current_date >= $trip_end_date) {
                $remark_message = "The application cannot be processed. Please submit a reimbursement request via HRIS website";
                $tanggal_kirim = date('c');
                $full_name = Yii::app()->globalFunction->get_user_name($user_id);
                $isi_pesan = "Auto Rejected - " . $remark_message;

                // 1. Insert Response
                $ins = "INSERT INTO fin_trs_advance_money_response (adv_mon_id, tanggal_kirim, nama_user, isi_pesan) 
                        VALUES ('" . $id . "', '" . $tanggal_kirim . "', '(" . $user_id . ") " . $full_name . "', '" . $isi_pesan . "')";
                Yii::app()->db->createCommand($ins)->execute();

                // 2. Update Approval Status to Rejected (3)
                $statement = "UPDATE fin_trs_approval 
                               SET approver_flag = '3', approver_date = '" . date('c') . "' 
                               WHERE doc_no = '" . $id . "' AND approver_id = '" . $user_id . "'";
                Yii::app()->db->createCommand($statement)->execute();

                // 3. Unlink PUM from SPPD
                $statement = "UPDATE ebt_trs_sppd SET advance_money = '0' WHERE sppd_id = '" . $pum_model->sppd_id . "'";
                Yii::app()->db->createCommand($statement)->execute();

                // 4. Update PUM Status to Rejected (3)
                $statement = "UPDATE fin_trs_advance_money SET status = '3', sppd_id = '' WHERE adv_mon_id = '" . $id . "'";
                Yii::app()->db->createCommand($statement)->execute();

                Yii::app()->user->setFlash('error', $remark_message);
                $this->redirect(array('update', 'id' => $id));
            }

            // INSERT RESPONSE IF EXIST
            if ($_REQUEST['response_text'] != '') {
                $tanggal_kirim = date('c');
                $isi_pesan = "Approved - " . $_REQUEST['response_text'];
                $ins = "INSERT INTO fin_trs_advance_money_response (adv_mon_id, tanggal_kirim, nama_user, isi_pesan) 
                        VALUES ('" . $id . "', '" . $tanggal_kirim . "', '(" . $user_id . ") " . $model_employee->emp_name . "', '" . $isi_pesan . "')";
                $comm_ins = Yii::app()->db->createCommand($ins);
                $comm_ins->execute();
            }

            // UPDATE EXISTING APPROVAL FLAG TO APPROVE
            $statement = "UPDATE fin_trs_approval 
                        SET approver_flag = '2', approver_date = '" . date('c') . "' 
                        WHERE doc_no = '" . $id . "' AND approver_id = '" . Yii::app()->user->id . "'";
            $command = Yii::app()->db->createCommand($statement);
            $command->execute();

            // GET DATA EXISTING APPROVAL
            $sql_first = "SELECT * FROM fin_trs_approval WHERE doc_no = '" . $id . "' AND approver_id = '" . $user_id . "'";
            $command_first = Yii::app()->db->createCommand($sql_first);
            $reader_first = $command_first->queryRow();

            $next_order_approval = $reader_first['order_approval'] + 1;

            $_sql = "SELECT a.departure_time, a.arrival_time, d.departure_date, d.arrival_date, b.transportation_name, c.status,
                d.meal_amount, d.hotel_amount, d.allowance_amount, e.others, e.remark, e.currency_id, f.emp_name,
                (select g.city_name from hgs_mst_city g where a.to=g.city_id) as city_name_to, g.mail_address
                FROM ebt_trs_sppd_destination a, ebt_mst_transportation_type b, itf_mst_status c, ebt_trs_sppd d, fin_trs_advance_money e,
                    hgs_mst_employee f, hgs_mst_user g
                WHERE a.transportation_id=b.transportation_id AND c.id=d.status AND a.sppd_id=d.sppd_id AND d.sppd_id=e.sppd_id AND 
                    d.emp_no=f.emp_no AND g.user_id=e.emp_no AND d.sppd_id = '" . $pum_model->sppd_id . "'";
            $_command = Yii::app()->db->createCommand($_sql);
            $_reader = $_command->queryAll();

            foreach ($_reader as $rows) {
                $currency = $rows['currency_id'];
                $emp_name = $rows['emp_name'];
                $transportation_name = $rows['transportation_name'];
                $status = $rows['status'];
                $remark = $rows['remark'];
                $employee_mail_address = $rows['mail_address'];
                $meal_amount = (int) $rows['meal_amount'];
                $hotel_amount = (int) $rows['hotel_amount'];
                $allowance_amount = (int) $rows['allowance_amount'];
                $others_amount = (int) $rows['others'];
                $days = Yii::app()->globalFunction->getDeviationDateDays($rows['departure_date'], $rows['arrival_date']);
                $meal_allowance_amount = $meal_amount + $allowance_amount;
                $meal_allowance_amount_by_days = $meal_allowance_amount / ($days + 1);

                if ($days == 0) {
                    $hotel_amount_by_days = 0;
                } else {
                    $hotel_amount_by_days = $hotel_amount / $days;
                }
                $grand_total = $meal_allowance_amount + $hotel_amount + $others_amount;
            }

            // CEK FULL APPROVED OR NOT
            $sql_count = "SELECT COUNT(approver_id) AS app_count FROM fin_trs_approval WHERE doc_no = '" . $id . "'";
            $command_count = Yii::app()->db->createCommand($sql_count);
            $reader_count = $command_count->queryRow();
            $app_count = $reader_count['app_count'];

            // APPROVER 1
            $sql_approver1 = "SELECT a.approver_date, b.full_name, b.mail_address FROM fin_trs_approval a, hgs_mst_user b 
                            WHERE a.approver_id = b.user_id AND a.doc_no = '" . $id . "' AND a.order_approval = '1' 
                            ORDER BY a.order_approval ASC";
            $command_approver1 = Yii::app()->db->createCommand($sql_approver1);
            $reader_approver1 = $command_approver1->queryRow();
            $approver_name_1 = isset($reader_approver1['full_name']) ? $reader_approver1['full_name'] : '';
            $approver_date_1 = isset($reader_approver1['approver_date']) ? $reader_approver1['approver_date'] : '';

            // APPROVER 2
            if ($app_count == 4) {
                $sql_approver2 = "SELECT a.approver_date, b.full_name, b.mail_address FROM fin_trs_approval a, hgs_mst_user b 
                                WHERE a.approver_id = b.user_id AND a.doc_no = '" . $id . "' AND a.order_approval = '2' 
                                ORDER BY a.order_approval ASC";
                $command_approver2 = Yii::app()->db->createCommand($sql_approver2);
                $reader_approver2 = $command_approver2->queryRow();
                $approver_name_2 = isset($reader_approver2['full_name']) ? $reader_approver2['full_name'] : '';
                $approver_date_2 = isset($reader_approver2['approver_date']) ? $reader_approver2['approver_date'] : '';
            } else {
                $approver_name_2 = '';
                $approver_date_2 = '';
            }

            // APPROVER HC
            if ($model_employee->division_id != 'GAD') {
                $sql_approverhc = "SELECT a.approver_date, b.full_name, b.mail_address FROM fin_trs_approval a, hgs_mst_user b 
                            WHERE a.approver_id = b.user_id AND a.doc_no = '" . $id . "' AND a.order_approval = '" . ($app_count - 1) . "' 
                            ORDER BY a.order_approval ASC";
                $command_approverhc = Yii::app()->db->createCommand($sql_approverhc);
                $reader_approverhc = $command_approverhc->queryRow();
                $approver_name_hc = isset($reader_approverhc['full_name']) ? $reader_approverhc['full_name'] : '';
                $approver_date_hc = isset($reader_approverhc['approver_date']) ? $reader_approverhc['approver_date'] : '';
            }

            // APPROVER FAD
            $sql_approverfad = "SELECT a.approver_date, b.full_name, b.mail_address FROM fin_trs_approval a, hgs_mst_user b 
                            WHERE a.approver_id = b.user_id AND a.doc_no = '" . $id . "' AND a.order_approval = '" . $app_count . "' 
                            ORDER BY a.order_approval ASC";
            $command_approverfad = Yii::app()->db->createCommand($sql_approverfad);
            $reader_approverfad = $command_approverfad->queryRow();
            $approver_name_fad = isset($reader_approverfad['full_name']) ? $reader_approverfad['full_name'] : '';
            $approver_date_fad = isset($reader_approverfad['approver_date']) ? $reader_approverfad['approver_date'] : '';

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
                        d.sppd_id = '" . $pum_model->sppd_id . "'
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
                        d.sppd_id = '" . $pum_model->sppd_id . "'
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
                        a.sppd_id = '" . $pum_model->sppd_id . "'
                    ORDER BY a.dest_id ASC";
            $commandAllDest = Yii::app()->db->createCommand($sqlAllDest);
            $readerAllDest = $commandAllDest->queryAll();
            $city_destination = '';
            $totalDest = 0;
            foreach ($readerAllDest as $rowAllDest) {
                $totalDest++;
                if ($totalDest == 1) {
                    $city_destination = $rowAllDest['city_name_to'];
                } else {
                    $city_destination = $city_destination . " - " . $rowAllDest['city_name_to'];
                }
            }

            $sql_cek_count = "SELECT COUNT(*) FROM fin_trs_approval WHERE doc_no = '" . $id . "' AND order_approval = '" . $next_order_approval . "'";
            $count = Yii::app()->db->createCommand($sql_cek_count)->queryScalar();

            if ($count > 0) {

                // UPDATE NEXT APPROVAL FLAG TO ON PROGRESS
                $statement = "UPDATE fin_trs_approval SET approver_flag = '1' WHERE doc_no = '" . $id . "' AND order_approval = '" . $next_order_approval . "'";
                $command = Yii::app()->db->createCommand($statement);
                $command->execute();

                // GET DATA DETAIL PUM FOR EMAIL
                $sql_app = "SELECT b.full_name, b.mail_address FROM fin_trs_approval a, hgs_mst_user b 
                            WHERE a.approver_id = b.user_id 
                            AND a.doc_no = '" . $id . "' 
                            AND a.order_approval = '" . $next_order_approval . "'";
                $command_app = Yii::app()->db->createCommand($sql_app);
                $reader_app = $command_app->queryRow();
                // die($sql_app);
                $next_approver_name = $reader_app['full_name'];
                $next_approver_email = strtolower($reader_app['mail_address']);

                // "Eka.Nisa@hino.co.id",
                // "Yuanna.Fatmawati@hino.co.id",
                // "Meutya.Societa@hino.co.id",
                // "Internship.HC@hino.co.id",
                // "andito.lutfi@hino.co.id"

                $message = "<font face='Arial' style='font-size:12px'>Dear Bapak/Ibu <b>$next_approver_name</b>,<br/><br/>" .
                    "Kami informasikan karyawan yang mengajukan uang muka perjalanan dinas dengan rincian sebagai berikut : <br/><br/>" .
                    "Hereby we inform you, employess's detail who proposed advance money for business trip activities as follow : <br/><br/></font>" .
                    "<style type='text/css'>
                        table {
                        color: #333; /* Lighten up font color */
                        font-family: Arial; /* Nicer font */
                        width: 640px;
                        border-collapse:
                        collapse; border-spacing: 0;
                        }
                        
                        td, th { border: 1px solid #CCC; height: 30px; } /* Make cells a bit taller */
                        
                        th {
                        background: #ADD8E6; /* Light grey background */
                        font-weight: bold; /* Make sure they're bold */
                        text-align: left;
                        font-size: 12px;
                        }
                        
                        td {
                        background: #FAFAFA; /* Lighter grey background */
                        text-align: left; /* Center our text */
                        font-size: 12px;
                        }
                        
                        td.first {
                        background: #FAFAFA; /* Lighter grey background */
                        text-align: center; /* Center our text */
                        }
                        
                        /* Cells in even rows (2,4,6...) are one color */
                        tr:nth-child(even) td { background: #F1F1F1; }  
                        
                        
                        </style>
                        <table>
                        <tr>
                            <th colspan=4>Rincian Perjalanan Dinas<br />
                            <i>Detail of Business Trip</i></th>
                        </tr>
                        <tr>
                            <td>PUM No</td>
                            <td colspan=3>$pum_model->adv_mon_id</td>
                        </tr>
                        <tr>
                            <td>NIK / Nama Karyawan<br />
                            <i>Employee ID / Employee Name</i>
                        </td>
                            <td colspan=3>$model_employee->emp_name ($model_employee->emp_no)</td>
                        </tr>
                        <tr>
                            <td>Divisi/Dept.<br>
                            <i>Division/Dept.</i></td>
                            <td colspan=3>$sppd_model->division_id</td>
                        </tr>
                        <tr>
                            <td>Tujuan<br>
                            <i>Destination</i></td>
                            <td colspan=3>$city_destination</td>
                        </tr>
                        <tr>
                            <td>Jadwal Keberangkatan<br>
                            <i>Departure Schedule</i>
                        </td>
                            <td colspan=3>$departure_date&nbsp;Jam:&nbsp;$departure_time</td>
                        </tr>
                        <tr>
                            <td>Jadwal Kepulangan<br>
                            <i>Arrival Schedule</i></td>
                            <td colspan=3>$arrival_date&nbsp;Jam:&nbsp;$arrival_time</td>
                        </tr>
                        <tr>
                            <td>Status Pengajuan<br>
                            <i>Approval Status</i>
                            <td colspan=3>$status</td>
                        </tr>
                        <tr>
                            <td>Moda Transportasi<br>
                            <i>Transportation Mode</i>
                        </td>
                            <td colspan=3>$transportation_name</td>
                        </tr>
                        <tr>
                            <td>Hotel<br>
                            <i>Hotel</i></td>
                            <td colspan=3></td>
                        </tr>
                        <tr>
                            <th colspan=4>Rincian Pengajuan PUM<br />
                            <i>Detail of Business Trip</i></th>
                        </tr>
                        <tr>
                            <td>Keterangan<br>
                            <i>Remarks</i></td>
                            <td class='first'>Biaya<br>
                            <i>Cost</i></td>
                            <td class='first'>Satuan<br>
                            <i>Unit</i></td>
                            <td class='first'>Total Biaya<br>
                            <i>Total Cost</i></td>
                        </tr>
                        <tr>
                            <td>Tunjangan Perjalanan Dinas<br>
                            <i>Business Trip Allowance</i></td>
                            <td class='first'>" . $currency . " " . number_format($meal_allowance_amount_by_days) . "</td>
                            <td class='first'>" . ($days + 1) . " hari</td>
                            <td class='first'>" . $currency . " " . number_format($meal_allowance_amount) . "</td>
                        </tr>
                        <tr>
                            <td>Biaya Hotel<br>
                            <i>Hotel Cost</i></td>
                            <td class='first'>" . $currency . " " . number_format($hotel_amount_by_days) . "</td>
                            <td class='first'>" . ($days + 1) . " Hari $days Malam</td>
                            <td class='first'>" . $currency . " " . number_format($hotel_amount) . "</td>
                        </tr>
                        <tr>
                            <td>Biaya Lainnya<br>
                            <i>Other Cost</i></td>
                            <td class='first'></td>
                            <td class='first'></td>
                            <td class='first'>" . $currency . " " . number_format($others_amount) . "</td>
                        </tr>
                        <tr>
                            <td>Keterangan Biaya Lainnya<br>
                            <i>Remarks for Other Cost</i></td>
                            <td colspan=3>$remark</td>
                        </tr>              
                        <tr>
                            <td>Total Pengajuan uang Muka<br>
                            <i>Total Advance Money</i></td>
                            <td colspan='2'></td>
                            <td class='first'>" . $currency . " " . number_format($grand_total) . "</td>
                        </tr>
                	</table>
                	<br/>
                	
                	<font face='Arial' style='font-size:12px'>
                    Silahkan klik link di bawah ini untuk mengakses Aplikasi E-Business Trip<br/> 
                    <i>Please click the link below to access E-Business Trip Application</i><br/>
                    <a href='" . $app_url . "'>E-Business Trip</a>
                    </font><br>
                	<br>
                          
                    <font face='Arial' style='font-size:12px'>
                    Demikian informasi yang dapat kami sampaikan<br/>
                    <i>That is the information we can provide</i><br/>
                    <br/>
                    Terima kasih<br/>
                    <i>Thank you</i><br/>
                    <br/>
                    @Powered by HIS
                    </font>";

                $encoded = base64_encode($message);

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://his-be-notification.hino.co.id/email-services/ebt-pum-approved',
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => '{
                        "email": [
                            "' . $next_approver_email . '"
                        ],
                        "body": {
                            "adv_mon_id": "' . $pum_model->adv_mon_id . '",
                            "message": "' . $encoded . '"
                        }
                    }',
                    CURLOPT_HTTPHEADER => array(
                        'Accept: application/json',
                        'Content-Type: application/json'
                    )
                ));

                $response = curl_exec($curl);

                curl_close($curl);
                // echo $response;

                $result = json_decode($response, true);
                if (isset($result['message']) and $result['message'] == 'email has been sent') {
                    $statement_sppd = "UPDATE fin_trs_approval SET email_status = 1, email_response = '" . $result['message'] . "' 
                                        WHERE doc_no = '" . $id . "' AND order_approval = '" . $next_order_approval . "'";
                    $command_sppd = Yii::app()->db->createCommand($statement_sppd);
                    $command_sppd->execute();
                } else {
                    $statement_sppd = "UPDATE fin_trs_approval SET email_response = '" . $response . "' 
                                        WHERE doc_no = '" . $id . "' AND order_approval = '" . $next_order_approval . "'";
                    $command_sppd = Yii::app()->db->createCommand($statement_sppd);
                    $command_sppd->execute();
                }
            }

            // IF FULL APPROVED
            $sql_app = "SELECT COUNT(approver_id) AS app_count FROM fin_trs_approval WHERE doc_no = '" . $id . "' AND approver_flag = '2'";
            $command_app = Yii::app()->db->createCommand($sql_app);
            $reader_app = $command_app->queryRow();
            $app_order = $reader_app['app_count'];

            if ($app_count == $app_order) {

                // UPDATE PUM STATUS TO APPROVED
                $statement = "UPDATE fin_trs_advance_money SET status = '2' WHERE adv_mon_id = '" . $id . "'";
                $command = Yii::app()->db->createCommand($statement);
                $command->execute();

                // GET EMAIL USER THAT CREATE PUM
                $sql_creator = "SELECT mail_address FROM hgs_mst_user WHERE user_id = '" . $model_employee->emp_no . "'";
                $command_creator = Yii::app()->db->createCommand($sql_creator);
                $reader_creator = $command_creator->queryRow();
                $creator_mail = strtolower($reader_creator['mail_address']);

                // GET UPDATE DATA PUM STATUS
                $sql_after_update = "SELECT b.status FROM fin_trs_advance_money a 
                    INNER JOIN itf_mst_status b ON b.id = a.status
                    WHERE a.adv_mon_id = '" . $id . "'";
                $command_after_update = Yii::app()->db->createCommand($sql_after_update);
                $reader_after_update = $command_after_update->queryRow();

                $status_after_update = $reader_after_update['status'];

                // "Eka.Nisa@hino.co.id",
                // "Yuanna.Fatmawati@hino.co.id",
                // "Meutya.Societa@hino.co.id",
                // "Internship.HC@hino.co.id",
                // "andito.lutfi@hino.co.id"

                $message = "<font face='Arial' style='font-size:12px'>Dear Bapak/Ibu <b>" . $model_employee->emp_name . "</b>,<br/><br/>" .
                    "Dengan ini kami informasikan bahwa pengajuan perjalanan dinas / penempatan sementara Anda telah disetujui oleh pihak terkait: <br/><br/>" .
                    "Please be informed that your business trip / temporary stay proposal have been approved by respective parties : <br/><br/></font>" .
                    "<style type='text/css'>
                        table {
                        color: #333; 
                        font-family: Arial; 
                        width: 640px;
                        border-collapse:
                        collapse; border-spacing: 0;
                        }
                        
                        td, th { border: 1px solid #CCC; height: 30px; } 
                        
                        th {
                        background: #ADD8E6;
                        font-weight: bold;
                        text-align: left;
                        font-size: 12px;
                        }
                        
                        td {
                        background: #FAFAFA;
                        text-align: left;
                        font-size: 12px;
                        }
                        
                        td.first {
                        background: #FAFAFA;
                        text-align: center;
                        }

                        tr:nth-child(even) td { background: #F1F1F1; }
                        </style>
                        <table>
                        <tr>
                            <th colspan=4>Rincian Perjalanan Dinas<br />
                            <i>Detail of Business Trip</i></th>
                        </tr>
                        <tr>
                            <td>PUM No</td>
                            <td colspan=3>$pum_model->adv_mon_id</td>
                        </tr>
                        <tr>
                            <td>NIK / Nama Karyawan<br />
                            <i>Employee ID / Employee Name</i>
                        </td>
                            <td colspan=3>$model_employee->emp_name ($model_employee->emp_no)</td>
                        </tr>
                        <tr>
                            <td>Divisi/Dept.<br>
                            <i>Division/Dept.</i></td>
                            <td colspan=3>$sppd_model->division_id</td>
                        </tr>
                        <tr>
                            <td>Tujuan<br>
                            <i>Destination</i></td>
                            <td colspan=3>$city_destination</td>
                        </tr>
                        <tr>
                            <td>Awal Perjalanan Dinas<br>
                            <i>Start Business Trip</i>
                        </td>
                            <td colspan=3>$departure_date&nbsp;Jam:&nbsp;$departure_time</td>
                        </tr>
                        <tr>
                            <td>Akhir Perjalanan Dinas<br>
                            <i>End Business Trip</i></td>
                            <td colspan=3>$arrival_date&nbsp;Jam:&nbsp;$arrival_time</td>
                        </tr>
                        <tr>
                            <td>Status Pengajuan<br>
                            <i>Approval Status</i>
                            <td colspan=3>$status</td>
                        </tr>
                        <tr>
                            <td>Moda Transportasi<br>
                            <i>Transportation Mode</i>
                        </td>
                            <td colspan=3>$transportation_name</td>
                        </tr>
                        <tr>
                            <td>Hotel<br>
                            <i>Hotel</i></td>
                            <td colspan=3></td>
                        </tr>
                        <tr>
                            <td>Diinstruksikan Oleh<br><i>Instructed by</i></td>
                            <td colspan=3>
                            ($approver_name_1)&nbsp;&nbsp;&nbsp;&nbsp;($approver_date_1)<br />
                            ($approver_name_2)&nbsp;&nbsp;&nbsp;&nbsp;($approver_date_2)
                            </td>
                        </tr>
                        <tr>
                            <td>Diketahui Oleh<br><i>Acknowledge by</i></td>
                            <td colspan=3>
                            ($approver_name_hc)&nbsp;&nbsp;&nbsp;&nbsp;($approver_date_hc)<br />
                            ($approver_name_fad)&nbsp;&nbsp;&nbsp;&nbsp;($approver_date_fad)
                            </td>
                        </tr>
                        <tr>
                            <th colspan=4>Rincian Pengajuan PUM<br />
                            <i>Detail of Business Trip</i></th>
                        </tr>
                        <tr>
                            <td>Keterangan<br>
                            <i>Remarks</i></td>
                            <td class='first'>Biaya<br>
                            <i>Cost</i></td>
                            <td class='first'>Satuan<br>
                            <i>Unit</i></td>
                            <td class='first'>Total Biaya<br>
                            <i>Total Cost</i></td>
                        </tr>
                        <tr>
                            <td>Tunjangan Perjalanan Dinas<br>
                            <i>Business Trip Allowance</i></td>
                            <td class='first'>" . $currency . " " . number_format($meal_allowance_amount_by_days) . "</td>
                            <td class='first'>" . ($days + 1) . " hari</td>
                            <td class='first'>" . $currency . " " . number_format($meal_allowance_amount) . "</td>
                        </tr>
                        <tr>
                            <td>Biaya Hotel<br>
                            <i>Hotel Cost</i></td>
                            <td class='first'>" . $currency . " " . number_format($hotel_amount_by_days) . "</td>
                            <td class='first'>" . ($days + 1) . " Hari $days Malam</td>
                            <td class='first'>" . $currency . " " . number_format($hotel_amount) . "</td>
                        </tr>
                        <tr>
                            <td>Biaya Lainnya<br>
                            <i>Other Cost</i></td>
                            <td class='first'></td>
                            <td class='first'></td>
                            <td class='first'>" . $currency . " " . number_format($others_amount) . "</td>
                        </tr>
                        <tr>
                            <td>Keterangan Biaya Lainnya<br>
                            <i>Remarks for Other Cost</i></td>
                            <td colspan=3>$remark</td>
                        </tr>              
                        <tr>
                            <td>Total Pengajuan uang Muka<br>
                            <i>Total Advance Money</i></td>
                            <td colspan='2'></td>
                            <td class='first'>" . $currency . " " . number_format($grand_total) . "</td>
                        </tr>
                	</table>
                	<br/>
                	
                	<font face='Arial' style='font-size:12px'>
                    Silahkan klik link di bawah ini untuk mengakses Aplikasi E-Business Trip<br/> 
                    <i>Please click the link below to access E-Business Trip Application</i><br/>
                    <a href='" . $app_url . "'>E-Business Trip</a>
                    </font><br>
                	<br>
                	
                	<font face='Arial' style='font-size:12px'>
                    <b><u>Keterangan:</u><br><i>Note</i></b>
                    <ol>
                        <li>
                            Pengajuan permintaan uang muka Anda telah disetujui oleh pihak terkait.<br>
                            <i>Please be informed that your advance money proposal have been approved by respective parties.</i>
                        </li>
                        <li>
                            Karyawan diwajibkan untuk melakukan proses pengembalian uang muka dilengkapi dengan dokumen pendukung ke FAD maksimal 10 hari kerja terhitung dari tanggal kepulangan perjalanan dinas.<br>
                            <i>Employee obligated to return advance money to FAD within 10 working days after arrival from business trip / temporary assignement.</i>
                        </li>
                    </ol>
                    </font>
                    <br>
                          
                    <font face='Arial' style='font-size:12px'>
                    Demikian informasi yang dapat kami sampaikan<br/>
                    <i>That is the information we can provide</i><br/>
                    <br/>
                    Terima kasih<br/>
                    <i>Thank you</i><br/>
                    <br/>
                    @Powered by HIS
                    </font>";

                // echo $message;

                $encoded = base64_encode($message);
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://his-be-notification.hino.co.id/email-services/ebt-pum-approved',
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => '{
                        "email": [
                            "' . $creator_mail . '"
                        ],
                        "body": {
                            "adv_mon_id": "' . $pum_model->adv_mon_id . '",
                            "message": "' . $encoded . '"
                        }
                    }',
                    CURLOPT_HTTPHEADER => array(
                        'Accept: application/json',
                        'Content-Type: application/json'
                    ),
                ));
                $response = curl_exec($curl);
                curl_close($curl);

                $result = json_decode($response, true);
                if (isset($result['message']) and $result['message'] == 'email has been sent') {
                    $statement_sppd = "UPDATE fin_trs_advance_money SET email_status = 1, email_response = '" . $result['message'] . "' 
                                        WHERE adv_mon_id = '" . $id . "'";
                    $command_sppd = Yii::app()->db->createCommand($statement_sppd);
                    $command_sppd->execute();
                } else {
                    $statement_sppd = "UPDATE fin_trs_advance_money SET email_response = '" . $response . "' 
                                        WHERE adv_mon_id = '" . $id . "'";
                    $command_sppd = Yii::app()->db->createCommand($statement_sppd);
                    $command_sppd->execute();
                }
            }
        }

        // $this->redirect(array('site/ListPumApproval','id'=>$id));
        $this->redirect(array('update', 'id' => $id));
    }

    private function getEmployee($emp_no)
    {
        $sql2 = "SELECT usr.mail_address FROM hgs_mst_user usr WHERE user_id = '" . $emp_no . "'";
        $command2 = Yii::app()->db->createCommand($sql2);
        $reader2 = $command2->query();

        return $reader2;
    }

    public function actionRequestPum()
    {
        $sppd_id = $_REQUEST['sppd_id'];
        $statement = "SELECT * FROM ebt_trs_sppd WHERE sppd_id ='" . $sppd_id . "'";
        $command = Yii::app()->db->createCommand($statement);
        $get_sppd_info = $command->queryRow();

        $now = date('c');
        $doc_date = date('Y-m-d');
        $user = $get_sppd_info['emp_no'];
        $doc_no = $this->setDocumentNo($user);
        $serial_no = $this->setRecordNo($user);

        // CHECK IF REQUEST DATE IS PAST TRIP END DATE
        $trip_end_date = $get_sppd_info['arrival_date'];
        $current_date = date('Y-m-d');

        if ($current_date >= $trip_end_date) {
            Yii::app()->user->setFlash('success', '<b>The application cannot be processed. Please submit a reimbursement request via HRIS website</b>');
            $this->redirect(array('businesstravel/update', 'id' => $sppd_id));
        }

        // Cek SPPD ALL Approved
        // $sppd_approval_count = "select count(*) as total_data from ebt_trs_approval where doc_no = '" . $sppd_id . "'";
        // $command_sppd_approval_count = Yii::app()->db->createCommand($sppd_approval_count);
        // $row_sppd_approval_count = $command_sppd_approval_count->queryRow();

        // $sppd_approved_count = "select count(*) as total_data from ebt_trs_approval where doc_no = '" . $sppd_id . "' and approver_flag = '2'";
        // $command_sppd_approved_count = Yii::app()->db->createCommand($sppd_approved_count);
        // $row_sppd_approved_count = $command_sppd_approved_count->queryRow();

        // if($row_sppd_approval_count['total_data'] != $row_sppd_approved_count['total_data']){

        //     Yii::app()->user->setFlash('success', "<b>Can't progress Request PUM because SPPD (".$sppd_id.") has not been fully approved yet.</b>");
        //     $this->redirect(array('businesstravel/update','id'=>$sppd_id));

        // }else{

        $total_amount = $get_sppd_info['meal_amount'] + $get_sppd_info['allowance_amount'] + $get_sppd_info['hotel_amount'];

        $count = AdvanceMoneyModel::model()->countByAttributes(array(
            'emp_no' => $get_sppd_info['emp_no'],
            'paid_status' => 0,
            'status' => 2,
            //'transfer_status' => 1
        ));

        if ($count >= 2) {
            Yii::app()->user->setFlash('success', '<b>Sorry, At this time you cannot apply for a cash advance. Please complete the Outstanding immediately.</b>');
            $this->redirect(array('businesstravel/update', 'id' => $sppd_id));
        } else {
            if ($get_sppd_info['trip_id'] == '5' or $get_sppd_info['trip_id'] == '7') {
                $curr_id = 'USD';
            } else {
                $curr_id = 'IDR';
            }
            // Proses pembuatan permintaan tiket berdasarkan data sppd yang sudah diambil
            $statement = "INSERT INTO fin_trs_advance_money (
                    adv_mon_id,
                    adv_mon_date,
                    currency_id,
                    division_id,
                    dept_id,
                    emp_no,
                    towards,
                    amount,
                    adv_prepare,
                    created_date,
                    created_by,
                    modified_date,
                    modified_by,
                    status,
                    serial_no,
                    sppd_id
                ) VALUES (
                    :doc_no,
                    :doc_date,
                    :curr_id,
                    :division_id,
                    :dept_id,
                    :emp_no,
                    :purpose,
                    :amount,
                    'Money',
                    :created_date,
                    :created_by,
                    :modified_date,
                    :modified_by,
                    '0',
                    :serial_no,
                    :sppd_id
                )";

            $command = Yii::app()->db->createCommand($statement);
            $command->bindParam(":doc_no", $doc_no);
            $command->bindParam(":doc_date", $doc_date);
            $command->bindParam(":curr_id", $curr_id);
            $command->bindParam(":division_id", $get_sppd_info['division_id']);
            $command->bindParam(":dept_id", Yii::app()->globalFunction->get_dept_emp($get_sppd_info['emp_no']));
            $command->bindParam(":emp_no", $get_sppd_info['emp_no']);
            $command->bindParam(":purpose", $get_sppd_info['purpose']);
            $command->bindParam(":amount", $total_amount);
            $command->bindParam(":created_date", $now);
            $command->bindParam(":created_by", $user);
            $command->bindParam(":modified_date", $now);
            $command->bindParam(":modified_by", $user);
            $command->bindParam(":serial_no", $serial_no);
            $command->bindParam(":sppd_id", $sppd_id);

            $command->execute();

            // Update flag permintaan uang muka pada data sppd, menandakan bahwa data sppd sudah
            // pernah mengajukan permintaan uang muka secara langsung dari layar dokumen sppd 

            $statement = "UPDATE ebt_trs_sppd SET advance_money = '1' where sppd_id='" . $sppd_id . "'";
            $command = Yii::app()->db->createCommand($statement);
            $command->execute();
        }

        // Memindahkan layar yang dilihat oleh user 
        $this->redirect(array('update', 'id' => $doc_no));
        // }
    }

    // Function actionReject ini digunakan melakukan reject pada suatu dokumen
    // @param $id adalah nomer dokumen yang akan direject / dibatalkan 
    // @return dokumen yang sudah direject akan berubah statusnya menjadi (3)
    public function actionReject()
    {

        $tanggal_kirim = date('c');
        $nama_user = Yii::app()->user->id;
        $id = $_POST['id'];
        $isi_pesan = "Reject - " . $_POST['response_text'];

        $adv_mon_model = $this->loadModel($id);
        $sppd_model = BusinessTravelModel::model()->findByPk($adv_mon_model->sppd_id);

        $full_name = Yii::app()->globalFunction->get_user_name($nama_user);

        $ins = "INSERT INTO fin_trs_advance_money_response (adv_mon_id, tanggal_kirim, nama_user, isi_pesan) 
                VALUES ('" . $id . "', '" . $tanggal_kirim . "', '(" . $nama_user . ") " . $full_name . "', '" . $isi_pesan . "')";
        $comm_ins = Yii::app()->db->createCommand($ins);
        $comm_ins->execute();

        // $list_email = 'doris.heryanto@hino.co.id';
        $statement = "UPDATE fin_trs_approval SET approver_flag = '3', approver_date = '" . date('c') . "' WHERE doc_no = '" . $id . "' AND approver_id = '" . Yii::app()->user->id . "'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        // NEW LOGIC - PUM Reject : Unlink PUM SPPD
        $statement = "UPDATE ebt_trs_sppd SET advance_money = '0' WHERE sppd_id = '" . $adv_mon_model->sppd_id . "'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        // NEW LOGIC - PUM Reject : Unlink PUM SPPD
        $statement = "UPDATE fin_trs_advance_money SET status = '3', sppd_id = '' WHERE adv_mon_id = '" . $id . "'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        //added by doris start on Tue, Feb 10, 2016
        //added by doris heryanto to get user approval ~~~~start
        $_sql = "SELECT a.departure_time, a.arrival_time, d.departure_date, d.arrival_date, b.transportation_name, c.status,
            d.meal_amount, d.hotel_amount, d.allowance_amount, e.others, e.remark, f.emp_name,
            (select g.city_name from hgs_mst_city g where a.to=g.city_id) as city_name_to, g.mail_address,f.emp_no
            FROM ebt_trs_sppd_destination a, ebt_mst_transportation_type b, itf_mst_status c, ebt_trs_sppd d, fin_trs_advance_money e,
                hgs_mst_employee f, hgs_mst_user g
            WHERE a.transportation_id=b.transportation_id AND c.id=e.status AND a.sppd_id=d.sppd_id AND d.sppd_id=e.sppd_id AND 
                d.emp_no=f.emp_no AND g.user_id=e.emp_no AND d.sppd_id = '" . $sppd_model->sppd_id . "'";

        //SELECT b.emp_name FROM ebt_trs_approval a, hgs_mst_employee b WHERE a.appover_id=b.emp_no AND order_approval=1 AND doc_no = ";
        $_command = Yii::app()->db->createCommand($_sql);
        $_reader = $_command->queryAll();

        foreach ($_reader as $rows) {
            $emp_name = $rows['emp_name'];
            $emp_no = $rows['emp_no'];
            $departure_date = $rows['departure_date'];
            $departure_time = $rows['departure_time'];
            $arrival_date = $rows['arrival_date'];
            $arrival_time = $rows['arrival_time'];
            $transportation_name = $rows['transportation_name'];
            $status = $rows['status'];
            $remark = $rows['remark'];
            $city_destination = $rows['city_name_to'];
            $employee_mail_address = $rows['mail_address'];
            $meal_amount = (int) $rows['meal_amount'];
            $hotel_amount = (int) $rows['hotel_amount'];
            $allowance_amount = (int) $rows['allowance_amount'];
            $others_amount = (int) $rows['others'];
            $days = Yii::app()->globalFunction->getDeviationDateDays($departure_date, $arrival_date);
            $meal_allowance_amount = $meal_amount + $allowance_amount;
            $meal_allowance_amount_by_days = $meal_allowance_amount / ($days + 1);

            if ($days == 0)
                $hotel_amount_by_days = 0;
            else
                $hotel_amount_by_days = $hotel_amount / $days;

            $grand_total = $meal_allowance_amount + $hotel_amount + $others_amount;
        }
        //~~~~~ finish
        //finish

        // $message = "<font face='Arial, Helvetica, sans-serif' style='font-size:12px'>Dear Bapak/Ibu <b>$emp_name</b>,<br/><br/>" .
        //     "Dengan ini kami informasikan bahwa pengajuan perjalanan dinas / penempatan sementara Anda telah ditolak oleh pihak terkait: <br/><br/>" .
        //     "Please be informed that your business trip / temporary stay proposal have been rejected by respective parties : <br/><br/></font>" .
        //     "<style type='text/css'>
        //             table {
        //             color: #333; /* Lighten up font color */
        //             font-family: Helvetica, Arial, sans-serif; /* Nicer font */
        //             width: 640px;
        //             border-collapse:
        //             collapse; border-spacing: 0;
        //             }
                    
        //             td, th { border: 1px solid #CCC; height: 30px; } /* Make cells a bit taller */
                    
        //             th {
        //             background: #ADD8E6; /* Light grey background */
        //             font-weight: bold; /* Make sure they're bold */
        //             text-align: left;
        //             font-size: 10px;
        //             }
                    
        //             td {
        //             background: #FAFAFA; /* Lighter grey background */
        //             text-align: left; /* Center our text */
        //             }
                    
        //             td.first {
        //             background: #FAFAFA; /* Lighter grey background */
        //             text-align: center; /* Center our text */
        //             }
                    
        //             /* Cells in even rows (2,4,6...) are one color */
        //             tr:nth-child(even) td { background: #F1F1F1; }  
                    
                    
        //             </style>
        //             <table>
        //             <tr>
        //                 <th colspan=4>Rincian Perjalanan Dinas<br />
        //                 <i>Detail of Business Trip</i></th>
        //             </tr>
        //             <tr>
        //                 <td>SPPD No</td>
        //                 <td colspan=3>$sppd_model->sppd_id</td>
        //             </tr>
        //             <tr>
        //                 <td>NIK / Nama Karyawan<br />
        //                 <i>Employee ID / Employee Name</i>
        //             </td>
        //                 <td colspan=3>$emp_name ($emp_no)</td>
        //             </tr>
        //             <tr>
        //                 <td>Divisi/Dept.<br>
        //                 <i>Division/Dept.</i></td>
        //                 <td colspan=3>$sppd_model->division_id</td>
        //             </tr>
        //             <tr>
        //                 <td>Tujuan<br>
        //                 <i>Destination</i></td>
        //                 <td colspan=3>$city_destination</td>
        //             </tr>
        //             <tr>
        //                 <td>Jadwal Keberangkatan<br>
        //                 <i>Departure Schedule</i>
        //             </td>
        //                 <td colspan=3>$departure_date&nbsp;Jam:&nbsp;$departure_time</td>
        //             </tr>
        //             <tr>
        //                 <td>Jadwal Kepulangan<br>
        //                 <i>Arrival Schedule</i></td>
        //                 <td colspan=3>$arrival_date&nbsp;Jam:&nbsp;$arrival_time</td>
        //             </tr>
        //             <tr>
        //                 <td>Status Pengajuan<br>
        //                 <i>Approval Status</i>
        //                 <td colspan=3>$status</td>
        //             </tr>
        //             <tr>
        //                 <td>Moda Transportasi<br>
        //                 <i>Transportation Mode</i>
        //             </td>
        //                 <td colspan=3>$transportation_name</td>
        //             </tr>
        //             <tr>
        //                 <td>Hotel<br>
        //                 <i>Hotel</i></td>
        //                 <td colspan=3></td>
        //             </tr>
        //             <tr>
        //                 <td>Diinstruksikan Oleh<br>
        //                 <i>Instructed by</i>
        //             </td>
        //                 <td colspan=3>($approver_name_1)&nbsp;&nbsp;&nbsp;&nbsp;($approver_date_1)<br />
        //                 ($approver_name_2)&nbsp;&nbsp;&nbsp;&nbsp;($approver_date_2)</td>
                        
        //             </tr>
        //             <tr>
        //                 <th colspan=4>Rincian Pengajuan PUM<br />
        //                 <i>Detail of Business Trip</i></th>
        //             </tr>
        //             <tr>
        //                 <td>keterangan<br>
        //                 <i>Remarks</i></td>
        //                 <td class='first'>Biaya<br>
        //                 <i>Cost</i></td>
        //                 <td class='first'>Satuan<br>
        //                 <i>Unit</i></td>
        //                 <td class='first'>Total Biaya<br>
        //                 <i>Total Cost</i></td>
        //             </tr>
        //             <tr>
        //                 <td>Tunjangan Perjalanan Dinas<br>
        //                 <i>Business Trip Allowance</i></td>
        //                 <td class='first'>" . number_format($meal_allowance_amount_by_days) . "</td>
        //                 <td class='first'>" . ($days + 1) . " hari</td>
        //                 <td class='first'>" . number_format($meal_allowance_amount) . "</td>
        //             </tr>
        //             <tr>
        //                 <td>Biaya Hotel<br>
        //                 <i>Hotel Cost</i></td>
        //                 <td class='first'>" . number_format($hotel_amount_by_days) . "</td>
        //                 <td class='first'>" . ($days + 1) . " Hari (" . ($days + 1) . " Hari $days Malam)</td>
        //                 <td class='first'>" . number_format($hotel_amount) . "</td>
        //             </tr>
        //             <tr>
        //                 <td>Biaya Lainnya<br>
        //                 <i>Other Cost</i></td>
        //                 <td class='first'></td>
        //                 <td class='first'></td>
        //                 <td class='first'>" . number_format($others_amount) . "</td>
        //             </tr>
        //             <tr>
        //                 <td>Keterangan Biaya Lainnya<br>
        //                 <i>Remarks for Other Cost</i></td>
        //                 <td colspan=3>$remark</td>
        //             </tr>              
        //             <tr>
        //                 <td>Total Pengajuan uang Muka<br>
        //                 <i>Total Advance Money</i></td>
        //                 <td colspan=2></td>
        //                 <td>" . number_format($grand_total) . "</td>
        //             </tr>
        //             <tr>
        //                 <td>Status Proses Permintaan Uang Muka<br>
        //                 <i>Advance Money Process Status</i>
        //             </td>
        //                 <td colspan=3>($approver_name_hr)&nbsp;&nbsp;&nbsp;&nbsp;($approver_date_hr)<br />
        //                 ($approver_name_fad)&nbsp;&nbsp;&nbsp;&nbsp;($approver_date_fad)</td>
        //             </tr>
        //     	</table><br/><hr>  

        //         <font face='Arial, Helvetica, sans-serif' style='font-size:13px'><b><u>Keterangan:</u><br>Note</b>
        //         </font>
        //         <ol>
        //             <li>
        //                 Pengajuan permintaan uang muka Anda telah ditolak oleh pihak terkait.<br>
        //                 <i>Please be informed that your advance money proposal have been rejected by respective parties.</i>
        //             </li>
        //             <li>
        //                 Karyawan diwajibkan untuk melakukan proses pengembalian uang muka dilengkapi dengan dokumen pendukung ke FAD 
        //                 maksimal 10 hari kerja terhitung dari tanggal kepulangan perjlanan dinas.<br>
        //                 <i>Employee obligated to return advance money to FAD within 10 working days after arrival from business trip / temporary assignement.</i>
        //             </li>
        //         </ol>  

        //         <font face='Arial, Helvetica, sans-serif' style='font-size:12px'>
        //             Demikian informasi yang dapat kami sampaikan<br/>Terima kasih<br/><br/>
        //             @Powered by HIS
        //         </font>";

        //Yii::app()->globalFunction->sendMail($list_email,'[Testing dari 99] : Pengajuan PUM',$message);//finish copas by doris  31 Juli 2015
        //Yii::app()->globalFunction->sendMail($employee_mail_address,'[Jangan Dibalas] : Pengajuan PUM',$message);disable 13 sept 2022

        // $this->redirect(array('update','id'=>$id));
        return $id;
    }

    // Function actionPaid ini dieksekusi ketika karyawan melakukan pembayaran/penggantian uang muka
    // @param $id adalah nomer dokumen yang akan diproses 
    // @return dokumen yang sudah diproses akan berubah status paid nya menjadi (1)
    public function actionPaid($id)
    {
        $statement = "UPDATE fin_trs_advance_money SET paid_status = '1' WHERE adv_mon_id = '" . $id . "'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        $model = $this->loadModel($id);

        $command1 = Yii::app()->db->createCommand("INSERT INTO fin_trs_advance_money_response(
                                                    adv_mon_id,
                                                    tanggal_kirim,
                                                    nama_user,
                                                    isi_pesan,
                                                    status_read,
                                                    sent_to
                                                ) VALUES ( 
                                                    '" . $id . "',
                                                    '" . date('c') . "',
                                                    '" . Yii::app()->user->id . "',
                                                    'Permintaan uang muka anda telah berhasil kami transfer, silahkan periksa di rekening bank anda',
                                                    '0',
                                                    '" . $model->created_by . "'
                                                )");

        $command1->execute();

        $this->redirect(array('update', 'id' => $id));
    }

    public function mailsend($to, $from, $from_name, $subject, $message, $cc = array(), $attachment = array())
    {
        $mail = Yii::app()->Smtpmail;
        $mail->SetFrom($from, $from_name);
        $mail->Subject = $subject;
        $mail->MsgHTML($message);
        $mail->AddAddress($to, "");
        $mail->AddAttachment($attachment);
        $mail->Send();
    }

    // Function actionTransfer ini dieksekusi ketika staff finansial sudah melakukan transfer uang muka ke rekening karyawan
    // @param $id adalah nomer dokumen yang akan di proses
    // @return dokumen yang sudah diproses akan berubah status transfer nya menjadi (1)
    public function actionTransfer($id)
    {
        /* get employee information start*/
        //added by Doris Heryanto on Mar 1, 2016
        $sql_to_get_emp = "
            SELECT d.mail_address, b.emp_name, b.emp_no, b.nomor_rekening, a.amount, a.others, c.filename 
            FROM fin_trs_advance_money a, hgs_mst_employee b, fin_trs_advance_money_file_dokumen c, hgs_mst_user d
            WHERE a.adv_mon_id=c.adv_mon_id AND a.emp_no=b.emp_no AND d.user_id=b.emp_no AND a.adv_mon_id='" . $id . "'";
        $command_to_get_emp = Yii::app()->db->createCommand($sql_to_get_emp);
        $reader_to_get_emp = $command_to_get_emp->queryRow();

        $emp_no = $reader_to_get_emp['emp_no'];
        $emp_name = $reader_to_get_emp['emp_name'];
        $norek = $reader_to_get_emp['nomor_rekening'];
        $amount = $reader_to_get_emp['amount'];
        $others = $reader_to_get_emp['others'];
        $filename = $reader_to_get_emp['filename'];
        $mail_address = $reader_to_get_emp['mail_address'];

        $grand_total = $amount + $others;
        $list_email2 = 'doris.heryanto@hino.co.id,taofik@hino.co.id';

        /* get employee information finish*/

        //added by doris on 31 juli 2015 start
        $message = "<font face='Arial, Helvetica, sans-serif' style='font-size:12px'>Dear Bapak/Ibu $emp_name,<br/><br/>" .
            "Dengan ini kami informasikan bahwa Finance sudah melakukan transfer PUM kepada : <br/><br/>" .
            "<style type='text/css'>
              table {
              color: #333; /* Lighten up font color */
              font-family: Helvetica, Arial, sans-serif; /* Nicer font */
              width: 640px;
              border-collapse:
              collapse; border-spacing: 0;
              }
              
              td, th { border: 1px solid #CCC; height: 30px; } /* Make cells a bit taller */
              
              th {
              background: #ADD8E6; /* Light grey background */
              font-weight: bold; /* Make sure they're bold */
              text-align: left;
              font-size: 10px;
              }
              
              td {
              background: #FAFAFA; /* Lighter grey background */
              text-align: left; /* Center our text */
              font-size: 10px;
              }
              
              td.first {
              background: #FAFAFA; /* Lighter grey background */
              text-align: center; /* Center our text */
              }
              
              /* Cells in even rows (2,4,6...) are one color */
              tr:nth-child(even) td { background: #F1F1F1; }  
              </style>
            
            <table border='1' cellpadding='0' cellspacing='0' width='800'>
                    <tr bgcolor='#ECE9D8' height='30'>
                        <th align='left' width='800' colspan='4'>
                            <font face='Arial, Helvetica, sans-serif' style='font-size:12px'>
                                Rincian Bukti Pengiriman PUM
                            </font>
                        </th>
                    </tr>
                    
                    <tr bgcolor='#ECE9D8' height='30'>
                        <td align='left' width='400'>
                            <font face='Arial, Helvetica, sans-serif' style='font-size:12px'>
                                NIK / Nama Karyawan
                            </font>
                        </td>
                        <td align='left' width='600' colspan='3'>
                            <font face='Arial, Helvetica, sans-serif' style='font-size:12px'>
                                &nbsp;$emp_no / $emp_name
                            </font>
                        </td>
                    </tr>
                    <tr bgcolor='#ECE9D8' height='30'>
                        <td align='left' width='400'>
                            <font face='Arial, Helvetica, sans-serif' style='font-size:12px'>
                                Nomor Rekening
                            </font>
                        </td>
                        <td align='left' width='600' colspan='3'>
                            <font face='Arial, Helvetica, sans-serif' style='font-size:12px'>
                                &nbsp;$norek
                            </font>
                        </td>
                    </tr>
                    <tr bgcolor='#ECE9D8' height='30'>
                        <td align='left' width='400'>
                            <font face='Arial, Helvetica, sans-serif' style='font-size:12px'>
                                Jumlah Transfer
                            </font>
                        </td>
                        <td align='left' width='600' colspan='3'>
                            <font face='Arial, Helvetica, sans-serif' style='font-size:12px'>
                                &nbsp;$grand_total
                            </font>
                        </td>
                    </tr>
                    <tr bgcolor='#ECE9D8' height='30'>
                        <td align='left' width='400'>
                            <font face='Arial, Helvetica, sans-serif' style='font-size:12px'>
                                Note
                            </font>
                        </td>
                        <td align='left' width='600' colspan='3'>
                            <font face='Arial, Helvetica, sans-serif' style='font-size:12px'>
                                &nbsp;-
                            </font>
                        </td>
                    </tr>
            </table><br/>Untuk link EBTA bisa langsung klik : http://global.hino.co.id/his_uat/<hr>
            <font face='Arial, Helvetica, sans-serif' style='font-size:12px'>
                Kami lampirkan juga bukti transfer (pembayaran) PUM.
            </font>
            <br><hr>
            <font face='Arial, Helvetica, sans-serif' style='font-size:12px'>
                Demikian informasi yang dapat kami sampaikan<br/>Terima kasih<br/><br/>
                @Powered by HIS
            </font>
            ";
        //$to = 'susana.wijaya@hino.co.id';
        $cc = 'eka.nisa@hino.co.id';
        $subject = '[Jangan dibalas] : Pengajuan PUM';
        //$attachment = Yii::app()->basePath."/attachment/advance_money/$filename";
        //Yii::app()->globalFunction->mailsend($mail_address,$from,$from_name,$subject,$message,$cc,$attachment);16 sept 2022
        //Yii::app()->globalFunction->sendMail($list_email2,'[Jangan dibalas] : Pengajuan PUM',$message);//finish copas by doris  31 Juli 2015

        $statement = "UPDATE fin_trs_advance_money SET transfer_status = '1' WHERE adv_mon_id = '" . $id . "'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        $this->redirect(array('update', 'id' => $id));
    }

    // Fungsi actionSendResponse ini digunakan untuk melakukan proses pengiriman pesan ke approver
    // @param string $id adalah nomer dokumen SPPD yang akan diproses
    // @return mengirimkan informasi bahwa teks response sudah berhasil terkirim
    public function actionSendResponse()
    {

        $tanggal_kirim = date('c');
        $nama_user = Yii::app()->user->id;
        $adv_mon_id = $_POST['adv_mon_id'];
        $isi_pesan = $_POST['response_text'];
        $date_now = date('Y-m-d');

        $model = $this->loadModel($adv_mon_id);

        if ($model->created_by == $nama_user) {

            $statement = "SELECT * FROM fin_trs_approval WHERE approver_flag = 1 AND doc_no = '" . $adv_mon_id . "'";
            $command = Yii::app()->db->createCommand($statement);
            $readers = $command->queryRow();
            $receipt = $readers['approver_id'];
        } else {

            $receipt = $model->created_by;
        }

        /*
         $statement = "SELECT * FROM fin_trs_approval WHERE approver_flag = 1 AND doc_no = '".$adv_mon_id."'";
         $command = Yii::app()->db->createCommand($statement);
         $readers = $command->queryRow();
         $receipt = $readers['approver_id'];
        */

        $command1 = Yii::app()->db->createCommand("INSERT INTO fin_trs_advance_money_response(
                                                        adv_mon_id,
                                                        tanggal_kirim,
                                                        nama_user,
                                                        isi_pesan,
                                                        status_read,
                                                        sent_to
                                                    ) VALUES ( 
                                                        '" . $adv_mon_id . "',
                                                        '" . $tanggal_kirim . "',
                                                        '" . $nama_user . "',
                                                        '" . $isi_pesan . "',
                                                        '0',
                                                        '" . $receipt . "'
                                                    )");

        $command1->execute();
    }

    // Fungsi actionSendBack ini digunakan untuk melakukan proses pengiriman balik dokumen
    // @param string $id adalah nomer dokumen SPPD yang akan diproses
    // @return status dokumen akan berubah menjadi draft kembali
    public function actionSendBack()
    {

        $tanggal_kirim = date('c');
        $nama_user = Yii::app()->user->id;
        $adv_mon_id = $_POST['id'];
        $isi_pesan = "Revise - " . $_POST['response_text'];

        $adv_mon_model = $this->loadModel($adv_mon_id);
        $full_name = Yii::app()->globalFunction->get_user_name($nama_user);

        $ins = "INSERT INTO fin_trs_advance_money_response (adv_mon_id, tanggal_kirim, nama_user, isi_pesan) 
                VALUES ('" . $adv_mon_id . "', '" . $tanggal_kirim . "', '(" . $nama_user . ") " . $full_name . "', '" . $isi_pesan . "')";
        $comm_ins = Yii::app()->db->createCommand($ins);
        $comm_ins->execute();

        $statement = "DELETE FROM fin_trs_approval WHERE doc_no = '" . $adv_mon_id . "'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        $statement = "UPDATE fin_trs_advance_money SET status = '0' WHERE adv_mon_id = '" . $adv_mon_id . "'";
        $command = Yii::app()->db->createCommand($statement);
        $command->execute();

        $this->redirect(array('update', 'id' => $adv_mon_id));
    }

    private function setDocumentNo($emp_no = '')
    {
        $userid = ($emp_no == '') ? Yii::app()->user->id : $emp_no;
        $_user = UsersModel::model()->find('user_id=?', array($userid));
        $_prefix_division = EmployeeModel::model()->find('emp_no=?', array($userid));

        $_sql = "SELECT * FROM fin_trs_advance_money WHERE division_id = '" . $_prefix_division->division_id . "' AND adv_mon_date LIKE '" . date('Y-m') . "%' ORDER BY serial_no DESC LIMIT 0,1";
        $_command = Yii::app()->db->createCommand($_sql);
        $_reader = $_command->queryAll();
        $_temp = '';

        foreach ($_reader as $rows) {
            $_temp = (int) $rows['serial_no'];
        }

        $_temp = $_temp + 1;
        $_doc_no = 'PUM/' . $_prefix_division->division_id . '/' . date('m') . '/' . date('Y') . '/' . sprintf("%03d", $_temp);

        return $_doc_no;
    }

    private function setRecordNo($emp_no = '')
    {
        $userid = ($emp_no == '') ? Yii::app()->user->id : $emp_no;
        $_user = UsersModel::model()->find('user_id=?', array($userid));
        $_prefix_division = EmployeeModel::model()->find('emp_no=?', array($userid));

        $_sql = "SELECT * FROM fin_trs_advance_money WHERE division_id = '" . $_prefix_division->division_id . "' AND adv_mon_date LIKE '" . date('Y-m') . "%' ORDER BY serial_no DESC LIMIT 0,1";
        $_command = Yii::app()->db->createCommand($_sql);
        $_reader = $_command->queryAll();
        $_temp = '';

        foreach ($_reader as $rows) {
            $_temp = (int) $rows['serial_no'];
        }

        $_temp = $_temp + 1;

        return $_temp;
    }

    public function isAllowed($user, $access)
    {

        //print_r(Yii::app()->user->id);
        //die;
        $_user = UsersModel::model()->find('user_id=?', array(Yii::app()->user->id));
        $_access = AccessModel::model()->find('role_id=:role_id AND access_id = :access_id', array(':role_id' => $_user->role_id, 'access_id' => 'advanced_money'));

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
        } elseif ($access == 'approve') {

            $_allowed = $_access->approve;
        } else {

            $_allowed = false;
        }

        return $_allowed;
    }
}
