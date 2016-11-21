$(document).ready(function () {
  // If the custom stylesheet field is empty, select the current style from the previews
  if (!$('input#styleurl').val()){
    var radiobutton = $('input[name="stylesheet_gallery"][value="' + $('select#stylesheet').val() + '"]');
    radiobutton.click();
    $('.preview_wrapper').removeClass('selected');
    radiobutton.parent().parent().addClass('selected');
  }
  // If the input is clicked, redirect it to the overlay click event
  $('input[name="stylesheet_gallery"]').change(function() {
    $('.preview_wrapper').removeClass('selected');
    var parent = $(this).parent();
    parent.addClass('selected');
    var radiobutton = parent.find('input');
    radiobutton.prop('checked', true);
    $('select#stylesheet').val(radiobutton.attr('value'));
    $('select#stylesheet').change();
    $('input#styleurl').val('');
  })
  // Passthrough image click to radio button
  $('.preview_image').click(function(e) {
    e.currentTarget.children[1].children[0].children[0].click()
  })
  // If the drop-down is changed, select the appropriate item in gallery, clear the custom CSS field
  $('select#stylesheet').change(function() {
    var radiobutton = $('input[name="stylesheet_gallery"][value="' + $(this).val() + '"]');
    radiobutton.prop('checked', true);
    $('.preview_wrapper').removeClass('selected');
    radiobutton.parent().parent().addClass('selected');
    $('input#styleurl').val('');
  })
  // If the custom CSS field is changed, clear radio buttons
  $('input#styleurl').keydown(function() {
    $('input[name="stylesheet_gallery"]').each(function() {
      $(this).prop('checked', false);
    })
    $('.preview_wrapper').removeClass('selected');
  })
  // If the input is empty, select appropriate gallery item again by the drop-down
  $('input#styleurl').keyup(function() {
    if (!$(this).val()){
      $('select#stylesheet').change();
    }
  })
  // Allow the CSS gallery to be expanded/contracted
  $('#toggle_css_gallery').click(function (e) {
    e.preventDefault();
    $('#css_gallery').slideToggle(function () {
      $('#toggle_css_gallery').text($(this).is(':visible') ? 'Hide gallery' : 'Show gallery');
    });
  });
  document.querySelectorAll('[name=style_additions\\[\\]]').forEach(function(el) {
    el.addEventListener('change', function(e) {
      if (e.target.checked) {
        document.body.classList.add('style_'+e.target.value)
      } else {
        document.body.classList.remove('style_'+e.target.value)
      }
    })
  })
  var userstyle = $('link[rel=stylesheet][title]')[0]
  $('select#stylesheet').on('change', function(e) {
    var changetext = e.target.options[e.target.selectedIndex].text.toLowerCase()
    userstyle.href = userstyle.href.replace(/\/[^\/]+?\/style.css/, '/'+changetext+'/style.css')
    $('.style_addition').each(function(i, el){
      if (el.id == 'style_addition_'+changetext) {
        if (el.children.length) {
          el.classList.remove('hidden')
          $('#style_additions_tr')[0].classList.remove('hidden')
        } else {
          $('#style_additions_tr')[0].classList.add('hidden')
        }
      } else {
        el.classList.add('hidden')
      }
    })
  })
});
