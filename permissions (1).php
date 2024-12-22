<?php
	
	/**
	 * Includes
	 */
	
	require_once( "inc/dbfunc.php" );
	require_once( "inc/dev_tools/print_dev.php" );
	
	
	/**
	 * Variables
	 */
	
	$result = array();
	$active = array();
	$blocks = array();


	/**
	 * MySQL
	 */
	
	// Permission Blocks

	$query = "
		SELECT
			pb.permissionBlockID,
			pb.permissionBlock,
			pb.permissionBlockDescription,
			pg.permissionGroupID,
			pg.permissionGroup,
			pg.locationBased
		FROM
			permissionBlock pb
		LEFT JOIN
			permissionGroup pg
			ON pb.permissionGroup = pg.permissionGroupID
		WHERE
			pb.active = 1
		ORDER BY
			pg.permissionGroupID ASC,
			pb.permissionBlock
	";
	
	$values = array();
	
	$result['permissionBlocks'] = dbquery( $query, $values );
	
	
	// Locations
	
	$query = "
		SELECT
			l.locationID,
			l.location
		FROM
			location l
		WHERE
			l.stockable = 1
	";
	
	$result['location'] = dbquery( $query, $values );
	
	// Permissions
	
	$query = "
		SELECT
			*
		FROM
			permissionLink pl
	";
	
	$result['permissions'] = dbquery( $query, $values );
	
	
	// Users
	
	$query = "
		SELECT
			*
		FROM
			user u
		ORDER BY
			u.username ASC
	";
	
	$result['users'] = dbquery( $query, $values );
	
	
	/**
	 * Process
	 */
	
	// Permission Blocks
	
	$temp = array();
	
	foreach ( $result['permissionBlocks'] as $row )
	{
		
		$blocks[] = $row['permissionBlockID'];
		
		if ( ! isset( $temp[$row['permissionGroupID']] ) )
		{
			$temp[$row['permissionGroupID']] = array();
		}
		
		$temp[$row['permissionGroupID']][] = $row;
		
	}
	
	$result['permissionBlocks'] = $temp;
	
	
	// Permissions
	
	$temp = array();
	
	foreach ( $result['permissions'] as $row )
	{
		
		if ( ! isset( $temp[ $row['user'] ] ) )
		{
			$temp[ $row['user'] ] = array();
		}
		
		if ( ! isset( $temp[ $row['user'] ][ $row['permissionBlock'] ] ) )
		{
			$temp[ $row['user'] ][ $row['permissionBlock'] ] = array();
			$temp[ $row['user'] ][ $row['permissionBlock'] ]['locations'] = array();
			$temp[ $row['user'] ][ $row['permissionBlock'] ]['allLocation'] = array();
		}
		
		$temp[ $row['user'] ][ $row['permissionBlock'] ]['allLocation'][] = $row['allLocation'];
		$temp[ $row['user'] ][ $row['permissionBlock'] ]['locations'][] = $row['location'];
			
	}
	
	$result['permissions'] =  $temp;
	unset( $temp );
	
	
	// Users
	
	foreach ( $result['users'] as $row )
	{
		
		if ( $row['active'] == true )
		{
			$active[] = $row['userID'];
		}
		
	}
	
	
	/**
	 * Display
	 */
	
?>
<div class='content'>
	
	<div id="permissions_container">
		
		<div class='userContainer'>
			<table>
				
				<tr>
					
					<td style='text-align: right; font-weight: 800;'>User:</td>
					
					<td>
						<select id="user">
							<?php

							foreach ( $result['users'] as $row )
							{

								if ( $row['userID'] == $_SESSION['user_id'] )
								{
									echo "<option selected='selected' value='{$row['userID']}'>{$row['username']}</option>";
								}
								else
								{
									echo "<option value='{$row['userID']}'>{$row['username']}</option>";
								}

							}

							?>
						</select>
					</td>
					
				</tr>
				
				<tr>
					
					<td style='text-align: right;  font-weight: 800;'>Active:</td>
					
					<td>
						<div style='width: 12px; margin: 0 auto;'><input id='userActive' type='checkbox' checked /></div>
					</td>
					
				</tr>
				
			</table>
		</div>
		
		<div class='permissionSave_container' style='top: -30px;'>
			<button class='permissionSave'>Save</button>
		</div>
				
		<table id="permissions" style='margin-top: -20px'>
		
		<?php
			
			foreach ( $result['permissionBlocks'] as $group )
			{
				
				$colspan = count( $result['location'] ) + 2;
				
				echo "<tr class='groupName'>";
				echo "<th colspan='{$colspan}'>{$group[0]['permissionGroup']}</th>";
				echo "</tr>";
				
				echo "<tr class='groupHeader'>";
				echo "<th><div>Block</div></th>";
				
				if ( $group[0]['locationBased'] == false )
				{
					echo "<th colspan='" . ( $colspan - 1 ) . "'><div>Enable</div></th>";
				}
				else
				{
					
					if ( count( $result['location'] ) > 0 )
					{
						echo "<th><div>All</div></th>";
						
						foreach ( $result['location'] as $row )
						{
							echo "<th><div>{$row['location']}</div></th>";
						}
						
					}
					
				}
				
				echo "</tr>";
				
				foreach( $group as $key => $row )
				{
					
					if ( $key % 2 == 1 )
					{
						echo "<tr class='odd'>";
					}
					else
					{
						echo "<tr class='even'>";
					}
					
					echo "<td><div class='permissionBlock'>{$row['permissionBlock']}</div><div class='permissionBlockDescription'>{$row['permissionBlockDescription']}</div></td>";
					
					
					if ( 
						$group[0]['locationBased'] == true && 
						count( $result['location'] ) > 0
					) {
						
						echo "<td class='checkbox'><div><input class='locationAll' data-block='{$row['permissionBlockID']}' type='checkbox' /></div></td>";
					
						foreach ( $result['location'] as $location )
						{
							echo "<td class='checkbox'><div><input class='location' data-block='{$row['permissionBlockID']}' data-location='{$location['locationID']}' type='checkbox' /></div></td>";
						}
					
					}
					else
					{
						echo "<td class='checkbox' colspan='" . ( $colspan - 1 ) . "'><div><input class='permissionAll' data-block='{$row['permissionBlockID']}' type='checkbox' /></div></td>";
					}
					
					echo "</tr>";
					
				}
				
			}
		?>
		
		</table>
		
		<div style='margin-top: 20px'>
			<button class='permissionSave'>Save</button>
		</div>
		
	</div>
