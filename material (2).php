<?php

	/**
	 * Variables
	 */
	
	require_once('inc/functions/date_conversion.php');
	
	
	/**
	 * Variables
	 */
	
	$result = array();
	
	
	/**
	 * MySQL
	 */
	
	// Material
	
	$query = "
		SELECT
			m.materialID as 'id',
			m.materialType as 'materialTypeID',
			mml.company as 'manufacturerID',
			CONCAT( '$', convert( m.cost, DECIMAL(11,2) ) ) as 'cost',
			m.material as 'material',
			mt.materialTypeID as 'materialTypeID',
			mt.materialType as 'materialType',
			c.company as 'manufacturer',
			m.waterLow,
			m.waterHigh,
			m.mixLow,
			m.mixHigh,
			m.taps,
			m.lowerSpec,
			m.upperSpec,
			m.stdWater,
			m.stdMix
		FROM
			material m
		LEFT JOIN
			materialType mt
			ON m.materialType = mt.materialTypeID
		LEFT JOIN
			materialManufacturerLink mml
			ON mml.material = m.materialID
		LEFT JOIN
			company c
			ON mml.company = c.companyID
		WHERE
			m.materialID = ?
	";

	$values = array(
		$_GET['id']
	);

	$result['material'] = dbquery( $query, $values );
	
	
	// Exothermal
	
	$query = "
		SELECT
			qcTestID,
			startTime,
			stopTime,
			lotcode
		FROM
			qcTest
		WHERE
			material = ?
		ORDER BY
			startTime DESC
	";
	
	$values = array( 
		$_GET['id'] 
	);
	
	$result['exotherm'] = dbquery( $query, $values );
	
	
	// Location
	
	if ( isset( $permissions[3][2] ) > 0 )
	{
	
		$values = array();
	
		$query = "
			SELECT
				*
			FROM
				location l
			WHERE
		";
	
		foreach( $permissions[3][2] as $location => $enabled )
		{
			$query .= " l.locationID = ? OR ";
			$values[] = $location;
		}
		
		$query = substr( $query, 0, -3 );
		
		$query .= " ORDER BY l.location ASC";
		
		$result['location'] = dbquery( $query, $values );
	
	}
	else if ( isset( $permissions[1][1] ) )
	{
		
		$query = "
			SELECT
				l.locationID,
				l.location
			FROM
				location l
			WHERE
				stockable = 1
			ORDER BY
				l.location ASC
		";
		
		$result['location'] = dbquery( $query, array() );
		
	}
	else
	{
		$result['location'] = array();
	}
	
	
	// Inventory
	
	$query = "
		SELECT
			cll.companyLocationLinkID as 'companyLocationLinkID',
			cll.location as 'locationID',
			l.location as 'location',
			cll.company as 'ownerID',
			c.company as 'owner',
			i.materialInventoryID as 'materialInventoryID',
			IF(
				i.stock is null,
				CONCAT(
					0,
					' ',
					me.measurePlural
				),
				CONCAT(
					FORMAT( i.stock, 0),
					' ',
					IF(
						i.stock = 1,
						me.measureSingular,
						me.measurePlural
					)
				)
			) as 'stock',
			i.stockLevelWarning as 'stockLevelWarning'
		FROM
			companyLocationLink cll
		LEFT JOIN
			location l
			ON cll.location = l.locationID
		LEFT JOIN
			company c
			ON cll.company = c.companyID
		LEFT JOIN
			companyCompanyPropertyLink ccpl
			ON ccpl.company = c.companyID
		LEFT JOIN
			materialInventory i
			ON cll.companyLocationLinkID = i.companyLocationLink
			AND i.material = ?
		JOIN
			(
				SELECT
					me.*
				FROM
					material ma
				LEFT JOIN
					measure me
					ON ma.measure = me.measureID
				WHERE
					ma.materialID =  ?
			) as me
		WHERE
			l.stockable = 1
		AND
			ccpl.companyProperty = 1
		ORDER BY
			l.location ASC,
			c.company ASC
	";
	
	$values = array(
		intval( $_GET['id'] ),
		intval( $_GET['id'] )
	);
	
	$result['inventory'] = dbquery( $query, $values );
	
	
	// Transaction
	
	$query = "
		SELECT
			*,
			@num := if(@type = temp2.transactionType, @num + 1, 1) as row_number,
		    @type := temp2.transactionType as dummy
		FROM
			(
				SELECT
					@type := 0,
					@num := 0
			) temp
		JOIN
			(
				SELECT
					mt.materialTransactionID as 'materialTransactionID',
					mt.materialInventory as 'materialInventoryID',
					c1.company as 'owner',
					l1.location as 'location',
					mt.transactionType as 'transactionType',
					IF(
						mt.value is null,
						CONCAT(
							0,
							' ',
							me.measurePlural
						),
						CONCAT(
							FORMAT( mt.value, 0),
							' ',
							IF(
								mt.value = 1,
								me.measureSingular,
								me.measurePlural
							)
						)
					) as 'value',
					mt.cost as 'cost',
					c2.company as 'customer',
					l2.location as 'shippingLocation',
					mt.user as 'userID',
					u.username as 'user',
					mt.timestamp as 'timestamp',
					mt.notes as 'notes'
				FROM
					permissionLink pl
				LEFT JOIN
					(
						SELECT
							*
						FROM
							materialInventory i
						LEFT JOIN
							companyLocationLink cll
							ON i.companyLocationLink = cll.companyLocationLinkID
						WHERE
							i.material = ?
					) temp
					ON IF (
						pl.permissionBlock = 1,
						1 = 1,
						IF(
							pl.allLocation = 1,
							1 = 1,
							temp.location = pl.location
						)
					)
				LEFT JOIN
					materialTransaction mt
					ON mt.materialInventory = temp.materialInventoryID
					AND IF(
						pl.permissionBlock = 1,
						1 = 1,
						IF(
							pl.permissionBlock = 12,
							mt.transactionType = 1,
							IF(
								pl.permissionBlock = 13,
								mt.transactionType = 2,
								IF(
									pl.permissionBlock = 14,
									mt.transactionType = 3,
									IF(
										pl.permissionBlock = 15,
										mt.transactionType = 4,
										IF(
											pl.permissionBlock = 16,
											mt.transactionType = 5,
											IF(
												pl.permissionBlock = 17,
												mt.transactionType = 6,
												IF(
													pl.permissionBlock = 18,
													mt.transactionType = 7,
													1 = 0
												)
											)
										)
									)
								)
							)
						)
					)
					AND IF(
						pl.permissionBlock = 1,
						1 = 1,
						mt.user = pl.user
					)
				LEFT JOIN
					material ma
					ON temp.material = ma.materialID
				LEFT JOIN
					measure me
					ON ma.measure = me.measureID
				LEFT JOIN
					companyLocationLink cll1
					ON temp.companyLocationLink = cll1.companyLocationLinkID
				LEFT JOIN
					location l1
					ON cll1.location = l1.locationID
				LEFT JOIN
					company c1
					ON cll1.company = c1.companyID
				LEFT JOIN
					companyLocationLink cll2
					ON mt.companyLocationLink = cll2.companyLocationLinkID
				LEFT JOIN
					location l2
					ON cll2.location = l2.locationID
				LEFT JOIN
					company c2
					ON cll2.company = c2.companyID
				LEFT JOIN
					user u
					ON mt.user = u.userID
				WHERE
					pl.user = ?
				AND
					mt.materialTransactionID is not null
				AND
					mt.transactionType != 2
				ORDER BY
					mt.transactionType ASC,
					mt.timestamp DESC,
					mt.materialTransactionID DESC
			) temp2
		GROUP BY
			temp2.transactionType,
			temp2.timestamp,
			temp2.materialTransactionID
		HAVING
			row_number <= 1000
		ORDER BY
			temp2.transactionType ASC,
			temp2.timestamp DESC,
			temp2.materialTransactionID DESC
	";
	
	$values = array(
		$_GET['id'],
		$_SESSION['user_id']
	);
	
	$result['transaction'] = dbquery( $query, $values );
	
	
	/**
	 * Display
	 */
