<?php
require "../config/init.php";

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit;
}

$user_id = (int)($_GET['id'] ?? 0);
if (!$user_id) exit;

/* ================= FETCH USER ================= */
$sql = "
SELECT
    u.*,
    mc.community_name,
    mg.gender_name,
    mq.qualification_name,
    city.city_name,
    mk.kulam_name,
    mgot.gothiram_name,
    mr.raasi_name,
    ms.star_name,
    u.dhosam_type_id,
    udt.dhosam_type,
    u.dhosam_name_id,
    udn.dhosam_name
FROM users u
LEFT JOIN master_community mc ON mc.id = u.community_id
LEFT JOIN master_gender mg ON mg.id = u.gender_id
LEFT JOIN master_qualification mq ON mq.id = u.qualification_id
LEFT JOIN master_city city ON city.id = u.city_id
LEFT JOIN master_kulam mk ON mk.id = u.kulam_id
LEFT JOIN master_gothiram mgot ON mgot.id = u.gothiram_id
LEFT JOIN master_raasi mr ON mr.id = u.raasi_id
LEFT JOIN master_star ms ON ms.id = u.star_id
LEFT JOIN master_dhosam_type udt ON udt.id = u.dhosam_type_id
LEFT JOIN master_dhosam_name udn ON udn.id = u.dhosam_name_id
WHERE u.id = $user_id
LIMIT 1
";

$res = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($res);
if (!$data) exit;

/* ================= DATA ================= */

$headerText = strtoupper(trim(
    ($data['name'] ?? '')
    . (isset($data['qualification_name']) && $data['qualification_name'] !== '' ? ' - ' . $data['qualification_name'] : '')
    . (isset($data['username']) && $data['username'] !== '' ? ' (' . $data['username'] . ')' : '')
));
$age = !empty($data['dob']) ? (new DateTime())->diff(new DateTime($data['dob']))->y : '';
$city = $data['city_name'] ?? '';
$heightcm = $data['height'] ?? '';
$photo   = !empty($data['profile_photo']) ? "../".$data['profile_photo'] : '';
$jadagam = !empty($data['jathagam_file']) ? "../".$data['jathagam_file'] : '';

// Grouped details for sections
$income_raw = isset($data['monthly_income']) ? trim((string)$data['monthly_income']) : '';
$income_is_nil = ($income_raw === '' || strtolower($income_raw) === 'nil' || $income_raw == 0 || $income_raw == '0.0' || $income_raw == '0.00');
$monthly_income = $income_is_nil ? 'Nil' : 'Rs ' . (is_numeric($income_raw) ? intval(round((float)$income_raw)) : $income_raw);
$basic_info = [
    ['DOB', $data['dob'] ?? ''],
    ['TOB', $data['tob'] ?? ''],
    ['Monthly Income', $monthly_income],
    ['Community', $data['community_name'] ?? ''],
    ['Kulam', $data['kulam_name'] ?? ''],
    ['Working City', $data['working_city'] ?? ''],
    ['Address', $data['address'] ?? ''],
    ['Others', $data['other_info'] ?? ''],
];
$tob_raw = $data['tob'] ?? '';
if ($tob_raw) {
    $tob_dt = DateTime::createFromFormat('H:i:s', $tob_raw) ?: DateTime::createFromFormat('H:i', $tob_raw);
    $tob_fmt = $tob_dt ? $tob_dt->format('h:i A') : $tob_raw;
} else {
    $tob_fmt = '';
}
$basic_info = [
    ['DOB', $data['dob'] ?? ''],
    ['TOB', $tob_fmt],
    ['Monthly Income', $monthly_income],
    ['Community', $data['community_name'] ?? ''],
    ['Kulam', $data['kulam_name'] ?? ''],
    ['Working City', $data['working_city'] ?? ''],
    ['Address', $data['address'] ?? ''],
    ['Others', $data['other_info'] ?? ''],
];

$horoscope = [
    ['Raasi', $data['raasi_name'] ?? ''],
    ['Star', $data['star_name'] ?? ''],
    ['Dhosam Type', $data['dhosam_type'] ?? ''],
    ['Dhosam Name', $data['dhosam_name'] ?? ''],
];

$community = [
    ['Community', $data['community_name'] ?? ''],
    ['Sub Community', $data['sub_community'] ?? ''],
    ['Kulam', $data['kulam_name'] ?? ''],
    ['Gothiram', $data['gothiram_name'] ?? ''],
];

$profile = [
    ['User Name', $data['username'] ?? ''],
    ['Password', $data['password'] ?? ''],
    ['Status', $data['status'] ?? ''],
];

/* ================= IMAGE ================= */

$width  = 1000;
$height = 1700;
$image  = imagecreatetruecolor($width, $height);
$white  = imagecolorallocate($image, 255,255,255);
$accent = imagecolorallocate($image, 35,45,85);
$boxBg  = imagecolorallocate($image, 230,235,245);
$text   = imagecolorallocate($image, 20,20,20);
$whiteT = imagecolorallocate($image, 255,255,255);
$sectionBg = imagecolorallocate($image, 210,220,240);

