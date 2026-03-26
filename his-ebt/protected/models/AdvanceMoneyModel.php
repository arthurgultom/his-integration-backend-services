<?php

/**
 * This is the model class for table "fin_trs_advance_money".
 *
 * The followings are the available columns in table 'fin_trs_advance_money':
 * @property integer $adv_mon_id
 * @property string $adv_mon_date
 * @property string $emp_no
 * @property string $currency_id
 * @property string $division_id
 * @property string $towards
 * @property string $on_date
 * @property string $dept_id
 * @property integer $amount
 * @property integer $others
 * @property integer $remark
 * @property string $created_date
 * @property string $created_by
 * @property string $modified_date
 * @property string $modified_by
 * @property integer $status
 * @property integer $serial_no
 * @property integer $paid_status
 * @property integer $transfer_status
 */
class AdvanceMoneyModel extends CActiveRecord
{

	public $search_status;
    public $search_emp;
    public $search_emp_name;
	public $search_city;
    public $search_departure_date;
    public $search_arrival_date;
	public $search_trip;
    private static $_divisionMap = null;

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'fin_trs_advance_money';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
            array('budget_code', 'required'),
			array('amount, status, serial_no, paid_status, others, grand_total, transfer_status, export_status', 'numerical', 'integerOnly'=>true),
			array('adv_mon_id, emp_no, division_id, dept_id, budget_code', 'length', 'max'=>20),
			array('created_by, modified_by, created_date, modified_date', 'length', 'max'=>50),
			array('bank_acc, nomor_rekening','length','max'=>30),
			array('nama_rekening', 'length', 'max'=>100),
			array('currency_id', 'length', 'max'=>10),
			array('adv_mon_date, towards, on_date', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('remark, adv_mon_id, adv_mon_date, emp_no, currency_id, budget_code, towards, others, grand_total, on_date, division_id, dept_id, search_status, search_emp_name, amount, created_by, created_date, modified_date, modified_by, serial_no, status, paid_status, export_status, transfer_status, nomor_rekening', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'rel_status' => array(self::BELONGS_TO, 'StatusModel', 'status'),
            'rel_emp' => array(self::BELONGS_TO, 'EmployeeModel', 'emp_no'),
            'rel_sppd' => array(self::BELONGS_TO, 'BusinessTravelModel', 'sppd_id'),
            //'rel_fin_pum' => array(self::BELONGS_TO, 'AdvanceMoneyApprovalModel', 'doc_no'),
            'rel_fin_pum'=> array(self::BELONGS_TO, 'AdvanceMoneyApprovalModel','doc_no','foreignKey'=>array('adv_mon_id'=>'doc_no')),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'adv_mon_id' => 'PUM ID',
			'adv_mon_date' => 'Document Date',
			'emp_no' => 'NIK',
			'currency_id' => 'Currency',
			'towards' => 'Towards',
			'on_date' => 'Please Prepare On',
			'division_id' => 'Division ID',
			'dept_id' => 'Dept ID',
			'amount' => 'Amount',
			'others' => 'Others',
			'remark' => 'Remark',
			'bank_acc' => 'Bank Account',
			'created_by' => 'Created By',
			'created_date' => 'Created Date',
			'modified_by' => 'Modified By',
			'modified_date' => 'Modified Date',
			'serial_no'=> 'Serial Number',
			'status' => 'Status',
			'search_status' => 'Status',
            'search_emp_name' => 'Employee Name',
			'paid_status' => 'Paid Status',
			'transfer_status' => 'Transfer Status',
            'sppd_id' => 'SPPD Request',
            'nomor_rekening' => 'BCA Account Number',
			'nama_rekening' => 'BCA Account Name',
            'grand_total'=> 'Grand Total',
            'budget_code' => 'Budget Code'
		);
	}

	//added by doris heryanto on Dec 5, 2017
	public function getStatus()
	{
		if ($this->export_status == 1)
		{
			return 'Sudah di export';
		} else {
			return 'Belum di export';
		}

        }

        //added by doris heryanto on Dec 5, 2017
	public function getStatusSettle()
	{
		if ($this->paid_status == 1 && $this->transfer_status == 1)
		{
			return 'Sudah di Settle';
		} else {
			return 'Belum di Settle';
		}

        }

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search($approver_id)
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

        $approver_flag = array('1','2');

		$criteria->with = array('rel_status', 'rel_fin_pum', 'rel_emp','rel_sppd');

		//glory 23 jan 2017 - set order by newest date
        //$criteria->order= 't.adv_mon_id DESC';
		//$criteria->order= 't.status, t.adv_mon_date DESC';
		$criteria->order= 'rel_sppd.departure_date DESC, t.adv_mon_id DESC';
		//finish

		$criteria->compare('adv_mon_id',$this->adv_mon_id);
		$criteria->compare('adv_mon_date',$this->adv_mon_date,true);
		$criteria->compare('t.emp_no',$this->emp_no,true);
		$criteria->compare('currency_id',$this->currency_id,true);
		$criteria->compare('towards',$this->towards,true);
		$criteria->compare('on_date',$this->on_date,true);
        // $criteria->compare('rel_fin_pum.approver_flag',$approver_flag,true);
        // $criteria->compare('rel_fin_pum.approver_id',$approver_id,true);

		// to filter the user division whether FAD or not
		// for FAD Approver the whole transaction will be showed
		// else the data will be showed only selected division of users

		// $division_filter = Yii::app()->globalFunction->get_division_emp(Yii::app()->user->id);

		/*if($division_filter == 'FAD'){

			$criteria->compare('division_id',$this->division_id,true);

		}else{

			$criteria->compare('division_id',$division_filter,true);

		}*/

        //glory 24 November 2016 - untuk keperluan filter dan search by emp name
		//start
		$criteria->compare('rel_emp.emp_name', $this->search_emp_name, true);
		//end

