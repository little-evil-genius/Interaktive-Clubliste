# Interaktive-Clubliste 1.0
Dieses Plugin erweitert das Board um eine interaktive Clubliste. Ausgewählte Usergruppen können neue Clubs hinzufügen und Clubs beitreten. Um einen Club hinzufügen zu können, wird ein aussagekräftiger Titel, eine Zeitangabe, eine Beschreibung, ein Leiter und eine Einordnung in eine Kategorie benötigt. Clubs müssen vom Team erst freigeschaltet werden im Mod-CP. Clubs vom Team werden automatisch freigeschaltet. Nach der Freischaltung können User sich mit ihren Accounts als Mitglieder der Clubs eintragen. Sie können sich, wenn nicht anders eingestellt im ACP in so viele Clubs eintragen wollen, wie sie möchten. Ersteller das Clubs und das Team können die Clubs bearbeiten und löschen. Zusätzlich werden die Clubmitgliedschaften im Profil angezeigt, unterteilt in Mitgliedschaften und Leitungspositionen.
Beim Erstellen eines Clubs kann ausgewählt werden, ob dieser Club mit Positionen arbeiten zB Quarterback bei einem Football Team. Diese Position muss dann per Popup-Fenster beim eintreten angegeben werden. 

# Datenbank-Änderungen
Hinzugefügte Tabellen:
- PRÄFIX_clubs
- PRÄFIX_clubs_user

# Neue Templates
- clublist	
- clublist_add	
- clublist_bit	
- clublist_bit_users	
- clublist_edit	
- clublist_filter	
- clublist_join_position	
- clublist_memberprofile	
- clublist_memberprofile_bit	
- clublist_memberprofile_bit_none	
- clublist_memberprofile_conductor_bit	
- clublist_memberprofile_conductor_bit_none	
- clublist_modcp	
- clublist_modcp_bit	
- clublist_modcp_nav

# Template Änderungen - neue Variablen
- member_profile - {$member_profile_clubs}
- header - {$new_club_alert}
- modcp_nav_users - {$nav_clublist}

# ACP-Einstellungen - Clubliste
- Erlaubte Gruppen Hinzufügen
- Erlaubte Gruppen Beitreten
- Kategorien
- Löschfunktion
- Bearbeitungsfunktion
- Begrenzte Mitgliedschaft
- Anzahl der Mitgliedschaften
- Filterfunktion
- Multipage-Navigation
- Anzahl der Clubs (Multipage-Navigation)
- Listen PHP (Navigation Ergänzung)

# Links
- euerforum.de/misc.php?action=clublist
- euerforum.de/modcp.php?action=clublist

# Demo
Clubübersicht<p>
  <img src="https://stormborn.at/plugins/clublist_uebersicht.png" />
  
Maske beim Hinzufügen<p>
  <img src="https://stormborn.at/plugins/clublist_add.png" />
  
Team-Alert auf dem Index<p>
  <img src="https://stormborn.at/plugins/clublist_alert.png" />
  
Mod-CP<p>
  <img src="https://stormborn.at/plugins/clublist_modcp.png" />
  
Club beitreten mit Position<p>
  <img src="https://stormborn.at/plugins/clublist_position.png" />
  
Club bearbeiten<p>
  <img src="https://stormborn.at/plugins/clublist_edit.png" />
  
Clubs im Profil<p>
  <img src="https://stormborn.at/plugins/clublist_profil.png" />
  <img src="https://stormborn.at/plugins/clublist_profilnone.png" />

Alerts Beanachrichtigung<p>
  <img src="https://stormborn.at/plugins/clublist_alerts.png" />

# Support
Wie viele von euch wissen, bin ich noch kein wirklicher Profi und habe auch noch nicht allzu viele Plugins geschrieben, somit ist teilweise mein Wissen auch begrenzt und ich weiß nicht immer sofort eine Lösung. 
Aber ich versuche mein bestes, auch wenn es manchmal etwas langsamer vorangeht. Ich hab auch kein Problem, wenn jemand anderes Support gibt und somit hilft.
