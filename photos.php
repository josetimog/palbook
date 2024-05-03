<?php

    require('connection.php');

    session_start();

    if(isset($_SESSION['user']))
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

            // Get the user information
            $query2 = "SELECT * FROM users WHERE id=:id";
            $statement2 = $db->prepare($query2);
            $statement2->bindValue(':id', $id, PDO::PARAM_INT);
            $statement2->execute();
            $row_id = $statement2 ->fetch();
            $page_username = str_replace(' ', '-', $row_id['username']);

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

            $row_user_id = $row_id['id'];

            // Get the friend count
            $queryFriends = "SELECT * FROM friendships WHERE (first_id='$id' OR second_id='$id') AND type='friends'";
            $statement_friends = $db->prepare($queryFriends);
            $statement_friends->execute();
            $friendCount = $statement_friends->rowCount();

            // Get all the images associated with the user of the page
            $query_images = "SELECT * FROM images WHERE id = :id";
            $statement_images = $db->prepare($query_images);
            $statement_images->bindValue(':id', $id, PDO::PARAM_INT);
            $statement_images->execute();

        }
    }
    else
    {
        echo "<script>location.href='login.php'</script>";
    }

?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Palbook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="../../style.css">
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
                    <a href="../../profile/<?= $session_user_id ?>/<?= $session_username ?>" class=" nav-link active" id="navHome">Home</a>
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
    
    <div class="container container-fluid px-0">
        <section id="hero" class="mx-auto my-2 bg-white py-4 mb-3 px-sm-0">

            <div class="row">
                <div class="col-md-4 px-0 d-flex justify-content-center">
                   
                    <img src="../../images/<?= $profile_pic_url ?>" class="border-3 border-white rounded-circle img-fluid w-50 h-auto shadow-sm p-1">
                </div>
                <div class="col-md-4 px-0 d-flex justify-content-center align-items-center py-2">
                    <div class="d-flex flex-column">
                        <h1 class="fw-bolder"><?= $row_id['username'] ?></h1>
                        <?php if($friendCount == 1): ?>
                            <p class="mb-0 h6"><strong><?= $friendCount ?></strong> friend</p>
                        <?php else: ?>
                            <p class="mb-0 h6"><strong><?= $friendCount ?></strong> friends</p>
                        <?php endif ?>
                    </div>                   
                </div>

                <!-- Test for friendship -->
                <?php $friendTest = 1 ?>

                <?php
                    $friendToTest = $row_id['id'];
                    if($session_user_id != $friendToTest)
                    {
                        $queryFriendTest = "SELECT friendship_id
                                            FROM friendships
                                            WHERE ((first_id='$session_user_id' && second_id='$friendToTest') ||
                                                    (first_id='$friendToTest' && second_id='$session_user_id'))
                                            AND (type='friends' OR type='pending_first_second' OR type='pending_second_first')";
                        $statement_friend_test = $db->prepare($queryFriendTest);
                        $statement_friend_test->execute();
                        $friendTest = $statement_friend_test->rowCount();
                    }
                ?>                                            

                <div class="col-md-4 px-0 d-flex justify-content-center align-items-end">
                    <?php if($row_id['username'] == $_SESSION['user']): ?>
                        <a class="btn btn-light" href="../../editProfile/<?= $session_user_id ?>/<?= $session_username ?>"><i class="fa-solid fa-pen me-2"></i>Edit profile</a>
                    <?php elseif($friendTest == 0): ?>
                        <form action="../../friends/<?= $session_user_id?>/<?= $session_username ?>" method="post">
                            <input type="hidden" name="requester" value="<?= $session_user_id ?>">
                            <input type="hidden" name="requestee" value="<?= $row_id['id']?>">
                            <input type="submit" name="command" class="btn btn-primary" value="Add friend">
                        </form>
                    <?php endif ?>                                       
                </div>
            </div>

            <!-- <nav class="navbar border-top">
                <ul class="navbar-nav">
                    <li class="nav-item"><a href="profile.php?id=<?= $row_id['id'] ?>" class="text-decoration-none">Posts</a></li>
                    <li class="nav-item"><a href="#" class="text-decoration-none">Photos</a></li>
                </ul>
            </nav> -->

        </section>

    </div>

    <div class="container container-fluid">
        <div class="row">
            <div class="col px-0">

                <div class="card rounded mb-6 bg-white shadow-sm border-0 px-xs-0 p-3 pt-1 custom-card">
                    <div class="d-flex border-bottom py-2 mb-2">
                        <a href="../../profile/<?= $row_id['id'] ?>/<?= $page_username ?>" class="rounded-pill bg-white border-none me-3 p-2 text-decoration-none text-muted fw-bolder">Posts</a>
                        <p class="bg-body-secondary rounded-pill mb-0 me-3 px-3 py-2 text-primary fw-bolder">Photos</p>
                    </div>
                    <p class="fw-bold mb-3 h5"><?= $row_id['username'] ?>'s Photos</p>
                    
                    <div class="row">
                        <?php while($image = $statement_images->fetch()): ?>
                            <div class="col-4 card p-0 border-0">
                                <img src="../../images/<?= $image['url'] ?>">
                            </div>
                        <?php endwhile ?>
                    </div>


                </div>

                


            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
  </body>
</html>