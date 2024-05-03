<?php

require("connection.php");

// Get the post Id and user Id from the POST data
$postId = $_POST['post_id'];
$userId = $_POST['user_id'];

// Update the like count in the database
$queryExistingLikes = "SELECT * FROM likes WHERE post_id=$postId AND user_id=$userId";
$getExistingLikes = $db->prepare($queryExistingLikes);
$getExistingLikes->execute();
$existingLikes = $getExistingLikes->rowCount();
if ($existingLikes == 0)
{
    $queryInsertLike = "INSERT INTO likes (post_id, user_id) VALUES (:postId, :userId)";
    $insertLike = $db->prepare($queryInsertLike);
    
    // Bind values to the parameters
    $insertLike->bindParam(':postId', $postId, PDO::PARAM_INT);
    $insertLike->bindParam(':userId', $userId, PDO::PARAM_INT);
    
    // Execute the query
    $insertLike->execute();
}


// Get the updated like count
$queryLikeCount = "SELECT * FROM likes WHERE post_id=:postId";
$getLikeCount = $db->prepare($queryLikeCount);
$getLikeCount->bindParam(':postId', $postId, PDO::PARAM_INT);
$getLikeCount->execute();
$postLikeCount = $getLikeCount->rowCount();

echo $postLikeCount;

?>