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

$app->match('/majors/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'MAJOR_ID', 
		'DEPARTMENT_ID', 
		'MAJOR_GRADE_ID', 
		'NAME', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(11)', 
		'int(11)', 
		'varchar(100)', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `majors`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `majors`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'DEPARTMENT_ID'){
			    $findexternal_sql = 'SELECT NAME FROM `DEPARTMENTS` WHERE `DEPARTMENT_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['NAME'];
			}
			else if($table_columns[$i] == 'MAJOR_GRADE_ID'){
			    $findexternal_sql = 'SELECT NAME FROM `MAJOR_GRADES` WHERE `MAJOR_GRADE_ID` = ?';
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
$app->match('/majors/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . majors . " WHERE ".$idfldname." = ?";
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



$app->match('/majors', function () use ($app) {
    
	$table_columns = array(
		'MAJOR_ID', 
		'DEPARTMENT_ID', 
		'MAJOR_GRADE_ID', 
		'NAME', 

    );

    $primary_key = "MAJOR_ID";	

    return $app['twig']->render('majors/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('majors_list');



$app->match('/majors/create', function () use ($app) {
    
    $initial_data = array(
		'DEPARTMENT_ID' => '', 
		'MAJOR_GRADE_ID' => '', 
		'NAME' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `DEPARTMENT_ID`, `NAME` FROM `DEPARTMENTS`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['DEPARTMENT_ID']] = $findexternal_row['NAME'];
	}
	if(count($options) > 0){
	    $form = $form->add('DEPARTMENT_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('DEPARTMENT_ID', 'text', array('required' => true));
	}

	$options = array();
	$findexternal_sql = 'SELECT `MAJOR_GRADE_ID`, `NAME` FROM `MAJOR_GRADES`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['MAJOR_GRADE_ID']] = $findexternal_row['NAME'];
	}
	if(count($options) > 0){
	    $form = $form->add('MAJOR_GRADE_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('MAJOR_GRADE_ID', 'text', array('required' => true));
	}



	$form = $form->add('NAME', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `majors` (`DEPARTMENT_ID`, `MAJOR_GRADE_ID`, `NAME`) VALUES (?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['DEPARTMENT_ID'], $data['MAJOR_GRADE_ID'], $data['NAME']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'majors created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('majors_list'));

        }
    }

    return $app['twig']->render('majors/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('majors_create');



$app->match('/majors/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `majors` WHERE `MAJOR_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('majors_list'));
    }

    
    $initial_data = array(
		'DEPARTMENT_ID' => $row_sql['DEPARTMENT_ID'], 
		'MAJOR_GRADE_ID' => $row_sql['MAJOR_GRADE_ID'], 
		'NAME' => $row_sql['NAME'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `DEPARTMENT_ID`, `NAME` FROM `DEPARTMENTS`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['DEPARTMENT_ID']] = $findexternal_row['NAME'];
	}
	if(count($options) > 0){
	    $form = $form->add('DEPARTMENT_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('DEPARTMENT_ID', 'text', array('required' => true));
	}

	$options = array();
	$findexternal_sql = 'SELECT `MAJOR_GRADE_ID`, `NAME` FROM `MAJOR_GRADES`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['MAJOR_GRADE_ID']] = $findexternal_row['NAME'];
	}
	if(count($options) > 0){
	    $form = $form->add('MAJOR_GRADE_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('MAJOR_GRADE_ID', 'text', array('required' => true));
	}


	$form = $form->add('NAME', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `majors` SET `DEPARTMENT_ID` = ?, `MAJOR_GRADE_ID` = ?, `NAME` = ? WHERE `MAJOR_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['DEPARTMENT_ID'], $data['MAJOR_GRADE_ID'], $data['NAME'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'majors edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('majors_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('majors/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('majors_edit');



$app->match('/majors/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `majors` WHERE `MAJOR_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `majors` WHERE `MAJOR_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'majors deleted!',
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

    return $app->redirect($app['url_generator']->generate('majors_list'));

})
->bind('majors_delete');






