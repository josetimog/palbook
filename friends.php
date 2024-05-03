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

        if (isset($_GET['id']))
        {
            $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            $queryUser = "SELECT * FROM users WHERE id=:id";
            $statement_user = $db->prepare($queryUser);
            $statement_user->bindValue(':id', $id, PDO::PARAM_INT);
            $statement_user->execute();
            $row = $statement_user->fetch();
            $row_username = $row['username'];
            $row_id = $row['id'];
        }

        // Get the friend count
        $queryFriends = "SELECT * FROM friendships WHERE (first_id='$row_id' OR second_id='$row_id') AND type='friends'";
        $statement_friends = $db->prepare($queryFriends);
        $statement_friends->execute();
        $friendCount = $statement_friends->rowCount();

        if ($_POST && isset($_POST['remover']) && isset($_POST['removee']) && $_POST['command'] === 'Remove friend')
        {
            $first_user_id = filter_input(INPUT_POST, 'remover', FILTER_SANITIZE_NUMBER_INT);
            $second_user_id = filter_input(INPUT_POST, 'removee', FILTER_SANITIZE_NUMBER_INT);
            $queryRemoveFriend = "DELETE FROM friendships
                                  WHERE ((first_id=:first_user_id && second_id=:second_user_id) ||
                                         (first_id=:second_user_id && second_id=:first_user_id))
                                  AND type='friends'";
            $statement_remove_friend = $db->prepare($queryRemoveFriend);
            $statement_remove_friend->bindValue(':first_user_id', $first_user_id);
            $statement_remove_friend->bindValue(':second_user_id', $second_user_id);
            $statement_remove_friend->execute();
        }

        if ($_POST && isset($_POST['requester']) && isset($_POST['requestee']) && $_POST['command'] === 'Add friend')
        {
            $requester_id = filter_input(INPUT_POST, 'requester', FILTER_SANITIZE_NUMBER_INT);
            $requestee_id = filter_input(INPUT_POST, 'requestee', FILTER_SANITIZE_NUMBER_INT);

            if ($requester_id < $requestee_id)
            {
                $querySendRequest = "INSERT INTO friendships (first_id, second_id, type) VALUES (:requester_id, :requestee_id, 'pending_first_second')";
                $statement_send_request = $db->prepare($querySendRequest);
                $statement_send_request->bindValue(':requester_id', $requester_id);
                $statement_send_request->bindValue(':requestee_id', $requestee_id);
                $statement_send_request->execute();
            }

            if ($requester_id > $requestee_id)
            {
                $querySendRequest = "INSERT INTO friendships (first_id, second_id, type) VALUES (:requestee_id, :requester_id, 'pending_second_first')";
                $statement_send_request = $db->prepare($querySendRequest);
                $statement_send_request->bindValue(':requester_id', $requester_id);
                $statement_send_request->bindValue(':requestee_id', $requestee_id);
                $statement_send_request->execute();                
            }

        }
        
        // Get all friends of user id
        $queryAllFriendsId = "SELECT 
                                    CASE
                                        WHEN first_id ='$row_id' THEN second_id
                                        ELSE first_id
                                    END AS friend_user_id
                                FROM friendships
                                WHERE (first_id='$row_id' OR second_id='$row_id') AND type='friends'";
        $statement_all_friends_id = $db->prepare($queryAllFriendsId);
        $statement_all_friends_id->execute();

        // Sort the retrieved list of friends
        if ($_POST && isset($_POST['sortFriends']))
        {
            $sortColumn = $_POST['sortFriends'];
            $queryAllFriendsId = "SELECT 
                                    CASE
                                        WHEN first_id ='$row_id' THEN second_id
                                        ELSE first_id
                                    END AS friend_user_id
                                FROM friendships
                                WHERE (first_id='$row_id' OR second_id='$row_id') AND type='friends'
                                ORDER BY (SELECT $sortColumn FROM users WHERE id = friend_user_id)";
            $statement_all_friends_id = $db->prepare($queryAllFriendsId);
            $statement_all_friends_id->execute();            
        }
    }
    else
    {
        echo "<script>location.href='index.php'</script>";
    }

