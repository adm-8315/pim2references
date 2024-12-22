<?php
	
	/**
	 * Includes
	 */

	require_once("inc/dbfunc.php");


	/**
	 * Variables
	 */

	$result = array();
	$dates = array();
	$shortDate = array();
	$style = array();
	$days = array(
		0 => 'sun',
		1 => 'mon',
		2 => 'tue',
		3 => 'wed',
		4 => 'thu',
		5 => 'fri',
		6 => 'sat',
	);
	
	
	/**
	 * Processing
	 */
	
	$textToday = date('Y-m-d');
	$today = strtotime( $textToday );
	
	if ( 
		isset($_GET['week']) &&
		$_GET['week'] != 0
	) {
		$today = strtotime( date('Y-m-d', strtotime( $_GET['week'] . " week")) );
	}
	
	
	
	for ( $i = 0; $i < 7; $i++ )
	{	
		
		$dates[$i] = date('Y-m-d', $today - ((date('w', $today) - $i) *60*60*24));
		$shortDate[$i] = date('n/j', $today - ((date('w', $today) - $i) *60*60*24));
		
		if ( $textToday == $dates[$i] ) // Today
		{
			$style[$i] = array('today');
		}
		else if ( $textToday > $dates[$i] ) // Before Today
		{
			$style[$i] = array('earlier');
		}
		else // After Today
		{
			$style[$i] = array('later');
		}
		
	}
	
	/**
	 * MySQL
	 */
	
	$values = array();
	
	$query = "
		SELECT
			po.productionOrderID,
			IF(
				c.company is null,
				p.product,
				CONCAT( c.company, ' ', p.product)
			) as 'product',
			pos.pourDate,
			pos.stripDate,
			pos.fireDate
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
	";
	
	foreach( $dates as $date )
	{
		$query .= "pourDate = ? OR stripDate = ? OR fireDate = ? OR ";
		$values[] = $date;
		$values[] = $date;
		$values[] = $date;
	}
	
	$query = rtrim( $query, ' OR ');
	
	$query .= "
		ORDER BY
			IF(
				c.company is null,
				p.product,
				CONCAT( c.company, ' ', p.product)
			) ASC
	";
	
	$result['schedule'] = dbquery( $query, $values );
?>

<div class='content'>
	
	<?php
		
		foreach( $days as $index => $day )
		{
	?>
	
	<div id='<?php echo $day; ?>' data-date='<?php echo $dates[$index]; ?>' class='scheduleColumn <?php foreach( $style[$index] as $class) { echo $class . ' '; } ?>'>
		<div class='title'><?php echo $days[$index] . " " . $shortDate[$index]; ?></div>
		<div class='pour'>
			<div class='title'>Pour</div>
			<div class='product_scroll'>
				<?php
					foreach( $result['schedule'] as $row )
					{
					
						if ( $row['pourDate'] == $dates[$index] )
						{
							echo "<div class='productLine' data-productionorder='{$row['productionOrderID']}' data-pourdate='{$row['pourDate']}'>{$row['product']}</div>";
						}
					}
				?>
			</div>
			<div class='schedule_trigger'>edit</div>
		</div>
		<div class='strip'>
			<div class='title'>Strip</div>
			<div class='product_scroll'>
				<?php
					foreach( $result['schedule'] as $row )
					{
						
						if ( $row['stripDate'] == $dates[$index] )
						{
							echo "<div class='productLine' data-productionorder='{$row['productionOrderID']}' data-pourdate='{$row['pourDate']}'>{$row['product']}</div>";
						}
					}
				?>
			</div>
			<div class='schedule_trigger'>edit</div>
		</div>
		<div class='fire'>
			<div class='title'>Fire</div>
			<div class='product_scroll'>
				<?php
					foreach( $result['schedule'] as $row )
					{
						
						if ( $row['fireDate'] == $dates[$index] )
						{
							echo "<div class='productLine' data-productionorder='{$row['productionOrderID']}' data-pourdate='{$row['pourDate']}'>{$row['product']}</div>";
						}
					}
				?>
			</div>
			<div class='schedule_trigger'>edit</div>
		</div>
		<div class='ship'>
			<div class='title'>Ship</div>
			
			<div class='schedule_trigger'>edit</div>
		</div>
		<div class='print'>
			<div class='title'>Print</div>
			<div class='schedule_trigger print_production'>print production</div>
			<!--<div class='schedule_trigger print_firing'>print firing</div>-->
			<!--<div class='schedule_trigger print_shipping'>print shipping</div>-->
		</div>
		
	</div>
		
	<?php
		}
	?>
	
</div>