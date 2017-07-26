var BBCode = {
  spoiler: function(link) {
    if ($(link.nextSibling).has_class('hidden')) {
      $(link.nextSibling).gshow();
      $(link).html('Hide');
      if ($(link).attr("value")) {
        $(link).attr("value", "Hide" + $(link).attr("value").substring(4))
      }
    } else {
      $(link.nextSibling).ghide();
      $(link).html('Show');
      if ($(link).attr("value")) {
        $(link).attr("value", "Show" + $(link).attr("value").substring(4))
      }
    }
  }
};

function wrapSelected(box, wrap, offset) {
  if (!Array.isArray(wrap)) wrap = [wrap, wrap]
  if (wrap.length < 2) wrap[1] = wrap[0]
  let s = box.selectionStart
  let e = box.selectionEnd
  let v = box.value
  box.value = v.slice(0,s)+wrap[0]+v.slice(s,e)+wrap[1]+v.slice(e)
  box.focus()
  box.selectionEnd = (offset!==undefined?s+offset:e+wrap[0].length)
}

function BBEditor(box) {
  if (box.previousSibling.className == 'bbcode_bar') return
  let buttons = [
    {short:'B', name:'Bold', wrap:['[b]','[/b]']},
    {short:'I', name:'Italic', wrap:['[i]','[/i]']},
    {short:'U', name:'Underline', wrap:['[u]','[/u]']},
    {short:'S', name:'Strikethrough', wrap:['[s]','[/s]']},
    {short:'Left', name:'Align Left', wrap:['[align=left]','[/align]']},
    {short:'Center', name:'Align Center', wrap:['[align=center]','[/align]']},
    {short:'Right', name:'Align Right', wrap:['[align=right]','[/align]']},
    {short:'Pre', name:'Preformatted', wrap:['[pre]','[/pre]']},
    {short:'H1', name:'Subheading 1', wrap:'=='},
    {short:'H2', name:'Subheading 2', wrap:'==='},
    {short:'H3', name:'Subheading 3', wrap:'===='},
    {short:'Color', name:'Color', wrap:['[color=]','[/color]'], offset:7},
    {short:'TeX', name:'LaTeX', wrap:['[tex]','[/tex]']},
    {short:'Quote', name:'Quote', wrap:['[quote]','[/quote]']},
    {short:'List', name:'List', wrap:['[*]','']},
    {short:'Hide', name:'Spoiler', wrap:['[spoiler]','[/spoiler]']},
    {short:'Img', name:'Image', wrap:['[img]','[/img]']},
    {short:'Vid', name:'Video', wrap:['[embed]','[/embed]']},
    {short:'Link', name:'Link', wrap:['[url]','[/url]']},
    {short:'Torr', name:'Torrent', wrap:['[torrent]','[/torrent]']}
  ]
  let bar = document.createElement('ul')
  bar.className = "bbcode_bar"
  bar.style.width = box.offsetWidth+'px'
  // Let the DOM update and then snap the size again (twice)
  setTimeout(function() {
    bar.style.width = box.offsetWidth+'px'
    bar.style.width = box.offsetWidth+'px'
  }, 1)
  for (let button of buttons) {
    li = document.createElement('li')
    b = document.createElement('a')
    b.setAttribute('title', button.name)
    b.innerHTML = button.short
    b.addEventListener('click', e=>wrapSelected(box, button.wrap, button.offset))
    li.appendChild(b)
    bar.appendChild(li)
  }
  box.parentNode.insertBefore(bar, box)
}

if (document.readyState == 'complete') {
  $('.bbcode_editor').each(function(i, el) { BBEditor(el) })
} else {
  document.addEventListener("DOMContentLoaded", function() {
    $('.bbcode_editor').each(function(i, el) { BBEditor(el) })
  })
}
