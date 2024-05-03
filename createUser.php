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
        
        // Check if the session users has admin privilege
        if ($session_user['role'] == 'admin')
        {
            if (isset($_GET['id']))
            {
                $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
                $queryUser = "SELECT * FROM users WHERE id=:id";
                $statement_user = $db->prepare($queryUser);
                $statement_user->bindValue(':id', $id, PDO::PARAM_INT);
                $statement_user->execute();
                $row = $statement_user->fetch();
                $row_username = $row['username'];
            }

            // Create a new user account
            if (isset($_POST['username']) && 
                isset($_POST['email']) && 
                isset($_POST['password']) && 
                isset($_POST['retypePassword']) && 
                isset($_POST['role']) && 
                $_POST['command'] === 'Create User')
            {
                $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $retypePassword = filter_input(INPUT_POST, 'retypePassword', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

                // Check if the username or email already exists
                $queryExistingUser = "SELECT * FROM users WHERE username='$username'";
                $statement_existing_user = $db->prepare($queryExistingUser);
                $statement_existing_user -> execute();
                $count_existing_user = $statement_existing_user -> rowCount();

                $queryExistingEmail = "SELECT * FROM users WHERE email ='$email'";
                $statement_existing_email = $db->prepare($queryExistingEmail);
                $statement_existing_email -> execute();
                $count_existing_email = $statement_existing_email -> rowCount();

                if ($count_existing_user == 0 && $count_existing_email == 0)
                {
                    if ($password == $retypePassword)
                    {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $queryCreateUser = "INSERT INTO users (username,email,password,role) VALUES (:username, :email, :password, :role)";
                        $statement_create_user = $db->prepare($queryCreateUser);
                        $statement_create_user->bindValue(':username', $username);
                        $statement_create_user->bindValue(':email', $email);
                        $statement_create_user->bindValue(':password', $hash);
                        $statement_create_user->bindValue(':role', $role);

                        if ($statement_create_user -> execute())
                        {
                            header("Location:../../manageUsers/$session_user_id/$session_username");
                            exit;
                        }
                    }
                    else
                    {
                        echo '<script>
                        window.location.href="../../createUser/' . $session_user_id . '/' . $session_username . '";
                        alert("Passwords do not match.");
                        </script>';
                    }
                }
                else
                {
                    if ($count_existing_user > 0)
                    {
                        echo '<script>
                            //window.location.href="createUser.php?id=' .$session_user_id . '";
                            window.location.href="../../createUser/' . $session_user_id . '/' . $session_username . '";
                            alert("Username already exists");
                        </script>';
                    }
                    if ($count_existing_email > 0)
                    {
                        echo '<script>
                            //window.location.href="createUser.php?id=' .$session_user_id . '";
                            window.location.href="../../createUser/' . $session_user_id . '/' . $session_username . '";
                            alert("Email already exists");
                        </script>';
                    } 
                }
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
    <title>Create New User</title>
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

        <form action="../../createUser/<?= $row['id'] ?>/<?= $session_username ?>" class="w-m-50" method="post">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <div class="form-group mb-3">
                <label for="username">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Username">
            </div>
            <div class="form-group mb-3">
                <label for="email">Email</label>
                <input type="email" name="email" class="form-control" placeholder="Email address">
            </div>
            <div class="form-group mb-3">
                <label for="password">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Password">
            </div>
            <div class="form-group mb-3">
                <label for="retypePassword">Password</label>
                <input type="password" name="retypePassword" class="form-control" placeholder="Retype password">
            </div>
            <div class="form-group mb-4">
                <label for="role">Role</label>
                <select name="role" class="form-select w-m-25">
                    <option value="user">user</option>
                    <option value="admin">admin</option>
                    <option value="guest">guest</option>
                </select>
            </div>
            <div class="form-group mb-5">
                <input type="submit" name="command" value="Create User" class="btn btn-primary" onclick="return confirm('Are you sure you wish to create this user?')">
            </div>
        </form>

    </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>