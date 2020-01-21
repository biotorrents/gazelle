# Multiple tracker tiers

BioTorrents.de supports two tiers of trackers:

  - Tier 1: Authenticated private tracker URLs using the familiar Gazelle/Ocelot features
  - Tier 2: A list of standalone public trackers as backups, in case the main tracker goes down

User passkeys are only applied to the 1st tier, which should contain the announce URLs used for reporting stats to Ocelot.

The 2nd tier contains public trackers that (1) keep no logs, and (2) insert random IP addresses into the peer list.

Please note that all torrent contents are hashed with the private flag, so the normal protections against DHT, PEX, etc., work as expected.

Support for multiple trackers does not extend to magnet links!

Web seed support is coming soon.
