# Provenance and changes

`src/` holds the source of three packages from the PEAR repository. Apart from
the changes listed below, every file in `src/` is **byte-identical** to the file
in the original release archive.

| Package | Version | Original archive | SHA-256 of the archive | License |
|---|---|---|---|---|
| HTTP | 1.4.1 | https://pear.php.net/get/HTTP-1.4.1.tgz | `4a2ec096d8059f9293bb0c52080dcb26bcbe9a7a29452e19d1959a9bc6e5fc45` | New BSD |
| HTTP_Header | 1.2.1 | https://pear.php.net/get/HTTP_Header-1.2.1.tgz | `e640778c63fb64ac331585cb407c23a5f9862eed7b17e3dc8c382001d34ca3ce` | BSD, revised |
| HTTP_Download | 1.1.4 | https://pear.php.net/get/HTTP_Download-1.1.4.tgz | `c3627878959835438997e16bbba5707ab00cf3d930297f41d0964d67e88b5341` | BSD, revised |

The hashes are those of the archives as downloaded; they are not an independent
signature of the upstream authors. The original `package.xml` of each release is
kept in `upstream/`.

Original authors (from the `package.xml` files): Michael Wallner and Philippe
Jausions (HTTP, HTTP_Download); Wolfram Kriesing, Davey Shafik and Michael
Wallner (HTTP_Header).

## Changes from the original release

All changes make the code load on PHP 7 and later, or are fixes the application
that uses it depended on. Line numbers refer to the original files.

`src/HTTP/Download.php` (HTTP_Download 1.1.4)

1. line 252: `$this->HTTP = &new HTTP_Header;` became `$this->HTTP = new HTTP_Header;`
   (assigning the result of `new` by reference is a parse error since PHP 7.0).
2. line 766: `$d = &new HTTP_Download();` became `$d = new HTTP_Download();` (same reason).
3. line 912: before `while (!feof($this->handle))`, a guard was added that stops
   with the message `Error! ($this->handle must be resource, false detected)` when
   `$this->handle` is `false`; the loop now calls `@feof()`.

`src/HTTP/Download/Archive.php` (HTTP_Download 1.1.4)

4. lines 71, 77, 83, 89, 116: `= &new ...` became `= new ...` (same reason as 1).

## PHP 5.6 to PHP 8.4 compatibility changes

5. `src/HTTP.php` and `src/HTTP/Header.php`: eight curly-brace string offsets
   became bracket offsets. `HTTP.php` utility methods and
   `HTTP_Header::redirect()` were declared static for their existing static
   callers. `HTTP::redirect()` explicitly uses `ENT_COMPAT` to preserve the
   pre-PHP-8.1 `htmlspecialchars()` behavior.
6. `src/HTTP/Download.php`, `src/HTTP/Header.php`, and
   `src/HTTP/Header/Cache.php`: `__construct()` delegates to each existing
   PHP 4-style constructor so initialization also runs on PHP 8.
7. `HTTP_Download::sortChunks()` uses a PHP 5.6-compatible closure instead of
   the removed `create_function()`. `isRangeRequest()` tests the result of
   `getRanges()` directly instead of calling `count()` on its string result.
8. Methods documented and called as static in `HTTP_Download`,
   `HTTP_Download_Archive` and `HTTP_Download_PgLOB` were declared static.
9. References to ini settings removed before PHP 5.6 (`y2k_compliance`,
   `safe_mode`, `mime_magic.magicfile`) were removed. `guessContentType()` now
   calls `mime_content_type()` when `MIME_Type` is unavailable.

## Remaining limitations

- `HTTP_Download_Archive` needs `Archive_Tar` / `Archive_Zip`, which are not
  required by this package. Its behavior has not been tested across PHP versions.
- The PostgreSQL LOB path has not been tested across PHP versions.

Changes are made centrally in this package rather than in applications or
their `vendor/` directories.
