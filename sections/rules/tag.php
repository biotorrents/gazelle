<?
//Include the header
View::show_header('Tagging Rules');
?>
<!-- General Rules -->
<div class="thin">
  <div class="header">
    <h2 id="general" class="center">Tagging</h2>
  </div>
  <div class="box pad rule_summary" style="padding: 10px 10px 10px 20px;">
<?    Rules::display_site_tag_rules(false) ?>
  </div>
  <!-- END General Rules -->
<? include('jump.php'); ?>
</div>
<?
View::show_footer();
?>
