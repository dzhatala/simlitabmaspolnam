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

$app->match('/documentusulan/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'DOCUMENT_USULAN_ID', 
		'DOCUMENT_ID', 
		'USULAN_ID', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(11)', 
		'int(11)', 

    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE ( 1 AND ";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
	$whereClause .= " ) ";

    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `documentusulan`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `documentusulan`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'DOCUMENT_ID'){
			    $findexternal_sql = 'SELECT `DOCUMENT_ID` FROM `document` WHERE `DOCUMENT_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['DOCUMENT_ID'];
			}
			else if($table_columns[$i] == 'USULAN_ID'){
			    $findexternal_sql = 'SELECT `USULAN_ID` FROM `usulan` WHERE `USULAN_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['USULAN_ID'];
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
$app->match('/documentusulan/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . documentusulan . " WHERE ".$idfldname." = ?";
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



$app->match('/documentusulan', function () use ($app) {
    
	$table_columns = array(
		'DOCUMENT_USULAN_ID', 
		'DOCUMENT_ID', 
		'USULAN_ID', 

    );

    $primary_key = "DOCUMENT_USULAN_ID";	

    return $app['twig']->render('documentusulan/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('documentusulan_list');



$app->match('/documentusulan/create', function () use ($app) {
    
    $initial_data = array(
		'DOCUMENT_ID' => '', 
		'USULAN_ID' => '', 

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
	$findexternal_sql = 'SELECT `DOCUMENT_ID`, `DOCUMENT_ID` FROM `document`  '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['DOCUMENT_ID']] = $findexternal_row['DOCUMENT_ID'];
	}
	if(count($options) > 0){
	    $form = $form->add('DOCUMENT_ID', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('DOCUMENT_ID', 'text', array_merge(array('required' => true),$field_default_ro));
	}

	$field_nullable= true  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	$options = array();
	$findexternal_sql = 'SELECT `USULAN_ID`, `USULAN_ID` FROM `usulan`  '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['USULAN_ID']] = $findexternal_row['USULAN_ID'];
	}
	if(count($options) > 0){
	    $form = $form->add('USULAN_ID', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('USULAN_ID', 'text', array_merge(array('required' => true),$field_default_ro));
	}



	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
			/**__BEFORE_EXECUTE__UPDATE_CREATE__**/
            $update_query = "INSERT INTO `documentusulan` (`DOCUMENT_ID`, `USULAN_ID`) VALUES (?, ?)";
            $app['db']->executeUpdate($update_query, array($data['DOCUMENT_ID'], $data['USULAN_ID']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'documentusulan created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('documentusulan_list'));

        }
    }

    return $app['twig']->render('documentusulan/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('documentusulan_create');



$app->match('/documentusulan/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `documentusulan` WHERE `DOCUMENT_USULAN_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('documentusulan_list'));
    }

    
    $initial_data = array(
		'DOCUMENT_ID' => $row_sql['DOCUMENT_ID'], 
		'USULAN_ID' => $row_sql['USULAN_ID'], 

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
	$findexternal_sql = 'SELECT `DOCUMENT_ID`, `DOCUMENT_ID` FROM `document`  '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['DOCUMENT_ID']] = $findexternal_row['DOCUMENT_ID'];
	}
	if(count($options) > 0){
	    $form = $form->add('DOCUMENT_ID', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('DOCUMENT_ID', 'text', array_merge(array('required' => true),$field_default_ro));
	}

	$field_nullable= true  ; 
	if ($app['credentials']['current_role']!="Administrator"){
		  $field_default_ro =array('read_only' => true ) ; 
	}else { 
		  $field_default_ro =array('read_only' => false ) ; 
	} 
	$limiter="" ;
	$options = array();
	$findexternal_sql = 'SELECT `USULAN_ID`, `USULAN_ID` FROM `usulan`  '  . $limiter ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['USULAN_ID']] = $findexternal_row['USULAN_ID'];
	}
	if(count($options) > 0){
	    $form = $form->add('USULAN_ID', 'choice', array_merge($field_default_ro,array(
	        'required' => $field_nullable,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    )));
	}
	else{
	    $form = $form->add('USULAN_ID', 'text', array_merge(array('required' => true),$field_default_ro));
	}


	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
			/**__BEFORE_EXECUTE__UPDATE_EDIT__**/

            $update_query = "UPDATE `documentusulan` SET `DOCUMENT_ID` = ?, `USULAN_ID` = ? WHERE `DOCUMENT_USULAN_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['DOCUMENT_ID'], $data['USULAN_ID'], $id));            

	
            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'documentusulan edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('documentusulan_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('documentusulan/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('documentusulan_edit');



$app->match('/documentusulan/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `documentusulan` WHERE `DOCUMENT_USULAN_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `documentusulan` WHERE `DOCUMENT_USULAN_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'documentusulan deleted!',
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

    return $app->redirect($app['url_generator']->generate('documentusulan_list'));

})
->bind('documentusulan_delete');






