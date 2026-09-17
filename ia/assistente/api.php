<?php
/**
 * Assistente de dúvidas — Autonomia Digital CV
 * Proxy seguro entre o site e a API do Gemini.
 *
 * 🔐 A chave da API vive FORA da raiz web (ver $KEY_FILE) e NUNCA está no
 *    repositório. A página do site fala com este endpoint; só este endpoint
 *    fala com o modelo de IA — a chave nunca chega ao browser.
 */

header('Content-Type: application/json; charset=utf-8');

// Só o próprio site pode chamar este endpoint.
$ORIGIN = 'https://autonomiadigitalcv.com';
header('Access-Control-Allow-Origin: ' . $ORIGIN);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido.']);
    exit;
}

// ---- configuração ------------------------------------------------------
$MODELO   = 'gemini-2.0-flash';        // muda aqui se quiseres outro modelo
$KEY_FILE = '/var/www/gemini.key';      // fora da raiz web (não é servido)
$MAX_PERGUNTA = 500;                     // limite de caracteres

// ---- chave -------------------------------------------------------------
$key = @trim(@file_get_contents($KEY_FILE));
if (!$key) {
    http_response_code(500);
    echo json_encode(['erro' => 'Assistente indisponível de momento.']);
    exit;
}

// ---- pergunta do aluno -------------------------------------------------
$in = json_decode(file_get_contents('php://input'), true);
$pergunta = isset($in['pergunta']) ? trim($in['pergunta']) : '';
if ($pergunta === '') {
    echo json_encode(['erro' => 'Escreve uma pergunta.']);
    exit;
}
if (mb_strlen($pergunta) > $MAX_PERGUNTA) {
    $pergunta = mb_substr($pergunta, 0, $MAX_PERGUNTA);
}

// ---- instrução do sistema (personalidade do assistente) ----------------
$system = "És o assistente da Autonomia Digital CV. Ajudas alunos de TIC e de Excel "
        . "em português europeu (pt-PT), tratando por \"tu\". Responde de forma simples, "
        . "curta e encorajadora, com exemplos práticos quando ajudar. Foca-te em "
        . "informática, Excel, Word e uso do computador. Se não souberes ou a pergunta "
        . "fugir ao tema, diz que não sabes — não inventes.";

// ---- chamada ao Gemini -------------------------------------------------
$payload = [
    'system_instruction' => ['parts' => [['text' => $system]]],
    'contents' => [['role' => 'user', 'parts' => [['text' => $pergunta]]]],
    'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 512],
];

$url = "https://generativelanguage.googleapis.com/v1beta/models/"
     . rawurlencode($MODELO) . ":generateContent?key=" . urlencode($key);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 30,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200 || $resp === false) {
    http_response_code(502);
    echo json_encode(['erro' => 'O assistente não conseguiu responder. Tenta outra vez.']);
    exit;
}

$data  = json_decode($resp, true);
$texto = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
if ($texto === '') {
    echo json_encode(['erro' => 'Sem resposta. Reformula a pergunta.']);
    exit;
}

echo json_encode(['resposta' => $texto]);
