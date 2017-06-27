$(document).ready(function() {
  var path = window.location.pathname.split('/').reverse()[0].split(".")[0];
  var action = (window.location.search.match(/action=([^&]+q)/)||[,''])[1];
  if (path == "forums" && action == "new") $("#newthreadform").validate();
  if (path == "reports" && action == "report") $("#report_form").validate();
  if (path == "inbox" && (action == "viewconv" || action == "compose")) $("#messageform").validate();
  if (path == "user" && action == "notify") $("#filter_form").validate();
  if (path == "requests" && action == "new") $("#request_form").preventDoubleSubmission();
  if (path == "sitehistory" && action == "edit") $("#event_form").validate();
  if (path == "tools" && action == "calendar") $("#event_form").validate();
  if (path == "tools" && action == "mass_pm") $("#messageform").validate();
});
