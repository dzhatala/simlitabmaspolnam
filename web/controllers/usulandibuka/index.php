<?php

/*
 * This file is part of the CRUD Admin Generator project.
 *
 * Author: Jon Segador <jonseg@gmail.com>
 * Web: http://crud-admin-generator.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;

$app->match('/usulandibuka/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
    $start = 0;
    $vars = $request->query->all();
    $qsStart = (int)$vars["start"];
    $search = $vars["search"];
    $order = $vars["order"];
    $columns = $vars["columns"];
    $qsLength = (int)$vars["length"];    
    
    if($qsStart) {
        $start = $qsStart;
    }    
	
    $index = $start;   
    $rowsPerPage = $qsLength;
       
    $rows = array();
    
    $searchValue = $search['value'];
    $orderValue = $order[0];
    
    $orderClause = "";
    if($orderValue) {
        $orderClause = " ORDER BY ". $columns[(int)$orderValue['column']]['data'] . " " . $orderValue['dir'];
    }
    
    $table_columns = array(
		'USULANDIBUKA_ID', 
		'TAHUN_USULAN_ID', 
		'TIPE_USULAN_ID', 
		'NAMA', 
		'TANGGAL_BUKA', 
		'BATASAKHIR', 
		'STATUS', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(11)', 
		'int(11)', 
		'varchar(255)', 
		'date', 
		'date', 
		'int(11)', 

    );    
    
	
    $whereClause = "";
    $transform_text_to_key=""; /**  ... to enable search for externals ...**/
	
	/** find externals fields for search key and passing it to like as and id **/
	
	/**Creating enabler .. for  usulandibuka:tahun_usulan_id  **/

	if ($searchValue!==""){
	    $search_sql = "SELECT `TAHUN_USULAN_ID` FROM `tahun_usulan` WHERE `TAHUN_USULAN` LIKE '%". $searchValue . "%'" ; 
	    $search_rows = array(); $search_rows = $app['db']->fetchAll($search_sql);
	    //error_log("#####tahun_usulan_id=>tahun_usulan######".count($search_row));
	    if(count($search_rows)>0){
	      foreach($search_rows as $search_row)  { 
	         $transform_text_to_key .= " OR  tahun_usulan_id=".$search_row['TAHUN_USULAN_ID']; 
	      } 
	    }
	 } 

	/**Creating enabler .. for  usulandibuka:tipe_usulan_id  **/

	if ($searchValue!==""){
	    $search_sql = "SELECT `TIPE_USULAN_ID` FROM `tipeusulan` WHERE `NAMA` LIKE '%". $searchValue . "%'" ; 
	    $search_rows = array(); $search_rows = $app['db']->fetchAll($search_sql);
	    //error_log("#####tipe_usulan_id=>tipeusulan######".count($search_row));
	    if(count($search_rows)>0){
	      foreach($search_rows as $search_row)  { 
	         $transform_text_to_key .= " OR  tipe_usulan_id=".$search_row['TIPE_USULAN_ID']; 
	      } 
	    }
	 } 

	/**Creating enabler .. for  usulandibuka:status  **/

	if ($searchValue!==""){
	    $search_sql = "SELECT `OPTIONSSTATUSUSULANDIBUKA_ID` FROM `optionsstatususulandibuka` WHERE `NAME` LIKE '%". $searchValue . "%'" ; 
	    $search_rows = array(); $search_rows = $app['db']->fetchAll($search_sql);
	    //error_log("#####status=>optionsstatususulandibuka######".count($search_row));
	    if(count($search_rows)>0){
	      foreach($search_rows as $search_row)  { 
	         $transform_text_to_key .= " OR  status=".$search_row['OPTIONSSTATUSUSULANDIBUKA_ID']; 
	      } 
	    }
	 } 


    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE ( 1 AND ";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        //external search  version
		$whereClause =  $whereClause . "   usulandibuka.".$col . " LIKE '%". $searchValue ."%' ".$transform_text_to_key;
        
		//non external search version ...
		//$whereClause =  $whereClause . "   usulandibuka.".$col . " LIKE '%". $searchValue ."%' ";
        
        $i = $i + 1;
    }
	$whereClause .= " ) ";
    
	
    
	
	
	
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `usulandibuka`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `usulandibuka`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'TAHUN_USULAN_ID'){
			    $findexternal_sql = 'SELECT `TAHUN_USULAN` FROM `tahun_usulan` WHERE `TAHUN_USULAN_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['TAHUN_USULAN'];
			}
			else if($table_columns[$i] == 'TIPE_USULAN_ID'){
			    $findexternal_sql = 'SELECT `NAMA` FROM `tipeusulan` WHERE `TIPE_USULAN_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['NAMA'];
			}
			else if($table_columns[$i] == 'STATUS'){
			    $findexternal_sql = 'SELECT `NAME` FROM `optionsstatususulandibuka` WHERE `OPTIONSSTATUSUSULANDIBUKA_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['NAME'];
			}
			else{
			    $rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
			}


        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});




