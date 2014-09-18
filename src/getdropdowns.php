<?php 

//DISCLAIMER:
//LIMITATION OF LIABILITY: uptime software does not warrant that software obtained
//from the Grid will meet your requirements or that operation of the software will
//be uninterrupted or error free. By downloading and installing software obtained
//from the Grid you assume all responsibility for selecting the appropriate
//software to achieve your intended results and for the results obtained from use
//of software downloaded from the Grid. uptime software will not be liable to you
//or any party related to you for any loss or damages resulting from any claims,
//demands or actions arising out of use of software obtained from the Grid. In no
//event shall uptime software be liable to you or any party related to you for any
//indirect, incidental, consequential, special, exemplary or punitive damages or
//lost profits even if uptime software has been advised of the possibility of such
//damages.



require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/classLoader.inc";


session_start();

$user_name =  $_SESSION['current_user']->getName();
$user_pass = $_SESSION['current_user']->getPassword();

session_write_close();


// Set the JSON header
header("Content-type: text/json");


include("uptimeDB.php");
include("uptimeApi.php");

if (isset($_GET['query_type'])){
    $query_type = $_GET['query_type'];
}
if (isset($_GET['uptime_offest'])){
    $offset = $_GET['uptime_offest'];
}
if (isset($_GET['time_frame'])){
    $time_frame = $_GET['time_frame'];
}
if (isset($_GET['monitor'])){
    $service_monitor = explode("-", $_GET['monitor']);
    $erdc_parameter_id = $service_monitor[0];
    if ( count ($service_monitor) > 1)
    {
        $data_type_id = $service_monitor[1];
    }
    $performance_monitor = $_GET['monitor'];

}
if (isset($_GET['elements'])){
    $elementList = explode(",", $_GET['elements']);
}
if (isset($_GET['groups'])){
    $groupList = explode(",", $_GET['groups']);
}

if (isset($_GET['views'])){
    $viewList = explode(",", $_GET['views']);
}

if (isset($_GET['port'])){
    $ports = explode(",", $_GET['port']);
}
if (isset($_GET['object_list'])){
    $objectList = explode(",", $_GET['object_list']);
}
$json = array();
$oneElement = array();
$performanceData = array();
//date_default_timezone_set('UTC');





$uptime_api_username = $user_name;
$uptime_api_password = $user_pass;
$uptime_api_hostname = "localhost";     // up.time Controller hostname (usually localhost, but not always)
$uptime_api_port = 9997;
$uptime_api_version = "v1";
$uptime_api_ssl = true;

// Create API object
$uptime_api = new uptimeApi($uptime_api_username, $uptime_api_password, $uptime_api_hostname, $uptime_api_port, $uptime_api_version, $uptime_api_ssl);



// Enumerate elements with performance counters   
if ($query_type == "elements_for_performance") {
    $elements = $uptime_api->getElements("type=Server&isMonitored=1");

    foreach ($elements as $d) {
        $json[$d['name']] = $d['id'];
    }

    //sort alphabeticaly on name instead of their order on ID
    ksort($json);  

    echo json_encode($json);
}

// Enumerate elements with performance counters   
elseif ($query_type == "groups_for_performance") {


    $groups = $uptime_api->getGroups();

    foreach ($groups as $d) {
        $json[$d['name']] = $d['id'];
    }

    //sort alphabeticaly on name instead of their order on ID
    ksort($json);  

    echo json_encode($json);
}

// Enumerate elements with performance counters   
elseif ($query_type == "views_for_performance") {
    
    $db = new uptimeDB;
    if ($db->connectDB())
    {
        echo "";

    }
    else
    {
        echo "unable to connect to DB exiting";    
        exit(1);
    }


    $sql = "select id, name from entity_view order by name asc;
            ";
            
        $result = $db->execQuery($sql);
        
        foreach ($result as $row) {
            $json[$row['NAME']] = $row['ID'];
        }
    
    //sort alphabeticaly on name instead of their order on ID
    ksort($json);    

    // Echo results as JSON
    echo json_encode($json);
}

// Unsupported request
else {
    echo "Error: Unsupported Request '$query_type'" . "</br>";
    echo "Acceptable types are 'elements', 'monitors', and 'metrics'" . "</br>";
    }

?>