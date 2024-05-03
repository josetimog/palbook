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
        
        // Check if the session users has admin privilege
        if ($session_user['role'] == 'admin')
        {
            if ($_POST && isset($_POST['sortAllUsers']))
            {
                $sortColumn = $_POST['sortAllUsers'];
                $queryAllUsers = "SELECT * FROM users ORDER BY $sortColumn";
                $statement_all_users = $db->prepare($queryAllUsers);
                $statement_all_users->execute();
            }
            else
            {
                $queryAllUsers = "SELECT * FROM users";
                $statement_all_users = $db->prepare($queryAllUsers);
                $statement_all_users->execute();
            }
        }
        else
        {
            echo "<script>location.href='index.php'</script>";
        }

    }

?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Palbook Manage Users</title>
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
                        <a class="dropdown-item" href="../../editProfile/<?= $session_user_id ?>/<?= $session_username ?>">Edit Profile</a>
                        <?php if($session_user_role == 'admin'): ?>
                            <a class="dropdown-item active" href="../../editProfile/<?= $session_user_id ?>/<?= $session_username ?>">Manage Users</a>
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


    <!-- class="d-flex mb-3"  -->
    <div class="container">

        <form action="../../manageUsers/<?= $session_user_id ?>/<?= $session_username ?>" method="post" >
            <div class="row w-75 mb-3">
                
                <div class="col-2 d-flex justify-content-end align-items-center p-0">
                    <label class="me-2" for="sortAllUsers">Sort By:</label>
                </div>
                
                <div class="col-2 p-0 d-flex align-items-center">
                    <select name="sortAllUsers" id="sortAllUsers" class="form-select me-2">
                        <option value="id">id</option>
                        <option value="username">username</option>
                        <option value="email">email</option>
                        <option value="register_date">register_date</option>
                        <option value="role">role</option>
                    </select>                  
                </div>
                
                <div class="col-1 d-flex align-items-center">
                    <button type="submit" class="btn btn-primary">Sort</button>
                </div>
                
                <div class="col-3 d-flex justify-content-end">
                    <a class="btn btn-primary" href="../../createUser/<?= $session_user_id ?>/<?= $session_username ?>">Create User</a>
                </div>

            </div>
            
        </form>

        <table class="table">
            <thead>
                <tr>
                    <th scope="col"></th>
                    <th scope="col">id</th>
                    <th scope="col">username</th>
                    <th scope="col">email</th>
                    <th scope="col">password</th>
                    <th scope="col">register_date</th>
                    <th scope="col">role</th>
                </tr>
            </thead>
            <tbody>
                <?php while($allUsers = $statement_all_users->fetch()): ?>
                    <?php $allUsersName =  str_replace(' ', '-', $allUsers['username'] ) ?>
                    <tr>
                        <td><a href="../../editUser/<?= $allUsers['id'] ?>/<?= $allUsersName ?>">Edit</a></td>
                        <td><?= $allUsers['id'] ?></td>
                        <td><?= $allUsers['username'] ?></td>
                        <td><?= $allUsers['email'] ?></td>
                        <td><?= $allUsers['password'] ?></td>
                        <td><?= $allUsers['register_date'] ?></td>
                        <td><?= $allUsers['role'] ?></td>
                    </tr>
                <?php endwhile ?>
            </tbody>
        </table>
    </div>
  

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>