# Wettervorhersage-Block

Ein WordPress-Plugin, das einen Wettervorhersage-Block für den Gutenberg-Editor bereitstellt. Es verwendet die OpenWeatherMap-API, um aktuelle Wetterdaten sowie eine 5-Tage-Vorhersage für einen angegebenen Standort anzuzeigen.

## Funktionen

- **Aktuelles Wetter**: Zeigt die aktuelle Temperatur, gefühlte Temperatur und Wetterbeschreibung an (approximiert aus der frühesten Vorhersage).
- **5-Tage-Vorhersage**: Liefert Tageshöchst- und -tiefsttemperaturen, Niederschlagswahrscheinlichkeit und Wettericons für bis zu 5 Tage.
- **Anpassbar**: Standort und Icon-Farbe können direkt in den Blockeinstellungen im Gutenberg-Editor angepasst werden.
- **Caching**: Wetterdaten werden für eine Stunde zwischengespeichert, um API-Aufrufe zu minimieren.
- **Deutsche Lokalisierung**: Wetterbeschreibungen und Tagesnamen werden auf Deutsch angezeigt; bereit für i18n.

## Voraussetzungen

- WordPress 5.0 oder höher (für Gutenberg-Unterstützung)
- Ein gültiger [OpenWeatherMap-API-Schlüssel](https://openweathermap.org/api) (kostenlos für Basisnutzung verfügbar – einfach registrieren, dann erhält man einen API Key)

## Installation

1. Lade das Plugin als ZIP-Datei herunter oder klone das Repository:
   ```
   git clone https://github.com/[dein-username]/wettervorhersage-block.git
   ```
2. Die .zip einfach als neues Plugin in WordPress hochladen.
3. Aktiviere das Plugin im WordPress-Adminbereich unter **Plugins**.
4. Erstelle eine neue Constant in deiner wp-config.php, um dort deinen OpenWeatherMap-API-Schlüssel zu hinterlegen nach dem Schema:
   ```
   define('OPENWEATHERMAP_API_KEY', '123456789123456789');
   ```
5. Füge den „Wettervorhersage-Block“ in einem Beitrag oder einer Seite über den Gutenberg-Editor hinzu.

## Konfiguration

- **Standort**: Standardmäßig auf „London“ gesetzt. Du kannst entweder einen Städtenamen (z. B. „Berlin“) oder eine OpenWeatherMap-Stadt-ID verwenden und diese einfach im Block Editor in der Sidebar des Wetter-Blocks eingeben.
- **API-Schlüssel**: Erforderlich für API-Aufrufe. Ohne gültigen Schlüssel wird eine Fehlermeldung angezeigt und der Block ist auch nicht verfügbar.

## Dateistruktur

- `weather-forecast-block.php`: Hauptdatei des Plugins mit PHP-Logik und serverseitigem Rendering.
- `js/weather-block.js`: JavaScript für den Block-Editor.
- `css/style.css`: CSS-Styling für Frontend und Editor (eine .scss Datei liegt ebenfalls bereit, um Anpassungen übersichtlicher vorzunehmen => diese wurde nur kompiliert zur finalen .css)
- `icons/`: Verzeichnis mit SVG-Wettericons (z. B. `01d.svg`, `02n.svg`).

## Entwicklung

### Block-Attribute

- `location` (string): Der Standort für die Wetterdaten (Standard: „London“).
- `iconColor` (string): Die bevorzugte Icon-Farbe (Standard: #000).

### API-Aufrufe

Das Plugin verwendet die [OpenWeatherMap 5-Tage-Vorhersage-API](https://openweathermap.org/forecast5). Daten werden in metrischen Einheiten (Celsius) und auf Deutsch abgerufen.

### Anpassen

- **Icons**: Füge eigene SVG-Icons im Ordner `icons/` hinzu, passend zu den OpenWeatherMap-Icon-Codes (z. B. `10d.svg`).
- **Styling**: Bearbeite `style.css`, um das Erscheinungsbild anzupassen; alternativ können auch einige der CSS Variablen im eigenen Stylesheet überschrieben werden, um den Look anzupassen.

## Fehlerbehebung

- **„Ungültiger API-Schlüssel“**: Überprüfe deinen API-Schlüssel in den Blockeinstellungen.
- **„Keine Wetterdaten verfügbar“**: Stelle sicher, dass der Standort korrekt ist und die API erreichbar ist.
- **Debugging**: Entkommentiere die Debugging-Ausgabe in `wettervorhersage-block.php`, um API-URLs und Rohdaten anzuzeigen (nur für Administratoren sichtbar).

## Lizenz

Dieses Projekt ist unter der [GPLv2 oder höher](https://www.gnu.org/licenses/gpl-2.0.html) lizenziert, wie es für WordPress-Plugins üblich ist.

## Mitwirken

Beiträge sind willkommen! Erstelle einen Fork, bearbeite den Code und reiche einen Pull Request ein. Issues können im GitHub-Issue-Tracker gemeldet werden.

## Autor

Entwickelt von CEATE.
Weiterentwickelt von nexTab.

---

This README provides all essential information for users and developers, including installation instructions, configuration details, and a basic structure for contributions. You can customize it further by adding your GitHub username, specific contribution guidelines, or additional sections like „Bekannte Probleme“ if needed. Let me know if you’d like adjustments!
