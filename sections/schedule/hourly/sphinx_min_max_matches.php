<?
$SphQL = new SphinxqlQuery();
$SphQL->where_match('_all', 'fake', false);
$SphQL->select('id')->from('torrents, delta')->limit(0,0,10000);
$TTorrents = $SphQL->query()->get_meta('total_found');

$SphQL->select('groupid')->group_by('groupid')$SphQL->from('torrents, delta')->limit(0,0,10000);
$TGroups = $SphQL->query()->get_meta('total_found');

$Cache->cache_value('sphinx_min_max_matches', 2*($TTorrents-$TGroups));
?>
