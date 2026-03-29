<?php

class BusinessTravelDestinationController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';

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
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('index','view','create','update','admin','delete','delete2'),
				'users'=>array('@'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}
  
	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model = new BusinessTravelDestinationModel;
        $model_to = new BusinessTravelDestinationModel;
        
		$city = CityModel::model()->showList();
        
		$city_model = new CityModel('search');
        $city_model->unsetAttributes();  // clear any default values
        $city_model->attributes = $_GET['CityModel'];

		$transportation = TransportationTypeModel::model()->showList();
		$sppd_id = $_REQUEST['sppd_id'];
		
        // Check Trip Type
        $sqlTrip = "SELECT COUNT(*) FROM ebt_trs_sppd WHERE sppd_id='".$sppd_id."' AND (trip_id = 5 OR trip_id = 7)";
		$countTrip = Yii::app()->db->createCommand($sqlTrip)->queryScalar();
		
        // Check Total Destination
		$start_city_id = '';
		$start_city_name = '';
		$start_date = '';
		$start_time = '';
		$sqlDest = "SELECT COUNT(*) FROM ebt_trs_sppd_destination WHERE sppd_id='".$sppd_id."'";
		$countDest = Yii::app()->db->createCommand($sqlDest)->queryScalar();
		if($countDest > 0){
            // Get Last Destination
            $sqlLastDest = "SELECT * FROM ebt_trs_sppd_destination WHERE sppd_id='".$sppd_id."' ORDER BY dest_id DESC LIMIT 1";
		    $dataLastDest = Yii::app()->db->createCommand($sqlLastDest)->queryRow();
		    
		    $sqlGetCity = "SELECT * FROM hgs_mst_city WHERE city_id='".$dataLastDest['to']."' ORDER BY city_id ASC LIMIT 1";
		    $dataGetCity = Yii::app()->db->createCommand($sqlGetCity)->queryRow();
		    
		    $start_city_id = $dataLastDest['to'];
		    $start_city_name = $dataGetCity['city_name'];
		    $start_date = $dataLastDest['arrival_date'];
		    $start_time = $dataLastDest['arrival_time'];
		}

		$city_option_selected = array();
		$city_option_selected[Yii::app()->globalFunction->get_user_base(Yii::app()->user->id)] = array('selected'=>true);
		
		if(isset($_POST['BusinessTravelDestinationModel']))
		{
			$sql = "SELECT COUNT(*) FROM ebt_trs_sppd sppd, ebt_trs_sppd_destination dest
				where sppd.sppd_id=dest.sppd_id and sppd.emp_no='".Yii::app()->user->id."' and 
				dest.departure_date = '".$_POST['BusinessTravelDestinationModel']['departure_date']."' and (sppd.status = 1 or sppd.status = 2)";
			$count = Yii::app()->db->createCommand($sql)->queryScalar();
			
			$datetime1 = new DateTime($_POST['BusinessTravelDestinationModel']['departure_date'].' '.$_POST['BusinessTravelDestinationModel']['departure_time']);
            $datetime2 = new DateTime($_POST['BusinessTravelDestinationModel']['arrival_date'].' '.$_POST['BusinessTravelDestinationModel']['arrival_time']);
            $interval = $datetime1->diff($datetime2);
            $elapsed = $interval->format('%r');
            
			if ($count == 1) {
				Yii::app()->user->setFlash('success', '<b>You already have a business trip on that date.<b>');
				$this->redirect(array('businessTravelDestination/create','sppd_id'=>$sppd_id));
			} if($elapsed == '-' or ($datetime1 == $datetime2)){
                Yii::app()->user->setFlash('success', '<b>Ensure that the End Trip date and time occur after the Start Trip date and time.</b>');
				$this->redirect(array('businessTravelDestination/create','sppd_id'=>$sppd_id));
            } else {
				$model->attributes=$_POST['BusinessTravelDestinationModel'];
					
				// Untuk menghitung selisih hari berdasarkan tanggal berangkat dan tanggal kedatangan
				$days_trip = Yii::app()->globalFunction->getDeviationDateDays($model->departure_date, $model->arrival_date);
	
				// Untuk mengambil kode level dari karyawan 
				$level_emp = Yii::app()->globalFunction->get_level_emp(Yii::app()->user->id);
				
				// Untuk mengambil region dari suatu tujuan
				$region_dest = Yii::app()->globalFunction->get_region_city($model->to);
	
				// Untuk mengambil region dari suatu tujuan
				$province_dest = Yii::app()->globalFunction->get_province_id($model->to);
				
				// GET DATA SPPD
				$sqlSPPD = "SELECT * FROM ebt_trs_sppd WHERE sppd_id='".$sppd_id."'";
		        $dataSPPD = Yii::app()->db->createCommand($sqlSPPD)->queryRow();
	
				if ($days_trip > 13) 
				{
					// Untuk mengambil special allowance dari karyawan added 
					$allowance_emp = Yii::app()->globalFunction->get_special_allowance_emp($level_emp, $province_dest);

					// Untuk mengambil amount meal dari karyawan
					if($dataSPPD['trip_id'] == 7){
					    $meal_emp = 0;
					}else{
					    $meal_emp = Yii::app()->globalFunction->get_meal_emp_temp($level_emp, $province_dest);
					}
					
					// Untuk mengambil amount special_allowance+meal+accomodation dari karyawan
					$amount_emp = Yii::app()->globalFunction->get_amount_emp_temp($level_emp, $province_dest);

					// Untuk mengambil amount accomodation dari karyawan
		            if($dataSPPD['trip_id'] == 5 or $dataSPPD['trip_id'] == 7)
					{
						$hotel_emp = 0;
						$booking_hotel = 'No';
					} else {
    					$hotel_emp = Yii::app()->globalFunction->get_accomodation_emp($level_emp, $province_dest);
						$booking_hotel = 'Yes';
					}
		
					$total_allowance = $allowance_emp * ($days_trip + 1);
					$total_meal = $meal_emp * ($days_trip + 1);
					
					$total_hotel = $hotel_emp;
					
					$total_amount = $total_allowance + $total_meal + $total_hotel;
				} else {
					
					// Untuk mengambil amount allowance dari karyawan added 
					$allowance_emp = Yii::app()->globalFunction->get_allowance_emp($level_emp, $province_dest);
					
					// Untuk mengambil amount meal dari karyawan
					if($dataSPPD['trip_id'] == 7){
					    $meal_emp = 0;
					}else{
					    $meal_emp = Yii::app()->globalFunction->get_meal_emp($level_emp, $province_dest);
					}
					
					// Untuk mengambil amount allowance+meal dari karyawan
					$amount_emp = Yii::app()->globalFunction->get_amount_emp($level_emp, $province_dest);

					// Untuk mengambil amount allowance hotel dari karyawan
					if($dataSPPD['trip_id'] == 5 or $dataSPPD['trip_id'] == 7)
					{
						$hotel_emp = 0;
						$booking_hotel = 'No';
					} else {
						$hotel_emp = Yii::app()->globalFunction->get_hotel_emp($level_emp, $province_dest);
						$booking_hotel = 'Yes';
					}
					
					// Untuk mengambil amount allowance hotel dari karyawan
					//added by doris on 23 nov 2017 2:45 pm
					$count = BusinessTravelDestinationModel::model()->countByAttributes(array(
						'sppd_id'=> $sppd_id,
					));
					
					if ($count>0) {
						$total_allowance = $allowance_emp * $days_trip;
						$total_meal = $meal_emp * $days_trip;
						$total_hotel = $hotel_emp * $days_trip;
						$total_amount = $total_allowance + $total_meal + $total_hotel;
					} else {
						$total_allowance = $allowance_emp * ($days_trip + 1);
						$total_meal = $meal_emp * ($days_trip + 1);
						$total_hotel = $hotel_emp * $days_trip;
						$total_amount = $total_allowance + $total_meal + $total_hotel;
					}
				}
	
				// Set data berdasarkan attributes yang didapat dari suatu form
				$model->sppd_id = $sppd_id;
				$model->meal_amount = $total_meal;
				$model->allowance_amount = $total_allowance;
				$model->hotel_amount = $total_hotel;
				$model->total_amount = $total_amount;
				$model->days = $days_trip;
				$model->created_date = date('c');
				$model->created_by = Yii::app()->user->id;
				$model->modified_date = date('c');
				$model->modified_by = Yii::app()->user->id;
				
				// $model->types_of_trip = $_POST['BusinessTravelDestinationModel']['types_of_trip'];
				$model->types_of_trip = 'One Way';
				$model->booking_hotel = $booking_hotel;
	
				if($model->save()){
					
					// Simpan total allowance dan hotel dari karyawan
	
					$statement="UPDATE 
					                ebt_trs_sppd 
					            SET 
					                meal_amount = meal_amount + ".$total_meal.", 
					                allowance_amount = allowance_amount + ".$total_allowance.", 
					                hotel_amount = hotel_amount + ".$total_hotel.", 
					                total_amount = total_amount + ".$total_amount." 
					            WHERE 
					                sppd_id = '".$sppd_id."'";
					$command = Yii::app()->db->createCommand($statement);
					$command->execute();
	
					$this->redirect(array('businessTravel/update','id'=>$model->sppd_id));
				}
			}
		}

		$this->render('create',array(
			'model'=>$model,
			'city'=>$city,
			'city_model'=>$city_model,
			'city_option_selected'=>$city_option_selected,
			'transportation'=>$transportation,
			'countTrip'=>$countTrip,
			'start_city_id'=>$start_city_id,
		    'start_city_name'=>$start_city_name,
		    'start_date'=>$start_date,
		    'start_time'=>$start_time
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
		$city = CityModel::model()->showList();
		$transportation = TransportationTypeModel::model()->showList();

		$city_model = new CityModel('search');
        $city_model->unsetAttributes();
        $city_model->attributes = $_GET['CityModel'];
		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);
		
		$sppd_id = $_REQUEST['sppd_id'];
		
        // Check Destination
		$start_city_name = '';
		$dest_order = '';
		$orderDest = 0;
		$dest_id_first = 0;
        $sqlDest = "SELECT * FROM ebt_trs_sppd_destination WHERE sppd_id='".$sppd_id."' ORDER BY dest_id ASC";
        // die($sqlDest);
	    $readerDest = Yii::app()->db->createCommand($sqlDest)->queryAll();
	    foreach($readerDest as $dataDest){
	        $orderDest++;
	        if($orderDest == 1){
				$dest_id_first = $dataDest['dest_id'];
			}
	        // echo $dataDest['dest_id'].' == '.$id;
	        if($dataDest['dest_id'] == $id){
	            $dest_order = $orderDest;
	            if($orderDest > 1){
        	        $sqlGetCity = "SELECT * FROM hgs_mst_city WHERE city_id='".$model->from."' ORDER BY city_id ASC LIMIT 1";
        		    $dataGetCity = Yii::app()->db->createCommand($sqlGetCity)->queryRow();
        		    
        		    $start_city_name = $dataGetCity['city_name'];
        	    }
	        }
	    }
	    // die($dest_order.' - '.$start_city_name);

		if(isset($_POST['BusinessTravelDestinationModel']))
		{
			$model->attributes=$_POST['BusinessTravelDestinationModel'];

			// Untuk menghitung selisih hari berdasarkan tanggal berangkat dan tanggal kedatangan
			$days_trip = Yii::app()->globalFunction->getDeviationDateDays($model->departure_date, $model->arrival_date);

			// Untuk mengambil kode level dari karyawan 
			$level_emp = Yii::app()->globalFunction->get_level_emp(Yii::app()->user->id);
			
			// Untuk mengambil region dari suatu tujuan
			$region_dest = Yii::app()->globalFunction->get_region_city($model->to);

            // Untuk mengambil region dari suatu tujuan
			$province_dest = Yii::app()->globalFunction->get_province_id($model->to);

            if ($days_trip > 13) 
            {
    			// Untuk mengambil special allowance dari karyawam added 
    			$allowance_emp = Yii::app()->globalFunction->get_special_allowance_emp($level_emp, $province_dest);
                
                // Untuk mengambil amount meal dari karyawam
    			$meal_emp = Yii::app()->globalFunction->get_meal_emp_temp($level_emp, $province_dest);
                
                // Untuk mengambil amount special_allowance+meal+accomodation dari karyawam
    			$amount_emp = Yii::app()->globalFunction->get_amount_emp_temp($level_emp, $province_dest);
    
    			// Untuk mengambil amount accomodation dari karyawan
    			$hotel_emp = Yii::app()->globalFunction->get_accomodation_emp($level_emp, $province_dest);
    
    			if($model->dest_id == $dest_id_first){
					$total_allowance = $allowance_emp * ($days_trip + 1);
					$total_meal = $meal_emp * ($days_trip + 1);
					//$total_amount  = $amount_emp * ($days_trip + 1);
				}else{
					$total_allowance = $allowance_emp * $days_trip;
					$total_meal = $meal_emp * $days_trip;
					//$total_amount  = $amount_emp * $days_trip;
				}
                
    			$total_hotel = $hotel_emp;
    			
    			$total_amount = $total_allowance + $total_meal + $hotel_emp;
            } else {
                
                // Untuk mengambil amount allowance dari karyawam added 
    			$allowance_emp = Yii::app()->globalFunction->get_allowance_emp($level_emp, $province_dest);
                
                // Untuk mengambil amount meal dari karyawam
    			$meal_emp = Yii::app()->globalFunction->get_meal_emp($level_emp, $province_dest);
                
                // Untuk mengambil amount allowance+meal dari karyawam
    			$amount_emp = Yii::app()->globalFunction->get_amount_emp($level_emp, $province_dest);
    
    			// Untuk mengambil amount allowance hotel dari karyawan
    			$hotel_emp = Yii::app()->globalFunction->get_hotel_emp($level_emp, $province_dest);
    
    			if($model->dest_id == $dest_id_first){
					$total_allowance = $allowance_emp * ($days_trip + 1);
					$total_meal = $meal_emp * ($days_trip + 1);
					//$total_amount  = $amount_emp * ($days_trip + 1);
				}else{
					$total_allowance = $allowance_emp * $days_trip;
					$total_meal = $meal_emp * $days_trip;
					//$total_amount  = $amount_emp * $days_trip;
				}
                
    			$total_hotel = $hotel_emp * $days_trip;
    			
    			$total_amount = $total_allowance + $total_meal + $total_hotel;                                                
            }
		        
	        if($total_hotel == ''){
	            $total_hotel = 0;   
	        }
    
			// Set data berdasarkan attributes yang didapat dari suatu form
			// $model->sppd_id = $sppd_id;
			$model->allowance_amount = $total_allowance;
			$model->meal_amount = $total_meal;
			$model->hotel_amount = $total_hotel;
			$model->total_amount = $total_amount;

			$model->days = Yii::app()->globalFunction->getDeviationDateDays($model->departure_date, $model->arrival_date);
            $model->modified_date = date('c');
            $model->modified_by = Yii::app()->user->id;
			
			//glory, 28 jan 2016
			//jenis trip
			//$model->types_of_trip = $_POST['BusinessTravelDestinationModel']['types_of_trip'];

			if($model->save()){
			    
			    // Update Next Destination
                $sqlDest = "SELECT * FROM ebt_trs_sppd_destination WHERE sppd_id='".$model->sppd_id."' AND dest_id > '".$id."' ORDER BY dest_id ASC LIMIT 1";
                $dataDest = Yii::app()->db->createCommand($sqlDest)->queryRow();
        	    if(isset($dataDest['dest_id'])){
        	        $statementDest = "UPDATE ebt_trs_sppd_destination SET `from` = ".$model->to.", departure_date = '".$model->arrival_date."', departure_time = '".$model->arrival_time."' WHERE dest_id = '".$dataDest['dest_id']."'";
    				$commandDest = Yii::app()->db->createCommand($statementDest);
    				$commandDest->execute();
        	    }
        	    
        	    // SUMMARIZE TOTAL
        	    $sqlSum = "SELECT 
                    SUM(meal_amount) sum_meal, 
                    SUM(allowance_amount) sum_allowance, 
                    SUM(hotel_amount) sum_hotel, 
                    SUM(total_amount) sum_total
                    FROM ebt_trs_sppd_destination 
                    WHERE sppd_id = '".$model->sppd_id."'";
                $dataSum = Yii::app()->db->createCommand($sqlSum)->queryRow();
			
				//$statement = "UPDATE ebt_trs_sppd SET allowance_amount = allowance_amount + ".$total_allowance.", hotel_amount = hotel_amount + ".$total_hotel.", total_amount = total_amount + ".$total_amount." WHERE sppd_id = '".$model->sppd_id."'";
                $statement = "UPDATE ebt_trs_sppd SET meal_amount = ".$dataSum['sum_meal'].", allowance_amount = ".$dataSum['sum_allowance'].", hotel_amount = ".$dataSum['sum_hotel'].", total_amount = ".$dataSum['sum_total']." WHERE sppd_id = '".$sppd_id."'";
				$command = Yii::app()->db->createCommand($statement);
				$command->execute();
				$this->redirect(array('businessTravel/update','id'=>$model->sppd_id));
			
			}
			
		}

		$this->render('update',array(
			'model'=>$model,
			'city'=>$city,
			'city_model'=>$city_model,
			'transportation'=>$transportation,
			'start_city_name'=>$start_city_name,
		    'dest_order'=>$dest_order
		));
	}

    /**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
	    $statement1 = "SELECT * FROM ebt_trs_sppd_destination WHERE dest_id = '".$id."'";
		$command1 = Yii::app()->db->createCommand($statement1);
		$reader1 = $command1->queryRow();
		
		// Check Count Data
        $sqlCount = "SELECT COUNT(*) FROM ebt_trs_sppd_destination WHERE sppd_id='".$reader1['sppd_id']."'";
		$countData = Yii::app()->db->createCommand($sqlCount)->queryScalar();
		
		if($countData > 1){
		    $this->loadModel($id)->delete();
		    
		    // RECALCULATE TRIPS
		    $sqlDest = "SELECT * FROM ebt_trs_sppd_destination WHERE sppd_id='".$reader1['sppd_id']."' ORDER BY dest_id ASC";
            $readerDest = Yii::app()->db->createCommand($sqlDest)->queryAll();
            $indexDest = 0;
    	    foreach($readerDest as $dataDest){
    	        $days_trip = Yii::app()->globalFunction->getDeviationDateDays($dataDest['departure_date'], $dataDest['arrival_date']);
                $level_emp = Yii::app()->globalFunction->get_level_emp(Yii::app()->user->id);
    			$region_dest = Yii::app()->globalFunction->get_region_city($dataDest['to']);
    			$province_dest = Yii::app()->globalFunction->get_province_id($dataDest['to']);
                if ($days_trip > 13) 
                {
        			$allowance_emp = Yii::app()->globalFunction->get_special_allowance_emp($level_emp, $province_dest);
        			$meal_emp = Yii::app()->globalFunction->get_meal_emp_temp($level_emp, $province_dest);
        			$amount_emp = Yii::app()->globalFunction->get_amount_emp_temp($level_emp, $province_dest);
        			$hotel_emp = Yii::app()->globalFunction->get_accomodation_emp($level_emp, $province_dest);
        			if($indexDest == 0){
    					$total_allowance = $allowance_emp * ($days_trip + 1);
    					$total_meal = $meal_emp * ($days_trip + 1);
    				}else{
    					$total_allowance = $allowance_emp * $days_trip;
    					$total_meal = $meal_emp * $days_trip;
    				}
        			$total_hotel = $hotel_emp;
        			$total_amount = $total_allowance + $total_meal + $hotel_emp;
                } else {
        			$allowance_emp = Yii::app()->globalFunction->get_allowance_emp($level_emp, $province_dest);
        			$meal_emp = Yii::app()->globalFunction->get_meal_emp($level_emp, $province_dest);
        			$amount_emp = Yii::app()->globalFunction->get_amount_emp($level_emp, $province_dest);
        			$hotel_emp = Yii::app()->globalFunction->get_hotel_emp($level_emp, $province_dest);
        			if($indexDest == 0){
    					$total_allowance = $allowance_emp * ($days_trip + 1);
    					$total_meal = $meal_emp * ($days_trip + 1);
    				}else{
    					$total_allowance = $allowance_emp * $days_trip;
    					$total_meal = $meal_emp * $days_trip;
    				}
        			$total_hotel = $hotel_emp * $days_trip;
        			$total_amount = $total_allowance + $total_meal + $total_hotel;                                                
                }
    	        if($total_hotel == ''){
    	            $total_hotel = 0;   
    	        }
    	        
    	        $statement = "UPDATE ebt_trs_sppd_destination SET 
    	            meal_amount = ".$total_meal.", 
    	            allowance_amount = ".$total_allowance.", 
    	            hotel_amount = ".$total_hotel.", 
    	            total_amount = ".$total_amount." 
    	            WHERE dest_id = '".$dataDest['dest_id']."'";
				$command = Yii::app()->db->createCommand($statement);
				$command->execute();
    	        
    	        $indexDest++;
    	    }
    
    		// SUMMARIZE TOTAL
    		$sqlSum = "SELECT 
    			SUM(meal_amount) sum_meal, 
    			SUM(allowance_amount) sum_allowance, 
    			SUM(hotel_amount) sum_hotel, 
    			SUM(total_amount) sum_total
    			FROM ebt_trs_sppd_destination 
    			WHERE sppd_id = '".$reader1['sppd_id']."'";
    		$dataSum = Yii::app()->db->createCommand($sqlSum)->queryRow();
    
    		// Proses untuk update total amount yang ada di dokumen header perjalanan dinas
    		$statement2 = "UPDATE ebt_trs_sppd SET meal_amount = ".$dataSum['sum_meal'].", allowance_amount = ".$dataSum['sum_allowance'].", hotel_amount = ".$dataSum['sum_hotel'].", total_amount = ".$dataSum['sum_total']." WHERE sppd_id = '".$reader1['sppd_id']."'";
    		$command2 = Yii::app()->db->createCommand($statement2);
    		$command2->execute();
    
    		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
    		// if(!isset($_GET['ajax']))
    		//	$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
    		return true;
		}else{
		    return false;
		}
	}
	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */

	public function actionDelete2($id) //added by doris on Dec 11, 2017
	{

		// Untuk mengambil total amount yang sudah tersimpan ke dalam dokumen detil dari perjalanan dinas
		// agar dapat digunakan untuk mengurangi angka total amount allowance yang sudah masuk ke dalam 
		// dokumen header perjalanan dinas
		$statement1 = "SELECT * FROM ebt_trs_sppd_destination WHERE dest_id = '".$id."'";
		$command1 = Yii::app()->db->createCommand($statement1);
		$reader1 = $command1->queryRow();
		$meal_amount = $reader1['meal_amount'] * $reader1['days'];
		$allowance_amount = $reader1['allowance_amount'] * $reader1['days'];
		$hotel_amount = $reader1['hotel_amount'];
		$total_amount = $reader1['total_amount'];

		// Proses untuk mengurangi total amount yang ada di dokumen header perjalanan dinas
		$statement2 = "UPDATE ebt_trs_sppd SET 
			meal_amount = meal_amount - ".$meal_amount.", 
			allowance_amount = allowance_amount - ".$allowance_amount.", 
			hotel_amount = hotel_amount - ".$hotel_amount.", 
			total_amount = total_amount - ".($meal_amount + $allowance_amount + $hotel_amount)." 
			WHERE sppd_id = '".$reader1['sppd_id']."'";
		$command2 = Yii::app()->db->createCommand($statement2);
		$command2->execute();

		$this->loadModel($id)->delete();

		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}
	
	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$dataProvider=new CActiveDataProvider('BusinessTravelDestinationModel');
		$this->render('index',array(
			'dataProvider'=>$dataProvider,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new BusinessTravelDestinationModel('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['BusinessTravelDestinationModel']))
			$model->attributes=$_GET['BusinessTravelDestinationModel'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return BusinessTravelDestinationModel the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=BusinessTravelDestinationModel::model()->findByPk($id);

		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param BusinessTravelDestinationModel $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='business-travel-destination-model-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
