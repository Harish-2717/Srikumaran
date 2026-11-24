<?php
require "config/init.php";

$cid = (int)($_POST['community_id'] ?? 0);

$q = mysqli_query($conn,
    "SELECT id, sub_community_name 
     FROM master_sub_community 
     WHERE status=1 AND community_id=$cid
     ORDER BY sub_community_name"
);

echo '<option value="">Select</option>';
while($r=mysqli_fetch_assoc($q)){
    echo "<option value='{$r['id']}'>{$r['sub_community_name']}</option>";
}
