<?php
	
	$query = "
		SELECT
			po.*,
			f.furnace,
			fp.patternDescription,
			fp.patternTemperature,
			fp.patternTime,
			poml.quantity,
			me.measureSingular,
			me.measurePlural,
			poml.water,
			poml.mixTime,
			vt.vibrationType,
			poml.vibrationTime,
			m.materialID,
			m.material,
			m.stdWater,
			m.waterHigh,
			m.waterLow,
			m.stdMix,
			m.mixHigh,
			m.mixLow,
			GROUP_CONCAT( poo.productionOrderOption ) as productionOrderOption,
			GROUP_CONCAT( poo.productionOrderOptionType ) as productionOrderOptionType,
			CONCAT(MONTH(po.fillDate),' / ',DAY(po.fillDate),' / ',YEAR(po.fillDate)) as 'fillDate',
			IF(
				c.company is null,
				p.product,
				CONCAT( c.company, ' ', p.product)
			) as 'product'
		FROM
			productionOrder po
		LEFT JOIN
			furnacePattern fp
			ON fp.furnacePatternID = po.furnacePattern
		LEFT JOIN
			furnace f
			ON fp.furnace = f.furnaceID
		LEFT JOIN
			productionOrderMaterialLink poml
			ON po.productionOrderID = poml.productionOrder
		LEFT JOIN
			vibrationType vt
			ON poml.vibrationType = vt.vibrationTypeID
		LEFT JOIN
			material m
			ON poml.material = m.materialID
		LEFT JOIN
			measure me
			ON m.measure = me.measureID
		LEFT JOIN
			productionOrderProductionOrderOptionLink popool
			ON po.productionOrderID = popool.productionOrder
		LEFT JOIN
			productionOrderOption poo
			ON popool.productionOrderOption = poo.productionOrderOptionID
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
			productionOrderID = ?
		GROUP BY
			m.materialID
		ORDER BY
			poml.quantity DESC
	";
	
	$values = array(
		$_GET['id']
	);
	
	$result['productionOrder'] = dbquery( $query, $values );
	
?>

<div class='content'>
	<div class='product_wrapper'>
		<div class='product_content'>
			<div class='product_tab_content'>
				
				<table class='material_table center'>
	
					<tr>
						<td colspan='2' class='sectionTitle'>
							Production Order
						</td>
					</tr>

					<tr>
						<td class='left'>Product</td>
						<td class='right'><?php echo $result['productionOrder'][0]['product']; ?></td>
					</tr>

					<tr>
						<td class='left'>Open Orders</td>
						<td class='right'><?php echo $result['productionOrder'][0]['quantityOrdered']; ?> Pcs.</td>
					</tr>

					<tr>
						<td class='left'>Date Required</td>
						<td class='right'><?php echo $result['productionOrder'][0]['fillDate']; ?></td>
					</tr>

				</table>
				
				<table class='material_table materials'>
	
					<tr>
						<td colspan='6' class='sectionTitle'>
							Materials
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
		
					foreach ( $result['productionOrder'] as $row )
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


				<table class='material_table center'>
	
					<tr>
						<td colspan='2' class='sectionTitle'>
							Quality Control
						</td>
					</tr>

					<tr>
						<td class='left'>Taps</td>
						<td class='right'><?php echo $result['productionOrder'][0]['taps']; ?></td>
					</tr>

					<tr>
						<td class='left'>Lower Spec</td>
						<td class='right'><?php echo $result['productionOrder'][0]['lowerSpec']; ?></td>
					</tr>

					<tr>
						<td class='left'>Upper Spec</td>
						<td class='right'><?php echo $result['productionOrder'][0]['upperSpec']; ?></td>
					</tr>

				</table>


				<table class='material_table center'>
	
					<tr>
						<th class='sectionTitle'>
							Curing
						</th>
					</tr>
	
					<?php
	
					$options = explode(",", $result['productionOrder'][0]['productionOrderOption'] );
					
					$curingOptionsFound = false;
		
					foreach ( explode(",", $result['productionOrder'][0]['productionOrderOptionType'] ) as $key => $row )
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


				<table class='material_table center'>
	
					<tr>
						<td colspan='2' class='sectionTitle'>
							Drying
						</td>
					</tr>

					<tr>
						<td class='left'>Furnace</td>
						<td class='right'><?php echo $result['productionOrder'][0]['furnace']; ?></td>
					</tr>

					<tr>
						<td class='left'>Pattern</td>
						<td class='right'><?php echo $result['productionOrder'][0]['furnacePattern']; ?></td>
					</tr>
	
					<tr>
						<td class='left'>Pattern Desc.</td>
						<td class='right'><?php echo $result['productionOrder'][0]['patternDescription']; ?></td>
					</tr>

					<tr>
						<td class='left'>Temperature</td>
						<td class='right'><?php echo $result['productionOrder'][0]['patternTemperature']; ?></td>
					</tr>
	
					<tr>
						<td class='left'>Time</td>
						<td class='right'><?php echo $result['productionOrder'][0]['patternTime']; ?></td>
					</tr>

				</table>
	

				<table class='material_table center'>

					<tr>
						<th class='sectionTitle'>
							Packaging
						</th>
					</tr>
	
					<?php
	
					$options = explode(",", $result['productionOrder'][0]['productionOrderOption'] );
		
					foreach ( explode(",", $result['productionOrder'][0]['productionOrderOptionType'] ) as $key => $row )
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
				
				
				<?php
				if (
					isset( $permissions[1][1] ) ||
					isset( $permissions[6][45] )
				) {
				?>
				<table class='material_table center'>
					<tr class='deleteOrder no_select'><td>Delete Production Order</td></tr>
				</table>
				<?php
				}
				?>
					
					
			</div>
		</div>
	</div>
</div>

<style>

.deleteOrder {
	text-align: center;
	background: #fdc643;
}

.deleteOrder:hover {
	cursor: pointer;
}

.deleteOrder td {
	padding: 4px;
	border: 1px solid grey;
}

</style>

<script>

	$(".deleteOrder").on("click", function () {
	
		var transactionID = $(this).attr("data-transactionid");
	
		$("body").append( '<div id="dialog-confirm" title="Delete Production Order"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>Are you sure?</p></div>' );
		
		$( "#dialog-confirm" ).dialog({
			autoResize: true,
	      	modal: true,
	      	buttons: {
	        	"Yes": function() {
				
					request = $.ajax({
						url: "ajax/delete_productionOrder.php",
						type: "post",
						data: "productionOrder=<?php echo $_GET['id']; ?>",
						global: false
					}).done( function ( response, textStatus, jqXHR) {
						var url_in_split = document.URL.split("?");
						window.location.href = url_in_split[0] + "?nav=report&report=openOrder";
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

</script>