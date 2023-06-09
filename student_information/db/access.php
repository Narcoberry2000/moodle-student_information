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

/**
 * @package   local_student_information
 * @copyright 2023, Javier Lara  <Javier14@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $DB;  

// Funcion para crear el rol, indicando el nombre, abreviatura y descripción, además, se le puede indicar el arqueotipo.
// El Rol solamente se crea si no exista ya el registro de Parent

if ($DB->record_exists_select('role','name = "Parent"'))
    return;

$roleId = create_role("Parent", "parent", "Rol creado en fichero access", '');

//Creación de array con los contextos (los contextos son constantes ya creadas por moodle)
$contextlevels = [CONTEXT_USER, CONTEXT_MODULE, CONTEXT_COURSE];

//Creación de array de las capabilities que debe tener el rol
$myCapabilities = array("enrol/self:enrolself", 
                 "mod/assign:view",
                 "mod/assignment:view",
                 "mod/book:read",
                 "mod/chat:readlog",
                 "mod/data:view",
                 "mod/data:viewentry",
                 "mod/folder:view",
                 "mod/forum:viewdiscussion",
                 "mod/glossary:view",
                 "mod/page:view",
                 "mod/quiz:view",
                 "mod/resource:view",
                 "mod/wiki:viewpage",
                 "mod/workshop:view",
                 "moodle/category:viewcourselist",
                 "moodle/user:editprofile",
                 "moodle/user:readuserblogs",
                 "moodle/user:readuserposts",
                 "moodle/user:viewalldetails",
                 "moodle/user:viewdetails",
                 "moodle/user:viewuseractivitiesreport",
                 "report/log:viewtoday",
                 "report/outline:view",
                 "report/outline:viewuserreport",
                 "tool/policy:acceptbehalf",
                 "moodle/course:view"//Para ver los cursos sin participar
                );

//Le aplico mediante la siguiente función al rol los contextos
set_role_contextlevels($roleId, $contextlevels);

//Crear bucle por cada capability en el array de myCapabilities (foreach)
foreach ($myCapabilities as $myCapability)
    {
//Asignamos cada capability al rol mediante el id recogido, CAP_ALLOW es la constante que indica el tipo de permiso.
//El 1 es el contexto general, el 1 significa que se la asigna al rol sin ningún contexto.       
    assign_capability($myCapability, CAP_ALLOW, $roleId, 1);
    }

//Creamos el custommenuitems
//Almacenamos en una variable la sentencia a ejecutar.
$sql = "UPDATE mdl_config SET value = ? WHERE name = 'custommenuitems'";
$params = [
    '{ifhasarolename Parent}{getstring:local_student_information}primaryMenu{/getstring}|/local/student_information/index.php{/ifhasarolename}'
];

$DB->execute($sql, $params);


/*$sql = "UPDATE mdl_config SET value = '{ifhasarolename Parent}{getstring:local_student_information}primaryMenu{/getstring}|/local/student_information/index.php{/ifhasarolename}' WHERE name = 'custommenuitems'"; 
//Usamos la variable global de la base de datos

$DB->execute($sql);*/