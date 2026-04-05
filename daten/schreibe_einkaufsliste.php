<?php
$input = file_get_contents('php://input');
file_put_contents(__DIR__ . '/einkaufsliste.json', $input);
echo '{"success":true}';
?>