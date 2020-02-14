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

$app->match('/exam_entry_results/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'EXAM_ENTRY_RESUT_ID', 
		'EXAM_ENTRY_ID', 
		'DOCUMENT_ID', 
		'EXAM_FINAL_GRADE_ID', 
		'RESULT', 

    );
    
    $table_columns_type = array(
		'char(10)', 
		'int(11)', 
		'int(11)', 
		'int(11)', 
		'float', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `exam_entry_results`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `exam_entry_results`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'EXAM_ENTRY_ID'){
			    $findexternal_sql = 'SELECT IS_CALCULATED FROM `EXAM_ENTRIES` WHERE `EXAM_ENTRIE_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['IS_CALCULATED'];
			}
			else if($table_columns[$i] == 'DOCUMENT_ID'){
			    $findexternal_sql = 'SELECT PATH_INFO FROM `DOCUMENTS` WHERE `DOCUMENT_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['PATH_INFO'];
			}
			else if($table_columns[$i] == 'EXAM_FINAL_GRADE_ID'){
			    $findexternal_sql = 'SELECT TEXT_LETTER_VALUE FROM `EXAM_FINAL_GRADES` WHERE `EXAM_FINAL_GRADE_ID` = ?';
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['TEXT_LETTER_VALUE'];
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
$app->match('/exam_entry_results/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . exam_entry_results . " WHERE ".$idfldname." = ?";
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



$app->match('/exam_entry_results', function () use ($app) {
    
	$table_columns = array(
		'EXAM_ENTRY_RESUT_ID', 
		'EXAM_ENTRY_ID', 
		'DOCUMENT_ID', 
		'EXAM_FINAL_GRADE_ID', 
		'RESULT', 

    );

    $primary_key = "EXAM_ENTRY_RESUT_ID";	

    return $app['twig']->render('exam_entry_results/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('exam_entry_results_list');



$app->match('/exam_entry_results/create', function () use ($app) {
    
    $initial_data = array(
		'EXAM_ENTRY_RESUT_ID' => '', 
		'EXAM_ENTRY_ID' => '', 
		'DOCUMENT_ID' => '', 
		'EXAM_FINAL_GRADE_ID' => '', 
		'RESULT' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `EXAM_ENTRIE_ID`, `IS_CALCULATED` FROM `EXAM_ENTRIES`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['EXAM_ENTRIE_ID']] = $findexternal_row['IS_CALCULATED'];
	}
	if(count($options) > 0){
	    $form = $form->add('EXAM_ENTRY_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('EXAM_ENTRY_ID', 'text', array('required' => true));
	}

	$options = array();
	$findexternal_sql = 'SELECT `DOCUMENT_ID`, `PATH_INFO` FROM `DOCUMENTS`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['DOCUMENT_ID']] = $findexternal_row['PATH_INFO'];
	}
	if(count($options) > 0){
	    $form = $form->add('DOCUMENT_ID', 'choice', array(
	        'required' => false,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('DOCUMENT_ID', 'text', array('required' => false));
	}

	$options = array();
	$findexternal_sql = 'SELECT `EXAM_FINAL_GRADE_ID`, `TEXT_LETTER_VALUE` FROM `EXAM_FINAL_GRADES`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['EXAM_FINAL_GRADE_ID']] = $findexternal_row['TEXT_LETTER_VALUE'];
	}
	if(count($options) > 0){
	    $form = $form->add('EXAM_FINAL_GRADE_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('EXAM_FINAL_GRADE_ID', 'text', array('required' => true));
	}



	$form = $form->add('EXAM_ENTRY_RESUT_ID', 'text', array('required' => true));
	$form = $form->add('RESULT', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `exam_entry_results` (`EXAM_ENTRY_RESUT_ID`, `EXAM_ENTRY_ID`, `DOCUMENT_ID`, `EXAM_FINAL_GRADE_ID`, `RESULT`) VALUES (?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['EXAM_ENTRY_RESUT_ID'], $data['EXAM_ENTRY_ID'], $data['DOCUMENT_ID'], $data['EXAM_FINAL_GRADE_ID'], $data['RESULT']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'exam_entry_results created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('exam_entry_results_list'));

        }
    }

    return $app['twig']->render('exam_entry_results/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('exam_entry_results_create');



$app->match('/exam_entry_results/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `exam_entry_results` WHERE `EXAM_ENTRY_RESUT_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('exam_entry_results_list'));
    }

    
    $initial_data = array(
		'EXAM_ENTRY_RESUT_ID' => $row_sql['EXAM_ENTRY_RESUT_ID'], 
		'EXAM_ENTRY_ID' => $row_sql['EXAM_ENTRY_ID'], 
		'DOCUMENT_ID' => $row_sql['DOCUMENT_ID'], 
		'EXAM_FINAL_GRADE_ID' => $row_sql['EXAM_FINAL_GRADE_ID'], 
		'RESULT' => $row_sql['RESULT'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `EXAM_ENTRIE_ID`, `IS_CALCULATED` FROM `EXAM_ENTRIES`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['EXAM_ENTRIE_ID']] = $findexternal_row['IS_CALCULATED'];
	}
	if(count($options) > 0){
	    $form = $form->add('EXAM_ENTRY_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('EXAM_ENTRY_ID', 'text', array('required' => true));
	}

	$options = array();
	$findexternal_sql = 'SELECT `DOCUMENT_ID`, `PATH_INFO` FROM `DOCUMENTS`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['DOCUMENT_ID']] = $findexternal_row['PATH_INFO'];
	}
	if(count($options) > 0){
	    $form = $form->add('DOCUMENT_ID', 'choice', array(
	        'required' => false,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('DOCUMENT_ID', 'text', array('required' => false));
	}

	$options = array();
	$findexternal_sql = 'SELECT `EXAM_FINAL_GRADE_ID`, `TEXT_LETTER_VALUE` FROM `EXAM_FINAL_GRADES`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['EXAM_FINAL_GRADE_ID']] = $findexternal_row['TEXT_LETTER_VALUE'];
	}
	if(count($options) > 0){
	    $form = $form->add('EXAM_FINAL_GRADE_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('EXAM_FINAL_GRADE_ID', 'text', array('required' => true));
	}


	$form = $form->add('EXAM_ENTRY_RESUT_ID', 'text', array('required' => true));
	$form = $form->add('RESULT', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `exam_entry_results` SET `EXAM_ENTRY_RESUT_ID` = ?, `EXAM_ENTRY_ID` = ?, `DOCUMENT_ID` = ?, `EXAM_FINAL_GRADE_ID` = ?, `RESULT` = ? WHERE `EXAM_ENTRY_RESUT_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['EXAM_ENTRY_RESUT_ID'], $data['EXAM_ENTRY_ID'], $data['DOCUMENT_ID'], $data['EXAM_FINAL_GRADE_ID'], $data['RESULT'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'exam_entry_results edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('exam_entry_results_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('exam_entry_results/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('exam_entry_results_edit');



$app->match('/exam_entry_results/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `exam_entry_results` WHERE `EXAM_ENTRY_RESUT_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `exam_entry_results` WHERE `EXAM_ENTRY_RESUT_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'exam_entry_results deleted!',
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

    return $app->redirect($app['url_generator']->generate('exam_entry_results_list'));

})
->bind('exam_entry_results_delete');






