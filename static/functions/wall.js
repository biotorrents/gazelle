function wall(parent, children, style, min) {
  var min = min || 2
  var b = $(parent+':not(.hidden)').raw()
  var bs = $(parent+':not(.hidden) '+children).toArray()

  if (!window.getComputedStyle(b).height) {return}

  bs.forEach(function(el){el.style.width='';el.style.height=''})

  var rows = []
  if (typeof(style) === 'number') {
    for (var i=0; i<bs.length; i+=style)
      rows.push(bs.slice(i,i+style))
  } else {
    var a = 0
    for (i in style) {
      rows.push(bs.slice(a, a+style[i]))
      a += style[i]
    }
  }
  if (rows.length >= 2 && rows[rows.length-1].length < min) {
    var needed = min - rows[rows.length-1].length
    if (rows[rows.length-2].length - needed >= min) {
      for(i=0; i<needed; i++) {
        rows[rows.length-1]=[rows[rows.length-2].pop()].concat(rows[rows.length-1])
      }
    } else {
      rows[rows.length-2] = rows[rows.length-2].concat(rows[rows.length-1])
      rows.splice(rows.length-1, 1)
    }
  }

  function getW(e) {
    var a = window.getComputedStyle(e).width.match(/[0-9.]+/)
    return a ? parseFloat(a[0]) : 0
  }
  function getH(e) {
    var a = window.getComputedStyle(e).height.match(/[0-9.]+/)
    return a ? parseFloat(a[0]) : 0
  }

  for (i in rows) {
    for (j in rows[i]) {
      rows[i][j].style.width = (getW(rows[i][j]) / getH(rows[i][j]) * 100) + 'px'
      rows[i][j].style.height = 100 + 'px'
    }
    var w = rows[i].reduce(function(x, y) { return x + getW(y) }, 0)
    for (j in rows[i]) {
      rows[i][j].style.height=(getH(rows[i][j])*(getW(b)-(4*rows[i].length))/w)+'px'
      rows[i][j].style.width=(getW(rows[i][j])*(getW(b)-(4*rows[i].length))/w)+'px'
      rows[i][j].style.display = 'inline-block'
      rows[i][j].style.margin = '1px'
    }
  }
}
