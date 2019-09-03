# HUEDevice
   Dieses Modul bildet die verschiedenen HUE Geräte in IP-Symcon ab.
   Darunter zählen zum Beispiel Sensoren, Lichter, Schalter und Gruppen.
     
   ## Inhaltverzeichnis
   1. [Konfiguration](#1-konfiguration)
   2. [Funktionen](#2-funktionen)
   
   ## 1. Konfiguration
   
   Feld | Beschreibung
   ------------ | ----------------
   HUE Device ID | Hier wird das Topic (shelly1-deviceid) des Shelly1 eingetragen. Dazu muss zur Zeit die folgende URL aufgerufen werden: http://ShellyIP/settings dort ist derHostname zu finden. Der Hostname ist die DeviceID!
   Geräte Typ | Auswhal zwischen Licht, Sensor/Schalter und Gruppe
   Sensor Typ | Nur sichtbar, wenn als Geräte Typ Sensor ausgewählt wurde,hier kann der Typ des Sensors ausgewählt werden.

  ## 2. Funktionen
   
   **PHUE_SwitchMode($InstanceID, $Value)**\
   Mit dieser Funktion ist es möglich das Gerät ein- bzw. auszuschalten.
   ```php
   Shelly_SwitchMode(25537, true); //Einschalten
   Shelly_SwitchMode(25537, false); //Ausschalten
   ```

   **PHUE_DimSet($InstanceID, $Value)**\
   Mit dieser Funktion ist es möglich das Gerät bzw. die Gruppe zu dimmen.
   ```php
   PHUE_DimSet(25537, 50); //auf 50% dimmen
   ```
   
   **PHUE_ColorSet($InstanceID, $Value)**\
   Mit dieser Funktion ist es möglich die Farbe der Lampe bzw. der Gruppe zu ändern. Der Wert wird in Hex angegeben werden.
   ```php
   PHUE_ColorSet(25537, '#FF0000'); //Farbe Rot
   ```

   **PHUE_SatSet($InstanceID, $Value)**\
   Mit dieser Funktion ist es möglich die Sättigung der Lampe bzw. der Gruppe zu ändern. Der Wert wird in Integer angegeben werden.
   ```php
   PHUE_SatSet(25537, 50); //Sättigung 50
   ```

   **PHUE_CTSet($InstanceID, $Value)**\
   Mit dieser Funktion ist es möglich die Farbtemperatur der Lampe bzw. der Gruppe zu ändern. Der Wert wird in Integer angegeben werden.
   ```php
   PHUE_CTSet(25537, 366); //Farbtemperatur 366
   ```

   **PHUE_SceneSet($InstanceID, $Value)**\
   Mit dieser Funktion ist es möglich eine Szene für die Gruppe zu aktiveren.
   ```php
   PHUE_SceneSet(25537, 'ID der Szene');
   ```

   **PHUE_AlertSet($InstanceID, $Value)**\
   Mit dieser Funktion ist es möglich einen Alarm für die Lampe bzw. Gruppe zu aktiveren.
   ```php
   PHUE_AlertSet(25537, 'alert');
   ```

   **PHUE_EffectSet($InstanceID, $Value)**\
   Mit dieser Funktion ist es möglich einen Effekt für die Lampe bzw. Gruppe zu aktiveren.
   ```php
   PHUE_EffectSet(25537, 'colorloop'); //Effekt colorloop
   ```