# catatsumuri/http-download

PEAR's `HTTP_Download`, `HTTP_Header` and `HTTP`, packaged for Composer.

`HTTP_Download` sends files or in-memory data to the browser as a download or
inline content and answers conditional (`ETag`, `304`) and byte-range
(`206`, `416`, multipart) requests by itself. These packages are not available on
Packagist under `pear/…`, and PEAR's own installer is not an option for
Composer-based applications, so the original source is published here as it was
released, with the provenance recorded in [NOTICE.md](NOTICE.md).

PEAR の HTTP_Download / HTTP_Header / HTTP を、Composer で使えるようにそのまま
パッケージにしたものです。元のソースとの差分は `NOTICE.md` に全部書いてあります。

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

This is a faithful copy of old code (HTTP_Download 1.1.4 is from 2010). The
smoke test passes on PHP 7.4 (the PHP 4 style constructors raise deprecation
notices there). The code comes from the PHP 5 era, but this package has not been
run on PHP 5.6. It is **not PHP 8 compatible**. See "Known issues" in
[NOTICE.md](NOTICE.md). Fixes are made here, in this package.

## Test

```sh
php tests/smoke.php /path/to/a/project/vendor/autoload.php
```

The smoke test starts PHP's built-in web server and checks real HTTP exchanges
(attachment and inline with a Japanese file name, `ETag`/`304`, `Range`/`206`,
`416`, a file served with `setFile()`). The autoload file is only needed to find
`pear/pear-core-minimal`.

## License

3-clause BSD, with the copyright holders of the original packages. See
[LICENSE](LICENSE), `licenses/` and [NOTICE.md](NOTICE.md).
