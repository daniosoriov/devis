(function ($) {

  /**
   * The recommended way for producing HTML markup through JavaScript is to write
   * theming functions. These are similiar to the theming functions that you might
   * know from 'phptemplate' (the default PHP templating engine used by most
   * Drupal themes including Omega). JavaScript theme functions accept arguments
   * and can be overriden by sub-themes.
   *
   * In most cases, there is no good reason to NOT wrap your markup producing
   * JavaScript in a theme function.
   */
  Drupal.theme.prototype.devisExampleButton = function (path, title) {
    // Create an anchor element with jQuery.
    return $('<a href="' + path + '" title="' + title + '">' + title + '</a>');
  };
    

  /**
   * Behaviors are Drupal's way of applying JavaScript to a page. In short, the
   * advantage of Behaviors over a simple 'document.ready()' lies in how it
   * interacts with content loaded through Ajax. Opposed to the
   * 'document.ready()' event which is only fired once when the page is
   * initially loaded, behaviors get re-executed whenever something is added to
   * the page through Ajax.
   *
   * You can attach as many behaviors as you wish. In fact, instead of overloading
   * a single behavior with multiple, completely unrelated tasks you should create
   * a separate behavior for every separate task.
   *
   * In most cases, there is no good reason to NOT wrap your JavaScript code in a
   * behavior.
   *
   * @param context
   *   The context for which the behavior is being executed. This is either the
   *   full page or a piece of HTML that was just added through Ajax.
   * @param settings
   *   An array of settings (added through drupal_add_js()). Instead of accessing
   *   Drupal.settings directly you should use this because of potential
   *   modifications made by the Ajax callback that also produced 'context'.
   */
  Drupal.behaviors.devisExampleBehavior = {
    attach: function (context, settings) {
      // By using the 'context' variable we make sure that our code only runs on
      // the relevant HTML. Furthermore, by using jQuery.once() we make sure that
      // we don't run the same piece of code for an HTML snippet that we already
      // processed previously. By using .once('foo') all processed elements will
      // get tagged with a 'foo-processed' class, causing all future invocations
      // of this behavior to ignore them.
      $('.some-selector', context).once('foo', function () {
        // Now, we are invoking the previously declared theme function using two
        // settings as arguments.
        var $anchor = Drupal.theme('devisExampleButton', settings.myExampleLinkPath, settings.myExampleLinkTitle);

        // The anchor is then appended to the current element.
        $anchor.appendTo(this);
      });
    }
  };
    
    Drupal.theme.prototype.devisSpecialBoxContent = function() {
        var li1 = '<li>Vous &#234;tes comptable-fiscaliste ou expert-comptable?</li>';
        var li2 = '<li><a class="navSpecialBoxReg" href="/devenir-comptable">Inscrivez-vous!</a></li>';
        return $('<ul class="navSpecialBox">'+ li1 + li2 +'</ul>');
    };
    
    Drupal.theme.prototype.devisSpecialBoxMeasureWidth = function(element, anchor) {
        if ($(window).width() <= 704) {
            $('.navSpecialBox').remove();
        }
        else {
            anchor.appendTo(element.parent());
        }
    };
    
    Drupal.behaviors.devisGeneral = {
        attach: function (context, settings) {
            $('.grippie').remove();
        }
    };
    
    Drupal.behaviors.devisBecomeProviderBox = {
        attach: function (context, settings) {
            var $anchor = Drupal.theme('devisSpecialBoxContent');
            $('.navSpecial', context).once('foo', function() {
                //alert(JSON.stringify(settings));
                
                $anchor.appendTo($(this).parent());
            });
            
            $(window).resize(function() {
                Drupal.theme('devisSpecialBoxMeasureWidth', $('.navSpecial'), $anchor);
            });
            Drupal.theme('devisSpecialBoxMeasureWidth', $('.navSpecial'), $anchor);
        }
    };
    
    Drupal.behaviors.devisStickyNav = {
        attach: function (context, settings) {
            var  mn = $('.l-region--navigation');
            mns = 'navSticky';
            hdr = $('header').height();

            $(window).scroll(function() {
                if ($(window).width() > 704) {
                    if ($(this).scrollTop() > hdr) {
                        mn.addClass(mns);
                    }
                    else {
                        mn.removeClass(mns);
                    }
                }
                else {
                    mn.removeClass(mns);
                }
            });
            
            $(window).resize(function() {
                if ($(window).width() <= 704) {
                    mn.removeClass(mns);
                }
            });
        }
    };
  
  /** 
   * Function to control the select boxes of the regions in the user profile.
   */
  Drupal.behaviors.devisProviderProfileCheckbox = {
    attach: function (context, settings) {
      $("input[value='BEL']").click(function() {
        if ($(this).attr('checked')) {
          $("input[name*='field_active_regions_belgium']").attr('checked', '');
          $(this).attr('checked', 'checked');
        }
      });
      
      $("input[name*='field_active_regions_belgium']").click(function() {
        if ($(this).attr('checked') && $(this).val() != 'BEL') {
          $("input[value='BEL']").attr('checked', '');
        }
      });
    }
  };

})(jQuery);
