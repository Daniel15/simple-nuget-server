<?php
include('../inc/core.php');
header('Content-Type: text/plain; charset=utf-8');
echo DB::countPackages();
