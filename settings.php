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

        if(isset($_GET['id']))
        {
            // Sanitize the id
            $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

            $query2 = "SELECT * FROM users WHERE id=:id";
            $statement2 = $db->prepare($query2);
            $statement2->bindValue(':id', $id, PDO::PARAM_INT);
            $statement2->execute();
            $row_id = $statement2->fetch(PDO::FETCH_ASSOC); // Fetch the row
        }

        if ($session_user_id != $row_id['id'])
        {
            echo "<script>location.href='index.php'</script>";
        }

        // Set the profile picture to the changed picture
        if ($_POST && isset($_POST['image_id']))
        {
            $image_id = filter_input(INPUT_POST, 'image_id', FILTER_SANITIZE_NUMBER_INT);
            $query_change_profile_pic = "UPDATE users SET profile_image_id=:profile_image_id WHERE id=:id";
            $statement_change_profile_pic = $db->prepare($query_change_profile_pic);
            $statement_change_profile_pic->bindValue(':id', $id);
            $statement_change_profile_pic->bindValue(':profile_image_id', $image_id, PDO::PARAM_INT);
            $statement_change_profile_pic->execute();

            header("Location:../../profile/$session_user_id/$session_username");
            exit;
        }

        // Get the profile pic url
        if ($row_id['profile_image_id'] == null)
        {
            $profile_pic_url = 'anonymous.jpg';
        }
        else
        {
            $image_id = $row_id['profile_image_id'];
            $query_profile_pic = "SELECT url from images WHERE image_id='$image_id'";
            $statement_profile_pic = $db->prepare($query_profile_pic);
            $statement_profile_pic->execute();

            if ($row_profile_pic = $statement_profile_pic->fetch())
            {
                $profile_pic_url = $row_profile_pic['url'];
            }
            else
            {
                $profile_pic_url = 'anonymous.jpg';
            }
        }

        // Get all the images associated with the user of the page
        $query_images = "SELECT * FROM images WHERE id = :id";
        $statement_images = $db->prepare($query_images);
        $statement_images->bindValue(':id', $id, PDO::PARAM_INT);
        $statement_images->execute();

        if ($_POST && isset($_POST['id']) && $_POST['command'] === 'Delete account')
        {
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $queryDeleteUser = "DELETE FROM users WHERE id = :id LIMIT 1";
            $statement_delete_user = $db->prepare($queryDeleteUser);
            $statement_delete_user->bindValue(':id', $id, PDO::PARAM_INT);
            $statement_delete_user->execute();        

            unset($_SESSION['user']);

            // Redirect after DELETE
            header("Location:../../index.php");
            exit;
        }

    }
    else
    {
        echo "<script>location.href='index.php'</script>";
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Palbook Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="../../style.css">
    <script defer src="../../editProfile.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
  </head>
<body>

<nav class="navbar navbar-expand-lg bg-white mb-3 sticky-top">
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav" 
        aria-controls="nav" aria-label="Expand Navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <a href="../../profile/<?= $session_user_id ?>/<?= $session_username ?>" class="navbar-brand d-none d-md-block">Palbook</a>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="../../profile/<?= $session_user_id ?>/<?= $session_username ?>" class="nav-link" id="navHome">Home</a>
                </li>
                <li class="nav-item">
                    <a href="../../friends/<?= $session_user_id ?>/<?= $session_username ?>" class="nav-link">Friends</a>
                </li>
                <li class="nav-item">
                    <a href="../../post/<?= $session_user_id ?>/<?= $session_username ?>" class="nav-link">Post</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Menu
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item active" href="../../editProfile/<?= $session_user_id ?>/<?= $session_username ?>">Edit Profile</a>
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

<div class="container d-flex align-items-center justify-content-center">
    <div class="card p-3 mb-3">
        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <p class="h4 mb-2">Change profile picture</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    Edit
                </button>
            </div>

            <div class="d-flex justify-content-center">
                <img src="../../images/<?= $profile_pic_url ?>" class="border-3 border-white rounded-circle img-fluid w-50 h-auto shadow-sm p-1" style="max-width: 156px;">
            </div>
            
        </div>
        
        <!-- Modal for choosing a profile picture -->
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Set Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <?php while($userImage = $statement_images->fetch()): ?>
                                <div class="col-md-4">
                                    <img src="../../images/<?= $userImage['url'] ?>" class="img-fluid imageChoice" data-image_id="<?= $userImage['image_id'] ?>">
                                </div>
                            <?php endwhile ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> 
                    <form action="../../editProfile/<?= $session_user_id ?>/<?= $session_username ?>" id="profilePictureForm" method="post">
                        <button type="button" class="btn btn-primary" id="chooseImageBtn">Choose image</button>            
                    </form>
                </div>
                </div>
            </div>
        </div>








        <div class="border-top pt-3">
            <form action="../../editProfile/<?= $session_user_id ?>/<?= $session_username ?>" method="POST">
                <p class="h4 mb-4">Change password</p>
                <input type="hidden" name="id" value="<?= $row_id['id'] ?>">

                <div class="form-group mb-3">
                    <label for="currentPassword">Current password</label>
                    <input type="password" class="form-control" id="currentPassword" name="currentPassword" placeholder="Enter current password">
                </div>

                <div class="form-group mb-3">
                    <label for="newPassword">New password</label>
                    <input type="password" class="form-control" id="newPassword" name="newPassword" placeholder="Enter new password">
                </div>

                <div class="form-group mb-5">
                    <label for="school">Retype new password</label>
                    <input type="password" class="form-control" id="retypeNewPassword" name="retypeNewPassword" placeholder="Retype new password">
                </div>

                <div class="mb-3">
                    <input class="form-control mb-3 btn btn-primary" type="submit" id="btnDeleteAccount" value="Change password" name="submit">
                </div>
            </form>            
        </div>


        <div class="border-top pt-3">
            <form action="../../settings/<?= $session_user_id ?>/<?= $session_username ?>" method="POST">
                <p class="h4 mb-4">Delete your account</p>
                <input type="hidden" name="id" value="<?= $row_id['id'] ?>">
                <p>Once you delete your account, it cannot be retrived. All traces of your account will be removed from Palbook. 
                    This includes your posts, comments, likes and photos. Proceed with caution.
                            </p>
                <div class="mb-3">
                    <input class="form-control mb-3 btn btn-primary" type="submit" id="btnDeleteAccount" value="Delete account" name="command" onclick="return confirm('Are you sure you want to delete your account?')">
                </div>
            </form>            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>