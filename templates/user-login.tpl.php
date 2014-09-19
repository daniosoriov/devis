<h2><?php print render($title); ?></h2>
<?php
// split the username and password so we can put the form links were we want (they are in the "user-login-links" div bellow)
print drupal_render($form['name']);
print drupal_render($form['pass']);
?>
<div class="user-login-links">
    <a href="/user/password"><?php print render($password_label) ?></a>
    &nbsp;|&nbsp;
    <a href="/devenir-comptable"><?php print render($register_label) ?></a>
</div>
<?php
print drupal_render($form['form_build_id']);
print drupal_render($form['form_id']);
print drupal_render($form['actions']);
?>