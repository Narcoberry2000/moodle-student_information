<?php
namespace local_student_information\task;

defined('MOODLE_INTERNAL') || die();
//global $CFG, $DB;

//require('$CFG->dirroot/../../../config.php');



class studentinformation extends \core\task\scheduled_task {

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
    	error_log("He leido la string de la tareas");
        return get_string('cleanuptask', 'local_student_information');
    }

    /**
     * Remove old entries from table block_recent_activity
     */
    public function execute() {
    	global $CFG, $DB;

    	require_once("$CFG->dirroot/lib/moodlelib.php");
// Utilizamos el usuario no_reply para enviarlo
    	$userFrom = \core_user::get_noreply_user();
// Recogemos la respuesta de los hijos con un ultimo acceso superior a 2 días
		$usersLastAccess = get_parent_lastaccess();
// Creamos el tema del mensaje        
        $subject = "Notificación de último acceso";
        
// Array para almacenar todos los hijos que no han accedido en los ultimos dos días por cada padre        
        $padresHijos = array();
        $padre = -1;
        $i = 0;
        foreach($usersLastAccess as $access)
            {
// Accedemos solo si el padre ha cambiado                       
            if ($access->id != $padre)
                {
// Para no acceder la primera vez                    
                if ($padre != -1)
                    {
// Recogemos el cuerpo del mensaje para el padre e hijos enviados
                    $cuerpoMSJ = $this->createBodyMail($padresHijos);
// Enviamos el email al padre en cuestión enviando los parametros
                    email_to_user(\core_user::get_user($padresHijos[0]->id), $userFrom, $subject, $cuerpoMSJ, '', '', '', true);
// Como cambiamos de padre ponemos a cero el contador y vaciamos el array                    
                    $padresHijos = array();
                    $i = 0;                    
                    }
// Igualamos la variable para recoger el padre anterior                    
                $padre = $access->id;    
                }
// Metemos el registro del hijo (Con alguna información repetida)                
            $padresHijos[$i++] = $access;
            }
// Este codigo se ejecuta para envíar tambien el ultimo de todos los padres            
        $cuerpoMSJ = $this->createBodyMail($padresHijos);
        email_to_user(\core_user::get_user($padresHijos[0]->id), $userFrom, $subject, $cuerpoMSJ, '', '', '', true);
	}

// ****************************************
// Función para crear el cuerpo del mensaje
// ****************************************    

function createBodyMail($parent)
    {
// Vamos añadiendo el contenido del mensaje para el padre e hijos recibidos
    $bodyMail = "Estimado Sr/Sra " . $parent[0]->nombre_padre . " " . $parent[0]->apellido_padre . ",";
    $bodyMail .= "\n\n";
// Recogemos la cantidad de hijos    
    $longitud = count($parent);
// Cambiamos el texto depediendo de si hay un hijo o varios    
    if ($longitud > 1)
        $bodyMail .= "Este mensaje es para notificarle el último acceso a la plataforma de sus hijos.\n ";
    else
        $bodyMail .= "Este mensaje es para notificarle el último acceso a la plataforma de su hijo.\n ";
    $bodyMail .= "\n";
//  Indicamos la información de los hijos en el formato apropiado para cada hijo recogido   
    foreach ($parent as $hijo)
        {
        $fecha = date('Y-m-d', $hijo->lasttime);
        $bodyMail .= "El último acceso de ". $hijo->nombre_hijo . " " . $hijo->apellido_hijo . " fue el día " . $fecha . "\n";
        }
    $bodyMail .= "\n\n";
    $bodyMail .= "Un cordial saludo.";
// Devolvemos el cuerpo del mensaje    
    return $bodyMail;    
    }
}

// ***************************************************************************************
// Función para recoger los hijos y padres para los hijos que no acceden desde hace 2 días
// ***************************************************************************************

function get_parent_lastaccess()
    {
    global $DB;
//Realizamos un select en el que extraemos todos los cursos asociados al identificador como parametro (del hijo)
    $sql = "SELECT ROW_NUMBER() OVER(ORDER BY up.id) AS idn, up.id, up.firstname AS nombre_padre, up.lastname AS apellido_padre, up.email, u.firstname AS nombre_hijo, u.lastname AS apellido_hijo, MAX(ll.timecreated) AS lastTime FROM mdl_user u INNER JOIN mdl_logstore_standard_log ll ON u.id = ll.userid INNER JOIN mdl_context c ON u.id = c.instanceid INNER JOIN mdl_role_assignments ra ON c.id = ra.contextid INNER JOIN mdl_user up ON ra.userid = up.id WHERE c.contextlevel = 30 AND u.id NOT IN (SELECT DISTINCT l.userid FROM mdl_logstore_standard_log l WHERE l.timecreated >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 2 DAY))) GROUP BY ll.userid ORDER BY up.id;";


//get_records_sql nos devuelve el resultado de la sentencia
    return $DB->get_records_sql($sql);
    }
