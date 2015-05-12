<?php

#include_once 'classes/imageCompare.class.php';
# My own cutscene finder, using FFMPEG's one instead.
#$imageCompare = new imageCompare;
#$array = $imageCompare->main();
#print_r ($array);

include_once 'classes/soundtrackGenerator.class.php';
$soundtrackGenerator = new soundtrackGenerator;
$soundtrackGenerator->getSoundtrack();



?>