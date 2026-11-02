# catatsumuri/http-download

PEAR's `HTTP_Download`, `HTTP_Header` and `HTTP`, packaged for Composer.

`HTTP_Download` sends files or in-memory data to the browser as a download or
inline content and answers conditional (`ETag`, `304`) and byte-range
(`206`, `416`, multipart) requests by itself. These packages are not available on
Packagist under `pear/…`, and PEAR's own installer is not an option for
Composer-based applications, so the original source and its PHP
compatibility fixes are maintained with provenance recorded in
[NOTICE.md](NOTICE.md).

PEAR の HTTP_Download / HTTP_Header / HTTP を Composer パッケージとして保守しています。
PHP 5.6〜8.4 向けの修正と元のソースとの差分は `NOTICE.md` に記録しています。

## Install

```sh
composer require catatsumuri/http-download
```

Requires PHP 5.6 or later and `pear/pear-core-minimal`. The `include-path` entry in
`composer.json` keeps the original `require_once 'HTTP/Download.php';` working.

## Use

```php
require_once 'HTTP/Download.php';

$download = new HTTP_Download();
$download->setData($bytes);                       // or setFile($path)
$download->setContentDisposition(HTTP_DOWNLOAD_ATTACHMENT, 'report.pdf');
$download->setContentType('application/pdf');
$download->send();
```

The API is documented in the original
[HTTP_Download manual](https://pear.php.net/package/HTTP_Download).

## Status

Based on HTTP_Download 1.1.4 (2010), with PHP compatibility fixes. The
smoke test passes on PHP 5.6.40, 7.4.33 and 8.4.25. The application's 29-case
HTTP golden master matches on PHP 7.4 and 8.4 after normalizing the built-in
server's protocol version on PHP 8.4. PHP 5.6 differs on one `If-Range` body
case, also with the original package. Optional archive and PostgreSQL LOB
paths have not been exercised. See [NOTICE.md](NOTICE.md) for source changes
and remaining limitations.

## Test

```sh
php tests/smoke.php /path/to/a/project/vendor/autoload.php
```

The smoke test starts PHP's built-in web server and checks real HTTP exchanges
(attachment and inline with a Japanese file name, `ETag`/`304`, `Range`/`206`,
`416`, a file served with `setFile()`). It also checks constructors,
static helpers, range sorting and MIME type detection. The autoload file is
only needed to find `pear/pear-core-minimal`.

## License

3-clause BSD, with the copyright holders of the original packages. See
[LICENSE](LICENSE), `licenses/` and [NOTICE.md](NOTICE.md).
