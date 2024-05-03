<?php
    require('connection.php');
    session_start();
    //print_r($_POST);
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
            $row_username = str_replace(' ', '-', $row['username']);
            $row_id = $row['id'];
        }

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

        if ($_POST && isset($_POST['requester']) && isset($_POST['requestee']) && $_POST['command'] === 'Accept')
        {
            $first_user_id = filter_input(INPUT_POST, 'requester', FILTER_SANITIZE_NUMBER_INT);
            $second_user_id = filter_input(INPUT_POST, 'requestee', FILTER_SANITIZE_NUMBER_INT);
            $queryAcceptRequest = "UPDATE friendships
                                   SET type='friends'
                                   WHERE (first_id=:first_user_id AND second_id=:second_user_id) OR
                                         (first_id=:second_user_id AND second_id=:first_user_id)";
            $statement_accept_request = $db->prepare($queryAcceptRequest);
            $statement_accept_request->bindValue(':first_user_id', $first_user_id);
            $statement_accept_request->bindValue(':second_user_id', $second_user_id);
            $statement_accept_request->execute();
        }

        if ($_POST && 
            isset($_POST['requester']) && 
            isset($_POST['requestee']) && 
            ($_POST['command'] === 'Decline' || $_POST['command'] === 'Cancel' ))
        {
            $requester_id = filter_input(INPUT_POST, 'requester', FILTER_SANITIZE_NUMBER_INT);
            $requestee_id = filter_input(INPUT_POST, 'requestee', FILTER_SANITIZE_NUMBER_INT);

            $queryDeclineRequest = "DELETE FROM friendships
                                    WHERE ((first_id=:requester_id AND second_id=:requestee_id AND type='pending_first_second') OR
                                         (first_id=:requestee_id AND second_id=:requester_id AND type='pending_second_first'))";
            $statement_decline_request = $db->prepare($queryDeclineRequest);
            $statement_decline_request->bindValue(':requester_id', $requester_id);
            $statement_decline_request->bindValue(':requestee_id', $requestee_id);
            $statement_decline_request->execute();
        }
        
        // Retrive the incoming friend requests
        $queryIncomingRequests = "SELECT 
                                    CASE
                                        WHEN first_id ='$session_user_id' THEN second_id
                                        ELSE first_id
                                    END AS friend_user_id
                                FROM friendships
                                WHERE (first_id='$session_user_id' AND type='pending_second_first') OR 
                                      (second_id='$session_user_id' AND type='pending_first_second')";
        $statement_incoming_requests = $db->prepare($queryIncomingRequests);
        $statement_incoming_requests->execute();
        // $row_incoming = $statement_incoming_requests->fetch();
        // print_r($row_incoming);

        // Retrieve the outgoing friend requests
        $queryOutgoingRequests = "SELECT 
                                    CASE
                                        WHEN first_id ='$session_user_id' THEN second_id
                                        ELSE first_id
                                    END AS friend_user_id
                                FROM friendships
                                WHERE (first_id='$session_user_id' AND type='pending_first_second') OR 
                                      (second_id='$session_user_id' AND type='pending_second_first')";
        $statement_outgoing_requests = $db->prepare($queryOutgoingRequests);
        $statement_outgoing_requests->execute();


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
    <title>Friend Requests</title>
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
                    <a href="../../profile/<?= $session_user_id ?>/<?= $session_username ?>" class="nav-link" id="navHome">Home</a>
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
                            <p class="bg-body-secondary rounded-pill mb-0 me-3 px-3 py-2 text-dark fw-bolder">Friend requests</p>
                        <?php endif ?>
                        <a href="../../friends/<?= $row_id ?>/<?= $row_username ?>" class="rounded-pill bg-white border-none me-3 p-2 text-decoration-none text-muted fw-bolder">All friends</a> 
                    </div>

                    <p class="fw-bold mb-3 h5">Incoming Requests</p>

                    <?php if($statement_incoming_requests->rowCount() == 0): ?>
                        <p>No incoming friend requests</p>
                    <?php else: ?>

                        <!-- Incoming Request Cards -->
                        <?php while($allFriendRequests = $statement_incoming_requests->fetch()): ?>                     
                            <?php
                                $queryRequester = "SELECT * FROM users WHERE id='".$allFriendRequests['friend_user_id']."'";
                                $statement_requester = $db->prepare($queryRequester);
                                $statement_requester->execute();
                                $statement_requester_row = $statement_requester->fetch();
                                $requester_username = str_replace(' ', '-', $statement_requester_row['username']);

                                // Retrieve requester's profile pic url
                                $image_id = $statement_requester_row['profile_image_id'];
                                $query_requester_pic = "SELECT url from images WHERE image_id='$image_id'";
                                $statement_requester_pic = $db->prepare($query_requester_pic);
                                $statement_requester_pic->execute();

                                if ($statement_requester_pic = $statement_requester_pic->fetch())
                                {
                                    $requester_pic_url = $statement_requester_pic['url'];
                                }
                                else
                                {
                                    $requester_pic_url = 'anonymous.jpg';
                                }

                                // $statement_requester_pic = $statement_requester_pic->fetch();
                                // $requester_pic_url = $statement_requester_pic['url'];
                            ?>

                            <div class="card rounded p-3 mb-3 bg-white">
                                <div class="d-flex">
                                    <a href="../../profile/<?= $statement_requester_row['id'] ?>/<?= $requester_username ?>"><img src="../../images/<?= $requester_pic_url ?>" class="border rounded-circle image-fluid me-3" style="max-width: 80px;"></a>
                                    <div class="d-flex flex-column">
                                        <a href="../../profile/<?= $statement_requester_row['id'] ?>/<?= $requester_username ?>" class="text-decoration-none text-dark fw-bold mb-1"><?= $statement_requester_row['username'] ?></a>
                                        <!-- <small class="mb-2">Lives in <?= $statement_requester_row['location'] ?></small> -->
                                        <?php if($statement_requester_row['location'] != null): ?>
                                            <small class="mb-2">Lives in <?= $statement_requester_row['location'] ?></small>
                                        <?php endif ?>

                                        <?php if($session_user_id == $row_id): ?>
                                            <form action="../../friendRequests/<?= $session_user_id?>/<?= $session_username ?>" method="post">
                                                <input type="hidden" name="requestee" value="<?= $session_user_id ?>">
                                                <input type="hidden" name="requester" value="<?= $statement_requester_row['id']?>">
                                                <input type="submit" name="command" class="btn btn-primary pt-1 me-3" value="Accept">
                                                <input type="submit" name="command" class="btn btn-warning pt-1" value="Decline">
                                            </form>                                          
                                        <?php endif ?>

                                    </div>
                                </div>
                            </div>
                        <?php endwhile ?>
                    <?php endif ?>
                    

                    <p class="fw-bold mb-3 h5">Sent Requests</p>

                    <?php if($statement_outgoing_requests->rowCount() == 0): ?>
                        <p>No friend requests sent</p>
                    <?php else: ?>

                        <!-- Outgoing Request Cards -->
                        <?php while($allSentRequests = $statement_outgoing_requests->fetch()): ?>                     
                            <?php
                                $queryRequestee = "SELECT * FROM users WHERE id='".$allSentRequests['friend_user_id']."'";
                                $statement_requestee = $db->prepare($queryRequestee);
                                $statement_requestee->execute();
                                $statement_requestee_row = $statement_requestee->fetch();
                                $requestee_username = str_replace(' ', '-', $statement_requestee_row['username']);

                                // Retrieve friend's profile pic url
                                $image_id = $statement_requestee_row['profile_image_id'];
                                $query_requestee_pic = "SELECT url from images WHERE image_id='$image_id'";
                                $statement_requestee_pic = $db->prepare($query_requestee_pic);
                                $statement_requestee_pic->execute();

                                if ($statement_requestee_pic = $statement_requestee_pic->fetch())
                                {
                                    $requestee_pic_url = $statement_requestee_pic['url'];
                                }
                                else
                                {
                                    $requestee_pic_url = 'anonymous.jpg';
                                }

                                // $statement_requestee_pic = $statement_requestee_pic->fetch();
                                // $requestee_pic_url = $statement_requestee_pic['url'];
                            ?>

                            <div class="card rounded p-3 mb-3 p-3 bg-white">
                                <div class="d-flex">
                                    <a href="../../profile/<?= $statement_requestee_row['id'] ?>/<?= $requestee_username ?>"><img src="../../images/<?= $requestee_pic_url ?>" class="border rounded-circle image-fluid me-3" style="max-width: 80px;"></a>
                                    <div class="d-flex flex-column">
                                        <a href="../../profile/<?= $statement_requestee_row['id'] ?>/<?= $requestee_username ?>" class="text-decoration-none text-dark fw-bold mb-1"><?= $statement_requestee_row['username'] ?></a>
                                        <?php if($statement_requestee_row['location'] != null): ?>
                                            <small class="mb-2">Lives in <?= $statement_requestee_row['location'] ?></small>
                                        <?php endif ?>

                                        <?php if($session_user_id == $row_id): ?>
                                            <form action="../../friendRequests/<?= $session_user_id?>/<?= $session_username ?>" method="post">
                                                <input type="hidden" name="requestee"  value="<?= $statement_requestee_row['id']?>">
                                                <input type="hidden" name="requester" value="<?= $session_user_id ?>">
                                                <input type="submit" name="command" class="btn btn-primary pt-1" value="Cancel">
                                            </form>                                          
                                        <?php endif ?>
                                        
                                    </div>
                                </div>
                            </div>
                        <?php endwhile ?>
                    <?php endif ?>                 



                </div>
                </div>
            </div>    
        </div>
    </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>