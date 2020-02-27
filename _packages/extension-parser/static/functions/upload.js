// Line 11
function Categories() {
    let def = [
      'javdb', // Accession Number
      'audio', // Version
      'title', // Torrent Title
      'title_rj', // Organism
      'title_jp', // Strain/Variety
      'idols', // Authors(s)
      'studio', // Department/Lab
      'series', // Location
      'year', // Year
      'codec', // License
      // Platform changes below
      'resolution', // Assembly Level
      // Format changes below
      'archive', // Archive
      'tags', // Tags
      'cover', // Picture
      'mirrors', // Mirrors
      'screenshots', // Publications
      'group_desc', // Torrent Group Description
      'release_desc', // Torrent Description
      'censored', // Aligned/Annotated
      'anon', // Upload Anonymously
    ]
  
    let cats = [
      { // DNA
        'media': {}, // Platform
        'container': {}, // Format
      },
      { // RNA
        'media': {}, // Platform
        'container': {}, // Format
      },
      { // Proteins
        'media': {}, // Platform
        'container_prot': {}, // Format
      },
      { // Imaging
        'media_manga': {}, // Platform
        'container_games': {}, // Format
      },
      { // Extras
        'media': {}, // Platform
        'container_extra': {}, // Format
      }
    ]
  
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
      let field = tr.id.slice(0, -3)
      if (active[field]) {
        if (active[field].name) {
          tr.children[0].innerHTML = active[field].name
        }
        let notes = $(`#${tr.id} p.notes`).raw()
        if (notes) notes.innerHTML = active[field].notes || ''
        show(tr)
      } else {
        hide(tr)
      }
    }
  }
// Line 91
