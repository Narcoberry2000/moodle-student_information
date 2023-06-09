<?php


use navigation_node;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/my/lib.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/calendar_month/block_calendar_month.php');
require_once($CFG->dirroot . '/local/student_information/functions.php');

defined('MOODLE_INTERNAL') || die();

redirect_if_major_upgrade_required();

//Se necesita de una cuenta para acceder
require_login();

//Titulo de pagina
$strmymoodle = "Hijos/Tutorados";//get_string('myhome');

$userid = $USER->id;  // Usuario dueño de la pagina
$context = context_user::instance($USER->id); //El contexto del usuario

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
$PAGE->set_url('/local/student_information/mentees.php', $params);
$PAGE->set_pagelayout('course');
// Sirve para darle un limite al tamaño del bloque
$PAGE->add_body_class('limitedwidth');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_subpage($currentpage->id  . "_2");
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
    
//Si el registro no existe, si el bloque no existe entonces lo añadimos el bloque mentees  
if(!$DB->record_exists('block_instances', array('parentcontextid' =>  $context->id, 'subpagepattern' =>  $currentpage->id  . "_2", 'blockname' => "mentees")))
    {
    $record = new stdClass(); 
    $record->blockname = 'mentees'; // Nombre del bloque a introducir (Y encargado de mostrar el bloque especifico)
    $record->parentcontextid = $context->id; //El identificador del usuario que renderiza la pagina
    $record->pagetypepattern = 'my-index'; //Tipo de pagina
    $record->subpagepattern = $currentpage->id  . "_2"; //Identificador de la pagina en la que se debe mostrar el bloque
    $record->showinsubcontexts = '0'; 
    $record->defaultweight = 0;// Le indicamos que la posición que debe tomar el bloque mentees es la primera 
    $record->defaultregion = 'content'; //Se indica en la región de la pagina que se quiere introducir
    $record->timecreated = time(); // time() da la fecha y hora actual en el formato necesario para la BD
    $record->timemodified = time();
    $DB->insert_record('block_instances', $record, false); //Sentencia para introducir el registro en la base de datos
    }

//Renderizamos el header
echo $OUTPUT->header();
//Renderizamos el contenido de la pagina
echo $OUTPUT->custom_block_region('content');
//Renderizamos el footer
echo $OUTPUT->footer();

