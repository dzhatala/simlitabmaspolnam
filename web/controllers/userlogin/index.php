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

$app->match('/userlogin/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'USERLOGIN_ID', 
		'LOGIN', 
		'PLAIN_PASSWORD', 
		'HASHED_PASSWORD', 
		'USER_LEVEL', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'varchar(255)', 
		'varchar(255)', 
		'varchar(255)', 
		'int(11)', 

    );    
    
	
    $whereClause = "";
    $transform_text_to_key=""; /**  ... to enable search for externals ...**/
	
	/** find externals fields for search key and passing it to like as and id **/
	

    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE ( 1 AND ";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        //external search  version
		$whereClause =  $whereClause . "   userlogin.".$col . " LIKE '%". $searchValue ."%' ".$transform_text_to_key;
        
		//non external search version ...
		//$whereClause =  $whereClause . "   userlogin.".$col . " LIKE '%". $searchValue ."%' ";
        
        $i = $i + 1;
    }
	$whereClause .= " ) ";
    
	
    
	
	
	
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `userlogin`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `userlogin`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

		if( $table_columns_type[$i] != "blob") {
				$rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
		} else {				if( !$row_sql[$table_columns[$i]] ) {
						$rows[$row_key][$table_columns[$i]] = "0 Kb.";
				} else {
						$rows[$row_key][$table_columns[$i]] = " <a target='__blank' href='menu/download?id=" . $row_sql[$table_columns[0]];
						$rows[$row_key][$table_columns[$i]] .= "&fldname=" . $table_columns[$i];
						$rows[$row_key][$table_columns[$i]] .= "&idfld=" . $table_columns[0];
						$rows[$row_key][$table_columns[$i]] .= "'>";
						$rows[$row_key][$table_columns[$i]] .= number_format(strlen($row_sql[$table_columns[$i]]) / 1024, 2) . " Kb.";
						$rows[$row_key][$table_columns[$i]] .= "</a>";
				}
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
$app->match('/userlogin/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . userlogin . " WHERE ".$idfldname." = ?";
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



$app->match('/userlogin', function () use ($app) {
    
	$table_columns = array(
		'USERLOGIN_ID', 
		'LOGIN', 
		'PLAIN_PASSWORD', 
		'HASHED_PASSWORD', 
		'USER_LEVEL', 

    );
	
	/**translating here ...**/
	$tr_table_columns=array();
	foreach ($table_columns as $col){
		//var_dump($col) ;die;
		//array_push($tr_table_columns,$app['translator']->trans("userlogin".$col));
		array_push($tr_table_columns,$col);
	}
	$table_columns=$tr_table_columns;
	/****/

    $primary_key = "USERLOGIN_ID";	

    return $app['twig']->render('userlogin/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('userlogin_list');



$app->match('/userlogin/create', function () use ($app) {
    
    $initial_data = array(
		'LOGIN' => '', 
		'PLAIN_PASSWORD' => '', 
		'HASHED_PASSWORD' => '', 
		'USER_LEVEL' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('LOGIN', 'text', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('PLAIN_PASSWORD', 'text', array_merge(array('required' => false),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('HASHED_PASSWORD', 'text', array_merge(array('required' => false),$field_default_ro));
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('USER_LEVEL', 'text', array_merge(array('required' => true),$field_default_ro));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
			
            $update_query = "INSERT INTO `userlogin` (`LOGIN`, `PLAIN_PASSWORD`, `HASHED_PASSWORD`, `USER_LEVEL`) VALUES (?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['LOGIN'], $data['PLAIN_PASSWORD'], $data['HASHED_PASSWORD'], $data['USER_LEVEL']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'userlogin created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('userlogin_list'));

        }
    }

    return $app['twig']->render('userlogin/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('userlogin_create');



$app->match('/userlogin/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `userlogin` WHERE `USERLOGIN_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('userlogin_list'));
    }

    
    $initial_data = array(
		'LOGIN' => $row_sql['LOGIN'], 
		'PLAIN_PASSWORD' => $row_sql['PLAIN_PASSWORD'], 
		'HASHED_PASSWORD' => $row_sql['HASHED_PASSWORD'], 
		'USER_LEVEL' => $row_sql['USER_LEVEL'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('LOGIN', 'text', array_merge(array('required' => true),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('PLAIN_PASSWORD', 'text', array_merge(array('required' => false),$field_default_ro));
	$field_default_ro=array('required' => false,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('HASHED_PASSWORD', 'text', array_merge(array('required' => false),$field_default_ro));
	$field_default_ro=array('required' => true,'disabled' =>true)  ; 
	if($app['credentials']['current_role']=="Administrator"){
	unset($field_default_ro['disabled']);
	}
	$form = $form->add('USER_LEVEL', 'text', array_merge(array('required' => true),$field_default_ro));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
			

            $update_query = "UPDATE `userlogin` SET `LOGIN` = ?, `PLAIN_PASSWORD` = ?, `HASHED_PASSWORD` = ?, `USER_LEVEL` = ? WHERE `USERLOGIN_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['LOGIN'], $data['PLAIN_PASSWORD'], $data['HASHED_PASSWORD'], $data['USER_LEVEL'], $id));            

	
            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'userlogin edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('userlogin_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('userlogin/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('userlogin_edit');



$app->match('/userlogin/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `userlogin` WHERE `USERLOGIN_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `userlogin` WHERE `USERLOGIN_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'userlogin deleted!',
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

    return $app->redirect($app['url_generator']->generate('userlogin_list'));

})
->bind('userlogin_delete');






