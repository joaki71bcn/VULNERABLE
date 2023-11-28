<?php
session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>$_SERVER['HTTP_HOST'],'secure'=>false,'httponly'=>true,'samesite'=>'Strict']);
session_start();
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

// Check if the logout button is clicked
if (isset($_POST["logout"])) {
    setcookie("user","",time()-3600,"/");
    if (isset($_COOKIE[session_name()])) { 
        setcookie(session_name(),"",time()-3600,"/");
    }
    session_destroy();
    header("Location: /");
    exit();
}

$username = isset($_COOKIE["user"]) ? $_COOKIE["user"] : null;

// Check if the username and password are set in the POST request
if (isset($_POST["username"]) && isset($_POST["password"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];

    try {
        $conn = mysqli_connect("localhost","root","hacker2016","test_app");
        if (!$conn) { 
            die("Error en la conexión a la BBDD: ".mysqli_connect_error());
        } else {
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE username=? AND password=?");
            $stmt->bind_param("ss",$username,$password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result === false) { 
                die("Error en la consulta a la BBDD: ".mysqli_error($conn));
            } elseif ($result->num_rows > 0) {
                $unique_string = bin2hex(random_bytes(16));
                $cookie_options = ['expires'=>time()+(86400*30),'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Strict'];
                setcookie('user',$unique_string,$cookie_options);
                if (isset($_COOKIE["user"])) { 
                    $username = $_COOKIE["user"];
                }
	    // username sanitized	
            } else {
		$username_sanitized = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');    
                echo "<p>El usuario $username_sanitized o su contraseña no existe.</p>";
                exit();
            }
        }
    } catch (Exception $e) { 
        echo 'Caught exception: ', $e->getMessage(), "\n";
    }
}

// Display welcome message and user information if the username exists
if ($username) {
    // Sanitize the username before outputting it
    $username_sanitized = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

    echo "<h1>Bienvenido a la aplicación</h1>";
    // Use the sanitized username in the output
    echo "<p>El usuario $username_sanitized existe, ¡Bienvenido!</p>";
    echo '<img src="/'.$username_sanitized.'/'.$username_sanitized.'.jpg" alt="Imagen de bienvenida" width="200" height="150">';
    
    // Display the .txt files
    $files = glob("/var/www/html/VULNERABLE/$username_sanitized/*.txt");
    foreach($files as $file) { 
        echo "<p><a href='/".$username_sanitized."/".basename($file)."'>".basename($file)."</a></p>";
    }

    // Display the logout button
    echo '<form method="post"><input type="hidden" name="logout" value="true"><input type="submit" value="Cerrar sesión y borrar cookie"></form>';
}
?>
