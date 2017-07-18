#TODO







```
// $company, $profile, $plugin, $profilePluginData
/*
Then, for each practice the database can record up to five species from the above list.
For each placement, the student picks the species to which they plan to be exposed. Maximum of 5 picks, obviously.

IF only  Type A species are picked, then the placement is Type A
IF only  Type B species are picked, then the placement is Type B
IF only  Type C species are picked, then the placement is Type C
IF there is any combination of [Type A AND Type B] then the placement is Type B
IF there is any combination of [Type A AND Type B] AND [horses] AND/OR [Food/Fibre Production other] then the placement is Type B
IF there is any combination of [Type A AND Type C] then
    IF the number of A > the number of C then it is Type A
    ELSE it is Type C
IF there is any combination of [Type B AND Type C] then
    IF only Type B species AND [horses] AND/OR [Food/Fibre Production other] are picked then the placement is Type B
    ELSE the placement is Type C

Cheers!, Ted
*/

$typeA = 0;
$typeB = 0;
$typeC = 0;
$horse = 0;
$food  = 0;

$class = 'A';

foreach ($catList as $i => $cat) {
  switch($cat->class) {
    case 'A':
      $typeA++;
      break;
    case 'B':
      $typeB++;
      break;
    case 'C':
      $typeC++;
      break;
    case 'D':
    case 'E':
      return $cat->class;
  }
  if (preg_match('/.*(horse|equine).*/i', $cat->name)) {
    $horse = true;
  }
  if (preg_match('/.*(food|sheep|fibre).*/i', $cat->name)) {
    $food = true;
  }
}

if ($typeA && !$typeB && !$typeC) {         // IF only  Type A species are picked, then the placement is Type A
  $class = 'A';
} else if (!$typeA && $typeB && !$typeC) {  // IF only  Type B species are picked, then the placement is Type B
  $class = 'B';
} else if (!$typeA && !$typeB && $typeC) {  // IF only  Type C species are picked, then the placement is Type C
  $class = 'C';
} else if ($typeA && $typeB && !$typeC) {   // IF there is any combination of [Type A AND Type B] then the placement is Type B
  $class = 'B';
} else if ($typeA && $typeB && ($horse || $food)) { // IF there is any combination of [Type A AND Type B] AND [horses] AND/OR [Food/Fibre Production other] then the placement is Type B
  $class = 'B';
} else if ($typeA && !$typeB && $typeC) {   //  IF there is any combination of [Type A AND Type C] then
  if ($typeA > $typeC)    // IF the number of A > the number of C then it is Type A
  	$class = 'A';
  else                    // ELSE it is Type C
  	$class = 'C';
} else if (!$typeA && $typeB && $typeC) {   // IF there is any combination of [Type B AND Type C] then
  if ( ($typeC == 1 && ($horse || $food)) || ($typeC == 2 && ($horse && $food)) )     // IF only Type B species AND [horses] AND/OR [Food/Fibre Production other] are picked then the placement is Type B
  	$class = 'B';
  else                    // ELSE the placement is Type C
  	$class = 'C';
}

return $class;

```

