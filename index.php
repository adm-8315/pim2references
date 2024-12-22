<?php


	/**
	 * Includes
	 */
	
	require_once( "inc/page.php" );
	require_once( "inc/dbfunc.php" );
	require_once( "inc/session.php" );
	require_once( "inc/permissions.php" );
	require_once( "inc/nav.php" );
	
	
	/**
	 * Variables
	 */
	
	$result = array();
	date_default_timezone_set( 'America/Detroit' );
	
	
	/**
	 * Print
	 */
	
	if ( 
		isset( $_GET['nav'] ) &&
		$_GET['nav'] == 'print' 
	) {
		require_once( "print.php" );
		exit();
	}
	
	
?>

<!DOCTYPE html>
<html lang="en">
	<head>

	<?php
	
		echo "<title>P.I.M.S.";
	
		if ( $page['domain'] == 'localhost' )
		{
			echo " - DEV";
		}
	
		echo "</title>";
	
	?>
	
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="cache-control" content="max-age=0" />
	<meta http-equiv="cache-control" content="no-cache" />
	<meta http-equiv="expires" content="0" />
	<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
	<meta http-equiv="pragma" content="no-cache" />
	
	<link rel="shortcut icon" id="favicon" type="image/x-icon" href="favicon.ico?v=2">

	<?php
		echo "<!-- CSS -->";
		require_once("inc/css.php");
		
		echo "<!-- Javascript -->";
		require_once("inc/js.php");
	?>
	
    <!-- HTML5 shiv, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="js/html5shiv.js"></script>
	<![endif]-->

  </head>

  <body class='preload'>
	  
	<?php //print_r( $_SESSION ); ?>
	
	<div id="print"></div>
	<div id="screen_overlay"></div>
	<div id="screen_overlay_content"></div>
	
	<p id="spinner">Please wait while we do what we do best.</p>

    <div id="wrap">
	<?php
	
		if ( ! $load_login )
		{
			require_once( "inc/header.php" );
		}
		
	?>
		
	<div class="container">
	
		<!-- START CONTENT -->

		<?php

			if ( $load_login )
			{
				require_once( "login.php" );
			}
			else
			{
				require_once("inc/toolbar/toolbar.php");
				require_once("inc/menu.php");
				require_once( $_GET['nav'] . '.php' );
			}

		?>

		<!-- END CONTENT -->
      
	    	</div>

	    <div id="push"></div>
	</div>

	<?php
		if ( ! $load_login )
		{
			require_once( "inc/footer.php" );
		}
	?>

  </body>
</html>