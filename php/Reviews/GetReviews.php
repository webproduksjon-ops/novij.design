<?php

$reviewsPath = '../../data/reviews.json';

$reviewsFile = $reviewsPath;
if (file_exists($reviewsFile)) {
    $reviews = json_decode(file_get_contents($reviewsFile), true);
    echo json_encode($reviews);
} else {
    echo json_encode([]);
}
?>