<?php
    namespace Gumlet;
    include 'Gumlet/ImageResize.php';
    include 'Gumlet/ImageResizeException.php';
    use \Gumlet\ImageResize;
    use \Gumlet\ImageResizeException;

    require('connection.php');
    session_start();

    if (isset($_SESSION['user']))
    {
        // Get the session user information
        $username = $_SESSION['user'];
        $query_session_user= "SELECT * FROM users WHERE username=:username";
        $statement_session_user = $db->prepare($query_session_user);
        $statement_session_user->bindValue(':username', $username);
        $statement_session_user->execute();
        $session_user = $statement_session_user ->fetch();
        $session_username = $session_user['username'];
        $session_username = str_replace(' ', '-', $session_username);
        $session_user_id = $session_user['id'];
        $session_user_role = $session_user['role'];

        if (isset($_GET['id']))
        {
            // Sanitize the id
            $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

            $query2 = "SELECT id FROM users WHERE id=:id";
            $statement2 = $db->prepare($query2);
            $statement2->bindValue(':id', $id, \PDO::PARAM_INT);
            $statement2->execute();
            $row_id = $statement2->fetch(\PDO::FETCH_ASSOC); // Fetch the row
        }

        function file_upload_path($original_filename, $upload_subfolder_name = 'images')
        {
            $current_folder = dirname(__FILE__);

            // Build an array of paths segment names to be joins using OS specific slashes.
            $path_segments = [$current_folder, $upload_subfolder_name, basename($original_filename)];

            // The DIRECTORY_SEPARATOR constant is OS specific.
            return join(DIRECTORY_SEPARATOR, $path_segments);
        }
    
        function file_is_an_image($temporary_path, $new_path)
        {
            $allowed_mime_types = ['image/gif', 'image/jpeg', 'image/png'];
            $allowed_file_extensions = ['gif', 'jpg', 'jpeg', 'png'];

            $actual_file_extension = pathinfo($new_path, PATHINFO_EXTENSION);
            $actual_mime_type = getimagesize($temporary_path)['mime'];

            $file_extension_is_valid = in_array($actual_file_extension, $allowed_file_extensions);
            $mime_type_is_valid = in_array($actual_mime_type, $allowed_mime_types);

            return $file_extension_is_valid && $mime_type_is_valid;
        }

        $image_upload_detected = isset($_FILES['image']) && ($_FILES['image']['error'] === 0);
        $upload_error_detected = isset($_FILES['image']) && ($_FILES['image']['error'] > 0);

        if ($image_upload_detected)
        {
            $image_filename         = $_FILES['image']['name'];
            $temporary_image_path   = $_FILES['image']['tmp_name'];
            $new_image_path         = file_upload_path($image_filename);

            if (file_is_an_image($temporary_image_path, $new_image_path))
            {
                // This is the original sized image
                move_uploaded_file($temporary_image_path, $new_image_path);
            }

            // Create the resized images filenames here
            $string = $new_image_path;
            $extension = strrchr($string, ".");
            $filename_no_extension = strstr($string, ".", true);
            $filename_gallery = $filename_no_extension . "_gallery" . $extension;

            // Resize to maximum 500px width and replace original
            $image = new ImageResize($new_image_path);
            $image->resizeToWidth(600);
            $image->save($new_image_path);

            // Crop to width:600px and height:600px
            $image_gallery = new ImageResize($new_image_path);
            $image_gallery->crop(600,600);
            $image_gallery->save($filename_gallery);
        }

        $errorMessage = "";

        if($_POST && !empty($_POST['text']))
        {
            // Sanitize user input to escape HTML entities and filter out dangerous characters.
            $text = filter_input(INPUT_POST, 'text', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $query = "INSERT INTO posts (text,id) VALUES (:text,:id)";
            $statement = $db->prepare($query);
            $statement->bindValue(':text', $text);
            $statement->bindValue(':id',$id);
            $statement->execute();


            // Process the data from the uploaded image.
            if ($image_upload_detected)
            {
                $url = $_FILES['image']['name'];
                $queryImage = "INSERT INTO images (id, url) VALUES (:id, :url)";
                $statementImage = $db->prepare($queryImage);
                $statementImage->bindValue(':id', $id);
                $statementImage->bindValue(':url', $url);
                $statementImage->execute();

                $image_id = $db->lastInsertId();

                $query_image_to_post = "UPDATE posts SET image_id=:image_id WHERE post_id=(SELECT MAX(post_id) FROM posts)";
                $statement_image_to_post = $db->prepare($query_image_to_post);
                $statement_image_to_post->bindValue(':image_id',$image_id);
                $statement_image_to_post->execute();
            }

            header("Location:../../profile/$session_user_id/$session_username");
            exit;
        }
        else if($_POST && empty($_POST['text']))
        {
            $errorMessage = "Error: Text cannot be empty.";
        }

    }
    else
    {
        echo "<script>location.href='login.php'</script>";
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Palbook Post</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <script src="../../tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      tinymce.init({
        selector: '#mytextarea'
      });
    </script>
  </head>

