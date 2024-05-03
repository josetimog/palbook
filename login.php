<?php
    
    require('connection.php');
    session_start();

    if($_POST && $_POST['user'] && $_POST['pass']  && isset($_POST['submit']))
    {
        $username = filter_input(INPUT_POST, 'user', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $password = filter_input(INPUT_POST, 'pass', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $query = "SELECT * FROM users WHERE username = :username";
        $statement = $db->prepare($query);
        $statement->bindParam(':username', $username);
        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row)
        {
            $id = $row['id'];
            $username = $row['username'];

            $hashed_password = $row['password'];

            if (password_verify($password, $hashed_password))
            {
                if (isset($_SESSION['user']))
                {
                    $username = str_replace(' ', '-', $username);
                    header("Location:profile/$id/$username");
                    exit;
                }
                else
                {
                    if ($_POST['user'] == $username && $_POST['pass'] == $password)
                    {
                        $_SESSION['user'] = $username;
                        $username = str_replace(' ', '-', $username);
                        header("Location:profile/$id/$username");
                        exit;
                    }
                    else
                    {
                        echo "<script>alert('Please sign in')</script>";
                        echo "<script>location.href='index.php'</script>";
                    }
                }

            }
            else
            {
                echo '<script>
                  window.location.href = "index.php";
                  alert("Login failed. Invalid username or password.")
              </script>';
              exit;
            }
        }
        else
        {
            echo '<script>
                window.location.href = "index.php";
                alert("Login failed. Invalid username or password.")
            </script>';
        }
    }
    else
    {
        echo '<script>
            window.location.href = "index.php";
            alert("Please sign in")
        </script>';
    }

    

?>