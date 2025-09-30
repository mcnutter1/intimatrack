<?php
// inc/validation.php
function clamp_int($n, $min, $max){
  $n = (int)$n;
  if ($n < $min) $n = $min;
  if ($n > $max) $n = $max;
  return $n;
}
