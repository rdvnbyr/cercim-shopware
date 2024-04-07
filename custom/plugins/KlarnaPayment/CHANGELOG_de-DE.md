# 1.12.0
- Speicherung von Custom Fields an Bestelladressen für Shops unter 6.4.14.0 hinzugefügt
- Ergänzung einer Integration mit Billie zur Handhabung von B2B Bestellungen
- Die Klarna-Rechnungsadresse wird nun beim Bestellabschluss zu Shopware synchronisiert. Änderungen an der Rechnungsadresse in Shopware werden nun nicht mehr mit Klarna synchronisiert.

# 1.11.0
- Ergänzung des Autorisierungs-Callback
- Korrektur der Einschränkung auf eine Zahlungsart bei Verwendung von Klarna Express
- Änderung an dem Veröffentlichungsprozess zur Erstellung einer Plugin-Version, die mit Shopware 6.4 und Shopware 6.5 kompatibel ist

# 1.10.0
- Überprüfung auf ausgewähltes Klarna Express eingebaut, bevor automatisch eine Klarna-Zahlungsmethode ausgewählt wird
- Korrektur der Rundung von Beträgen
- Korrektur der Überschreibung der Beschreibung von Zahlungsarten
- Korrektur der Darstellung von individuellen Bildern für Zahlungsarten
- Korrektur der Hinzufügung der Cookies in der erforderlichen Gruppe
- Korrektur der Handhabung, wenn die Zahlungsart einer Bestellung geändert wird
- Nutzung von Custom Fields bei Bestellungen auf dedizierte EntityExtension umgestellt, um Probleme mit anderen Plugins zu vermeiden
- Anpassung der Synchronisierung mit Klarna, damit Bestellpositionen nicht mehr synchronisiert werden, sobald die Bestellung erfasst wurde

# 1.9.0
- Kompatibilität zu Shopware 6.5.0.0 hergestellt
- Anpassung der angezeigten Zahlungsartnamen im Checkout

# 1.8.0
- Übersetzung der Fehlermeldung bei Bestellanpassung in der Administration behoben
- Produktvarianteninformation im Modal für Erfassung und Rückerstattung hinzugefügt
- Fehler beim Schließen vom Detailmodal der Bestellhistorie korrigiert
- Codeoptimierungen um mögliche Fehler zu reduzieren
- Umgebungsvariable `APP_SECRET` via Symfony Service Definition in jeweiligen Services injected, um die LogicException im Checkout zu beheben.
- Kompatibilität für USA hinzugefügt
- Überschreibung der Administrations-Komponente `sw-order-detail-base` für weitere Plugins korrigiert

# 1.7.0
- Login via Klarna Express im Warenkorb ermöglicht
- Ergänzung der Session-Aktualisierung um die Session-Erstellung mit Klarna zu reduzieren
- Korrektur der Validierung der AGB-Checkbox im Checkout

# 1.6.3
- Hinzufügen einer Prüfung, ob die AGB im Checkout akzeptiert wurden

# 1.6.2
- Kompatibilität zum CSRF-Modus "ajax" hergestellt

# 1.6.1
- Kompatibilität zu Shopware 6.4.10.0 hergestellt

# 1.6.0
- Kompatibilität zu NetiNextEasyCoupon hinzugefügt. Gutscheine werden jetzt als Geschenkgutscheine an Klarna übertragen.
- Anpassung der Minimalversion zu Shopware 6.3.0.0
- Hinweis zur Vererbung der Plugin-Konfiguration für Shopware-Versionen unter 6.3.4.0 hinzugefügt
- Korrektur der De- und Aktivierung des Buttons zum Bestellabschluss mit Klarna Zahlungsarten
- Korrektur des Statuswechsels von `Autorisiert` zu `Bezahlt` bei manueller Erfassung
- Entfernen des Regel-Installers, da Kundenspezifische Regeln überschrieben wurden
- Entfernen der expliziten Einschränkungen der Zahlungsmethoden
- Korrektur der Cover-URL für Bestell-Anfragen zur Klarna API
- Hinzufügen der Produkt-URL für Bestell-Anfragen zur Klarna API 
- Korrektur der angezeigten Zahlungsarten basierend auf der Antwort der Klarna API

# 1.5.0
- Sprachdateien an neue Position verschoben
- Irland als verfügbares Land hinzugefügt
- Darstellung der Vererbung der Plugin-Konfiguration optimiert
- Wechsel des Zahlungsstatus auf Authorisiert, falls die Zahlung von Klarna authorisiert wurde
- Verwenden des ausgewählten Versandartnamens für die Bestellposition der Klarna Bestellung
- Unterstützung von Shopware Custom Products hinzugefügt

