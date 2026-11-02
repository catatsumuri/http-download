<?php
// Usage: php tests/smoke.php /path/to/vendor/autoload.php
// The autoload file only has to provide pear/pear-core-minimal (PEAR.php, System.php).
if (!isset($argv[1]) || !is_file($argv[1])) {
    fwrite(STDERR, "usage: php tests/smoke.php /path/to/vendor/autoload.php\n");
    exit(2);
}
$root = realpath(__DIR__.'/..');

function fail($message)
{
    fwrite(STDERR, 'FAIL: '.$message."\n");
    exit(1);
}

function check($condition, $message)
{
    if (!$condition) {
        fail($message);
    }
}

function header_value($headers, $name)
{
    foreach ($headers as $line) {
        $parts = explode(':', $line, 2);
        if (count($parts) === 2 && strcasecmp($parts[0], $name) === 0) {
            return trim($parts[1]);
        }
    }
    return null;
}

function get($url, $extra = array())
{
    $context = stream_context_create(array('http' => array(
        'ignore_errors' => true, 'timeout' => 5, 'follow_location' => 0,
        'header' => implode("\r\n", $extra),
    )));
    $body = @file_get_contents($url, false, $context);
    return array($body === false ? '' : $body, isset($http_response_header) ? $http_response_header : array());
}

require $argv[1];
set_include_path($root.'/src'.PATH_SEPARATOR.get_include_path());
require_once 'HTTP/Download.php';
require_once 'HTTP/Header/Cache.php';

$previousProtocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : null;
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$header = new HTTP_Header();
check($header->getHttpVersion() === '1.1', 'HTTP_Header constructor');
if ($previousProtocol === null) {
    unset($_SERVER['SERVER_PROTOCOL']);
} else {
    $_SERVER['SERVER_PROTOCOL'] = $previousProtocol;
}

$cache = new HTTP_Header_Cache();
check($cache->getHeader('Pragma') === 'cache', 'HTTP_Header_Cache constructor');
$download = new HTTP_Download();
check($download->HTTP instanceof HTTP_Header, 'HTTP_Download constructor');
check(is_string(HTTP::Date(0)), 'HTTP static Date');
$chunks = array(array(3, 4), array(0, 1));
$download->sortChunks($chunks);
check($chunks === array(array(0, 1), array(3, 4)), 'range comparator');

$dir = sys_get_temp_dir().'/http-download-smoke-'.uniqid();
mkdir($dir);
file_put_contents($dir.'/file.txt', "0123456789\n");
$mimeProbe = new HTTP_Download(array('file' => $dir.'/file.txt'));
check(!PEAR::isError($mimeProbe->guessContentType()), 'guessContentType');
check(isset($mimeProbe->headers['Content-Type']), 'guessed Content-Type');
file_put_contents($dir.'/router.php', <<<'PHP'
<?php
require getenv('SMOKE_AUTOLOAD');
// This repository's src/ must win over any other copy on the include path.
set_include_path(getenv('SMOKE_ROOT').'/src'.PATH_SEPARATOR.get_include_path());
require_once 'HTTP/Download.php';
$d = new HTTP_Download();
if (isset($_GET['file'])) {
    $d->setFile(__DIR__.'/file.txt');
    $d->setContentType('text/plain');
} else {
    $d->setData("attachment contents\n");
    $d->setContentType('application/pdf');
    $d->setContentDisposition(
        isset($_GET['inline']) ? HTTP_DOWNLOAD_INLINE : HTTP_DOWNLOAD_ATTACHMENT,
        "評価 表's.pdf"
    );
}
$d->send();
PHP
);
putenv('SMOKE_AUTOLOAD='.realpath($argv[1]));
putenv('SMOKE_ROOT='.$root);

$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if ($socket === false) {
    fail('cannot reserve a port');
}
$address = stream_socket_get_name($socket, false);
fclose($socket);
$command = 'exec '.escapeshellarg(PHP_BINARY).' -S '.escapeshellarg($address).' '.escapeshellarg($dir.'/router.php');
$process = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('file', $dir.'/server.log', 'a'), 2 => array('file', $dir.'/server.log', 'a')), $pipes, $dir);

try {
    check(is_resource($process), 'cannot start the test server');
    fclose($pipes[0]);
    $ready = false;
    for ($i = 0; $i < 40 && !$ready; $i++) {
        $c = @stream_socket_client('tcp://'.$address, $errno, $error, 0.1);
        if ($c) {
            fclose($c);
            $ready = true;
        } else {
            usleep(50000);
        }
    }
    check($ready, 'the test server did not start');
    $base = 'http://'.$address.'/';

    list($body, $h) = get($base);
    check(strpos($h[0], ' 200 ') !== false, 'attachment: status '.$h[0]);
    check($body === "attachment contents\n", 'attachment: body');
    check(header_value($h, 'Content-Type') === 'application/pdf', 'attachment: Content-Type');
    check(header_value($h, 'Content-Disposition') === 'attachment; filename="評価 表\'s.pdf"', 'attachment: Content-Disposition '.header_value($h, 'Content-Disposition'));
    check(header_value($h, 'Content-Length') === '20', 'attachment: Content-Length');
    $etag = header_value($h, 'ETag');
    check($etag !== null && $etag !== '', 'attachment: ETag');

    list($body, $h) = get($base.'?inline=1');
    check(header_value($h, 'Content-Disposition') === 'inline; filename="評価 表\'s.pdf"', 'inline: Content-Disposition');

    list($body, $h) = get($base.'?inline=1', array('Range: bytes=0-3'));
    check(strpos($h[0], ' 206 ') !== false && $body === 'atta', 'range: 206 and body');
    check(header_value($h, 'Content-Range') === 'bytes 0-3/20', 'range: Content-Range');

    list($body, $h) = get($base.'?inline=1', array('If-None-Match: '.$etag));
    check(strpos($h[0], ' 304 ') !== false && $body === '', 'conditional: 304');

    list($body, $h) = get($base.'?inline=1', array('Range: bytes=100-200'));
    check(strpos($h[0], ' 416 ') !== false, 'range: 416 for an unsatisfiable range');

    list($body, $h) = get($base.'?file=1', array('Range: bytes=2-5'));
    check(strpos($h[0], ' 206 ') !== false && $body === '2345', 'setFile: range body '.var_export($body, true));
    list($body, $h) = get($base.'?file=1');
    check(strpos($h[0], ' 200 ') !== false && $body === "0123456789\n", 'setFile: full body');
    check(header_value($h, 'Content-Disposition') === 'attachment; filename="file.txt"', 'setFile: default file name');

    echo "PASS: attachment/inline with a Japanese name, ETag and 304, Range 206 and 416, setFile\n";
} finally {
    if (is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
    }
    foreach (array('router.php', 'file.txt', 'server.log') as $f) {
        if (is_file($dir.'/'.$f)) {
            unlink($dir.'/'.$f);
        }
    }
    rmdir($dir);
}
