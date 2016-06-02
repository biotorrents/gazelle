function DisplayTrans() {
  if (['Softsubs','Hardsubs'].indexOf($('select[name="sub"]').raw().value) != -1) {
    $('#subber').raw().parentNode.parentNode.style.display = 'table-row'
	} else {
    $('#subber').raw().parentNode.parentNode.style.display = 'none'
	}
}

function Categories() {
  var save = {};
	var form_elements = $('#dynamic_form input[name], #dynamic_form select, #dynamic_form textarea');
  for (var i = 0; i < form_elements.length; i++) {
	  if (["Preview", "---", ""].indexOf(form_elements[i].value) == -1) {
			if (form_elements[i].name.slice(-1) == '[]') {
				save[form_elements[i].name] = save[form_elements[i].name] || new Array()
				save[form_elements[i].name][form_elements[i].id.slice(form_elements[i].id.search(/[0-9]/))] = form_elements[i].value
			} else if (form_elements[i].type == 'checkbox') {
				save[form_elements[i].name] = form_elements[i].checked;
			} else {
      	save[form_elements[i].name] = form_elements[i].value;
			}
		}
	}

	ajax.get('ajax.php?action=upload_section&categoryid=' + $('#categories').raw().value, function (response) {
		$('#dynamic_form').raw().innerHTML = response;
		initMultiButtons();
		// Evaluate the code that generates previews.
		eval($('#dynamic_form script.preview_code').html());

		for (i in save) {
			if (Array.isArray(save[i])) {
				for (j in save[i]) {
					if (!($('#'+i.slice(0,-2)+'_'+j).raw())) AddArtistField()
					$('#'+i.slice(0,-2)+'_'+j).raw().value = save[i][j]
				}
			} else if (typeof(save[i]) == 'boolean') {
				if ($('[name="'+i+'"]').raw()) $('[name="'+i+'"]').raw().checked = save[i]
			} else {
				if ($('[name="'+i+'"]').raw()) $('[name="'+i+'"]').raw().value = save[i]
			}
		}
    if ($('#categories').raw().value == "1") DisplayTrans()
		if ($('#ressel').raw() && $('#ressel').raw().value == "Other") {
			$('#resolution').raw().readOnly = false
			$('#resolution').gshow()
		}
		initAutocomplete()
	});
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

function GroupRemaster() {
	var remasters = json.decode($('#json_remasters').raw().value);
	var index = $('#groupremasters').raw().options[$('#groupremasters').raw().selectedIndex].value;
	if (index != "") {
		$('#remaster_year').raw().value = remasters[index][1];
		$('#remaster_title').raw().value = remasters[index][2];
		$('#remaster_record_label').raw().value = remasters[index][3];
		$('#remaster_catalogue_number').raw().value = remasters[index][4];
	}
}

function AnidbAutofill() {
	var map = { artist: 'idols_0',
							title: 'title',
							title_jp: 'title_jp',
							year: 'year',
							description: 'album_desc' }
	var aid = $('#anidb').raw().value
	$.getJSON('/ajax.php?action=anidb&aid='+aid, function(data) {
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
	var cn = $('#catalogue').raw().value.toUpperCase()
	$.getJSON('/ajax.php?action=javfill&cn='+cn, function(data) {
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

function DoujAutofill() {
	var map = {	artists: 'idols',
							title: 'title',
							title_jp: 'title_jp',
							year: 'year',
							tags: 'tags',
              lang: 'lang',
							cover: 'image',
							pages: 'pages',
							description: 'release_desc' }
	var nh = $('#catalogue').raw().value
	$.getJSON('/ajax.php?action=doujin&url='+nh, function(data) {
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
      var cont = getval(mi, 'Format')
      var contel = $('[name=container]').raw()
      if (cont == 'Matroska') { contel.value = 'MKV' }
    }
  })
}
