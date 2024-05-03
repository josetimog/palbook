<?php
    session_start();
    require('connection.php');
    if ($_POST && $_POST['user'] && $_POST['email'] && $_POST['pass'] && $_POST['retypePass'] && isset($_POST['submit']))
    {
        $username = filter_input(INPUT_POST, 'user', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $password = filter_input(INPUT_POST, 'pass', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $retypePass = filter_input(INPUT_POST, 'retypePass', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $query = "SELECT * FROM users WHERE username='$username'";
        $statement = $db->prepare($query);
        $statement->execute();
        $count_user = $statement->rowCount();

        $query = "SELECT * FROM users WHERE email='$email'";
        $statement = $db->prepare($query);
        $statement->execute();
        $count_email = $statement->rowCount();

        if ($count_user == 0 && $count_email == 0)
        {
            if ($password == $retypePass)
            {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO users(username, email, password, profile_image_id) VALUES('$username', '$email', '$hash', 1)";
                $statement = $db->prepare($query);
                
                if($statement->execute())
                {
                    $query = "SELECT * FROM users WHERE username = :username";
                    $statement = $db->prepare($query);
                    $statement->bindValue(':username', $username);
                    $statement->execute();
                    $row = $statement -> fetch();
                    $id = $row['id'];
                    $_SESSION['user'] = $username;
                    $username = str_replace(' ', '-', $username);
                    header("Location:profile/$id/$username");
                }
            }
            else
            {
                echo '<script>
                    alert("Passwords do not match.");
                    window.location.href = "signup.php"
                </script>';
            }
        }
        else
        {
            if ($count_user > 0)
            {
                echo '<script>
                    window.location.href="signup.php";
                    alert("Username already exists");
                </script>';
            }
            if ($count_email > 0)
            {
                echo '<script>
                    window.location.href="signup.php";
                    alert("Email already exists");
                </script>';
            } 
        }
    }

?>