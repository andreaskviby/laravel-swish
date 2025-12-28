# Bidra till Laravel Swish

Tack för att du är intresserad av att bidra till Laravel Swish! Detta dokument beskriver hur du kan hjälpa till att förbättra projektet.

## Innehållsförteckning

- [Uppförandekod](#uppförandekod)
- [Hur kan jag bidra?](#hur-kan-jag-bidra)
- [Rapportera buggar](#rapportera-buggar)
- [Föreslå nya funktioner](#föreslå-nya-funktioner)
- [Pull Requests](#pull-requests)
- [Kodstil](#kodstil)
- [Testning](#testning)

## Uppförandekod

Detta projekt och alla som deltar i det styrs av principen om respektfull kommunikation. Genom att delta förväntas du upprätthålla denna standard.

## Hur kan jag bidra?

Det finns många sätt att bidra till Laravel Swish:

- 🐛 Rapportera buggar
- 💡 Föreslå nya funktioner
- 📝 Förbättra dokumentation
- 🧪 Skriva tester
- 💻 Implementera nya funktioner
- 🌐 Översätta dokumentation
- ⭐ Ge projektet en stjärna på GitHub

## Rapportera buggar

### Innan du rapporterar en bugg

1. **Kontrollera dokumentationen** - Kanske är det inte en bugg utan avsiktligt beteende
2. **Sök bland befintliga issues** - Någon annan kanske redan har rapporterat problemet
3. **Använd senaste versionen** - Kontrollera om buggen är fixad i en nyare version

### Hur rapporterar jag en bugg?

Skapa ett nytt issue på GitHub med följande information:

```markdown
**Beskrivning av buggen**
En tydlig och koncis beskrivning av vad buggen är.

**Steg för att återskapa**
1. Gå till '...'
2. Klicka på '...'
3. Se felet

**Förväntat beteende**
En tydlig beskrivning av vad du förväntade dig skulle hända.

**Faktiskt beteende**
Vad som faktiskt hände.

**Skärmdumpar**
Om tillämpligt, lägg till skärmdumpar för att förklara problemet.

**Miljö:**
- OS: [t.ex. Ubuntu 22.04]
- PHP Version: [t.ex. 8.2.0]
- Laravel Version: [t.ex. 10.0]
- Paket Version: [t.ex. 1.0.0]

**Ytterligare kontext**
Lägg till annan relevant information om problemet här.

**Felmeddelanden och stack traces**
```
[Klistra in eventuella felmeddelanden här]
```
```

## Föreslå nya funktioner

Vi välkomnar förslag på nya funktioner! Innan du skapar en feature request:

1. **Kontrollera att funktionen inte redan finns**
2. **Sök bland befintliga feature requests**
3. **Fundera på om funktionen passar projektet**

### Hur föreslår jag en ny funktion?

Skapa ett issue med följande struktur:

```markdown
**Är din feature request relaterad till ett problem?**
En tydlig beskrivning av problemet. T.ex. "Jag blir frustrerad när..."

**Beskriv lösningen du vill ha**
En tydlig beskrivning av vad du vill ska hända.

**Beskriv alternativ du har övervägt**
En tydlig beskrivning av alternativa lösningar eller funktioner du har övervägt.

**Användarfall**
Beskriv konkreta användarfall där denna funktion skulle vara användbar.

**Ytterligare kontext**
Lägg till annan relevant information eller skärmdumpar om feature requesten här.
```

## Pull Requests

### Process för Pull Requests

1. **Forka repositoryt**
2. **Skapa en branch** från `main`:
   ```bash
   git checkout -b feature/min-nya-funktion
   ```
3. **Gör dina ändringar**
4. **Lägg till tester** för din nya funktionalitet
5. **Kör testsviten** för att säkerställa att allt fungerar
6. **Commit dina ändringar** med tydliga commit-meddelanden
7. **Push till din fork**
8. **Öppna en Pull Request**

### Riktlinjer för Pull Requests

- **En funktion per PR** - Håll PRs fokuserade på en specifik ändring
- **Tydlig beskrivning** - Beskriv vad ändringen gör och varför
- **Tester** - Inkludera tester för nya funktioner
- **Dokumentation** - Uppdatera dokumentation om nödvändigt
- **Kodstil** - Följ projektets kodstil (se nedan)
- **Commit-meddelanden** - Skriv tydliga och beskrivande commits

### Pull Request mall

```markdown
## Beskrivning
[Beskriv dina ändringar här]

## Typ av ändring
- [ ] Buggfix (icke-breaking change som fixar ett issue)
- [ ] Ny funktion (icke-breaking change som lägger till funktionalitet)
- [ ] Breaking change (fix eller funktion som skulle orsaka befintlig funktionalitet att inte fungera som förväntat)
- [ ] Dokumentationsuppdatering

## Hur har detta testats?
[Beskriv hur du har testat dina ändringar]

## Checklista
- [ ] Min kod följer projektets kodstil
- [ ] Jag har gjort en själv-review av min kod
- [ ] Jag har kommenterat min kod, särskilt i svåra områden
- [ ] Jag har uppdaterat dokumentationen
- [ ] Mina ändringar genererar inga nya varningar
- [ ] Jag har lagt till tester som visar att min fix är effektiv eller att min funktion fungerar
- [ ] Nya och befintliga unit tests passerar lokalt med mina ändringar
- [ ] Eventuella beroende ändringar har mergats och publicerats i downstream moduler

## Relaterade issues
[Länka till relaterade issues här, t.ex. "Fixes #123"]
```

## Kodstil

Vi följer [PSR-12](https://www.php-fig.org/psr/psr-12/) kodstandard.

### Viktiga punkter

- **Indrag:** 4 mellanslag (inte tabs)
- **Radlängd:** Max 120 tecken (föredrar 80)
- **Namespace:** Ett namespace per fil
- **Use statements:** Alfabetisk ordning, grupperade
- **Klasser:** En klass per fil, PascalCase
- **Metoder:** camelCase
- **Konstanter:** UPPER_CASE
- **Kommentarer:** DocBlocks för alla klasser och publika metoder

### Exempel

```php
<?php

namespace AndreasKviby\LaravelSwish\Models;

use AndreasKviby\LaravelSwish\Exceptions\ValidationException;
use Ramsey\Uuid\Uuid;

/**
 * Representerar en Swish-betalningsbegäran
 */
class PaymentRequest
{
    /**
     * @var string Unikt ID för betalningen
     */
    public string $id;

    /**
     * Skapa en ny betalningsbegäran
     *
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        // Implementation
    }

    /**
     * Validera betalningsbegäran
     *
     * @throws ValidationException
     */
    public function validate(): void
    {
        // Implementation
    }
}
```

### Kodformatering

Du kan använda PHP CS Fixer för att automatiskt formatera kod:

```bash
composer require --dev friendsofphp/php-cs-fixer

./vendor/bin/php-cs-fixer fix
```

## Testning

### Köra tester

```bash
# Alla tester
./vendor/bin/phpunit

# Med testdox (läsbar output)
./vendor/bin/phpunit --testdox

# Specifik test
./vendor/bin/phpunit --filter PaymentRequestTest

# Med coverage
./vendor/bin/phpunit --coverage-html coverage
```

### Skriva tester

- **Placering:** Tester i `tests/Unit/` eller `tests/Feature/`
- **Namngivning:** Testfiler ska sluta med `Test.php`
- **Metoder:** Testmetoder ska börja med `test_`
- **Assertions:** Använd beskrivande assertions

#### Exempel på test

```php
<?php

namespace AndreasKviby\LaravelSwish\Tests\Unit;

use AndreasKviby\LaravelSwish\Models\PaymentRequest;
use AndreasKviby\LaravelSwish\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class PaymentRequestTest extends TestCase
{
    public function test_validates_required_fields()
    {
        $this->expectException(ValidationException::class);
        
        $payment = new PaymentRequest([
            'amount' => '100.00',
            // saknar obligatoriskt fält
        ]);
        
        $payment->validate();
    }
    
    public function test_can_convert_to_array()
    {
        $payment = new PaymentRequest([
            'payeeAlias' => '1234679304',
            'amount' => '100.00',
            'message' => 'Test',
            'callbackUrl' => 'https://example.com/callback',
        ]);
        
        $array = $payment->toArray();
        
        $this->assertArrayHasKey('payeeAlias', $array);
        $this->assertEquals('1234679304', $array['payeeAlias']);
    }
}
```

## Dokumentation

När du lägger till ny funktionalitet, uppdatera relevant dokumentation:

- **README.md** - Huvuddokumentation
- **EXAMPLES.md** - Praktiska exempel
- **INSTALLATION.md** - Installationsinstruktioner
- **CHANGELOG.md** - Lägg till under "Opublicerat"
- **Inline-kommentarer** - DocBlocks för nya klasser/metoder

### Dokumentationsstil

- **Språk:** Svenska (för användarriktad dokumentation)
- **Ton:** Professionell men tillgänglig
- **Format:** Markdown
- **Exempel:** Inkludera kodexempel där det är relevant
- **Tydlighet:** Förklara både vad och varför

## Frågor?

Om du har frågor om att bidra, öppna gärna ett issue eller kontakta maintainers.

## Erkännanden

Tack till alla som bidrar till att göra Laravel Swish bättre! ❤️

---

**Kom ihåg:** Alla bidrag, stora som små, är värdefulla. Vi uppskattar din tid och ansträngning!