?>

<div class='content'>
		
	<div class='material_container'>
		<div class='material_wrapper'>
			
			<div class='material_title'>
				<?php 
					if ( ! empty( $result['material'] ) ) 
					{ 
						echo $result['material'][0]['material']; 
				?>&nbsp;&nbsp;
				<button id='material_edit' data-material='<?php echo $_GET['id']; ?>'  style='z-index: 9998'>
					<span class='adjust_button_image'>&nbsp;</span>
				</button>
			</div>
			
			<table class='material_table'>
				
				<tr>
					<td class='left'>Material Type</td>
					<td class='right'><?php echo $result['material'][0]['materialType']; ?></td>
				</tr>
				
				<tr>
					<td class='left'>Manufacturer</td>
					<td class='right'><?php echo $result['material'][0]['manufacturer']; ?></td>
				</tr>
				
				<?php
				
				if (
					( // Finished Product
						(
							$result['material'][0]['materialTypeID'] == 16 ||
							$result['material'][0]['materialTypeID'] == 26
						) && (
							isset( $permissions[1][1] ) ||
							isset( $permissions[3][9] )
						)
					) ||
					( // Tools and Accessories
						$result['material'][0]['materialTypeID'] == 24 &&
						(
							isset( $permissions[1][1] ) ||
							isset( $permissions[3][6] )
						)
					) ||
					( // Material
						$result['material'][0]['materialTypeID'] != 16 &&
						$result['material'][0]['materialTypeID'] != 24 &&
						$result['material'][0]['materialTypeID'] != 26 &&
						(
							isset( $permissions[1][1] ) ||
							isset( $permissions[3][5] )
						)	
					)
				) {
					
				echo "
				<tr>
					<td class='left'>Cost</td>
					<td class='right'>{$result['material'][0]['cost']}</td>
				</tr>
				";
				
				}
				
				?>
				
			</table>
			
			<input type="hidden" id="overlay_pageCount" value="2" />
			<input type="hidden" id="overlay_material" value="<?php echo $_GET['id']; ?>" />
			<input type="hidden" id="overlay_material_category" value="1" />
			<input type="hidden" id="overlay_owner" />
			<input type="hidden" id="overlay_location" />
			
		</div>
	</div>
	
	
	<?php
		
		if ( 
			(
				$result['material'][0]['materialTypeID'] == 11 ||
				$result['material'][0]['materialTypeID'] == 13
			) && (
				isset( $permissions[1][1] ) ||
				isset( $permissions[3][38] )
			)
		) {
	?>
	
	<div class='qc_container'>
		<div class='qc_wrapper'>
			
			<div class='qc_title'>
				Standards
				<?php
		
					if ( 
						isset( $permissions[1][1] ) ||
						isset( $permissions[7][36] ) || 
						isset( $permissions[7][37] ) 
					)
					{
				?>
				<button id='qc_edit' style='z-index: 9998'>
					<span class='adjust_button_image'>&nbsp;</span>
				</button>
				<?php
					}
					
				?>
			</div>
			
			<table class='qc_table standards'>
				
				<tr>
					<th></th>
					<th class='std'>Std.</th>
					<th class='mfgLow'>Mfg. Low</th>
					<th class='mfgHigh'>Mfg. High</th>
				</tr>
				
				<tr>
					<th class='left'>Water</th>
					<td class='right std'><?php
						if ( isset( $result['material'][0]['stdWater'] ) )
						{
							echo $result['material'][0]['stdWater'] . "%"; 	
						} 
					?></td>
					<td class='right mfgLow'><?php
						if ( isset( $result['material'][0]['waterLow'] ) )
						{
							echo $result['material'][0]['waterLow'] . "%"; 	
						} 
					?></td>
					<td class='right mfgHigh'><?php
						if ( isset( $result['material'][0]['waterHigh'] ) )
						{
							echo $result['material'][0]['waterHigh'] . "%"; 	
						} 
					?></td>
				</tr>
				
				<tr>
					<th class='left'>Mix</th>
					<td class='right std'><?php
						if ( isset( $result['material'][0]['stdMix'] ) )
						{
							echo $result['material'][0]['stdMix'] . " min"; 	
						} 
					?></td>
					<td class='right mfgLow'><?php
						if ( isset( $result['material'][0]['mixLow'] ) )
						{
							echo $result['material'][0]['mixLow'] . " min"; 	
						} 
					?></td>
					<td class='right mfgHigh'><?php
						if ( isset( $result['material'][0]['mixHigh'] ) )
						{
							echo $result['material'][0]['mixHigh'] . " min"; 	
						} 
					?></td>
				</tr>
				
				<tr>
					<th class='left'>Taps</th>
					<td class='right std'><?php echo $result['material'][0]['taps']; ?></td>
					<td class='right mfgLow'></td>
					<td class='right mfgHigh'></td>
				</tr>
				
				<tr>
					<th class='left'>Lower Spec.</th>
					<td class='right std'><?php echo $result['material'][0]['lowerSpec']; ?></td>
					<td class='right mfgLow'></td>
					<td class='right mfgHigh'></td>
				</tr>
				
				<tr>
					<th class='left'>Upper Spec.</th>
					<td class='right std'><?php echo $result['material'][0]['upperSpec']; ?></td>
					<td class='right mfgLow'></td>
					<td class='right mfgHigh'></td>
				</tr>
				
			</table>
		</div>
	</div>
	
	
	
	<div class='qc_container'>
		<div class='qc_wrapper'>
			
			<div class='qc_title'>
				Exothermal Results
			</div>
			
			<table class='qc_table exotherm'>
				
				<tr>
					<th class='testID'>Test ID</th>
					<th class='testDate'>Date</th>
					<th class='testLot'>Lot Code</th>
				</tr>
				
				<?php
					
					if ( count( $result['exotherm'] ) != 0 )
					{
						
						foreach( $result['exotherm'] as $row )
						{
							
							if ( $row['stopTime'] != null )
							{
								echo "<tr class='hoverable stopped'>";
							}
							else
							{
								echo "<tr class='hoverable current'>";
							}
							
								echo "<td class='testID'>{$row['qcTestID']}</td>";
								echo "<td>" . mysql_to_date( $row['startTime'] ) . "</td>";
								echo "<td>{$row['lotcode']}</td>";
							echo "</tr>";
						}
						
					}
					else
					{
						echo "<tr><td colspan='3' style='text-align: center;'>No Results</td></tr>";
					}
					
				?>
				
				
			</table>
		</div>
	</div>
	
	<?php
		}
		
	?>
		
	<div class='inventory_container'>
		<div class='inventory_wrapper'>
			
			<div class='inventory_title'>Inventory</div>
			
			<div class='inventory_tabs'>
				
				<?php
				
					$order = array();
				
					foreach ( $result['location'] as $location )
					{
						$order[$location['locationID']] = $location['location'];
					}
		
					foreach ( $order as $i => $name )
					{
						
						if ( 
							( isset($_GET['location']) && $_GET['location'] == $i ) ||
							( ! isset($_GET['location']) && $_SESSION['default_location'] == $i)
						) {
							echo "<div class='inventory_tab selected' data-id='{$i}'>{$name}</div>";
						}
						else
						{
							echo "<div class='inventory_tab' data-id='{$i}'>{$name}</div>";
						}
			
					}
				
				?>
			
			</div>
			
			<div class='inventory_content'>
				
				<?php
					
					foreach ( $order as $i => $name )
					{

						$j = 0;
						$k = 0;
						$max = 1000;

						if ( 
							( isset($_GET['location']) && $_GET['location'] == $i ) ||
							( ! isset($_GET['location']) && $_SESSION['default_location'] == $i)
						) {
							echo "<table class='inventory_tab_content' data-id='{$i}'>";
						}
						else
						{
							echo "<table class='inventory_tab_content' data-id='{$i}' style='display: none;'>";
						}
						
						echo "<tr class='inventory_tab_title_row'><td colspan='2'><span>{$order[$i]}</span></td></tr>";
					
						echo "<tr>";
							echo "<th>Owner</th>";
							echo "<th>Inventory</th>";
							echo "<th></th>";
							echo "<th class='inventory_tab_row_data adjust'>Transaction</th>";
						echo "</tr>";


						while ( $k < count( $result['inventory'] ) && $k < $max )
						{

							if ( $result['inventory'][$k]['locationID'] == $i )
							{	

								echo "<tr class='inventory_tab_row'>";

								echo "<td class='inventory_tab_row_data owner'>" . $result['inventory'][$k]['owner'] . "</td>";
								echo "<td class='inventory_tab_row_data stock'>" . $result['inventory'][$k]['stock'] . "</td>";
								
								if ( 
									isset( $permissions[1][1] ) ||
									isset( $permissions[4][20][$i] ) || 
									isset( $permissions[2][18][$i] ) 
								) {
								
								echo "<td class='inventory_tab_row_data adjust'>" . 
									"<button class='adjust_button' data-location='{$result['inventory'][$k]['locationID']}' data-owner='{$result['inventory'][$k]['ownerID']}' data-material='{$_GET['id']}'  style='z-index: 9998'>" .
									"<span class='adjust_button_image'>&nbsp;</span></button></td>";
									
								}
								else
								{
									echo "<td></td>";
								}
								
								echo "<td class='inventory_tab_row_data transaction'>";
									echo "<button class='transaction_button' data-location='{$result['inventory'][$k]['locationID']}' data-owner='{$result['inventory'][$k]['ownerID']}' data-material='{$_GET['id']}' >Transaction</button>";
								echo "</td>";

								echo "</tr>";

								$j++;

							}

							$k++;

						}

						if ( $j == 0 )
						{
							echo "<div class='inventory_tab_row'>";
								echo "<div class='no_result'>No Results Found.</div>";
							echo "</div>";
						}

						echo "</table>";
					}

					echo "<div class='clearfix' style='margin-top: 20px'></div>";
				
				?>
				
			</div>
			
		</div>
	</div>
	
	<?php
	
	if ( ! empty( $result['transaction'] ) )
	{
	
	?>
	
	<div class='transaction_container'>
		<div class='transaction_wrapper'>
			
			<div class='transaction_title'>Transactions</div>
			
			<div class='transaction_tabs'>
				
				<?php
				
					$order = array(
						1 => 'Received',
						3 => 'Transfer',
						4 => 'Shipped',
						5 => 'Scrapped',
						6 => 'Used',
						7 => 'Adjustment'
					);
					
					$transactions = array(
						
						'Received' => array(
							'date' => 'Date',
							'owner' => 'Owner',
							'location' => 'Location',
							'value' => 'Amount',
							'cost' => 'Cost',
							'user' => 'User',
							'notes' => 'Notes'
						),
						
						'Produced' => array(
							'date' => 'Date',
							'owner' => 'Owner',
							'location' => 'Location',
							'value' => 'Amount',
							'cost' => 'Cost',
							'user' => 'User',
							'notes' => 'Notes'
						),
						
						'Transfer' => array(
							'date' => 'Date',
							'owner' => 'Owner',
							'location' => 'Location',
							'value' => 'Amount',
							'customer' => 'Owner',
							'shippingLocation' => 'Location',
							'user' => 'User',
							'notes' => 'Notes'
						),
						
						'Shipped' => array(
							'date' => 'Date',
							'owner' => 'Owner',
							'location' => 'Location',
							'value' => 'Amount',
							'customer' => 'Customer',
							'shippingLocation' => 'Location',
							'user' => 'User',
							'notes' => 'Notes'
						),
						
						'Scrapped' => array(
							'date' => 'Date',
							'owner' => 'Owner',
							'location' => 'Location',
							'value' => 'Amount',
							'user' => 'User',
							'notes' => 'Notes'
						),
						
						'Used' => array(
							'date' => 'Date',
							'owner' => 'Owner',
							'location' => 'Location',
							'value' => 'Amount',
							'user' => 'User',
							'notes' => 'Notes'
						),
						
						'Adjustment' => array(
							'date' => 'Date',
							'owner' => 'Owner',
							'location' => 'Location',
							'value' => 'Amount',
							'user' => 'User',
							'notes' => 'Notes'
						)
						
					);

					foreach ( $order as $i => $name )
					{

						reset( $order );
						if (
						 	( isset($_GET['transactionType']) && $_GET['transactionType'] == $i ) ||
							( ! isset($_GET['transactionType']) && key( $order ) == $i )
						) {
							echo "<div class='transaction_tab selected' data-id='{$i}'><span>&nbsp;</span>{$name}</div>";
						}
						else
						{
							echo "<div class='transaction_tab' data-id='{$i}'><span>&nbsp;</span>{$name}</div>";
						}

					}
					
				?>
				
			</div>
			
			<div class='transaction_tab_content_container'>
				
				
				
				<?php
				
					foreach ( $transactions as $transaction => $columns )
					{
					
						reset( $order );
						$i = array_search( $transaction, $order );
					
						if (
						 	( isset($_GET['transactionType']) && $_GET['transactionType'] == $i ) ||
							( ! isset($_GET['transactionType']) && key( $order ) == $i )
						) {
							echo "<table class='transaction_tab_content' data-id='{$i}'>";
						}
						else
						{
							echo "<table class='transaction_tab_content' data-id='{$i}' style='display: none;'>";
						}
					
						echo "<thead class='transaction_tab_row'><tr>";
					
					
						// Labels
						foreach ( $columns as $class => $label )
						{
							if ( 
								$class != 'cost' ||
								( // Finished Product
									(
										$result['material'][0]['materialTypeID'] == 16 ||
										$result['material'][0]['materialTypeID'] == 26
									) && (
										isset( $permissions[1][1] ) ||
										isset( $permissions[3][9] )
									)
								) ||
								( // Tools and Accessories
									$result['material'][0]['materialTypeID'] == 24 &&
									(
										isset( $permissions[1][1] ) ||
										isset( $permissions[3][6] )
									)
								) ||
								( // Material
									$result['material'][0]['materialTypeID'] != 16 &&
									$result['material'][0]['materialTypeID'] != 24 &&
									$result['material'][0]['materialTypeID'] != 26 &&
									(
										isset( $permissions[1][1] ) ||
										isset( $permissions[3][5] )
									)
								) || $row['userID'] == $_SESSION['user_id']
							) {
								echo "<th class='" . $class . "'>" . $label . "</th>";
							}
						}
						
						echo "</tr></thead>";
					
						// Data
						foreach ( $result['transaction'] as $row )
						{
						
							if ( $i == $row['transactionType'])
							{
							
								echo "<tr class='transaction_tab_row'>";

									foreach ( $columns as $class => $label )
									{
									
										if ( $class == 'date' )
										{
											$timestamp = strtotime( $row['timestamp'] );
											$date = date("m/d/Y", $timestamp);

											echo "<td class='transaction_tab_row_data date'>" . $date . "</td>";
										}
										else if ( $class == 'cost' )
										{
											if (
												( // Finished Product
													(
														$result['material'][0]['materialTypeID'] == 16 ||
														$result['material'][0]['materialTypeID'] == 26
													) && (
														isset( $permissions[1][1] ) ||
														isset( $permissions[3][9] )
													)
												) ||
												( // Tools and Accessories
													$result['material'][0]['materialTypeID'] == 24 &&
													(
														isset( $permissions[1][1] ) ||
														isset( $permissions[3][6] )
													)
												) ||
												( // Material
													$result['material'][0]['materialTypeID'] != 16 &&
													$result['material'][0]['materialTypeID'] != 24 &&
													$result['material'][0]['materialTypeID'] != 26 &&
													(
														isset( $permissions[1][1] ) ||
														isset( $permissions[3][5] )
													)
												) || $row['userID'] == $_SESSION['user_id']
											) {

											echo "<td class='transaction_tab_row_data " . $class . "'>$" . number_format( $row[$class], 2 ) . "</td>";
											
											}
											else
											{
												echo "<td class='transaction_tab_row_data " . $class . "'></td>";
											}
										}
										else if ( $class == 'notes' )
										{
										
											if ( ! empty( $row['notes'] ) )
											{
												echo "<td class='transaction_tab_row_data notes'><div data-title='" . htmlspecialchars( $row['notes'], ENT_QUOTES ) . "'>Note</div></td>";
											}
											else
											{
												echo "<td class='transaction_tab_row_data notes' style='visibility: hidden;'><div class='notes'><div data-title='" . htmlspecialchars( $row['notes'], ENT_QUOTES ) . "'>Note</div></div</td>";
											}
										
										}
										else
										{
											echo "<td class='transaction_tab_row_data " . $class . "'>" . $row[$class] . "</td>";
										}
									
									}
									
									if ( 
										isset( $permissions[1][1] ) || 
										$row['userID'] == $_SESSION['user_id'] 
									) {
										echo "<td  class='transaction_tab_row_data delete' data-transactionID='{$row['materialTransactionID']}' ><div class='delete'>Delete</div</td>";
									}

								echo "</tr>";
							
							}
						
						}
					
					
					
						echo "</tr></thead>";
					
						echo "</table>";
					
					}
				
				?>
				
			</div>
			
		</div>
	</div>
	
	<?php
	
		}	
	} 
	else 
	{ 
		echo "Material Not Found";
		echo "</div></div></div>";
	} 
	?>
	
