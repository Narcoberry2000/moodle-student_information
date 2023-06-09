<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

//INCLUDES 
use navigation_node;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/my/lib.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/local/student_information/functions.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/calendar_month/block_calendar_month.php');

defined('MOODLE_INTERNAL') || die();


redirect_if_major_upgrade_required();

//Se necesita de una cuenta para acceder
require_login();

//Titulo de pagina
$strmymoodle = get_string("primaryMenu", "local_student_information");

$userid = $USER->id;  // Usuario dueño de la pagina
$context = context_user::instance($USER->id); //El contexto del usuario
error_log(print_r($USER->id,true));

//Llamamos a la función is_user_parent para evitar el acceso a cualquier usuario que no tenga el rol Parent
if (!is_user_parent($userid))
    {
//Si el rol no es tipo Parent le indicamos que no tiene permisos y usamos exit() para evitar que ejecute la pagina y se almacenen registros en la base de datos (My_pages)        
    print("No tienes permisos para acceder a este recurso");
    exit();
    }

$pagetitle = $strmymoodle;

//Preguntamos si existe una pagina asociada al identificador del padre que accede y que el name sea de tipo __parent
if (!$DB->record_exists('my_pages', array('userid' =>  $userid, 'name' =>  "__parent", 'private' => MY_PAGE_PRIVATE)))
    {
    //Si no existe creamos el registro de la pagina en la que mostraremos la información para ese usuario padre
    $record = new stdClass(); 
    $record->userid = $userid;
    $record->name = "__parent";
    $record->private = MY_PAGE_PRIVATE;
    $DB->insert_record('my_pages', $record, false); //Sentencia para introducir el registro en la base de datos
    }
//Si la pagina actual no es del tipo __parent para el usuario en cuestion y privada entonces sacamos una excepción si no la almacenamos en currentpage
if (!$currentpage = my_get_page($userid, MY_PAGE_PRIVATE, '__parent')) 
    {
    throw new \moodle_exception('mymoodlesetup');
    }

// Configuramos la pagina
$params = array();
$PAGE->set_context($context);
$PAGE->set_url('/local/student_information/index.php', $params);
$PAGE->set_pagelayout('course');
// Sirve para darle un limite al tamaño del bloque
$PAGE->add_body_class('limitedwidth');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_subpage($currentpage->id . "_1");
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle); 

$class = 'core\navigation\views\secondary';
$secondary = new $class($PAGE);
$secondary -> initialise();



$node1 = navigation_node::create(get_string('tasksMenuOption', 'local_student_information'), new moodle_url('/local/student_information/index.php'), navigation_node::TYPE_CUSTOM);
$node2 = navigation_node::create(get_string('userMenuDedication', 'local_student_information'), new moodle_url('/local/student_information/studentdedication.php'), navigation_node::TYPE_CUSTOM);
$node3 = navigation_node::create(get_string('menteesMenu', 'local_student_information'), new moodle_url('/local/student_information/mentees.php'), navigation_node::TYPE_CUSTOM);



$nodes = array();
$nodes[0] = $node1;
$nodes[1] = $node2;
$nodes[2] = $node3;

$secondary->add_node($node1);
$secondary->add_node($node2);
$secondary->add_node($node3);

$PAGE->set_secondarynav($secondary);

//Si el registro existe, si el bloque existe entonces lo eliminamos
if($DB->record_exists('block_instances', array('parentcontextid' =>  $context->id, 'subpagepattern' =>  $currentpage->id, 'blockname' => "timeline")))
    {
    $DB->delete_records('block_instances', array('parentcontextid' =>  $context->id, 'subpagepattern' =>  $currentpage->id, 'blockname' => "timeline"));
    }

//Si el registro existe, si el bloque "calendar_month" existe entonces lo eliminamos    
if($DB->record_exists('block_instances', array('parentcontextid' =>  $context->id, 'subpagepattern' =>  $currentpage->id, 'blockname' => "calendar_month")))
    {
    $DB->delete_records('block_instances', array('parentcontextid' =>  $context->id, 'subpagepattern' =>  $currentpage->id, 'blockname' => "calendar_month"));
    }     

