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

        // Retrieve the users from the search field
        $searchUser = filter_input(INPUT_POST, 'searchTerm', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $querySearchUser = "SELECT * FROM users WHERE username LIKE :searchUser";
        $statement_search_user = $db->prepare($querySearchUser);
        $statement_search_user->bindValue(':searchUser', $searchUser . '%');
        $statement_search_user->execute();

        // Sorting
        if ($_POST && isset($_POST['sortSearchResults']))
        {
            $searchUser = filter_input(INPUT_POST, 'searchTerm', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $sortColumn = $_POST['sortSearchResults'];
            $querySearchUser = "SELECT * FROM users WHERE username LIKE :searchUser
                                ORDER BY $sortColumn";
            $statement_search_user = $db->prepare($querySearchUser);
            $statement_search_user->bindValue(':searchUser', $searchUser . '%');
            $statement_search_user->execute();            
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
    <title>Search Results</title>
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


    <div class="container">

        <div class="row">

            <div class="col">
                <div>
                    <p class="mb-3 fs-5">Search Results</p>
                </div>
                <?php if($statement_search_user->rowCount() == 0): ?>
                    <p>No results found</p>
                <?php else: ?>

                <!-- Sorting -->
                <form action="../../search/<?= $session_user_id ?>/<?= $session_username ?>" method="post" > 
                    <div class="row mb-3">
                        <input type="hidden" name="searchTerm" value="<?= $searchUser ?>">
                        <div class="col-4 d-flex justify-content-end align-items-center p-0">
                            <label class="me-2" for="sortSearchResults">Sort By:</label>
                        </div>
                        
                        <div class="col-4 p-0 d-flex align-items-center">
                            <select name="sortSearchResults" id="sortSearchResults" class="form-select me-2">
                                <option value="username">username</option>
                                <option value="location">location</option>
                            </select>                  
                        </div>
                        
                        <div class="col-4 d-flex align-items-center">
                            <button type="submit" class="btn btn-primary">Sort</button>
                        </div>
                    </div>
                </form>

                <?php while($userSearchResult = $statement_search_user->fetch()): ?>
                    <?php
                        // Retrieve friend's profile pic url
                        $image_id = $userSearchResult['profile_image_id'];
                        $query_profile_pic = "SELECT url from images WHERE image_id='$image_id'";
                        $statement_profile_pic = $db->prepare($query_profile_pic);
                        $statement_profile_pic->execute();

                        if ($statement_profile_pic = $statement_profile_pic->fetch())
                        {
                            $user_pic_url = $statement_profile_pic['url'];
                        }
                        else
                        {
                            $user_pic_url = 'anonymous.jpg';
                        }

                        // $statement_profile_pic = $statement_profile_pic->fetch();
                        // $user_pic_url = $statement_profile_pic['url'];

                        // Convert space to dash in username
                        $userSearchResultName = str_replace(' ', '-', $userSearchResult['username']);
                    ?>

                    

                    <div class="card rounded p-3 mb-3 p-3 bg-white">
                        <div class="d-flex">
                            <a href="../../profile/<?= $userSearchResult['id'] ?>/<?= $userSearchResultName ?>"><img src="../../images/<?= $user_pic_url ?>" class="border rounded-circle image-fluid me-3" style="max-width: 80px;"></a>
                            <div class="d-flex flex-column">
                                <a href="../../profile/<?= $userSearchResult['id'] ?>/<?= $userSearchResultName ?>" class="text-decoration-none text-dark fw-bold mb-1"><?= $userSearchResult['username'] ?></a>
                                <!-- <small class="mb-2">Lives in <?= $userSearchResult['location'] ?></small> -->
                                <?php if($userSearchResult['location'] != null): ?>
                                    <small class="mb-2">Lives in <?= $userSearchResult['location'] ?></small>
                                <?php endif ?>

                                <?php $friendTest = 1 ?>

                                <?php
                                    $friendToTest = $userSearchResult['id'];
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

                                <?php if($friendTest == 0): ?>
                                    <!-- Add Friend -->
                                    <form action="../../friends/<?= $session_user_id?>/<?= $session_username ?>" method="post">
                                        <input type="hidden" name="requester" value="<?= $session_user_id ?>">
                                        <input type="hidden" name="requestee" value="<?= $userSearchResult['id']?>">
                                        <input type="submit" name="command" class="btn btn-primary mt-1" value="Add friend">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>