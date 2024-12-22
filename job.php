<?php

	/**
	 * Includes
	 */
	
	require_once("inc/functions/date_conversion.php");
	
	
	/**
	 * Variables
	 */
	
	$result = array();
	
	
	/**
	 * MySQL
	 */
	
	// Job
	
	$query = "
		SELECT
			j.*,
			c.company,
			l.location
		FROM
			job j
		LEFT JOIN
			companyLocationLink cll
			ON j.companyLocationLink = cll.companyLocationLinkID
		LEFT JOIN	
			company c
			ON cll.company = c.companyID
		LEFT JOIN	
			location l
			ON cll.location = l.locationID
		WHERE
			jobID = ?
	";
	
	$values = array(
		$_GET['id']
	);
	
	$result['job'] = dbquery( $query, $values );
	
	
	// Equipment List
	
	$query = "
		SELECT
			e.equipmentID,
			et.equipmentType,
			e.equipment,
			e.identifier,
			el.job
		FROM
			equipment e
		LEFT JOIN
			equipmentType et
			ON e.equipmentType = et.equipmentTypeID
		LEFT JOIN
			equipmentList el
			ON e.equipmentID = el.equipment
		WHERE
			e.equipmentStatus = 1
		AND
			e.location = 1
		ORDER BY
			et.equipmentType ASC,
			e.equipment ASC
	";
	
	$values = array();
	
	$result['equipment'] = dbquery( $query, $values );
	
	
	// Grouping List
	
	$query = "
		SELECT
			g.*,
			gl.job
		FROM
			grouping g
		LEFT JOIN
			groupingList gl
			ON g.groupingID = gl.grouping
		WHERE
			g.location = 1
		ORDER BY
			g.grouping ASC
	";
	
	$values = array();
	
	$result['grouping'] = dbquery( $query, $values );
	
	
	// Item List
	
	$query = "
		SELECT
			i.*,
			it.itemType,
			temp.itemInventoryID,
			temp.stock,
			temp.locationID,
			temp.location,
			il.*
		FROM
			item i
		LEFT JOIN
			itemType it
			ON i.itemType = it.itemTypeID
		LEFT JOIN
			(
				SELECT
					ii.itemInventoryID,
					ii.item,
					ii.stock,
					l.*
				FROM
					itemInventory ii
				LEFT JOIN
					location l
					ON ii.location = l.locationID
				WHERE
					l.stockable = 1
				AND
					l.active = 1
			) temp
			ON i.itemID = temp.item
			AND temp.locationID = 1
		LEFT JOIN
			itemList il
			ON temp.itemInventoryID = il.itemInventory
			AND il.job = ?
		ORDER BY
			it.itemType ASC,
			i.item ASC
		
	";
	
	$values = array(
		$_GET['id']
	);
	
	$result['item'] = dbquery( $query, $values );
	
	
	/**
	 * Display
	 */
	
?>

