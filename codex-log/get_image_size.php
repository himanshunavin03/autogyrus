<?php
$path = $argv[1] ?? '';
$info = @getimagesize($path);
if (!$info) {
    fwrite(STDERR, "0\t0\n");
    exit(1);
}
echo $info[0] . "\t" . $info[1];
