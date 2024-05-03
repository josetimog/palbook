<?php

require("connection.php");

// Get the comment Id and user Id from the POST data
$commentId = $_POST['comment_id'];
$userId = $_POST['user_id'];

// Update the like count in the database
$queryExistingLikes = "SELECT * FROM likes WHERE comment_id=$commentId AND user_id=$userId";
$getExistingLikes = $db->prepare($queryExistingLikes);
$getExistingLikes->execute();
$existingLikes = $getExistingLikes->rowCount();

if ($existingLikes == 0)
{
    $queryInsertLike = "INSERT INTO likes (comment_id, user_id) VALUES (:commentId, :userId)";
    $insertLike = $db->prepare($queryInsertLike);
    
    // Bind values to the parameters
    $insertLike->bindParam(':commentId', $commentId, PDO::PARAM_INT);
    $insertLike->bindParam(':userId', $userId, PDO::PARAM_INT);
    
    // Execute the query
    $insertLike->execute();
}

// Get the updated like count
$queryLikeCount = "SELECT * FROM likes WHERE comment_id=:commentId";
$getLikeCount = $db->prepare($queryLikeCount);
$getLikeCount->bindParam(':commentId', $commentId, PDO::PARAM_INT);
$getLikeCount->execute();
$commentLikeCount = $getLikeCount->rowCount();

echo $commentLikeCount;

?>