<div class='content'>
		
	<div class='job_container'>
		<div class='job_wrapper'>
			
			<table class='job_table'>
				
				<tr>
					<td class='left'>Job #</td>
					<td class='right'><?php echo $result['job'][0]['jobNumber']; ?></td>
				</tr>
				
				<tr>
					<td class='left'>Customer</td>
					<td class='right'><?php echo $result['job'][0]['company']; ?></td>
				</tr>
				
				<tr>
					<td class='left'>Location</td>
					<td class='right'><?php echo $result['job'][0]['location']; ?></td>
				</tr>
				
				<tr>
					<td class='left'>Start Date</td>
					<td class='right'><?php echo mysql_to_date( $result['job'][0]['startDate'] ); ?></td>
				</tr>
				
				<tr>
					<td class='left'>Deliver Date</td>
					<td class='right'><?php echo mysql_to_date( $result['job'][0]['deliverDate'] ); ?></td>
				</tr>
				
				<tr>
					<td class='left'>Deliver Via</td>
					<td class='right'><?php echo $result['job'][0]['deliverVIA']; ?></td>
				</tr>
				
				<tr>
					<td class='left'>Description</td>
					<td class='right'><?php echo $result['job'][0]['description']; ?></td>
				</tr>
				
			</table>
			
			<input type="hidden" id="overlay_job" value="<?php echo $_GET['id']; ?>">
			<div class='job_edit'><span>&nbsp;</span></div>
			
			<div class='print'>Print</div>
			
		</div>
	</div>
	
	<div class='job_container'>
		<div class='job_container_headerRow'>
			<div class='job_container_header itemType'>Type</div
			><div class='job_container_header name'>Equipment</div
			><div class='job_container_header identifier'>Identifier</div
			><div class='job_container_header stock'>Required</div>
		</div>
		<div class='job_container_content'>
			<?php
				
				$noEquipment = true;
				
				if ( ! empty( $result['equipment'] ) )
				{
					
					foreach ( $result['equipment'] as $row )
					{
						echo "<div class='job_container_content_row equipment";
					
						if ( ! empty( $row['job'] ) && $row['job'] == $_GET['id'] )
						{
							echo " pack'>";
							$noEquipment = false;
						}
						else
						{
							echo " hide'>";
						}
						
						echo "<div class='id' style='display: none;' data-id='" . $row['equipmentID'] . "'></div>";
						
						echo "<div class='itemType'>" . $row['equipmentType'] . "</div>";
					
						echo "<div class='name'>" . $row['equipment'] . "</div>";
						
						echo "<div class='identifier'>" . $row['identifier'] . "</div>";
						
						echo "<div class='value'><input type='checkbox' class='equipment' ";
						
						if ( ! empty( $row['job'] ) && $row['job'] == $_GET['id']  )
						{
							echo "checked='checked'";
						}
							
						echo " /></div>";
					
						echo "</div>";
					
					}
					
				}
				
				if ( $noEquipment )
				{
					echo "<div class='job_container_content_row equipment noResult'>No Equipment Packed</div>";
				}
		
			?>
		</div>
		<div class='job_container_show equipment'>
			Show
		</div>
	</div>
	
	<div class='job_container'>
		<div class='job_container_headerRow'>
			<div class='job_container_header groupingName'>Grouping</div
			><div class='job_container_header value'>Required</div>
		</div>
		<div class='job_container_content'>
			<?php
				
				$noGroupings = true;
				
				if ( ! empty( $result['grouping'] ) )
				{
					
					foreach ( $result['grouping'] as $row )
					{
						echo "<div class='job_container_content_row grouping";
					
						if ( ! empty( $row['job'] ) && $row['job'] == $_GET['id'] )
						{
							echo " pack'>";
							$noGroupings = false;
						}
						else
						{
							echo " hide'>";
						}
						
						echo "<div class='id' style='display: none;' data-id='" . $row['groupingID'] . "'></div>";
					
						echo "<div class='groupingName'>" . $row['grouping'] . "</div>";
						
						echo "<div class='value'><input type='checkbox' class='grouping' ";
						
						if ( ! empty( $row['job'] ) && $row['job'] == $_GET['id']  )
						{
							echo "checked='checked'";
						}
							
						echo " /></div>";
					
						echo "</div>";
					
					}
					
				}
				
				if ( $noGroupings )
				{
					echo "<div class='job_container_content_row grouping noResult'>No Groupings Packed</div>";
				}
		
			?>
		</div>
		<div class='job_container_show grouping'>
			Show
		</div>
	</div>
	
	<div class='job_container'>
		<div class='job_container_headerRow'>
			<div class='job_container_header itemType'>Type</div
			><div class='job_container_header name'>Item</div
			><div class='job_container_header stock'>Available</div
			><div class='job_container_header value'>Required</div>
		</div>
		<div class='job_container_content'>
			<?php
			
				$noItems = true;
		
				if ( ! empty( $result['item'] ) )
				{
				
					foreach ( $result['item'] as $row )
					{
						echo "<div class='job_container_content_row item";
					
						if ( ! empty( $row['value'] ) )
						{
							echo " pack'>";
						}
						else
						{
							echo " hide'>";
						}
						
						echo "<div class='id' style='display: none;' data-id='" . $row['itemID'] . "'></div>";
						
						echo "<div class='itemType'>" . $row['itemType'] . "</div>";
					
						echo "<div class='name'>" . $row['item'] . "</div>";
					
						echo "<div class='stock'><input type='number' value='" . ( ! empty( $row['stock'] ) ? $row['stock'] : "0" ) . "' disabled /></div>";
					
						echo "<div class='value'><input type='number' min='0' class='item' value='" . ( ! empty( $row['value'] ) ? $row['value'] : "0" ) . "' /><span class='print_value'>" . ( ! empty( $row['value'] ) ? $row['value'] : "0" ) . "</span></div>";
						
						if ( ! empty( $row['value']) )
						{
							$noItems = false;
						}
					
						echo "</div>";
					
					}
				}
					
				
				echo "<div class='job_container_content_row item noResult";
				
				if ( ! $noItems )
				{
					echo " hide";
				}
				
				echo "'>No Items Packed</div>";
		
			?>
		</div>
		<div class='job_container_show item' data-hide='1'>
			Show
		</div>
	</div>
	
	<div class='job_save_wrapper'>
		<button id='job_save'>Save</button>
	</div>
	
</div>

