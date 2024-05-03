<?php
    
    session_start();

    // Create an image with the CAPTCHA code
    $image = imagecreate(100, 40);
    $bg_color = imagecolorallocate($image, 255, 255, 255); // white background
    $text_color = imagecolorallocate($image, 0, 0, 0); // black text
    imagestring($image, 5, 20, 10, $_SESSION['captcha'], $text_color); // draw CAPTCHA text
    header("Content-type: image/png");
    imagepng($image);
    imagedestroy($image);

    $letter1 = chr(rand(97,122));
    $letter2 = chr(rand(97,122));
    $letter3 = chr(rand(97,122));
    $letter4 = chr(rand(97,122));
    $letter5 = chr(rand(97,122));
    $captcha = $letter1 . $letter2 . $letter3 . $letter4 . $letter5;
    // Store the CAPTCHA code in the session for verification later
    $_SESSION['captcha'] = $captcha;

?>
