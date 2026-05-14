# Mail Collector charset normalization comparison

This evidence was produced with the same `tests/imap/MailCollectorTest.php::testBuildTicketNormalizesCharsetEvidenceMails`
test and the same `.eml` fixtures.

## Commands

Before the implementation, only the test and fixtures were applied on top of the previous core code:

```bash
php vendor/bin/phpunit -c phpunit.xml.dist tests/imap/MailCollectorTest.php --filter testBuildTicketNormalizesCharsetEvidenceMails
```

After the implementation:

```bash
php vendor/bin/phpunit -c phpunit.xml.dist tests/functional/Glpi/Mail/ImportedMailContentSanitizerTest.php tests/imap/MailCollectorTest.php --filter '/(testSanitizeImportedMailContent|testInvalidUtf8BytesNeverEscapeSanitizer|testBuildTicketNormalizesCharsetEvidenceMails)/'
```

## Results

| Scenario | Fixture | Previous core result | New core result |
| --- | --- | --- | --- |
| ISO-8859-1 quoted-printable | `01-latin1-quoted-printable.eml` | Pass | Pass |
| Windows-1252 quoted-printable | `02-windows1252-quoted-printable.eml` | Pass | Pass |
| UTF-8 mojibake subject/body | `03-utf8-mojibake.eml` | Fail: `ConteÃºdo com mojibake` remained | Pass |
| HTML mojibake | `04-html-mojibake.eml` | Fail: `AÃ§Ã£o` / `validaÃ§Ã£o` remained in HTML | Pass |
| Invalid base64 fallback | `05-invalid-base64-fallback.eml` | Pass | Pass with explicit fallback logging |
| UTF-8 BOM | `06-utf8-bom.eml` | Pass in collector fixture | Pass |
| Invisible control bytes | `07-control-bytes-quoted-printable.eml` | Fail: `0x00` and `0x07` remained in content | Pass |
| Double-encoded replacement token | `08-replacement-token-mojibake.eml` | Fail: `ï¿½` remained | Pass |
| Broken UTF-8 bytes | `09-broken-utf8-quoted-printable.eml` | Fail: content was not valid UTF-8 | Pass |

## Raw summaries

Previous core with new evidence tests:

```text
..FF..FFF                                                           9 / 9 (100%)

FAILURES!
Tests: 9, Assertions: 63, Failures: 5.
```

New core, plugin disabled:

```text
.................                                                 17 / 17 (100%)

OK (17 tests, 121 assertions)
```

New core, plugin temporarily enabled:

```text
.................                                                 17 / 17 (100%)

OK (17 tests, 121 assertions)
```

The plugin was returned to `state=0` after the comparison.
