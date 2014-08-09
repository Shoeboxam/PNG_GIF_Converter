<?php


require 'lib/GifCreator/GifCreator.php';


$image_name = "sea_lantern.png";
$frametime = 20;
$tick_rate = 20;
$interpolation_framecount = 16;

$image_path = "assets/$image_name";

if (file_exists($image_path)) {

  $image = imagecreatefrompng($image_path);

  $frametime_list = [];
  $frametime_list_interp = [];
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

  for ($index = 0; $index < count($frameindex_list); $index += 1){

    //If extra information is encoded into the frame, split it apart
    if (is_array($frameindex_list[$index])){
      $frametime_list[$index] = $frameindex_list[$index]['time'];
      $frameindex_list[$index] = $frameindex_list[$index]['index'];
    }

    //If interpolate is set, interpolate!
    if (array_key_exists('interpolate', $image_properties['animation']) && $image_properties['animation']['interpolate']){
      for ($index_interp = 0; $index_interp < 1; $index_interp += (1 / $interpolation_framecount)) {

        //Smoothstep opacity
        $opacity = $index_interp * $index_interp * (3 - 2 * $index_interp) * 100;

        //Use current index as base, then merge next index at given opacity
        $image_buffer = imagecreatetruecolor($width, $width);
        imagecopy($image_buffer, $image, 0, 0, 0, ($width * $frameindex_list[$index]), $width, $width);

        $index_incremented = ($index + 1) % count($frameindex_list); //Wrap increment
        imagecopymerge($image_buffer, $image, 0, 0, 0, ($width * $frameindex_list[$index_incremented]), $width, $width, $opacity);

        //Save interpolated frame data
        $image_split[] = $image_buffer;
        $frametime_list_interp[] = $frametime_list[$index] / $interpolation_framecount;
      }

    } else {
      //Copy segments of animation strip into an image array based on frame order
      $image_buffer = imagecreatetruecolor($width, $width);
      imagecopy($image_buffer, $image, 0, 0, 0, ($width * $frameindex_list[$index]), $width, $width);
      $image_split[] = $image_buffer;
    }
  }

  if (array_key_exists('interpolate', $image_properties['animation']) && $image_properties['animation']['interpolate']){
    //Overwrite standard frametimes with the expanded interpolation frametime list
    $frametime_list = $frametime_list_interp;
  }

  $gc = new \GifCreator\GifCreator();
  //$image_split contains straight frames in variables
  $gc -> create($image_split, $frametime_list, 0); //0 is infinite loop

  $image_gif = $gc->getGif();

  //Output and save
  $gif_path = str_replace('.png', '.gif', "output/$image_name");
  file_put_contents($gif_path, $image_gif);
  echo "<img src='$gif_path' alt='Gif!'/>";

} else {
  echo "Error: Could not find $image_path";
}
?>