?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Friends</title>
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
                    <a href="../../profile/<?= $session_user_id ?>/<?= $session_username ?>" class=" nav-link" id="navHome">Home</a>
                </li>
                <li class="nav-item">
                    <a href="../../friends/<?= $session_user_id ?>/<?= $session_username ?>" class="nav-link active">Friends</a>
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



    <div class="container container-fluid">
    
        <div class="row">


                <div class="col px-0 mb-3">
                    <div class="card rounded bg-white shadow-sm border-0 px-xs-0 p-3 pt-1">
                        <div class="d-flex border-bottom py-2 mb-3">
                            <?php if($session_user_id == $row['id']): ?>
                                <a href="../../friendRequests/<?= $session_user_id ?>/<?= $session_username ?>" class="rounded-pill bg-white border-none me-3 p-2 text-decoration-none text-muted fw-bolder">Friend requests</a> 
                            <?php endif ?>
                            <p class="bg-body-secondary rounded-pill mb-0 me-3 px-3 py-2 text-dark fw-bolder">All friends</p>
                        </div>

                        <?php if($statement_all_friends_id->rowCount() == 0): ?>
                            <p>No friends to show</p>
                        <?php else: ?>
                            <!-- Sorting -->
                            <form action="../../friends/<?= $row_id ?>/<?= $row_username ?>" method="post" > 
                                <div class="row mb-3">
                                    <div class="col-4 d-flex justify-content-end align-items-center p-0">
                                        <label class="me-2" for="sortFriends">Sort By:</label>
                                    </div>
                                    
                                    <div class="col-4 p-0 d-flex align-items-center">
                                        <select name="sortFriends" id="sortFriends" class="form-select me-2">
                                            <option value="username">username</option>
                                            <option value="location">location</option>
                                        </select>                  
                                    </div>
                                    
                                    <div class="col-4 d-flex align-items-center">
                                        <button type="submit" class="btn btn-primary">Sort</button>
                                    </div>
                                </div>
                            </form>

                            <!-- Friend count -->
                            <?php if($friendCount == 1): ?>
                                <p class="fw-bold mb-3 h5"><?= $friendCount ?> Friend</p>
                            <?php else: ?>
                                <p class="fw-bold mb-3 h5"><?= $friendCount ?> Friends</p>
                            <?php endif ?>

                            <!-- Friend Cards -->
                            <?php while($allFriendsRowId = $statement_all_friends_id->fetch()): ?>                     
                                <?php
                                    $queryFriend = "SELECT * FROM users WHERE id='".$allFriendsRowId['friend_user_id']."'";
                                    $statement_friend = $db->prepare($queryFriend);
                                    $statement_friend->execute();
                                    $statement_friend_row = $statement_friend->fetch();
                                    $statement_friend_row_name = str_replace(' ', '-', $statement_friend_row['username']);

                                    // Retrieve friend's profile pic url
                                    $image_id = $statement_friend_row['profile_image_id'];
                                    $query_friend_pic = "SELECT url from images WHERE image_id='$image_id'";
                                    $statement_friend_pic = $db->prepare($query_friend_pic);
                                    $statement_friend_pic->execute();

                                    if ($statement_friend_pic = $statement_friend_pic->fetch())
                                    {
                                        $friend_pic_url = $statement_friend_pic['url'];
                                    }
                                    else
                                    {
                                        $friend_pic_url = 'anonymous.jpg';
                                    }

                                ?>

                                <div class="card rounded p-3 mb-3 p-3 bg-white">
                                    <div class="d-flex">
                                        <a href="../../profile/<?= $statement_friend_row['id'] ?>/<?= $statement_friend_row_name ?>"><img src="../../images/<?= $friend_pic_url ?>" class="border rounded-circle image-fluid me-3" style="max-width: 80px;"></a>
                                        <div class="d-flex flex-column">
                                            <a href="../../profile/<?= $statement_friend_row['id'] ?>/<?= $statement_friend_row_name ?>" class="text-decoration-none text-dark fw-bold mb-1"><?= $statement_friend_row['username'] ?></a>
                                            <?php if($statement_friend_row['location'] != null): ?>
                                                <small class="mb-2">Lives in <?= $statement_friend_row['location'] ?></small>
                                            <?php endif ?>
                                            
                                            <?php $friendTest = 1 ?>
                                            <?php if($session_user_id == $row_id): ?>
                                                
                                                <!-- Remove Friend -->
                                                <form action="../../friends/<?= $session_user_id ?>/<?= $session_username ?>" method="post">
                                                    <input type="hidden" name="remover" value="<?= $session_user_id ?>">
                                                    <input type="hidden" name="removee" value="<?= $statement_friend_row['id']?>">
                                                    <input type="submit" name="command" class="btn btn-primary mt-1" value="Remove friend" onclick="return confirm('Are you sure you wish to remove this friend?')">
                                                </form>
                                                <!--<button class="btn btn-primary">Remove friend</button>-->
                                            <?php else: ?>
                                                <?php
                                                    $friendToTest = $statement_friend_row['id'];
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
                                            <?php endif ?>
                                            
                                            <?php if($friendTest == 0): ?>
                                                <!-- Add Friend -->
                                                <form action="../../friends/<?= $session_user_id ?>/<?= $session_username ?>" method="post">
                                                    <input type="hidden" name="requester" value="<?= $session_user_id ?>">
                                                    <input type="hidden" name="requestee" value="<?= $statement_friend_row['id']?>">
                                                    <input type="submit" name="command" class="btn btn-primary" value="Add friend">
                                                </form>
                                            <?php endif ?>

                                        </div>
                                    </div>
                                </div>
                            <?php endwhile ?>
                        <?php endif ?>

                    </div>
                </div>




                   

                                    
                <!-- </div> -->



        </div>
        
    </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>