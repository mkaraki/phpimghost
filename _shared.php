<?php

function quality_check($quality, $min, $max)
{
    if ($quality === null)
        return;
    if ($quality < $min || $quality > $max) {
        http_response_code(400);
        die('Invalid request');
    }
}
