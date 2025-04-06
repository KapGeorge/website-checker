<?php
/**
 * Website Status Checker & Telegram Notifier
 *
 * Requirements:
 * - PHP 7.1 or higher
 * - domains.txt with list of domains (one per line)
 * - config.php with 'botToken' and 'chatId'
 *
 * @author  Your Name
 * @version 1.0
 */

declare(strict_types=1);

// --- Runtime PHP version check ---
if (version_compare(PHP_VERSION, '7.1.0', '<')) {
    die("❌ This script requires PHP 7.1 or higher. Current version: " . PHP_VERSION . "\n");
}

// --- Load Configuration ---
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    throw new RuntimeException('❌ Configuration file not found: config.php');
}

$config = require $configPath;
$botToken = $config['botToken'] ?? null;
$chatId   = $config['chatId'] ?? null;

if (!$botToken || !$chatId) {
    throw new InvalidArgumentException('❌ Bot token or chat ID not set in config.php');
}

// --- Load Domains ---
$domainsFile = __DIR__ . '/domains.txt';
if (!file_exists($domainsFile)) {
    throw new RuntimeException('❌ Domain list file not found: domains.txt');
}

$domains = file($domainsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!$domains) {
    throw new RuntimeException('❌ No domains to process in domains.txt');
}

/**
 * Sends a message to a Telegram chat.
 *
 * @param string $chatId
 * @param string $message
 * @param string $botToken
 * @return bool
 */
function sendTelegramMessage(string $chatId, string $message, string $botToken): bool
{
    $apiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

    $params = http_build_query([
        'chat_id'    => $chatId,
        'text'       => $message,
        'parse_mode' => 'HTML',
    ]);

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $params,
            'timeout' => 10,
        ]
    ]);

    $result = @file_get_contents($apiUrl, false, $context);

    return $result !== false;
}

/**
 * Gets the <title> of a webpage by domain.
 *
 * @param string $domain
 * @return string
 */
function getPageTitle(string $domain): string
{
    $url = "https://{$domain}";
    $html = @file_get_contents($url);

    if ($html && preg_match("/<title>(.*?)<\/title>/si", $html, $matches)) {
        return trim($matches[1]);
    }

    return 'N/A';
}

/**
 * Checks the status of a domain.
 *
 * @param string $domain
 * @return string
 */
function checkWebsite(string $domain): string
{
    $url = "https://{$domain}";
    $message = "<b>Domain:</b> {$domain}\n";

    // HTTP status
    $headers = @get_headers($url);
    $statusLine = $headers[0] ?? '';
    preg_match('/\s(\d{3})\s/', $statusLine, $statusMatch);
    $statusCode = (int)($statusMatch[1] ?? 0);

    $message .= "<b>Status:</b> {$statusCode}" . ($statusCode >= 400 ? " 🔥" : "") . "\n";

    // Page title
    $title = getPageTitle($domain);
    $message .= "<b>Title:</b> {$title}\n";

    // Response time
    $start = microtime(true);
    @file_get_contents($url);
    $end = microtime(true);
    $duration = round($end - $start, 2);

    $message .= "<b>Response Time:</b> {$duration} sec\n";

    return $message;
}

// --- Build report ---
$report = '';
foreach ($domains as $domain) {
    $report .= checkWebsite(trim($domain)) . "\n\n";
}

// --- Send to Telegram ---
$success = sendTelegramMessage($chatId, $report, $botToken);

if (!$success) {
    error_log("❌ Failed to send message to Telegram.");
}
