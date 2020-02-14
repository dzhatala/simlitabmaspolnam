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

$app->match('/lecturers/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'LECTURER_ID', 
		'EMPLOYEE_NO', 
		'FIRST_NAME', 
		'MIDDLE_NAME', 
		'LAST_NAME', 
		'ADDRESS', 
		'PHONE_NO', 
		'JOIN_DATE', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'varchar(50)', 
		'varchar(100)', 
		'varchar(50)', 
		'varchar(50)', 
		'text', 
		'varchar(50)', 
		'datetime', 

    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `lecturers`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `lecturers`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/lecturers/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . lecturers . " WHERE ".$idfldname." = ?";
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



$app->match('/lecturers', function () use ($app) {
    
	$table_columns = array(
		'LECTURER_ID', 
		'EMPLOYEE_NO', 
		'FIRST_NAME', 
		'MIDDLE_NAME', 
		'LAST_NAME', 
		'ADDRESS', 
		'PHONE_NO', 
		'JOIN_DATE', 

    );

    $primary_key = "LECTURER_ID";	

    return $app['twig']->render('lecturers/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('lecturers_list');



$app->match('/lecturers/create', function () use ($app) {
    
    $initial_data = array(
		'EMPLOYEE_NO' => '', 
		'FIRST_NAME' => '', 
		'MIDDLE_NAME' => '', 
		'LAST_NAME' => '', 
		'ADDRESS' => '', 
		'PHONE_NO' => '', 
		'JOIN_DATE' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('EMPLOYEE_NO', 'text', array('required' => true));
	$form = $form->add('FIRST_NAME', 'text', array('required' => true));
	$form = $form->add('MIDDLE_NAME', 'text', array('required' => false));
	$form = $form->add('LAST_NAME', 'text', array('required' => false));
	$form = $form->add('ADDRESS', 'textarea', array('required' => true));
	$form = $form->add('PHONE_NO', 'text', array('required' => true));
	$form = $form->add('JOIN_DATE', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `lecturers` (`EMPLOYEE_NO`, `FIRST_NAME`, `MIDDLE_NAME`, `LAST_NAME`, `ADDRESS`, `PHONE_NO`, `JOIN_DATE`) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['EMPLOYEE_NO'], $data['FIRST_NAME'], $data['MIDDLE_NAME'], $data['LAST_NAME'], $data['ADDRESS'], $data['PHONE_NO'], $data['JOIN_DATE']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'lecturers created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('lecturers_list'));

        }
    }

    return $app['twig']->render('lecturers/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('lecturers_create');



$app->match('/lecturers/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `lecturers` WHERE `LECTURER_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('lecturers_list'));
    }

    
    $initial_data = array(
		'EMPLOYEE_NO' => $row_sql['EMPLOYEE_NO'], 
		'FIRST_NAME' => $row_sql['FIRST_NAME'], 
		'MIDDLE_NAME' => $row_sql['MIDDLE_NAME'], 
		'LAST_NAME' => $row_sql['LAST_NAME'], 
		'ADDRESS' => $row_sql['ADDRESS'], 
		'PHONE_NO' => $row_sql['PHONE_NO'], 
		'JOIN_DATE' => $row_sql['JOIN_DATE'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('EMPLOYEE_NO', 'text', array('required' => true));
	$form = $form->add('FIRST_NAME', 'text', array('required' => true));
	$form = $form->add('MIDDLE_NAME', 'text', array('required' => false));
	$form = $form->add('LAST_NAME', 'text', array('required' => false));
	$form = $form->add('ADDRESS', 'textarea', array('required' => true));
	$form = $form->add('PHONE_NO', 'text', array('required' => true));
	$form = $form->add('JOIN_DATE', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `lecturers` SET `EMPLOYEE_NO` = ?, `FIRST_NAME` = ?, `MIDDLE_NAME` = ?, `LAST_NAME` = ?, `ADDRESS` = ?, `PHONE_NO` = ?, `JOIN_DATE` = ? WHERE `LECTURER_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['EMPLOYEE_NO'], $data['FIRST_NAME'], $data['MIDDLE_NAME'], $data['LAST_NAME'], $data['ADDRESS'], $data['PHONE_NO'], $data['JOIN_DATE'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'lecturers edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('lecturers_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('lecturers/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('lecturers_edit');



$app->match('/lecturers/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `lecturers` WHERE `LECTURER_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `lecturers` WHERE `LECTURER_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'lecturers deleted!',
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

    return $app->redirect($app['url_generator']->generate('lecturers_list'));

})
->bind('lecturers_delete');