</div>

<script>

	( function ($) {
		
		var request;
		
		$(document).on( "click", ".permissionAll", function () {
			
			if ( $(this).prop('checked') === true )
			{
				$(this).parent().parent().parent().parent().find("input[type='checkbox']").prop('checked', true).attr("disabled", true);
				$(this).attr("disabled", false);
			}
			else
			{
				$(this).parent().parent().parent().parent().find("input[type='checkbox']").prop('checked', false).attr("disabled", false);
			}
			
		});
		
		$(document).on( "click", ".locationAll", function () {
			
			if ( $(this).prop('checked') === true )
			{
				$(this).parent().parent().parent().find("input[type='checkbox']").prop('checked', true);
			}
			else
			{
				$(this).parent().parent().parent().find("input[type='checkbox']").prop('checked', false);
			}
			
		});
		
		$(document).on( "click", ".location", function () {
						
			if ( $(this).parent().parent().parent().find(".checkbox div .location:checked").length == $(this).parent().parent().parent().find(".checkbox div .location").length )
			{
				$(this).parent().parent().parent().find(".checkbox div .locationAll").prop( 'checked', true );
			}
			else
			{
				$(this).parent().parent().parent().find(".checkbox div .locationAll").prop( 'checked', false );
			}
						
		});
		
		$(document).on( "change", "#user", function () {
			activeUser( $(this).val() ) ;
			permissionUser( $(this).val() ) ;
	 	});
		
		$(document).on( "click", ".permissionSave", function () {
			permissionSave();
		});
		
		function activeUser( userID )
		{
			
			activeUsers = <?php echo json_encode( $active ); ?>;
			
			if ( $.inArray( userID, activeUsers ) )
			{
				$("#userActive").prop( "checked", true );
			}
			else
			{
				$("#userActive").prop( "checked", false );
			}
			
		}
		
		function permissionUser( userID )
		{
			
			var permissions = <?php echo json_encode( $result['permissions'] ); ?>;
			
			
			$("#permissions").find( "input[type='checkbox']" ).prop( "checked", false ).attr("disabled", false);
			
			if ( permissions[userID] != undefined )
			{
				$.each( permissions[userID], function ( block, values ) {
				
					if ( block == 1 )
					{
					
						$("#permissions").find( "input[type='checkbox']" ).prop( "checked", true ).attr("disabled", true);
						$("#permissions").find( ".permissionAll" ).attr("disabled", false);
						
					}
					else
					{
					
						if ( values['allLocation'] == 1 )
						{
							$("input[type='checkbox'][data-block='" + block + "']").prop( "checked", true );
						}
						else
						{
						
							$.each( values.locations, function( index, locationID ) {
								$("input[type='checkbox'][data-block='" + block + "'][data-location='" + locationID + "']").prop( "checked", true );
							});
						
						}
					
					}
				
				});
				
			}
			
		}
		
		function permissionSave()
		{
			
			var out = {};
			var blocks = <?php echo json_encode( $blocks ); ?>;
			
			out['user'] = $("#user").val();
			out['active'] = 0;
			if ( $("#userActive:checked").length > 0 )
			{
				out['active'] = 1;
			}
			
			out['permissions'] = {};
			
			if ( $(".permissionAll:checked").length > 0 )
			{
				out['permissions'][1] = 1;
			}
			else
			{
				
				$.each( blocks, function ( key, block ) {
					
					if ( $(".locationAll:checked[data-block='" + block + "']").length > 0 )
					{
						out['permissions'][block] = {};
						out['permissions'][block]["-1"] = 1;
					}
					else
					{
						
						$(".location:checked[data-block='" + block + "']").each( function () {
							
							if ( out['permissions'][block] === undefined )
							{
								out['permissions'][block] = {};
							}
							
							out['permissions'][block][$(this).data('location')] = 1;
							
						});
						
					}
					
				});
				
			}
			
			if (request) {
				request.abort();
			}
			
			console.log(  );
			
			request = $.ajax({
				url: "ajax/permissions.php",
				type: 'post',
				data: "JSON=" + JSON.stringify( out )
			}).done(function( response, textStatus, jqXHR ) {
				$("#screen_overlay_content")
					.html(response);
				console.log( response );
			});
			
			$("#screen_overlay").addClass("active");
			$("#screen_overlay_content").addClass("active").addClass("status");

			$("#screen_overlay").delay(1000).queue(function() {

				$(this).removeClass("active");
				$("#screen_overlay_content").removeClass("active");

				$(this).dequeue();

			}).delay(200).queue(function() {
				$("#screen_overlay_content").removeClass("status");

				$(this).dequeue();
				
				location.reload();
			});
			
		}
		
		permissionUser( $("#user").val() );
		
	})(jQuery);

</script>