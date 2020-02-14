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

$app->match('/user_details/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'USER_DETAIL_ID', 
		'USERLOGIN_ID', 
		'NAME', 
		'EMAIL', 
		'MOBILE', 
		'ADDRESS', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(11)', 
		'varchar(255)', 
		'varchar(255)', 
		'varchar(255)', 
		'text', 

    );    
    
	
    $whereClause = "";
    $transform_text_to_key=""; /**  ... to enable search for externals ...**/
	
	/** find externals fields for search key and passing it to like as and id **/
	
	/**Creating enabler .. for  user_details:userlogin_id  **/

	if ($searchValue!==""){
	    $search_sql = "SELECT `USERLOGIN_ID` FROM `userlogin` WHERE `LOGIN` LIKE '%". $searchValue . "%'" ; 
	    $search_rows = array(); $search_rows = $app['db']->fetchAll($search_sql);
	    //error_log("#####userlogin_id=>userlogin######".count($search_row));
	    if(count($search_rows)>0){
	      foreach($search_rows as $search_row)  { 
	         $transform_text_to_key .= " OR  userlogin_id=".$search_row['USERLOGIN_ID']; 
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
		$whereClause =  $whereClause . "   user_details.".$col . " LIKE '%". $searchValue ."%' ".$transform_text_to_key;
        
		//non external search version ...
		//$whereClause =  $whereClause . "   user_details.".$col . " LIKE '%". $searchValue ."%' ";
        
        $i = $i + 1;
    }
	$whereClause .= " ) ";
    
	
    
	
	
	
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `user_details`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `user_details`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'USERLOGIN_ID'){
			    $findexternal_sql = 'SELECT `LOGIN` FROM `userlogin` WHERE `USERLOGIN_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['LOGIN'];
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
$app->match('/user_details/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . user_details . " WHERE ".$idfldname." = ?";
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



$app->match('/user_details', function () use ($app) {
    
	$table_columns = array(
		'USER_DETAIL_ID', 
		'USERLOGIN_ID', 
		'NAME', 
		'EMAIL', 
		'MOBILE', 
		'ADDRESS', 

    );

    $primary_key = "USER_DETAIL_ID";	

    return $app['twig']->render('user_details/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('user_details_list');



$app->match('/user_details/create', function () use ($app) {
    
    $initial_data = array(
		'USERLOGIN_ID' => '', 
		'NAME' => '', 
		'EMAIL' => '', 
		'MOBILE' => '', 
		'ADDRESS' => '', 

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
	$findexternal_sql = 'SELECT userlogin.USERLOGIN_ID, userlogin.LOGIN FROM userlogin  '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['USERLOGIN_ID']] = $findexternal_row['LOGIN'];
	}
	if(count($options) > 0){
	    $form = $form->add('USERLOGIN_ID', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('USERLOGIN_ID', 'text', array_merge(array('required' => true),$field_default_ro));
	}



	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('NAME', 'text', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('EMAIL', 'text', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('MOBILE', 'text', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('ADDRESS', 'textarea', array_merge(array('required' => false),$field_default_ro));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
			
            $update_query = "INSERT INTO `user_details` (`USERLOGIN_ID`, `NAME`, `EMAIL`, `MOBILE`, `ADDRESS`) VALUES (?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['USERLOGIN_ID'], $data['NAME'], $data['EMAIL'], $data['MOBILE'], $data['ADDRESS']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'user_details created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('user_details_list'));

        }
    }

    return $app['twig']->render('user_details/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('user_details_create');



$app->match('/user_details/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `user_details` WHERE `USER_DETAIL_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('user_details_list'));
    }

    
    $initial_data = array(
		'USERLOGIN_ID' => $row_sql['USERLOGIN_ID'], 
		'NAME' => $row_sql['NAME'], 
		'EMAIL' => $row_sql['EMAIL'], 
		'MOBILE' => $row_sql['MOBILE'], 
		'ADDRESS' => $row_sql['ADDRESS'], 

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
	$findexternal_sql = 'SELECT userlogin.USERLOGIN_ID, userlogin.LOGIN FROM userlogin  '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['USERLOGIN_ID']] = $findexternal_row['LOGIN'];
	}
	if(count($options) > 0){
	    $form = $form->add('USERLOGIN_ID', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('USERLOGIN_ID', 'text', array_merge(array('required' => true),$field_default_ro));
	}


	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('NAME', 'text', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('EMAIL', 'text', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('MOBILE', 'text', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('ADDRESS', 'textarea', array_merge(array('required' => false),$field_default_ro));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
			

            $update_query = "UPDATE `user_details` SET `USERLOGIN_ID` = ?, `NAME` = ?, `EMAIL` = ?, `MOBILE` = ?, `ADDRESS` = ? WHERE `USER_DETAIL_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['USERLOGIN_ID'], $data['NAME'], $data['EMAIL'], $data['MOBILE'], $data['ADDRESS'], $id));            

	
            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'user_details edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('user_details_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('user_details/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('user_details_edit');



$app->match('/user_details/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `user_details` WHERE `USER_DETAIL_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `user_details` WHERE `USER_DETAIL_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'user_details deleted!',
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

    return $app->redirect($app['url_generator']->generate('user_details_list'));

})
->bind('user_details_delete');






