<?php

	/**
	 * Includes
	 */
	
	require_once("inc/dbfunc.php");
	
	
	/**
	 * Variables
	 */
	
	$result = array();
	$permissions_permission = false;
	
	
	/**
	 * MySQL
	 */
	
	// Permission
	
	$query = "
		SELECT
			*
		FROM
			`permissionLink`
		WHERE
			user = ?
	";

	$values = array(
		$_SESSION['user_id']
	);

	$result['permission'] = dbquery( $query, $values );
	
	// User
	
	$query = "
		SELECT
			*
		FROM
			user
		WHERE
			userID = ?
	";
	
	$values = array(
		$_SESSION['user_id']
	);
	
	$result['user'] = dbquery( $query, $values );
	
	// Location
	
	$query = "
		SELECT
			*
		FROM
			location
		WHERE
			stockable = 1
		AND
			active = 1
		ORDER BY
			location ASC
	";
	
	$values = array();
	
	$result['location'] = dbquery( $query, $values );
	
	// Owner
	
	$query = "
		SELECT
			c.companyID,
			c.company
		FROM
			company c
		LEFT JOIN
			companyCompanyPropertyLink ccpl
			ON ccpl.company = c.companyID
		WHERE
			ccpl.companyProperty = 1
		AND
			c.active = 1
		ORDER BY
			c.company ASC
	";
	
	$values = array();
	
	$result['owner'] = dbquery( $query, $values );
	
	
	/**
	 * Permission Edit Check
	 */
	
	foreach ( $result['permission'] as $row )
	{
		if ( $row['permissionBlock'] == 1 )
		{
			$permissions_permission = true;
		}
	}
	
	
	/**
	 * Display
	 */
	
?>

<div id="user_profile">
	<div class='user_profile_header'>User Profile for <?= $result['user'][0]['username'] ?></div>

	<div id="user_profile_resetPassword" class="user_profile_block">
	
		<div class="user_profile_block_header">Change Password</div>
	
		<div id="user_profile_form_current">
			<div class="user_profile_label"> Current Password</div>
			<div class="user_profile_content">
				<input type="password" id="password_current" />
			</div>
		</div>
	
		<div id="user_profile_form_current">
			<div class="user_profile_label"> New Password</div>
			<div class="user_profile_content">
				<input type="password" id="password_new" />
			</div>
		</div>
	
		<div id="user_profile_form_current">
			<div class="user_profile_label"> Repeat Password</div>
			<div class="user_profile_content">
				<input type="password" id="password_repeat" />
			</div>
		</div>
		
		<div class="user_block_submit">
			Confirm
		</div>
		<div class="clear_me"></div>
	
	</div>

	<div id="user_profile_defaultLocation" class="user_profile_block">
	
		<div class="user_profile_block_header">Default Location</div>
	
		<div id="user_profile_form_defaultLocation">
			<div class="user_profile_label">Default Location</div>
			<div class="user_profile_content">
				<select id="defaultLocation" >
					<?php
			
					foreach ( $result['location'] as $row )
					{
						if ( $row['locationID'] == $result['user'][0]['defaultLocation'] )
						{
							// Selected
							echo "<option data-location='{$row['locationID']}' selected='selected'>{$row['location']}</option>";
						}
						else
						{
							// Other
							echo "<option data-location='{$row['locationID']}' >{$row['location']}</option>";
						}
				
					}
			
					?>
				</select>
			</div>
		</div>
		
		<div class="user_block_submit">
			Confirm
		</div>
		<div class="clear_me"></div>
	
	</div>

	<div id="user_profile_defaultOwner" class="user_profile_block">
	
		<div class="user_profile_block_header">Default Owner</div>
	
		<div id="user_profile_form_defaultOwner">
			<div class="user_profile_label">Default Owner</div>
			<div class="user_profile_content">
				<select id="defaultOwner" >
					<?php
			
					foreach ( $result['owner'] as $row )
					{
						if ( $row['companyID'] == $result['user'][0]['defaultOwner'] )
						{
							// Selected
							echo "<option data-owner='{$row['companyID']}' selected='selected'>{$row['company']}</option>";
						}
						else
						{
							// Other
							echo "<option data-owner='{$row['companyID']}' >{$row['company']}</option>";
						}
				
					}
			
					?>
				</select>
			</div>
		</div>
		
		<div class="user_block_submit">
			Confirm
		</div>
		<div class="clear_me"></div>
	
	</div>
	
</div>

<script>

	( function($) {

		/**
		 * User Profile
		 */

		$(document).on("click", ".user_block_submit", function() {

			if (request) {
				request.abort();
			}

			switch ($(this).parent().attr("id")) {

				case 'user_profile_resetPassword':

					request = $.ajax({
						url: "ajax/change_password.php",
						type: "post",
						data: "passwordCurrent=" +
							$(this).parent().find("#password_current").val() +
							"&passwordNew=" +
							$(this).parent().find("#password_new").val() +
							"&passwordRepeat=" +
							$(this).parent().find("#password_repeat").val()
					}).done(function(response, textStatus, jqXHR) {
						$("#screen_overlay_content")
							.html(response);
					});

					break;

				case 'user_profile_defaultLocation':

					request = $.ajax({
						url: "ajax/change_default_location.php",
						type: "post",
						data: "defaultLocation=" +
							$(this).parent().find("#defaultLocation")
							.find(":selected")
							.attr("data-location")
					}).done(function(response, textStatus, jqXHR) {
						$("#screen_overlay_content")
							.html(response);
					});

					break;

				case 'user_profile_defaultOwner':

					request = $.ajax({
						url: "ajax/change_default_owner.php",
						type: "post",
						data: "defaultOwner=" +
							$(this).parent().find("#defaultOwner")
							.find(":selected")
							.attr("data-owner")
					}).done(function(response, textStatus, jqXHR) {
						$("#screen_overlay_content")
							.html(response);
					});

					break;

			}

			$("#screen_overlay").addClass("active");
			$("#screen_overlay_content").addClass("active").addClass("status");

			$("#screen_overlay").delay(1000).queue(function() {

				$(this).removeClass("active");
				$("#screen_overlay_content").removeClass("active");

				$(this).dequeue();

			}).delay(200).queue(function() {
				$("#screen_overlay_content").removeClass("status");

				//$("#screen_overlay_content").html("");

				$(this).dequeue();
			});


		});
	})(jQuery);

</script>