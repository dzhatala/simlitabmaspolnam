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

$app->match('/students/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'STUDENT_ID', 
		'ADMISSION_NO', 
		'FIRST_NAME', 
		'MIDDLE_NAME', 
		'LAST_NAME', 
		'ADDRESS', 
		'PHONE_NO', 
		'REGISTRATION_DATE', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `students`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `students`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
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
$app->match('/students/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . students . " WHERE ".$idfldname." = ?";
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



$app->match('/students', function () use ($app) {
    
	$table_columns = array(
		'STUDENT_ID', 
		'ADMISSION_NO', 
		'FIRST_NAME', 
		'MIDDLE_NAME', 
		'LAST_NAME', 
		'ADDRESS', 
		'PHONE_NO', 
		'REGISTRATION_DATE', 

    );

    $primary_key = "STUDENT_ID";	

    return $app['twig']->render('students/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('students_list');



$app->match('/students/create', function () use ($app) {
    
    $initial_data = array(
		'ADMISSION_NO' => '', 
		'FIRST_NAME' => '', 
		'MIDDLE_NAME' => '', 
		'LAST_NAME' => '', 
		'ADDRESS' => '', 
		'PHONE_NO' => '', 
		'REGISTRATION_DATE' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);



	$form = $form->add('ADMISSION_NO', 'text', array('required' => true));
	$form = $form->add('FIRST_NAME', 'text', array('required' => true));
	$form = $form->add('MIDDLE_NAME', 'text', array('required' => false));
	$form = $form->add('LAST_NAME', 'text', array('required' => false));
	$form = $form->add('ADDRESS', 'textarea', array('required' => true));
	$form = $form->add('PHONE_NO', 'text', array('required' => true));
	$form = $form->add('REGISTRATION_DATE', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `students` (`ADMISSION_NO`, `FIRST_NAME`, `MIDDLE_NAME`, `LAST_NAME`, `ADDRESS`, `PHONE_NO`, `REGISTRATION_DATE`) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['ADMISSION_NO'], $data['FIRST_NAME'], $data['MIDDLE_NAME'], $data['LAST_NAME'], $data['ADDRESS'], $data['PHONE_NO'], $data['REGISTRATION_DATE']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'students created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('students_list'));

        }
    }

    return $app['twig']->render('students/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('students_create');



$app->match('/students/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `students` WHERE `STUDENT_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('students_list'));
    }

    
    $initial_data = array(
		'ADMISSION_NO' => $row_sql['ADMISSION_NO'], 
		'FIRST_NAME' => $row_sql['FIRST_NAME'], 
		'MIDDLE_NAME' => $row_sql['MIDDLE_NAME'], 
		'LAST_NAME' => $row_sql['LAST_NAME'], 
		'ADDRESS' => $row_sql['ADDRESS'], 
		'PHONE_NO' => $row_sql['PHONE_NO'], 
		'REGISTRATION_DATE' => $row_sql['REGISTRATION_DATE'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('ADMISSION_NO', 'text', array('required' => true));
	$form = $form->add('FIRST_NAME', 'text', array('required' => true));
	$form = $form->add('MIDDLE_NAME', 'text', array('required' => false));
	$form = $form->add('LAST_NAME', 'text', array('required' => false));
	$form = $form->add('ADDRESS', 'textarea', array('required' => true));
	$form = $form->add('PHONE_NO', 'text', array('required' => true));
	$form = $form->add('REGISTRATION_DATE', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `students` SET `ADMISSION_NO` = ?, `FIRST_NAME` = ?, `MIDDLE_NAME` = ?, `LAST_NAME` = ?, `ADDRESS` = ?, `PHONE_NO` = ?, `REGISTRATION_DATE` = ? WHERE `STUDENT_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['ADMISSION_NO'], $data['FIRST_NAME'], $data['MIDDLE_NAME'], $data['LAST_NAME'], $data['ADDRESS'], $data['PHONE_NO'], $data['REGISTRATION_DATE'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'students edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('students_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('students/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('students_edit');



$app->match('/students/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `students` WHERE `STUDENT_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `students` WHERE `STUDENT_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'students deleted!',
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

    return $app->redirect($app['url_generator']->generate('students_list'));

})
->bind('students_delete');






