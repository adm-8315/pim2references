<?php

	/**
	 * Variables
	 */
	
	$result = array();
	
	
	/**
	 * MySQL
	 */
	
	$query = "
		(
			SELECT
				g.*,
				1 as 'test',
				et.equipmentType as 'type',
				e.equipment as 'name',
				1 as 'value'
			FROM
				grouping g
			LEFT JOIN
				groupingEquipmentLink gel
				ON g.groupingID = gel.grouping
			LEFT JOIN
				equipment e
				ON gel.equipment = e.equipmentID
			LEFT JOIN
				equipmentType et
				ON e.equipmentType = et.equipmentTypeID
			WHERE
				g.groupingID = ?
		)
		UNION
		(
			SELECT
				g.*,
				2 as 'test',
				it.itemType as 'type',
				i.item as 'name',
				gil.value as 'value'
			FROM
				grouping g
			LEFT JOIN
				groupingItemLink gil
				ON g.groupingID = gil.grouping
			LEFT JOIN
				item i
				ON gil.item = i.itemID
			LEFT JOIN
				itemType it
				ON i.itemType = it.itemTypeID
			WHERE
				g.groupingID = ?
		)
	";
	
	$values = array(
		$_GET['id'],
		$_GET['id']
	);
	
	$result['grouping'] = dbquery( $query, $values );
	
	
	/**
	 * Display
	 */ 
?>

<div class='content'>
		
		
		
	<div class='material_container'>
		<div class='material_wrapper'>
			
			<div class='material_title'>
				<?php echo $result['grouping'][0]['grouping']; ?>
				&nbsp;&nbsp;
				<button class='grouping_edit edit' style='z-index: 9998'><span class='adjust_button_image'>&nbsp;</span></button>
			</div>
			
			<input type="hidden" id="overlay_grouping" value="<?php echo $_GET['id']; ?>">
			
		</div>
	</div>
	
	
	
	<div class='inventory_container'>
		<div class='inventory_wrapper'>

			<div class='inventory_title'>
				Equipment
				&nbsp;&nbsp;
				<button id='equipment_edit' class='edit' data-material='<?php echo $_GET['id']; ?>'  style='z-index: 9998'><span class='plus_button_image'>&nbsp;</span></button>
			</div>

			<div class='inventory_content'>

				<table class='inventory_tab_content'>

					<tr>
						<th>Equipment Type</th>
						<th>Equipment</th>
					</tr>

					<?php

					foreach ( $result['grouping'] as $row )
					{
						
						if ( $row['test'] == 1 )
						{
							echo "<tr class='inventory_tab_row'>";
								echo "<td class='inventory_tab_row_data type'>" . $row['type'] . "</td>";
								echo "<td class='inventory_tab_row_data name'>" . $row['name'] . "</td>";
							echo "<tr>";
						}
					}

					?>

				</table>

			</div>

		</div>
	</div>
	
	
	
	<div class='inventory_container'>
		<div class='inventory_wrapper'>

			<div class='inventory_title'>
				Item
				&nbsp;&nbsp;
				<button id='item_edit' class='edit' data-material='<?php echo $_GET['id']; ?>'  style='z-index: 9998'><span class='plus_button_image'>&nbsp;</span></button>
			</div>

			<div class='inventory_content'>

				<table class='inventory_tab_content'>

					<tr>
						<th>Item Type</th>
						<th>Item</th>
						<th>Amount</th>
					</tr>

					<?php

					foreach ( $result['grouping'] as $row )
					{
						
						if ( $row['test'] == 2 )
						{
							echo "<tr class='inventory_tab_row'>";
								echo "<td class='inventory_tab_row_data type'>" . $row['type'] . "</td>";
								echo "<td class='inventory_tab_row_data name'>" . $row['name'] . "</td>";
								echo "<td class='inventory_tab_row_data name'>" . $row['value'] . "</td>";
							echo "<tr>";
						}
					}

					?>

				</table>

			</div>

		</div>
	</div>
	
	
	
</div>
