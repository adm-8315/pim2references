<?php
	
	/**
	 * Variables
	 */
	
	$result = array();
	
	
	/**
	 * MySQL
	 */
	
	// Product
	
	$query = "
		SELECT DISTINCT
			p.productID,
			IF ( 
				c.company is not null,
				CONCAT( c.company, ' ', p.product ),
				p.product
			) as 'product',
			CONCAT( '$', convert( p.cost, DECIMAL(11,2) ) ) as 'cost',
			c.company as 'consumer',
			p.productType as 'productTypeID',
			pt.productType as 'productType',
			f.formTag
		FROM
			product p
		LEFT JOIN
			productConsumerLink pcl
			ON p.productID = pcl.product
		LEFT JOIN
			companyLocationLink cll
			ON pcl.companyLocationLink = cll.companyLocationLinkID
		LEFT JOIN
			company c
			ON cll.company = c.companyID
		LEFT JOIN
			productType pt
			ON pt.productTypeID = p.productType
		LEFT JOIN
			formProductLink fpl
			ON p.productID = fpl.product
		LEFT JOIN
			form f
			ON fpl.form = f.formID
		WHERE
			p.productID = ?
	";

	$values = array(
		$_GET['id']
	);

	$result['product'] = dbquery( $query, $values );
	
	
	// Location
	
	if ( isset( $permissions[3][3] ) > 0 )
	{
	
		$values = array();
	
		$query = "
			SELECT
				*
			FROM
				location l
			WHERE
		";
	
		foreach( $permissions[3][8] as $location => $enabled )
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
			i.productInventoryID as 'productInventoryID',
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
			productInventory i
			ON cll.companyLocationLinkID = i.companyLocationLink
			AND i.product = ?
		JOIN
			(
				SELECT
					me.*
				FROM
					product ma
				LEFT JOIN
					measure me
					ON ma.measure = me.measureID
				WHERE
					ma.productID =  ?
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
					mt.productTransactionID as 'productTransactionID',
					mt.productInventory as 'productInventoryID',
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
							productInventory i
						LEFT JOIN
							companyLocationLink cll
							ON i.companyLocationLink = cll.companyLocationLinkID
						WHERE
							i.product = ?
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
					productTransaction mt
					ON mt.productInventory = temp.productInventoryID
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
					product ma
					ON temp.product = ma.productID
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
					mt.productTransactionID is not null
				AND
					mt.transactionType != 1
				ORDER BY
					mt.transactionType ASC,
					mt.timestamp DESC,
					mt.productTransactionID DESC
			) temp2
		GROUP BY
			temp2.transactionType,
			temp2.timestamp,
			temp2.productTransactionID
		HAVING
			row_number <= 1000
		ORDER BY
			temp2.transactionType ASC,
			temp2.timestamp DESC,
			temp2.productTransactionID DESC
	";
	
	$values = array(
		$_GET['id'],
		$_SESSION['user_id']
	);
	
	$result['transaction'] = dbquery( $query, $values );
	
	// Production Order
	
	$query = "
		SELECT
			pot.productionOrderTemplateID as 'id',
			m.material,
			f.formTag as 'form'
		FROM
			productionOrderTemplate pot
		LEFT JOIN
			(
				SELECT
					potml.productionOrderTemplate, 
					potml.material,
					potml.quantity
				FROM 
					productionOrderTemplateMaterialLink potml
				LEFT JOIN
					(
						SELECT
							potml.*,
							max(potml.quantity) as max
						FROM
							productionOrderTemplateMaterialLink potml
						LEFT JOIN
							material m
							ON potml.material = m.materialID
						WHERE
							m.materialType = 11
						OR
							m.materialType = 12
						OR
							m.materialType = 13
						OR
							m.materialType = 34
						GROUP BY
							potml.productionOrderTemplate
					) max
					ON max.productionOrderTemplate = potml.productionOrderTemplate
					AND max.max = potml.quantity
				WHERE
					max.max is not null
			) potml
			ON potml.productionOrderTemplate = pot.productionOrderTemplateID
		LEFT JOIN
			material m
			ON potml.material = m.materialID
		LEFT JOIN
			formProductLink fpl
			ON pot.product = fpl.product
		LEFT JOIN
			form f
			ON fpl.form = f.formID
		WHERE
			pot.product = ?
		AND
			pot.active = 1
		ORDER BY
			potml.quantity ASC
	";
	
	$values = array(
		$_GET['id']
	);
	
	$result['productionOrder'] = dbquery( $query, $values );
	
	// Production Order Template
	
	foreach ( $result['productionOrder'] as $productionOrder )
	{
		
		$query = "
			SELECT
				pot.*,
				f.furnace,
				fp.patternDescription,
				fp.patternTemperature,
				fp.patternTime,
				potml.quantity,
				me.measureSingular,
				me.measurePlural,
				potml.water,
				potml.mixTime,
				vt.vibrationType,
				potml.vibrationTime,
				m.materialID,
				m.material,
				m.stdWater,
				m.waterHigh,
				m.waterLow,
				m.stdMix,
				m.mixHigh,
				m.mixLow,
				GROUP_CONCAT( poo.productionOrderOption ) as productionOrderOption,
				GROUP_CONCAT( poo.productionOrderOptionType ) as productionOrderOptionType
			FROM
				productionOrderTemplate pot
			LEFT JOIN
				furnacePattern fp
				ON fp.furnacePatternID = pot.furnacePattern
			LEFT JOIN
				furnace f
				ON fp.furnace = f.furnaceID
			LEFT JOIN
				productionOrderTemplateMaterialLink potml
				ON pot.productionOrderTemplateID = potml.productionOrderTemplate
			LEFT JOIN
				vibrationType vt
				ON potml.vibrationType = vt.vibrationTypeID
			LEFT JOIN
				material m
				ON potml.material = m.materialID
			LEFT JOIN
				measure me
				ON m.measure = me.measureID
			LEFT JOIN
				productionOrderTemplateProductionOrderOptionLink potpool
				ON pot.productionOrderTemplateID = potpool.productionOrderTemplate
			LEFT JOIN
				productionOrderOption poo
				ON potpool.productionOrderOption = poo.productionOrderOptionID
			WHERE
				productionOrderTemplateID = ?
			GROUP BY
				m.materialID
			ORDER BY
				potml.quantity DESC
		";
		
		$values = array(
			$productionOrder['id']
		);
		
		$result['productionOrderTemplate'][$productionOrder['id']] = dbquery( $query, $values );
		
	}
	
	
	/**
	 * Display
	 */
?>

<div class='content'>
		
	<div class='material_container'>
		<div class='material_wrapper'>
			
			<div class='material_title'>
				<?php 
					if ( ! empty( $result['product'] ) ) 
					{ 
						echo $result['product'][0]['product']; 
				?>&nbsp;&nbsp;
				<button id='material_edit' data-product='<?php echo $_GET['id']; ?>'  style='z-index: 9998'>
					<span class='adjust_button_image'>&nbsp;</span>
				</button>
			</div>
			
			<table class='material_table'>
				
				<tr>
					<td class='left'>Consumer</td>
					<td class='right'><?php echo $result['product'][0]['consumer']; ?></td>
				</tr>
				
				<tr>
					<td class='left'>Product Type</td>
					<td class='right'><?php echo $result['product'][0]['productType']; ?></td>
				</tr>
				
				<tr>
					<td class='left'>Form ID</td>
					<td class='right'><?php echo $result['product'][0]['formTag']; ?></td>
				</tr>
				
				<?php
				
				if (
					( // Finished Product
						(
							$result['product'][0]['productTypeID'] == 16 ||
							$result['product'][0]['productTypeID'] == 26
						) && (
							isset( $permissions[1][1] ) ||
							isset( $permissions[3][9] )
						)
					) ||
					( // Tools and Accessories
						$result['product'][0]['productTypeID'] == 24 &&
						(
							isset( $permissions[1][1] ) ||
							isset( $permissions[3][6] )
						)
					) ||
					( // Material
						$result['product'][0]['productTypeID'] != 16 &&
						$result['product'][0]['productTypeID'] != 24 &&
						$result['product'][0]['productTypeID'] != 26 &&
						(
							isset( $permissions[1][1] ) ||
							isset( $permissions[3][5] )
						)
					)
				) {
					
				echo "
				<tr>
					<td class='left'>Cost</td>
					<td class='right'>{$result['product'][0]['cost']}</td>
				</tr>
				";
				
				}
				
				?>
				
			</table>
			
			<input type="hidden" id="overlay_pageCount" value="2" />
			<input type="hidden" id="overlay_material" value="<?php echo $_GET['id']; ?>" />
			<input type="hidden" id="overlay_material_category" value="2" />
			<input type="hidden" id="overlay_owner" />
			<input type="hidden" id="overlay_location" />
			<input type="hidden" id="overlay_order" value="<?php if ( ! empty( $result['productionOrder'][0]['id'] ) ) { echo $result['productionOrder'][0]['id']; } ?>" />
			
		</div>
	</div>
	
	
	<?php
		
		if ( 
			(
				$result['product'][0]['productTypeID'] == 11 ||
				$result['product'][0]['productTypeID'] == 13
			) && (
				isset( $permissions[1][1] ) ||
				isset($permissions[3][38]) 
			)
		) {
	?>
	
	<div class='qc_container'>
		<div class='qc_wrapper'>
			
			<div class='qc_title'>
				Quality Control
				<?php
		
					if (
						isset($permissions[1][1]) || 
						isset($permissions[7][36]) || 
						isset($permissions[7][37]) 
					) {
				?>
				<button id='qc_edit' style='z-index: 9998'>
					<span class='adjust_button_image'>&nbsp;</span>
				</button>
				<?php
					}
					
				?>
			</div>
			
			<table class='qc_table'>
				
				<tr>
					<th></th>
					<th class='std'>Std.</th>
					<th class='mfgLow'>Mfg. Low</th>
					<th class='mfgHigh'>Mfg. High</th>
				</tr>
				
				<tr>
					<th class='left'>Water</th>
					<td class='right std'><?php
						if ( isset( $result['product'][0]['stdWater'] ) )
						{
							echo $result['product'][0]['stdWater'] . "%"; 	
						} 
					?></td>
					<td class='right mfgLow'><?php
						if ( isset( $result['product'][0]['waterLow'] ) )
						{
							echo $result['product'][0]['waterLow'] . "%"; 	
						} 
					?></td>
					<td class='right mfgHigh'><?php
						if ( isset( $result['product'][0]['waterHigh'] ) )
						{
							echo $result['product'][0]['waterHigh'] . "%"; 	
						} 
					?></td>
				</tr>
				
				<tr>
					<th class='left'>Mix</th>
					<td class='right std'><?php
						if ( isset( $result['product'][0]['stdMix'] ) )
						{
							echo $result['product'][0]['stdMix'] . " min"; 	
						} 
					?></td>
					<td class='right mfgLow'><?php
						if ( isset( $result['product'][0]['mixLow'] ) )
						{
							echo $result['product'][0]['mixLow'] . " min"; 	
						} 
					?></td>
					<td class='right mfgHigh'><?php
						if ( isset( $result['product'][0]['mixHigh'] ) )
						{
							echo $result['product'][0]['mixHigh'] . " min"; 	
						} 
					?></td>
				</tr>
				
				<tr>
					<th class='left'>Taps</th>
					<td class='right std'><?php echo $result['product'][0]['taps']; ?></td>
					<td class='right mfgLow'></td>
					<td class='right mfgHigh'></td>
				</tr>
				
				<tr>
					<th class='left'>Lower Spec.</th>
					<td class='right std'><?php echo $result['product'][0]['lowerSpec']; ?></td>
					<td class='right mfgLow'></td>
					<td class='right mfgHigh'></td>
				</tr>
				
				<tr>
					<th class='left'>Upper Spec.</th>
					<td class='right std'><?php echo $result['product'][0]['upperSpec']; ?></td>
					<td class='right mfgLow'></td>
					<td class='right mfgHigh'></td>
				</tr>
				
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
							
							if (
								isset( $permissions[1][1] ) || 
								isset( $permissions[4][20][$i] ) || 
								isset( $permissions[2][18][$i] ) 
							) {
								echo "<th></th>";
							}
							
							if (
								isset( $permissions[1][1] ) ||
								isset( $permissions[2] )
							) {
								echo "<th class='inventory_tab_row_data adjust'>Transaction</th>";
							}
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
								
								if (
									isset( $permissions[1][1] ) ||
									isset( $permissions[2] )
								) {
									echo "<td class='inventory_tab_row_data transaction'>";
										echo "<button class='transaction_button' data-location='{$result['inventory'][$k]['locationID']}' data-owner='{$result['inventory'][$k]['ownerID']}' data-material='{$_GET['id']}' >Transaction</button>";
									echo "</td>";
								}

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
	
	<div class='product_container'>
		<div class='product_wrapper'>
			
			<div class='product_title'>Production Orders</div>
			
			<div class='sub_menu'>
				<?php

				if (
					(
						isset( $permissions[1][1] ) ||
						isset( $permissions[5][44] )
					) &&
					! empty( $result['productionOrder'] ) 
				) {
					echo "<button id='queue' data-nav='report' data-report='product'>Queue</button>";
				}
				
				if (
					isset( $permissions[1][1] ) ||
					isset( $permissions[5][43] ) ||
					isset( $permissions[6][42] )
				) {	
					
				?>
				<div id='potMenu_container'>
					<div id='potMenu' data-nav='report' data-report='product'>Menu</div>
					<div id='potMenu_options' style='display: none;'>
						<?php
							
							if (
								isset( $permissions[1][1] ) ||
								isset( $permissions[5][43] )
							) { 
								echo "<div class='option' id='potMenu_new'>New Template</div>";
							}
					
							if ( 
								(
									isset( $permissions[1][1] ) ||
									isset( $permissions[6][42] )
								) &&
								! empty( $result['productionOrder'] ) )
							{
								echo "<div class='option' id='potMenu_delete'>Delete Template</div>";
							}
							
						?>
					</div>
				</div>
				<?php
					
				}
					
				?>
				<div class='clearfix'></div>
			</div>
			
			<div class='product_tabs'>
				
				<?php
				
				$first = true;
					
				foreach ( $result['productionOrder'] as $productionOrder )
				{
					
					if ( $first )
					{
						echo "<div class='product_tab selected' data-id='{$productionOrder['id']}'>{$productionOrder['material']}</div>";
						$first = false;
					}
					else
					{
						echo "<div class='product_tab' data-id='{$productionOrder['id']}'>{$productionOrder['material']}</div>";
					}
					
				}
					
				?>
			
			</div>
			
			<div class='product_content'>
				
				<?php
				
					$first = true;
					
					foreach ( $result['productionOrder'] as $i => $productionOrder )
					{

						$j = 0;
						$k = 0;
						$max = 1000;
						
						if ( $first )
						{
							echo "<div class='product_tab_content' data-id='{$productionOrder['id']}'>";
						}
						else
						{
							echo "<div class='product_tab_content' data-id='{$productionOrder['id']}' style='display: none;'>";
						}
							
				?>
						
						
						<table class='material_table materials'>
							
							<tr>
								<td colspan='6' class='sectionTitle'>
									Materials
									<?
										if (
											isset( $permissions[1][1] ) ||
											isset( $permissions[6][42] )
										)
										{
									?>
									<button id='po_material_edit' class='edit_button' style='z-index: 9998'>
										<span class='adjust_button_image'>&nbsp;</span>
									</button>
									<?php
										}
									?>
								</td>
							</tr>
				
							<tr>
								<th class='center centerLabel' style='width: 75px'>Qty.</th>
								<th class='center centerLabel' style='width: 175px'>Material</th>
								<th class='center centerLabel' style='width: 75px'>Water</th>
								<th class='center centerLabel' style='width: 75px'>Mix Time</th>
								<th class='center centerLabel' style='width: 75px'>Vib. Type</th>
								<th class='center centerLabel' style='width: 75px'>Vib. Time</th>
							</tr>
							
							<?php
								
							foreach ( $result['productionOrderTemplate'][$productionOrder['id']] as $row )
							{
								
								echo "<tr class='materialRow' data-nav='material' data-id='{$row['materialID']}'>";	
															
								if ( $row['quantity'] > 1 )
								{
									echo "<td class='quantity'>{$row['quantity']} {$row['measurePlural']}</td>";
								}
								else
								{
									echo "<td class='quantity'>{$row['quantity']} {$row['measureSingular']}</td>";
								}
								
								echo "<td>{$row['material']}</td>";
								
								if ( ! empty( $row['water'] ) || $row['water'] === 0 )
								{
									
									$waterString = "";
							
									if ( $row['stdWater'] != null )
									{
										$waterString .= "Std. " . $row['stdWater'] . "% | ";
									}
									else
									{
										$waterString .= "Std. Not Set | ";
									}
							
									if ( $row['waterLow'] != null || $row['waterHigh'] != null )
									{
										$waterString .= "Mfg. Rec. " . $row['waterLow'] . "% - " . $row['waterHigh'] . "%";
									}
									else
									{
										$waterString .= "Mfg. Rec. Not Recorded";
									}
							
									echo "<td class='center'><div class='material_water_hook' data-title='" . $waterString . "'>" . $row['water'] . " %</div></td>";
									
								}
								else
								{
									echo "<td class='center'>{$row['water']}</td>";
								}
								
								if ( ! empty( $row['mixTime'] ) || $row['mixTime'] === 0 )
								{
							
									$mixString = "";
						
									if ( $row['stdMix'] != null )
									{
										$mixString .= "Std. " . $row['stdMix'] . "min | ";
									}
									else
									{
										$mixString .= "Std. Not Set | ";
									}
						
									if ( $row['mixLow'] != null || $row['mixHigh'] != null )
									{
										$mixString .= "Mfg. Rec. " . $row['mixLow'] . "min - " . $row['mixHigh'] . "min";
									}
									else
									{
										$mixString .= "Mfg. Rec. Not Recorded";
									}
						
									echo "<td class='center'><div class='material_mix_hook' data-title='" . $mixString . "'>" . $row['mixTime'] . " min</div></td>";
									
								}
								else
								{
									echo "<td class='center'>{$row['mixTime']}</td>";
								}
								
								echo "<td class='center'>{$row['vibrationType']}</td>";
								
								if ( ! empty( $row['vibrationTime'] ) || $row['vibrationTime'] === 0 )
								{
									echo "<td class='center'>{$row['vibrationTime']} min</td>";
								}
								else
								{
									echo "<td class='center'>{$row['vibrationTime']}</td>";
								}
								
								echo "</tr>";
								
							}
								
							?>
				
						</table>
						
						<div class='clearfix' style='margin-top: 20px'></div>
						
						<table class='material_table center'>
							
							<tr>
								<td colspan='2' class='sectionTitle'>
									Quality Control
									<?
										if (
											isset( $permissions[1][1] ) ||
											isset( $permissions[6][42] )
										)
										{
									?>
									<button id='po_qc_edit' class='edit_button' style='z-index: 9998'>
										<span class='adjust_button_image'>&nbsp;</span>
									</button>
									<?php
										}
									?>
								</td>
							</tr>
				
							<tr>
								<td class='left'>Taps</td>
								<td class='right'><?php echo $result['productionOrderTemplate'][$productionOrder['id']][0]['taps']; ?></td>
							</tr>
				
							<tr>
								<td class='left'>Lower Spec</td>
								<td class='right'><?php echo $result['productionOrderTemplate'][$productionOrder['id']][0]['lowerSpec']; ?></td>
							</tr>
				
							<tr>
								<td class='left'>Upper Spec</td>
								<td class='right'><?php echo $result['productionOrderTemplate'][$productionOrder['id']][0]['upperSpec']; ?></td>
							</tr>
				
						</table>
						
						<div class='clearfix' style='margin-top: 20px'></div>
						
						<table class='material_table center'>
							
							<tr>
								<th class='sectionTitle'>
									Curing
									<?
										if (
											isset( $permissions[1][1] ) ||
											isset( $permissions[6][42] )
										)
										{
									?>
									<button id='po_curing_edit' class='edit_button' style='z-index: 9998'>
										<span class='adjust_button_image'>&nbsp;</span>
									</button>
									<?php
										}
									?>
								</th>
							</tr>
							
							<?php
							
							$options = explode(",", $result['productionOrderTemplate'][$productionOrder['id']][0]['productionOrderOption'] );
							
							$curingOptionsFound = false;
								
							foreach ( explode(",", $result['productionOrderTemplate'][$productionOrder['id']][0]['productionOrderOptionType'] ) as $key => $row )
							{
								
								if ( $row == 1 )
								{
									$curingOptionsFound = true;
									echo "<tr>";
									echo "<td class='center'>". $options[$key] . "</td>";
									echo "</tr>";
								}
								
							}
							
							if ( ! $curingOptionsFound )
							{
								echo "<tr>";
								echo "<td class='center'>None</td>";
								echo "</tr>";
							}
								
							?>
				
						</table>
						
						<div class='clearfix' style='margin-top: 20px'></div>
						
						<table class='material_table center'>
							
							<tr>
								<td colspan='2' class='sectionTitle'>
									Drying
									<?
										if (
											isset( $permissions[1][1] ) ||
											isset( $permissions[6][42] )
										)
										{
									?>
									<button id='po_drying_edit' class='edit_button' style='z-index: 9998'>
										<span class='adjust_button_image'>&nbsp;</span>
									</button>
									<?php
										}
									?>
								</td>
							</tr>
				
							<tr>
								<td class='left'>Furnace</td>
								<td class='right'><?php echo $result['productionOrderTemplate'][$productionOrder['id']][0]['furnace']; ?></td>
							</tr>
				
							<tr>
								<td class='left'>Pattern</td>
								<td class='right'><?php echo $result['productionOrderTemplate'][$productionOrder['id']][0]['furnacePattern']; ?></td>
							</tr>
							
							<tr>
								<td class='left'>Pattern Desc.</td>
								<td class='right'><?php echo $result['productionOrderTemplate'][$productionOrder['id']][0]['patternDescription']; ?></td>
							</tr>
				
							<tr>
								<td class='left'>Temperature</td>
								<td class='right'><?php echo $result['productionOrderTemplate'][$productionOrder['id']][0]['patternTemperature']; ?></td>
							</tr>
							
							<tr>
								<td class='left'>Time</td>
								<td class='right'><?php echo $result['productionOrderTemplate'][$productionOrder['id']][0]['patternTime']; ?></td>
							</tr>
				
						</table>
						
						<div class='clearfix' style='margin-top: 20px'></div>
						
						<table class='material_table center'>
				
							<tr>
								<th class='sectionTitle'>
									Packaging
									<?
										if (
											isset( $permissions[1][1] ) ||
											isset( $permissions[6][42] )
										)
										{
									?>
									<button id='po_packaging_edit' class='edit_button' style='z-index: 9998'>
										<span class='adjust_button_image'>&nbsp;</span>
									</button>
									<?php
										}
									?>
								</th>
							</tr>
							
							<?php
							
							$options = explode(",", $result['productionOrderTemplate'][$productionOrder['id']][0]['productionOrderOption'] );
								
							foreach ( explode(",", $result['productionOrderTemplate'][$productionOrder['id']][0]['productionOrderOptionType'] ) as $key => $row )
							{
								
								if ( $row == 2 )
								{
									echo "<tr>";
									echo "<td class='center'>". $options[$key] . "</td>";
									echo "</tr>";
								}
								
							}
								
							?>
				
						</table>
						
						<div class='clearfix'></div>
						
				<?php
						echo "</div>";
						
						$first = false;
						
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
						2 => 'Produced',
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
										$result['product'][0]['productTypeID'] == 16 ||
										$result['product'][0]['productTypeID'] == 26
									) && (
										isset( $permissions[1][1] ) ||
										isset( $permissions[3][9] )
									)
								) ||
								( // Tools and Accessories
									$result['product'][0]['productTypeID'] == 24 &&
									(
										isset( $permissions[1][1] ) ||
										isset( $permissions[3][6] )
									)
								) ||
								( // Material
									$result['product'][0]['productTypeID'] != 16 &&
									$result['product'][0]['productTypeID'] != 24 &&
									$result['product'][0]['productTypeID'] != 26 &&
									(
										isset( $permissions[1][1] ) ||
										isset( $permissions[3][5] )
									)
								)
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
														$result['product'][0]['productTypeID'] == 16 ||
														$result['product'][0]['productTypeID'] == 26
													) && (
														isset( $permissions[1][1] ) ||
														isset( $permissions[3][9] )
													)
												) ||
												( // Tools and Accessories
													$result['product'][0]['productTypeID'] == 24 &&
													(
														isset( $permissions[1][1] ) ||
														isset( $permissions[3][6] )
													)
												) ||
												( // Material
													$result['product'][0]['productTypeID'] != 16 &&
													$result['product'][0]['productTypeID'] != 24 &&
													$result['product'][0]['productTypeID'] != 26 &&
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
										echo "<td  class='transaction_tab_row_data delete' data-transactionID='{$row['productTransactionID']}' ><div class='delete'>Delete</div</td>";
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
		echo "Product Not Found";
		echo "</div></div></div>";
	} 
	
	?>
	
