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

$app->match('/batches/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'BATCH_ID', 
		'MAJOR_ID', 
		'NAME', 

    );
    
    $table_columns_type = array(
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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `batches`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `batches`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'MAJOR_ID'){
			    $findexternal_sql = 'SELECT NAME FROM `MAJORS` WHERE `MAJOR_ID` = ?';
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
$app->match('/batches/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . batches . " WHERE ".$idfldname." = ?";
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



$app->match('/batches', function () use ($app) {
    
	$table_columns = array(
		'BATCH_ID', 
		'MAJOR_ID', 
		'NAME', 

    );

    $primary_key = "BATCH_ID";	

    return $app['twig']->render('batches/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('batches_list');



$app->match('/batches/create', function () use ($app) {
    
    $initial_data = array(
		'MAJOR_ID' => '', 
		'NAME' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `MAJOR_ID`, `NAME` FROM `MAJORS`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['MAJOR_ID']] = $findexternal_row['NAME'];
	}
	if(count($options) > 0){
	    $form = $form->add('MAJOR_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('MAJOR_ID', 'text', array('required' => true));
	}



	$form = $form->add('NAME', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `batches` (`MAJOR_ID`, `NAME`) VALUES (?, ?)";
            $app['db']->executeUpdate($update_query, array($data['MAJOR_ID'], $data['NAME']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'batches created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('batches_list'));

        }
    }

    return $app['twig']->render('batches/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('batches_create');



$app->match('/batches/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `batches` WHERE `BATCH_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('batches_list'));
    }

    
    $initial_data = array(
		'MAJOR_ID' => $row_sql['MAJOR_ID'], 
		'NAME' => $row_sql['NAME'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql = 'SELECT `MAJOR_ID`, `NAME` FROM `MAJORS`';
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['MAJOR_ID']] = $findexternal_row['NAME'];
	}
	if(count($options) > 0){
	    $form = $form->add('MAJOR_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('MAJOR_ID', 'text', array('required' => true));
	}


	$form = $form->add('NAME', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `batches` SET `MAJOR_ID` = ?, `NAME` = ? WHERE `BATCH_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['MAJOR_ID'], $data['NAME'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'batches edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('batches_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('batches/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('batches_edit');



$app->match('/batches/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `batches` WHERE `BATCH_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `batches` WHERE `BATCH_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'batches deleted!',
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

    return $app->redirect($app['url_generator']->generate('batches_list'));

})
->bind('batches_delete');






