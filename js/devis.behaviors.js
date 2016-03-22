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
    
    Drupal.theme.prototype.devisSpecialBoxContent = function(url) {
      var li1 = '<li>Vous &#234;tes comptable-fiscaliste ou expert-comptable?</li>';
      var li2 = '<li><a class="navSpecialBoxReg" href="'+ url +'">Inscrivez-vous!</a></li>';
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
  
  Drupal.theme.prototype.devisSelectCompanyLabel = function(element) {
    
    return title;
  }
  
  Drupal.theme.prototype.devisPrepareLabelHTML = function(id, element, default_title, required) {
    var title = default_title + required;
    switch (id) {
      case 'company':
        if (element == "association") {
          title = 'Nom de votre association'+ required;
        }
        break;
        
      case 'tva':
        if (element == "association" || element == "nonexistent" || element == "independent_plan") {
          //title = 'Num&eacute;ro de TVA de votre association';//+ required;
          title = default_title;//+ required;
        }
        break;
    }
    return title;
  }
  
  Drupal.theme.prototype.devisChangePricesFromLegalStatus = function(legalStatus, prices) {
    var newOptions = {};
    $.each(prices, function(key, value) {
      if (key == legalStatus) {
        newOptions = value;
        /*$.each(value, function(keyIn, valueIn) {
          newOptions.push({
            key: keyIn, 
            value: valueIn
          });
        });*/
      }
    });

    //console.log(newOptions);
    //newOptions = newOptions.sort(function(a, b){return newOptions[a].localeCompare(newOptions[b]) });
    //newOptions = newOptions.sort(function(a, b) {return a[1] - b[1]});
    //console.log(newOptions);
    
    var $el = $("#edit-field-devis-product-und");
    $el.empty(); // remove old options
    $.each(newOptions, function(key, value) {
      $el.append($("<option></option>")
         .attr("value", key).text(value));
    });
  }
  
  /*Drupal.theme.prototype.devisReplaceText = function(element, needle, str) {
    element.each(function() {
      var text = $(this).text();
      $(this).text(text.replace(needle, str)); 
    });
  }*/
    
  Drupal.behaviors.devisGeneral = {
    attach: function (context, settings) {
      $('.grippie').remove();
      
      $(window).scroll(function() {
        var value = $(this).scrollTop();
        if (value > 50) {
          $(".l-region--header-custom").addClass("small");
          $(".l-navigation-content").addClass("small");
        }
        else {
          $(".l-region--header-custom").removeClass("small");
          $(".l-navigation-content").removeClass("small");
        }
      });
      
      if (typeof easyDropDown != 'undefined' && $.isFunction(easyDropDown)) {
        // deactivate easydropdown on mobile devices.
        if ($(window).width() <= 384) {
          $('div').easyDropDown('disable');
        }
        // easyDropDown to 10 show 10 values.
        else {
          $('select').easyDropDown({ cutOff: 10 });
        }
      }
      
      $(".arrow-first").click(function() {
        $('html, body').animate({
          scrollTop: $(".second").offset().top - $('header').height() + 12
        }, 850);
      });
      
      $(".arrow-special").click(function() {
        $('html, body').animate({
          scrollTop: $(".third").offset().top - $('header').height() + 12
        }, 850);
      });
      
      $(".arrow-second").click(function() {
        $('html, body').animate({
          scrollTop: $(".third").offset().top - $('header').height()
        }, 850);
      });
      
      $(".arrow-third").click(function() {
        $('html, body').animate({
          scrollTop: $("#messages-box").offset().top - $('header').height()
        }, 850);
      });
      
      $(".arrow-contact").click(function() {
        $('html, body').animate({
          scrollTop: $(".group-contact-form").offset().top - $('header').height()
        }, 850);
      });

      var $window = $(window);
      $('section[data-type="background"]').each(function() {
        var $bgobj = $(this); // assigning the object
        $(window).scroll(function() {
          var yPos = -($window.scrollTop() / $bgobj.data('speed'));
          // Put together our final background position
          var coords = '50% '+ yPos + 'px';
          // Move the background
          $bgobj.css({ backgroundPosition: coords });
        });
      });
      
      // If the form was submitted and there was an error, 
      // scroll the page until the messages-box.
      // This is for the two main forms of the site.
      if (typeof settings.form_info != 'undefined') {
        if (settings.form_info.submitted && settings.form_info.error) {
          $('html, body').animate({
            scrollTop: $("#messages-box").offset().top - $('header').height()
          }, 850);
        }
      }
      
      // Placeholders don't work on IE.
      // Solution: http://www.hagenburger.net/BLOG/HTML5-Input-Placeholder-Fix-With-jQuery.html
      $('[placeholder]').focus(function() {
        var input = $(this);
        if (input.val() == input.attr('placeholder')) {
          input.val('');
          input.removeClass('placeholder');
        }
      }).blur(function() {
        var input = $(this);
        if (input.val() == '' || input.val() == input.attr('placeholder')) {
          input.addClass('placeholder');
          input.val(input.attr('placeholder'));
        }
      }).blur();
      
      $('[placeholder]').parents('form').submit(function() {
        $(this).find('[placeholder]').each(function() {
          var input = $(this);
          if (input.val() == input.attr('placeholder')) {
            input.val('');
          }
        })
      });
      
    }
  };
    
  Drupal.behaviors.devisBecomeProviderBox = {
    attach: function (context, settings) {
      //console.log(settings);
      if ($.support.opacity) { /* IE 6-8 */ 
        var $anchor = Drupal.theme('devisSpecialBoxContent', settings.devenir.url);
        $('.navSpecial', context).once('foo', function() {
          $anchor.appendTo($(this).parent());
        });

        $(window).resize(function() {
          Drupal.theme('devisSpecialBoxMeasureWidth', $('.navSpecial'), $anchor);
        });
        Drupal.theme('devisSpecialBoxMeasureWidth', $('.navSpecial'), $anchor);
      }
    }
  };
    
    /*Drupal.behaviors.devisStickyNav = {
      attach: function (context, settings) {
        var  mn = $('header.l-header-content');
        mns = 'navSticky';
        hdr = ($('header').height() < 100) ? 135 : $('header').height();

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
    };*/
  
  /** 
   * Function to control the select boxes of the regions in the user profile.
   */
  /*Drupal.behaviors.devisLegalDialogBox = {
    attach: function (context, settings) {
      $( "#dialog" ).dialog({
        autoOpen: false,
        dialogClass: 'dialog-box',
        width: 900,
        height: 400,
        modal: true,
        draggable: false,
        resizable: false,
        show: 'fade', 
        hide: 'fade',
        //minWidth: 500,
        //maxHeight: 600,
        //maxWidth: 800,
        //modal: true,
      });
      $( "#opener" ).click(function() {
        $( "#dialog" ).dialog( "open" );
        return false;
      });
    }
  };*/
  
  
  /** 
   * Function to control the select boxes of the regions in the user profile.
   */
  Drupal.behaviors.devisProviderProfileCheckbox = {
    attach: function (context, settings) {
      $("input[value='BEL']").click(function() {
        if ($(this).prop('checked')) {
          $("input[name*='field_active_regions_belgium']").prop('checked', false);
          $(this).prop('checked', true);
        }
      });
      
      $("input[name*='field_active_regions_belgium']").click(function() {
        if ($(this).prop('checked') && $(this).val() != 'BEL') {
          $("input[value='BEL']").prop('checked', false);
        }
      });
    }
  };
  
  
  Drupal.behaviors.devisDemanderDevis = {
    attach: function (context, settings) {
      //console.log(settings);
      if (settings.comptable) {
        // Add the mandatory mark to some fields.
        var title = $("label[for='edit-field-change-accountant-reason-und']").html();
        $("label[for='edit-field-change-accountant-reason-und']").html(title + settings.comptable.required);
        var title = $("label[for='edit-field-change-accountant-other-und-0-value']").html();
        $("label[for='edit-field-change-accountant-other-und-0-value']").html(title + settings.comptable.required);

        //console.log(settings);

        // Change the Company label depending on the legal status.
        // Change the TVA label depending on the legal status.
        // Change the prices.
        $("#edit-field-legal-status-und").change(function() {
          var val = $(this).val();
          var title = Drupal.theme('devisPrepareLabelHTML', 'company', val, settings.comptable.companyTitle, settings.comptable.required);
          $("label[for='edit-field-company-name-und-0-value']").html(title);
          var title = Drupal.theme('devisPrepareLabelHTML', 'tva', val, settings.comptable.tvaTitle, settings.comptable.required);
          $("label[for='edit-field-tva-und-0-value']").html(title);

          Drupal.theme('devisChangePricesFromLegalStatus', val, settings.prices);

        });
        var $object = $('form#comptable-entityform-edit-form');
        if ($object.length) {
          var val = $("#edit-field-legal-status-und").val();
          var title = Drupal.theme('devisPrepareLabelHTML', 'company', val, settings.comptable.companyTitle, settings.comptable.required);
          $("label[for='edit-field-company-name-und-0-value']").html(title);
          var title = Drupal.theme('devisPrepareLabelHTML', 'tva', val, settings.comptable.tvaTitle, settings.comptable.required);
          $("label[for='edit-field-tva-und-0-value']").html(title);
        }
      }
    }
  };
  
  Drupal.behaviors.devisDeleteUnnecessaryBr = {
    attach: function (context, settings) {
      $(".remove-br").find("br").remove();
    }
  };
  
  Drupal.behaviors.countdown = {
    attach: function (context, settings) {
      if (typeof settings.stats.minutesLeft != 'undefined') {
        $('#defaultCountdown').countdown({
          until: '+'+ settings.stats.minutesLeft +'m', 
          format: 'HMS', 
          description: settings.stats.description,
          expiryText: settings.stats.expiryText,
          onExpiry: noLongerValid,
        });

        function noLongerValid() { 
          $("input#edit-submit").remove();
        }
      }
    }
  };
  
  Drupal.behaviors.devisFAQ = {
    attach: function (context, settings) {
      
      $('.faq-answer-devis').hide();
      
      $('.faq-question-devis').click(
      function() {
        var toggle = $(this).nextUntil('.faq-question-devis');
        toggle.slideToggle();
        //$('.faq-answer-devis').not(toggle).slideUp();
        return false;
      });
    }
  };
  
  setTimeout(function() {
    $(document).ready(function() {
      $('select.error').parent().parent().addClass('dropdown-error');
    });
  }, 20);


})(jQuery);