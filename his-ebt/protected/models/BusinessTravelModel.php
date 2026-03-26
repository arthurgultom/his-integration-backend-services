<?php

/**
 * This is the model class for table "ebt_trs_sppd".
 *
 * The followings are the available columns in table 'ebt_trs_sppd':
 * @property string $sppd_id
 * @property string $sppd_date
 * @property string $emp_no
 * @property string $purpose
 * @property integer $trip_id
 * @property string $instructed_by
 * @property string $departure_date
 * @property string $arrival_date
 * @property integer $days
 * @property integer $meal_amount
 * @property integer $allowance_amount
 * @property integer $hotel_amount
 * @property integer $transport_amount
 * @property integer $others_amount
 * @property integer $total_amount
 * @property string $created_date
 * @property string $created_by
 * @property string $modified_date
 * @property string $modified_by
 * @property integer $status
 */
class BusinessTravelModel extends CActiveRecord
{

	public $search_status;
	public $search_emp;
	public $search_emp_name;
	public $search_city;
	public $search_departure_date;
	public $search_arrival_date;
	public $search_trip;
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ebt_trs_sppd';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			//array('sppd_id, sppd_date, emp_no, purpose, trip_id, instructed_by, departure_date, arrival_date, days, meal_amount, allowance_amount, hotel_amount, transport_amount, others_amount, total_amount, created_date, created_by, modified_date, modified_by', 'required'),
			array('sppd_id, sppd_date, emp_no, purpose, trip_id, instructed_by, days, meal_amount, allowance_amount, hotel_amount, transport_amount, others_amount, total_amount, created_date, created_by, modified_date, modified_by', 'required'),
			array('trip_id, status, booking_ticket, advance_money, days, meal_amount, allowance_amount, hotel_amount, transport_amount, others_amount, total_amount, serial_no', 'numerical', 'integerOnly' => true),
			array('sppd_id, division_id, emp_no, instructed_by', 'length', 'max' => 20),
			array('created_by, modified_by, departure_date, arrival_date', 'length', 'max' => 50),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('sppd_id, sppd_date, emp_no, purpose, trip_id, instructed_by, departure_date, arrival_date, days, meal_amount, allowance_amount, hotel_amount, transport_amount, others_amount, total_amount, search_status, search_departure_date, search_arrival_date, search_emp, search_emp_name, search_city, search_trip, created_date, created_by, modified_date, modified_by, status, admin_id', 'safe', 'on' => 'search'),
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
			'rel_employee' => array(self::BELONGS_TO, 'EmployeeModel', 'emp_no'),
			'rel_trip_type' => array(self::BELONGS_TO, 'TripTypeModel', 'trip_id'),
			'rel_ebt_dest' => array(self::HAS_ONE, 'BusinessTravelDestinationModel', 'sppd_id', 'order' => 'dest_id DESC'),
			'rel_city' => array(self::HAS_ONE, 'CityModel', array('to' => 'city_id'), 'through' => 'rel_ebt_dest'),
			'rel_ebt_trs_app' => array(self::BELONGS_TO, 'TripTypeModel', 'trip_id'),
			'rel_ebt_app' => array(self::BELONGS_TO, 'BusinessTravelApprovalModel', 'doc_no', 'foreignKey' => array('sppd_id' => 'doc_no')),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'sppd_id' => 'SPPD ID',
			'sppd_date' => 'SPPD Date',
			'division_id' => 'Division ID',

