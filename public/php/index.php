<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die();
}

$request = file_get_contents('php://input');
$input = json_decode($request, true);

$url = 'https://www.mentalfloss.com/kennections';
if (isset($input['puzzle'])) {
    $url = 'https://www.mentalfloss.com/posts/ken-jennings-kennections-quiz-' . $input['puzzle'];
}

$html = file_get_contents($url);
$html = explode('__PRELOADED_STATE__ =', $html)[1];
$html = explode('</script>', $html)[0];
print_r($html);
