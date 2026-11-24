<?php
require "config/init.php";

$q = mysqli_query($conn,
    "SELECT id, niram_name 
     FROM master_niram 
     WHERE status=1 
     ORDER BY id ASC"
);

echo '<option value="">Select Niram</option>';
while ($r = mysqli_fetch_assoc($q)) {
    echo "<option value='" . htmlspecialchars($r['niram_name']) . "'>" . htmlspecialchars($r['niram_name']) . "</option>";
}


