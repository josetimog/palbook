<?php

    require('connection.php');

    if ($_POST && isset($_POST['id']) && isset($_POST['reply_id']))
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $reply_id = filter_input(INPUT_POST, 'reply_id', FILTER_SANITIZE_NUMBER_INT);
        
        // Get the username of the profile page
        $queryUsername = "SELECT username FROM users WHERE id=:id";
        $statement_query_username = $db->prepare($queryUsername);
        $statement_query_username->bindValue(':id', $id, PDO::PARAM_INT);
        $statement_query_username->execute();
        $row_username = $statement_query_username->fetch();
        // $username = $row_username['username'];
        $username = str_replace(' ', '-', $row_username['username']);

        if($_POST['command'] === 'delete reply')
        {
            $queryDeleteReply = "DELETE FROM replies WHERE reply_id=:reply_id";
            $statement_delete_reply = $db->prepare($queryDeleteReply);
            $statement_delete_reply->bindValue(':reply_id', $reply_id, PDO::PARAM_INT);
            $statement_delete_reply->execute();
        }
        elseif($_POST['command'] === 'hide reply')
        {
            $queryHideReply = "UPDATE replies SET visible=0 WHERE reply_id=:reply_id";
            $statement_hide_reply = $db->prepare($queryHideReply);
            $statement_hide_reply->bindValue(':reply_id', $reply_id, PDO::PARAM_INT);
            $statement_hide_reply->execute();
        }
        elseif($_POST['command'] === 'unhide reply')
        {
            $queryShowReply = "UPDATE replies SET visible=1 WHERE reply_id=:reply_id";
            $statement_show_reply = $db->prepare($queryShowReply);
            $statement_show_reply->bindValue(':reply_id', $reply_id, PDO::PARAM_INT);
            $statement_show_reply->execute();
        }

        header("Location:profile/$id/$username");
        exit;
    }



?>
        