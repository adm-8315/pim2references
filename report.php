<?php
	
	header('Access-Control-Allow-Origin: *'); 
	
	/**
	 * Variables
	 */
	
	$result =  array();
	$error = false;
	$headerArray = array();
	$query = "";
	$values = array();
	$valueTotal = 0;
	$location = array();
	$date = array();
	
	
	/**
	 * Validation
	 */
				
	
	// Location
	
	if ( isset( $_GET['location'] ) )
	{
		
		$query = "
			SELECT
				l.locationID,
				l.location
			FROM
				location l
			WHERE
				l.stockable = 1
			AND
				l.locationID = ?
			LIMIT 1
		";
		
		$values = array(
			$_GET['location']
		);
		
		$result['location'] = dbquery( $query, $values );
		
	}
	
	if ( empty( $result['location'] ) )
	{
		
		
		
		$query = "
			SELECT
				u.defaultLocation as 'locationID',
				l.location as 'location'
			FROM
				user u
			LEFT JOIN
				location l
				ON u.defaultLocation = l.locationID
			WHERE
				u.userID = ?
			LIMIT 1
		";
		
		$values = array(
			$_SESSION['user_id']
		);
		
		$result['location'] = dbquery( $query, $values );
		
	}
	
	
	// Owner
	
	$query = "
		SELECT
			c.companyID as 'id',
			c.company as 'owner',
			u.defaultOwner
		FROM
			company c
		LEFT JOIN
			user u
			ON u.defaultOwner = c.companyID
			AND	u.userID = ?
		LEFT JOIN
			companyCompanyPropertyLink ccpl
			ON ccpl.company = c.companyID
		WHERE
			ccpl.companyProperty = 1
	";

	$values = array(
		$_SESSION['user_id']
	);

	$result['owner'] = dbquery( $query, $values );
	
	
	/**
	 * Functions
	 */
	
	// Report Head

	function reportHead ( $head, $date = null, $valueTotal = null )
	{

		echo "
			<div class='app_report_table app_report_head'>
				<table>
					<thead>
		";
	
		// Title
	
		echo "
			<tr>
				<th class='head_row'><div><span class='head'>{$head}</span>
		";
	
		// Date
		
		if ( $date != null )
		{
	
			echo "
							<span class='date'>
			";
	
			if ( isset( $date['begin'] ) )
			{
				$temp = explode( "-", $date['begin'] );
		
				echo $temp[1] . " / " . $temp[2] . " / " . $temp[0] . "&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;"; 
				//echo $date['begin'] . " - ";
			}
	
			$temp = explode( "-", $date['end'] );
	
			echo $temp[1] . " / " . $temp[2] . " / " . $temp[0];
	
			echo "
						</span></div></tr></thead>
			";
			
		}
	
		// Value
	
		if ( isset( $valueTotal ) )
		{
			echo "
						<tr>
							<th class='title'> Total Value: ( $" .  number_format( $valueTotal, 2) . " )</th>
						</tr>
			";
		}
	
		echo "
				</table>
			</div>
		";

	}
	
	// Report

	function report ( $table_headers, $table_data, $table_title = null)
	{
		
		global $permissions;
		
		$skipColumns = array(
			'id',
			'idOverride',
			'nav',
			'overlay',
			'warning'
		);
	
		if ( ! empty( $table_title ) )
		{
			
			echo "<div class='title'>{$table_title}";
			
			if (
				(
					isset( $permissions[1][1] ) ||
					isset( $permissions[5][43] )
				) &&
				$table_title == "OpenOrders"
			) {
				echo "
					<button id='productionOrder_new' style='z-index: 9998'>
						<span class='plus_button_image'>&nbsp;</span>
					</button>
				";
			}
						
			echo "</div>";
			
		}
	
		echo '
			<div class="app_report_table">
				<table>
					<thead>
		';
		
			echo "<tr>";
				echo "<th>#</th>";

			foreach ( $table_headers as $column )
			{
				echo "<th>{$column}</th>";
			}

			echo "</tr></thead>";
	
			if ( empty( $table_data ) )
			{
		
				echo "<tr class='blank'>";
					$tempWidth = count( $table_headers) + 1;
					echo "<td colspan='{$tempWidth}'>No Results Found.</td>";
				echo "</tr>";
		
			}
			else
			{
		
				foreach ( $table_data as $index => $row )
				{
				
					$rowNum = $index + 1;
					
					echo "<tr ";
					
					if ( isset( $row['nav'] ) )
					{
						echo "data-nav='{$row['nav']}' ";
					}
					
					if ( isset( $row['overlay'] ) )
					{
						echo "data-overlay='{$row['overlay']}' ";
					}
					
					if ( isset( $row['pourDate'] ) )
					{
						echo "data-pourDate='{$row['pourDate']}' ";
					}
					
					if ( 
						isset( $row['warning'] ) &&
						$row['warning'] == '1'
					) {
						echo "class='reportWarning' ";
					}
					
					if ( isset($row['idOverride']) )
					{
						echo "data-id='{$row['idOverride']}'>";
							echo "<td>{$row['idOverride']}</td>";
					} 
					else
					{
						echo "data-id='{$row['id']}'>";
							echo "<td>{$rowNum}</td>";
					}
					
			
					foreach ( $row as $column => $value )
					{
						if ( in_array( $column, $skipColumns ) )
						{
							continue;
						}
						
						if ( strtolower( $column ) == 'product' || strtolower( $column ) == 'material' )
						{
							echo "<td><div style='page-break-inside:avoid;' class='name'>{$value}</div></td>";
						}
						else
						{
							echo "<td><div style='page-break-inside:avoid;'>" . $value . "</div></td>";
						}
						
					}
					echo "</tr>";
			
				}
		
			}

		echo "</table>";
		echo "</div>";
	}
	
	
	/**
	 * Process
	 */
	
	// Location
	
	$location = array( 'id' => $result['location'][0]['locationID'], 'location' => $result['location'][0]['location'] );
	
	
	// Date
	
	if (
		isset( $_GET['date_end'] ) && 
		count( explode( '/', $_GET['date_end'] ) ) == 3
	) {
		
		$temp = explode( '/', $_GET['date_end'] );
		$date['end'] = $temp[2] . '-' . $temp[0] . '-' . $temp[1];
		
	} 
	else 
	{
		$date['end'] = date("Y-m-d");
	}
	
	if (
		isset( $_GET['date_begin'] ) && 
		count( explode( '/', $_GET['date_begin'] ) ) == 3
	) {
		
		$temp = explode( '/', $_GET['date_begin'] );
		$date['begin'] = $temp[2] . '-' . $temp[0] . '-' . $temp[1];
		
	} 
	else 
	{
		if ( 
			$_GET['report'] == "raw_material_stock" ||
			$_GET['report'] == "finished_product_stock" ||
			$_GET['report'] == "tools_accessories_stock" ||
			$_GET['report'] == "stock_value" ||
			$_GET['report'] == "tools_accessories_value"
		) {
			$date['begin'] = null;
		}
		else
		{
			$date['begin'] = date("Y-m-01");
		}
	}
	
	
	// Owner
		
	foreach ( $result['owner'] as $row )
	{
		if ( isset($_GET['owner']) && $row['id'] == $_GET['owner'] )
		{
			
			$owner['id'] = $row['id'];
			$owner['owner'] = $row['owner'];
			
		}
		else if ( ! isset($_GET['owner']) && isset($row['defaultOwner']) )
		{
			
			$owner['id'] = $row['id'];
			$owner['owner'] = $row['owner'];
			
		}
		
	}
	
	
	// Report
	
	if ( file_exists( 'report/' . $_GET['report'] . '.php' ) )
	{
		include_once( 'report/' . $_GET['report'] . '.php' );
	}
	
	
	/**
	 * Display
	 */
	
	echo "<div class='content'>";

	switch ( $_GET['report'] )
	{
	
		case 'raw_material_stock':
			$head = "Raw Material Stock Report for {$location['location']}";
			reportHead( $head, $date );
			report( $headerArray, $result['report'] );
			break;
		
		case 'finished_product_stock':
			$head = "Finished Product Stock Report for {$location['location']}";
			reportHead( $head, $date );
			foreach ( $result['report'] as $key => $materialType )
			{
				report( $headerArray, $materialType, $key );
			}
			break;
		
		case 'tools_accessories_stock':
			$head = "Tools and Accessories Stock Report for {$location['location']}";
			reportHead( $head, $date );
			report( $headerArray, $result['report'] );
			break;
		
		case 'stock_value':
			$head = "Stock Value Report for {$owner['owner']} at {$location['location']}";
			reportHead( $head, $date, $valueTotal );
			foreach ( $result['report'] as $key => $materialType )
			{
				report( $headerArray, $materialType, $key );
			}
			break;
		
		case 'tools_accessories_value':
			$head = "Tools and Accessories Value Report for {$owner['owner']} at {$location['location']}";
			reportHead( $head, $date );
			foreach ( $result['report'] as $key => $materialType )
			{
				report( $headerArray, $materialType, $key );
			}
			break;
	
		case 'material_usage':
			$head = "Material Usage Report for {$owner['owner']} at {$location['location']}";
			reportHead( $head, $date );
			report( $headerArray, $result['report'] );
			break;
		
		case 'production':
			$head = "Production Report for {$owner['owner']} at {$location['location']}";
			reportHead( $head, $date );
			foreach ( $result['report'] as $key => $materialType )
			{
				report( $headerArray, $materialType, $key );
			}
			break;
		
		case 'finished_product_sales':
			$head = "Finished Product Sales Report for {$owner['owner']} at {$location['location']}";
			reportHead( $head, $date );
			foreach ( $result['report'] as $key => $materialType )
			{
				report( $headerArray, $materialType, $key );
			}
			break;
		
		case 'scrap':
			$head = "Scrap Report for {$owner['owner']} at {$location['location']}";
			reportHead( $head, $date );
			foreach ( $result['report'] as $key => $materialType )
			{
				report( $headerArray, $materialType, $key );
			}
			break;
		
		case 'inventory_adjustment':
			$head = "Inventory Adjustment Report at {$location['location']}";
			reportHead( $head, $date );
			foreach ( $result['report'] as $key => $materialType )
			{
				report( $headerArray, $materialType, $key );
			}
			break;
			
		case 'precastProduct':
			$head = "Precast Products";
			reportHead( $head, $date );
			report( $headerArray, $result['report'] );
			break;
		
		case 'openOrder':
			$head = "Open Orders";
			reportHead( $head, array( 'end' => (new DateTime('today'))->format('Y-m-d') ) );
			report( $headerArray, $result['report'] );
			break;
		
		case 'job':
			$head = "Jobs";
			reportHead( $head );
			report( $headerArray, $result['report'] );
			break;
		
		case 'equipment':
			$head = "Equipment";
			reportHead( $head );
			report( $headerArray, $result['report'] );
			break;
		
		case 'grouping':
			$head = "Grouping";
			reportHead( $head );
			report( $headerArray, $result['report'] );
			break;
		
		case 'item':
			$head = "Item";
			reportHead( $head );
			report( $headerArray, $result['report'] );
			break;
		
		case 'shortage':
			$head = "Shortage";
			reportHead( $head, $date );
			report( $headerArray, $result['report'] );
			break;
			
		case 'warning':
			$head = "Stock Level Warnings";
			reportHead( $head );
			foreach ( $result['report'] as $key => $materialType )
			{
				report( $headerArray[$key], $materialType, $key );
			}
			break;
	
		default:
			echo "<h4>404: That page does not exist.</h4>";
			$error = true;
			break;
		
	}
	
	echo "</div>";

?>

<script>

	( function ($) {

		$(document).on("click", ".app_report_table tr", function() {

			if ( $(this).attr("data-nav") != undefined && $(this).attr("data-nav") != 'overlay' ) {

				var url_in_split = document.URL.split("?");
				window.location.href = url_in_split[0] + "?nav=" + $(this).attr("data-nav") + "&id=" + $(this).attr("data-id");
			}

		});
	
	})(jQuery);

</script>