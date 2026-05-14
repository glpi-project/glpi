# Mail Collector charset normalization comparison

This evidence was produced with the same `tests/imap/MailCollectorTest.php::testBuildTicketNormalizesCharsetEvidenceMails`
test, the same `.eml` fixtures, and a dedicated followup test using an existing test user.

## Commands

Before the implementation, only the test and fixtures were applied on top of the previous core code:

```bash
php vendor/bin/phpunit -c phpunit.xml.dist tests/imap/MailCollectorTest.php --filter '/(testBuildTicketNormalizesCharsetEvidenceMails|testBuildTicketNormalizesCharsetEvidenceFollowup)/'
```

After the implementation:

```bash
php vendor/bin/phpunit -c phpunit.xml.dist tests/functional/Glpi/Mail/ImportedMailContentSanitizerTest.php tests/imap/MailCollectorTest.php --filter '/(testSanitizeImportedMailContent|testInvalidUtf8BytesNeverEscapeSanitizer|testBuildTicketNormalizesCharsetEvidenceMails|testBuildTicketNormalizesCharsetEvidenceFollowup)/'
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
| Followup content | Existing ticket reference with `_test_user@glpi.com` | Fail: `AÃ§Ã£o nÃ£o concluÃ­da` remained in followup content | Pass |

## Raw summaries

Previous core with new evidence tests:

```text
..FF..FFFF                                                        10 / 10 (100%)

FAILURES!
Tests: 10, Assertions: 70, Failures: 6.
```

New core, plugin disabled:

```text
..................                                                18 / 18 (100%)

OK (18 tests, 129 assertions)
```

New core, plugin temporarily enabled:

```text
..................                                                18 / 18 (100%)

OK (18 tests, 129 assertions)
```

The plugin was returned to `state=0` after the comparison.
