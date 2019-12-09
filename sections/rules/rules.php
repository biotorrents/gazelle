<?
//Include the header
View::show_header('Rule Index');
?>
<!-- General Rules -->
<div class="thin">
  <div class="header">
    <h2 id="general" class="center">Golden Rules</h2>
  </div>
  <div class="box pad rule_summary" style="padding: 10px 10px 10px 20px;">
<? Rules::display_golden_rules(); ?>
  </div>
  <!-- END General Rules -->
<? include('jump.php'); ?>
</div>
<?
View::show_footer();
?>
