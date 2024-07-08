<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Allow: GET, POST, OPTIONS, PUT, DELETE');
header('Content-Type: application/json');

//<!--========== PHP CONNECTION TO DATABASE ==========-->
$host = "localhost";
$username = "root";
$pass = "";
$dbname = "jfb";
$method = $_SERVER['REQUEST_METHOD'];
$conn = mysqli_connect($host, $username, $pass, $dbname); //create connection

//check connection
if(!$conn){
      echo "Error: No se pudo conectar a MySQL." . PHP_EOL;
      exit;
}

//funciones de utilidad para el server

function currentPeriod(){
	$time_period="";
	$exist_period=false;
	$start_current_period = "";
	$end_current_period = "";		
	$current_period ="";
	$isOpen=0;
	
	$Q_time_period = mysqli_query($GLOBALS['conn'], "SELECT YEAR(NOW()) AS actual, YEAR(NOW() - INTERVAL 1 YEAR) AS anterior");
	$row_time_period = mysqli_fetch_assoc($Q_time_period);
	$time_period = $row_time_period['anterior']."-".$row_time_period['actual'];
	
	$Q_exist = mysqli_query($GLOBALS['conn'], "SELECT 1 FROM period WHERE name = '$time_period'");
	$exist_period = mysqli_num_rows($Q_exist) > 0;		
	
	if($exist_period){
		$Q_current_period = mysqli_query($GLOBALS['conn'], "select * from period order by id DESC limit 1");
		$row_current_period = mysqli_fetch_assoc($Q_current_period);
		$start_current_period = $row_current_period['start_date'];
		$end_current_period = $row_current_period['end_date'];
		$current_period = $row_current_period['name'];
		$isOpen = $row_current_period['isOpen'];
	}
	
	$obj = array('current_period'=>$current_period,'time_period'=>$time_period,'start_current_period'=>$start_current_period,'end_current_period'=>$end_current_period,'exist_period'=>$exist_period,'isOpen'=>$isOpen);		
	return $obj; 	
}

function ifPersonExist($cedula){
  $existe = false;
  $resultado = mysqli_query($GLOBALS['conn'], "SELECT 1 FROM person WHERE cedula = '$cedula'");
  if(mysqli_num_rows($resultado) > 0) $existe = true;
  return $existe;	
}

function ifUserExist($user_name){
	$existe = false;
	$resultado = mysqli_query($GLOBALS['conn'], "SELECT 1 FROM user WHERE user_name = '$user_name'");
	if(mysqli_num_rows($resultado) > 0) $existe = true;
	return $existe;	
  }

function ifStudentExistRegistration($student_id){
	$existe = false;
	$current_period = currentPeriod()['current_period'];

	$resultado = mysqli_query($GLOBALS['conn'], "SELECT 1 FROM registration WHERE student_id = $student_id and period='$current_period'");
	if(mysqli_num_rows($resultado) > 0) $existe = true;
	return $existe;	
  }

function returnIdParent($cedula) {
    $resultado = mysqli_query($GLOBALS['conn'], "SELECT id FROM person WHERE cedula = '$cedula'");
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc($resultado);
        return $row['id'];
    } else {
        return null; 
    }
}

function returnDatPerson($id) {
	$obj = array();
    $resultado = mysqli_query($GLOBALS['conn'], "SELECT * FROM person WHERE id = $id");
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc($resultado);
		$obj = array(
			'id'=>$row['id'],
			'cedula' => $row['cedula'],
			'name' => $row['name'],
			'second_name' => $row['second_name'],
			'last_name'	=> $row['last_name'],
			'second_last_name' => $row['second_last_name'],
			'email'	=> $row['email'],
			'phone'	=> $row['phone'],
			'birthday' =>$row['birthday'],
			'gender' => $row['gender'],
			'address' => $row['address']
		);
    }
	return $obj;	
}


function returnListSection($period){
	$obj = array();
	$consulta = "SELECT * from section where period='$period'";		
	$resultado = mysqli_query($GLOBALS['conn'], $consulta);
	if ($resultado && mysqli_num_rows($resultado) > 0) {
		while($row = mysqli_fetch_assoc($resultado)) {
			$obj[]=array('id'=>$row['id'],'year'=>$row['year'],'section_name'=>$row['section_name'],'teacher_id'=>returnDatPerson($row['teacher_id']),'quota'=>$row['quota'],'period'=>$row['period']);
		}   			
	}
	return $obj;
}



