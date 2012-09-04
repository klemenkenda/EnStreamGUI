var wrapper;
		var target;
		var current;
		
		function prepareObj(obj) {
		  // cache the copy of jQuery(this) - the start image
      var $$ = obj;

			// get the target from the backgroundImage + regexp
      target = slika[1];
			current = $$.attr("src");
			
      // nice long chain: wrap img element in span
      wrapper = $$.wrap('<span id="span-img" style="position: absolute;"></span>')
        // change selector to parent - i.e. newly created span
        .parent()
        // prepend a new image inside the span
        .prepend('<img>')
        // change the selector to the newly created image
        .find(':first-child')
        // set the image to the target
        .attr('src', target);		
			
			// the CSS styling of the start image needs to be handled
      // differently for different browsers
      if ($.browser.msie || $.browser.mozilla) {
          $$.css({
              'position' : 'absolute', 
              'left' : 0,
              'background' : '',
              'top' : this.offsetTop
          });
      } else if ($.browser.opera && $.browser.version < 9.5) {
          // Browser sniffing is bad - however opera < 9.5 has a render bug 
          // so this is required to get around it we can't apply the 'top' : 0 
          // separately because Mozilla strips the style set originally somehow...                    
          $$.css({
              'position' : 'absolute', 
              'left' : 0,
              'background' : '',
              'top' : "0"
          });
      } else { // Safari
          $$.css({
              'position' : 'absolute', 
              'left' : 0,
              'background' : ''
          });
      }
		}
		
		function prepareImg() {
		 
			
			current = target;
			target = slika[Math.floor(Math.random() * slika.length)];
			
			
			$("#img-section")
			  .attr('src', current);
			$("#img-section")
				.css({'opacity':1});
				
      $("#span-img").find(":first-child")
			  .attr('src', target);
			// alert(current + " " + target);
		
		}
		
		function crossFade(obj) {
		  // cache the copy of jQuery(this) - the start image
      var $$ = obj;

      // similar effect as single image technique, except using .animate 
      // which will handle the fading up from the right opacity for us
      
			$$.stop().animate({
         opacity: 0
      }, 500).queue(function() {
			  prepareImg();		
			});
			
		}