imagefill($image, 0, 0, $white);

$font = __DIR__ . '/../fonts/NotoSansTamil-Regular.ttf';
if (!file_exists($font)) {
    $font = __DIR__ . '/../fonts/arial.ttf';
}

// HEADER
imagefilledrectangle($image, 0, 0, $width, 120, $accent);
$headerFontSize = fitFontSize($headerText, $font, $width);
imagettftext($image, $headerFontSize, 0, centerText($headerText, $headerFontSize, $font, $width), 65, $whiteT, $font, $headerText);

// Info line (age, height, city)
$infoParts = array_filter([
    $age !== '' ? $age . ' YEARS' : '',
    'Height : ' . $heightcm,
    $city
]);
$infoText = implode(' | ', $infoParts);
imagettftext($image, 22, 0, centerText($infoText, 22, $font, $width), 105, $whiteT, $font, $infoText);

// PHOTO and JADAGAM side by side at top
$imgY = 200;
$imgH = 700;
$imgW = 400;
$imgGap = 60;
$imgTopPad = 0;
$imgLeftX = ($width/2) - $imgW - ($imgGap/2);
$imgRightX = ($width/2) + ($imgGap/2);
// Profile photo (left)
if ($photo && file_exists($photo)) {
    $src = imagecreatefromstring(file_get_contents($photo));
    $sw = imagesx($src); $sh = imagesy($src);
    $ratio = min($imgW/$sw, $imgH/$sh);
    $nw = $sw*$ratio; $nh = $sh*$ratio;
    $dx = $imgLeftX + ($imgW-$nw)/2;
    $dy = $imgY + ($imgH-$nh)/2;
    imagefilledrectangle($image, $imgLeftX-10, $imgY-10, $imgLeftX+$imgW+10, $imgY+$imgH+10, $boxBg);
    imagerectangle($image, $imgLeftX-10, $imgY-10, $imgLeftX+$imgW+10, $imgY+$imgH+10, $accent);
    imagecopyresampled($image, $src, $dx, $dy, 0, 0, $nw, $nh, $sw, $sh);
    imagedestroy($src);
}
// Jadagam image (right)
if ($jadagam && file_exists($jadagam)) {
    $src = imagecreatefromstring(file_get_contents($jadagam));
    $sw = imagesx($src); $sh = imagesy($src);
    $ratio = min($imgW/$sw, $imgH/$sh);
    $nw = $sw*$ratio; $nh = $sh*$ratio;
    $dx = $imgRightX + ($imgW-$nw)/2;
    $dy = $imgY + ($imgH-$nh)/2;
    imagefilledrectangle($image, $imgRightX-10, $imgY-10, $imgRightX+$imgW+10, $imgY+$imgH+10, $boxBg);
    imagerectangle($image, $imgRightX-10, $imgY-10, $imgRightX+$imgW+10, $imgY+$imgH+10, $accent);
    imagecopyresampled($image, $src, $dx, $dy, 0, 0, $nw, $nh, $sw, $sh);
    imagedestroy($src);
}

// Start details below images
$y = $imgY + $imgH + 20;
$sectionPad = 30;
$sectionW = $width - 2*$sectionPad;
$sectionX = $sectionPad;

function drawSectionHeader($img, $x, $y, $w, $label, $font, $bg, $tc) {
    imagefilledrectangle($img, $x, $y, $x+$w, $y+38, $bg);
    imagerectangle($img, $x, $y, $x+$w, $y+38, $tc);
    imagettftext($img, 20, 0, $x+15, $y+28, $tc, $font, $label);
}