			//'emp_no' => 'Employee Name',
			'emp_no' => 'NIK',
			'emp_name' => 'Name',
			'purpose' => 'Purpose',
			'level_name' => 'Level',
			'division_name' => 'Divisi',
			'trip_id' => 'Trip Type',
			'instructed_by' => 'Instructed By',
			'departure_date' => 'Departure Date',
			'arrival_date' => 'Arrival Date',
			'days' => 'Days Trip',
			'meal_amount' => 'Meal Amount',
			'allowance_amount' => 'Allowance Amount',
			'hotel_amount' => 'Hotel Amount',
			'transport_amount' => 'Tranportation Amount',
			'others_amount' => 'Others Amount',
			'total_amount' => 'Total Amount',
			'advance_money' => 'PUM Request',
			'booking_ticket' => 'Booking Ticket Request',
			'created_date' => 'Created Date',
			'created_by' => 'Created By',
			'modified_date' => 'Modified Date',
			'modified_by' => 'Modifed By',
			'status' => 'Status',
			'search_status' => 'Status',
			'search_departure_date' => 'Start Trip',
			'search_arrival_date' => 'End Trip',
			'search_emp' => 'Employee Name',
			'search_city' => 'City Name',
			'search_trip' => 'Trip Type',
			'serial_no' => 'Serial Number of Records',
		);
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
	public function searchByAdmin()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria = new CDbCriteria;

		$criteria->with = array('rel_status', 'rel_trip_type', 'rel_employee');
		$criteria->order = 't.sppd_date, t.sppd_id DESC';

		$approver_flag = array('1', '2'); //added by doris on Dec 14, 2015 on 10:26 AM
		$criteria->compare('sppd_id', $this->sppd_id, true);
		$criteria->compare('sppd_date', $this->sppd_date, true);
		$criteria->compare('division_id', $this->division_id, true);
		$criteria->compare('t.emp_no', $this->emp_no, true);
		$criteria->compare('purpose', $this->purpose, true);
		$criteria->compare('trip_id', $this->trip_id);
		$criteria->compare('instructed_by', $this->instructed_by, true);
		$criteria->compare('departure_date', $this->departure_date, true);
		$criteria->compare('arrival_date', $this->arrival_date, true);
		$criteria->compare('days', $this->days);
		$criteria->compare('meal_amount', $this->meal_amount);
		$criteria->compare('allowance_amount', $this->allowance_amount);
		$criteria->compare('hotel_amount', $this->hotel_amount);
		$criteria->compare('transport_amount', $this->transport_amount);
		$criteria->compare('others_amount', $this->others_amount);
		$criteria->compare('total_amount', $this->total_amount);
		$criteria->compare('advance_money', $this->advance_money);
		$criteria->compare('booking_ticket', $this->booking_ticket);
		$criteria->compare('created_date', $this->created_date, true);
		$criteria->compare('created_by', $this->created_by, true);
		$criteria->compare('modified_date', $this->modified_date, true);
		$criteria->compare('modified_by', $this->modified_by, true);
		//$criteria->compare('t.status',$this->status,true);
		$criteria->compare('rel_status.status', $this->search_status);
		//$criteria->compare('rel_employee.emp_name', $this->search_emp_name);

		//diubah oleh glory 9 des 2016 - untuk keperluan search, mengganti rel_employee.emp_noo jadi emp_name
		$criteria->compare('rel_employee.emp_name', $this->search_emp, true);
		//end

		$criteria->compare('rel_trip_type.trip_name', $this->search_trip);
		//$criteria->compare('rel_city.city_name',$this->search_city);
		$criteria->compare('serial_no', $this->serial_no);
		//$criteria->compare('rel_ebt_app.approver_flag',$approver_flag);
		//$criteria->compare('rel_ebt_app.approver_id',$approver_id);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				//'defaultOrder'=>'CUSTOMER_NAME',
				'attributes' => array(
					'search_status' => array(
						'asc' => 'rel_status.status ASC',
						'desc' => 'rel_status.status DESC',
					),
					'search_emp' => array(
						'asc' => 'rel_employee.emp_no ASC',
						'desc' => 'rel_employee.emp_no DESC',
					),
					'search_emp_name' => array(
						'asc' => 'rel_employee.emp_name ASC',
						'desc' => 'rel_employee.emp_name DESC',
					),
					'search_trip' => array(
						'asc' => 'rel_trip_type.trip_name ASC',
						'desc' => 'rel_trip_type.trip_name DESC',
					),
					'search_city' => array(
						'asc' => 'rel_city.rel_to.city_name ASC',
						'desc' => 'rel_city.rel_to.city_name DESC',
					),
					'*',
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
	public function search($approver_id)
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria = new CDbCriteria;

		//glory 9 des 2016 - menambahkan relation rel_employee
		$criteria->with = array('rel_status', 'rel_ebt_app', 'rel_trip_type', 'rel_employee');
		//$criteria->order= 't.sppd_id DESC';
		// $criteria->order= 't.status, t.sppd_id, t.sppd_date DESC';
		$criteria->order = 't.sppd_date DESC';

		$approver_flag = array('1', '2'); //added by doris on Dec 14, 2015 on 10:26 AM
		$criteria->compare('sppd_id', $this->sppd_id, true);
		$criteria->compare('sppd_date', $this->sppd_date, true);
		$criteria->compare('division_id', $this->division_id, true);
		$criteria->compare('t.emp_no', $this->emp_no, true);
		$criteria->compare('purpose', $this->purpose, true);
		$criteria->compare('trip_id', $this->trip_id);
		$criteria->compare('instructed_by', $this->instructed_by, true);
		$criteria->compare('departure_date', $this->departure_date, true);
		$criteria->compare('arrival_date', $this->arrival_date, true);
		$criteria->compare('days', $this->days);
		$criteria->compare('meal_amount', $this->meal_amount);
		$criteria->compare('allowance_amount', $this->allowance_amount);
		$criteria->compare('hotel_amount', $this->hotel_amount);
		$criteria->compare('transport_amount', $this->transport_amount);
		$criteria->compare('others_amount', $this->others_amount);
		$criteria->compare('total_amount', $this->total_amount);
		$criteria->compare('advance_money', $this->advance_money);
		$criteria->compare('booking_ticket', $this->booking_ticket);
		$criteria->compare('created_date', $this->created_date, true);
		$criteria->compare('created_by', $this->created_by, true);
		$criteria->compare('modified_date', $this->modified_date, true);
		$criteria->compare('modified_by', $this->modified_by, true);
		$criteria->compare('t.status', $this->status, true);
		$criteria->compare('rel_status.status', $this->search_status);

		//glory 9 des 2016 - mengganti rel_employee.emp_no jadi emp_name untuk filtering
		$criteria->compare('rel_employee.emp_name', $this->search_emp, true);
		//end

		$criteria->compare('rel_trip_type.trip_name', $this->search_trip);
		//$criteria->compare('rel_ebt_dest.rel_to.city_name',$this->search_city);
		$criteria->compare('serial_no', $this->serial_no);
		$criteria->compare('rel_ebt_app.approver_flag', $approver_flag);

		//edit approver flag
		//$criteria->compare('rel_ebt_app.approver_flag', '1');

		$criteria->compare('rel_ebt_app.approver_id', $approver_id);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				//'defaultOrder'=>'CUSTOMER_NAME',
				'attributes' => array(
					'search_status' => array(
						'asc' => 'rel_status.status ASC',
						'desc' => 'rel_status.status DESC',
					),
					'search_emp' => array(
						'asc' => 'rel_employee.emp_no ASC',
						'desc' => 'rel_employee.emp_no DESC',
					),
					'search_trip' => array(
						'asc' => 'rel_trip_type.trip_name ASC',
						'desc' => 'rel_trip_type.trip_name DESC',
					),
					/*'search_city'=>array(
                        'asc'=>'rel_ebt_dest.rel_to.city_name ASC',
                        'desc'=>'rel_ebt_dest.rel_to.city_name DESC',
                    ),*/
					'*',
				),
			)
		));
	}

	public function searchNeedApproval($approver_id)
	{
		$criteria = new CDbCriteria;
		$criteria->together = true;
		$criteria->with = array('rel_status', 'rel_ebt_app', 'rel_trip_type', 'rel_employee');

		$criteria->order = 't.modified_date DESC';

		$criteria->compare('t.sppd_id', $this->sppd_id, true);
		$criteria->compare('t.sppd_date', $this->sppd_date, true);
		$criteria->compare('t.departure_date', $this->departure_date, true);
		$criteria->compare('t.arrival_date', $this->arrival_date, true);
		if (Yii::app()->globalFunction->get_user_role_id($this->emp_no) != 20) {
			$criteria->compare('t.emp_no', $this->emp_no, true);
		} else {
			$criteria->compare('t.emp_no', $this->emp_no, true);
			$endDate = date('Y-m-d');
			$startDate = date('Y-m-d', strtotime('-3 months'));
			$criteria->addBetweenCondition('t.created_date', $startDate, $endDate);
		}
		$criteria->compare('rel_employee.emp_name', $this->search_emp, true);
		$criteria->compare('t.advance_money', $this->advance_money, true);
		$criteria->compare('rel_trip_type.trip_name', $this->search_trip, true);
		$criteria->compare('rel_city.city_name', $this->search_city, true);
		$criteria->compare('t.status', $this->search_status);
		$criteria->compare('rel_ebt_app.approver_flag', '1');
		$criteria->compare('rel_ebt_app.approver_id', $approver_id);
		$criteria->compare('t.status', '1');

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'attributes' => array(
					'search_status' => array(
						'asc' => 'rel_status.status ASC',
						'desc' => 'rel_status.status DESC',
					),
					'search_emp' => array(
						'asc' => 'rel_employee.emp_no ASC',
						'desc' => 'rel_employee.emp_no DESC',
					),
					'search_trip' => array(
						'asc' => 'rel_trip_type.trip_name ASC',
						'desc' => 'rel_trip_type.trip_name DESC',
					),
					'*',
				),
			)
		));
	}

	public function searchApprovalHistory($approver_id)
	{
		$criteria = new CDbCriteria;
		$criteria->together = true;
		$criteria->with = array('rel_status', 'rel_ebt_app', 'rel_trip_type', 'rel_employee');

		$criteria->order = 't.modified_date DESC';

		$criteria->compare('t.sppd_id', $this->sppd_id, true);
		$criteria->compare('t.sppd_date', $this->sppd_date, true);
		$criteria->compare('t.departure_date', $this->departure_date, true);
		$criteria->compare('t.arrival_date', $this->arrival_date, true);
		if (Yii::app()->globalFunction->get_user_role_id($this->emp_no) != 20) {
			$criteria->compare('t.emp_no', $this->emp_no, true);
		} else {
			$criteria->compare('t.emp_no', $this->emp_no, true);
			$endDate = date('Y-m-d');
			$startDate = date('Y-m-d', strtotime('-3 months'));
			$criteria->addBetweenCondition('t.created_date', $startDate, $endDate);
		}
		$criteria->compare('rel_employee.emp_name', $this->search_emp, true);
		$criteria->compare('t.advance_money', $this->advance_money, true);
		$criteria->compare('rel_trip_type.trip_name', $this->search_trip, true);
		$criteria->compare('rel_city.city_name', $this->search_city, true);
		$criteria->compare('t.status', $this->search_status);
		$criteria->compare('rel_ebt_app.approver_flag', '2');
		$criteria->compare('rel_ebt_app.approver_flag', '3', false, 'OR');
		$criteria->compare('rel_ebt_app.approver_id', $approver_id);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'attributes' => array(
					'search_status' => array(
						'asc' => 'rel_status.status ASC',
						'desc' => 'rel_status.status DESC',
					),
					'search_emp' => array(
						'asc' => 'rel_employee.emp_no ASC',
						'desc' => 'rel_employee.emp_no DESC',
					),
					'search_trip' => array(
						'asc' => 'rel_trip_type.trip_name ASC',
						'desc' => 'rel_trip_type.trip_name DESC',
					),
					'*',
				),
			)
		));
	}

	public function searchByDivision($division_id, $dept_id, $level, $position, $emp_no) //backup oleh glory - 2 des 2016, problem : list pak ronal kosong
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria = new CDbCriteria;
		$criteria->together = true;
		$criteria->with = array('rel_employee');
		$approver_flag = array(1, 2);

		if ($division_id == 'JATAKE') {
			$div = array('ASD', 'DDT', 'STD', 'SPL', 'MDB', 'BPB');
			$level_id = array(2, 3, 4, 5);
			$criteria->compare('t.emp_no', $this->emp_no, true);
		} else {
			if ($level == 10) {
				$div = $division_id;
				$dept = explode(" ", $dept_id);
				//$div = explode(" ", $division_id);
				$level_id = array(2, 3, 4, 10);
				$criteria->compare('t.emp_no', $this->emp_no, true);
			} elseif ($level == 2) {
				$div = $division_id;
				$dept = explode(" ", $dept_id);
				//$div = explode(" ", $division_id);
				$level_id = array(2, 3, 4, 5, 6, 7);
				$criteria->compare('t.emp_no', $this->emp_no, true);
			} elseif ($level == 3) {
				$div = $division_id;
				$dept = explode(" ", $dept_id);
				//$div = explode(" ", $division_id);
				$level_id = array(3, 4, 5, 6, 7);
				$criteria->compare('t.emp_no', $this->emp_no, true);
			} elseif ($level == 4) {
				//$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
				$dept = explode(" ", $dept_id);
				$div = $division_id;
				$level_id = array(4, 5, 6, 7);
				$criteria->compare('t.emp_no', $this->emp_no, true);
			} elseif ($level == 5) {
				$dept = explode(" ", $dept_id);
				//$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
				$div = $division_id;
				$level_id = array(5, 6, 7);
				$criteria->compare('t.emp_no', $this->emp_no, true);
			} elseif ($level == 6) {
				if ($position == 4) {
					$dept = explode(" ", $dept_id);
					//$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
					$div = $division_id;
					$level_id = array(6);
					$criteria->compare('t.emp_no', $emp_no, true);
				} elseif ($position == 3) {
					$div = $division_id;
					$level_id = array(6, 7);
					$dept = explode(" ", $dept_id);
					$criteria->compare('t.emp_no', $this->emp_no, true);
				} else {
					//$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
					$div = $division_id;
					$level_id = array(6, 7);
					$dept = explode(" ", $dept_id);
					$criteria->compare('t.emp_no', $this->emp_no, true);
				}
			} elseif ($level == 9) {
				$dept = explode(" ", $dept_id);
				//$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
				$div = $division_id;
				$level_id = array(2, 3, 4, 9);
				$criteria->compare('t.emp_no', $this->emp_no, true);
			} else {
				//$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
				$div = $division_id;
				$level_id = array(7);
				$criteria->compare('t.emp_no', $this->emp_no, true);
			}
		};

		//$criteria->order= 't.sppd_id DESC';
		$criteria->compare('t.sppd_id', $this->sppd_id, true);
		$criteria->order = 't.sppd_date DESC';
		$criteria->compare('rel_employee.division_id', $div);
		//if($dept_id != '') {
		//$criteria->compare('rel_employee.dept_id',$dept_id);
		//$criteria->addInCondition('rel_employee.dept_id',$dept);
		//};            
		$criteria->compare('rel_employee.level_id', $level_id);

		$criteria->compare('sppd_date', $this->sppd_date, true);

		$criteria->compare('purpose', $this->purpose, true);
		$criteria->compare('trip_id', $this->trip_id);
		$criteria->compare('instructed_by', $this->instructed_by, true);
		$criteria->compare('departure_date', $this->departure_date, true);
		$criteria->compare('arrival_date', $this->arrival_date, true);
		$criteria->compare('days', $this->days);
		$criteria->compare('meal_amount', $this->meal_amount);
		$criteria->compare('allowance_amount', $this->allowance_amount);
		$criteria->compare('hotel_amount', $this->hotel_amount);
		$criteria->compare('transport_amount', $this->transport_amount);
		$criteria->compare('others_amount', $this->others_amount);
		$criteria->compare('total_amount', $this->total_amount);
		$criteria->compare('advance_money', $this->advance_money);
		$criteria->compare('booking_ticket', $this->booking_ticket);
		$criteria->compare('created_date', $this->created_date, true);
		$criteria->compare('created_by', $this->created_by, true);
		$criteria->compare('modified_date', $this->modified_date, true);
		$criteria->compare('modified_by', $this->modified_by, true);
		//$criteria->compare('rel_trip_type.trip_name',$this->search_trip);
		//$criteria->compare('rel_ebt_dest.rel_to.city_name',$this->search_city);
		//$criteria->compare('rel_ebt_app.approver_flag',$approver_flag);
		//$criteria->compare('rel_ebt_app.approver_id',Yii::app()->user->id);

		//edit approver flag - glory 10 06 2016
		//$criteria->compare('rel_ebt_app.approver_flag', $approver_flag, true);

		//glory 9 des 2016 - filtering - nambahin criteria rel_employee.emp_name
		$criteria->compare('rel_employee.emp_name', $this->search_emp, true);

		//glory 31 Jan 2017 - nambahin field status (t.status) untuk filteran dropdown status
		//$criteria->compare('t.status',$this->status, true);

		$criteria->compare('rel_status.status', $this->search_status);
		$criteria->compare('serial_no', $this->serial_no);

		//print_r($criteria);
		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'defaultOrder' => 't.sppd_id DESC',
				'attributes' => array(
					/*'search_status'=>array(
                        'asc'=>'rel_status.status ASC',
                        'desc'=>'rel_status.status DESC',
                    ),
					'search_trip'=>array(
                        'asc'=>'rel_trip_type.trip_name ASC',
                        'desc'=>'rel_trip_type.trip_name DESC',
                    ),
					'search_city'=>array(
                        'asc'=>'rel_city.city_name ASC',
                        'desc'=>'rel_city.city_name DESC',
                    ),*/
					'*',
				),
			)
		));
	}

	public function searchByDivision__20240326($division_id, $dept_id, $level, $position, $emp_no) //backup oleh glory - 2 des 2016, problem : list pak ronal kosong
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria = new CDbCriteria;
		$criteria->together = true;
		$criteria->with = array('rel_employee');
		$approver_flag = array(1, 2);

		if ($division_id == 'JATAKE') {
			$div = array('ASD', 'DDT', 'STD', 'SPL', 'MDB', 'BPB');
			$level_id = array(2, 3, 4, 5);
			$criteria->compare('t.emp_no', $this->emp_no, true);
		} else {
			if ($level == 10) {
				$div = $division_id;
				$dept = explode(" ", $dept_id);
				//$div = explode(" ", $division_id);
				$level_id = array(2, 3, 4, 5, 6, 7);
				$criteria->compare('t.emp_no', $this->emp_no, true);
			} elseif ($level == 2) {
				$div = $division_id;
				$dept = explode(" ", $dept_id);
				//$div = explode(" ", $division_id);
				$level_id = array(2, 3, 4, 5, 6, 7);
				$criteria->compare('t.emp_no', $this->emp_no, true);
			} elseif ($level == 3) {
				$div = $division_id;
				$dept = explode(" ", $dept_id);
				//$div = explode(" ", $division_id);
				$level_id = array(3, 4, 5, 6, 7);
				$criteria->compare('t.emp_no', $this->emp_no, true);
			} elseif ($level == 4) {
				//$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
				$dept = explode(" ", $dept_id);
				$div = $division_id;
				$level_id = array(2, 3, 4, 5, 6, 7);
				$criteria->compare('t.emp_no', $this->emp_no, true);
			} elseif ($level == 5) {
				$dept = explode(" ", $dept_id);
				//$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
				$div = $division_id;
				$level_id = array(5, 6, 7);
				$criteria->compare('t.emp_no', $this->emp_no, true);
			} elseif ($level == 6) {
				if ($position == 4) {
					$dept = explode(" ", $dept_id);
					//$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
					$div = $division_id;
					$level_id = array(6);
					$criteria->compare('t.emp_no', $emp_no, true);
				} elseif ($position == 3) {
					$div = $division_id;
					$level_id = array(6, 7);
					$dept = explode(" ", $dept_id);
					$criteria->compare('t.emp_no', $this->emp_no, true);
				} else {
					//$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
					$div = $division_id;
					$level_id = array(6, 7);
					$dept = explode(" ", $dept_id);
					$criteria->compare('t.emp_no', $this->emp_no, true);
				}
			} elseif ($level == 9) {
				$dept = explode(" ", $dept_id);
				//$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
				$div = $division_id;
				$level_id = array(2, 3, 4);
				$criteria->compare('t.emp_no', $this->emp_no, true);
			} else {
				//$div = explode(" ", $division_id); //added by doris heryanto on Jun 15, 2017
				$div = $division_id;
				$level_id = array(7);
				$criteria->compare('t.emp_no', $this->emp_no, true);
			}
		};

		//$criteria->order= 't.sppd_id DESC';
		$criteria->compare('t.sppd_id', $this->sppd_id, true);
		$criteria->order = 't.sppd_date DESC';
		//$criteria->compare('rel_employee.division_id',$div);
		//if($dept_id != '') {
		//$criteria->compare('rel_employee.dept_id',$dept_id);
		//$criteria->addInCondition('rel_employee.dept_id',$dept);
		//};            
		$criteria->compare('rel_employee.level_id', $level_id);

		$criteria->compare('sppd_date', $this->sppd_date, true);

		$criteria->compare('purpose', $this->purpose, true);
		$criteria->compare('trip_id', $this->trip_id);
		$criteria->compare('instructed_by', $this->instructed_by, true);
		$criteria->compare('departure_date', $this->departure_date, true);
		$criteria->compare('arrival_date', $this->arrival_date, true);
		$criteria->compare('days', $this->days);
		$criteria->compare('meal_amount', $this->meal_amount);
		$criteria->compare('allowance_amount', $this->allowance_amount);
		$criteria->compare('hotel_amount', $this->hotel_amount);
		$criteria->compare('transport_amount', $this->transport_amount);
		$criteria->compare('others_amount', $this->others_amount);
		$criteria->compare('total_amount', $this->total_amount);
		$criteria->compare('advance_money', $this->advance_money);
		$criteria->compare('booking_ticket', $this->booking_ticket);
		$criteria->compare('created_date', $this->created_date, true);
		$criteria->compare('created_by', $this->created_by, true);
		$criteria->compare('modified_date', $this->modified_date, true);
		$criteria->compare('modified_by', $this->modified_by, true);
		//$criteria->compare('rel_trip_type.trip_name',$this->search_trip);
		//$criteria->compare('rel_ebt_dest.rel_to.city_name',$this->search_city);
		//$criteria->compare('rel_ebt_app.approver_flag',$approver_flag);
		//$criteria->compare('rel_ebt_app.approver_id',Yii::app()->user->id);

		//edit approver flag - glory 10 06 2016
		//$criteria->compare('rel_ebt_app.approver_flag', $approver_flag, true);

		//glory 9 des 2016 - filtering - nambahin criteria rel_employee.emp_name
		$criteria->compare('rel_employee.emp_name', $this->search_emp, true);

		//glory 31 Jan 2017 - nambahin field status (t.status) untuk filteran dropdown status
		//$criteria->compare('t.status',$this->status, true);

		$criteria->compare('rel_status.status', $this->search_status);
		$criteria->compare('serial_no', $this->serial_no);

		//print_r($criteria);
		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'defaultOrder' => 't.sppd_id DESC',
				'attributes' => array(
					/*'search_status'=>array(
                        'asc'=>'rel_status.status ASC',
                        'desc'=>'rel_status.status DESC',
                    ),
					'search_trip'=>array(
                        'asc'=>'rel_trip_type.trip_name ASC',
                        'desc'=>'rel_trip_type.trip_name DESC',
                    ),
					'search_city'=>array(
                        'asc'=>'rel_city.city_name ASC',
                        'desc'=>'rel_city.city_name DESC',
                    ),*/
					'*',
				),
			)
		));
	}

	public function searchByBooker($div_multiple)
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		//glory 2-12-2016 - mengubah parameter searchbydivision
		//sepertinya tidak muncul karena parameter get_dept_emp
		//jadinya sementara dihilangin dulu
		//modelnya juga parameternya dihilangin dulu, jadi cuma terima parameter user login
		//kodingan awal tidak dihapus

		$criteria = new CDbCriteria;

		$approver_flag = array('1', '2');
		$status_sppd = array('1', '2');
		$criteria->with = array('rel_employee');
		$criteria->together = true;
		//$criteria->with = array('rel_status','rel_ebt_app');
		//$criteria->order= 't.sppd_id DESC';
		//$div_multi = explode(" ", $div_multiple);
		$criteria->compare('rel_employee.division_multiple', $div_multiple, true);
		$criteria->order = 't.sppd_date DESC';
		//$criteria->compare('rel_employee.division_id',$division_id, true);
		//$criteria->compare('t.division_id',$division_id);
		//$criteria->compare('rel_employee.dept_id',$dept_id);
		//$criteria->addInCondition('rel_employee.division_multiple',$div_multi);
		//$criteria->compare('rel_employee.division_multiple',array($div_multiple),true);
		$criteria->compare('t.sppd_id', $this->sppd_id, true);
		$criteria->compare('sppd_date', $this->sppd_date, true);
		$criteria->compare('t.emp_no', $this->emp_no, true);
		$criteria->compare('purpose', $this->purpose, true);
		$criteria->compare('trip_id', $this->trip_id);
		$criteria->compare('instructed_by', $this->instructed_by, true);
		$criteria->compare('departure_date', $this->departure_date, true);
		$criteria->compare('arrival_date', $this->arrival_date, true);
		$criteria->compare('days', $this->days);
		$criteria->compare('meal_amount', $this->meal_amount);
		$criteria->compare('allowance_amount', $this->allowance_amount);
		$criteria->compare('hotel_amount', $this->hotel_amount);
		$criteria->compare('transport_amount', $this->transport_amount);
		$criteria->compare('others_amount', $this->others_amount);
		$criteria->compare('total_amount', $this->total_amount);
		$criteria->compare('advance_money', $this->advance_money);
		$criteria->compare('booking_ticket', $this->booking_ticket);
		$criteria->compare('created_date', $this->created_date, true);
		$criteria->compare('created_by', $this->created_by, true);
		$criteria->compare('modified_date', $this->modified_date, true);
		$criteria->compare('modified_by', $this->modified_by, true);
		$criteria->compare('rel_employee.emp_name', $this->search_emp, true);
		$criteria->compare('t.status', $status_sppd, true);
		//$criteria->compare('rel_trip_type.trip_name',$this->search_trip);
		//$criteria->compare('rel_city.city_name',$this->search_city);
		//$criteria->compare('rel_ebt_app.approver_flag',$approver_flag);
		//$criteria->compare('rel_ebt_app.approver_id',$approver_id);

		//edit approver flag - glory 10 06 2016
		//edit again by glory des 2 2016 - problem pak ronal tidak bisa approve sppd karena list tidak muncul (no result found)
		//yang diubah : nambahin filter approver id
		//sama nambahin filter status = 1, jadi yang muncul cuma yang statusnya on progress
		//$criteria->compare('rel_ebt_app.approver_flag', $approver_flag, true);
		//$criteria->compare('rel_ebt_app.approver_id', $userid, true);
		//$criteria->compare('t.status',1,true);

		$criteria->compare('t.status', $this->search_status);
		$criteria->compare('serial_no', $this->serial_no);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'defaultOrder' => 't.sppd_id DESC',
				'attributes' => array(
					'search_emp' => array(
						'asc' => 'rel_employee.emp_no ASC',
						'desc' => 'rel_employee.emp_no DESC',
					),
					'search_status' => array(
						'asc' => 'rel_status.status ASC',
						'desc' => 'rel_status.status DESC',
					),
					/*'search_trip'=>array(
                        'asc'=>'rel_trip_type.trip_name ASC',
                        'desc'=>'rel_trip_type.trip_name DESC',
                    ),
					'search_city'=>array(
                        'asc'=>'rel_city.city_name ASC',
                        'desc'=>'rel_city.city_name DESC',
                    ),*/
					'*',
				),
			)
		));
	}

	public function searchByEmpNo($emp_no)
	{
		$criteria = new CDbCriteria;
		$criteria->together = true;
		if (Yii::app()->globalFunction->get_user_role_id($emp_no) != 20) {
			$criteria->with = array('rel_status', 'rel_trip_type', 'rel_city', 'rel_employee');
		} else {
			$criteria->with = array('rel_status', 'rel_trip_type', 'rel_employee');
		}

		$criteria->order = 't.modified_date DESC';

		$criteria->compare('t.sppd_id', $this->sppd_id, true);
		$criteria->compare('t.sppd_date', $this->sppd_date, true);
		$criteria->compare('t.departure_date', $this->departure_date, true);
		$criteria->compare('t.arrival_date', $this->arrival_date, true);
		$criteria->compare('t.advance_money', $this->advance_money, true);
		$criteria->compare('rel_trip_type.trip_name', $this->search_trip, true);
		$criteria->compare('rel_employee.emp_name', $this->search_emp, true);

		if (Yii::app()->globalFunction->get_user_role_id($emp_no) != 20) {
			$criteria->compare('t.emp_no', $emp_no, true);
			$criteria->compare('t.status', $this->search_status);
			$criteria->compare('rel_city.city_name', $this->search_city, true);
		} else {
			$criteria->compare('t.emp_no', $this->emp_no, true);
			$criteria->addCondition('t.status <> :status_id');
			$criteria->params[':status_id'] = 0;
		}

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'defaultOrder' => 't.sppd_id DESC',
				'attributes' => array(
					'search_status' => array(
						'asc' => 'rel_status.status ASC',
						'desc' => 'rel_status.status DESC',
					),

					'search_trip' => array(
						'asc' => 'rel_trip_type.trip_name ASC',
						'desc' => 'rel_trip_type.trip_name DESC',
					),

					'*',
				),
			)
		));
	}

	public function listSPPDHead($emp_no)
	{
		$criteria = new CDbCriteria;
		$criteria->together = true;
		$criteria->with = array('rel_status', 'rel_trip_type', 'rel_city', 'rel_employee');

		$criteria->order = 't.modified_date DESC';

		$criteria->compare('t.sppd_id', $this->sppd_id, true);
		$criteria->compare('t.sppd_date', $this->sppd_date, true);
		$criteria->compare('t.departure_date', $this->departure_date, true);
		$criteria->compare('t.arrival_date', $this->arrival_date, true);
		$criteria->compare('t.emp_no', $this->emp_no, true);
		$criteria->compare('t.advance_money', $this->advance_money, true);
		$criteria->compare('rel_trip_type.trip_name', $this->search_trip, true);
		$criteria->compare('rel_city.city_name', $this->search_city, true);
		$criteria->compare('t.status', $this->search_status);
		$criteria->compare('rel_employee.emp_name', $this->search_emp, true);

		$endDate = date('Y-m-d');
		$startDate = date('Y-m-d', strtotime('-6 months'));
		$criteria->addBetweenCondition('t.created_date', $startDate, $endDate);

		// GET HEAD OF ADMIN
		$_sql = "SELECT * FROM hgs_mst_admin WHERE user_id = '" . $emp_no . "'";
		$_command = Yii::app()->db->createCommand($_sql);
		$_reader = $_command->queryAll();
		$heads = array();
		$no = 0;
		foreach ($_reader as $rows) {
			if ($no == 0) {
				$criteria->compare('t.created_by', $rows['head_id']);
			} else {
				$criteria->compare('t.created_by', $rows['head_id'], false, 'OR');
			}
			$no++;
		}

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'defaultOrder' => 't.sppd_id DESC',
				'attributes' => array(
					'search_status' => array(
						'asc' => 'rel_status.status ASC',
						'desc' => 'rel_status.status DESC',
					),

					'search_trip' => array(
						'asc' => 'rel_trip_type.trip_name ASC',
						'desc' => 'rel_trip_type.trip_name DESC',
					),

					'*',
				),
			)
		));
	}

	public function listSPPDDiv($emp_no)
	{
		$criteria = new CDbCriteria;
		$criteria->together = true;
		$criteria->with = array('rel_status', 'rel_trip_type', 'rel_employee');

		$criteria->order = 't.modified_date DESC';

		$criteria->compare('t.sppd_id', $this->sppd_id, true);
		$criteria->compare('t.sppd_date', $this->sppd_date, true);
		$criteria->compare('t.departure_date', $this->departure_date, true);
		$criteria->compare('t.arrival_date', $this->arrival_date, true);
		$criteria->compare('t.emp_no', $this->emp_no, true);
		$criteria->compare('t.advance_money', $this->advance_money, true);
		$criteria->compare('rel_trip_type.trip_name', $this->search_trip, true);
		//$criteria->compare('rel_city.city_name',$this->search_city,true);
		$criteria->compare('t.status', $this->search_status);
		$criteria->compare('rel_employee.emp_name', $this->search_emp, true);

		$div_id = Yii::app()->globalFunction->get_division_emp($emp_no);
		$criteria->compare('t.division_id', $div_id);

		//$endDate = date('Y-m-d');
		//$startDate = date('Y-m-d', strtotime('-6 months'));
		//$criteria->addBetweenCondition('t.created_date', $startDate, $endDate);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
			'sort' => array(
				'defaultOrder' => 't.sppd_id DESC',
				'attributes' => array(
					'search_status' => array(
						'asc' => 'rel_status.status ASC',
						'desc' => 'rel_status.status DESC',
					),

					'search_trip' => array(
						'asc' => 'rel_trip_type.trip_name ASC',
						'desc' => 'rel_trip_type.trip_name DESC',
					),

					'*',
				),
			)
		));
	}

	public function getLevelName($emp_no)
	{
		$select = "SELECT level_name FROM hgs_mst_employee e JOIN hgs_mst_job_level jl on e.level_id = jl.level_id WHERE e.emp_no = '$emp_no'";
		$conn = Yii::app()->db->createCommand($select);
		$conn->execute();

		return $conn->queryScalar();
	}

	public function getDivisionName($div_id)
	{
		$select = "SELECT division_name FROM ebt_trs_sppd e JOIN hgs_mst_division d ON e.division_id = d.division_id WHERE e.division_id = '$div_id'";
		$conn = Yii::app()->db->createCommand($select);
		$conn->execute();

		return $conn->queryScalar();
	}

	public function getLocationName($emp_no, $loc_id)
	{
		$select = "SELECT location_name FROM hgs_mst_employee e JOIN hgs_mst_location l ON e.location_id = l.location_id WHERE e.location_id = '$loc_id' AND e.emp_no = '$emp_no'";
		$conn = Yii::app()->db->createCommand($select);
		$conn->execute();

		return $conn->queryScalar();
	}

	public function showListSPPD($emp_no, $sppd_id)
	{
		$connection = Yii::app()->db;
		$command = $connection->createCommand("SELECT * FROM " . $this->tableName() . " 
            WHERE emp_no='" . $emp_no . "' AND status='2' AND sppd_id='" . $sppd_id . "'");
		$rows = $command->queryAll();
		return CHtml::listData($rows, 'sppd_id', 'sppd_id');
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return BusinessTravelModel the static model class
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function getStatus($id)
	{
		return Yii::app()->db->createCommand("SELECT status FROM tws_mst_status WHERE id = '$id'")->queryScalar();
	}

	public function getAllDataStatus_0()
	{
		$connection = Yii::app()->db;
		$sqlStatement2 = "
            SELECT
				ebt_trs_sppd.sppd_id,
				ebt_trs_sppd.sppd_date,
				hgs_mst_employee.emp_no,
				DATE_FORMAT(ebt_trs_sppd.sppd_date, '%m') AS `Month`,
				DATE_FORMAT(ebt_trs_sppd.sppd_date, '%Y') AS `Year`,
				ebt_trs_sppd.purpose,
				tws_mst_status.`status`,
				ebt_trs_sppd.division_id,
				ebt_trs_sppd_destination.departure_date,
				ebt_trs_sppd_destination.arrival_date,
				ebt_trs_sppd_destination.days,
				ebt_trs_sppd.meal_amount,
				ebt_trs_sppd.allowance_amount,
				ebt_trs_sppd.hotel_amount,
				(ebt_trs_sppd.meal_amount+ebt_trs_sppd.allowance_amount+ebt_trs_sppd.hotel_amount) AS `total_amount`
			FROM
				ebt_trs_sppd
			LEFT JOIN ebt_trs_sppd_destination ON ebt_trs_sppd.sppd_id = ebt_trs_sppd_destination.sppd_id
			LEFT JOIN hgs_mst_employee ON hgs_mst_employee.emp_no = ebt_trs_sppd.emp_no
			LEFT JOIN tws_mst_status ON ebt_trs_sppd.`status` = tws_mst_status.id
			WHERE
				ebt_trs_sppd_destination.departure_date BETWEEN '2023-07-01'
			AND '2023-12-31'
			AND ebt_trs_sppd.`status` != '0'
			ORDER BY
				departure_date ASC";

		$command2 = $connection->createCommand($sqlStatement2);
		$reader2 = $command2->query();

		return $reader2;
	}
}
