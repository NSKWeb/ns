<?php
/**
 * NS Link - chain engine (builder + task page renderer + click logging).
 */

require_once __DIR__ . '/db.php';

/**
 * Build a chain payload from admin form input.
 *
 * @param array<int, array{url: string, wait: string|int}> $links Ordered page links.
 * @param string $dest Final destination URL.

 * @return array{code: string, name?: string, steps: array<int, array>}
 */
function nslink_chain_build(array $links, string $dest): array
{
    $code = nslink_random_code(8);
    $steps = [];
    $total = count($links);

    foreach ($links as $i => $link) {
        $url = trim((string)($link['url'] ?? ''));
        if ($url === '') {
            continue;
        }
        $step = $i + 1;
        $isLast = $step === $total && $dest !== '';
        $steps[] = [
            'step'   => $step,
            'total'  => $total,
            'wait'   => max(1, (int)($link['wait'] ?? 8)),
            'url'    => $url,
            'next'   => $isLast ? $dest : null,
            'dest'   => $isLast ? $dest : null,
            'link_id'=> null,
        ];
    }

    if ($steps === []) {
        $steps[] = [
            'step'   => 1,
            'total'  => 1,
            'wait'   => 8,
            'url'    => '',
            'next'   => $dest !== '' ? $dest : null,
            'dest'   => $dest !== '' ? $dest : null,
            'link_id'=> null,
        ];
    }

    return ['code' => $code, 'steps' => $steps];
}

/**
 * Insert a chain (and its per-step links) into the DB.
 */
function nslink_chain_create(string $name, array $chain): array
{
    $db = nslink_db();
    $name = trim($name);
    if ($name === '') {
        $name = 'Chain ' . $chain['code'];
    }
    $chain['name'] = $name;
    $now = date('Y-m-d H:i:s');
    $chain['created_at'] = $now;
    $chain['updated_at'] = $now;

    $db->beginTransaction();
    try {
        $stmt = $db->prepare(
            'INSERT INTO chains (code, name, steps_json, created_at, updated_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$chain['code'], $name, json_encode($chain, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $now, $now]);
        $chainId = (int)$db->lastInsertId();

        foreach ($chain['steps'] as &$s) {
            $code = nslink_random_code(6);
            $url = '/task.php?chain=' . urlencode($chain['code']) . '&step=' . (int)$s['step'];
            $title = 'Chain step ' . (int)$s['step'];
            $stmt = $db->prepare('INSERT INTO links (code, url, title) VALUES (?, ?, ?)');
            $stmt->execute([$code, $url, $title]);
            $s['link_id'] = (int)$db->lastInsertId();
        }
        unset($s);

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    $final = json_encode($chain, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $stmt = $db->prepare('UPDATE chains SET steps_json = ? WHERE id = ?');
    $stmt->execute([$final, $chainId]);

    return ['id' => $chainId] + $chain;
}

/**
 * Load a chain by code.
 */
function nslink_chain_get(string $code): ?array
{
    $stmt = nslink_db()->prepare('SELECT * FROM chains WHERE code = ?');
    $stmt->execute([$code]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $data = json_decode($row['steps_json'], true);
    if (!is_array($data)) {
        return null;
    }
    $data['id'] = (int)$row['id'];
    $data['code'] = $row['code'];
    $data['name'] = $row['name'];
    $data['created_at'] = $row['created_at'];
    return $data;
}

/**
 * Load a chain by one of its per-step link codes (/go/<code>).
 */
function nslink_chain_by_link_code(string $code): ?array
{
    $stmt = nslink_db()->prepare('SELECT id, url FROM links WHERE code = ?');
    $stmt->execute([$code]);
    $row = $stmt->fetch();
    if (!$row || !preg_match('/chain=([A-Za-z0-9]+).*step=(\d+)/', $row['url'], $m)) {
        return null;
    }
    $chain = nslink_chain_get($m[1]);
    if ($chain === null) {
        return null;
    }
    $chain['chain_id']    = $chain['id'];
    $chain['link_id']     = (int)$row['id'];
    $chain['link_code']   = $row['code'];
    $chain['link_url']    = $row['url'];
    $chain['current_step'] = (int)$m[2];
    return $chain;
}

/**
 * Log a click for a step.
 */
function nslink_log_click(?int $linkId, ?int $chainId, int $step): void
{
    try {
        $ip   = $_SERVER['REMOTE_ADDR']    ?? '';
        $ua   = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ref  = $_SERVER['HTTP_REFERER']   ?? '';
        $stmt = nslink_db()->prepare(
            'INSERT INTO clicks (link_id, chain_id, step, ip, user_agent, referer) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$linkId, $chainId, $step, $ip, $ua, $ref]);
    } catch (Throwable $e) {
        // logging must never break the page
    }
}

/**
 * Render the task page HTML for a chain step.
 */
function nslink_chain_render(array $chain, int $step): string
{
    $steps = $chain['steps'] ?? [];
    $current = null;
    foreach ($steps as $s) {
        if ((int)($s['step'] ?? 0) === $step) {
            $current = $s;
            break;
        }
    }
    if ($current === null) {
        http_response_code(404);
        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>404 - Not Found</title></head><body><h1>404</h1><p>Step not found.</p></body></html>';
    }

    $wait   = max(1, (int)($current['wait'] ?? 8));
    $total  = (int)($current['total'] ?? count($steps));
    $dest   = (string)($current['dest'] ?? '');
    $code   = (string)($chain['code'] ?? '');
    $url    = (string)($current['url'] ?? '');
    $stepLabel = (int)$current['step'];
    $isLast = (int)($current['step'] ?? 0) === $total;

    // Continue => next step of this chain; final step => destination URL.
    if ($isLast) {
        $nextUrl = $dest !== '' ? $dest : '';
    } else {
        $nextUrl = '/task.php?chain=' . rawurlencode($code) . '&step=' . ($stepLabel + 1);
    }
    $destAttr = htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8');
    $nextAttr = htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8');
    $codeAttr = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $urlAttr  = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

    $btnLabel = $isLast ? 'Open Your Link ->' : 'Continue ->';
    $barWidth = $total > 1 ? round(($stepLabel / $total) * 100) : 100;

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>NS Link - Task {$stepLabel} of {$total}</title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="task-page">
<main class="task-card" data-wait="{$wait}" data-step="{$stepLabel}" data-total="{$total}">
  <div class="task-progress">
    <span class="task-count">Step {$stepLabel} / {$total}</span>
    <div class="task-bar"><div class="task-bar-fill" style="width: {$barWidth}%"></div></div>
  </div>
  <article class="task-article">
    <h1>Task {$stepLabel}</h1>
    <p class="task-timer" data-wait="{$wait}">Please wait <span class="countdown">{$wait}</span> seconds</p>
    <div class="nslink-ad-slot nslink-ad-top"><!-- ad slot --></div>
    <div class="task-content"><p>Your link is ready - continue reading below.</p><p class="task-url">{$urlAttr}</p></div>
    <div class="nslink-ad-slot nslink-ad-mid"><!-- ad slot --></div>
  </article>
  <footer class="task-footer">
    <button type="button" class="btn btn-primary btn-continue" disabled id="continueBtn" data-next="{$nextAttr}" data-dest="{$destAttr}">
      <span class="btn-label">{$btnLabel}</span>
    </button>
  </footer>
</main>
<script src="/assets/js/task.js"></script>
</body>
</html>
HTML;
}

/**
 * Random alphanumeric code (no look-alike chars).
 */
function nslink_random_code(int $len = 6): string
{
    $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $max = strlen($chars) - 1;
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $chars[random_int(0, $max)];
    }
    return $out;
}