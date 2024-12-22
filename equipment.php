<?php
	
	/**
	 * Variables
	 */
	
	$result = array();
	
	
	/**
	 * MySQL
	 */
	
	// Equipment
	
	$query = "
		SELECT
			e.equipmentID,
			e.equipment,
			e.identifier,
			et.*,
			es.*,
			l.location
		FROM
			equipment e
		LEFT JOIN
			equipmentType et
			ON e.equipmentType = et.equipmentTypeID
		LEFT JOIN
			equipmentStatus es
			ON e.equipmentStatus = es.equipmentStatusID
		LEFT JOIN
			location l
			ON e.location = l.locationID
		WHERE
			e.equipmentID = ?
	";
	
	$values = array(
		$_GET['id']
	);
	
	$result['equipment'] = dbquery( $query, $values );
	
	
	// Preventative Maintenance
	
	$query = "
		SELECT
			pm.preventativeMaintenanceID as 'id',
			pm.valueInt as 'value',
			pmt.preventativeMaintenanceType,
			pm.preventativeMaintenance,
			pm.valueDate as 'date'
		FROM
			preventativeMaintenance pm
		LEFT JOIN
			preventativeMaintenanceType pmt
			ON pm.preventativeMaintenanceType = pmt.preventativeMaintenanceTypeID
		WHERE
			pm.equipment = ?
		AND
			pm.completeDate is null
	";
	
	$values = array(
		$_GET['id']
	);
	
	$result['preventativeMaintenance'] = dbquery( $query, $values );
	
	
	// Equipment Log
	
	$query = "
		SELECT
			pml.date,
			pml.value,
			pmt.preventativeMaintenanceType
		FROM
			preventativeMaintenanceLog pml
		LEFT JOIN
			preventativeMaintenanceType pmt
			ON pml.preventativeMaintenanceType = pmt.preventativeMaintenanceTypeID
		WHERE
			pml.equipment = ?
		ORDER BY
			pml.date DESC
	";
	
	$values = array(
		$_GET['id']
	);
	
	$result['equipmentLog'] = dbquery( $query, $values );
	
	
	/**
	 * Display
	 */ 
?>

<div class='content'>
		
	<div class='material_container'>
		<div class='material_wrapper'>
			
			<div class='material_title'>
				<?php 
					echo $result['equipment'][0]['equipment']; 
					
					if ( 
						isset( $permissions[1][1] ) ||
						isset( $permissions[6][26] )
					) {
						echo "&nbsp;&nbsp;<button class='equipment_edit edit' style='z-index: 9998'><span class='adjust_button_image'>&nbsp;</span></button>";
					}
				?>
			</div>
			
			<table class='material_table'>
				
				<tr>
					<td class='left'>Type</td>
					<td class='right'><?php echo $result['equipment'][0]['equipmentType']; ?></td>
				</tr>
				
				<tr>
					<td class='left'>Identifier</td>
					<td class='right'><?php echo $result['equipment'][0]['identifier']; ?></td>
				</tr>
				
				<tr>
					<td class='left'>Status</td>
					<td class='right'><?php echo $result['equipment'][0]['equipmentStatus']; ?></td>
				</tr>
				
				<tr>
					<td class='left'>Location</td>
					<td class='right'><?php echo $result['equipment'][0]['location']; ?></td>
				</tr>
				
			</table>
			
			<input type="hidden" id="overlay_equipment" value="<?php echo $_GET['id']; ?>">
			<input type="hidden" id="overlay_preventative_maintenance_id">
			
			
		</div>
	</div>
	
	<div class='material_container'>
		<div class='material_wrapper'>
			
			<div class='material_title'>
				Preventative Maintanence
				<?php 
					echo $result['equipment'][0]['equipment']; 
					
					if ( 
						isset( $permissions[1][1] ) ||
						isset( $permissions[6][26] )
					) {
						echo "&nbsp;&nbsp;<button class='preventative_maintenance_trigger edit' style='z-index: 9998'><span class='plus_button_image'>&nbsp;</span></button>";
					}
				?>
			</div>
			
			<table class='material_table'>
				
				<?php
					
				foreach( $result['preventativeMaintenance'] as $row )
				{
					
					echo "<tr class='preventativeMaintenanceRow' data-id='" . $row['id'] . "'>";
					
						echo "<td class='left no_select'>{$row['preventativeMaintenance']}</td>";
						if ( $row['preventativeMaintenanceType'] != null )
						{
							echo "<td class='right no_select'>";
								echo number_format( $row['value'] );
								echo "&nbsp;" . $row['preventativeMaintenanceType'];
							echo "</td>";
						}
						else
						{
							
							echo "<td class='right'>";
								
								$temp = explode( "-", $row['date'] );
								echo $temp[1] . " / " . $temp[2] . " / " . $temp[0];
							
							echo "</td>";
						}
						
					echo "</tr>";
					
				}
					
				?>
				
			</table>
			
		</div>
	</div>
	
	<div class='material_container'>
		<div class='material_wrapper'>
			
			<div class='material_title'>
				Equipment Log
				<?php 
					echo $result['equipment'][0]['equipment']; 
					
					if ( 
						isset( $permissions[1][1] ) ||
						isset( $permissions[6][26] )
					) {
						echo "&nbsp;&nbsp;<button class='equipment_log_trigger edit' style='z-index: 9998'><span class='plus_button_image'>&nbsp;</span></button>";
					}
				?>
			</div>
			
			<table class='material_table'>
				
				<tr>
					<th class='left'>Date</th>
					<th class='right'>Recording</th>
				</tr>
				
				<?php
					
				foreach( $result['equipmentLog'] as $row )
				{
					
					echo "<tr>";
					
						echo "<td class='left'>";
							$temp = explode( "-", $row['date'] );
							echo $temp[1] . " / " . $temp[2] . " / " . $temp[0];
						echo "</td>";
						
						echo "<td class='right'>";
							echo number_format($row['value']) . "&nbsp;" . $row['preventativeMaintenanceType'];
						echo "</td>";
					
					echo "</tr>";
					
				}
					
				?>
				
			</table>
			
		</div>
	</div>
	
</div>

<style>

	.preventativeMaintenanceRow:hover {
		background: #eee;
		cursor: pointer;
	}

</style>