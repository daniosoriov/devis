<h2><?php print render($title); ?></h2>
<?php
print drupal_render($form['name']);
print drupal_render($form['form_build_id']);
print drupal_render($form['form_id']);
print drupal_render($form['actions']);
?>