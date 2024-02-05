<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die();
}

$request = file_get_contents('php://input');
$input = json_decode($request, true);

$options = [
    "http" => [
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.9999.999 Safari/537.36",
    ],
];
$context = stream_context_create($options);

$url = 'https://www.mentalfloss.com/authors/ken-jennings';
if (isset($input['puzzle'])) {
    $url = 'https://www.mentalfloss.com/posts/ken-jennings-kennections-quiz-' . $input['puzzle'];
}

$html = file_get_contents($url, false, $context);
$html = explode('__PRELOADED_STATE__ =', $html)[1];
$html = explode('</script>', $html)[0];
print_r($html);
