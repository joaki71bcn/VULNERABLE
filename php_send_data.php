<?php
session_start();

// Configurar el manejo de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Comprueba si se ha hecho clic en el botón de cierre de sesión
if (isset($_POST["logout"])) {
    // Borra la cookie "user"
    setcookie("user", "", time() - 3600, "/");
    header("Location: /"); // Redirige al usuario a la página principal
    exit();
}

// Obtener el valor de la cookie "user" y asignarlo a la variable $username
$username = isset($_COOKIE["user"]) ? $_COOKIE["user"] : null;

if (isset($_POST["username"]) && isset($_POST["password"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];

    try {
        // Intenta establecer la conexión a la BBDD
        $conn = mysqli_connect("localhost", "root", "hacker2016", "test_app");

        if (!$conn) {
            // La conexión falló, muestra un mensaje personalizado
            die("Error en la conexión a la BBDD: " . mysqli_connect_error());
        } else {
            // Consulta BBDD si usuario existe
            $query = "SELECT * FROM usuarios WHERE username='$username' AND password='$password'";
            $result = mysqli_query($conn, $query);

            if ($result === false) {
                // Error en la consulta
                die("Error en la consulta a la BBDD: " . mysqli_error($conn));
            } elseif (mysqli_num_rows($result) > 0) {
                // Usuario existe, establece la cookie "user"
                $unique_string = bin2hex(random_bytes(16)); // Genera una cadena alfanumérica única y larga
                setcookie("user", $unique_string, time() + (86400 * 30), "/"); // 86400 = 1 day
                if(isset($_COOKIE["user"])) {
                    $username = $_COOKIE["user"];
                }
            } else {
                // Usuario no existe, muestra un mensaje de error
                echo "<p>El usuario $username no existe.</p>";
                exit();
            }
        }
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
}

if ($username) {
    // Usuario existe
    echo "<h1>Bienvenido a la aplicación</h1>";
    echo "<p>El usuario $username existe, ¡Bienvenido!</p>";
    // Muestra imagen
    echo '<img src="/'.$username.'/'.$username.'.jpg" alt="Imagen de bienvenida" width="200" height="150">';

    // Muestra los archivos .txt
    $files = glob("/var/www/html/VULNERABLE/$username/*.txt");
    foreach($files as $file) {
        echo "<p><a href='/".$username."/".basename($file)."'>".basename($file)."</a></p>";
    }

    // Muestra el botón de cierre de sesión
	echo '<form method="post">
	<input type="hidden" name="logout" value="true">
	<input type="submit" value="Cerrar sesión y borrar cookie">
	</form>';
}
?>