<body>

<nav class="navbar navbar-expand-lg bg-white mb-3 sticky-top">
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav" 
        aria-controls="nav" aria-label="Expand Navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <a href="../../profile/<?= $session_user_id ?>/<?= $session_username ?>" class="navbar-brand d-none d-md-block">Palbook</a>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="../../profile/<?= $session_user_id ?>/<?= $session_username ?>" class=" nav-link" id="navHome">Home</a>
                </li>
                <li class="nav-item">
                    <a href="../../friends/<?= $session_user_id ?>/<?= $session_username ?>" class="nav-link">Friends</a>
                </li>
                <li class="nav-item">
                    <a href="../../post/<?= $session_user_id ?>/<?= $session_username ?>" class="nav-link active">Post</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Menu
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="../../editProfile/<?= $session_user_id ?>/<?= $session_username ?>">Edit Profile</a>
                        <?php if($session_user_role == 'admin'): ?>
                            <a class="dropdown-item" href="../../manageUsers/<?= $session_user_id ?>/<?= $session_username ?>">Manage Users</a>
                        <?php endif ?>
                        <a class="dropdown-item" href="../../settings/<?= $session_user_id ?>/<?= $session_username ?>">Settings</a>
                        <a class="dropdown-item" a href="../../logout.php">Logout</a>
                    </div>
                </li> 
            </ul>
        </div>
        <form class="form-inline my-2 my-lg-0 d-flex me-3" action="../../search/<?= $session_user_id ?>/<?= $session_username ?>" method="post">
            <input class="form-control mr-sm-2 me-2" type="search" name="searchTerm" placeholder="Search" aria-label="Search">
            <button class="btn btn-outline-primary my-2 my-sm-0" type="submit">Search</button>
        </form>
    </nav>

<div class="container">
    <div class="card p-3 mb-3">
        <form action="../../post/<?= $session_user_id ?>/<?= $session_username ?>" method="post" enctype='multipart/form-data'>
            <legend>New Post</legend>
            <input type="hidden" name="id" value="<?= $row_id['id'] ?>">

            <div class="mb-3">
                <textarea class="form-control" name="text" id="mytextarea"></textarea>
            </div>
                
            <?php if(!empty($errorMessage)): ?>
                <p id="errorMessage"><?= $errorMessage ?></p>
            <?php endif ?>
            
            <!-- Source: https://stackoverflow.com/questions/1944267/how-to-change-the-button-text-of-input-type-file -->
            <div class="btn btn-secondary mb-4" id="uploadImage" onclick="document.getElementById('image').click();">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-camera me-2"></i>
                    <p class="mb-0">Upload Photo</p>
                </div>
            </div>
            <!-- <input type="button" class="btn btn-primary" id="uploadImage" value="Upload Image" onclick="document.getElementById('image').click();"> -->

            <div class="mb-3">
                <input class="form-control mb-3" type='file' name='image' id='image' value='Upload Image' style="display: none;">
            </div>

            <div class="mb-3">
                <input class="form-control mb-3 btn btn-primary" type="submit" name="command" value="Post">
            </div>
        </form>
    </div>


    <!-- <?php if ($upload_error_detected): ?>
        <p>Error Number: <?= $_FILES['image']['error'] ?></p>
    <?php elseif ($image_upload_detected): ?>
        <p>Client-Side Filename: <?= $_FILES['image']['name'] ?></p>
        <p>Apparent Mime Type: <?= $_FILES['image']['type'] ?></p>
        <p>Size in Bytes: <?= $_FILES['image']['size'] ?></p>
        <p>Temporary Patch: <?= $_FILES['image']['tmp_name'] ?></p>
    <?php endif ?> -->

</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>