# Ändringslogg

Alla betydande ändringar i detta projekt dokumenteras i denna fil.

Formatet är baserat på [Keep a Changelog](https://keepachangelog.com/sv/1.0.0/),
och detta projekt följer [Semantic Versioning](https://semver.org/lang/sv/).

## [Opublicerat]

### Tillagt
- Initial release av Laravel Swish-paketet
- Komplett implementering av Swish API v2
- Stöd för Payment Requests (betalningsbegäran)
- Stöd för Refunds (återbetalningar)
- Stöd för Payouts (utbetalningar)
- QR-kod generering för desktop-betalningar
- Certificate hantering och validering
- SwishClient för API-kommunikation
- PaymentRequest, Refund och Payout modeller
- Komplett felhantering med custom exceptions
- Laravel Service Provider för enkel integration
- Swish Facade för bekväm användning
- Omfattande dokumentation på svenska
  - README.md - Komplett API-dokumentation
  - INSTALLATION.md - Steg-för-steg installationsguide
  - EXAMPLES.md - Praktiska användningsexempel
- Unit tests för kärnfunktionalitet
- Stöd för PHP 8.1, 8.2, 8.3
- Stöd för Laravel 10 och 11
- MIT-licens

### Planerat för framtida versioner
- Integration med Laravel Events för betalningsstatus
- Stöd för QR-kod caching
- Dashboard för betalningsöversikt
- Webhook verification helpers
- Mer omfattande testsvit
- Feature tests med mockat API
- GitHub Actions CI/CD
- Packagist auto-deployment

## [1.0.0] - TBD

Första stabila versionen kommer släppas efter:
- Community-testning
- Produktionsverifiering
- Security audit
- Performance-optimering

---

## Versioneringsstrategi

Vi följer Semantic Versioning:

- **MAJOR version** (X.0.0) - Inkompatibla API-ändringar
- **MINOR version** (0.X.0) - Ny funktionalitet, bakåtkompatibel
- **PATCH version** (0.0.X) - Bakåtkompatibla buggfixar

## Bidrag

För att föreslå ändringar eller rapportera buggar, öppna ett issue eller pull request på GitHub.