function returnDatSection($id) {
	$obj = array();
    $resultado = mysqli_query($GLOBALS['conn'], "SELECT * FROM section WHERE id = $id");
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc($resultado);
		$obj=array('id'=>$row['id'],'year'=>$row['year'],'section_name'=>$row['section_name'],'teacher_id'=>returnDatPerson($row['teacher_id']),'quota'=>$row['quota'],'period'=>$row['period']);
    }
	return $obj;	
}


function returnRegisterList(){
	$obj = array();
	$consulta = "SELECT * from registration";		
	$resultado = mysqli_query($GLOBALS['conn'], $consulta);

		while($row = mysqli_fetch_assoc($resultado)) {
			$obj[]=array('id'=>$row['id'],'year'=>$row['year'],'section_id'=>returnDatSection($row['section_id']),'student_id'=>returnDatPerson($row['student_id']),'parent_id'=>returnDatPerson($row['parent_id']),'period'=>$row['period']);
		}   			
	
	return $obj;
}

function returnNextSection($_year,$period){
	$year = ['primero', 'segundo', 'tercero', 'cuarto', 'quinto'];
	$section = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
	$next_section="";
	$next_year="";	
	$obj =array();
	$consulta = "SELECT * from section where period='$period' and year='$_year' order by section_name DESC limit 1";
	$resultado = mysqli_query($GLOBALS['conn'], $consulta);
	if ($resultado && mysqli_num_rows($resultado) > 0) {
		while($row = mysqli_fetch_assoc($resultado)) {
			if (in_array($row['year'], $year)) {
				if (in_array($row['section_name'], $section)) {
					for($index=0; $index<=count($section);$index++){
						if($section[$index] == $row['section_name']){
							if($index < count($section)){
								$next_year = $row['year'];
								$next_section = $section[$index+1];	
								break;
							}							
						}
					}
				}
				else{
					$next_year = $row['year'];
					$next_section = $section[0];						
				}
			}
			$obj=array('year'=>$next_year,'next_section'=>$next_section);			
		}   			
	}else{
		$obj=array('year'=>$_year,'next_section'=>$section[0]);			
	}
	return $obj;
}


//-------------------------------------

//*******Metodos de Comunicacion con el Front *************

if ($method == "OPTIONS") {
    die();
}

