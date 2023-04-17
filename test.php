<?php
$timestamp = 1333699439;
echo gmdate("Y-m-d\TH:i:s\Z", $timestamp);

$posts = array();

for ($i = 0; $i <= 9; $i++) {
    $resp['id'] = $i;
    $resp['name'] = "juan $i";
    $resp['last'] = "quispe $i";

    array_push($posts, $resp);
}

array_push($posts, $resp);

foreach ($posts as $j => $post) {
    $val = json_encode($post);
    print_r("<pre>index: $j</pre>");
    print_r("<pre>post: $val </pre>");
}
?>