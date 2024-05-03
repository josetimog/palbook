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
        $session_user = $statement_session_user->fetch();
        $session_user_id = $session_user['id'];
        $session_username = $session_user['username'];
        $session_username = str_replace(' ', '-', $session_username);
        $session_user_role = $session_user['role'];
        $session_user_profile_image_id = $session_user['profile_image_id'];

        // Get the session user profile pic
        $query_session_user_profile_pic = "SELECT url from images WHERE image_id='$session_user_profile_image_id'";
        $statement_session_user_profile_pic = $db->prepare($query_session_user_profile_pic);
        $statement_session_user_profile_pic->execute();
        if ($row_session_user_profile_pic = $statement_session_user_profile_pic->fetch())
        {
            $session_user_profile_pic_url = $row_session_user_profile_pic['url'];
        }
        else
        {
            $session_user_profile_pic_url = 'anonymous.jpg';
        }

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
            $row_username = str_replace(' ', '-', $row_id['username']);
            $row_user_id = $row_id['id'];

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
            
            // Get first 6 friends of current profile page user
            $queryAllFriendsId = "SELECT 
                                    CASE
                                        WHEN first_id ='$row_user_id' THEN second_id
                                        ELSE first_id
                                        END AS friend_user_id
                                    FROM friendships
                                WHERE (first_id='$row_user_id' OR second_id='$row_user_id') AND type='friends'
                                LIMIT 6";
            $statement_six_friends_id = $db->prepare($queryAllFriendsId);
            $statement_six_friends_id->execute();

            // Get the friend count
            $queryFriends = "SELECT * FROM friendships WHERE (first_id='$id' OR second_id='$id') AND type='friends'";
            $statement_friends = $db->prepare($queryFriends);
            $statement_friends->execute();
            $friendCount = $statement_friends->rowCount();

            // Get the posts
            $query = "SELECT * FROM posts WHERE id=:id ORDER BY updated_at DESC LIMIT 10";
            $statement = $db->prepare($query);
            $statement->bindValue(':id', $id, PDO::PARAM_INT);
            $statement->execute();

            // Get the image using the id
            $query_image = "SELECT * FROM images WHERE id = :id";
            $statement_image = $db->prepare($query_image);
            $statement_image->bindValue(':id', $id, PDO::PARAM_INT);
            $statement_image->execute();
            $row_image = $statement_image->fetchall();

            if ($_POST && isset($_POST['commentText']) && isset($_POST['post_id']))
            {
                $text = filter_input(INPUT_POST, 'commentText', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $post_id = filter_input(INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT);
                $username = $_SESSION['user'];
                
                // Fetch the user's ID using the prepared statement
                $query = "SELECT id FROM users WHERE username=:username";
                $statement_commenter_id = $db->prepare($query);
                $statement_commenter_id->bindValue(':username', $username);
                $statement_commenter_id->execute();
                $row = $statement_commenter_id->fetch();
                $id = $row['id'];

                // Insert comment using prepared statement
                $query = "INSERT INTO comments (post_id, text, id) VALUES (:post_id, :text, $id)";
                $statement_insert_comment = $db->prepare($query);
                $statement_insert_comment->bindValue(':post_id', $post_id);
                $statement_insert_comment->bindValue(':text', $text);
                $statement_insert_comment->execute();
            }

            if($_POST && isset($_POST['replyText']) && isset($_POST['comment_id']))
            {
                $text = filter_input(INPUT_POST, 'replyText', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_SANITIZE_NUMBER_INT);
                $username = $_SESSION['user'];
                
                // Fetch the user's ID using the prepared statement
                $query = "SELECT id FROM users WHERE username=:username";
                $statement_replier_id = $db->prepare($query);
                $statement_replier_id->bindValue(':username', $username);
                $statement_replier_id->execute();
                $row = $statement_replier_id->fetch();
                $id = $row['id'];

                // Insert comment using prepared statement
                $query = "INSERT INTO replies (comment_id, text, id) VALUES (:comment_id, :text, $id)";
                $statement_insert_reply = $db->prepare($query);
                $statement_insert_reply->bindValue(':comment_id', $comment_id);
                $statement_insert_reply->bindValue(':text', $text);
                $statement_insert_reply->execute();
            }
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" type="text/css" href="../../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

    <script>
        $(document).ready(function() {

            // Event for liking a post
            $(".post-like-btn").click(function() {
                let postId = $(this).data("post-id");
                let userId = $(this).data("user-id");

                $.ajax({
                    url: "../../like_post.php",
                    type: "POST",
                    data: {
                            post_id: postId,
                            user_id: userId,
                        },
                    success: function(data) {
                        // update count
                        $("#likes-" + postId).html(data);
                    }
                })
            });

            // Event for liking a comment
            $(".comment-like-btn").click(function() {
                let commentId = $(this).data("comment-id");
                let userId = $(this).data("user-id");

                $.ajax({
                    url: "../../like_comment.php",
                    type: "POST",
                    data: {
                            comment_id: commentId,
                            user_id: userId,
                        },
                    success: function(data) {
                        // update count
                        $("#likes-" + commentId).html(data);
                    }
                })
            });

            // Event for liking a reply
            $(".reply-like-btn").click(function() {
                let replyId = $(this).data("reply-id");
                let userId = $(this).data("user-id");

                $.ajax({
                    url: "../../like_reply.php",
                    type: "POST",
                    data: {
                            reply_id: replyId,
                            user_id: userId,
                        },
                    success: function(data) {
                        // update count
                        $("#likes-" + replyId).html(data);
                    }
                })
            });


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
                <li class="nav-item d-flex align-items-center justify-content-center">
                    <div class="mx-4">
                        <a href="../../profile/<?= $session_user_id ?>/<?= $session_username ?>" class=" nav-link active p-0 d-flex flex-column align-items-center" id="navHome">
                            <i class="fa-solid fa-house-chimney"></i>
                            <div class="text-xs">Home</div>
                        </a>
                        
                    </div>
                </li>
                <li class="nav-item d-flex align-items-center justify-content-center">
                    <div class="mx-4">
                        <a href="../../friends/<?= $session_user_id ?>/<?= $session_username ?>" class="nav-link p-0 d-flex flex-column align-items-center">
                            <i class="fa-solid fa-user-group"></i>
                            <div class="text-xs">Friends</div>
                        </a>
                    </div>
                </li>
                <li class="nav-item d-flex align-items-center justify-content-center">
                    <div class="mx-4">
                        <a href="../../post/<?= $session_user_id ?>/<?= $session_username ?>" class="nav-link p-0 d-flex flex-column align-items-center">
                            <i class="fa-solid fa-pen"></i>
                            <span class="text-xs">Post</span>
                        </a>
                    </div>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle-no-arrow p-0 mx-4" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                         <div class="d-flex flex-column align-items-center">
                            <img src="../../images/<?= $session_user_profile_pic_url ?>" class="img-fluid rounded-circle" style="max-width: 34px;">
                         </div>   
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="../../editProfile/<?= $session_user_id ?>/<?= $session_username ?>"><i class="fa-solid fa-pen"></i> Edit Profile</a>
                        <?php if($session_user_role == 'admin'): ?>
                            <a class="dropdown-item" href="../../manageUsers/<?= $session_user_id ?>/<?= $session_username ?>"><i class="fa-solid fa-user-gear"></i> Manage Users</a>
                        <?php endif ?>
                        <div class="d-flex">
                            <a class="dropdown-item" href="../../settings/<?= $session_user_id ?>/<?= $session_username ?>"><i class="fa-solid fa-gear"></i> Settings</a>
                        </div>
                        <div class="d-flex">
                            <a class="dropdown-item" a href="../../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                        </div>
                    </div>
                </li>

                <!-- Notifications -->
                <li class="d-flex justify-content-center align-items-center">
                    <div class="mx-4">
                        <i class="nav-item fa-solid fa-bell fa-xl me-3"></i>
                    </div>
                </li>
                
            </ul>
        </div>
        
        <form class="form-inline my-2 my-lg-0 d-flex me-3" action="../../search/<?= $session_user_id ?>/<?= $session_username ?>" method="post">
            <input class="form-control mr-sm-2 me-2" type="search" name="searchTerm" placeholder="Search" aria-label="Search">
            <button class="btn btn-outline-primary my-2 my-sm-0" type="submit">Search</button>
        </form>
        <!-- <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Menu
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="editProfile.php?id=<?= $session_user_id ?>">Edit Profile</a>
                        <?php if($session_user_role == 'admin'): ?>
                            <a class="dropdown-item" href="manageUsers.php?id=<?= $session_user_id ?>">Manage Users</a>
                        <?php endif ?>
                        <a class="dropdown-item" href="settings.php?id=<?= $session_user_id ?>">Settings</a>
                        <a class="dropdown-item" a href="logout.php">Logout</a>
                    </div>
                </li>
            </ul>
        </div> -->

    </nav>
    
    <div class="container container-fluid px-0">
        <section id="hero" class="mx-auto my-2 bg-white py-4 mb-3 px-sm-0">

            <div class="row">
                <div class="col-md-4 px-0 d-flex justify-content-center">
                   <!-- Main Profile Picture -->
                    <div class="main-profile-pic d-flex justify-content-center">
                        <img src="../../images/<?= $profile_pic_url ?>" class="border-3 border-white rounded-circle img-fluid w-50 h-auto shadow-sm p-1">
                        <?php if($row_id['username'] == $_SESSION['user']): ?>
                            <div class="add-profile-pic d-flex justify-content-center align-items-center bg-body-secondary border-3 border-white"><i class="fa-solid fa-camera"></i></div>
                        <?php endif ?>
                        
                    </div>
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
                        <form action="../../friends/<?= $session_user_id ?>/<?= $session_username ?>" method="post">
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
            <div class="col-md-4 px-0 pe-md-3">

                <div class="card rounded mb-6 bg-white shadow-sm border-0 px-xs-0 p-3 pt-1 custom-card">
                    <div class="d-flex border-bottom py-2 mb-2">
                        <p class="bg-body-secondary rounded-pill mb-0 me-3 px-3 py-2 text-primary fw-bolder">Posts</p>
                        <a href="../../photos/<?= $row_user_id ?>/<?= $row_username ?>" class="rounded-pill bg-white border-none me-3 p-2 text-decoration-none text-muted fw-bolder">Photos</a>
                    </div>
                    <p class="fw-bold mb-3 h5">Details</p>
                    <div class="mb-2">
                        <i class="fa-solid fa-briefcase"></i>
                        <strong class="font-semibold"><?= $row_id['occupation'] ?></strong>
                    </div>
                    <div class="mb-2">
                        <i class="fa-solid fa-graduation-cap"></i>
                        Studied at <strong><?= $row_id['school'] ?></strong>
                    </div>
                    <div class="mb-2">
                        <i class="fa-solid fa-location-dot"></i>
                        Lives in <strong><?= $row_id['location'] ?></strong>
                    </div>                            
                </div>

                <div class="card rounded mb-6 bg-white shadow-sm border-0 p-3 custom-card d-flex flex-column">
                    <p class="fw-bold mb-3 h5">Friends</p>
                    <div class="row mb-3 gx-2 gy-2">
                        <?php while($sixFriendsRowId = $statement_six_friends_id->fetch()): ?>
                            <?php
                                $querySixFriends = "SELECT * FROM users WHERE id='".$sixFriendsRowId['friend_user_id']."'";
                                $statement_six_friends = $db->prepare($querySixFriends);
                                $statement_six_friends->execute();
                                $statement_six_friends_row = $statement_six_friends->fetch();
                                $six_friends_name = str_replace(' ', '-', $statement_six_friends_row['username']);

                                // Retrieve friend's profile pic url
                                $image_id = $statement_six_friends_row['profile_image_id'];
                                $query_thumbnail_friend_pic = "SELECT url from images WHERE image_id='$image_id'";
                                $statement_thumbnail_friend_pic = $db->prepare($query_thumbnail_friend_pic);
                                $statement_thumbnail_friend_pic->execute();

                                if ($statement_thumbnail_friend_pic = $statement_thumbnail_friend_pic->fetch())
                                {
                                    $friend_thumbnail_pic_url = $statement_thumbnail_friend_pic['url'];  
                                }
                                else
                                {
                                    $friend_thumbnail_pic_url = 'anonymous.jpg';
                                }
 
                            ?>
                            
                            <div class="col-4">
                                <div class="card rounded p-0 bg-white border-0">
                                    <div class="d-flex flex-column">
                                        <a href="../../profile/<?= $statement_six_friends_row['id'] ?>/<?= $six_friends_name ?>"><img src="../../images/<?= $friend_thumbnail_pic_url ?>" class="border rounded image-fluid w-100"></a>
                                        <a href="../../profile/<?= $statement_six_friends_row['id'] ?>/<?= $six_friends_name ?>" class="text-decoration-none text-dark fw-bold align-self-center"><?= $statement_six_friends_row['username'] ?></a>
                                    </div>
                                </div>
                            </div>

                        <?php endwhile ?>
                    </div>
                    <div>
                        <a href="../../friends/<?= $row_user_id ?>/<?= $row_username ?>" class="text-decoration-none">
                            <div class="d-flex justify-content-center rounded-2 p-1 bg-body-secondary">
                                <p class="text-dark fw-bolder mb-0">See all friends</p>
                            </div>
                        </a>
                    </div>  
                </div>

            </div>

            <div class="col-md-8 px-0">
                
                <div class="card rounded mb-6 p-3 bg-white shadow-sm border-0 custom-card">
                    <p class="fw-bold mb-0 h5"><?= $row_id['username'] ?>'s posts</p>
                </div>
                
                <?php if($row_id['username'] == $_SESSION['user']): ?>
                    <div class="card rounded bg-white shadow-sm border-0 mb-6 custom-card">
                    <a href="../../post/<?= $session_user_id ?>/<?= $session_username ?>" class="text-decoration-none p-1">
                        <button class="btn btn-light border-0 d-flex py-3 px-3 flex-items-center justify-start w-100">
                            <i class="fa-solid fa-pen fs-5 p-2"></i>
                            <div class="d-flex flex-column align-items-start">
                                <h5 class="fw-bold">Create a Post</h5>
                                <p class="fw-light mb-0">Share a photo or write something</p>
                            </div>
                        </button>
                    </a>
                </div>               
                <?php endif ?>

                <div id="all_posts">
                    <?php while($row = $statement->fetch()): ?>
                        <div class="post main-div py-3 rounded bg-white shadow-sm custom-card ">
                            <div class="d-flex justify-content-between px-4">
                                <div class="d-flex">
                                    <a href="../../profile/<?= $row['id'] ?>/<?= $row_username ?>" class="m-0 p-0">
                                        <img src="../../images/<?= $profile_pic_url ?>" class="border rounded-circle w-5 img-fluid me-2" style="max-width: 40px;">
                                    </a>                                    
                                    <div class="d-flex flex-column">
                                        <a href="../../profile/<?= $row['id'] ?>/<?= $row_username ?>" class="text-decoration-none text-dark fw-bold"><?= $row_id['username'] ?></a>
                                        <small><?= date('F j, Y, g:i a', strtotime($row['updated_at'])) ?></small>
                                    </div>
                                </div>
                                    <?php if($row_id['username'] == $_SESSION['user'] || $session_user_role == 'admin'): ?>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-white rounded-circle dropdown-toggle-no-arrow" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-ellipsis align-self-end"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a href="../../edit/<?= $row['post_id'] ?>/<?= $session_username ?>" class="dropdown-item">edit post</a></li>
                                            </ul>
                                        </div>
                                    <?php endif ?> 
                                </div>

                            <div class="post_content px-4 pt-2">
                                <!-- <?= substr($row['text'], 0, 200) . (strlen($row['text']) > 200 ? '...' : '') ?> -->
                                
                                <?php
                                    $decoded_content = html_entity_decode($row['text']);
                                    echo $decoded_content;
                                ?>
                                
                                <br>
                            </div>
                                
                                <?php foreach($row_image as $image): ?>
                                    <?php if($image['image_id'] == $row['image_id']): ?>
                                        <img src="../../images/<?= $image['url'] ?>" class="card-img-top img-fluid">
                                    <?php endif ?>
                                <?php endforeach ?>

                                <!-- <a href="show.php?post_id=<?= $row['post_id'] ?>">see more</a> -->
                            <!-- </div> -->
                            <br>
                            
                            <!-- Retrieve the current amount of likes for the post -->
                            <?php
                                $postId = $row['post_id'];
                                $queryLikeCount = "SELECT * FROM likes WHERE post_id=:postId";
                                $getLikeCount = $db->prepare($queryLikeCount);
                                $getLikeCount->bindParam(':postId', $postId, PDO::PARAM_INT);
                                $getLikeCount->execute();
                                $postLikeCount = $getLikeCount->rowCount();
                            ?>

                            <div class="px-4 py-2 border-bottom d-flex justify-content-between">
                                <div class="d-flex mb-0 align-items-center">
                                    <button class="btn btn-primary border-0 rounded-circle me-1 d-flex justify-content-center align-items-center" style="width:20px; height:20px;" disabled>
                                        <i class="fa-solid fa-thumbs-up fa-2xs"></i>
                                    </button>
                                    <small class="mb-0 text-secondary" id="likes-<?= $row['post_id'] ?>"><?= $postLikeCount ?></small>
                                </div>

                                <?php
                                    $post_id = $row['post_id'];
                                    $query_comments = "SELECT * FROM comments WHERE post_id=:post_id";
                                    $statement_commentCount = $db->prepare($query_comments);
                                    $statement_commentCount -> bindValue(':post_id',$post_id);
                                    $statement_commentCount->execute();
                                    $commentCount = $statement_commentCount->rowCount();
                                ?>

                                <small class="mb-0 align-self-center text-secondary"><?= $commentCount ?> comments</small>
                            </div>

                            <div class=" mt-2 mb-2 px-4 pb-2 py-1 d-flex justify-content-between mx-auto border-bottom">
                                <div class="d-flex align-items-center ms-4">
                                    <i class="fa-regular fa-thumbs-up me-1"></i>
                                    <button class="btn text-dark border-0 py-0 px-0 post-like-btn" data-user-id="<?= $session_user_id ?>" data-post-id="<?= $post_id ?>"><small>Like</small></button>
                                </div>
                                <div class="d-flex align-items-center me-4">
                                    <i class="fa-regular fa-message me-1"></i>
                                    <button class="btn text-dark border-0 py-0 px-0" data-bs-toggle="collapse" data-bs-target="#collapseCommentBox<?= $post_id ?>" aria-expanded="false" aria-controls="collapseCommentBox<?= $post_id ?>"><small>Comment</small></button>
                                </div>
                            </div>
                            
                            <!-- Comment Box -->
                            <div class="collapse" id="collapseCommentBox<?= $post_id ?>">
                                <form action="../../profile/<?= $row_user_id ?>/<?= $row_username ?>" method="POST" class="px-4 border-top pt-2 mb-2 d-flex" id="commentForm">
                                    <input type="hidden" name="post_id" value="<?= $row['post_id'] ?>">
                                    <input type="hidden" name="captcha" value="<?= $_SESSION['captcha'] ?>">
                                    <textarea class="form-control border-0 bg-light rounded-4" name="commentText" id="commentText" cols="30" rows="2" placeholder="Write a comment..."></textarea>

                                    <button type="button" class="btn btn-primary border-0 rounded-circle mx-2" style="width:40px; height:40px;" data-bs-toggle="modal" data-bs-target="#exampleModal"><i class="fa-solid fa-paper-plane align-self-center"></i></button>

                                    <!--  Comment Modal Source: https://getbootstrap.com/docs/4.0/components/modal/ -->
                                    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Verify your humanity</h1>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <!-- calling this will update the captcha session. the current page is still holding on to the old captcha -->
                                                    <img src="../../captcha.php" alt="CAPTCHA image">
                                                    <p>Type the characters above:</p>
                                                    <input type="text" id="modalCaptchaText">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="button" class="btn btn-primary" id="submitComment">Post comment</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </form>
                            </div>

                            <script>
                                document.getElementById('submitComment').addEventListener('click', function() {
                                    let captchaInput = document.getElementById('modalCaptchaText').value;
                                    let sessionCaptcha = "<?php echo $_SESSION['captcha']?>";
                                    if(captchaInput == sessionCaptcha)
                                    {
                                        document.getElementById('commentForm').submit();
                                    }
                                    else
                                    {
                                        alert('CAPTCHA verification failed. Please try again.');
                                    }
                                });
                            </script>
                            <!--  Comment Modal Source: https://getbootstrap.com/docs/4.0/components/modal/ -->
                            <!-- <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5" id="exampleModalLabel">Verify your humanity</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <img src="captcha.php">
                                        <p>Type the characters above:</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary">Post comment</button>
                                    </div>
                                    </div>
                                </div>
                            </div> -->

                            <!-- <div class="collapse" id="collapseReplyComment<?= $comment_id ?>">
                                    <div class="card card-body border-0">
                                        <form action="profile.php?id=<?= $row_id['id'] ?>" method="post" class="d-flex align-items-center">
                                            <input type="hidden" name="comment_id" value="<?= $comment_id ?>">
                                            <textarea class="form-control mb-2" rows="3" name="replyText" placeholder="Write your reply..."></textarea>
                                            <button type="submit" class="btn btn-primary border-0 rounded-circle mx-2" style="width:40px; height:40px;"><i class="fa-solid fa-paper-plane align-self-center"></i></button>
                                        </form>
                                    </div>
                                </div> -->



                            <?php
                                $post_id = $row['post_id'];
                                $query_comments = "SELECT * FROM comments WHERE post_id=:post_id";
                                $statement_comments = $db->prepare($query_comments);
                                $statement_comments -> bindValue(':post_id',$post_id);
                                $statement_comments->execute();
                            ?>

                            <?php while($row_statements = $statement_comments->fetch()): ?>

                                <?php
                                    $comment_id = $row_statements['comment_id'];
                                    $query_replies = "SELECT * FROM replies WHERE comment_id=:comment_id";
                                    $statement_replies = $db->prepare($query_replies);
                                    $statement_replies -> bindValue(':comment_id',$comment_id);
                                    $statement_replies->execute();
                                ?>

                                <?php
                                    $id = $row_statements['id'];
                                    $query_comment_user = "SELECT * FROM users WHERE id=:id";
                                    $statement_comment_user = $db->prepare($query_comment_user);
                                    $statement_comment_user -> bindValue(':id', $id, PDO::PARAM_INT);
                                    $statement_comment_user->execute();
                                    $row_comment_user = $statement_comment_user->fetch();
                                    $comment_user_name = str_replace(' ', '-', $row_comment_user['username']);

                                    // Get the profile pic url
                                    $image_id = $row_comment_user['profile_image_id'];
                                    $query_commenter_pic = "SELECT url from images WHERE image_id='$image_id'";
                                    $statement_commenter_pic = $db->prepare($query_commenter_pic);
                                    $statement_commenter_pic->execute();

                                    if ($row_commenter_pic = $statement_commenter_pic->fetch())
                                    {
                                        $commenter_pic_url = $row_commenter_pic['url'];
                                    }
                                    else
                                    {
                                        $commenter_pic_url = 'anonymous.jpg';
                                    }

                                    // Calculate the elapsed time since the comment was made
                                    date_default_timezone_set('Canada/Central');
                                    $current_time = time();
                                    $item_created_time = strtotime($row_statements['created_at']);
                                    $time_difference = $current_time - $item_created_time;

                                    // Convert the time difference to a human-readable format
                                    if ($time_difference < 60)
                                    {
                                        $elapsed_time = 'Just now';
                                    }
                                    elseif ($time_difference < 3600)
                                    {
                                        $elapsed_time = floor($time_difference / 60). 'm';
                                    }
                                    elseif ($time_difference < 86400)
                                    {
                                        $elapsed_time = floor($time_difference / 3600) . 'h';
                                    }
                                    else
                                    {
                                        $elapsed_time = floor($time_difference / 86400);
                                        if ($elapsed_time == 1)
                                        {
                                            $elapsed_time = $elapsed_time . 'd';
                                        }
                                        else
                                        {
                                            $elapsed_time = $elapsed_time . 'd';
                                        }
                                    }
                                ?>

                                <div class="d-flex px-2 mb-2">
                                    <div class="col-auto">
                                        <a href="../../profile/<?= $row_comment_user['id'] ?>/<?= $comment_user_name ?>"><img src="../../images/<?= $commenter_pic_url ?>" class="border rounded-circle w-5 img-fluid me-2" style="max-width: 40px;"></a>
                                    </div>
                                    <div>
                                        <div class="bg-body-secondary rounded-4 p-2 mb-1">
                                            <div class="d-flex justify-content-between">
                                                <a href="../../profile/<?= $row_comment_user['id'] ?>/<?= $comment_user_name ?>" class="text-decoration-none text-dark fw-bold"><?= $row_comment_user['username'] ?></a>

                                                <?php if($session_user_role == 'admin'): ?>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm bg-body-secondary rounded-circle dropdown-toggle-no-arrow" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-ellipsis align-self-end"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <form action="../../editComment.php" method="post">
                                                                <input type="hidden" name="id" value="<?= $row_id['id'] ?>">
                                                                <input type="hidden" name="comment_id" value="<?= $row_statements['comment_id'] ?>">
                                                                <?php if($row_statements['visible'] == 1): ?>
                                                                    <li class="d-flex align-items-center">
                                                                        <i class="fa-solid fa-eye-slash ps-3"></i>
                                                                        <input type="submit" name="command" value="hide comment" class="dropdown-item ps-2">
                                                                    </li>
                                                                <?php endif ?>
                                                                <?php if($row_statements['visible'] == 0): ?>
                                                                    <li><input type="submit" name="command" value="unhide comment" class="dropdown-item"></li>
                                                                <?php endif ?>                                                                
                                                                <li class="d-flex align-items-center">
                                                                    <i class="fa-solid fa-trash-can ps-3"></i>
                                                                    <input type="submit" name="command" value="delete comment" class="dropdown-item ps-2" onclick="return confirm('Are you sure you wish to delete this comment?')">
                                                                </li>
                                                            </form>
                                                        </ul>
                                                    </div>
                                                <?php endif ?>

                                            </div>
                                            <p class="mb-0">
                                                <?php if($row_statements['visible'] == 1): ?>
                                                    <?= $row_statements['text'] ?>
                                                <?php endif ?>
                                                <?php if($row_statements['visible'] == 0): ?>
                                                    <p class="fw-lighter fst-italic mb-0 pb-0">[Comment hidden by admin]</p>
                                                <?php endif ?>
                                            </p>
                                        </div>

                                        <!-- Retrieve the current amount of likes for the comment -->
                                        <?php
                                            $commentId = $row_statements['comment_id'];
                                            $queryLikeCount = "SELECT * FROM likes WHERE comment_id=:commentId";
                                            $getLikeCount = $db->prepare($queryLikeCount);
                                            $getLikeCount->bindParam(':commentId', $commentId, PDO::PARAM_INT);
                                            $getLikeCount->execute();
                                            $commentLikeCount = $getLikeCount->rowCount();
                                        ?>

                                        <div class="d-flex align-items-center">
                                            <small class="me-3"><?= $elapsed_time ?></small>
                                            <!-- <small class="fw-semibold text-secondary me-3 text-sm m-0">Like</small> -->
                                            <button class="border-0 bg-white fw-semibold text-secondary text-sm me-3 m-0 comment-like-btn" data-user-id="<?= $session_user_id ?>" data-comment-id="<?= $row_statements['comment_id'] ?>"><small>Like</small></button>
                                            <button class="btn fw-semibold text-secondary border-0 py-0 px-0 me-3" data-bs-toggle="collapse" data-bs-target="#collapseReplyComment<?= $comment_id ?>" aria-expanded="false" aria-controls="collapseReply"><small>Reply</small></button>
                                            
                                            <!-- Like icon/counter -->
                                            <div class="d-flex mb-0 align-items-center">
                                                <button class="btn btn-primary border-0 rounded-circle me-1 d-flex justify-content-center align-items-center" style="width:20px; height:20px;" disabled>
                                                    <i class="fa-solid fa-thumbs-up fa-2xs"></i>
                                                </button>
                                                <small class="mb-0 text-secondary" id="likes-<?= $row_statements['comment_id'] ?>"><?= $commentLikeCount ?></small>
                                            </div>                                                

                                        </div>
                                    </div>
                                    
                                </div>

                                <!--- Reply Form --->
                                <div class="collapse" id="collapseReplyComment<?= $comment_id ?>">
                                    <div class="card card-body border-0">
                                        <form action="../../profile/<?= $row_id['id'] ?>/<?= $row_username ?>" method="post" class="d-flex align-items-center">
                                            <input type="hidden" name="comment_id" value="<?= $comment_id ?>">
                                            <textarea class="form-control mb-2 rounded-4" rows="3" name="replyText" placeholder="Write your reply..."></textarea>
                                            <button type="submit" class="btn btn-primary border-0 rounded-circle mx-2" style="width:40px; height:40px;"><i class="fa-solid fa-paper-plane align-self-center"></i></button>
                                            <!-- <button type="submit" class="btn btn-primary">Submit</button> -->
                                        </form>
                                    </div>
                                </div>

                                <?php while($row_replies = $statement_replies->fetch()): ?>

                                    <?php
                                        $id = $row_replies['id'];
                                        $query_reply_user = "SELECT * FROM users WHERE id=:id";
                                        $statement_reply_user = $db->prepare($query_reply_user);
                                        $statement_reply_user -> bindValue(':id', $id, PDO::PARAM_INT);
                                        $statement_reply_user->execute();
                                        $row_reply_user = $statement_reply_user->fetch();
                                        $reply_user_name = str_replace(' ', '-', $row_reply_user['username']);

                                        // Get the profile pic url
                                        $image_id = $row_reply_user['profile_image_id'];
                                        $query_replier_pic = "SELECT url from images WHERE image_id='$image_id'";
                                        $statement_replier_pic = $db->prepare($query_replier_pic);
                                        $statement_replier_pic->execute();

                                        if ($row_replier_pic = $statement_replier_pic->fetch())
                                        {
                                            $replier_pic_url = $row_replier_pic['url'];
                                        }
                                        else
                                        {
                                            $replier_pic_url = 'anonymous.jpg';
                                        }

                                        // Calculate the elapsed time since the reply was made
                                        date_default_timezone_set('Canada/Central');
                                        $current_time = time();
                                        $item_created_time = strtotime($row_statements['created_at']);
                                        $time_difference = $current_time - $item_created_time;

                                        // Convert the time difference to a human-readable format
                                        if ($time_difference < 60)
                                        {
                                            $elapsed_time = 'Just now';
                                        }
                                        elseif ($time_difference < 3600)
                                        {
                                            $elapsed_time = floor($time_difference / 60). 'm';
                                        }
                                        elseif ($time_difference < 86400)
                                        {
                                            $elapsed_time = floor($time_difference / 3600) . 'h';
                                        }
                                        else
                                        {
                                            $elapsed_time = floor($time_difference / 86400);
                                            if ($elapsed_time == 1)
                                            {
                                                $elapsed_time = $elapsed_time . 'd';
                                            }
                                            else
                                            {
                                                $elapsed_time = $elapsed_time . 'd';
                                            }
                                        }
                                    ?>

                                    <div class="d-flex ps-5 pe-2 mb-2">
                                        <div class="col-auto">
                                            <a href="../../profile/<?= $row_reply_user['id'] ?>/<?= $reply_user_name ?>"><img src="../../images/<?= $replier_pic_url ?>" class="border rounded-circle w-5 img-fluid me-2" style="max-width: 40px;"></a>
                                        </div>
                                        <div>
                                            <div class="bg-body-secondary shadow-sm rounded-4 p-2 mb-1">
                                                <div class="d-flex justify-content-between">
                                                    <a href="../../profile/<?= $row_reply_user['id'] ?>/<?= $reply_user_name ?>" class="text-decoration-none text-dark fw-bold"><?= $row_reply_user['username'] ?></a>

                                                    <?php if($session_user_role == 'admin'): ?>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm bg-body-secondary rounded-circle dropdown-toggle-no-arrow" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-ellipsis align-self-end"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <form action="../../editReply.php" method="post">
                                                                    <input type="hidden" name="id" value="<?= $row_id['id'] ?>">
                                                                    <input type="hidden" name="reply_id" value="<?= $row_replies['reply_id'] ?>">
                                                                    <?php if($row_replies['visible'] == 1): ?>
                                                                        <li><input type="submit" name="command" value="hide reply" class="dropdown-item"></li>
                                                                    <?php endif ?>
                                                                    <?php if($row_replies['visible'] == 0): ?>
                                                                        <li><input type="submit" name="command" value="unhide reply" class="dropdown-item"></li>
                                                                    <?php endif ?>                                                                
                                                                    <li><input type="submit" name="command" value="delete reply" class="dropdown-item" onclick="return confirm('Are you sure you wish to delete this reply?')"></li>
                                                                </form>
                                                            </ul>
                                                        </div>
                                                    <?php endif ?>



                                                </div>
                                                <p class="mb-0">
                                                    <?php if($row_replies['visible'] == 1): ?>
                                                        <?= $row_replies['text'] ?>
                                                    <?php endif ?>
                                                    <?php if($row_replies['visible'] == 0): ?>
                                                        <p class="fw-lighter fst-italic mb-0 pb-0">[Reply hidden by admin]</p>
                                                    <?php endif ?>
                                                </p>
                                            </div>
                                            <div class="d-flex">
                                                <small class="me-3 mb-1"><?= $elapsed_time ?></small>
                                                <button class="border-0 bg-white fw-semibold text-secondary text-sm me-3 m-0 reply-like-btn" data-user-id="<?= $session_user_id ?>" data-reply-id="<?= $row_replies['reply_id'] ?>"><small>Like</small></button>

                                                <!-- Retrieve the current amount of likes for the comment -->
                                                <?php
                                                    $replyId = $row_replies['reply_id'];
                                                    $queryReplyLikeCount = "SELECT * FROM likes WHERE reply_id=:replyId";
                                                    $getReplyLikeCount = $db->prepare($queryReplyLikeCount);
                                                    $getReplyLikeCount->bindParam(':replyId', $replyId, PDO::PARAM_INT);
                                                    $getReplyLikeCount->execute();
                                                    $replyLikeCount = $getReplyLikeCount->rowCount();
                                                ?>

                                                <!-- Like icon/counter -->
                                                <div class="d-flex mb-0 align-items-center">
                                                    <button class="btn btn-primary border-0 rounded-circle me-1 d-flex justify-content-center align-items-center" style="width:20px; height:20px;" disabled>
                                                        <i class="fa-solid fa-thumbs-up fa-2xs"></i>
                                                    </button>
                                                    <small class="mb-0 text-secondary" id="likes-<?= $row_replies['reply_id'] ?>"><?= $replyLikeCount ?></small>
                                                </div> 
                                            </div>
                                        </div>

                                    </div>
                                <?php endwhile ?>

        
                            <?php endwhile ?>


                        </div>
                    <?php endwhile ?>
                </div>

                <div class="card mb-6 p-3 rounded shadow-sm border-0 custom-card">
                    <div class="d-flex justify-content-center">
                        <p class="fw-bold h5">No more posts</p>
                    </div>                    
                </div>


            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
  </body>
</html>