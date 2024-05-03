<?php
    require('connection.php');
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
                    <h1>Sign up</h1>
                    <p>It's quick and easy.</p><br>
                    <form id="signupform" action="signupProcess.php" onsubmit="return isValid()" method="POST">
                        <input type="text" id="user" name="user" placeholder="Username">
                        <input type="email" id="email" name="email" placeholder="Email address">
                        <input type="password" id="pass" name="pass" placeholder="Password">
                        <input type="password" id="retypePass" name="retypePass" placeholder="Re-type password">
                        <input type="submit" id="btnSignUp" value="Sign Up" name="submit">
                    </form>
                    <div id="existingAccountDiv">
                        <a href="index.php" id="btnExistingAccount">Already have an account?</a>
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