</div>

<script>
	<!--
	( function ($) {
		
		
		
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
		
		
		
		
		$("#potMenu").on( "click", function () {
			$("#potMenu_options").toggle();
		});
		
		$(document).on("mouseleave", "#potMenu_container", function() {
			$("#potMenu_options").hide();
		});
		
		$("#potMenu_delete").on( "click", function() {
			
			var potID = $(".product_tab.selected").attr("data-id");
			
			$("body").append( '<div id="dialog-confirm" title="Delete"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>Delete template for ' + <?php 
					if ( ! empty( $result['product'] ) ) 
					{ 
						echo "'" . addslashes( $result['product'][0]['product'] ) . "'";
					} 
				?> + ' - ' + $(".product_tab.selected").text() + '</p></div>' );
				
			$( "#dialog-confirm" ).dialog({
				autoResize: true,
		      	modal: true,
		      	buttons: {
		        	"Yes": function() {
						
						request = $.ajax({
							url: "ajax/delete_productionOrderTemplate.php",
							type: "post",
							data: "productionOrderTemplateID=" + potID,
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
		
		
		
		
		
		$(".product_tab").on("click", function () {
			
			$("#overlay_order").val( $(".product_tab.selected").attr("data-id") );
			
			$(".product_tab_content[data-id='" + $(".product_container .selected").attr("data-id") + "']").hide();
			$(".product_container .selected").removeClass("selected");
			
			$(this).addClass("selected");
			$(".product_tab_content[data-id='" + $(this).attr("data-id") + "']").show();
			$("#overlay_order").val( $(this).attr("data-id") );
		});
		
		$(".product_tab_row").each( function () {

			var marginLeft = ( $(this).parent().width() - $(this).width() ) / 2;
			
			$(this)
				.css({
					"marginLeft": marginLeft
				});
		});
		
		$(".material_table.materials tr").on("click", function() {
				
			if ( $(this).attr("data-nav") )
			{
				generateURL( $(this) );
			}	
			
		});
		
		
		
		
		
		$(".transaction_tab").on("click", function () {
			$(".transaction_tab_content[data-id='" + $(".transaction_container .selected").attr("data-id") + "']").hide();
			$(".transaction_container .selected").removeClass("selected");
			
			$(this).addClass("selected");
			$(".transaction_tab_content[data-id='" + $(this).attr("data-id") + "']").show();
		});
		
		$(".transaction_tab_row_data.delete").on("click", function () {
			
			var transactionID = $(this).attr("data-transactionid");
			
			$("body").append( '<div id="dialog-confirm" title="Delete"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>' + $(".transaction_tab.selected").text() + " " + $(this).parent().find(".transaction_tab_row_data.value").text() + " of " + <?php 
					if ( ! empty( $result['product'] ) ) 
					{ 
						echo "'" . addslashes( $result['product'][0]['product'] ) . "'";
					} 
				?> + '</p></div>' );
				
			$( "#dialog-confirm" ).dialog({
				autoResize: true,
		      	modal: true,
		      	buttons: {
		        	"Yes": function() {
						
						request = $.ajax({
							url: "ajax/product.php",
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