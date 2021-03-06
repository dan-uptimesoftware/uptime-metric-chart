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

// Set the JSON header
header("Content-type: text/json");

include("uptimeDB.php");

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
if (isset($_GET['element'])){
	$elementList = explode(",", $_GET['element']);
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

// Enumerate monitors  	
if ($query_type == "monitors") {
    $sql = "select distinct erp.ERDC_PARAMETER_ID as erdc_param, eb.name, ep.short_description as short_desc, ep.parameter_type, ep.units, ep.data_type_id, description
            from erdc_retained_parameter erp
            join erdc_configuration ec on erp.configuration_id = ec.id
            join erdc_base eb on ec.erdc_base_id = eb.erdc_base_id
            join erdc_parameter ep on ep.erdc_parameter_id = erp.erdc_parameter_id
            join erdc_instance ei on ec.id = ei.configuration_id
            where ei.entity_id is not null
            order by name, description;
            ";

	$result = $db->execQuery($sql);
	foreach ($result as $row) {

			$my_data_type_id = $row['DATA_TYPE_ID'];
		    if ($my_data_type_id == 2 or $my_data_type_id == 3 or $my_data_type_id == 6) {				
				if ($row['UNITS'] == "") {
					$k = $row['ERDC_PARAM'] . "-" . $row['DATA_TYPE_ID'];
					$v = $row['NAME'] . " - " . $row['SHORT_DESC'];
					$json[$k] = $v;

				} else {
					$k = $row['ERDC_PARAM'] . "-" . $row['DATA_TYPE_ID'] ;
					$v = $row['NAME'] . " - " . $row['SHORT_DESC'] . " (" . $row['UNITS'] . ")";
					$json[$k] = $v;
				}
			}

	}
    // Echo results as JSON
    echo json_encode($json);
}

//Enumerate elements and monitor instance namesand associate with a particular monitor
elseif ($query_type == "elements_for_monitor") {
    $sql = "select distinct e.entity_id, e.name, e.display_name, erp.ERDC_PARAMETER_ID as erdc_param, ei.erdc_instance_id as erdc_instance, ei.name monitor_name 
            from erdc_retained_parameter erp
            join erdc_instance ei on erp.CONFIGURATION_ID = ei.configuration_id
            join entity e on e.ENTITY_ID = ei.ENTITY_ID
            where erp.ERDC_PARAMETER_ID = $erdc_parameter_id;
            ";

    	$result = $db->execQuery($sql);
		
		foreach ($result as $row) {
			$k = $row['ENTITY_ID'] . "-" . $row['ERDC_INSTANCE'];
			$v = $row['DISPLAY_NAME'] . " - " . $row['MONITOR_NAME'];
			$json[$k] = $v;
			}
		
    // Echo results as JSON
    echo json_encode($json);
}
	
elseif ($query_type == "ranged_objects") {
	
	$i = 0;
	foreach ($elementList as $element_id_and_erdc_id) {
		$ids = explode("-", $element_id_and_erdc_id);
		$element_id = $ids[0];
		$erdc_instance_id = $ids[1];
	

		$sql = "select * 
                from ranged_object ro
                where ro.instance_id = $erdc_instance_id               
                ";

    	$result = $db->execQuery($sql);
		
		foreach ($result as $row) {
			$json[$row['INSTANCE_ID']. "-" . $row['ID']]
			 = $row['OBJECT_NAME'];
		}
	}
	// Echo results as JSON
    echo json_encode($json);
				
}
	
	
//Enumerate metrics for specific monitor/element instance
elseif ($query_type == "servicemonitor") {
    
	//$elementList is an array where each item is elementID-erdcID 	
	$i = 0;
	if (($data_type_id == 2) ||($data_type_id == 3)) {
		foreach ($elementList as $element_id_and_erdc_id) {
		
			$ids = explode("-", $element_id_and_erdc_id);
			$element_id = $ids[0];
			$erdc_instance_id = $ids[1];
			
			if ($data_type_id == 2) {
				if ($db->dbType == "mysql")
				{
				$sql = "select * 
						from erdc_int_data eid
						where eid.erdc_instance_id = $erdc_instance_id
						and eid.erdc_parameter_id = $erdc_parameter_id 
						and sampletime > date_sub(now(),interval  ". $time_frame . " second)
						order by sampletime";
				}
				elseif($db->dbType == "oracle")
				{
				$sql = "select * 
						from erdc_int_data eid
						where eid.erdc_instance_id = $erdc_instance_id
						and eid.erdc_parameter_id = $erdc_parameter_id 
						and sampletime > sysdate - interval  '". $time_frame . "' second
						order by sampletime";
				}
				elseif($db->dbType == "mssql")
				{
					$sql = "select * 
							from erdc_int_data eid
							where eid.erdc_instance_id = $erdc_instance_id
							and eid.erdc_parameter_id = $erdc_parameter_id 
							and sampletime > DATEADD(second, -". $time_frame . ", GETDATE())
							order by sampletime";
				}
			} elseif ($data_type_id == 3) {
				if ($db->dbType == "mysql")
				{
				$sql = "select * 
						from erdc_decimal_data eid
						where eid.erdc_instance_id = $erdc_instance_id
						and eid.erdc_parameter_id = $erdc_parameter_id
						and sampletime > date_sub(now(),interval  ". $time_frame . " second)
						order by sampletime";
				}
				elseif($db->dbType == "oracle")
				{

				$sql = "select * 
						from erdc_decimal_data eid
						where eid.erdc_instance_id = $erdc_instance_id
						and eid.erdc_parameter_id = $erdc_parameter_id
						and sampletime >  sysdate - interval  '". $time_frame . "' second
						order by sampletime";


				}
				elseif($db->dbType == "mssql")
				{

				$sql = "select * 
						from erdc_decimal_data eid
						where eid.erdc_instance_id = $erdc_instance_id
						and eid.erdc_parameter_id = $erdc_parameter_id
						and sampletime > DATEADD(second, -". $time_frame . ", GETDATE())
						order by sampletime";
				}
			}
		
			else {
				die('Invalid query');
				}
				
				$result = $db->execQuery($sql);
			
				$from_time = strtotime("-" . (string)$time_frame . " seconds")-$offset;   
				foreach ($result as $row) {
					$sample_time = strtotime($row['SAMPLETIME'])-$offset;
					if ($sample_time >= $from_time) {
						$x = $sample_time * 1000;
						$y = (float)$row['VALUE'];
						$metric = array($x, $y);
						array_push($performanceData, $metric);
					   }
				}
				
				
				// Get Element Name
				$sql_element_name = "Select display_name from entity where entity_id = $element_id";
				$result = $db->execQuery($sql_element_name);
				$row = $result[0];
				$element_name = $row['DISPLAY_NAME'];	
				

				array_push($oneElement, $element_name);
				array_push($oneElement, $performanceData);
				array_push($json, $oneElement);
				$oneElement = array();
				$performanceData = array();
				$i++;
			
		
	}
}
	elseif ($data_type_id == 6) {
		
		foreach($objectList as $single_ranged_object) {
			
			$element_and_ranged = explode("-",$single_ranged_object);
			$erdc_instance_id = $element_and_ranged[0];
			$ranged_object_id = $element_and_ranged[1];

			if ($db->dbType == "mysql")
			{
			$sql = "select value,sample_time
				from ranged_object_value rov
				join ranged_object ro on rov.ranged_object_id = ro.id
				join erdc_instance ei on ei.erdc_instance_id = ro.instance_id				
				join erdc_configuration ec on ei.configuration_id = ec.id
				join erdc_parameter ep on ep.erdc_base_id = ec.erdc_base_id
				where rov.ranged_object_id = $ranged_object_id
				and ep.name = rov.name
				and ep.erdc_parameter_id = $erdc_parameter_id
				and rov.sample_time > date_sub(now(),interval  ". $time_frame . " second)
				order by rov.sample_time
				";
			}
			elseif ($db->dbType == "oracle")
			{

			$sql = "select value,sample_time
				from ranged_object_value rov
				join ranged_object ro on rov.ranged_object_id = ro.id
				join erdc_instance ei on ei.erdc_instance_id = ro.instance_id				
				join erdc_configuration ec on ei.configuration_id = ec.id
				join erdc_parameter ep on ep.erdc_base_id = ec.erdc_base_id
				where rov.ranged_object_id = $ranged_object_id
				and ep.name = rov.name
				and ep.erdc_parameter_id = $erdc_parameter_id
				and rov.sample_time > sysdate - interval  '". $time_frame . "' second
				order by rov.sample_time
				";

			}
			elseif ( $db->dbType == "mssql")
			{

			$sql = "select value,sample_time
				from ranged_object_value rov
				join ranged_object ro on rov.ranged_object_id = ro.id
				join erdc_instance ei on ei.erdc_instance_id = ro.instance_id				
				join erdc_configuration ec on ei.configuration_id = ec.id
				join erdc_parameter ep on ep.erdc_base_id = ec.erdc_base_id
				where rov.ranged_object_id = $ranged_object_id
				and ep.name = rov.name
				and ep.erdc_parameter_id = $erdc_parameter_id
				and rov.sample_time > DATEADD(second, -". $time_frame . ", GETDATE())
				order by rov.sample_time
				";

			}
			
				$result = $db->execQuery($sql);

				$from_time = strtotime("-" . (string)$time_frame . " seconds")-$offset;   
				foreach($result as $row) {
					$sample_time = strtotime($row['SAMPLE_TIME'])-$offset;
					if ($sample_time >= $from_time) {
						$x = $sample_time * 1000;
						$y = (float)$row['VALUE'];
						$metric = array($x, $y);
						array_push($performanceData, $metric);
					   }
					}
			
				// Get Element Name
				$sql_element_name = "select display_name from entity e 
										join erdc_instance ei on ei.entity_id = e.entity_id
										where erdc_instance_id = $erdc_instance_id";
				
				$result = $db->execQuery($sql_element_name);
				$row = $result[0];
				$element_name = $row['DISPLAY_NAME'];
				
				// For ranged data, use the object name & element name in the series legend
				$sql_object_name = "select object_name from ranged_object ro where ro.id = $ranged_object_id";

				$result = $db->execQuery($sql_object_name);
				$row = $result[0];
				$element_name = $row['object_name'] . " - " . $element_name;
				

			
			array_push($oneElement, $element_name);
			array_push($oneElement, $performanceData);
			array_push($json, $oneElement);
			$oneElement = array();
			$performanceData = array();
			$i++;
			
		
		}
	}
    // Echo results as JSON
    echo json_encode($json);
}

// Enumerate elements with performance counters   
elseif ($query_type == "elements_for_performance") {
    $sql = "select e.entity_id, e.display_name
            from entity e
            join erdc_base eb on eb.erdc_base_id = e.defining_erdc_base_id
            where e.entity_type_id not in (2, 3, 4, 5)
            and e.entity_subtype_id in (1,21, 12)
            and eb.name != 'MonitorDummyVmware'
            and e.monitored = 1
            order by display_name;
            ";
			
	    $result = $db->execQuery($sql);
		
		foreach ($result as $row) {
			$json[$row['DISPLAY_NAME']] = $row['ENTITY_ID'];
		}
	
	// Echo results as JSON
    echo json_encode($json);
}

// Get performance metrics
elseif ($query_type == "performance") {

	foreach ($elementList as $element_id) {


		if ($performance_monitor == "cpu") {
			if ($db->dbType == "mysql") {
			$sql = "Select ps.uptimehost_id, ps.sample_time, pa.cpu_usr, pa.cpu_sys , pa.cpu_wio
					from performance_sample ps 
					join performance_aggregate pa on pa.sample_id = ps.id
					where ps.uptimehost_id = $element_id					
					and ps.sample_time > date_sub(now(),interval  ". $time_frame . " second)
					order by ps.sample_time";
			}
			elseif($db->dbType == "oracle") {
			$sql = "Select ps.uptimehost_id, ps.sample_time, pa.cpu_usr, pa.cpu_sys , pa.cpu_wio
					from performance_sample ps 
					join performance_aggregate pa on pa.sample_id = ps.id
					where ps.uptimehost_id = $element_id					
					and ps.sample_time > sysdate - interval  '". $time_frame . "' second
					order by ps.sample_time";

			}
			elseif($db->dbType == "mssql")
			{
			$sql = "Select ps.uptimehost_id, ps.sample_time, pa.cpu_usr, pa.cpu_sys , pa.cpu_wio
				from performance_sample ps 
				join performance_aggregate pa on pa.sample_id = ps.id
				where ps.uptimehost_id = $element_id	
				and ps.sample_time > DATEADD(second, -". $time_frame . ", GETDATE())
				order by ps.sample_time";
			}

					
		}
		elseif ($performance_monitor == "used_swap_percent" or $performance_monitor == "worst_disk_usage" or $performance_monitor == "worst_disk_busy"){
			if ($db->dbType == "mysql") {
			$sql = "Select ps.uptimehost_id, ps.sample_time, pa.$performance_monitor as value
					from performance_sample ps 
					join performance_aggregate pa on pa.sample_id = ps.id
					where ps.uptimehost_id = $element_id
					and ps.sample_time > date_sub(now(),interval  ". $time_frame . " second)
					order by ps.sample_time";
			}
			elseif($db->dbType == "oracle") {
			$sql = "Select ps.uptimehost_id, ps.sample_time, pa.$performance_monitor as value
					from performance_sample ps 
					join performance_aggregate pa on pa.sample_id = ps.id
					where ps.uptimehost_id = $element_id
					and ps.sample_time > sysdate - interval  '". $time_frame . "' second
					order by ps.sample_time";


			}
			elseif($db->dbType == "mssql")
			{
			$sql = "Select ps.uptimehost_id, ps.sample_time, pa.$performance_monitor as value
				from performance_sample ps 
				join performance_aggregate pa on pa.sample_id = ps.id
				where ps.uptimehost_id = $element_id
				and ps.sample_time > DATEADD(second, -". $time_frame . ", GETDATE())
				order by ps.sample_time";

			}
		}
		elseif ($performance_monitor == "memory") {
			if ($db->dbType == 'mysql')
			{
			$sql = "Select ps.uptimehost_id, pa.sample_id, ps.sample_time, pa.free_mem, ec.memsize
					from performance_sample ps
					join performance_aggregate pa on pa.sample_id = ps.id
					join entity_configuration ec on ec.entity_id = ps.uptimehost_id
					where ps.uptimehost_id = $element_id
					and ps.sample_time > date_sub(now(),interval  ". $time_frame . " second)
					order by ps.sample_time";
			}
			elseif($db->dbType == "oracle") {
			$sql = "Select ps.uptimehost_id, pa.sample_id, ps.sample_time, pa.free_mem, ec.memsize
					from performance_sample ps
					join performance_aggregate pa on pa.sample_id = ps.id
					join entity_configuration ec on ec.entity_id = ps.uptimehost_id
					where ps.uptimehost_id = $element_id
					and ps.sample_time > sysdate - interval  '". $time_frame . "' second
					order by ps.sample_time";

			}
			elseif($db->dbType == "mssql")
			{
			$sql = "Select ps.uptimehost_id, pa.sample_id, ps.sample_time, pa.free_mem, ec.memsize
					from performance_sample ps
					join performance_aggregate pa on pa.sample_id = ps.id
					join entity_configuration ec on ec.entity_id = ps.uptimehost_id
					where ps.uptimehost_id = $element_id
					and ps.sample_time > DATEADD(second, -". $time_frame . ", GETDATE())
					order by ps.sample_time";
			}


		}
		else {
			die('Invalid query');
		}
     
			$result = $db->execQuery($sql);

			foreach($result as $row) {
				$sample_time = strtotime($row['SAMPLE_TIME'])-$offset;
				$x = $sample_time * 1000;
				if ($performance_monitor == "cpu") {
					$a = (float)$row['CPU_USR'];
					$b = (float)$row['CPU_SYS'];
					$c = (float)$row['CPU_WIO'];
					$y = ($a + $b + $c);
				} elseif ($performance_monitor == "memory") {
					$total_ram = (float)$row['MEMSIZE'];
					$free_ram = (float)$row['FREE_MEM'];
					$used_ram = $total_ram - $free_ram;
					$y = round(($used_ram / $total_ram * 100), 1);
				} elseif ($performance_monitor == "used_swap_percent" or $performance_monitor == "worst_disk_usage"
							or $performance_monitor == "worst_disk_busy") {
								$y = (float)$row['VALUE'];
					}
				$metric = array($x, $y);
				array_push($performanceData, $metric);
				}
			
		// Get Element Name
		$sql_element_name = "Select display_name from entity where entity_id = $element_id";
		$result = $db->execQuery($sql_element_name);
		$row = $result[0];
		$element_name = $row['DISPLAY_NAME'];			
		
		
		
		array_push($oneElement, $element_name);
		array_push($oneElement, $performanceData);
		//print_r($performanceData);
		array_push($json, $oneElement);
		$oneElement = array();
		$performanceData = array();
	}
    // Echo results as JSON
    echo json_encode($json);
}

elseif ($query_type == "listNetworkDevice") {
	$sql = "select e.entity_id, e.display_name from entity e 
			join entity_subtype es on es.entity_subtype_id = e.entity_subtype_id
			where es.name = 'Network Device' 
			order by es.name";
			
			
	$result = $db->execQuery($sql);
	foreach ($result as $row) {
		$json[$row['ENTITY_ID']] = $row['DISPLAY_NAME'];
	}
	
	
    // Echo results as JSON
    echo json_encode($json);
}

elseif ($query_type == "devicePort") {
	
	// Only supports 1 network device for now
	$sql = "select if_index, if_name 
			from net_device_port_config pc 
			where pc.entity_id = $elementList[0]";
			
	$result = $db->execQuery($sql);
	foreach($result as $row) {
			$json[$row['IF_INDEX']]
				= $row['IF_NAME'];
			}
	
    // Echo results as JSON
    echo json_encode($json);
}

// Get network device metrics
elseif ($query_type == "network") {
	$i = 0;
	foreach($ports as $singlePort) {

		if ($db->dbType == "mysql"){
		$sql = "select * from net_device_perf_port pp 
				join net_device_port_config pc on pp.if_index = pc.if_index 
				join net_device_perf_sample ps on ps.id = pp.sample_id
				where pc.entity_id = $elementList[0] 
				and ps.entity_id = $elementList[0] 
				and	pp.if_index = $singlePort
				and ps.sample_time > date_sub(now(),interval  ". $time_frame . " second)		  
				order by ps.sample_time";
		}
		elseif($db->dbType == "oracle"){
			$sql = "select * from net_device_perf_port pp 
				join net_device_port_config pc on pp.if_index = pc.if_index 
				join net_device_perf_sample ps on ps.id = pp.sample_id
				where pc.entity_id = $elementList[0] 
				and ps.entity_id = $elementList[0] 
				and	pp.if_index = $singlePort
				and ps.sample_time > sysdate - interval  '". $time_frame . "' second 		  
				order by ps.sample_time";

		}
		elseif($db->dbType == "mssql")
		{
			$sql = "select * from net_device_perf_port pp 
				join net_device_port_config pc on pp.if_index = pc.if_index 
				join net_device_perf_sample ps on ps.id = pp.sample_id
				where pc.entity_id = $elementList[0] 
				and ps.entity_id = $elementList[0] 
				and	pp.if_index = $singlePort
				and ps.sample_time > DATEADD(second, -". $time_frame . ", GETDATE())
				order by ps.sample_time";

		}

			$result = $db->execQuery($sql);
			
			$from_time = strtotime("-" . (string)$time_frame . " seconds")-$offset;   
			foreach ($result as $row) {
				$sample_time = strtotime($row['SAMPLE_TIME'])-$offset;
				$x = $sample_time * 1000;
				if(preg_match("/kbps/",$performance_monitor)) {

					$y = (float)$row[strtoupper("$performance_monitor")] / 1024;
				}
				else {
					$y = (float)$row[strtoupper("$performance_monitor")];
				}
				$metric = array($x, $y);
				array_push($performanceData, $metric);
			}
			
			// Get Port Name
			$sql_port_name = "Select if_name from net_device_port_config 
								where entity_id = $elementList[0] 
								and if_index = $singlePort";
			$result = $db->execQuery($sql_port_name);
			$row = $result[0];
			$port_name = $row['IF_NAME'];
			
		

		array_push($oneElement, $port_name);
		array_push($oneElement, $performanceData);
		array_push($json, $oneElement);
		$oneElement = array();
		$performanceData = array();
		$i++;
	
		
	}
    // Echo results as JSON
    echo json_encode($json);
	

}

    
// Unsupported request
else {
    echo "Error: Unsupported Request '$query_type'" . "</br>";
    echo "Acceptable types are 'elements', 'monitors', and 'metrics'" . "</br>";
    }

?>
