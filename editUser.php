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
                $row_username = str_replace(' ', '-', $row['username']);
            }

            // Update the user information
            if (isset($_POST['id']) && 
                isset($_POST['username']) && 
                isset($_POST['email']) && 
                isset($_POST['role']) &&
                $_POST['command'] === 'Update')
            {
                // Sanitize user input to escape HTML entities and filter out dangerous characters.
                $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
                $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                
                // Build the parameterized SQL query and bind to the above sanitized values.
                $queryUpdateUser = "UPDATE users SET username=:username, email=:email, role=:role WHERE id=:id";
                $statement_update_user = $db->prepare($queryUpdateUser);
                $statement_update_user->bindValue(':id', $id);
                $statement_update_user->bindValue(':username', $username);
                $statement_update_user->bindValue(':email', $email);
                $statement_update_user->bindValue(':role', $role);
                $statement_update_user->execute();

                // Redirect after the update
                header("Location:../../manageUsers/$session_user_id/$session_username");
                exit;
            }
            else if ($_POST && isset($_POST['id']) && $_POST['command'] === 'Delete User')
            {
                $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
                $queryDeleteUser = "DELETE FROM users WHERE id = :id LIMIT 1";
                $statement_delete_user = $db->prepare($queryDeleteUser);
                $statement_delete_user->bindValue(':id', $id, PDO::PARAM_INT);
                $statement_delete_user->execute();        

                // Redirect after DELETE
                header("Location:../../manageUsers/$session_user_id/$session_username");
                exit;
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
    <link rel="stylesheet" type="text/css" href="style.css">
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

        <h1 class="fs-5">Current User Data</h1>
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">id</th>
                    <th scope="col">username</th>
                    <th scope="col">email</th>
                    <th scope="col">password</th>
                    <th scope="col">register_date</th>
                    <th scope="col">role</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['username'] ?></td>
                    <td><?= $row['email'] ?></td>
                    <td><?= $row['password'] ?></td>
                    <td><?= $row['register_date'] ?></td>
                    <td><?= $row['role'] ?></td>
                </tr>
            </tbody>
        </table>

        <form action="../../editUser/<?= $row['id'] ?>/<?= $row_username ?>" class="w-m-50" method="post">
            <input type="hidden" name="id" value="<?= $row['id'] ?>">
            <div class="form-group mb-3">
                <label for="username">Username</label>
                <input type="text" name="username" class="form-control" value="<?=$row['username']?>">
            </div>
            <div class="form-group mb-3">
                <label for="email">Email</label>
                <input type="email" name="email" class="form-control" value="<?= $row['email'] ?>">
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
                <input type="submit" name="command" value="Update" class="btn btn-primary">
            </div>
            <div class="form-group mb-5">
                <input type="submit" name="command" value="Delete User" class="btn btn-warning" onclick="return confirm('Are you sure you wish to delete this user account?')">
            </div>
        </form>

    </div>
  

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>