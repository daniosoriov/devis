<h2 class="pane-title"><?php print render($title); ?></h2>
<?php
print drupal_render($form['info']);
print drupal_render($form['form_build_id']);
print drupal_render($form['form_id']);
print drupal_render($form['actions']);
?>