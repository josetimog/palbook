<?php
    require('connection.php');
    session_start();

    //print_r($_SESSION['user']);
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

        header("Location:profile/$session_user_id/$session_username");
        exit;
    }

    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Palbook</title>
</head>
<body>

    <main>
        <section id="loginSection">
            <div class="row">
                <div class="column" id="loginLogo">
                    <h2>Palbook</h2>
                </div>
                <div class="column" id="formAndNewAccount">
                    <form id="loginform" action="login.php" onsubmit="return isValid()" method="POST">
                        <input type="text" id="user" name="user" placeholder="Username">
                        <input type="password" id="pass" name="pass" placeholder="Password">
                        <input type="submit" id="btnLogin" value="Log in" name="submit">
                    </form>
                    <div id="createAccountDiv">
                        <a href="signup.php" id="btnCreateAccount">Create new account</a>
                    </div>
                </div>
            </div>
        </section>
    </main>
    

    <script>
        function isValid()
        {
            let user = document.form.user.value;
            let pass = document.form.pass.value;
            if (user.length == "" && pass.length == "")
            {
                alert("Username and password field is empty!!!");
                return false;
            }
            else
            {
                if (user.length == "")
                {
                    alert("Username is empty!!!");
                    return false;
                }

                if (pass.length == "")
                {
                    alert("Password is empty!!!");
                    return false;
                }
            }
        }
    </script>
</body>
</html>