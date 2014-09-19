<?php

dpm($form, 'HOLA');
print render($form['form_id']);
print render($form['form_build_id']);
print render($form['form_token']);

print render ($form['field_user_firstname']);
print render ($form['field_user_lastname']);
print render ($form['field_user_dob']);
?>

<input type=”submit” name=”op” id=”edit-submit” value=”HOLAHOLA”  />