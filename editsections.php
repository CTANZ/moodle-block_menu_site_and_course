<?php // $Id:$
      // Add an extra section to a course
    require_once("../../config.php");
    
    $id = required_param('id',PARAM_INT);    // course ID
    $remove = optional_param('remove',0,PARAM_INT);    // course ID
    if (!has_capability('moodle/course:update', get_context_instance(CONTEXT_COURSE, $id))) {
        error('Sorry you are not allowed to do that'); 	
    } else {
    	
    	$section_count = get_field('course','numsections','id',$id);
    	//$section_count = $section_count+1;
    	if (!empty($remove)) {
    	    $section_count = $section_count-1;
    	} else {
    		$section_count = $section_count+1;    		
    	}
    	$dataobject = (object)array('id' => $id, 'numsections' => $section_count);
    	update_record('course', $dataobject);
    	header('Location: '.$CFG->wwwroot.'/course/view.php?id='.$id);
    	exit;
    	
    }
    

?>