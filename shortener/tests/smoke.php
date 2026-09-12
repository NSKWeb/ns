<?php
/**
 * NS Link - smoke test (E2E).
 *
 * Usage:
 *   php tests/smoke.php            # boots internal server + runs checks
 *
 * Requires: PHP built-in server (php -S) on 127.0.0.1:8899.
 */

$BASE = 'http://127.0.0.1:8899';
$COOKIE = '';
$fails = 0;
$passes = 0;

function fail(string $msg): void
{
    global $fails;
    $fails++;
    echo 'FAIL  ' . $msg . "\n";
}

function pass(string $msg): void
{
    global $passes;
    $passes++;
    echo 'OK    ' . $msg . "\n";
}

/**
 * Simple HTTP client over the bundled curl extension.
 * Writes/reads cookies via a local cookie jar so admin session persists.
 */
function http_request(string $method, string $path, ?array $post = null, bool $follow = false): array
{
    global $BASE;
    $h = curl_init();
    curl_setopt($h, CURLOPT_URL, $BASE . $path);
    curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($h, CURLOPT_HEADER, true);
    curl_setopt($h, CURLOPT_COOKIEFILE, '/tmp/nslink-cookies.txt');
    curl_setopt($h, CURLOPT_COOKIEJAR, '/tmp/nslink-cookies.txt');
    curl_setopt($h, CURLOPT_FOLLOWLOCATION, $follow);
    curl_setopt($h, CURLOPT_MAXREDIRS, 5);
    curl_setopt($h, CURLOPT_TIMEOUT, 10);
    if ($post !== null) {
        curl_setopt($h, CURLOPT_POST, true);
        curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $raw = (string)curl_exec($h);
    $info = curl_getinfo($h);
    $status = (int)($info['http_code'] ?? 0);
    curl_close($h);

    // Split headers vs body.
    $body = '';
    $location = '';
    if (strpos($raw, "\r\n\r\n") !== false) {
        $hdrBlock = explode("\r\n\r\n", $raw, 2)[0] . "\r\n";
        $body = explode("\r\n\r\n", $raw, 2)[1] ?? '';
    } elseif (strpos($raw, "\n\n") !== false) {
        $hdrBlock = explode("\n\n", $raw, 2)[0] . "\n";
        $body = explode("\n\n", $raw, 2)[1] ?? '';
    } else {
        $hdrBlock = $raw;
        $body = '';
    }
    if (preg_match('/(?i)^location:\s*(.*?)\s*$/m', $hdrBlock, $m)) {
        $location = trim($m[1]);
    }
    return ['status' => $status, 'body' => $body, 'location' => $location];
}

function expect_status(string $label, array $res, int $want): void
{
    if ($res['status'] === $want) {
        pass($label);
    } else {
        fail($label . ' (got ' . $res['status'] . ')');
    }
}

// ---------- Boot server ----------
$docRoot = realpath(__DIR__ . '/..');
echo "DocRoot: " . $docRoot . "\n";
echo "Server:  " . $BASE . "\n\n";

// ---------- 1. Public landing ----------
$res = http_request('GET', '/');
expect_status('GET / -> 200', $res, 200);

// ---------- 2. Login (admin default) ----------
$res = http_request('POST', '/admin/login.php', ['user' => 'admin', 'pass' => 'ns-admin-2026'], false);
expect_status('POST /admin/login.php (default login)', $res, 302);

if ($res['status'] !== 302) {
    fail('Cannot proceed without login - aborting chain test.');
    summary();
    exit(1);
}

// ---------- 3. Dashboard ----------
$res = http_request('GET', '/admin/index.php');
expect_status('GET /admin/index.php (logged in)', $res, 200);

// ---------- 4. Create a chain (3 steps + dest) ----------
$post = [
    'action' => 'create',
    'name' => 'Smoke chain',
    'count' => '3',
    'dest' => 'https://example.com/final',
    'step_url_1' => 'https://step-a.example.com/1',
    'step_wait_1' => '2',
    'step_url_2' => 'https://step-b.example.com/2',
    'step_wait_2' => '2',
    'step_url_3' => 'https://step-c.example.com/3',
    'step_wait_3' => '3',
];
$res = http_request('POST', '/admin/index.php', $post, false);
expect_status('POST /admin/index.php (chain create -> 302)', $res, 302);

// ---------- 5. Find the created chain ----------
// easiest: read DB directly? no - use admin pages. Fetch chains page and parse.
$res = http_request('GET', '/admin/chains.php');
expect_status('GET /admin/chains.php', $res, 200);
$chainCode = null;
if (preg_match('@<td><code>([a-zA-Z0-9]{8})</code>@', $res['body'], $m)) {
    $chainCode = $m[1];
    pass('Parsed chain code: ' . $chainCode);
} else {
    fail('Could not parse chain code from chains page');
}

if ($chainCode === null) {
    summary();
    exit(1);
}

// short-link code of first step should be in the row too (2nd link)
$firstShort = null;
if (preg_match('@/go/([a-zA-Z0-9]{6})</a></td>\s*<td><a href="/go/@s', $res['body'], $m)) {
    $firstShort = $m[1];
    pass('Parsed first-step short code: ' . $firstShort);
} else {
    // try simpler: first /go/ link of length 6 in the table
    if (preg_match_all('@href="/go/([a-zA-Z0-9]{6})"@', $res['body'], $mm)) {
        $firstShort = $mm[1][0] ?? null;
        if ($firstShort) pass('Parsed first-step short code (alt): ' . $firstShort);
    }
    if (!$firstShort) fail('Could not parse first-step short code');
}

// ---------- 6. Public chain start (/go/<chaincode>) ----------
if ($chainCode) {
    $res = http_request('GET', '/go/' . $chainCode, null, true);
    expect_status('GET /go/' . $chainCode . ' (chain start)', $res, 200);
    if (strpos($res['body'], 'NS Link - Task') !== false) {
        pass('Chain start renders task page');
    } else {
        fail('Chain start should render task page');
    }
}

// ---------- 7. First step task page ----------
if ($firstShort) {
    $res = http_request('GET', '/go/' . $firstShort, null, false);
    expect_status('GET /go/' . $firstShort . ' (step 1)', $res, 200);
    if (strpos($res['body'], 'Step 1 / 3') !== false
        && strpos($res['body'], 'data-wait="2"') !== false) {
        pass('Step 1 task page has correct step/total/wait');
    } else {
        fail('Step 1 task page content mismatch');
    }
    if (strpos($res['body'], 'Continue') !== false) {
        pass('Step 1 shows continue button');
    } else {
        fail('Step 1 missing continue button');
    }
    if (preg_match('@data-next="([^"]*step=2[^"]*)"@', $res['body'], $m)) {
        pass('Step 1 continue points to step 2: ' . $m[1]);
    } else {
        fail('Step 1 continue should point to step 2');
    }
}

// ---------- 8. Click logging (server-side) ----------
// go.php logs a click on step render; verify in admin stats
$res = http_request('GET', '/admin/stats.php');
expect_status('GET /admin/stats.php', $res, 200);
if (strpos($res['body'], 'Total clicks recorded:') !== false) {
    pass('Stats page renders');
} else {
    fail('Stats page missing totals');
}

// ---------- 8b. Simple short link (M1 E2E) ----------
$simplePost = [
    'action' => 'create',
    'name' => 'Simple smoke link',
    'dest' => 'https://example.com/plain',
];
$res = http_request('POST', '/admin/index.php', $simplePost, false);
expect_status('POST /admin/index.php (simple link create -> 302)', $res, 302);
$simpleCode = '';
if (preg_match('@[\?&]link=([a-zA-Z0-9]{6})@', $res['location'], $m)) {
    $simpleCode = $m[1];
    pass('Simple link code from Location: ' . $simpleCode);
} else {
    fail('Could not read simple link code from redirect Location');
}

if ($simpleCode !== '') {
    $res = http_request('GET', '/go/' . $simpleCode, null, false);
    expect_status('GET /go/' . $simpleCode . ' (redirect)', $res, 302);
    if (strpos($res['location'], 'https://example.com/plain') !== false) {
        pass('Simple link redirects to correct destination');
    } else {
        fail('Simple link redirect Location mismatch: ' . $res['location']);
    }
}

// ---------- 9. Final step render (destination) ----------
if ($chainCode) {
    $res = http_request('GET', '/task.php?chain=' . $chainCode . '&step=3', null, false);
    expect_status('GET /task.php (final step)', $res, 200);
    if (strpos($res['body'], 'Open Your Link') !== false
        && strpos($res['body'], 'step=3') === false) {
        pass('Final step shows Open Your Link button');
    } else {
        fail('Final step should show Open Your Link');
    }
    if (preg_match('@data-next="(https://example\.com/final)"@', $res['body'], $m)) {
        pass('Final step continue points to destination: ' . $m[1]);
    } else {
        fail('Final step continue should point to destination');
    }
}

summary();

function summary(): void
{
    global $passes, $fails;
    echo "\n" . '---- Summary ----' . "\n";
    echo 'Passed: ' . $passes . "\n";
    echo 'Failed: ' . $fails . "\n";
    if ($fails > 0) {
        exit(1);
    }
    exit(0);
}