function DisplayTrans() {
  if (['Softsubs','Hardsubs'].indexOf($('select[name="sub"]').raw().value) != -1) {
    $('#subber').raw().parentNode.parentNode.style.display = 'table-row'
  } else {
    $('#subber').raw().parentNode.parentNode.style.display = 'none'
  }
}

function Categories() {
  let def = ['title', 'title_rj', 'title_jp', 'year', 'lang', 'censored', 'tags', 'cover', 'group_desc', 'release_desc', 'anon']
  let cats = [{
    'javdb': {},
    'idols': {name: 'Idol(s)'},
    'studio': {name: 'Studio'},
    'series': {name: 'Series'},
    'media': {},
    'container': {},
    'codec': {},
    'resolution': {},
    'audio': {},
    'sub': {},
    'mediainfo': {},
    'screenshots': {name: 'Screenshots'},
    'group_desc': {notes: 'Contains information such as a description of the movie, a link to a JAV catalogue, etc.'},
    'release_desc': {notes: 'Contains information such as encoder settings or watermarks'}
  }, {
    'anidb': {},
    'idols': {name: 'Artist/Studio'},
    'studio': false,
    'series': {name: 'Circle (Optional)'},
    'media': {},
    'container': {},
    'codec': {},
    'resolution': {},
    'audio': {},
    'sub': {},
    'trans': {name: 'Translation Group (optional)'},
    'mediainfo': {},
    'tags': {notes: 'Remember to use the \'3d\' tag if your upload is 3DCG!'},
    'screenshots': {name: 'Screenshots'},
    'group_desc': {notes: 'Contains information such as a description of the anime, a link to AniDB, etc.'},
    'release_desc': {notes: 'Contains information such as encoder settings or episode source differences'}
  }, {
    'ehentai': {},
    'idols': {name: 'Artist'},
    'studio': {name: 'Publisher (Optional)'},
    'series': {name: 'Circle (Optional)'},
    'pages': {},
    'media_manga': {},
    'archive_manga': {},
    'trans': {name: 'Translation Group (optional)'},
    'screenshots': {name: 'Samples'},
    'group_desc': {notes: 'Contains information such as a description of the doujin.'},
    'release_desc': {notes: 'Contains information such as formatting information.'}
  }, {
    'idols': {name: 'Developer'},
    'series': {name: 'Circle (Optional)'},
    'studio': {name: 'Publisher (Optional)'},
    'dlsite': {},
    'media_games': {},
    'container_games': {},
    'archive': {},
    'trans': {name: 'Translation/Release Group (optional)'},
    'tags': {notes: 'Tags you should consider, if appropriate: <strong>visual.novel</strong>, <strong>nukige</strong>'},
    'screenshots': {name: 'Screenshots', notes: '<strong class="important_text">Promotional materials from a game\'s store page are NOT screenshots</strong>'},
    'group_desc': {notes: 'Contains information such as a description of the game, its mechanics, etc.'},
    'release_desc': {notes: 'Contains information such as <strong>version</strong>, install instructions, patching instructions, etc.'}
  }, {
    'idols': {name: 'Creators/Authors (Optional)'},
    'studio': {name: 'Publisher (Optional)'},
    'year': false,
    'lang': false,
    'dlsite': {},
    'screenshots': {name: 'Screenshots'},
    'release_desc': false
  }]
  let active = {}
  for (let field of def) active[field] = {}
  let category = 0
  if ($('input[name="type"]').raw()) category = $('input[name="type"]').raw().value
  if ($('#categories').raw()) category = $('#categories').raw().value
  active = Object.assign(active, cats[category])

  let hide = el => {
    Array.from($(`#${el.id} input, #${el.id} select, #${el.id} textarea`)).forEach(inp => inp.disabled = true)
    $(el).ghide()
  }
  let show = el => {
    Array.from($(`#${el.id} input, #${el.id} select, #${el.id} textarea`)).forEach(inp => inp.disabled = false)
    $(el).gshow()
  }

  let trs = $('#dynamic_form tr')
  for (let tr of trs) {
    let field = tr.id.slice(0,-3)
    if (active[field]) {
      if (active[field].name) {
        tr.children[0].innerHTML = active[field].name
      }
      let notes = $(`#${tr.id} p.notes`).raw()
      if (notes) notes.innerHTML = active[field].notes||''
      show(tr)
    } else {
      hide(tr)
    }
  }
}

