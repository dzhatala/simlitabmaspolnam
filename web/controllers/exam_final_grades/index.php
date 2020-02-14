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

$app->match('/exam_final_grades/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
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
		'EXAM_FINAL_GRADE_ID', 
		'STUDENT_ID', 
		'PER_CLASS_ACTIVE_SUBJECT_ID', 
		'FLOAT_VALUE', 
		'TEXT_LETTER_VALUE', 
		'USE_ENTRIES', 
		'USE_GRADER', 

    );
    
    $table_columns_type = array(
		'int(11)', 
		'int(11)', 
		'int(11)', 
		'float', 
		'varchar(50)', 
		'tinyint(1)', 
		'tinyint(1)', 

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
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `exam_final_grades`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `exam_final_grades`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

			if($table_columns[$i] == 'STUDENT_ID'){
			    $findexternal_sql = "select `STUDENT_ID`, `ADMISSION_NO` , concat(concat(concat(concat(STUDENT_ID,'-'),admission_no),'-'),first_name,concat('-',concat(' ',concat(ifnull(middle_name,''),ifnull(last_name,''))))) as `LOOKUP`  from students  WHERE `STUDENT_ID`=?   ";
			    $findexternal_row = $app['db']->fetchAssoc($findexternal_sql, array($row_sql[$table_columns[$i]]));
			    $rows[$row_key][$table_columns[$i]] = $findexternal_row['LOOKUP'];
			}
			else if($table_columns[$i] == 'PER_CLASS_ACTIVE_SUBJECT_ID'){
			    $findexternal_sql = "select `PER_CLASS_ACTIVE_SUBJECT_ID`  ,concat(concat(concat(concat(PER_CLASS_ACTIVE_SUBJECT_ID,concat('-',concat(academic_calendars.year_start,concat('/',academic_calendars.year_end)))), concat('-',batches.NAME)),concat('-',concat(lecturers.first_name,concat('-',concat(ifnull(lecturers.last_name,''),'-'))))), 
						concat(concat(subjects.NAME,'-'),CLASS_SECTIONS.NAME) )  as `LOOKUP` from PER_CLASS_ACTIVE_SUBJECTS ,class_sections, subjects,lecturers ,batches,academic_calendars   WHERE 1  AND   per_class_active_subjects.CLASS_SECTION_ID=class_sections.CLASS_SECTION_ID   AND   per_class_active_subjects.SUBJECT_ID=subjects.SUBJECT_ID   AND   per_class_active_subjects.LECTURER_ID=lecturers.LECTURER_ID   AND   class_sections.BATCH_ID=batches.BATCH_ID   AND   class_sections.ACADEMIC_CALENDAR_ID=ACADEMIC_CALENDARS.ACADEMIC_CALENDAR_ID    "   . " AND per_class_active_SUBJECT_ID=? " ;
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
$app->match('/exam_final_grades/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . exam_final_grades . " WHERE ".$idfldname." = ?";
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



$app->match('/exam_final_grades', function () use ($app) {
    
	$table_columns = array(
		'EXAM_FINAL_GRADE_ID', 
		'STUDENT_ID', 
		'PER_CLASS_ACTIVE_SUBJECT_ID', 
		'FLOAT_VALUE', 
		'TEXT_LETTER_VALUE', 
		'USE_ENTRIES', 
		'USE_GRADER', 

    );

    $primary_key = "EXAM_FINAL_GRADE_ID";	

    return $app['twig']->render('exam_final_grades/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('exam_final_grades_list');



$app->match('/exam_final_grades/create', function () use ($app) {
    
    $initial_data = array(
		'STUDENT_ID' => '', 
		'PER_CLASS_ACTIVE_SUBJECT_ID' => '', 
		'FLOAT_VALUE' => '', 
		'TEXT_LETTER_VALUE' => '', 
		'USE_ENTRIES' => '', 
		'USE_GRADER' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql ="select `STUDENT_ID`, `ADMISSION_NO` , concat(concat(concat(concat(STUDENT_ID,'-'),admission_no),'-'),first_name,concat('-',concat(' ',concat(ifnull(middle_name,''),ifnull(last_name,''))))) as `LOOKUP`  from students order by `ADMISSION_NO`  ";
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['STUDENT_ID']] = $findexternal_row['LOOKUP'];
	}
	if(count($options) > 0){
	    $form = $form->add('STUDENT_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('STUDENT_ID', 'text', array('required' => true));
	}

	$options = array();
	$findexternal_sql ="select `PER_CLASS_ACTIVE_SUBJECT_ID`  ,concat(concat(concat(concat(PER_CLASS_ACTIVE_SUBJECT_ID,concat('-',concat(academic_calendars.year_start,concat('/',academic_calendars.year_end)))), concat('-',batches.NAME)),concat('-',concat(lecturers.first_name,concat('-',concat(ifnull(lecturers.last_name,''),'-'))))), 
						concat(concat(subjects.NAME,'-'),CLASS_SECTIONS.NAME) )  as `LOOKUP` from PER_CLASS_ACTIVE_SUBJECTS ,class_sections, subjects,lecturers ,batches,academic_calendars   WHERE 1  AND   per_class_active_subjects.CLASS_SECTION_ID=class_sections.CLASS_SECTION_ID   AND   per_class_active_subjects.SUBJECT_ID=subjects.SUBJECT_ID   AND   per_class_active_subjects.LECTURER_ID=lecturers.LECTURER_ID   AND   class_sections.BATCH_ID=batches.BATCH_ID   AND   class_sections.ACADEMIC_CALENDAR_ID=ACADEMIC_CALENDARS.ACADEMIC_CALENDAR_ID    "  ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['PER_CLASS_ACTIVE_SUBJECT_ID']] = $findexternal_row['LOOKUP'];
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



	$form = $form->add('FLOAT_VALUE', 'text', array('required' => false));
	$form = $form->add('TEXT_LETTER_VALUE', 'text', array('required' => false));
	$form = $form->add('USE_ENTRIES', 'text', array('required' => true));
	$form = $form->add('USE_GRADER', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "INSERT INTO `exam_final_grades` (`STUDENT_ID`, `PER_CLASS_ACTIVE_SUBJECT_ID`, `FLOAT_VALUE`, `TEXT_LETTER_VALUE`, `USE_ENTRIES`, `USE_GRADER`) VALUES (?, ?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['STUDENT_ID'], $data['PER_CLASS_ACTIVE_SUBJECT_ID'], $data['FLOAT_VALUE'], $data['TEXT_LETTER_VALUE'], $data['USE_ENTRIES'], $data['USE_GRADER']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'exam_final_grades created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('exam_final_grades_list'));

        }
    }

    return $app['twig']->render('exam_final_grades/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('exam_final_grades_create');



$app->match('/exam_final_grades/edit/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `exam_final_grades` WHERE `EXAM_FINAL_GRADE_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Row not found!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('exam_final_grades_list'));
    }

    
    $initial_data = array(
		'STUDENT_ID' => $row_sql['STUDENT_ID'], 
		'PER_CLASS_ACTIVE_SUBJECT_ID' => $row_sql['PER_CLASS_ACTIVE_SUBJECT_ID'], 
		'FLOAT_VALUE' => $row_sql['FLOAT_VALUE'], 
		'TEXT_LETTER_VALUE' => $row_sql['TEXT_LETTER_VALUE'], 
		'USE_ENTRIES' => $row_sql['USE_ENTRIES'], 
		'USE_GRADER' => $row_sql['USE_GRADER'], 

    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$options = array();
	$findexternal_sql ="select `STUDENT_ID`, `ADMISSION_NO` , concat(concat(concat(concat(STUDENT_ID,'-'),admission_no),'-'),first_name,concat('-',concat(' ',concat(ifnull(middle_name,''),ifnull(last_name,''))))) as `LOOKUP`  from students order by `ADMISSION_NO`  ";
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['STUDENT_ID']] = $findexternal_row['LOOKUP'];
	}
	if(count($options) > 0){
	    $form = $form->add('STUDENT_ID', 'choice', array(
	        'required' => true,
	        'choices' => $options,
	        'expanded' => false,
	        'constraints' => new Assert\Choice(array_keys($options))
	    ));
	}
	else{
	    $form = $form->add('STUDENT_ID', 'text', array('required' => true));
	}

	$options = array();
	$findexternal_sql ="select `PER_CLASS_ACTIVE_SUBJECT_ID`  ,concat(concat(concat(concat(PER_CLASS_ACTIVE_SUBJECT_ID,concat('-',concat(academic_calendars.year_start,concat('/',academic_calendars.year_end)))), concat('-',batches.NAME)),concat('-',concat(lecturers.first_name,concat('-',concat(ifnull(lecturers.last_name,''),'-'))))), 
						concat(concat(subjects.NAME,'-'),CLASS_SECTIONS.NAME) )  as `LOOKUP` from PER_CLASS_ACTIVE_SUBJECTS ,class_sections, subjects,lecturers ,batches,academic_calendars   WHERE 1  AND   per_class_active_subjects.CLASS_SECTION_ID=class_sections.CLASS_SECTION_ID   AND   per_class_active_subjects.SUBJECT_ID=subjects.SUBJECT_ID   AND   per_class_active_subjects.LECTURER_ID=lecturers.LECTURER_ID   AND   class_sections.BATCH_ID=batches.BATCH_ID   AND   class_sections.ACADEMIC_CALENDAR_ID=ACADEMIC_CALENDARS.ACADEMIC_CALENDAR_ID    "  ;
	$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
	foreach($findexternal_rows as $findexternal_row){
	    $options[$findexternal_row['PER_CLASS_ACTIVE_SUBJECT_ID']] = $findexternal_row['LOOKUP'];
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


	$form = $form->add('FLOAT_VALUE', 'text', array('required' => false));
	$form = $form->add('TEXT_LETTER_VALUE', 'text', array('required' => false));
	$form = $form->add('USE_ENTRIES', 'text', array('required' => true));
	$form = $form->add('USE_GRADER', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `exam_final_grades` SET `STUDENT_ID` = ?, `PER_CLASS_ACTIVE_SUBJECT_ID` = ?, `FLOAT_VALUE` = ?, `TEXT_LETTER_VALUE` = ?, `USE_ENTRIES` = ?, `USE_GRADER` = ? WHERE `EXAM_FINAL_GRADE_ID` = ?";
            $app['db']->executeUpdate($update_query, array($data['STUDENT_ID'], $data['PER_CLASS_ACTIVE_SUBJECT_ID'], $data['FLOAT_VALUE'], $data['TEXT_LETTER_VALUE'], $data['USE_ENTRIES'], $data['USE_GRADER'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'exam_final_grades edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('exam_final_grades_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('exam_final_grades/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('exam_final_grades_edit');



$app->match('/exam_final_grades/delete/{id}', function ($id) use ($app) {

    $find_sql = "SELECT * FROM `exam_final_grades` WHERE `EXAM_FINAL_GRADE_ID` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `exam_final_grades` WHERE `EXAM_FINAL_GRADE_ID` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'exam_final_grades deleted!',
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

    return $app->redirect($app['url_generator']->generate('exam_final_grades_list'));

})
->bind('exam_final_grades_delete');






