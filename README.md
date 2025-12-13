# BoatNotesPro
BoatNotesPro - Info for public distribution &amp; uploading Notes to user's API


To upload BoatNotes in Real Time:
I have uploaded 4 files to help you get going. Feel free to use as is, or modify for your needs.

1) You may use the files event.sql & events.txt (same file contents, just different formats), to create an initial database table to ingest Events.
2) database.php needs to have 3 fields updated: $db_name, $username & $password
3) uploadEvents.php is the file that should be called by BoatNotesPro app in the Settings Tab. Apple requires file must use be accessed on a secure server (https://)

4) The JSON format of file that is sent:

JSON:  key name & value's data type: 
    id          UUID: (e.g. 40723612-6F3A-475E-8DA8-ADE846734541)
    timestamp   Date: For now, outputs Z (e.g. 2025-01-06 04:11:44.123456)
    latitude    Double: decimal degrees (S are -values) 
    longitude   Double: decimal degrees (W are -values)
    eventType   String: One of standard BoatNotes types (e.g. Comment), or could be custom set by user
    desc        String: Main description field, no length restrictions set
    property    String: Mostly used to provide more context for eventType
    value1      String: Often a numerical value, but not necessarily, so transmitted as String
    value2      String: Not used often, same as value1
    boatName    String
    venueName   String  
    userName    String
