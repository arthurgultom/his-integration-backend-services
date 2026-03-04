public function actionExport() {
    $connection = Yii::app()->db;

    // Modified query to exclude already exported items (checked against fin_export_excel_log)
    // Replaces AdvanceMoneyModel::model()->getAllDataStatus_0() to include the exclusion logic directly
    $sql = "SELECT a.adv_mon_id, b.emp_name, c.division_name, 
             CONCAT( 
                 (SELECT hgs_mst_city.city_name FROM ebt_trs_sppd_destination INNER JOIN hgs_mst_city ON ebt_trs_sppd_destination.`from`  = hgs_mst_city.city_id WHERE ebt_trs_sppd_destination.sppd_id = a.sppd_id ORDER BY ebt_trs_sppd_destination.departure_date ASC LIMIT 1), 
                 ' - ', 
                 (SELECT hgs_mst_city.city_name FROM ebt_trs_sppd_destination INNER JOIN hgs_mst_city ON ebt_trs_sppd_destination.to = hgs_mst_city.city_id WHERE ebt_trs_sppd_destination.sppd_id = a.sppd_id ORDER BY ebt_trs_sppd_destination.departure_date DESC LIMIT 1) 
             ) AS 'trip', 
             (SELECT departure_date FROM ebt_trs_sppd_destination WHERE sppd_id = a.sppd_id ORDER BY departure_date ASC LIMIT 1) AS 'start_trip', 
             (SELECT arrival_date FROM ebt_trs_sppd_destination WHERE sppd_id = a.sppd_id ORDER BY departure_date DESC LIMIT 1) AS 'end_trip', 
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
             AND export_status = '0'
             AND a.adv_mon_id NOT IN (SELECT doc_no FROM fin_export_excel_log)";

    $command = $connection->createCommand($sql);
    $model = $command->queryAll();

    foreach($model as $row) {
        $id = $row['adv_mon_id'];
        
        $statement_adv = "UPDATE fin_trs_advance_money SET export_status = '1' WHERE adv_mon_id = :id";
        $command_adv = $connection->createCommand($statement_adv);
        $command_adv->bindValue(':id', $id);
        $command_adv->execute();
        
        $statement = "INSERT INTO fin_export_excel_log VALUES 
            (NULL, :id, :activity, :user, :date)";
        $command_log = $connection->createCommand($statement);
        $command_log->bindValue(':id', $id);
        $command_log->bindValue(':activity', Yii::app()->user->id." melakukan export pada ".date('Y-m-d H:i:s'));
        $command_log->bindValue(':user', Yii::app()->user->id);
        $command_log->bindValue(':date', date('Y-m-d H:i:s'));
        $command_log->execute();
    }
    
    Yii::app()->request->sendFile('pum.xls',
        $this->renderPartial('excel',array(
                'model'=>$model,
        ),true)
    );
}