/* Download blob img */
$app->match('/usulandibuka/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . usulandibuka . " WHERE ".$idfldname." = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($rowid));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('menu_list'));
    }

    header('Content-Description: File Transfer');
    header('Content-Type: image/jpeg');
    header("Content-length: ".strlen( $row_sql[$fieldname] ));
    header('Expires: 0');
    header('Cache-Control: public');
    header('Pragma: public');
    ob_clean();    
    echo $row_sql[$fieldname];
    exit();
   
    
});



$app->match('/usulandibuka', function () use ($app) {
    
	$table_columns = array(
		'USULANDIBUKA_ID', 
		'TAHUN_USULAN_ID', 
		'TIPE_USULAN_ID', 
		'NAMA', 
		'TANGGAL_BUKA', 
		'BATASAKHIR', 
		'STATUS', 

    );
	
	/**translating here ...**/
	$tr_table_columns=array();
	foreach ($table_columns as $col){
		//var_dump($col) ;die;
		//array_push($tr_table_columns,$app['translator']->trans("usulandibuka".$col));
		array_push($tr_table_columns,$col);
	}
	$table_columns=$tr_table_columns;
	/****/

    $primary_key = "USULANDIBUKA_ID";	

    return $app['twig']->render('usulandibuka/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('usulandibuka_list');



$app->match('/usulandibuka/create', function () use ($app) {
    
    $initial_data = array(
		'TAHUN_USULAN_ID' => '', 
		'TIPE_USULAN_ID' => '', 
		'NAMA' => '', 
		'TANGGAL_BUKA' => '', 
		'BATASAKHIR' => '', 
		'STATUS' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$field_nullable= true  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	$options = array();
	$findexternal_sql = 'SELECT tahun_usulan.TAHUN_USULAN_ID, tahun_usulan.TAHUN_USULAN FROM tahun_usulan  '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['TAHUN_USULAN_ID']] = $findexternal_row['TAHUN_USULAN'];
	}
	if(count($options) > 0){
	    $form = $form->add('TAHUN_USULAN_ID', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('TAHUN_USULAN_ID', 'text', array_merge(array('required' => true),$field_default_ro));
	}

	$field_nullable= true  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	$options = array();
	$findexternal_sql = 'SELECT tipeusulan.TIPE_USULAN_ID, tipeusulan.NAMA FROM tipeusulan  '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['TIPE_USULAN_ID']] = $findexternal_row['NAMA'];
	}
	if(count($options) > 0){
	    $form = $form->add('TIPE_USULAN_ID', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('TIPE_USULAN_ID', 'text', array_merge(array('required' => true),$field_default_ro));
	}

	$field_nullable= true  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	$options = array();
	$findexternal_sql = 'SELECT optionsstatususulandibuka.OPTIONSSTATUSUSULANDIBUKA_ID, optionsstatususulandibuka.NAME FROM optionsstatususulandibuka  '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['OPTIONSSTATUSUSULANDIBUKA_ID']] = $findexternal_row['NAME'];
	}
	if(count($options) > 0){
	    $form = $form->add('STATUS', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('STATUS', 'text', array_merge(array('required' => true),$field_default_ro));
	}



	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('NAMA', 'text', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		  $field_default_ro =array_merge($field_default_ro,array('data' => date("Y-m-d"))) ; 
	} 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array_merge($field_default_ro,array('read_only' => true )) ; 
	} 
	$form = $form->add('TANGGAL_BUKA', 'text', 
 		  $field_default_ro);
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		  $field_default_ro =array_merge($field_default_ro,array('data' => date("Y-m-d"))) ; 
	} 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array_merge($field_default_ro,array('read_only' => true )) ; 
	} 
	$form = $form->add('BATASAKHIR', 'text', 
 		  $field_default_ro);


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
			
            $update_query = "INSERT INTO `usulandibuka` (`TAHUN_USULAN_ID`, `TIPE_USULAN_ID`, `NAMA`, `TANGGAL_BUKA`, `BATASAKHIR`, `STATUS`) VALUES (?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['TAHUN_USULAN_ID'], $data['TIPE_USULAN_ID'], $data['NAMA'], $data['TANGGAL_BUKA'], $data['BATASAKHIR'], $data['STATUS']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'usulandibuka created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('usulandibuka_list'));

        }
    }

    return $app['twig']->render('usulandibuka/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('usulandibuka_create');



$app->match('/usulandibuka/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `usulandibuka` WHERE `USULANDIBUKA_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('usulandibuka_list'));
    }

    
    $initial_data = array(
		'TAHUN_USULAN_ID' => $row_sql['TAHUN_USULAN_ID'], 
		'TIPE_USULAN_ID' => $row_sql['TIPE_USULAN_ID'], 
		'NAMA' => $row_sql['NAMA'], 
		'TANGGAL_BUKA' => $row_sql['TANGGAL_BUKA'], 
		'BATASAKHIR' => $row_sql['BATASAKHIR'], 
		'STATUS' => $row_sql['STATUS'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$field_nullable= true  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	$options = array();
	$findexternal_sql = 'SELECT tahun_usulan.TAHUN_USULAN_ID, tahun_usulan.TAHUN_USULAN FROM tahun_usulan  '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['TAHUN_USULAN_ID']] = $findexternal_row['TAHUN_USULAN'];
	}
	if(count($options) > 0){
	    $form = $form->add('TAHUN_USULAN_ID', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('TAHUN_USULAN_ID', 'text', array_merge(array('required' => true),$field_default_ro));
	}

	$field_nullable= true  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	$options = array();
	$findexternal_sql = 'SELECT tipeusulan.TIPE_USULAN_ID, tipeusulan.NAMA FROM tipeusulan  '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['TIPE_USULAN_ID']] = $findexternal_row['NAMA'];
	}
	if(count($options) > 0){
	    $form = $form->add('TIPE_USULAN_ID', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('TIPE_USULAN_ID', 'text', array_merge(array('required' => true),$field_default_ro));
	}

	$field_nullable= true  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	$options = array();
	$findexternal_sql = 'SELECT optionsstatususulandibuka.OPTIONSSTATUSUSULANDIBUKA_ID, optionsstatususulandibuka.NAME FROM optionsstatususulandibuka  '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['OPTIONSSTATUSUSULANDIBUKA_ID']] = $findexternal_row['NAME'];
	}
	if(count($options) > 0){
	    $form = $form->add('STATUS', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('STATUS', 'text', array_merge(array('required' => true),$field_default_ro));
	}


	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('NAMA', 'text', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		  $field_default_ro =array_merge($field_default_ro,array('data' => date("Y-m-d"))) ; 
	} 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array_merge($field_default_ro,array('read_only' => true )) ; 
	} 
	$form = $form->add('TANGGAL_BUKA', 'text', 
 		  $field_default_ro);
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	if (strpos($app['request']->getRequestUri(),"create")!==false){
		  $field_default_ro =array_merge($field_default_ro,array('data' => date("Y-m-d"))) ; 
	} 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array_merge($field_default_ro,array('read_only' => true )) ; 
	} 
	$form = $form->add('BATASAKHIR', 'text', 
 		  $field_default_ro);


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
			

            $update_query = "UPDATE `usulandibuka` SET `TAHUN_USULAN_ID` = ?, `TIPE_USULAN_ID` = ?, `NAMA` = ?, `TANGGAL_BUKA` = ?, `BATASAKHIR` = ?, `STATUS` = ? WHERE `USULANDIBUKA_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['TAHUN_USULAN_ID'], $data['TIPE_USULAN_ID'], $data['NAMA'], $data['TANGGAL_BUKA'], $data['BATASAKHIR'], $data['STATUS'], $id));            

	
            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'usulandibuka edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('usulandibuka_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('usulandibuka/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('usulandibuka_edit');



$app->match('/usulandibuka/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `usulandibuka` WHERE `USULANDIBUKA_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `usulandibuka` WHERE `USULANDIBUKA_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'usulandibuka deleted!',
            )
        );
    }
    else{
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );  
    }

    return $app->redirect($app['url_generator']->generate('usulandibuka_list'));

})
->bind('usulandibuka_delete');






