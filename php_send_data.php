<?php
// Anti CSP vulnerability
header("Content-Security-Policy: default-src 'self'; img-src 'self'; script-src 'self'; style-src 'self'; object-src 'none'; require-trusted-types-for 'script'; frame-ancestors 'none'; form-action 'self';");

header('X-Frame-Options: SAMEORIGIN');

// Session cookie
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'domain' => $_SERVER['HTTP_HOST'], 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']);
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Section 1: Logout functionality
if (isset($_POST["logout"])) {
    if (!isset($_POST["csrf_token"]) || $_POST["csrf_token"] !== $_SESSION['csrf_token']) {
        echo "CSRF detected, the action will be aborted";
        exit();
    }

    setcookie("user", "", time() - 3600, "/");
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), "", time() - 3600, "/");
    }
    session_destroy();
    header("Location: /");
    exit();
}

// Section 2: Login and User Existence Check
$username = isset($_COOKIE["user"]) ? $_COOKIE["user"] : null;

if (isset($_POST["username"]) && isset($_POST["password"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];

    try {
        $conn = mysqli_connect("localhost", "root", "hacker2016", "test_app");
        if (!$conn) {
            die("Error en la conexión a la BBDD: " . mysqli_connect_error());
        } else {
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE username=? AND password=?");
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result === false) {
                die("Error en la consulta a la BBDD: " . mysqli_error($conn));
            } elseif ($result->num_rows > 0) {
                $unique_string = bin2hex(random_bytes(16));
                $cookie_options = ['expires' => time() + (86400 * 30), 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict'];
                setcookie('user', $unique_string, $cookie_options);
                if (isset($_COOKIE["user"])) {
                    $username = $_COOKIE["user"];
                }
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

// Section 3: Generate and store CSRF token after successful login
if (isset($_POST["username"]) && isset($_POST["password"])) {
    $csrf_token = bin2hex(random_bytes(16));
    $_SESSION['csrf_token'] = $csrf_token;
}

// Section 4: Display user information if the username exists
if ($username) {
    $username_sanitized = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    echo "<h1>Bienvenido a la aplicación</h1>";
    echo "<p>El usuario $username_sanitized existe, ¡Bienvenido!</p>";
    echo '<img src="/' . $username_sanitized . '/' . $username_sanitized . '.jpg" alt="Imagen de bienvenida" width="200" height="150">';

    $files = glob("/var/www/html/VULNERABLE/$username_sanitized/*.txt");
    foreach ($files as $file) {
        echo "<p><a href='/" . $username_sanitized . "/" . basename($file) . "'>" . basename($file) . "</a></p>";
    }

    // Section 5: Display the logout button with the CSRF token
    echo '<form method="post">';
    echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
    echo '<input type="hidden" name="logout" value="true">';
    echo '<input type="submit" value="Cerrar sesión y borrar cookie">';
    echo '</form>';
}
?>

