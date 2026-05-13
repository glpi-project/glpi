# Mail Collector charset evidence

These fixtures document reproducible mail import cases that previously depended on plugin-side correction.

| Fixture | Scenario | Expected normalized result |
| --- | --- | --- |
| `01-latin1-quoted-printable.eml` | ISO-8859-1 body and encoded subject | Accents are converted to valid UTF-8. |
| `02-windows1252-quoted-printable.eml` | Windows-1252 punctuation and currency | Smart quotes, dash and euro are preserved. |
| `03-utf8-mojibake.eml` | Valid UTF-8 mojibake text | Portuguese accents are repaired. |
| `04-html-mojibake.eml` | HTML body with mojibake | HTML tags and entities are preserved while text is repaired. |
| `05-invalid-base64-fallback.eml` | Invalid base64 transfer encoding | Import keeps processing through a non-strict fallback. |

The fixtures are intentionally independent from any plugin hook so they validate the core mail collector path.
