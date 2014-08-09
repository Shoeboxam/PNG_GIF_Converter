<?php

require 'lib/GifCreator/GifCreator.php';

$image_path = "redstone_lamp_on.png";
$frametime = 1;
$tick_rate = 20;

if (file_exists($image_path)){

  $image = imagecreatefrompng($image_path);

  $frametime_list = [];
  $frameindex_list = [];

  $width = imagesx($image);
  $height = imagesy($image);

  //Use json file if supplied
  if (file_exists("$image_path.mcmeta")){
    $image_properties = json_decode(file_get_contents("$image_path.mcmeta"), true);

    //Retrieve frame order from json if set, otherwise set frame order to a row
    $frameindex_list = [];
    if (array_key_exists('frames', $image_properties['animation'])) {
      $frameindex_list = $image_properties['animation']['frames'];
    } else {
      $frameindex_list = range(0, $height / $width - 1);
    }

    //Retrieve frametime from json if set, then create array of frametimes
    if (array_key_exists('frametime', $image_properties['animation'])){
      $frametime = $image_properties['animation']['frametime'];
    }
    $frametime_list = array_fill(0, count($frameindex_list), $frametime / $tick_rate);

  } else {
    echo nl2br("Using defaults for $image_path.mcmeta." . PHP_EOL);

    $frameindex_list = range(0, $height / $width - 1);
    $frametime_list = array_fill(0, count($frameindex_list), $frametime / $tick_rate);
  }

  //Instantiate empty image array
  $image_split = array_fill(0, count($frameindex_list), imagecreatetruecolor($width, $width));

  if (!file_exists('Temp')) {
    mkdir('Temp');
  }

  for ($index = 0; $index < count($frameindex_list); $index += 1){

    //If extra information is encoded into the frame, split it apart
    if (is_array($frameindex_list[$index])){
      $frametime_list[$index] = $frameindex_list[$index]['time'];
      $frameindex_list[$index] = $frameindex_list[$index]['index'];
    }

    //Convert time durations to milliseconds
    //$frametime_list[$index] *= 1000;

    //Copy segments of animation strip into an image array based on frame order
    imagecopy($image_split[$index], $image, 0, 0, 0, ($width * $frameindex_list[$index]), $width, $width);

    //Save frames to files for the lib to read from
    $frame_path = "temp/" . str_replace('.png', "_$index.png", $image_path);
    imagepng($image_split[$index], "$frame_path");
    $frame_path_list[$index] = $frame_path;
  }

  $gc = new \GifCreator\GifCreator();
  //$image_split contains straight frames in variables
  $gc -> create($frame_path_list, $frametime_list, 0); //0 is infinite loop

  $image_gif = $gc->getGif();

  foreach ($frame_path_list as $frame_path){
    unlink($frame_path);
  }

  rmdir('Temp');

  //Output and save
  $gif_path = str_replace('.png', '.gif', $image_path);
  file_put_contents($gif_path, $image_gif);
  echo "<img src='$gif_path' alt='Gif!'/>";


} else {
  echo "Error: Could not find $image_path";
}
?>