function drawSectionDetails($img, $x, $y, $w, $arr, $font, $boxBg, $accent, $text) {
    $colW = $w/2-10;
    $yy = $y;
    $maxRows = 0;
    $rowHeights = [];
    $boxes = [];
    // First pass: calculate required height for each box
    foreach ($arr as $i => $r) {
        if (!$r[1]) continue;
        $label = $r[0].':';
        $value = (string)$r[1];
        $col = $i%2;
        $row = intdiv($i,2);
        $bx = $x + $col*($colW+20);
        // Calculate label width
        $bbox = imagettfbbox(15, 0, $font, $label);
        $labelWidth = $bbox[2] - $bbox[0];
        // Wrap value so that it starts on the same line as label, and wraps only if too long
        $maxValueWidth = $colW - ($labelWidth + 40); // 10 left + label + 10 gap + 10 right
        $words = preg_split('/\s+/', $value);
        $lines = [];
        $currentLine = '';
        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            $testBox = imagettfbbox(16, 0, $font, $testLine);
            $testWidth = $testBox[2] - $testBox[0];
            if ($testWidth > $maxValueWidth && $currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }
        if ($currentLine !== '') $lines[] = $currentLine;
        $nLines = count($lines);
        $boxH = 22 + $nLines*20 + 8; // 22 for label, 20 per line, 8 padding
        $boxes[$row][$col] = [
            'bx' => $bx,
            'label' => $label,
            'labelWidth' => $labelWidth,
            'lines' => $lines,
            'boxH' => $boxH
        ];
        if (!isset($rowHeights[$row]) || $boxH > $rowHeights[$row]) {
            $rowHeights[$row] = $boxH;
        }
        if ($row+1 > $maxRows) $maxRows = $row+1;
    }
    // Second pass: draw boxes with correct heights, all boxes in a row use the tallest height for that row
    for ($row = 0; $row < $maxRows; $row++) {
        $h = $rowHeights[$row] ?? 38;
        for ($col = 0; $col < 2; $col++) {
            if (!isset($boxes[$row][$col])) continue;
            $b = $boxes[$row][$col];
            $by = $yy;
            imagefilledrectangle($img, $b['bx'], $by, $b['bx']+$colW, $by+$h-2, $boxBg);
            imagerectangle($img, $b['bx'], $by, $b['bx']+$colW, $by+$h-2, $accent);
            // Draw label
            imagettftext($img, 15, 0, $b['bx']+10, $by+22, $accent, $font, $b['label']);
            // Draw value lines, first line starts after label, others indented
            $vy = $by+22;
            foreach ($b['lines'] as $li => $line) {
                if ($li == 0) {
                    imagettftext($img, 16, 0, $b['bx']+10+$b['labelWidth']+10, $vy, $text, $font, $line);
                } else {
                    imagettftext($img, 16, 0, $b['bx']+10+$b['labelWidth']+10, $vy, $text, $font, $line);
                }
                $vy += 20;
            }
        }
        $yy += $h;
    }
    return $yy - $y;
}

// BASIC INFORMATION
drawSectionHeader($image, $sectionX, $y, $sectionW, 'BASIC INFORMATION', $font, $sectionBg, $accent);
$y += 45;
$h = drawSectionDetails($image, $sectionX, $y, $sectionW, $basic_info, $font, $boxBg, $accent, $text);
$y += $h + 18;

// HOROSCOPE
drawSectionHeader($image, $sectionX, $y, $sectionW, 'HOROSCOPE', $font, $sectionBg, $accent);
$y += 45;
$h = drawSectionDetails($image, $sectionX, $y, $sectionW, $horoscope, $font, $boxBg, $accent, $text);
$y += $h + 18;

// ...COMMUNITY and PROFILE sections removed as per request...

// (Jadagam image is now shown at top right, so skip showing it again below)

// FOOTER
$footer = [
    "Sri Kumaran Manamaalai Coimbatore",
    "Call us : +91 87540 32742 / 0422 2666 505"
];
$fy = $height - 45;
foreach ($footer as $i => $f) {
    imagettftext($image, 14, 0, centerText($f, 14, $font, $width), $fy + ($i * 22), $text, $font, $f);
}

/* ================= FOOTER ================= */
$footer = [
    "Sri Kumaran Manamaalai Coimbatore",
    "Call us : +91 87540 32742 / 0422 2666 505"
];

$fy = $height - 45;
foreach ($footer as $i => $f) {
    imagettftext($image, 14, 0, centerText($f, 14, $font, $width), $fy + ($i * 22), $text, $font, $f);
}

/* ================= OUTPUT ================= */
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
exit;

/* ================= HELPERS ================= */
function centerText($t, $s, $f, $w) {
    $b = imagettfbbox($s, 0, $f, $t);
    return ($w - ($b[2] - $b[0])) / 2;
}

function fitFontSize($text, $font, $maxWidth, $start = 36, $min = 18) {
    for ($s = $start; $s >= $min; $s--) {
        $b = imagettfbbox($s, 0, $font, $text);
        if (($b[2] - $b[0]) <= $maxWidth - 100) {
            return $s;
        }
    }
    return $min;
}

function drawBox($img, $x, $y, $w, $l, $v, $f, $bg, $bd, $tc) {
    if (!$l || !$v) return;
    imagefilledrectangle($img, $x, $y, $x+$w, $y+75, $bg);
    imagerectangle($img, $x, $y, $x+$w, $y+75, $bd);
    imagettftext($img, 18, 0, $x+15, $y+28, $tc, $f, "$l:");
    imagettftext($img, 22, 0, $x+15, $y+58, $tc, $f, mb_strimwidth($v, 0, 40, '...', 'UTF-8'));
}

function drawFullBox($img, $x, $y, $w, $l, $v, $f, $bg, $bd, $tc) {
    if (!$v) return 0;
    $lines = explode("\n", wordwrap($v, 90));
    $h = 45 + (count($lines) * 28);
    imagefilledrectangle($img, $x, $y, $x+$w, $y+$h, $bg);
    imagerectangle($img, $x, $y, $x+$w, $y+$h, $bd);
    imagettftext($img, 18, 0, $x+15, $y+28, $tc, $f, "$l:");
    $ty = $y + 60;
    foreach ($lines as $line) {
        imagettftext($img, 20, 0, $x+15, $ty, $tc, $f, $line);
        $ty += 28;
    }
    return $h;
}
