<?php
require "config/init.php";
$m = mysqli_real_escape_string($conn, $_POST['mobile']);
$q = mysqli_query($conn, "SELECT id FROM users WHERE mobile='$m' LIMIT 1");
echo mysqli_num_rows($q) ? "exists" : "ok";
