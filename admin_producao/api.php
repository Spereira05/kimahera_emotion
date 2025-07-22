<?php

require("model.php");
require("controller.php");
$emotionModel = new EmotionModel();
$emotionController = new EmotionController();

$emotionController->api();

?>