<?php
    require_once("inc/session.php");
    if($_SESSION === null){ echo '<meta http-equiv="refresh" content="0;url=/">'; exit;}
    
    $template = file_get_contents("editor/main.html");
    echo $template;
    echo '<script>openEditor(null, "companies");</script>';
?>