		$criteria->compare('division_id',$this->division_id,true);
		$criteria->compare('dept_id',$this->dept_id,true);
		$criteria->compare('amount',$this->amount);
		$criteria->compare('others',$this->others);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('bank_acc',$this->bank_acc,true);
		$criteria->compare('created_date',$this->created_date,true);
		$criteria->compare('created_by',$this->created_by,true);
		$criteria->compare('modified_date',$this->modified_date,true);
		$criteria->compare('modified_by',$this->modified_by,true);
		$criteria->compare('serial_no',$this->serial_no,true);
		$criteria->compare('t.status',$this->status,true);
		$criteria->compare('paid_status',$this->paid_status,true);
		$criteria->compare('transfer_status',$this->transfer_status,true);
		$criteria->compare('rel_status.status', $this->search_status);



		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'sort'=>array(
                //'defaultOrder'=>'CUSTOMER_NAME',
                'attributes'=>array(
                    'search_status'=>array(
                        'asc'=>'rel_status.status ASC',
                        'desc'=>'rel_status.status DESC',
                    ),
                    '*',
                ),
            )
		));
	}

    public function getTransfer()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

        $approver_flag = array('2');
        $status = 2;
		//$criteria->with = array('rel_status', 'rel_fin_pum');
		$criteria->with = array('rel_emp');


		//glory 23 jan 2017 - set order by newest date
        //$criteria->order= 't.adv_mon_id DESC';
		$criteria->order= 't.adv_mon_date DESC';
		//finish

		$criteria->compare('adv_mon_id',$this->adv_mon_id);
		$criteria->compare('adv_mon_date',$this->adv_mon_date,true);
		$criteria->compare('emp_no',$this->emp_no,true);
		$criteria->compare('rel_emp.emp_name', $this->search_emp_name, true);
		$criteria->compare('currency_id',$this->currency_id,true);
		$criteria->compare('towards',$this->towards,true);
		$criteria->compare('on_date',$this->on_date,true);
        //$criteria->compare('rel_fin_pum.approver_flag',$approver_flag,true);


		// to filter the user division whether FAD or not
		// for FAD Approver the whole transaction will be showed
		// else the data will be showed only selected division of users

		$division_filter = Yii::app()->globalFunction->get_division_emp(Yii::app()->user->id);

		/*if($division_filter == 'FAD'){

			$criteria->compare('division_id',$this->division_id,true);

		}else{

			$criteria->compare('division_id',$division_filter,true);

		}*/

		$criteria->compare('division_id',$this->division_id,true);
		$criteria->compare('dept_id',$this->dept_id,true);
		$criteria->compare('amount',$this->amount);
		$criteria->compare('others',$this->others);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('bank_acc',$this->bank_acc,true);
		$criteria->compare('created_date',$this->created_date,true);
		$criteria->compare('created_by',$this->created_by,true);
		$criteria->compare('modified_date',$this->modified_date,true);
		$criteria->compare('modified_by',$this->modified_by,true);
		$criteria->compare('serial_no',$this->serial_no,true);
		$criteria->compare('t.status',$status,true);
		$criteria->compare('paid_status',$this->paid_status,true);
		$criteria->compare('transfer_status',$this->transfer_status,true);
		$criteria->compare('rel_status.status', $this->search_status);



		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'sort'=>array(
                //'defaultOrder'=>'CUSTOMER_NAME',
                'attributes'=>array(
                    'search_status'=>array(
                        'asc'=>'rel_status.status ASC',
                        'desc'=>'rel_status.status DESC',
                    ),
                    '*',
                ),
            )
		));
	}

    public function searchByAdmin()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

        $approver_flag = array('1','2');

		//glo - 08 juni 2017 - untuk keperluan filter by employee name
		$criteria->with = array('rel_emp','rel_status');

		//$criteria->with = array('rel_status', 'rel_fin_pum');

        //$criteria->order= 't. DESC';
        $criteria->order= 't.adv_mon_date DESC';
		$criteria->compare('adv_mon_id',$this->adv_mon_id);
		$criteria->compare('adv_mon_date',$this->adv_mon_date,true);
		$criteria->compare('t.emp_no',$this->emp_no,true);
		$criteria->compare('currency_id',$this->currency_id,true);
		$criteria->compare('towards',$this->towards,true);
		$criteria->compare('on_date',$this->on_date,true);
        //$criteria->compare('rel_fin_pum.approver_flag',$approver_flag,true);
        //$criteria->compare('rel_fin_pum.approver_id',$approver_id,true);

		// to filter the user division whether FAD or not
		// for FAD Approver the whole transaction will be showed
		// else the data will be showed only selected division of users

		$division_filter = Yii::app()->globalFunction->get_division_emp(Yii::app()->user->id);

		/*if($division_filter == 'FAD'){

			$criteria->compare('division_id',$this->division_id,true);

		}else{

			$criteria->compare('division_id',$division_filter,true);

		}*/

		$criteria->compare('division_id',$this->division_id,true);
		$criteria->compare('t.dept_id',$this->dept_id,true);
		$criteria->compare('amount',$this->amount);
		$criteria->compare('others',$this->others);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('bank_acc',$this->bank_acc,true);
		$criteria->compare('created_date',$this->created_date,true);
		$criteria->compare('created_by',$this->created_by,true);
		$criteria->compare('modified_date',$this->modified_date,true);
		$criteria->compare('modified_by',$this->modified_by,true);
		$criteria->compare('serial_no',$this->serial_no,true);
		$criteria->compare('t.status',$this->status,true);
		$criteria->compare('paid_status',$this->paid_status,true);
		$criteria->compare('transfer_status',$this->transfer_status,true);
		$criteria->compare('rel_status.status', $this->search_status);

		//glory 08 juni 2017 - untuk keperluan filter dan search by emp name
		//start
		$criteria->compare('rel_emp.emp_name', $this->search_emp_name, true);
		//end


		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'sort'=>array(
                //'defaultOrder'=>'CUSTOMER_NAME',
                'attributes'=>array(
                    'search_status'=>array(
                        'asc'=>'rel_status.status ASC',
                        'desc'=>'rel_status.status DESC',
                    ),
                    '*',
                ),
            )
		));
	}

    public function searchexport()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;
        $export_status = 0;
		$criteria->with = array('rel_status');
        $criteria->order= 't.adv_mon_id DESC';
		$criteria->compare('adv_mon_id',$this->adv_mon_id);
		$criteria->compare('adv_mon_date',$this->adv_mon_date,true);
		$criteria->compare('emp_no',$this->emp_no,true);
		$criteria->compare('currency_id',$this->currency_id,true);
		$criteria->compare('towards',$this->towards,true);
		$criteria->compare('on_date',$this->on_date,true);
        $criteria->compare('export_status',$export_status,true);

		// to filter the user division whether FAD or not
		// for FAD Approver the whole transaction will be showed
		// else the data will be showed only selected division of users

		//$division_filter = Yii::app()->globalFunction->get_division_emp(Yii::app()->user->id);

		/*if($division_filter == 'FAD'){

			$criteria->compare('division_id',$this->division_id,true);

		}else{

			$criteria->compare('division_id',$division_filter,true);

		}*/

		$criteria->compare('division_id',$this->division_id,true);
		$criteria->compare('dept_id',$this->dept_id,true);
		$criteria->compare('amount',$this->amount);
		$criteria->compare('others',$this->others);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('bank_acc',$this->bank_acc,true);
		$criteria->compare('created_date',$this->created_date,true);
		$criteria->compare('created_by',$this->created_by,true);
		$criteria->compare('modified_date',$this->modified_date,true);
		$criteria->compare('modified_by',$this->modified_by,true);
		$criteria->compare('serial_no',$this->serial_no,true);
		$criteria->compare('status',$this->status,true);
		$criteria->compare('paid_status',$this->paid_status,true);
		$criteria->compare('transfer_status',$this->transfer_status,true);
		$criteria->compare('rel_status.status', $this->search_status);


		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'sort'=>array(
                //'defaultOrder'=>'CUSTOMER_NAME',
                'attributes'=>array(
                    'search_status'=>array(
                        'asc'=>'rel_status.status ASC',
                        'desc'=>'rel_status.status DESC',
                    ),
                    '*',
                ),
            )
		));
	}

    public function searchByDivision2($division_id, $dept_id, $level, $position_id, $emp_no)
	{
            // @todo Please modify the following code to remove attributes that should not be searched.

            $criteria=new CDbCriteria;
            $approver_flag = array(1,2);
            $criteria->together = true;
            //glory 24 nov 2016 untuk keperluan filter
			$criteria->with = array('rel_emp','rel_fin_pum');

            //glory 23 jan 2017 - set order by newest date
            //$criteria->order= 't.adv_mon_id DESC';
            $criteria->order= 't.status, t.adv_mon_date DESC';
            //finish glory

            if ($division_id == 'JATAKE') {
                $div = array('ASD','DDT','STD','SPL','MDB','BPB');
                $level = array(2,3,4,5);
				//$criteria->compare('t.emp_no',Yii::app()->user->id,true);
            } else {
				if ($level == 2) {
					//$div = $division_id;
                    $dept = explode(" ", $dept_id);
					$div = explode(" ", $division_id);
					$level_id = array(2,3,4,5,6);
					//$criteria->compare('t.emp_no',Yii::app()->user->id,true);
				} elseif ($level == 3) {
                                        $dept = explode(" ", $dept_id);
					//$div = $division_id;
					$div = explode(" ", $division_id);
					$level_id = array(3,4,5,6);
					//$criteria->compare('t.emp_no',Yii::app()->user->id,true);
				} elseif ($level == 4)  {
                                        $dept = explode(" ", $dept_id);
					$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
					//$div = $division_id;
					$level_id = array(4,5,6,7);
					//$criteria->compare('t.emp_no',Yii::app()->user->id,true);
				} elseif ($level == 5)  {
                                        $dept = explode(" ", $dept_id);
					$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
					//$div = $division_id;
					$level_id = array(5,6,7);
					//$criteria->compare('t.emp_no',Yii::app()->user->id,true);
				} elseif ($level == 6)  {
					if ($position_id == 4) {
                                                $dept = explode(" ", $dept_id);
						$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
						//$div = $division_id;
						$level_id = array(6);
						$criteria->compare('t.emp_no',$emp_no,true);
					} else {
                                                $dept = explode(" ", $dept_id);
						$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
						//$div = $division_id;
						$level_id = array(6,7);
						//$criteria->compare('t.emp_no',Yii::app()->user->id,true);
					}

				} else {
                                        $dept = explode(" ", $dept_id);
					$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
					//$div = $division_id;
					$level_id = array(7);
					//$criteria->compare('t.emp_no',Yii::app()->user->id,true);
				}
            };

            $criteria->compare('adv_mon_id',$this->adv_mon_id);
            $criteria->compare('rel_emp.division_id',$div);
            //$criteria->compare('rel_emp.dept_id',$dept_id);
            if($dept_id != '') {
                //$criteria->compare('rel_emp.dept_id',$dept_id);
                $criteria->addInCondition('rel_emp.dept_id',$dept);
            };
            //$criteria->compare('rel_emp.level_id',$level_id);

            $criteria->compare('adv_mon_date',$this->adv_mon_date,true);
            //$criteria->compare('emp_no',$this->emp_no,true);

            //glory 8 des 2016
            $criteria->compare('t.emp_no',$this->emp_no,true);
            //end

            //$criteria->compare('t.emp_no',$approver_id,true);

            $criteria->compare('currency_id',$this->currency_id,true);
            $criteria->compare('towards',$this->towards,true);
            $criteria->compare('on_date',$this->on_date,true);
            $criteria->compare('amount',$this->amount);
            $criteria->compare('others',$this->others);
            $criteria->compare('remark',$this->remark,true);
            $criteria->compare('bank_acc',$this->bank_acc,true);
            $criteria->compare('created_date',$this->created_date,true);
            $criteria->compare('created_by',$this->created_by,true);
            $criteria->compare('modified_date',$this->modified_date,true);
            $criteria->compare('modified_by',$this->modified_by,true);
            $criteria->compare('serial_no',$this->serial_no,true);
            $criteria->compare('paid_status',$this->paid_status,true);
            $criteria->compare('transfer_status',$this->transfer_status,true);
            $criteria->compare('rel_status.status', $this->search_status);
            $criteria->compare('rel_fin_pum.approver_flag', $approver_flag, true);

			//glory 8 des 2016- untuk keperluan filter
            $criteria->compare('rel_emp.emp_name', $this->search_emp_name, true);
			//end

            //glory 31 jan 2017 - namabahin t.status untuk kebutuhan filter dropdown status
            $criteria->compare('t.status', $this->status, true);

            //$criteria->compare('rel_fin_pum.approver_flag',$approver_flag);
            //$criteria->compare('rel_fin_pum.approver_id',Yii::app()->user->id);

            return new CActiveDataProvider($this, array(
                'criteria'=>$criteria,
                    'sort'=>array(
                        //'defaultOrder'=>'CUSTOMER_NAME',
                        'attributes'=>array(
                            'search_status'=>array(
                            'asc'=>'rel_status.status ASC',
                            'desc'=>'rel_status.status DESC',
                            ),
                            '*',
                            'search_depart_date'=>array(
                            'asc'=>'rel_emp_name.employee_name ASC',
                            'desc'=>'rel_emp_name.employee_name DESC',
                            ),
                        ),
                    )
		));
	}

	public function searchByDivision($division_id, $dept_id, $level, $position_id, $emp_no)
	{
            // @todo Please modify the following code to remove attributes that should not be searched.

            $criteria=new CDbCriteria;
            //$approver_flag = array(2);
			//$status = array(1,2);
            $criteria->together = true;
            //glory 24 nov 2016 untuk keperluan filter
			$criteria->with = array('rel_emp');

            //glory 23 jan 2017 - set order by newest date
            //$criteria->order= 't.adv_mon_id DESC';
            //$criteria->order= 't.status, t.adv_mon_date DESC';
            //finish glory

            if ($division_id == 'JATAKE') {
                $div = array('ASD','DDT','STD','SPL','MDB','BPB');
                $level = array(2,3,4,5);
				//$criteria->compare('t.emp_no',Yii::app()->user->id,true);
            } else {
				if ($level == 2) {
					//$div = $division_id;
                    $dept = explode(" ", $dept_id);
					$div = explode(" ", $division_id);
					$level_id = array(2,3,4,5,6);
					$criteria->compare('t.emp_no',$this->emp_no,true);
				} elseif ($level == 3) {
                                        $dept = explode(" ", $dept_id);
					//$div = $division_id;
					$div = explode(" ", $division_id);
					$level_id = array(3,4,5,6);
					//$criteria->compare('t.emp_no',Yii::app()->user->id,true);
				} elseif ($level == 4)  {
                                        $dept = explode(" ", $dept_id);
					$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
					//$div = $division_id;
					$level_id = array(2,3,4,5,6,7);
					//$criteria->compare('t.emp_no',Yii::app()->user->id,true);
				} elseif ($level == 5)  {
                                        $dept = explode(" ", $dept_id);
					$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
					//$div = $division_id;
					$level_id = array(5,6,7);
					//$criteria->compare('t.emp_no',Yii::app()->user->id,true);
				} elseif ($level == 6)  {
					if ($position_id == 4) {
                                                $dept = explode(" ", $dept_id);
						$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
						//$div = $division_id;
						$level_id = array(6);
						$criteria->compare('t.emp_no',$emp_no,true);
					} else {
                                                $dept = explode(" ", $dept_id);
						$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
						//$div = $division_id;
						$level_id = array(6,7);
						//$criteria->compare('t.emp_no',Yii::app()->user->id,true);
					}

				} elseif ($level == 9) {
                    $dept = explode(" ", $dept_id);
                    //$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
                    $div = $division_id;
                    $level_id = array(2,3,4,9);
					$criteria->compare('t.emp_no',$this->emp_no,true);
                }  else {
                                        $dept = explode(" ", $dept_id);
					$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
					//$div = $division_id;
					$level_id = array(7,10);
					//$criteria->compare('t.emp_no',Yii::app()->user->id,true);
				}
            };

            $criteria->compare('t.adv_mon_id',$this->adv_mon_id, true);
			$criteria->order= 'adv_mon_date DESC';
            //$criteria->compare('rel_emp.division_id',$div);
            //$criteria->compare('rel_emp.dept_id',$dept_id);
            /*if($dept_id != '') {
                //$criteria->compare('rel_emp.dept_id',$dept_id);
                $criteria->addInCondition('rel_emp.dept_id',$dept);
            };*/
            $criteria->compare('rel_emp.level_id',$level_id);

            $criteria->compare('adv_mon_date',$this->adv_mon_date,true);
            //$criteria->compare('emp_no',$this->emp_no,true);

            //glory 8 des 2016
            //$criteria->compare('t.emp_no',$this->emp_no,true);
            //end

            //$criteria->compare('t.emp_no',$approver_id,true);

            $criteria->compare('currency_id',$this->currency_id,true);
            $criteria->compare('towards',$this->towards,true);
            $criteria->compare('on_date',$this->on_date,true);
            $criteria->compare('amount',$this->amount);
            $criteria->compare('others',$this->others);
            $criteria->compare('remark',$this->remark,true);
            $criteria->compare('bank_acc',$this->bank_acc,true);
            $criteria->compare('created_date',$this->created_date,true);
            $criteria->compare('created_by',$this->created_by,true);
            $criteria->compare('modified_date',$this->modified_date,true);
            $criteria->compare('modified_by',$this->modified_by,true);
            $criteria->compare('serial_no',$this->serial_no,true);
            $criteria->compare('paid_status',$this->paid_status,true);
            $criteria->compare('transfer_status',$this->transfer_status,true);
            $criteria->compare('rel_status.status', $this->search_status);
            //$criteria->compare('rel_fin_pum.approver_flag', $approver_flag, true);

			//glory 8 des 2016- untuk keperluan filter
            $criteria->compare('rel_emp.emp_name', $this->search_emp_name, true);
			//end

            //glory 31 jan 2017 - namabahin t.status untuk kebutuhan filter dropdown status
            //$criteria->compare('t.status', $status, true);

            //$criteria->compare('rel_fin_pum.approver_id',Yii::app()->user->id);

            return new CActiveDataProvider($this, array(
                'criteria'=>$criteria,
                    'sort'=>array(
                        //'defaultOrder'=>'CUSTOMER_NAME',
                        'attributes'=>array(
                            'search_status'=>array(
                            'asc'=>'rel_status.status ASC',
                            'desc'=>'rel_status.status DESC',
                            ),
                            '*',
                            'search_depart_date'=>array(
                            'asc'=>'rel_emp_name.employee_name ASC',
                            'desc'=>'rel_emp_name.employee_name DESC',
                            ),
                        ),
                    )
		));
	}
	
    //function searchByBooker added by doris heryanto on Dec 15, 2016 12:159 PM Ambon
    public function searchByBooker($div_multiple)
    {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria=new CDbCriteria;
        $approver_flag = array('1','2');
		$status_pum = array('1','2');
        $criteria->together = true;
        //glory 24 nov 2016 untuk keperluan filter
        $criteria->with = array('rel_emp');

        //glory 23 jan 2017 - set order by newest date
        //$criteria->order= 't.adv_mon_id DESC';
        $criteria->compare('rel_emp.division_multiple',$div_multiple, true);
        $criteria->order= 't.adv_mon_date DESC';
        //glory finish

        //$div_id = explode(" ", $div_multiple);echo $div_id;
        //$criteria->addInCondition('rel_emp.division_multiple',$div_id);
        $criteria->compare('adv_mon_id',$this->adv_mon_id);
        $criteria->compare('t.division_id',$division_id);
        //$criteria->compare('rel_emp.dept_id',$dept_id);
        $criteria->compare('adv_mon_date',$this->adv_mon_date,true);
        //$criteria->compare('emp_no',$this->emp_no,true);

        //glory 8 des 2016
        $criteria->compare('t.emp_no',$this->emp_no,true);
        //end

        //$criteria->compare('t.emp_no',$approver_id,true);
        $criteria->compare('currency_id',$this->currency_id,true);
        $criteria->compare('towards',$this->towards,true);
        $criteria->compare('on_date',$this->on_date,true);
        $criteria->compare('amount',$this->amount);
        $criteria->compare('others',$this->others);
        $criteria->compare('remark',$this->remark,true);
        $criteria->compare('bank_acc',$this->bank_acc,true);
        $criteria->compare('created_date',$this->created_date,true);
        $criteria->compare('created_by',$this->created_by,true);
        $criteria->compare('modified_date',$this->modified_date,true);
        $criteria->compare('modified_by',$this->modified_by,true);
        $criteria->compare('serial_no',$this->serial_no,true);
        $criteria->compare('paid_status',$this->paid_status,true);
        $criteria->compare('transfer_status',$this->transfer_status,true);
        $criteria->compare('t.status', $this->search_status);
		$criteria->compare('t.status',$status_pum,true);

        //glory 8 des 2016- untuk keperluan filter
        $criteria->compare('rel_emp.emp_name', $this->search_emp_name, true);
        //end

        //$criteria->compare('rel_fin_pum.approver_flag',$approver_flag,true);
        //$criteria->compare('rel_fin_pum.approver_id',$approver_id,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
                'sort'=>array(
                    //'defaultOrder'=>'CUSTOMER_NAME',
                    'attributes'=>array(
                        'search_status'=>array(
                        'asc'=>'rel_status.status ASC',
                        'desc'=>'rel_status.status DESC',
                        ),
                        '*',
                        'search_emp_name'=>array(
                        'asc'=>'rel_emp_name.employee_name ASC',
                        'desc'=>'rel_emp_name.employee_name DESC',
                        ),
                    ),
                )
        ));
    }

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function searchByDivision_20161012($dept_id)
	{
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria=new CDbCriteria;
        $approver_flag = array('1','2');
        $criteria->together = true;
        $criteria->with = array('rel_status','rel_emp', 'rel_fin_pum');
        $criteria->order= 't.adv_mon_id DESC';
        $criteria->compare('adv_mon_id',$this->adv_mon_id);
        $criteria->compare('rel_emp.dept_id',$dept_id);
        $criteria->compare('adv_mon_date',$this->adv_mon_date,true);
        $criteria->compare('emp_no',$this->emp_no,true);
        //$criteria->compare('t.emp_no',$approver_id,true);
        $criteria->compare('currency_id',$this->currency_id,true);
        $criteria->compare('towards',$this->towards,true);
        $criteria->compare('on_date',$this->on_date,true);
        $criteria->compare('amount',$this->amount);
        $criteria->compare('others',$this->others);
        $criteria->compare('remark',$this->remark,true);
        $criteria->compare('bank_acc',$this->bank_acc,true);
        $criteria->compare('created_date',$this->created_date,true);
        $criteria->compare('created_by',$this->created_by,true);
        $criteria->compare('modified_date',$this->modified_date,true);
        $criteria->compare('modified_by',$this->modified_by,true);
        $criteria->compare('serial_no',$this->serial_no,true);
        $criteria->compare('paid_status',$this->paid_status,true);
        $criteria->compare('transfer_status',$this->transfer_status,true);
        //$criteria->compare('rel_status.status', $this->search_status);
        //$criteria->compare('rel_emp.emp_name', $this->search_emp_name);
        //$criteria->compare('rel_emp.dept_id',$dept_id);
        $criteria->compare('rel_fin_pum.approver_flag',$approver_flag,true);
        //$criteria->compare('rel_fin_pum.approver_id',$approver_id,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
            'sort'=>array(
                //'defaultOrder'=>'CUSTOMER_NAME',
                'attributes'=>array(
                    'search_status'=>array(
                    'asc'=>'rel_status.status ASC',
                    'desc'=>'rel_status.status DESC',
                    ),
                    '*',
                    'search_emp_name'=>array(
                    'asc'=>'rel_emp_name.employee_name ASC',
                    'desc'=>'rel_emp_name.employee_name DESC',
                    ),
                ),
            )
	    ));
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search_filter_by_emp($emp_no)
	{
		$criteria=new CDbCriteria;
		$criteria->together = true;
		$criteria->with = array('rel_status','rel_emp');

		$criteria->order= 't.modified_date DESC';
		
		if(Yii::app()->globalFunction->get_user_role_id($emp_no) != 20 && Yii::app()->globalFunction->get_user_role_id($emp_no) != 58){
		    $criteria->compare('t.emp_no',$emp_no,true);
		}else{
		    $criteria->compare('t.emp_no',$this->emp_no,true);
		}

		$criteria->compare('t.status', $this->search_status,true);
		$criteria->compare('t.adv_mon_id',$this->adv_mon_id,true);
		$criteria->compare('t.adv_mon_date',$this->adv_mon_date,true);
		$criteria->compare('t.sppd_id', $this->sppd_id,true);
		$criteria->compare('t.currency_id',$this->currency_id,true);
		$criteria->compare('t.towards',$this->towards,true);
		$criteria->compare('t.on_date',$this->on_date,true);
		$criteria->compare('t.division_id',$this->division_id,true);
		$criteria->compare('t.dept_id',$this->dept_id,true);
		$criteria->compare('t.amount',$this->amount);
		$criteria->compare('t.paid_status',$this->paid_status,true);
		$criteria->compare('t.transfer_status',$this->transfer_status,true);
		$criteria->compare('rel_emp.emp_name',$this->search_emp_name,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'sort'=>array(
                'attributes'=>array(
                    'search_status'=>array(
                        'asc'=>'rel_status.status ASC',
                        'desc'=>'rel_status.status DESC',
                    ),
                    '*',
                ),
            )
		));
	}
	
	public function search_emp_head($emp_no)
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;
		$criteria->together = true;
		$criteria->with = array('rel_status','rel_emp');

		$criteria->order= 't.modified_date DESC';

		$criteria->compare('t.status', $this->search_status,true);
		$criteria->compare('t.adv_mon_id',$this->adv_mon_id,true);
		$criteria->compare('t.adv_mon_date',$this->adv_mon_date,true);
		$criteria->compare('t.sppd_id', $this->sppd_id,true);
		$criteria->compare('t.emp_no',$this->emp_no,true);
		$criteria->compare('t.currency_id',$this->currency_id,true);
		$criteria->compare('t.towards',$this->towards,true);
		$criteria->compare('t.on_date',$this->on_date,true);
		$criteria->compare('t.division_id',$this->division_id,true);
		$criteria->compare('t.dept_id',$this->dept_id,true);
		$criteria->compare('t.amount',$this->amount);
		$criteria->compare('t.paid_status',$this->paid_status,true);
		$criteria->compare('t.transfer_status',$this->transfer_status,true);
		$criteria->compare('rel_emp.emp_name',$this->search_emp_name,true);
		
		// GET HEAD OF ADMIN
		$_sql = "SELECT * FROM hgs_mst_admin WHERE user_id = '".$emp_no."'";
        $_command = Yii::app()->db->createCommand($_sql);
        $_reader = $_command->queryAll();
        $heads = array();
        $no = 0;
        foreach ($_reader as $rows) {
            if($no == 0){
    		    $criteria->compare('t.created_by', $rows['head_id']);
            }else{
    		    $criteria->compare('t.created_by', $rows['head_id'], false, 'OR');
            }
            $no++;
        }

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'sort'=>array(
                //'defaultOrder'=>'CUSTOMER_NAME',
                'attributes'=>array(
                    'search_status'=>array(
                        'asc'=>'rel_status.status ASC',
                        'desc'=>'rel_status.status DESC',
                    ),
                    '*',
                ),
            )
		));
	}
	
	public function searchNeedApproval($approver_id)
	{
	    $criteria=new CDbCriteria;
		$criteria->together = true;
		$criteria->with = array('rel_status', 'rel_emp', 'rel_fin_pum', 'rel_sppd');

		$criteria->order= 't.modified_date DESC';

		$criteria->compare('t.status', $this->search_status,true);
		$criteria->compare('t.adv_mon_id',$this->adv_mon_id,true);
		$criteria->compare('t.adv_mon_date',$this->adv_mon_date,true);
		$criteria->compare('t.sppd_id', $this->sppd_id,true);
		$criteria->compare('t.emp_no',$this->emp_no,true);
		$criteria->compare('t.currency_id',$this->currency_id,true);
		$criteria->compare('t.towards',$this->towards,true);
		$criteria->compare('t.on_date',$this->on_date,true);
		$criteria->compare('t.division_id',$this->division_id,true);
		$criteria->compare('t.dept_id',$this->dept_id,true);
		$criteria->compare('t.amount',$this->amount);
		$criteria->compare('t.paid_status',$this->paid_status,true);
		$criteria->compare('t.transfer_status',$this->transfer_status,true);
		$criteria->compare('rel_emp.emp_name',$this->search_emp_name, true);
        $criteria->compare('rel_fin_pum.approver_flag', '1');
        $criteria->compare('rel_fin_pum.approver_id', $approver_id);
        $criteria->compare('t.status', '1');

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,	
			'sort'=>array(
                'attributes'=>array(
                    'search_status'=>array(
                        'asc'=>'rel_status.status ASC',
                        'desc'=>'rel_status.status DESC',
                    ),
                    '*',
                ),
            )	
		));
	}
	
	public function searchApprovalHistory($approver_id)
	{
		$criteria=new CDbCriteria;
		$criteria->together = true;
		$criteria->with = array('rel_status', 'rel_emp', 'rel_fin_pum', 'rel_sppd');

		$criteria->order= 't.modified_date DESC';

		$criteria->compare('t.status', $this->search_status,true);
		$criteria->compare('t.adv_mon_id',$this->adv_mon_id,true);
		$criteria->compare('t.adv_mon_date',$this->adv_mon_date,true);
		$criteria->compare('t.sppd_id', $this->sppd_id,true);
		$criteria->compare('t.emp_no',$this->emp_no,true);
		$criteria->compare('t.currency_id',$this->currency_id,true);
		$criteria->compare('t.towards',$this->towards,true);
		$criteria->compare('t.on_date',$this->on_date,true);
		$criteria->compare('t.division_id',$this->division_id,true);
		$criteria->compare('t.dept_id',$this->dept_id,true);
		$criteria->compare('t.amount',$this->amount);
		$criteria->compare('t.paid_status',$this->paid_status,true);
		$criteria->compare('t.transfer_status',$this->transfer_status,true);
		$criteria->compare('rel_emp.emp_name',$this->search_emp_name, true);
		$criteria->compare('rel_fin_pum.approver_id', $approver_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,	
			'sort'=>array(
                'attributes'=>array(
                    'search_status'=>array(
                        'asc'=>'rel_status.status ASC',
                        'desc'=>'rel_status.status DESC',
                    ),
                    '*',
                ),
            ),
		));
	}
	
	public function searchHistoryFAD($approver_id)
	{
		$criteria=new CDbCriteria;
		$criteria->together = true;
		$criteria->with = array('rel_status', 'rel_emp');

		$criteria->order= 't.modified_date DESC';

		$criteria->compare('t.adv_mon_id',$this->adv_mon_id,true);
		$criteria->compare('t.adv_mon_date',$this->adv_mon_date,true);
		$criteria->compare('t.sppd_id', $this->sppd_id,true);
		$criteria->compare('t.emp_no',$this->emp_no,true);
		$criteria->compare('t.currency_id',$this->currency_id,true);
		$criteria->compare('t.towards',$this->towards,true);
		$criteria->compare('t.on_date',$this->on_date,true);
		$criteria->compare('t.division_id',$this->division_id,true);
		$criteria->compare('t.dept_id',$this->dept_id,true);
		$criteria->compare('t.amount',$this->amount);
		$criteria->compare('t.paid_status',$this->paid_status,true);
		$criteria->compare('t.transfer_status',$this->transfer_status,true);
		$criteria->compare('rel_emp.emp_name',$this->search_emp_name, true);
		$criteria->compare('t.status', $this->search_status,true);
		
		$criteria->addCondition('t.status IN (2,3)');
		
		//$endDate = date('Y-m-d');
        //$startDate = date('Y-m-d', strtotime('-6 months'));
        //$criteria->addBetweenCondition('t.created_date', $startDate, $endDate);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,	
			'sort'=>array(
                'attributes'=>array(
                    'search_status'=>array(
                        'asc'=>'rel_status.status ASC',
                        'desc'=>'rel_status.status DESC',
                    ),
                    '*',
                ),
            ),
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return AdvanceMoneyModel the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function searchlistapproval()
	{
		$user = Yii::app()->user->id;

		$count = Yii::app()->db->createCommand("SELECT COUNT(*) FROM fin_trs_approval a 
		                                            INNER JOIN fin_trs_advance_money b ON b.adv_mon_id = a.doc_no 
                                    				INNER JOIN ebt_trs_sppd_destination c ON b.sppd_id = c.sppd_id 
                                    				INNER JOIN ebt_trs_sppd d ON c.sppd_id = d.sppd_id
	                                            WHERE 
	                                                a.approver_id = '$user' AND 
	                                                a.approver_flag = 1 AND 
	                                                b.status = 1")->queryScalar();
		$statement =   "SELECT * FROM fin_trs_approval a 
    		                INNER JOIN fin_trs_advance_money b ON b.adv_mon_id = a.doc_no 
            				INNER JOIN ebt_trs_sppd_destination c ON b.sppd_id = c.sppd_id 
            				INNER JOIN ebt_trs_sppd d ON c.sppd_id = d.sppd_id
		                WHERE 
		                    a.approver_id = '".$user."' AND 
		                    a.approver_flag = '1' AND 
		                    b.status = '1' 
		                ORDER BY 
		                    b.adv_mon_date DESC";

		$dataProvider=new CSqlDataProvider($statement, array(
                                'totalItemCount'=>$count,
                                'sort'=>array(
									'attributes'=>array( 
    									'adv_mon_id'=>'PUM ID',
    									'emp_no'=>'NIK',
    									'trip_id'=>'Types of Trip',
    									'to'=>'Destination',
    									'departure_date'=>'Departure Date',
    									'arrival_date'=>'Arrival Date'
                                    ),
                                ),
                                'pagination'=>array(
                                    'pageSize'=>10,
                                ),
                ));

		return $dataProvider;
	}

	public function getName($id)
	{
		$sql = "SELECT full_name FROM hgs_mst_user WHERE user_id = '$id'";
		$command = Yii::app()->db->createCommand($sql);
		$command->execute();

		return $command->queryScalar();
	}

	//glory 11 05 2016 - to check if approver date is null - actionUpdate
	public function getApproverDateSppd($sppd_id, $user)
	{
		$sql = "SELECT approver_date FROM ebt_trs_approval a WHERE doc_no = '$sppd_id' AND approver_id = '$user'";
		$command = Yii::app()->db->createCommand($sql);
		$command->execute();
		return $command->queryScalar();
	}

	public function getAttachment($pumid)
	{
		$sql = "SELECT adv_mon_id FROM fin_trs_advance_money_file_dokumen a WHERE adv_mon_id = '$pumid'";
		$command = Yii::app()->db->createCommand($sql);
		$command->execute();
		return $command->queryScalar();
	}
	//11 05 2016

    //dded by doris on March 10, 2016 11:06 pm >> start >>
    public function getDestinationName($id)
	{
		$select = "SELECT city_name FROM hgs_mst_city WHERE city_id = '$id'";
		$conn = Yii::app()->db->createCommand($select);
		$conn->execute();

		return $conn->queryScalar();
	}
    //>> finish >>

	//Added by glory on January 3, 2017
	public function getDestinationNameSppd($id)
	{
		$select = "SELECT city_name FROM ebt_trs_sppd_destination d join hgs_mst_city c on d.to = c.city_id where d.sppd_id = '$id'";
		$conn = Yii::app()->db->createCommand($select);
		$conn->execute();

		return $conn->queryScalar();
	}
	//finish

	//glory - 13 jan 2017
	public function getNoRek($emp_id)
	{
		$select = "SELECT nomor_rekening FROM hgs_mst_employee WHERE emp_no = '$emp_id'";
		$conn = Yii::app()->db->createCommand($select);
		$conn->execute();

		return $conn->queryScalar();
	}
	//finish

	public function getDivisionName()
	{
		if (self::$_divisionMap === null) {
			$rows = Yii::app()->db->createCommand("SELECT division_id, division_name FROM hgs_mst_division")->queryAll();
			$map = array();
			foreach ($rows as $r) {
				$map[$r['division_id']] = $r['division_name'];
			}
			self::$_divisionMap = $map;
		}
		$id = $this->division_id;
		if (isset(self::$_divisionMap[$id])) {
			return self::$_divisionMap[$id];
		}
		return $id;
	}

	public function getAllDataStatus_0()
	{
		$connection = Yii::app()->db;
        /*$sqlStatement2 = "
            SELECT
                a.adv_mon_id,
                b.emp_name,
                e.division_name,
                d.city_name,
                c.departure_date,
                b.nomor_rekening,
                a.amount + a.others as grand_total,
                a.bank_acc as document_date
            FROM
                fin_trs_advance_money a,
                hgs_mst_employee b,
                ebt_trs_sppd_destination c,
                hgs_mst_city d,
                hgs_mst_division e
            WHERE
                a.sppd_id = c.sppd_id
                    AND c.`to` = d.city_id
                    AND a.emp_no = b.emp_no
                    AND e.division_id = a.division_id
                    AND a.status = '2'
                    AND export_status = '0'
            GROUP BY c.sppd_id";*/

		//glory 21 05 2016 - nambahin field last approver, approver data, remark
		/*$sqlStatement2 = "
            SELECT
				a.adv_mon_id,
				b.emp_name,
				e.division_name,
				d.city_name,
				c.departure_date,
				c.arrival_date,
				b.nomor_rekening,
				a.amount + a.others as grand_total,
				a.bank_acc as document_date,
				f.approver_id,
				f.approver_date,
				a.remark
            FROM
                fin_trs_advance_money a,
				hgs_mst_employee b,
				ebt_trs_sppd_destination c,
				hgs_mst_city d,
				hgs_mst_division e,
				fin_trs_approval f
            WHERE
				a.sppd_id = c.sppd_id
				AND c.`to` = d.city_id
				AND a.emp_no = b.emp_no
				AND e.division_id = a.division_id
				AND a.adv_mon_id = f.doc_no
				AND f.order_approval = '2'
				AND a.`status` = '2'
            GROUP BY c.sppd_id";

			$sqlStatement2 = "
                SELECT
    				a.adv_mon_id,
    				b.emp_name,
    				e.division_name,
    				d.city_name,
    				c.departure_date,
    				c.arrival_date,
    				b.nomor_rekening,
    				a.grand_total,
    				a.bank_acc as document_date,
    				a.remark,
    				a.budget_code
                FROM
            fin_trs_advance_money a,
    				hgs_mst_employee b,
    				ebt_trs_sppd_destination c,
    				hgs_mst_city d,
    				hgs_mst_division e
            WHERE
    				a.sppd_id = c.sppd_id
    				AND c.`to` = d.city_id
    				AND a.emp_no = b.emp_no
    				AND e.division_id = a.division_id
    				AND a.`status` = '2'
    				AND export_status = '0'
           GROUP BY c.sppd_id";*/
                   
        $sqlStatement2 = "SELECT a.adv_mon_id, b.emp_name, c.division_name,
            CONCAT(
            		(
            			SELECT hgs_mst_city.city_name 
                    	FROM ebt_trs_sppd_destination 
                        INNER JOIN hgs_mst_city 
                        ON ebt_trs_sppd_destination.`from`  = hgs_mst_city.city_id 
                        WHERE ebt_trs_sppd_destination.sppd_id = a.sppd_id 
                        ORDER BY ebt_trs_sppd_destination.departure_date ASC
                        LIMIT 1
                     ),
                     ' - ',
                     (
                     	SELECT hgs_mst_city.city_name 
                        FROM ebt_trs_sppd_destination 
                        INNER JOIN hgs_mst_city 
                        ON ebt_trs_sppd_destination.to = hgs_mst_city.city_id 
                        WHERE ebt_trs_sppd_destination.sppd_id = a.sppd_id 
                        ORDER BY ebt_trs_sppd_destination.departure_date DESC
                        LIMIT 1
                      )
            ) AS 'trip',
            (
            	SELECT departure_date 
                FROM ebt_trs_sppd_destination 
                WHERE sppd_id = a.sppd_id 
                ORDER BY departure_date ASC
                LIMIT 1
            ) AS 'start_trip',
            (
            	SELECT arrival_date 
                FROM ebt_trs_sppd_destination 
                WHERE sppd_id = a.sppd_id 
                ORDER BY departure_date DESC
                LIMIT 1
            ) AS 'end_trip',
            a.remark,
            a.budget_code,
            b.nomor_rekening,
            a.grand_total,
            a.status,
            export_status
            FROM
            fin_trs_advance_money a,
            hgs_mst_employee b,
            hgs_mst_division c
            WHERE
            a.emp_no = b.emp_no
            AND c.division_id = a.division_id
            AND a.`status` = '2'
            AND export_status = '0';";   
    
  		$command2=$connection->createCommand($sqlStatement2);
  		$reader2=$command2->query();

		return $reader2;
	}
	
	public function getAllDataIAD()
	{
		$connection = Yii::app()->db;
                   
        $sqlStatement2 = "select a.emp_no, b.emp_name, g.division_name, 
            a.sppd_id, a.purpose,  f.trip_name, a.sppd_date as submissiondate,
            max(case when c.order_approval = 1 then c.approver_date end) as sppd_approved_by_approver1,
            max(case when c.order_approval = 2 then c.approver_date end) as sppd_approved_by_approver2,
            max(case when c.order_approval = 3 then c.approver_date end) as sppd_approved_by_approver3,
            max(case when c.order_approval = 4 then c.approver_date end) as sppd_approved_by_approver4,
            case 
            	when a.`status` = 0 then 'Draft'
            	when a.`status` = 1 then 'In Progress'
            	when a.`status` = 2 then 'Approved'
            	when a.`status` = 3 then 'Rejected'
            end as sppd_status,
            d.adv_mon_id, d.adv_mon_date, d.created_date as pum_created,
            max(case when e.order_approval = 1 then e.approver_date end) as pum_approved_by_approver1,
            max(case when e.order_approval = 2 then e.approver_date end) as pum_approved_by_approver2,
            max(case when e.order_approval = 3 then e.approver_date end) as pum_approved_by_approver3,
            max(case when e.order_approval = 4 then e.approver_date end) as pum_approved_by_approver4,
            case 
            	when d.`status` = 0 then 'Draft'
            	when d.`status` = 1 then 'In Progress'
            	when d.`status` = 2 then 'Approved'
            	when d.`status` = 3 then 'Rejected'
            end as pum_status
            from ebt_trs_sppd a
            join hgs_mst_employee b
            on a.emp_no = b.emp_no
            join ebt_trs_approval c
            on a.sppd_id = c.doc_no
            left join fin_trs_advance_money d
            on a.sppd_id = d.sppd_id
            left join fin_trs_approval e
            on d.adv_mon_id = e.doc_no 
            join ebt_mst_trip_type f
            on a.trip_id = f.trip_id
            join hgs_mst_division g
            on a.division_id = g.division_id 
            group by a.emp_no, b.emp_name, a.sppd_id, d.adv_mon_id
            order by a.sppd_id;";   
    
  		$command2=$connection->createCommand($sqlStatement2);
  		$reader2=$command2->query();

		return $reader2;
	}
}
