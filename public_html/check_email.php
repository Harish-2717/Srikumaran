<?php
require "config/init.php";
$email = mysqli_real_escape_string($conn,$_POST['email']);
$q = mysqli_query($conn,"SELECT id FROM users WHERE email='$email' LIMIT 1");
echo mysqli_num_rows($q) ? "exists" : "ok";
