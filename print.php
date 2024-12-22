<?php
	
	/**
	 * Includes
	 */
	
	require_once( "inc/dbfunc.php" );
	require_once( "inc/print_functions.php");
	require_once( "inc/functions/date_conversion.php");
	require_once( "inc/dev_tools/print_dev.php" );
	
	
	/**
	 * Variables
	 */
		
	$result = array();
	$result['productionOrder'] = array();
	$pour = array();
	$strip = array();
	$date = date('m-d-Y');
	$mysqlDate = date('Y-m-d');
	
	if ( isset( $_POST['overlay_schedule_day'] ) )
	{
		$date = mysql_to_date( $_POST['overlay_schedule_day'] );
		$mysqlDate = $_POST['overlay_schedule_day'];
	}
	
	//print_dev( $pour );
	
	/**
	 * MySQL
	 */
	
	// Batching
	
	$query = "
		SELECT
			*
		FROM
			productionOrderScheduleBatching
		WHERE
			pourDate = ?
	";
	
	$values = array(
		$mysqlDate
	);
	
	$result['batching'] = dbquery( $query, $values );
	
	$pour = json_decode( $result['batching'][0]['batchingString'], true );
	
	// Production Orders
	
	foreach ( $pour as $hour => $productionOrders )
	{
		
		foreach ( $productionOrders['productionOrder'] as $productionOrder => $material )
		{
		
			$query = "
				SELECT
					*,
					IF(
						c.company is null,
						p.product,
						CONCAT( c.company, ' ', p.product)
					) as 'product',
					poml.quantity as 'quantity',
					pos.quantity as 'scheduleQuantity',
					po.notes as 'notes'
				FROM
					productionOrder po
				LEFT JOIN
					productionOrderMaterialLink poml
					ON po.productionOrderID = poml.productionOrder
				LEFT JOIN
					material m
					ON poml.material = m.materialID
				LEFT JOIN
					vibrationType vt
					ON poml.vibrationType = vt.vibrationTypeID
				LEFT JOIN
					measure me
					ON m.measure = me.measureID
				LEFT JOIN
					productConsumerLink pcl
					ON po.product = pcl.product
				LEFT JOIN
					product p
					ON po.product = p.productID
				LEFT JOIN
					companyLocationLink cll
					ON pcl.companyLocationLink = cll.companyLocationLinkID
				LEFT JOIN
					company c
					ON cll.company = c.companyID
				LEFT JOIN
					formProductLink fpl
					ON fpl.product = po.product
				LEFT JOIN
					form f
					ON fpl.form = f.formID
				LEFT JOIN
					productionOrderSchedule pos
					ON po.productionOrderID = pos.productionOrder
				WHERE
					po.productionOrderID = ?
				AND
					pos.pourDate = ?
				ORDER BY
					poml.quantity DESC
			";
		
			$values = array(
				$productionOrder,
				$mysqlDate
			);
		
			$result['productionOrder'][ $productionOrder ] = dbquery( $query, $values );
			
		}
		
	}
	
	
	// Stripping
	
	$query = "
		SELECT
			IF(
				c.company is null,
				p.product,
				CONCAT( c.company, ' ', p.product)
			) as 'product'
		FROM
			productionOrderSchedule pos
		LEFT JOIN
			productionOrder po
			ON pos.productionOrder = po.productionOrderID
		LEFT JOIN
			productConsumerLink pcl
			ON po.product = pcl.product
		LEFT JOIN
			product p
			ON po.product = p.productID
		LEFT JOIN
			companyLocationLink cll
			ON pcl.companyLocationLink = cll.companyLocationLinkID
		LEFT JOIN
			company c
			ON cll.company = c.companyID
		WHERE
			stripDate = ?
	";
	
	$values = array(
		$mysqlDate
	);
	
	$result['stripping'] = dbquery( $query, $values );
		
	
	// Mixers
	
	$query = "
		SELECT
			*
		FROM
			mixer mix
		WHERE
			mix.active = 1
	";
	
	$values = array();
	
	$result['mixers'] = dbquery( $query, $values );
		
	
	/**
	 * Process
	 */
	
	foreach( $result['stripping'] as $row )
	{
		$strip[] = $row['product'];
	}
		
	// Production Orders
		
	$productionOrders = productionOrder();
	
	//print_dev( $productionOrders );
	
	
	/**
	 * Display
	 */
	
?>

<html>

	<head>
		<link rel="stylesheet" href="css/print_schedule.css" type="text/css" charset="utf-8">
		<link rel="stylesheet" href="css/print_productionOrder.css" type="text/css" charset="utf-8">
		
		<script src='js/jquery-1.9.1.js'></script>
	</head>

	<body>

	<?php
	
		require_once( "inc/print/schedule.php" );
		
		
		foreach( $productionOrders as $productionOrder )
		{
			require( "inc/print/productionOrder.php" );
		}
	
	?>
	
	<script>
	
		var check = true;
	
		while ( check )
		{
			
			check = false;
		
			$(".subpage").each( function () {
			
				var subPage = $(this);
				var maxHeight = $(this).innerHeight();
				var tempHeight = 0;
				var addPage = true;
				var pageLink = undefined;
			
				$(this).children().each( function () 
				{
				
					tempHeight += $(this).outerHeight( true );
				
					if ( tempHeight > maxHeight )
					{
					
						if ( addPage )
						{
							pageLink = $("<div class='page'><div class='subPage'><div class='title label'><div class='left'></div><div class='right'><div>PRODUCTION ORDER</div><div><?php echo $date; ?></div></div><div class='clearMe'></div></div></div></div>");
							subPage.parent().after( pageLink );
							addPage = false;
							check = true;
						}
					
						$(this).appendTo(pageLink.find(".subPage"));
						//console.log( pageLink.find(".subPage") );
					}
				
				});
			
			});
			
		}
		
		$(".book").each( function () {
			
			var bookHolder = $(this);
			var count = $(this).find(".page").length;
						
			if ( ( count % 2 ) == 1 )
			{
				bookHolder.append("<div class='page'><div class='subPage'></div></div>");
			}
			
		});
		
		window.print();
		
	</script>

	</body>

</html>