# Multiple tracker tiers

BioTorrents.de supports two tiers of trackers:

  - Tier 1: Authenticated private tracker URLs using the familiar Gazelle/Ocelot features
  - Tier 2: A list of standalone public trackers as backups, in case the main tracker goes down

Please note that all torrent contents are hashed with the private flag, so the normal protections against DHT, PEX, etc., work as expected.

Support for multiple trackers does not extend to magnet links!