function Bitrate() {
  $('#other_bitrate').raw().value = '';
  if ($('#bitrate').raw().options[$('#bitrate').raw().selectedIndex].value == 'Other') {
    $('#other_bitrate_span').gshow();
  } else {
    $('#other_bitrate_span').ghide();
  }
}

function AltBitrate() {
  if ($('#other_bitrate').raw().value >= 320) {
    $('#vbr').raw().disabled = true;
    $('#vbr').raw().checked = false;
  } else {
    $('#vbr').raw().disabled = false;
  }
}

function add_tag() {
  if ($('#tags').raw().value == "") {
    $('#tags').raw().value = $('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value;
  } else if ($('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value == '---') {
  } else {
    $('#tags').raw().value = $('#tags').raw().value + ', ' + $('#genre_tags').raw().options[$('#genre_tags').raw().selectedIndex].value;
  }
}

var LogCount = 1;

function AddLogField() {
  if (LogCount >= 200) {
    return;
  }
  var LogField = document.createElement("input");
  LogField.type = "file";
  LogField.id = "file";
  LogField.name = "logfiles[]";
  LogField.size = 50;
  var x = $('#logfields').raw();
  x.appendChild(document.createElement("br"));
  x.appendChild(LogField);
  LogCount++;
}

function RemoveLogField() {
  if (LogCount == 1) {
    return;
  }
  var x = $('#logfields').raw();
  for (i = 0; i < 2; i++) {
    x.removeChild(x.lastChild);
  }
  LogCount--;
}

var ExtraLogCount = 1;

function AddExtraLogField(id) {
  if (LogCount >= 200) {
    return;
  }
  var LogField = document.createElement("input");
  LogField.type = "file";
  LogField.id = "file_" + id;
  LogField.name = "logfiles_" + id + "[]";
  LogField.size = 50;
  var x = $('#logfields_' + id).raw();
  x.appendChild(document.createElement("br"));
  x.appendChild(LogField);
  LogCount++;
}

function RemoveLogField() {
  if (LogCount == 1) {
    return;
  }
  var x = $('#logfields').raw();
  for (i = 0; i < 2; i++) {
    x.removeChild(x.lastChild);
  }
  LogCount--;
}

var FormatCount = 0;

function AddFormat() {
  if (FormatCount >= 10) {
    return;
  }
  FormatCount++;
  $('#extras').raw().value = FormatCount;

  var NewRow = document.createElement("tr");
  NewRow.id = "new_torrent_row"+FormatCount;
  NewRow.setAttribute("style","border-top-width: 5px; border-left-width: 5px; border-right-width: 5px;");

  var NewCell1 = document.createElement("td");
  NewCell1.setAttribute("class","label");
  NewCell1.innerHTML = "Extra Torrent File";

  var NewCell2 = document.createElement("td");
  var TorrentField = document.createElement("input");
  TorrentField.type = "file";
  TorrentField.id = "extra_torrent_file"+FormatCount;
  TorrentField.name = "extra_torrent_files[]";
  TorrentField.size = 50;
  NewCell2.appendChild(TorrentField);

  NewRow.appendChild(NewCell1);
  NewRow.appendChild(NewCell2);

  NewRow = document.createElement("tr");
  NewRow.id = "new_format_row"+FormatCount;
  NewRow.setAttribute("style","border-left-width: 5px; border-right-width: 5px;");
  NewCell1 = document.createElement("td");
  NewCell1.setAttribute("class","label");
  NewCell1.innerHTML = "Extra Format / Bitrate";

  NewCell2 = document.createElement("td");
  tmp = '<select id="releasetype" name="extra_formats[]"><option value="">---</option>';
  var formats=["Saab","Volvo","BMW"];
  for (var i in formats) {
    tmp += '<option value="'+formats[i]+'">'+formats[i]+"</option>\n";
  }
  tmp += "</select>";
  var bitrates=["1","2","3"];
  tmp += '<select id="releasetype" name="extra_bitrates[]"><option value="">---</option>';
  for (var i in bitrates) {
    tmp += '<option value="'+bitrates[i]+'">'+bitrates[i]+"</option>\n";
  }
  tmp += "</select>";

  NewCell2.innerHTML = tmp;
  NewRow.appendChild(NewCell1);
  NewRow.appendChild(NewCell2);


  NewRow = document.createElement("tr");
  NewRow.id = "new_description_row"+FormatCount;
  NewRow.setAttribute("style","border-bottom-width: 5px; border-left-width: 5px; border-right-width: 5px;");
  NewCell1 = document.createElement("td");
  NewCell1.setAttribute("class","label");
  NewCell1.innerHTML = "Extra Release Description";

  NewCell2 = document.createElement("td");
  NewCell2.innerHTML = '<textarea name="extra_release_desc[]" id="release_desc" cols="60" rows="4"></textarea>';

  NewRow.appendChild(NewCell1);
  NewRow.appendChild(NewCell2);
}

function RemoveFormat() {
  if (FormatCount == 0) {
    return;
  }
  $('#extras').raw().value = FormatCount;

  var x = $('#new_torrent_row'+FormatCount).raw();
  x.parentNode.removeChild(x);

  x = $('#new_format_row'+FormatCount).raw();
  x.parentNode.removeChild(x);

  x = $('#new_description_row'+FormatCount).raw();
  x.parentNode.removeChild(x);

  FormatCount--;
}


var ArtistCount = 1;

function AddArtistField() {
  window.getSelection().removeAllRanges()
  ArtistCount = $('input[name="idols[]"]').length;

  if (ArtistCount >= 200) {
    return;
  }
  var ArtistField = document.createElement("input");
  ArtistField.type = "text";
  ArtistField.id = "idols_" + ArtistCount;
  ArtistField.name = "idols[]";
  ArtistField.size = 45;

  var x = $('#idolfields').raw();
  x.appendChild(document.createElement("br"));
  x.appendChild(ArtistField);
  x.appendChild(document.createTextNode('\n'));

  if ($("#idol").data("gazelle-autocomplete")) {
    $(ArtistField).live('focus', function() {
      $(ArtistField).autocomplete({
        serviceUrl : 'artist.php?action=autocomplete'
      });
    });
  }

  ArtistCount++;
}

function RemoveArtistField() {
  window.getSelection().removeAllRanges()
  ArtistCount = $('input[name="idols[]"]').length;
  if (ArtistCount == 1) {
    return;
  }
  var x = $('#idolfields').raw();
  for (i = 0; i < 3; i++) {
    x.removeChild(x.lastChild);
  }
  ArtistCount--;
}

function AddScreenshotField() {
  var sss = $('[name="screenshots[]"]')
  if (sss.length >= 10) return
  var ScreenshotField = document.createElement("input");
  ScreenshotField.type = "text";
  ScreenshotField.id = "ss_" + sss.length;
  ScreenshotField.name = "screenshots[]";
  ScreenshotField.size = 45;

  var a = document.createElement("a")
  a.className = "brackets"
  a.innerHTML = "−"
  a.onclick = function(){RemoveScreenshotField(this)}

  var x = $('#screenshots').raw()
  var y = document.createElement("div")
  y.appendChild(ScreenshotField);
  y.appendChild(document.createTextNode('\n'));
  y.appendChild(a);
  x.appendChild(y);
}
function RemoveScreenshotField(el) {
  var sss = $('[name="screenshots[]"]')
  el.parentElement.remove()
}

function CheckVA () {
  if ($('#artist').raw().value.toLowerCase().trim().match(/^(va|various(\sa|a)rtis(t|ts)|various)$/)) {
    $('#vawarning').gshow();
  } else {
    $('#vawarning').ghide();
  }
}

function CheckYear() {
  var media = $('#media').raw().options[$('#media').raw().selectedIndex].text;
  if (media == "---" || media == "Vinyl" || media == "Soundboard" || media == "Cassette") {
    media = "old";
  }
  var year = $('#year').val();
  var unknown = $('#unknown').prop('checked');
  if (year < 1982 && year != '' && media != "old" && !unknown) {
    $('#yearwarning').gshow();
    $('#remaster').raw().checked = true;
    $('#remaster_true').gshow();
  } else if (unknown) {
    $('#remaster').raw().checked = true;
    $('#yearwarning').ghide();
    $('#remaster_true').gshow();
  } else {
    $('#yearwarning').ghide();
  }
}

function ToggleUnknown() {
  if ($('#unknown').raw().checked) {
    $('#remaster_year').raw().value = "";
    $('#remaster_title').raw().value = "";
    $('#remaster_record_label').raw().value = "";
    $('#remaster_catalogue_number').raw().value = "";

    if ($('#groupremasters').raw()) {
      $('#groupremasters').raw().selectedIndex = 0;
      $('#groupremasters').raw().disabled = true;
    }

    $('#remaster_year').raw().disabled = true;
    $('#remaster_title').raw().disabled = true;
    $('#remaster_record_label').raw().disabled = true;
    $('#remaster_catalogue_number').raw().disabled = true;
  } else {
    $('#remaster_year').raw().disabled = false;
    $('#remaster_title').raw().disabled = false;
    $('#remaster_record_label').raw().disabled = false;
    $('#remaster_catalogue_number').raw().disabled = false;

    if ($('#groupremasters').raw()) {
      $('#groupremasters').raw().disabled = false;
    }
  }
}

function AnimeAutofill() {
  var map = { artist: 'idols_0',
              title: 'title',
              title_rj: 'title_rj',
              title_jp: 'title_jp',
              year: 'year',
              description: 'album_desc' }
  var aid = $('#anidb').raw().value
  $.getJSON('/ajax.php?action=autofill&cat=anime&aid='+aid, function(data) {
    if (data.status != "success") return
    for (i in data.response) {
      if (map[i] && !($('#'+map[i]).raw().value)) {
        $('#'+map[i]).raw().value = data.response[i]
      }
    }
  })
}

function JavAutofill() {
  var map = { cn: 'javdb',
              idols: 'idols',
              title: 'title',
              title_jp: 'title_jp',
              year: 'year',
              studio: 'studio',
              image: 'image',
              tags: 'tags',
              description: 'album_desc' }
  var cn = $('#javdb_tr #catalogue').raw().value.toUpperCase()
  $.getJSON('/ajax.php?action=autofill&cat=jav&cn='+cn, function(data) {
    if (data.status != "success") {
      $('#catalogue').raw().value = 'Failed'
      return
    } else {
      $('#catalogue').raw().value = data.response.cn
    }
    for (i in data.response) {
      if (Array.isArray(data.response[i])) {
        for (j in data.response[i]) {
          if (i == 'idols') {
            if (!($('#'+map[i]+'_'+j).raw())) {
              AddArtistField()
            }
            $('#'+map[i]+'_'+j).raw().value = data.response[i][j]
          }
          if (map[i] == 'tags' && !($('#'+map[i]).raw().value)) {
            $('#'+map[i]).raw().value = data.response[i].join(', ')
          }
        }
      }
      if (map[i] && $('#'+map[i]).raw() && !($('#'+map[i]).raw().value)) {
        $('#'+map[i]).raw().value = data.response[i]
      }
    }
    if (data.response.screens.length) {
      $('#album_desc').raw().value = ('[spoiler=Automatically located thumbs][img]'+data.response.screens.join('[/img][img]')+'[/img][/spoiler]\n\n') + $('#album_desc').raw().value
    }
  })
}

function MangaAutofill() {
  var map = {  artists: 'idols',
              title: 'title',
              title_jp: 'title_jp',
              year: 'year',
              tags: 'tags',
              lang: 'lang',
              cover: 'image',
              circle: 'series',
              pages: 'pages',
              description: 'release_desc' }
  var nh = $('#ehentai_tr #catalogue').raw().value
  $.getJSON('/ajax.php?action=autofill&cat=manga&url='+nh, function(data) {
    if (data.status != "success") {
      $('#catalogue').raw().value = 'Failed'
      return
    }
    for (i in data.response) {
      if (Array.isArray(data.response[i])) {
        for (j in data.response[i]) {
          if (i == 'artists') {
            if (!($('#'+map[i]+'_'+j).raw())) {
              AddArtistField()
            }
            $('#'+map[i]+'_'+j).raw().value = data.response[i][j]
          }
          if (map[i] == 'tags' && !($('#'+map[i]).raw().value)) {
            $('#'+map[i]).raw().value = data.response[i].join(', ')
          }
        }
      }
      if (map[i] && $('#'+map[i]).raw() && (!($('#'+map[i]).raw().value) || $('#'+map[i]).raw().value == '---')) {
        $('#'+map[i]).raw().value = data.response[i]
      }
    }
  })
}

function SetResolution() {
  if ($('#ressel').raw().value != 'Other') {
    $('#resolution').raw().value = $('#ressel').raw().value
    $('#resolution').ghide()
  } else {
    $('#resolution').raw().value = ''
    $('#resolution').gshow()
    $('#resolution').raw().readOnly = false
  }
}

function MediaInfoExtract() {
  const mi = $('#mediainfo').raw().value
  function getval(mi, key) {
    var match = mi.match(new RegExp('^'+key+'\\s*:\\s*(.*)', 'mi'))
    return (match && match[1]) ? match[1] : false
  }
  ['container', 'codec', 'resolution', 'audioformat', 'lang'].forEach((sel) => {
    if (sel == 'resolution') {
      var width = getval(mi, 'Width')
      var height = getval(mi, 'Height')
      if (!(width && height)) { return }
      width = width.match(/[0-9 ]+/)[0].replace(/ /g, '')
      height = height.match(/[0-9 ]+/)[0].replace(/ /g, '')
      var ressel = $('[name=ressel]').raw()
      if (width == '680' && height == '480') { ressel.value = 'SD' }
      else if (height == '480') { ressel.value = '480p' }
      else if (height == '720') { ressel.value = '720p' }
      else if (height == '1080' && getval(mi, 'Scan type' == 'Interlaced')) { ressel.value = '1080i' }
      else if (height == '1080') { ressel.value = '1080p' }
      else if (width == '3840') { ressel.value = '4K' }
      else {
        ressel.value = 'Other'
        $('[name=resolution]').raw().value = width+'x'+height
      }
    } else if (sel == 'lang') {
      var val1 = getval(mi.slice(mi.search(/^Audio$/m)), 'Language')
      var val2 = getval(mi.slice(mi.search(/^Audio\nID.*[^1]/m)), 'Language')
      var val = (val2 && val2 != val1 && (val1+val2 == 'EnglishJapanese' || val1+val2 == 'JapaneseEnglish')) ? 'Dual Language' : val1
      if (val) { $('[name=lang]').raw().value = val }
    } else if (sel == 'container') {
      var containerTable = {'Matroska': 'MKV','MPEG-4': 'MP4','AVI': 'AVI','OGG': 'OGM','Windows Media': 'WMV'}
      var cont = getval(mi, 'Format')
      if (containerTable[cont]) { $('[name=container]').raw().value = containerTable[cont] }
    } else if (sel == 'codec') {
      var codecTable = {'WMV1':'WMV','VC-1':'WMV','HEVC':'HEVC'}
      var codec = getval(mi.slice(mi.search(/^Video$/m)), 'Format')
      var formatProfile = getval(mi.slice(mi.search(/^Video$/m)), 'Format profile')
      var codecID = getval(mi, 'Codec ID')
      var codel = $('[name=codec]').raw()
      if (codec == 'AVC') {
        codel.value = (formatProfile.includes('High 10')) ? '10-bit h264' : 'h264'
      }
      else if (codec == 'MPEG-4 Visual') {
        codel.value = (codecID == 'XVID') ? 'XVID' : 'DIVX'
      }
      else if (codecTable[codec]) {
        codel.value = codecTable[codec]
      }
    }
  })
}

function initAutofill() {
  $('[autofill]').each(function(i, el) {
    el.addEventListener('click', function(event) {
      ({'douj':MangaAutofill, 'anime':AnimeAutofill, 'jav':JavAutofill})[el.attributes['autofill'].value]()
    })
  })
}

$(function() {
  Categories();
  initAutofill();
  $(document).on('click', '.add_artist_button', AddArtistField);
  $(document).on('click', '.remove_artist_button', RemoveArtistField);
})
