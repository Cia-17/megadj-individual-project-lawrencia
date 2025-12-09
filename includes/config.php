<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');  
define('DB_PASS', '');      
define('DB_NAME', 'megadj_db');

define('SITE_NAME', 'MegaDj');
define('SITE_URL', 'http://localhost/megadj-individual-project-lawrencia/'); 


define('MAX_AUDIO_SIZE', 50 * 1024 * 1024); // 50MB
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024);  // 5MB
define('ALLOWED_AUDIO_TYPES', ['audio/mpeg', 'audio/wav', 'audio/mp3']);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);


define('AUDIO_UPLOAD_PATH', 'uploads/audio/');
define('IMAGE_UPLOAD_PATH', 'uploads/covers/');


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>