//Preguntamos si el registro no existe, si el bloque no existe entonces lo añadimos el bloque calendar_parent(creado mediante el plguin) 
if(!$DB->record_exists('block_instances', array('parentcontextid' =>  $context->id, 'subpagepattern' =>  $currentpage->id  . "_1", 'blockname' => "calendar_parent")))
    {
    $record = new stdClass(); 
    $record->blockname = 'calendar_parent'; // Nombre del bloque a introducir (Y encargado de mostrar el bloque especifico)
    $record->parentcontextid = $context->id; //El identificador del usuario que renderiza la pagina
    $record->pagetypepattern = 'my-index'; //Tipo de pagina
    $record->subpagepattern = $currentpage->id . "_1"; //Identificador de la pagina en la que se debe mostrar el bloque
    $record->showinsubcontexts = '0'; 
    $record->defaultweight = '1'; //Indica la posición en la que se introduce el bloque 
    $record->defaultregion = 'content'; //Se indica en la región de la pagina que se quiere introducir
    $record->timecreated = time(); // time() da la fecha y hora actual en el formato necesario para la BD
    $record->timemodified = time();
    $DB->insert_record('block_instances', $record, false); //Sentencia para introducir el registro en la base de datos    
//Si necesito cambiar un registro en lugar de añadirlo se utiliza update_record();
    }

//Renderizamos el header
echo $OUTPUT->header();

//Renderizamos el contenido de la pagina
echo $OUTPUT->custom_block_region('content');

//Usamos la función que nos devuelve los registros de los hijos y lo almacenamos en una variable
$usuariosHijos = get_child_users();

//Creamos la nueva array donde almacenaremos los identificadores de los usuarios hijos/tutorados
$identificadoresUser = array();

//Almacenamos cada identificador de los hijos recorriendo cada hijo y sacando exclusivamente el id.
$i = 0;
foreach ($usuariosHijos as $hijo) 
    {
    $identificadoresUser[$i++] = $hijo->instanceid;
    }

//A continuación en base a los identificadores de los usuarios sacamos los cursos de cada usuario
$UserCourses = get_child_courses($identificadoresUser[0]);

//Almacenamos cada identificador de los cursos recorriendo cada curso y sacando exclusivamente el id.
$i = 0;
$identificadoresCourse = array();
foreach ($UserCourses as $course) 
    {
    $identificadoresCourse[$i++] = $course->id;
    }

//Ahora en base a los cursos de cada hijo matriculamos al usuario actual a cada curso (Parent)
$j = 0;
//For each para obtener los cursos de cada hijo
foreach($identificadoresUser as $identificadorUser)
    {
    $UserCourses = get_child_courses($identificadoresUser[$j++]);
    $i = 0;
    $identificadoresCourse = array();
//For each para obtener todos los identificadores de los cursos del usuario que toca    
    foreach ($UserCourses as $course) 
        {
        $identificadoresCourse[$i++] = $course->id;
        }

/*Por cada identificador que se haya almacenado se ejecuta una sentencia que pregunta si para ese curso el hijo esta matriculado y nosotros no  
$IdentificadorUser que es el del hijo y en los que $userid no (que es el identificador del padre) esto para no matricularnos 2 veces en el mismo curso*/       
    foreach ($identificadoresCourse as $identificadorCourse)
        {
        $sql = "SELECT c.*
        FROM mdl_course c
        LEFT JOIN mdl_enrol e ON e.courseid = c.id AND e.enrol = 'manual'
        LEFT JOIN mdl_user_enrolments ue ON ue.enrolid = e.id AND ue.userid = $userid
        WHERE ue.id IS NULL
        AND c.category = 1
        AND c.id IN (
        SELECT c.id
        FROM mdl_course c
        JOIN mdl_user_enrolments ue ON ue.enrolid = e.id AND ue.userid = $identificadorUser
        JOIN mdl_user u ON u.id = ue.userid 
        JOIN mdl_enrol e ON e.id = ue.enrolid
        WHERE u.id = $identificadorUser
        AND c.id = e.courseid
        )
        AND c.id NOT IN (
        SELECT c.id
        FROM mdl_course c
        JOIN mdl_user_enrolments ue ON ue.enrolid = e.id AND ue.userid = $userid
        JOIN mdl_user u ON u.id = ue.userid 
        JOIN mdl_enrol e ON e.id = ue.enrolid
        WHERE u.id = $userid
        AND c.id = e.courseid
        )";
//Si existe algun curso para el que el hijo esta matriculado y nosotros no lo registramos       
        if($DB->record_exists_sql($sql))
            {
            $tstart = time();
            $tend = strtotime("+1 month");
//Recogemos el curso
            $instance = $DB->get_record('enrol', ['courseid' => $identificadorCourse, 'enrol' => 'manual']);
            $enrolplugin = enrol_get_plugin($instance->enrol);
//Y le damos el curso, el identificador del usuario a matricular y el rol en el cual se registrara (Encontrar como indicar que es Parent y no poniendolo a mano)            
            $enrolplugin->enrol_user($instance, $userid, 170, $tstart, $tend);
            }
        }
    }

//Renderizamos el footer
echo $OUTPUT->footer();

// Trigger dashboard has been viewed event.
$eventparams = array('context' => $context);
$event = \core\event\dashboard_viewed::create($eventparams);
$event->trigger();