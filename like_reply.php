<?php

require("connection.php");

// Get the reply Id and user Id from the POST data
$replyId = $_POST['reply_id'];
$userId = $_POST['user_id'];

// Update the like count in the database
$queryExistingLikes = "SELECT * FROM likes WHERE reply_id=$replyId AND user_id=$userId";
$getExistingLikes = $db->prepare($queryExistingLikes);
$getExistingLikes->execute();
$existingLikes = $getExistingLikes->rowCount();

if ($existingLikes == 0)
{
    $queryInsertLike = "INSERT INTO likes (reply_id, user_id) VALUES (:replyId, :userId)";
    $insertLike = $db->prepare($queryInsertLike);
    
    // Bind values to the parameters
    $insertLike->bindParam(':replyId', $replyId, PDO::PARAM_INT);
    $insertLike->bindParam(':userId', $userId, PDO::PARAM_INT);
    
    // Execute the query
    $insertLike->execute();
}

// Get the updated like count
$queryLikeCount = "SELECT * FROM likes WHERE reply_id=:replyId";
$getLikeCount = $db->prepare($queryLikeCount);
$getLikeCount->bindParam(':replyId', $replyId, PDO::PARAM_INT);
$getLikeCount->execute();
$replyLikeCount = $getLikeCount->rowCount();

echo $replyLikeCount;

?>