</div>

<script>
	<!--
	( function ($) {
		
		$(document).on("click", ".qc_table.exotherm tr.hoverable.stopped", function() {
			var url_in_split = document.URL.split("?");
			window.location.href = url_in_split[0] + "?nav=" + "exothermData" + "&id=" + $(this).find('.testID').html();
		});
		
		$(document).on("click", ".qc_table.exotherm tr.hoverable.current", function() {
			var url_in_split = document.URL.split("?");
			window.location.href = url_in_split[0] + "?nav=" + "exotherm";
		});
		
		
		
		$(".inventory_tab").on("click", function () {
			
			$(".inventory_tab_content[data-id='" + $(".inventory_container .selected").attr("data-id") + "']").hide();
			$(".inventory_container .selected").removeClass("selected");
			
			$(this).addClass("selected");
			$(".inventory_tab_content[data-id='" + $(this).attr("data-id") + "']").show();
		});
		
		$(".inventory_tab_row").each( function () {

			var marginLeft = ( $(this).parent().width() - $(this).width() ) / 2;
			
			$(this)
				.css({
					"marginLeft": marginLeft
				});
		});
		
		$(".inventory_tab_content")
			.css({
				"visibility": "visible",
				"display": "none"
			});
		$(".inventory_tab_content[data-id='" + <?php 
		
			if ( isset( $_GET['location'] ) )
			{
				echo $_GET['location'];
			}
			else 
			{
				echo $_SESSION['default_location'];
			}
		
		?> + "']").show();
		
		
		
		$(".transaction_tab").on("click", function () {
			$(".transaction_tab_content[data-id='" + $(".transaction_container .selected").attr("data-id") + "']").hide();
			$(".transaction_container .selected").removeClass("selected");
			
			$(this).addClass("selected");
			$(".transaction_tab_content[data-id='" + $(this).attr("data-id") + "']").show();
		});
		
		$(".transaction_tab_row_data.delete").on("click", function () {
			
			var transactionID = $(this).attr("data-transactionid");
			
			$("body").append( '<div id="dialog-confirm" title="Delete"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>' + $(".transaction_tab.selected").text() + " " + $(this).parent().find(".transaction_tab_row_data.value").text() + " of " + <?php 
					if ( ! empty( $result['material'] ) ) 
					{ 
						echo "'" . addslashes( $result['material'][0]['material'] ) . "'";
					} 
				?> + '</p></div>' );
				
			$( "#dialog-confirm" ).dialog({
				autoResize: true,
		      	modal: true,
		      	buttons: {
		        	"Yes": function() {
						
						request = $.ajax({
							url: "ajax/material.php",
							type: "post",
							data: "transactionID=" + transactionID,
							global: false
						}).done( function ( response, textStatus, jqXHR) {
							location.reload();
						});
						
		        	},
		        	"No": function() {
		          	  	$( this ).dialog( "close" );
					 	$("#dialog-confirm").remove();
		        	}
		      }
		    });
			
			$('div#dialog-confirm').on( 'dialogclose', function(event) {
			     $("#dialog-confirm").remove();
			});
			
		});
		
	})(jQuery);
	-->
</script>