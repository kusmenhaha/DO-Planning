
<?php
error_log("==== DEBUG FILTER ====");
foreach ($_GET as $key => $val) {
    error_log("Filter received: " . $key . " = " . $val);
}
?>