<script>

	( function ($) {
		
		var request;
		
		$("#job_save").on("click", function () {
			
			var data = [];
			
			$(".job_container .job_container_content .value input").filter( function () { 
				return ( 
					$(this).prop('type') == 'checkbox' && $(this).prop('checked') 
				) || ( 
					$(this).prop('type') == 'number' &&  $(this).val() != 0 
				); 
			}).each( function () {
				
				if ( $(this).hasClass('equipment')  )
				{
					data.push({
						type: 'equipment',
						id: $(this).parent().parent().find(".id").attr("data-id"),
						value: $(this).prop('checked'),
						location: 1
					});
				}
				else if ( $(this).hasClass('grouping') )
				{
					data.push({
						type: 'grouping',
						id: $(this).parent().parent().find(".id").attr("data-id"),
						value: $(this).prop('checked'),
						location: 1
					});
				}
				else if ( $(this).hasClass('item') )
				{
					data.push({
						type: 'item',
						id: $(this).parent().parent().find(".id").attr("data-id"),
						value: $(this).val(),
						location: 1
					});
				}
				
			});
			
			request = $.ajax({
				url: "ajax/job_save.php",
				type: "post",
				data: "job=<?php echo $_GET['id']; ?>" + 
					"&json=" + JSON.stringify( data )
			}).done( function ( response, textStatus, jqXHR) {
				
				$("#screen_overlay").addClass("active").addClass("status");
				$("#screen_overlay_content").html(response).addClass("active").addClass("status");
				
				$("#screen_overlay").delay(2000).queue(function() {

					$(this).removeClass("active").removeClass("status");
					$("#screen_overlay_content").removeClass("active").removeClass("status");

					window.location.reload(true);

					$(this).dequeue();

				});
				
			});
			
		});
		
		$(".job_container .job_wrapper .edit").on( "click", function () {
			
			request = $.ajax({
				url: "ajax/edit_job.php",
				type: "post",
				data: "dataJob=<?php echo $_GET['id']; ?>"
			}).done( function ( response, textStatus, jqXHR) {
				
				$(".job_table")
					.html( response );
					
				$("#editJob_startDate")
					.datepicker({
						showOn: 'button',
						setDate: 0,
						dateFormat: 'mm-dd-yy',
						prevText:'',
				        nextText:'',
					});
					
				$("#editJob_startDate")
					.parent()
					.find(".trigger")
					.click( function () {

						var visible = $('#ui-datepicker-div').is(':visible');
						$("#editJob_startDate")
							.datepicker( visible ? 'hide' : 'show' );
						$('#ui-datepicker-div').css('z-index', '11000');

					});
					
				$("#editJob_deliverDate")
					.datepicker({
						showOn: 'button',
						setDate: 0,
						dateFormat: 'mm-dd-yy',
						prevText:'',
				        nextText:'',
					});
					
				$("#editJob_deliverDate")
					.parent()
					.find(".trigger")
					.click( function () {

						var visible = $('#ui-datepicker-div').is(':visible');
						$("#editJob_deliverDate")
							.datepicker( visible ? 'hide' : 'show' );
						$('#ui-datepicker-div').css('z-index', '11000');

					});
					
				$('.ui-datepicker-trigger').removeClass('ui-helper-hidden-accessible');
				$('.ui-datepicker-trigger').css('z-index', '11000');
				
				// Validation

				$("#editJob_startDate")
					.mask('00-00-0000', {reverse: true});
					
				$("#editJob_deliverDate")
					.mask('00-00-0000', {reverse: true});
					
			});
			
		});
		
		
		
		
		
		$(".print").on( "click", function () {
			
			$(".job_container_content_row .value input").each( function (){
				
				if ( $(this).val() == 0 )
				{
					$(this).parent().parent().addClass("hide");
				}
				
			});
			
			$(".job_container_content_row .value input:not([value=''])").each( function () {
					$(this).parent().find(".print_value").html( $(this).val() );
			});
			
			window.print();	
			
		});
		
		
		
		$(".job_container_show").on( "click", function () {
			
			var toggleClass = '';
			
			if ( $(this).hasClass("equipment") )
			{
				toggleClass =  ".equipment";
			}
			
			if ( $(this).hasClass("grouping") )
			{
				toggleClass =  ".grouping";
			}
			
			if ( $(this).hasClass("item") )
			{
				toggleClass =  ".item";
			}
			
			if ( $(".job_container_show"+toggleClass).attr("data-hide") == 1 )
			{
			
				$(".job_container_content_row"+toggleClass).removeClass("hide");
				$(".job_container_content_row.noResult"+toggleClass).hide();
				$(".job_container_show"+toggleClass).html("Hide").attr("data-hide", 0);
			
			}
			else
			{
				
				switch ( toggleClass )
				{
					case '.item':
						
						$(".job_container_content_row"+toggleClass+" .value input").each( function (){
					
							if ( $(this).val() == 0 )
							{
								$(this).parent().parent().addClass("hide");
							}
					
						});
						
						break;
						
					case '.equipment':
					case '.grouping':
						
						$(".job_container_content_row"+toggleClass+" .value input").each( function (){
					
							if ( ! $(this).prop('checked') )
							{
								$(this).parent().parent().addClass("hide");
							}
					
						});
						
						break;
					
				}
				
				
				if ( $(".job_container_content_row"+toggleClass+":not(.hide)").length == 1 )
				{
					$(".job_container_content_row.noResult"+toggleClass).show();
				}
				
				$(".job_container_show"+toggleClass).html("Show").attr("data-hide", 1);
			}
			
		});
		
	})(jQuery);

</script>