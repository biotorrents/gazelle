$(() => {
  $("#post").click(e => {
    let hard_error = m => {
      if (e.isDefaultPrevented()) return
      alert(m)
      e.preventDefault()
    }
    let soft_error = m => {
      if (e.isDefaultPrevented()) return
      if (!confirm(`${m}\n\nPress OK to upload anyway`)) {
        e.preventDefault()
      }
    }

    if (!$('#file').raw().value) {
      hard_error('No torrent file is selected')
    }
    if ($('#album_desc').raw().value.length < 10) {
      hard_error('The group description is too short')
    }

    if ($('#file').raw().value.slice(-8).toLowerCase() != '.torrent') {
      soft_error('The file selected does not appear to be a .torrent file')
    }
    let mi = $('#mediainfo').raw().value
    if (mi && (!mi.includes('General') || !mi.includes('Video'))) {
      soft_error('Your MediaInfo does not appear to be from a valid MediaInfo utility')
    }
    if (!$('#image').raw().value) {
      soft_error('You did not include a cover image, which is mandatory if one exists')
    }
    if ($('#media_tr select').raw().value == 'DVD' && (['720p','1080i','1080p','4K'].includes($('#ressel').raw().value) || +$('#resolution').raw().value.split('x')[1] > 576)) {
      soft_error('Your selected resolution is too high to be a DVD. Are you sure the media type should be DVD and not WEB?')
    }
  })
  $('#tags').on('blur', e => $('#tags').raw().value = $('#tags').raw().value.split(',').map(t=>t.trim()).filter(t=>t).join(', '))
})
