<?php

//Función para obtener los usuarios hijo que estan asociados al padre
function get_child_users() 
    {
    global $CFG, $USER, $DB;

//Recuperamos los campos del usuario    
    $userfieldsapi = \core_user\fields::for_name();
    $allusernames = $userfieldsapi->get_sql('u', false, '', '', false)->selects;
//Hacemos un select de la información de los hijos del usuario actual enlazando los role_assignments, context y user.    
    if ($usercontexts = $DB->get_records_sql("SELECT c.instanceid, c.instanceid, $allusernames
                                                FROM {role_assignments} ra, {context} c, {user} u
                                               WHERE ra.userid = ?
                                                     AND ra.contextid = c.id
                                                     AND c.instanceid = u.id
                                                     AND c.contextlevel = ".CONTEXT_USER, array($USER->id))) {
    }
    
    return $usercontexts;
    }

//Función para extraer los cursos de un usuario por el identificador del usuario
function get_child_courses($id)
    {
    global $DB;
//Realizamos un select en el que extraemos todos los cursos asociados al identificador como parametro (del hijo)
    $sql = "SELECT c.id, c.fullname, c.shortname, c.summary, c.summaryformat, ue.timestart AS startdate, ue.timeend AS enddate FROM mdl_course c 
    JOIN mdl_user_enrolments ue
    JOIN mdl_user u ON u.id = ue.userid 
    JOIN mdl_enrol e ON e.id = ue.enrolid  
    WHERE u.id = $id 
    AND c.id = e.courseid";

//get_records_sql nos devuelve el resultado de la sentencia
    return $DB->get_records_sql($sql);
    }


//No se si realmente hace falta
function enroll_parent_to_child_courses() {
    global $DB;
    $cursos_enrol = get_child_courses(3);
    $userid = $USER->id;
    //$tstart = time();
    //$tend = strtotime("+1 month");   
    
    $i = 0;
        
    foreach ($cursos_enrol as $curso_enrol)
        {
        $instance = $DB->get_record('enrol', ['courseid' => $curso_enrol[$i++]->id, 'enrol' => 'manual']);
        $enrolplugin = enrol_get_plugin($instance->enrol);
        $enrolplugin->enrol_user($instance, $userid, $role->id, $timestart, $timeend);
        }
    }

//Función para compronar si el rol del usuario es de tipo Parent
function is_user_parent($id)
    {
    global $DB;
//Recogemos el contexto y base a este recogemos el rol        
    
    $sql = "SELECT EXISTS (SELECT * FROM mdl_role AS r INNER JOIN mdl_role_assignments AS ra ON r.id = ra.roleid AND ra.userid = $id WHERE r.name = 'Parent');";

    return $DB->get_records_sql($sql);
    }


    /*
    
//Indicamos las fechas en las que se deben encontrar los eventos y los almacenamos en variables
$tstart = time();
$tend = strtotime("+1 month");


// Obtener los eventos del calendario (necesitamos solo los ids de los usuarios)
$eventos = calendar_get_events($tstart, $tend, $identificadoresUser[0], false, $identificadoresCourse[1]);


//Filtramos los eventos quitando aquellos que no sean del tipo (eventtype = DUE)
$new_events = filter_events($eventos);



function filter_events($events)
    {
     $filtered_events = array();

    $i=0;
    foreach ($events as $event) 
        {
          if($event->eventtype == "due")
            {
                print "soy due";
             $filtered_events[$i++] = $event;   
            }
            else{
                print "no soy due";
            }
        }

    return $filtered_events;
    }*/