if ($method == "POST") {
    try {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        //verifica el inicio de sesion
        if(isset($data['login'])){
            
            $query = "SELECT * FROM user WHERE user_name='".$data['user']."'";
            $result = mysqli_query($conn, $query);
            
            if (!$result) {
                // Error en la consulta
                throw new Exception("Error en la consulta SQL: " . mysqli_error($conn));
            }

            $row = mysqli_fetch_array($result);
            
            $userExist = false;
            $pass = false;
            $user_id = '';
            $isAdmin = 0;
            
            if(mysqli_num_rows($result) > 0){
                $userExist = true;
				if (password_verify($data['password'], $row['password'] )){
                    $pass = true;
                    $user_id = $row['user_id'];
                    $isAdmin = $row['isAdmin'] ;
                }
            }
            
            $response = array('exists' => $userExist, 'pass' => $pass, 'user_id' => $user_id, 'isAdmin' => $isAdmin);
            echo json_encode($response);
        }
		
		if(isset($data['SendPeriodData'])){
			$start_date = mysqli_real_escape_string($conn, $data['sinceDate']);
			$end_date = mysqli_real_escape_string($conn, $data['toDate']);
			$name = mysqli_real_escape_string($conn, $data['name']);
			
            $query = "insert into period (start_date,end_date,name) values('$start_date','$end_date','$name')";
            $result = mysqli_query($conn, $query);
            
            if (!$result) {
                // Error en la consulta
                throw new Exception("Error en la consulta SQL: " . mysqli_error($conn));
            }
			
			$response = array('message' => 'ok');
			echo json_encode($response);
		}

		
		if(isset($data['update'])){ /* Actualiza segun un campo con su valor y  la tabla requerida*/
			$campo = mysqli_real_escape_string($conn, $data['campo']);
			$valor = mysqli_real_escape_string($conn, strtolower($data['valor']));
			$tabla = mysqli_real_escape_string($conn, $data['tabla']);
			
            $query = "update $tabla SET $campo=$valor where user_id=".$data['update'];
            $result = mysqli_query($conn, $query);
            
            if (!$result) {
                // Error en la consulta
                throw new Exception("Error en la consulta SQL: " . mysqli_error($conn));
            }
			
			$response = array('message' => 'Datos Guardados con exito...');
			echo json_encode($response);
		}

				
		if (isset($data['inscribe'])) {
			$message = 'Insert is Null in Inscribe';

			function insertPerson($conn, $data) {
				// Escapa los valores para evitar inyección de SQL
				$cedula = mysqli_real_escape_string($conn, $data['cedula']);
				$name = mysqli_real_escape_string($conn, strtolower($data['name']));
				$second_name = mysqli_real_escape_string($conn, strtolower($data['second_name']));
				$last_name = mysqli_real_escape_string($conn, strtolower($data['last_name']));
				$second_last_name = mysqli_real_escape_string($conn, strtolower($data['second_last_name']));
				$email = mysqli_real_escape_string($conn, strtolower($data['email']));
				$phone = mysqli_real_escape_string($conn, $data['phone']);
				$birthday = mysqli_real_escape_string($conn, $data['birthday']);
				$gender = mysqli_real_escape_string($conn, $data['gender']);
				$address = mysqli_real_escape_string($conn, strtolower($data['address']));
				
				// ...otros campos

				$query = "INSERT INTO person (cedula, name, second_name,last_name,second_last_name,email,phone,birthday,gender,address) VALUES ('$cedula', '$name','$second_name','$last_name','$second_last_name','$email','$phone','$birthday','$gender','$address')";
				$result = mysqli_query($conn, $query);

				if (!$result) {
					throw new Exception("Error en la consulta SQL: " . mysqli_error($conn));
				}

				return mysqli_insert_id($conn);
			}

			if (!ifPersonExist($data['parent']['cedula'])) {
				$endIdParent = insertPerson($conn, $data['parent']);
				$QinserParent = "INSERT INTO parent (person_id) VALUES ($endIdParent)";
				$result_parent = mysqli_query($conn, $QinserParent);
				$message = 'Usuario Inscrito con exito';
				$icon = 'success';

			}else{
				$endIdParent = returnIdParent($data['parent']['cedula']);
			}

			if (!ifPersonExist($data['student']['cedula'])) {
				$endIdStudent = insertPerson($conn, $data['student']);
				$QinserStudent = "INSERT INTO student (person_id, parent_id) VALUES ($endIdStudent, $endIdParent)";
				$result_student = mysqli_query($conn, $QinserStudent);
				$message = 'Usuario Inscrito con exito';
				$icon = 'success';

			}else{
				$endIdStudent = returnIdParent($data['student']['cedula']);
			}

			if(!ifStudentExistRegistration($endIdStudent)){
				$QinserRegistration = "INSERT INTO registration (student_id,parent_id,student_rel,section_id,period,year) VALUES ($endIdStudent, $endIdParent,'".$data['parent']['student_rel']."',".$data['other']['section'].",'".$data['period']."','".$data['other']['year']."')";
				$result_student = mysqli_query($conn, $QinserRegistration);	
				$message = 'Usuario Inscrito con exito';
				$icon = 'success';

			}
			else{
				$message = 'Error: No puedes insertar el mismo estudiante dos veces en el mismo periodo.';
				$icon = 'error';

			}


			$response = array('message' => $message,'icon'=>$icon);
			echo json_encode($response);
		}


		if (isset($data['addSection'])) {

			$message = 'Insertado';

				// Escapa los valores para evitar inyección de SQL
				$year = mysqli_real_escape_string($conn, $data['section']['year']);
				$SectionName = mysqli_real_escape_string($conn, $data['section']['SectionName']);
				$quota = mysqli_real_escape_string($conn, $data['section']['quota']);
				$person_id = mysqli_real_escape_string($conn, $data['section']['person_id']['id']);
				$period = mysqli_real_escape_string($conn, $data['section']['period']);
				
				// ...otros campos    
				$query = "INSERT INTO section (year,section_name,teacher_id,quota,period) VALUES ('$year','$SectionName',$person_id,$quota,'$period')";
				$result = mysqli_query($conn, $query);

				if (!$result) {
					throw new Exception("Error en la consulta SQL: " . mysqli_error($conn));
					$message = 'Error';
				}

			$response = array('message' => $message);
			echo json_encode($response);
		}

		if (isset($data['editSection'])) {

			$message = 'Editado';

				// Escapa los valores para evitar inyección de SQL
				$id = mysqli_real_escape_string($conn, $data['section']['id']);
				$year = mysqli_real_escape_string($conn, $data['section']['year']);
				$SectionName = mysqli_real_escape_string($conn, strtolower($data['section']['SectionName']));
				$quota = mysqli_real_escape_string($conn, $data['section']['quota']);
				$person_id = mysqli_real_escape_string($conn, $data['section']['person_id']['id']);
				$period = mysqli_real_escape_string($conn, $data['section']['period']);
				
				// ...otros campos    
				$query = "UPDATE section SET year='$year',section_name='$SectionName',teacher_id=$person_id,quota=$quota,period='$period' WHERE id=$id";
				$result = mysqli_query($conn, $query);

				if (!$result) {
					throw new Exception("Error en la consulta SQL: " . mysqli_error($conn));
					$message = 'Error';
				}

			$response = array('message' => $message);
			echo json_encode($response);
		}

		if (isset($data['editUser'])) {

			$message = 'Editado';

				// Escapa los valores para evitar inyección de SQL
				$id = mysqli_real_escape_string($conn, $data['user']['id']);
				$user_name = mysqli_real_escape_string($conn, $data['user']['user_name']);
				$password = mysqli_real_escape_string($conn, $data['user']['password']);
				$isAdmin = mysqli_real_escape_string($conn, $data['user']['isAdmin']);

				
				// ...otros campos    
				$query = "UPDATE user SET user_id='$id',user_name='$user_name',password=$password,isAdmin=$isAdmin";
				$result = mysqli_query($conn, $query);

				if (!$result) {
					throw new Exception("Error en la consulta SQL: " . mysqli_error($conn));
					$message = 'Error';
				}

			$response = array('message' => $message);
			echo json_encode($response);
		}


		if (isset($data['addUser'])) { /** AGREGA NUEVOS USUARIOS DEL SISTEMA **/
			$message = 'Insert is Null in Inscribe';
			$icon = 'error';

			function insertPerson($conn, $data) {
				// Escapa los valores para evitar inyección de SQL
				$cedula = mysqli_real_escape_string($conn, $data['cedula']);
				$name = mysqli_real_escape_string($conn, strtolower($data['name']));
				$second_name = mysqli_real_escape_string($conn, strtolower($data['second_name']));
				$last_name = mysqli_real_escape_string($conn, strtolower($data['last_name']));
				$second_last_name = mysqli_real_escape_string($conn, strtolower($data['second_last_name']));
				$email = mysqli_real_escape_string($conn, strtolower($data['email']));
				$phone = mysqli_real_escape_string($conn, $data['phone']);
				$birthday = mysqli_real_escape_string($conn, $data['birthday']);
				$gender = mysqli_real_escape_string($conn, $data['gender']);
				$address = mysqli_real_escape_string($conn, strtolower($data['address']));
				
				// ...otros campos

				$query = "INSERT INTO person (cedula, name, second_name,last_name,second_last_name,email,phone,birthday,gender,address) VALUES ('$cedula', '$name','$second_name','$last_name','$second_last_name','$email','$phone','$birthday','$gender','$address')";
				$result = mysqli_query($conn, $query);

				if (!$result) {
					throw new Exception("Error en la consulta SQL: " . mysqli_error($conn));
				}

				return mysqli_insert_id($conn);
			}


			if (!ifPersonExist($data['person']['cedula'])) {
				$endIdPerson = insertPerson($conn, $data['person']);

					if (!ifUserExist($data['userData']['user_name'])) {
						$hashContrasena = password_hash($data['userData']['password'], PASSWORD_BCRYPT);
						$QinsertUser = "INSERT INTO user (person_id,user_name,password,isAdmin) VALUES ($endIdPerson,'".$data['userData']['user_name']."','".$hashContrasena."',".$data['userData']['isAdmin'].")";
						$result = mysqli_query($conn, $QinsertUser);
						if (!$result) {
							throw new Exception("Error en la consulta SQL: " . mysqli_error($conn));
						}												
						$message = 'Usuario añadido con exito...';
						$icon = 'success';
					} else{
						$message = 'Error: Este usuario ya existe';
						$icon = 'error';
					}
				
			}else{
				$endIdPerson = returnIdParent($data['person']['cedula']);
				if (!ifUserExist($data['userData']['user_name'])) {
					$QinsertUser = "INSERT INTO user (person_id,user_name,password,isAdmin) VALUES ($endIdPerson,'".$data['userData']['user_name']."','".$data['userData']['password']."',".$data['userData']['isAdmin'].")";
					$result = mysqli_query($conn, $QinsertUser);
					if (!$result) {
						throw new Exception("Error en la consulta SQL: " . mysqli_error($conn));
					}					
					$message = 'Usuario añadido con exito...';
					$icon = 'success';
				} else{
					$message ='Error: Este usuario ya existe';
					$icon = 'error';
				}
			}

			

			$response = array('message' => $message,'icon'=>$icon);
			echo json_encode($response);
		}


		if (isset($data['hola'])){
	
			$response = array('message' => 'good');
			echo json_encode($response);
		}


		if (isset($data['editStudent'])) {

			$message = 'Editado';

				// Escapa los valores para evitar inyección de SQL
				$id = mysqli_real_escape_string($conn, $data['student']['id']);
				$cedula = mysqli_real_escape_string($conn, $data['student']['cedula']);
				$name = mysqli_real_escape_string($conn, strtolower($data['student']['name']));
				$second_name = mysqli_real_escape_string($conn, strtolower($data['student']['second_name']));
				$last_name = mysqli_real_escape_string($conn, strtolower($data['student']['last_name']));
				$second_last_name = mysqli_real_escape_string($conn, strtolower($data['student']['second_last_name']));
				$email = mysqli_real_escape_string($conn, strtolower($data['student']['email']));
				$phone = mysqli_real_escape_string($conn, $data['student']['phone']);
				$address = mysqli_real_escape_string($conn, strtolower($data['student']['address']));
				$gender = mysqli_real_escape_string($conn, $data['student']['gender']);
				$birthday = mysqli_real_escape_string($conn, $data['student']['birthday']);
				// ...otros campos    
				$query = "UPDATE person SET cedula='$cedula',name='$name',second_name='$second_name',last_name='$last_name',second_last_name='$second_last_name',email='$email',phone='$phone',address='$address',gender='$gender',birthday='$birthday' WHERE id=$id";
				$result = mysqli_query($conn, $query);

				if (!$result) {
					throw new Exception("Error en la consulta SQL: " . mysqli_error($conn));
					$message = 'Error';
				}

			$response = array('message' => $message);
			echo json_encode($response);
		}

    } catch (Exception $e) {		
        //http_response_code(500);
		$response = array('Error: ' => $e->getMessage());
		echo json_encode($response);		
        //echo json_encode(new stdClass()); // Devuelve un objeto JSON vacío
    }
}


