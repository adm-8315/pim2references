<?php
	
	/**
	 * Variables
	 */
	
	$result = array();
	
	
	/**
	 * MySQL
	 */
	
	$query = "
		SELECT
			i.itemID,
			i.item,
			i.lastPrice,
			i.lastSupplier,
			it.itemTypeID,
			it.itemType,
			l.locationID,
			l.location,
			IF (
				ii.stock is null,
				0,
				ii.stock
			) as 'stock'
		FROM
			item i
		JOIN
			location l
		LEFT JOIN
			itemType it
			ON it.itemTypeID = i.itemType
		LEFT JOIN
			itemInventory ii
			ON l.locationID = ii.location
			AND ii.item = ?
		WHERE
			i.itemID = ?
		AND
			l.stockable = 1
		ORDER BY
			l.location ASC
	";
	
	$values = array(
		$_GET['id'],
		$_GET['id']
	);
	
	$result['item'] = dbquery( $query, $values );
	
	
	/**
	 * Display
	 */ 
	
	//print_r($result['item']);
?>

<div class='content'>
		
	<div class='material_container'>
		<div class='material_wrapper'>
			
			<div class='material_title'>
				<?php echo $result['item'][0]['item']; ?>
				&nbsp;&nbsp;
				<button class='item_edit edit' style='z-index: 9998'><span class='adjust_button_image'>&nbsp;</span></button>
			</div>
			
			<table class='material_table'>
				
				<tr>
					<td class='left'>Item Type</td>
					<td class='right'><?php echo $result['item'][0]['itemType']; ?></td>
				</tr>
				
				<tr>
					<td class='left'>Last Supplier</td>
					<td class='right'><?php echo $result['item'][0]['lastSupplier']; ?></td>
				</tr>
				
				<tr>
					<td class='left'>Last Price</td>
					<td class='right'><?php echo $result['item'][0]['lastPrice']; ?></td>
				</tr>
				
			</table>
			
			<input type="hidden" id="overlay_item" value="<?php echo $_GET['id']; ?>">
			
		</div>
	</div>
		
	<div class='inventory_container'>
		<div class='inventory_wrapper'>
			
			<div class='inventory_title'>
				Inventory
				&nbsp;&nbsp;
				<button id='inventory_edit' class='edit' data-id='<?php echo $_GET['id']; ?>'  style='z-index: 9998'><span class='adjust_button_image'>&nbsp;</span></button>
			</div>
			
			<div class='inventory_content'>
				
				<table id='inventory_table' class='inventory_tab_content'>
					
					<tr>
						<th>Location</th>
						<th>Stock</th>
					</tr>
				
					<?php
				
					foreach ( $result['item'] as $row )
					{
						echo "<tr class='inventory_tab_row'>";
							echo "<td class='inventory_tab_row_data location' data-location='" . $row['locationID'] . "'>" . $row['location'] . "</td>";
							echo "<td class='inventory_tab_row_data stock'>" . $row['stock'] . "</td>";
						echo "<tr>";
					}
				
					?>
				
				</table>
				
			</div>
			
		</div>
	</div>
	
</div>