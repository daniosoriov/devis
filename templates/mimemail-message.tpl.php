<?php

/**
 * @file
 * Default theme implementation to format an HTML mail.
 *
 * Copy this file in your default theme folder to create a custom themed mail.
 * Rename it to mimemail-message--[module]--[key].tpl.php to override it for a
 * specific mail.
 *
 * Available variables:
 * - $recipient: The recipient of the message
 * - $subject: The message subject
 * - $body: The message body
 * - $css: Internal style sheets
 * - $module: The sending module
 * - $key: The message identifier
 *
 * @see template_preprocess_mimemail_message()
 */
?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <?php if ($css): ?>
    <style type="text/css">
      <!--
      <?php print $css ?>
      -->
    </style>
    <?php endif; ?>
  </head>
  <body id="mimemail-body" <?php if ($module && $key): print 'class="'. $module .'-'. $key .'"'; endif; ?>>
    <div id="center">
      <div id="main">
        <table id="main">
          <tbody>
            <tr>
              <td id="main-border-top">
              </td>
            </tr>
            <tr>
              <td align="center">
                <center>
                  <!--<div id="main-border-top"></div>-->
                </center>
                <table id="head">
                  <tbody>
                    <tr>
                      <td id="head">
                        <div>
                          <a href="http://3devis.be">
                            <img alt="3devis | Trouvez des comptables et fiscalistes qualifiés" src="https://3devis.be/sites/default/files/comptable-fiscaliste-bruxelles.png" height="101" width="250">
                          </a>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
            <tr>
              <td align="center">
                <center>
                </center>
                <table id="body">
                  <tbody>
                    <tr>
                      <td id="body-border-top">
                      </td>
                    </tr>
                    <tr>
                      <td>
                        <?php print $body ?>
                      </td>
                    </tr>
                    <tr>
                      <td id="body-border-bottom">
                      </td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
            <tr>
              <td id="footer" align="center">
                <table id="footer">
                  <tbody>
                    <tr>
                      <td id="footer-team">
                        L&#39;&eacute;quipe de <a href="http://3devis.be">3devis.be</a>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
            <tr>
              <td id="footer-border-bottom">
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </body>
</html>
