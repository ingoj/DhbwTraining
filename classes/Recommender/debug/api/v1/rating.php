<?php

require_once __DIR__ . "/_helper/_init.php";

$response = [
    "status"                   => RecommenderResponse::STATUS_SUCCESS,
    "response_type"            => RecommenderResponse::RESPONSE_TYPE_TEST_IS_FINISHED,
    "recomander_id"            => $random_recomander_id,
    "message"                  => "Rating",
    "message_type"             => RecommenderResponse::MESSAGE_TYPE_SUCCESS,
    "answer_response"          => "",
    "answer_response_type"     => RecommenderResponse::MESSAGE_TYPE_INFO,
    "progress"                 => 1,
    "progress_type"            => RecommenderResponse::MESSAGE_TYPE_SUCCESS,
    "learning_progress_status" => RecommenderResponse::LEARNING_PROGRESS_STATUS_COMPLETED,
    "competences"              => [
        11 => 13,
        12 => 16,
        13 => 19
    ]
];

require_once __DIR__ . "/_helper/_output.php";
