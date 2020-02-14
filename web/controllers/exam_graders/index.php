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

$app->match('/exam_graders/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'EXAM_GRADER_ID', 
		'PER_CLASS_ACTIVE_SUBJECT_ID', 
		'FORMULA', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(11)', 
		'text', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `exam_graders`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `exam_graders`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'PER_CLASS_ACTIVE_SUBJECT_ID'){
			    $findexternal_sql = 'SELECT SUBJECT_ID FROM `PER_CLASS_ACTIVE_SUBJECTS` WHERE `PER_CLASS_ACTIVE_SUBJECT_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['SUBJECT_ID'];
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
$app->match('/exam_graders/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . exam_graders . " WHERE ".$idfldname." = ?";
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



$app->match('/exam_graders', function () use ($app) {
    
	$table_columns = array(
		'EXAM_GRADER_ID', 
		'PER_CLASS_ACTIVE_SUBJECT_ID', 
		'FORMULA', 

    );

    $primary_key = "EXAM_GRADER_ID";	

    return $app['twig']->render('exam_graders/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('exam_graders_list');



$app->match('/exam_graders/create', function () use ($app) {
    
    $initial_data = array(
		'EXAM_GRADER_ID' => '', 
		'PER_CLASS_ACTIVE_SUBJECT_ID' => '', 
		'FORMULA' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `PER_CLASS_ACTIVE_SUBJECT_ID`, `SUBJECT_ID` FROM `PER_CLASS_ACTIVE_SUBJECTS`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['PER_CLASS_ACTIVE_SUBJECT_ID']] = $findexternal_row['SUBJECT_ID'];
	}
	if(count($options) > 0){
	    $form = $form->add('PER_CLASS_ACTIVE_SUBJECT_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('PER_CLASS_ACTIVE_SUBJECT_ID', 'text', array('required' => true));
	}



	$form = $form->add('EXAM_GRADER_ID', 'text', array('required' => true));
	$form = $form->add('FORMULA', 'textarea', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `exam_graders` (`EXAM_GRADER_ID`, `PER_CLASS_ACTIVE_SUBJECT_ID`, `FORMULA`) VALUES (?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['EXAM_GRADER_ID'], $data['PER_CLASS_ACTIVE_SUBJECT_ID'], $data['FORMULA']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'exam_graders created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('exam_graders_list'));

        }
    }

    return $app['twig']->render('exam_graders/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('exam_graders_create');



$app->match('/exam_graders/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `exam_graders` WHERE `EXAM_GRADER_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('exam_graders_list'));
    }

    
    $initial_data = array(
		'EXAM_GRADER_ID' => $row_sql['EXAM_GRADER_ID'], 
		'PER_CLASS_ACTIVE_SUBJECT_ID' => $row_sql['PER_CLASS_ACTIVE_SUBJECT_ID'], 
		'FORMULA' => $row_sql['FORMULA'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `PER_CLASS_ACTIVE_SUBJECT_ID`, `SUBJECT_ID` FROM `PER_CLASS_ACTIVE_SUBJECTS`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['PER_CLASS_ACTIVE_SUBJECT_ID']] = $findexternal_row['SUBJECT_ID'];
	}
	if(count($options) > 0){
	    $form = $form->add('PER_CLASS_ACTIVE_SUBJECT_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('PER_CLASS_ACTIVE_SUBJECT_ID', 'text', array('required' => true));
	}


	$form = $form->add('EXAM_GRADER_ID', 'text', array('required' => true));
	$form = $form->add('FORMULA', 'textarea', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `exam_graders` SET `EXAM_GRADER_ID` = ?, `PER_CLASS_ACTIVE_SUBJECT_ID` = ?, `FORMULA` = ? WHERE `EXAM_GRADER_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['EXAM_GRADER_ID'], $data['PER_CLASS_ACTIVE_SUBJECT_ID'], $data['FORMULA'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'exam_graders edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('exam_graders_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('exam_graders/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('exam_graders_edit');



$app->match('/exam_graders/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `exam_graders` WHERE `EXAM_GRADER_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `exam_graders` WHERE `EXAM_GRADER_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'exam_graders deleted!',
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

    return $app->redirect($app['url_generator']->generate('exam_graders_list'));

})
->bind('exam_graders_delete');






