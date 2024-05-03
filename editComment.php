<?php

    require('connection.php');

    if ($_POST && isset($_POST['id']) && isset($_POST['comment_id']))
    {
        $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
        $comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_SANITIZE_NUMBER_INT);
        
        // Get the username of the profile page
        $queryUsername = "SELECT username FROM users WHERE id=:id";
        $statement_query_username = $db->prepare($queryUsername);
        $statement_query_username->bindValue(':id', $id, PDO::PARAM_INT);
        $statement_query_username->execute();
        $row_username = $statement_query_username->fetch();
        // $username = $row_username['username'];
        $username = str_replace(' ', '-', $row_username['username']);

        if($_POST['command'] === 'delete comment')
        {
            $queryDeleteComment = "DELETE FROM comments WHERE comment_id=:comment_id";
            $statement_delete_comment = $db->prepare($queryDeleteComment);
            $statement_delete_comment->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
            $statement_delete_comment->execute();
        }
        elseif($_POST['command'] === 'hide comment')
        {
            $queryHideComment = "UPDATE comments SET visible=0 WHERE comment_id=:comment_id";
            $statement_hide_comment = $db->prepare($queryHideComment);
            $statement_hide_comment->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
            $statement_hide_comment->execute();
        }
        elseif($_POST['command'] === 'unhide comment')
        {
            $queryShowComment = "UPDATE comments SET visible=1 WHERE comment_id=:comment_id";
            $statement_show_comment = $db->prepare($queryShowComment);
            $statement_show_comment->bindValue(':comment_id', $comment_id, PDO::PARAM_INT);
            $statement_show_comment->execute();
        }

        header("Location:profile/$id/$username");
        exit;
    }
?>
        