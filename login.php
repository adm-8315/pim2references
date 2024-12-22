

<form class="form-signin" method="post"  <?php if ( $load_home ) { echo "action='index.php'"; } ?> >
	<h2 class="form-signin-heading">Please Sign In</h2>
	
	<?php
		if ( ! empty($message) )
		{ 
			echo "<span class='message'>{$message}</span>"; 
		}
	?>
	
	<input type="text" class="input-block-level" placeholder="Username" name="username"
	<?php 
		if ( isset($_POST['username']) ) 
		{
			echo "value=\"{$_POST['username']}\"";
		} 
	?> />
	<input type="password" class="input-block-level" placeholder="Password" name="password">
	
	<button class="button" type="submit" name="submit">Sign in</button>
</form>

<script>
<!--
	(function ($) {
		
		$(document).ready( function () {
			$(".button").button();
			$(".button").addClass('login');
			$(".button").find('span.ui-button-text').css("padding","0");
		});
		
	}(jQuery));
-->
</script>