if ($method == "GET") {

	if(isset($_GET['getpass'])){
		echo password_hash($_GET['getpass'], PASSWORD_BCRYPT);
	}

	if(isset($_GET['current_period'])){
		
		$time_period="";
		$exist_period=false;
		$start_current_period = "";
		$end_current_period = "";		
		$current_period ="";
		$isOpen=0;
		
		$Q_time_period = mysqli_query($conn, "SELECT YEAR(NOW()) AS actual, YEAR(NOW() - INTERVAL 1 YEAR) AS anterior");
		$row_time_period = mysqli_fetch_assoc($Q_time_period);
		$time_period = $row_time_period['anterior']."-".$row_time_period['actual'];
		
		$Q_exist = mysqli_query($conn, "SELECT 1 FROM period WHERE name = '$time_period'");
		$exist_period = mysqli_num_rows($Q_exist) > 0;		
		
		if($exist_period){
			$Q_current_period = mysqli_query($conn, "select * from period order by id DESC limit 1");
			$row_current_period = mysqli_fetch_assoc($Q_current_period);
			$start_current_period = $row_current_period['start_date'];
			$end_current_period = $row_current_period['end_date'];
			$current_period = $row_current_period['name'];
			$isOpen = $row_current_period['isOpen'];
		}
		
		$obj = array('current_period'=>$current_period,'time_period'=>$time_period,'start_current_period'=>$start_current_period,'end_current_period'=>$end_current_period,'exist_period'=>$exist_period,'isOpen'=>$isOpen);		
		echo json_encode($obj); 
	}

	if(isset($_GET['person_list'])){
		$obj = array();
		$consulta = "SELECT
			person.id,
			person.cedula,
			person.name,
			person.phone,
			person.second_name,
			person.last_name,
			person.second_last_name,
			person.email,
			person.birthday,
			person.gender,
			person.address
			FROM
			person
			INNER JOIN
			teacher ON teacher.person_id = person.id
			INNER JOIN
			parent ON parent.person_id = person.id";
		$resultado = mysqli_query($conn, $consulta);
		if ($resultado && mysqli_num_rows($resultado) > 0) {
			while($row = mysqli_fetch_assoc($resultado)) {      
				$obj[]=array('id'=>$row['id'],'phone'=>$row['phone'],'cedula'=>$row['cedula'],'name'=>$row['name'],'second_name'=>$row['second_name'],'last_name'=>$row['last_name'],'second_last_name'=>$row['second_last_name'],'email'=>$row['email'],'birthday'=>$row['birthday'],'gender'=>$row['gender'],'address'=>$row['address']);
			}   
		}
		echo json_encode($obj); 
	}		
	
	if(isset($_GET['teacher_list'])){
		$obj = array();
		$consulta = "SELECT 
		person.id,
		person.cedula,
		person.name,
		person.phone,
		person.second_name,
		person.last_name,
		person.second_last_name,
		person.email,
		person.birthday,
		person.gender,
		person.address 
		FROM person INNER JOIN teacher where teacher.person_id = person.id";
		$resultado = mysqli_query($conn, $consulta);
		if ($resultado && mysqli_num_rows($resultado) > 0) {
			while($row = mysqli_fetch_assoc($resultado)) {      
				$obj[]=array('id'=>$row['id'],'phone'=>$row['phone'],'cedula'=>$row['cedula'],'name'=>$row['name'],'second_name'=>$row['second_name'],'last_name'=>$row['last_name'],'second_last_name'=>$row['second_last_name'],'email'=>$row['email'],'birthday'=>$row['birthday'],'gender'=>$row['gender'],'address'=>$row['address']);
			}   
		}
		echo json_encode($obj); 
	}	
	
	if(isset($_GET['parent_list'])){
		$obj = array();
		$consulta = "SELECT 
		person.id,
		person.cedula,
		person.name,
		person.phone,
		person.second_name,
		person.last_name,
		person.second_last_name,
		person.email,
		person.birthday,
		person.gender,
		person.address
		FROM person INNER JOIN parent where parent.person_id = person.id";
		$resultado = mysqli_query($conn, $consulta);
		if ($resultado && mysqli_num_rows($resultado) > 0) {
			while($row = mysqli_fetch_assoc($resultado)) {      
				$obj[]=array('id'=>$row['id'],'phone'=>$row['phone'],'cedula'=>$row['cedula'],'name'=>$row['name'],'second_name'=>$row['second_name'],'last_name'=>$row['last_name'],'second_last_name'=>$row['second_last_name'],'email'=>$row['email'],'birthday'=>$row['birthday'],'gender'=>$row['gender'],'address'=>$row['address']);
			}   
		}
		echo json_encode($obj); 
	}
	
	if(isset($_GET['student_list'])){
		$obj = array();
		$consulta = "SELECT 
		person.id,
		person.cedula,
		person.name,
		person.phone,
		person.second_name,
		person.last_name,
		person.second_last_name,
		person.email,
		person.birthday,
		person.gender,
		person.address 
		FROM person INNER JOIN student where student.person_id = person.id";
		$resultado = mysqli_query($conn, $consulta);
		if ($resultado && mysqli_num_rows($resultado) > 0) {
			while($row = mysqli_fetch_assoc($resultado)) {      
				$obj[]=array('id'=>$row['id'],'phone'=>$row['phone'],'cedula'=>$row['cedula'],'name'=>$row['name'],'second_name'=>$row['second_name'],'last_name'=>$row['last_name'],'second_last_name'=>$row['second_last_name'],'email'=>$row['email'],'birthday'=>$row['birthday'],'gender'=>$row['gender'],'address'=>$row['address']);
			}   
		}
		echo json_encode($obj); 
	}


	if(isset($_GET['user_list'])){
		$obj = array();
		$consulta = "SELECT * FROM user where isDeleted=0";
		$resultado = mysqli_query($conn, $consulta);
		if ($resultado && mysqli_num_rows($resultado) > 0) {
			while($row = mysqli_fetch_assoc($resultado)) {      
				$obj[]=array('user_id'=>$row['user_id'],'person_id'=>returnDatPerson($row['person_id']),'password'=>$row['password'],'isAdmin'=>$row['isAdmin'],'user_name'=>$row['user_name'],'isBlocked'=>$row['isBlocked']);
			}   
		}
		echo json_encode($obj); 
	}


	

	if(isset($_GET['section_list'])){
		$period = $_GET['period']; //en el link get debe venir el periodo		
		echo json_encode(returnListSection($period)); 
	}
	
	if(isset($_GET['registration_list'])){
		//en el link get debe venir el periodo		
		echo json_encode(returnRegisterList()); 
	}	
	
	if(isset($_GET['next_section'])){
		echo json_encode(returnNextSection($_GET['year'],$_GET['period']));
	}

	if(isset($_GET['sorted_section_list'])){
		$year = $_GET['year'];
		$period = $_GET['period'];
		$obj = array();
		$consulta = "SELECT * 
		FROM section where year = '$year' and period='$period'";
		$resultado = mysqli_query($conn, $consulta);
		if ($resultado && mysqli_num_rows($resultado) > 0) {
			while($row = mysqli_fetch_assoc($resultado)) {      
				$obj[]=array('id'=>$row['id'],'teacher_id'=>$row['teacher_id'],'year'=>$row['year'],'section_name'=>$row['section_name'],'quota'=>$row['quota'],'period'=>$row['period']);
			}   
		}
		echo json_encode($obj); 
	}

	if(isset($_GET['stadistic'])){
		$period = currentPeriod()['current_period'];
		$sumParent = 0;
		$sumTeacher = 0;
		$sumSection = 0;	
		$sumStudent = 0;	

		$consulta = "select count(student_id) as total from registration where period = '$period'";
		$resultado = mysqli_query($conn, $consulta);
		$row = $row = mysqli_fetch_assoc($resultado);
		$sumStudent =  $row['total'];

		$consulta = "select count(*) as total from teacher";
		$resultado = mysqli_query($conn, $consulta);
		$row = $row = mysqli_fetch_assoc($resultado);
		$sumTeacher =  $row['total'];

		$consulta = "select count(*) as total from section where period = '$period'";
		$resultado = mysqli_query($conn, $consulta);
		$row = $row = mysqli_fetch_assoc($resultado);
		$sumSection =  $row['total'];		

		$consulta = "select count(DISTINCT registration.parent_id) as total from registration inner join parent where registration.parent_id = parent.person_id AND registration.period = '$period' GROUP BY registration.parent_id";
		$resultado = mysqli_query($conn, $consulta);
		$row = $row = mysqli_fetch_assoc($resultado);
		$sumParent =  $row['total'];		

		$obj = array(
			"period"=>$period, 
			"sumStudent" => $sumStudent,
			"sumTeacher" => $sumTeacher,
			"sumSection" => $sumSection,
			"sumParent" => $sumParent
		);

		echo json_encode($obj); 
	}

}



?>