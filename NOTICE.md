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

## Known issues that are left as they are

- The PHP 4 style constructors (`function HTTP_Download()`, `HTTP_Header()`,
  `HTTP_Header_Cache()`) raise "deprecated constructor" notices on PHP 7.x and no
  longer act as constructors on PHP 8.
- `src/HTTP.php` and `src/HTTP/Header.php` use `$string{0}` offsets
  (deprecated in PHP 7.4, a parse error in PHP 8).
- `HTTP_Download_Archive` needs `Archive_Tar` / `Archive_Zip`, which are not
  required by this package.
- `HTTP_Download::sortChunks()` uses `create_function()` (removed in PHP 8). It
  is never called.

These are fixed centrally in later releases of this package rather than in each
application that uses it.
