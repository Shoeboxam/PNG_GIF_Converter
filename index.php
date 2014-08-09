<?php


require 'lib/GifCreator/GifCreator.php';

//TODO: Normalize timings

$image_path = "redstone_lamp_on.png";
$frametime = 20;
$tick_rate = 20;

if (file_exists($image_path)){

  $image = imagecreatefrompng($image_path);

  $frametime_list = [];
  $frameindex_list = [];

  $image_split = [];

  $width = imagesx($image);
  $height = imagesy($image);

  //Use json file if supplied
  if (file_exists("$image_path.mcmeta")){
    $image_properties = json_decode(file_get_contents("$image_path.mcmeta"), true);

    //Retrieve frame order from json if set, otherwise set frame order to a row
    $frameindex_list = $image_properties['animation']['frames'];
    if (is_null($frameindex_list)) {
      $frameindex_list = range(0, $height / $width - 1);
    }

    //Retrieve frametime from json if set
    if (array_key_exists('frametime', $image_properties['animation'])){
      $frametime = $image_properties['animation']['frametime'];
    }

    $frametime_list = array_fill(0, count($frameindex_list), $frametime);

  } else {
    echo nl2br("Using defaults for $image_path.mcmeta." . PHP_EOL);

    $frameindex_list = range(0, $height / $width - 1);
    $frametime_list = array_fill(0, count($frameindex_list), $frametime);
  }
}

for ($index = 0; $index < count($frameindex_list); $index += 1){

  //If extra information is encoded into the frame, split it apart
  if (is_array($frameindex_list[$index])){
    $frametime_list[$index] = $frameindex_list[$index]['time'];
    $frameindex_list[$index] = $frameindex_list[$index]['index'];
  }

  //If interpolate is set, interpolate!
  if (array_key_exists('interpolate', $image_properties['animation']) && $image_properties['animation']['interpolate']){
    for ($opacity = 0; $opacity < 100; $opacity += 100 / $frametime) { //TODO: sinusoidal interpolation here

      //Use current index as base, then merge next index at given opacity
      $image_buffer = imagecopy($image_buffer, $image, 0, 0, 0, ($width * $frameindex_list[$index]), $width, $width);
      imagecopymerge($image_buffer, $image, 0, 0, 0, ($width * $frameindex_list[$index + 1]), $width, $width, $opacity);
      $image_split[] = $image_buffer;

      //Insert intermediate values into frametime_list
      array_splice($frametime_list, $index, $frametime_list[$index] / $opacity); //TODO: divisor accuracy, error testing
    }
  } else {
    //Copy segments of animation strip into an image array based on frame order
    $image_buffer = imagecreatetruecolor($width, $width);
    imagecopy($image_buffer, $image, 0, 0, 0, ($width * $frameindex_list[$index]), $width, $width);
    $image_split[] = $image_buffer;
  }
}

$gc = new \GifCreator\GifCreator();
//$image_split contains straight frames in variables
$gc -> create($image_split, $frametime_list, 0); //0 is infinite loop

$image_gif = $gc->getGif();

//Output and save
$gif_path = str_replace('.png', '.gif', $image_path);
file_put_contents($gif_path, $image_gif);
echo "<img src='$gif_path' alt='Gif!'/>";


} else {
  echo "Error: Could not find $image_path";
}
?>
