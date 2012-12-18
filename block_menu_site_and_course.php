<?php 
/* Menu Block
 * Charles Kelly, March 2008
 * Note around line 52 there are some options you can set.
 * Based on an earlier menu of mine.
 * Last update: March 26, 2008
 */
 
class block_menu_site_and_course extends block_base {
    function init() {
        $this->title = get_string('blocktitle','block_menu_site_and_course');
        $this->content_type = BLOCK_TYPE_TEXT;
        $this->version = 2008032400;
    }
    
    function preferred_width() {
        return 210;
    }

    public function instance_can_be_hidden() {
        return $this->user_can_edit();
    }
    
    function get_content() {
        global $USER, $CFG, $COURSE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        
        if (empty($this->instance)) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        $sections = function_exists('get_all_sections')? get_all_sections($COURSE->id): Array();
        $sectionname = get_string("name".$COURSE->format,'block_menu_site_and_course');
        $sectiongroup = $COURSE->format;

        if ($COURSE->id == SITEID) {  // Site-level
            $level = 'site';
            //NOT NEEDED HERE        
        } else { // Course-level
            //NOT NEEDED HERE          
            $level = 'course';
        }
        
            
        ob_start();
        print $this->get_tab_like_button_content($level);
        $this->content->text = ob_get_contents();
        ob_end_clean();        
        //     return $this->content;
    }
  
  
    function get_tab_like_button_content($level) {
        global $CFG, $DB, $COURSE, $OUTPUT;

        /* Options you can set
         * 1 = show
         * 0 = don't show
         * NOTE: if all of these are off, there is no need to put this on the main page.
         */
        $showcalendar = 0;
        $showparticipants = 1;
        $showprofile = 0;
        $showlogin = 0;
        require_once($CFG->dirroot.'/blocks/menu_site_and_course/truncate_description.php');
        
        $sections = (isset($COURSE->id) && function_exists('get_all_sections')) ? get_all_sections($COURSE->id) : Array();

        $format = 'section';

        $text = '';
        $text .= '<div id="nav">';
        $text .= '<ul class="list">';
        
        /* ==== THE BUTTONS START HERE ==== */
        // HOME
        if ($level != 'site'){
            $text .='<li class="s" style="font-weight:bold"><a href="'.$CFG->wwwroot.'/course/view.php?id='.$COURSE->id.'&home=1">'.$COURSE->shortname.'</a></li>';
        } else {
            $text .='<li class="h"><a href="'.$CFG->wwwroot.'/">'.get_string('home', 'moodle').'</a></li>';
        }
        
        // LOGIN
        
        if ($showlogin){
            if (!isloggedin() or isguestuser()) { 
                $text .='<li class="h"><a href="'.$CFG->wwwroot.'/login/">'.get_string('login', 'moodle').'</a></li>';
                // If you want a "logout" button, too, then uncomment the following 2 lines.
                //     } else {
                //          $text .='<li class="h"><a href="'.$CFG->wwwroot.'/login/logout.php">'.get_string('logout', 'moodle').'</a></li>';
            }
        }

        // === Course-only Items ====
        if ($level != 'site'){
            // LESSON MENU
            $context = context_course::instance($COURSE->id);
            $canviewhidden = has_capability('moodle/course:viewhiddensections', $context);
            if (function_exists('course_get_format')) {
                // Moodle 2.4
                $course = course_get_format($COURSE)->get_course();
                $numsections = $course->numsections;
            } else {
                // Moodle 2.0-2.3
                $numsections = $COURSE->numsections;
            }
            if (!empty($sections)) {
                foreach($sections as $section) {
                    if (!empty($CFG->enableavailability)) {
                        $ci = new condition_info_section($section, CONDITION_MISSING_EXTRATABLE);
                        $info = '';
                        $available = $ci->is_available($info);
                    } else {
                        // Availability disabled, assume available
                        $available = true;
                    }
                    if ($section->visible && $section->section > 0 && $section->section <= $numsections && ($available || $section->showavailability)) {
                        $summary = truncate_description($section->summary); //strip_tags($section->summary);
                        $name = strip_tags($section->name);
                        if (empty($summary)) {
                            $summary = get_string("name{$COURSE->format}",'block_menu_site_and_course').' '.$section->section;
                        }
                        $text .='<li class="r0';
                        if(!empty($_GET[$format]) && $_GET[$format]==$section->section) {$text.=' current';}
                        $text .='">';
                        $text.='<div class="icon column c0"><img src="'.$OUTPUT->pix_url("/i/one").'" class="icon"></div><div class="column c1">';
                        if(!$available) {
                            // Section not available - show greyed out (no link)
                            $text .= '<span class="unavailable">';
                            $text .= (!empty($summary) && empty($name)) ? $summary : $name;
                            $text .= '</span>';
                        } else {
                            // Section available - show link
                            $text .= '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$COURSE->id.'&'.$format.'='.$section->section.'" title="View '.strip_tags(str_replace('-', '',$summary)).'">';
                            $text .= (!empty($summary) && empty($name)) ? $summary : $name;
                            $text .= '</a>';
                        }
                        $text .= '</div></li>';
                    }
                }
              
                // SHOW ALL
                $text .='<li class="showall"><a href="'.$CFG->wwwroot.'/course/view.php?id='.$COURSE->id.'&'.$format.'=all" alt="'.get_string("showall",'moodle',$numsections).'">'.get_string("showall",'block_menu_site_and_course').'</a>';
       
                $text .= '</ul>';
              
                $text .= '<ul class="list">';
              
                if (isloggedin() and !isguestuser()) {
                    // PARTICIPANTS
                    if ($showparticipants){
                        $text .='<li class="r0"><div class="icon column c0"><img src="'.$OUTPUT->pix_url("/i/users").'" class="icon" alt="" /></div><div class="column c1"><a title="'.get_string('listofallpeople').'" href="'.$CFG->wwwroot."/user/index.php?id={$COURSE->id}".'">'.get_string('participants').'</a></div></li>';
                    }
                
                    // GRADES   
                    if ($COURSE->showgrades) {
                        $text .='<li class="r1"><div class="icon column c0"><img src="'.$OUTPUT->pix_url("/i/grades").'" class="icon" alt="" /></div><div class="column c1"><a href="'.$CFG->wwwroot.'/grade/index.php?id='.$COURSE->id.'">'.get_string('gradebook','grades').'</a></div></li>';
                    }
                }
            }
        }

        if (isloggedin() and !isguestuser()) { 
            if ($showprofile){
                $text .='<li class="h"><a href="'.$CFG->wwwroot.'/user/view.php?id='.$USER->id.'&course='.$COURSE->id.'">'.get_string('profile', 'moodle').'</a></li>';
            }
 
            // CALENDAR
            if ($showcalendar){
                 $text .='<li class="h"><a href="'.$CFG->wwwroot.'/calendar/view.php?view=upcoming&amp;course='.$COURSE->id.'">'.get_string('calendar', 'calendar').'</a></li>';
            }
        }

        $text .= '</ul><br clear="all" /></div>';

        return $text;
    }
}