<?php

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

        if(isset($_GET['post_id']))
        {
            // Sanitize the id. Like above but this time for INPUT GET
            $post_id = filter_input(INPUT_GET, 'post_id', FILTER_SANITIZE_NUMBER_INT);

            // Build the parameterized SQL query using the filtered id.
            $query = "SELECT * FROM posts WHERE post_id = :post_id LIMIT 1";
            $statement = $db->prepare($query);
            $statement->bindValue(':post_id', $post_id, PDO::PARAM_INT);

            // Execute the SELECT and fetch the single row returned.
            $statement->execute();
            $row = $statement->fetch();
            //print_r($row);
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


        // Update post if text and post_id are present in POST.
        if($_POST && isset($_POST['text']) && isset($_POST['post_id']) && $_POST['command'] === 'Update')
        {
            // Sanitize user input to escape HTML entities and filter out dangerous characters.
            $text = filter_input(INPUT_POST, 'text', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $post_id = filter_input(INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT);

            // Build the parameterized SQL query and bind to the above sanitized values.
            $query = "UPDATE posts SET text = :text where post_id = :post_id";
            $statement = $db->prepare($query);
            $statement->bindValue(':text', $text);
            $statement->bindValue(':post_id', $post_id, PDO::PARAM_INT);

            // EXECUTE the INSERT
            $statement->execute();

            if ($image_upload_detected)
            {
                $image_filename = $_FILES['image']['name'];
                $temporary_image_path = $_FILES['image']['tmp_name'];
                $new_image_path = file_upload_path($image_filename);

                if (file_is_an_image($temporary_image_path, $new_image_path))
                {
                    move_uploaded_file($temporary_image_path, $new_image_path);
                }

                $queryGetUserId = "SELECT id FROM posts WHERE post_id = :post_id LIMIT 1";
                $statementGetUserId = $db->prepare($queryGetUserId);
                $statementGetUserId->bindValue(':post_id', $post_id, PDO::PARAM_INT);
                $statementGetUserId->execute();
                $rowGetUserId = $statementGetUserId->fetch();
                
                $id = $rowGetUserId['id'];
                $url = $image_filename;
                $queryImage = "INSERT INTO images (id, url) VALUES (:id, :url)";
                $statementImage = $db->prepare($queryImage);
                $statementImage->bindValue(':id', $id);
                $statementImage->bindValue(':url', $url);
                $statementImage->execute();
        
                $image_id = $db->lastInsertId();
        
                $query_image_to_post = "UPDATE posts SET image_id=:image_id WHERE post_id='$post_id'";
                $statement_image_to_post = $db->prepare($query_image_to_post);
                $statement_image_to_post->bindValue(':image_id',$image_id);
                $statement_image_to_post->execute();
            }

            // Check if the associated image is being removed
            if (isset($_POST['removeImage']) && $_POST['removeImage'] == 'on')
            {
                $queryGetImageId = "SELECT image_id FROM posts WHERE post_id='$post_id'";
                $statementGetImageId = $db -> prepare($queryGetImageId);
                $statementGetImageId -> execute();
                $rowImageId = $statementGetImageId -> fetch();
                $image_id = $rowImageId['image_id'];

                $queryRemoveImageFromPost = "UPDATE posts SET image_id=0 WHERE post_id='$post_id'";
                $statementRemoveImageFromPost = $db -> prepare($queryRemoveImageFromPost);
                $statementRemoveImageFromPost -> execute();

                // Delete the image from the file system before deleting data from database
                $queryImageUrl = "SELECT url FROM images WHERE image_id=:image_id";
                $statement_image_url = $db->prepare($queryImageUrl);
                $statement_image_url->bindValue(':image_id', $image_id);
                $statement_image_url->execute();
                $row_image_url = $statement_image_url->fetch();
                $image_url = $row_image_url['url'];

                $file_to_delete = 'images/' . $image_url;
                unlink($file_to_delete);  

                // Delete the image data from the database
                $queryDeleteImage = "DELETE FROM images WHERE image_id='$image_id'";
                $statementDeleteImage = $db -> prepare($queryDeleteImage);
                $statementDeleteImage -> execute();
            }

            // Build the parameterized SQL query using the filtered id.
            $query2 = "SELECT * FROM posts WHERE post_id = :post_id LIMIT 1";
            $statement = $db->prepare($query2);
            $statement->bindValue(':post_id', $post_id, PDO::PARAM_INT);

            // Execute the SELECT and fetch the single row returned.
            $statement->execute();
            $row = $statement->fetch();
            $row_id = $row['id'];

            // Get the username of the profile page
            $queryUsername = "SELECT username FROM users WHERE id=:id";
            $statement_query_username = $db->prepare($queryUsername);
            $statement_query_username->bindValue(':id', $row_id, PDO::PARAM_INT);
            $statement_query_username->execute();
            $row_username = $statement_query_username->fetch();
            $username = $row_username['username'];            

            $username = str_replace(' ', '-', $username);
            // Redirect after update
            header("Location:../../profile/$row_id/$username");
            exit;
        }
        else if($_POST && isset($_POST['post_id']) && $_POST['command'] === 'Delete')
        {
            // Retrieve the id to get back to the profile
            $post_id = filter_input(INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT);
            $query2 = "SELECT * FROM posts WHERE post_id = :post_id LIMIT 1";
            $statement2 = $db->prepare($query2);
            $statement2->bindValue(':post_id', $post_id, PDO::PARAM_INT);
            $statement2->execute();
            $row = $statement2->fetch();
            $row_id = $row['id'];
            $image_id = $row['image_id'];

            // Retrieve the username of the post's owner
            $queryPoster = "SELECT username FROM users WHERE id='$row_id'";
            $statement_poster = $db->prepare($queryPoster);
            $statement_poster->execute();
            $row_poster = $statement_poster->fetch();
            $poster_username = $row_poster['username'];

            // Delete the image from the file system before deleting data from database
            $queryImageUrl = "SELECT url FROM images WHERE image_id=:image_id";
            $statement_image_url = $db->prepare($queryImageUrl);
            $statement_image_url->bindValue(':image_id', $image_id);
            $statement_image_url->execute();
            $row_image_url = $statement_image_url->fetch();
            $image_url = $row_image_url['url'];

            $file_to_delete = 'images/' . $image_url;
            unlink($file_to_delete);  

            // Delete the image data from the database
            $queryDeleteImage = "DELETE FROM images WHERE image_id='$image_id'";
            $statementDeleteImage = $db -> prepare($queryDeleteImage);
            $statementDeleteImage -> execute();

            // Delete the post from the posts table.
            $query = "DELETE FROM posts WHERE post_id = :post_id LIMIT 1";
            $statement = $db->prepare($query);
            $statement->bindValue(':post_id', $post_id, PDO::PARAM_INT);
            $statement->execute();  

            $poster_username = str_replace(' ', '-', $poster_username);

            // Redirect after DELETE
            header("Location:../../profile/$row_id/$poster_username");
            exit;
        }
        else{
            $post_id = false; // FALSE if we are not UPDATING or DELETING.
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
    <title>Palbook Edit Post</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="../../style.css">
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
                    <a href="../../profile/<?= $session_user_id ?>/<?= $session_username ?>" class="nav-link active" id="navHome">Home</a>
                </li>
                <li class="nav-item">
                    <a href="../../friends/<?= $session_user_id ?>/<?= $session_username ?>" class="nav-link">Friends</a>
                </li>
                <li class="nav-item">
                    <a href="../../post/<?= $session_user_id ?>/<?= $session_username ?>" class="nav-link">Post</a>
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
            <form action="../../edit/<?= $row['post_id'] ?>/<?= $session_username ?>" method="post" enctype='multipart/form-data'>
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <input type="hidden" name="post_id" value="<?= $row['post_id'] ?>">

                <legend>Edit Post</legend>
                <div class="mb-3">
                    <textarea class="form-control" name="text" id="mytextarea">
                        <?= trim($row['text']) ?>
                    </textarea>
                </div>
                
                <?php if( $row['image_id'] != 0):?>
                    <div class="mb-3">
                        <?php
                            $image_id = $row['image_id'];
                            $query_post_pic = "SELECT url from images WHERE image_id='$image_id'";
                            $statement_post_pic = $db->prepare($query_post_pic);
                            $statement_post_pic->execute();
                            $row_post_pic = $statement_post_pic->fetch();
                            $post_pic_url = $row_post_pic['url'];                        
                        ?>
                        <img src="../../images/<?= $post_pic_url ?>" class="img-fluid w-100 h-auto">
                    </div>
                    <div class="mb-3">
                        <input class="form-check-input" name="removeImage" type="checkbox" id="flexCheck">
                        <label class="form-check-label" for="flexCheck">Remove associated image from post</label>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <input class="form-control mb-3" type='file' name='image' id='image' value='Upload Image'>
                    </div>
                <?php endif ?>


        </div>
                <div class="mb-3">
                    <input class="btn btn-primary me-3" type="submit" name="command" value="Update">
                    <input class="btn btn-warning" type="submit" name="command" value="Delete" onclick="return confirm('Are you sure you wish to delete this post?')">
                </div>

            </form>
        <!-- </div> -->
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>