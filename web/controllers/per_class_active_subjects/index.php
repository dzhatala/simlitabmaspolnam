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

$app->match('/per_class_active_subjects/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'PER_CLASS_ACTIVE_SUBJECT_ID', 
		'CLASS_SECTION_ID', 
		'LECTURER_ID', 
		'SUBJECT_ID', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(11)', 
		'int(11)', 
		'int(11)', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `per_class_active_subjects`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `per_class_active_subjects`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'CLASS_SECTION_ID'){
			    $findexternal_sql = "select CLASS_SECTION_ID ,  concat(concat(concat(concat(CLASS_SECTION_ID,'-'),year_start),concat('/',concat(year_end,'-'))),concat(majors.name, ".
	" concat('-',concat(batches.name,concat('-',class_sections.name))))) as `LOOKUP`  from majors,class_sections,batches,academic_calendars  where class_sections.batch_id=batches.batch_id and  class_sections.academic_calendar_id=academic_calendars.academic_calendar_id  and batches.major_id=majors.major_id   "  . " AND CLASS_SECTION_ID=? " ;
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['LOOKUP'];
			}
			else if($table_columns[$i] == 'LECTURER_ID'){
			    $findexternal_sql = "select `LECTURER_ID`, `EMPLOYEE_NO` , concat(concat(LECTURER_ID,'-'),EMPLOYEE_NO,concat('-',concat(' ',concat(ifnull(first_name,''),concat(' ',ifnull(last_name,'')))))) as `LOOKUP`  from lecturers    "   . " WHERE LECTURER_ID=? " ;
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['LOOKUP'];
			}
			else if($table_columns[$i] == 'SUBJECT_ID'){
			    $findexternal_sql = "select `SUBJECT_ID`, `NAME` , concat(concat(SUBJECT_ID,'-'),' ',concat('-',concat(' ',concat(ifnull(CODE,''),concat(' ',ifnull(NAME,'')))))) as `LOOKUP`  from SUBJECTS   "   . " WHERE SUBJECT_ID=? " ;
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['LOOKUP'];
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
$app->match('/per_class_active_subjects/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . per_class_active_subjects . " WHERE ".$idfldname." = ?";
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



$app->match('/per_class_active_subjects', function () use ($app) {
    
	$table_columns = array(
		'PER_CLASS_ACTIVE_SUBJECT_ID', 
		'CLASS_SECTION_ID', 
		'LECTURER_ID', 
		'SUBJECT_ID', 

    );

    $primary_key = "PER_CLASS_ACTIVE_SUBJECT_ID";	

    return $app['twig']->render('per_class_active_subjects/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('per_class_active_subjects_list');



$app->match('/per_class_active_subjects/create', function () use ($app) {
    
    $initial_data = array(
		'CLASS_SECTION_ID' => '', 
		'LECTURER_ID' => '', 
		'SUBJECT_ID' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql ="select CLASS_SECTION_ID ,  concat(concat(concat(concat(CLASS_SECTION_ID,'-'),year_start),concat('/',concat(year_end,'-'))),concat(majors.name, ".
	" concat('-',concat(batches.name,concat('-',class_sections.name))))) as `LOOKUP`  from majors,class_sections,batches,academic_calendars  where class_sections.batch_id=batches.batch_id and  class_sections.academic_calendar_id=academic_calendars.academic_calendar_id  and batches.major_id=majors.major_id   " ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['CLASS_SECTION_ID']] = $findexternal_row['LOOKUP'];
	}
	if(count($options) > 0){
	    $form = $form->add('CLASS_SECTION_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('CLASS_SECTION_ID', 'text', array('required' => true));
	}

	$options = array();
	$findexternal_sql ="select `LECTURER_ID`, `EMPLOYEE_NO` , concat(concat(LECTURER_ID,'-'),EMPLOYEE_NO,concat('-',concat(' ',concat(ifnull(first_name,''),concat(' ',ifnull(last_name,'')))))) as `LOOKUP`  from lecturers    "  ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['LECTURER_ID']] = $findexternal_row['LOOKUP'];
	}
	if(count($options) > 0){
	    $form = $form->add('LECTURER_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('LECTURER_ID', 'text', array('required' => true));
	}

	$options = array();
	$findexternal_sql ="select `SUBJECT_ID`, `NAME` , concat(concat(SUBJECT_ID,'-'),' ',concat('-',concat(' ',concat(ifnull(CODE,''),concat(' ',ifnull(NAME,'')))))) as `LOOKUP`  from SUBJECTS   "  ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['SUBJECT_ID']] = $findexternal_row['LOOKUP'];
	}
	if(count($options) > 0){
	    $form = $form->add('SUBJECT_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('SUBJECT_ID', 'text', array('required' => true));
	}





    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `per_class_active_subjects` (`CLASS_SECTION_ID`, `LECTURER_ID`, `SUBJECT_ID`) VALUES (?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['CLASS_SECTION_ID'], $data['LECTURER_ID'], $data['SUBJECT_ID']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'per_class_active_subjects created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('per_class_active_subjects_list'));

        }
    }

    return $app['twig']->render('per_class_active_subjects/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('per_class_active_subjects_create');



$app->match('/per_class_active_subjects/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `per_class_active_subjects` WHERE `PER_CLASS_ACTIVE_SUBJECT_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('per_class_active_subjects_list'));
    }

    
    $initial_data = array(
		'CLASS_SECTION_ID' => $row_sql['CLASS_SECTION_ID'], 
		'LECTURER_ID' => $row_sql['LECTURER_ID'], 
		'SUBJECT_ID' => $row_sql['SUBJECT_ID'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql ="select CLASS_SECTION_ID ,  concat(concat(concat(concat(CLASS_SECTION_ID,'-'),year_start),concat('/',concat(year_end,'-'))),concat(majors.name, ".
	" concat('-',concat(batches.name,concat('-',class_sections.name))))) as `LOOKUP`  from majors,class_sections,batches,academic_calendars  where class_sections.batch_id=batches.batch_id and  class_sections.academic_calendar_id=academic_calendars.academic_calendar_id  and batches.major_id=majors.major_id   " ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['CLASS_SECTION_ID']] = $findexternal_row['LOOKUP'];
	}
	if(count($options) > 0){
	    $form = $form->add('CLASS_SECTION_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('CLASS_SECTION_ID', 'text', array('required' => true));
	}

	$options = array();
	$findexternal_sql ="select `LECTURER_ID`, `EMPLOYEE_NO` , concat(concat(LECTURER_ID,'-'),EMPLOYEE_NO,concat('-',concat(' ',concat(ifnull(first_name,''),concat(' ',ifnull(last_name,'')))))) as `LOOKUP`  from lecturers    "  ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['LECTURER_ID']] = $findexternal_row['LOOKUP'];
	}
	if(count($options) > 0){
	    $form = $form->add('LECTURER_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('LECTURER_ID', 'text', array('required' => true));
	}

	$options = array();
	$findexternal_sql ="select `SUBJECT_ID`, `NAME` , concat(concat(SUBJECT_ID,'-'),' ',concat('-',concat(' ',concat(ifnull(CODE,''),concat(' ',ifnull(NAME,'')))))) as `LOOKUP`  from SUBJECTS   "  ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['SUBJECT_ID']] = $findexternal_row['LOOKUP'];
	}
	if(count($options) > 0){
	    $form = $form->add('SUBJECT_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('SUBJECT_ID', 'text', array('required' => true));
	}




    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `per_class_active_subjects` SET `CLASS_SECTION_ID` = ?, `LECTURER_ID` = ?, `SUBJECT_ID` = ? WHERE `PER_CLASS_ACTIVE_SUBJECT_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['CLASS_SECTION_ID'], $data['LECTURER_ID'], $data['SUBJECT_ID'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'per_class_active_subjects edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('per_class_active_subjects_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('per_class_active_subjects/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('per_class_active_subjects_edit');



$app->match('/per_class_active_subjects/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `per_class_active_subjects` WHERE `PER_CLASS_ACTIVE_SUBJECT_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `per_class_active_subjects` WHERE `PER_CLASS_ACTIVE_SUBJECT_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'per_class_active_subjects deleted!',
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

    return $app->redirect($app['url_generator']->generate('per_class_active_subjects_list'));

})
->bind('per_class_active_subjects_delete');