# 1.4.4
- Behebung eines Fehlers mit Multi-Steuer Gutscheinen
- Behebung von Problemen, wenn das Zahlungsabschluss Modal geschlossen wird.
- Das Klarna Widget wird im Checkout nun über der Positionstabelle dargestellt, wenn mindestens Shopware 6.4 verwendet wird
- Korrektur des Buttons zum Bestellabschluss wenn das Klarna Modal geschlossen wird
- Behebung von ESLint Fehlermeldung mit Shopware 6.4.5.x

# 1.4.3
- Behebung der Deinstallation des Plugins, wenn die gespeicherten Daten gelöscht werden sollen

# 1.4.2
- Unterstützung von PHP 8 hinzugefügt
- Anpassung der Warenkorb-Hash-Logik zur Verifikation von nachträglichen Bestellanpassungen

# 1.4.1
- Kompatibilität zu Shopware 6.4 hergestellt

# 1.4.0
- Instant Shopping entfernt.
- Übersetzungen der Zahlungsarten hinzugefügt.
- Behebung eines Fehlers im Internet Explorer der einen Bestellabschluss verhinderte

# 1.3.8
- Die Regel für Klarna Zahlungsarten im Rule-Builder wurde entfernt.
- Italien als unterstütztes Land ergänzt. Hinweis: Instant Shopping ist nicht für Italien verfügbar.
- Behebung eines seltenen Fehlers durch ungültige Werte bei der Speicherung der Pluginkonfiguration.
- Eine Bestellung wird nur noch automatisch eingezogen, wenn die Betrugsprüfung erfolgreich war.
- Der automatische Einzug ist nun auch mit dem Bestell- und Lieferstatus offen möglich.

# 1.3.7
- Behebung von Fehlern in der Pluginkonfiguration
- Bitte beachten Sie: Ein Fehlverhalten bei vererbten Konfigurationswerten ist bekannt und in der Dokumentation näher beschrieben.

# 1.3.6
- Optimierung der Fehlerbehandlung bei der Verwendung von Instant Shopping
- Korrektur der fehlenden Bezeichnungen in der Plugin-Konfiguration
- Hinweis in der Dokumentation (https://klarna.kellerkinder.de/de/1-3/index.html#h-uebersetzung-der-zahlarten) zur Übersetzung von Zahlungsarten ergänzt.

# 1.3.5
- Ergänzung eines Standardnamens für die Zahlungsarten für die Installation unter einer anderen Systemsprache

# 1.3.4
- Korrektur der Handhabung von Gutscheinen im Warenkorb

# 1.3.3
- Korrektur des automatischen Packens des ZIP-Archiv um kompilierte Dateien wieder zu ergänzen
- Korrektur der Verwendung einer falschen Exception-Klasse

# 1.3.2
- Korrektur der Darstellung vom Instant Shopping Button
- Korrektur eines Fehlers bei der Aktualisierung der Bestellung wenn die Bestellung bereits eingezogen wurde
- Optimierung der Performance im Checkout und beim Aktualisieren der Bestellung

# 1.3.1
- Korrektur der Selektierung einer Bestellung aus den Suchergebnissen

# 1.3.0
- Sprache und Währung zur Verfügbarkeitsregel von Klarna Zahlarten hinzugefügt
- Übermittlung von Informationen zur Versandverfolgung an das Klarna Händlerportal  
- Korrektur der Installation, wenn eine Sprache nicht gefunden werden konnte
- Korrektur von Instant Shopping für Gäste bei konfigurierten erforderlichen Einstellungen für die Registrierung
- Optimierung der Validierung von API Zugangsdaten

# 1.2.1
- Korrektur der Anzeige von Lieferinformationen auf Produktdetailseiten
- Korrektur von Bestellaktualisierungen in der Administration mit anderen Zahlungsmethoden

# 1.2.0
- Kompatibilität zu Shopware 6.2 hergestellt

# 1.1.0
- Implementierung von Klarna Instant Shopping
- Unterstützung von reinen Nettopreisen hinzugefügt (ab Shopware 6.2.0)

# 1.0.4
- Korrektur des Bestellabschluss-Buttons für andere Zahlungsmethoden

# 1.0.3
- Korrektur des Namens der Rechnungsadresse in der Klarna Payment Session

# 1.0.2
- Kombinierte Pay Now-Zahlungsart und Kreditkarte ergänzt
- Bestellanpassungen in der Administration werden durch den Versions-Manager nun vor dem Speichern mit Klarna abgeglichen
- Anpassung an den Adressübergaben
- Zahlungskategorien können nun in der Pluginkonfiguration deaktiviert werden
- Abgleich von Bestellungsanpassungen während dem Bestellprozess werden nun verhindert, solange die Bestellung noch nicht bei Klarna angelegt wurde

# 1.0.1
- Rundungsfehler bei Klarna-Aufrufen behoben

# 1.0.0
- Erste Version der Klarna Payment Integration für Shopware 6.1
