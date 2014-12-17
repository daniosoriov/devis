<h2 class="pane-title"><?php print render($title); ?></h2>
<?php
// split the username and password so we can put the form links were we want (they are in the "user-login-links" div bellow)
print drupal_render($form['info']);
?>
<div class="user-login-links">
    <a href="<?= $password_url ?>"><?php print render($password_label) ?></a>
    &nbsp;|&nbsp;
    <a href="<?= $register_url ?>"><?php print render($register_label) ?></a>
</div>
<?php
print drupal_render($form['form_build_id']);
print drupal_render($form['form_id']);
print drupal_render($form['actions']);
?>