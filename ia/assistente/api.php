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
$MODELO   = 'gemini-flash-lite-latest'; // "lite": limite gratuito maior. Alt.: gemini-3.6-flash
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

// ---- travões de uso (protegem a quota gratuita) ------------------------
$RATE_DIR = sys_get_temp_dir() . '/ad_assist';
@mkdir($RATE_DIR, 0700, true);
$now = time();
$ip  = $_SERVER['REMOTE_ADDR'] ?? 'x';

// por IP: no máximo 15 perguntas em 10 minutos
$ipFile = $RATE_DIR . '/ip_' . md5($ip);
$hits = @json_decode(@file_get_contents($ipFile), true);
if (!is_array($hits)) { $hits = []; }
$hits = array_values(array_filter($hits, function ($t) use ($now) { return $t > $now - 600; }));
if (count($hits) >= 15) {
    http_response_code(429);
    echo json_encode(['erro' => 'Muitas perguntas seguidas. Espera um minuto e tenta de novo.']);
    exit;
}

// global: no máximo 300 perguntas por dia (protege a quota diária gratuita)
$dayFile  = $RATE_DIR . '/day_' . date('Ymd');
$dayCount = (int) @file_get_contents($dayFile);
if ($dayCount >= 300) {
    http_response_code(429);
    echo json_encode(['erro' => 'O assistente já ajudou muita gente hoje. Volta amanhã. 🙂']);
    exit;
}

// conta esta pergunta
$hits[] = $now;
@file_put_contents($ipFile, json_encode($hits), LOCK_EX);
@file_put_contents($dayFile, (string) ($dayCount + 1), LOCK_EX);

// ---- instrução do sistema (personalidade do assistente) ----------------
$system = "És o assistente da Autonomia Digital CV. Ajudas alunos de TIC e utilizadores em "
        . "geral, em português europeu (pt-PT), tratando por \"tu\". Responde de forma simples, "
        . "curta e encorajadora, com exemplos práticos quando ajudar. Ajudas com informática "
        . "do dia a dia: Windows, ficheiros e pastas, internet e segurança, Excel, Word e o uso "
        . "geral do computador. Se não souberes ou a pergunta fugir ao tema, diz que não sabes "
        . "— não inventes.";

// Contexto oficial (site, cursos, professor) — editável em contexto.txt, sem mexer no código.
$ctx = @trim(@file_get_contents(__DIR__ . '/contexto.txt'));
if ($ctx) {
    $system .= "\n\nCONTEXTO OFICIAL (usa isto para responder sobre a Autonomia Digital CV, "
             . "o site, os cursos e o professor André Sanches. Se te perguntarem algo sobre isto "
             . "que NÃO esteja aqui, diz que não tens a certeza e sugere o WhatsApp ou o e-mail. "
             . "Não inventes preços, datas nem contactos):\n" . $ctx;
}

// ---- chamada ao Gemini -------------------------------------------------
$payload = [
    'system_instruction' => ['parts' => [['text' => $system]]],
    'contents' => [['role' => 'user', 'parts' => [['text' => $pergunta]]]],
    'generationConfig' => [
        'temperature'    => 0.4,
        'maxOutputTokens'=> 1500,
    ],
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

if ($code === 429) {
    http_response_code(429);
    echo json_encode(['erro' => 'Estou a receber muitas perguntas ao mesmo tempo. Espera uns segundos e tenta de novo. 🙂']);
    exit;
}
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
