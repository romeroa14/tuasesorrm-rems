<?php
// Password que quieres usar
$password = 'admin123'; 

// Generamos el hash con el motor del servidor actual
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Copia este hash exactamente:<br><br>";
echo "<b>" . $hash . "</b>";