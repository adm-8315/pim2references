<?php
	
	/**
	 * Variables
	 */
	
	$result = array();
	
	
	/**
	 * MySQL
	 */
	
	// Company
	
	$query = "
		SELECT
			c.companyID,
			c.company,
			cp.companyPropertyID,
			cp.companyProperty,
			ct.companyTypeID,
			ct.companyType,
			n1.number as 'defaultNumber_phone',
			n1.ext as 'defaultNumber_phone_ext',
			n2.number as 'defaultNumber_fax',
			n2.ext as 'defaultNumber_fax_ext'
		FROM
			company c
		LEFT JOIN
			companyCompanyPropertyLink ccpl
			ON c.companyID = ccpl.company
		LEFT JOIN
			companyProperty cp
			ON ccpl.companyProperty = cp.companyPropertyID
		LEFT JOIN
			companyCompanyTypeLink cctl
			ON c.companyID = cctl.company
		LEFT JOIN
			companyType ct
			ON cctl.companyType = ct.companyTypeID
		LEFT JOIN
			number n1
			ON c.defaultNumber_phone = n1.numberID
		LEFT JOIN
			number n2
			ON c.defaultNumber_fax = n2.numberID
		WHERE
			c.companyID = ?
	";
	
	$values = array(
		$_GET['id']
	);
	
	$result['company'] = dbquery( $query, $values );
	
	
	// Contact
	
	$query = "
		SELECT
			p.*
		FROM
			companyLocationLinkPersonLink cllpl
		LEFT JOIN
			person p
			ON cllpl.person = p.personID
		WHERE
			cllpl.companyLocationLink = ?
	";
	
	$values = array(
		$_GET['id']
	);
	
	$result['contact'] = dbquery( $query, $values );
	
	
	// Products
	
	$query = "
		SELECT DISTINCT
			p.productID as 'id',
			p.product as 'product'
		FROM
			product p
		LEFT JOIN
			productConsumerLink pcl
			ON p.productID = pcl.product
		LEFT JOIN
			companyLocationLink cll
			ON pcl.companyLocationLink = cll.companyLocationLinkID
		WHERE
			p.productType = 16
		AND
			cll.company = ?
		GROUP BY
			p.productID
		ORDER BY
			p.product
		
	";
	
	$values = array(
		$_GET['id']
	);
	
	$result['product'] = dbquery( $query, $values );
	
	
	// Job Locations
	
	$query = "
		SELECT
			l.locationID,
			l.location
		FROM
			companyLocationLink cll
		LEFT JOIN
			location l
			ON cll.location = l.locationID
		WHERE
			l.active = 1
		AND
			cll.company = ?
	";
	
	$values = array(
		$_GET['id']	
	);
	
	$result['jobLocation'] = dbquery( $query, $values );
 	
	// Job
	
	$query = "
		SELECT
			j.*,
			cll.location
		FROM
			job j
		LEFT JOIN
			companyLocationLink cll
			ON j.companyLocationLink = cll.companyLocationLinkID
		WHERE
			cll.company = ?
		ORDER BY
			cll.location ASC
	";
	
	$values = array(
		$_GET['id']
	);
	
	$result['job'] = dbquery( $query, $values );
	
?>

<div class='content'>
	
	
	
	<div class='customer_container'>
		<div class='customer_wrapper'>
			
			<div class='customer_title'>
				<?php 
					if ( ! empty( $result['company'] ) ) 
					{ 
						echo $result['company'][0]['company']; 
				?>&nbsp;&nbsp;
				<button id='company_edit' data-material='<?php echo $_GET['id']; ?>'  style='z-index: 9998'>
					<span class='adjust_button_image'>&nbsp;</span>
				</button>
			</div>
			
			<table class='customer_table'>
				
				<tr>
					<td class='left'>Company Type</td>
					<td class='right'><?php echo $result['company'][0]['companyType']; ?></td>
				</tr>
				
			</table>
			
			<input type="hidden" id="overlay_company" value="<?php echo $_GET['id']; ?>">
			
		</div>
	</div>
	
	
	
	<!--<div class='contact_container'>
		<div class='contact_wrapper'>
			
			<div class='contact_title'>Contacts</div>
			
			<div class='contact_tabs'>
				Locations
			</div>
			
			<div class='contact_content'>
				
				Contacts
				
				<div class='clearfix' style='margin-top: 20px'></div>
				
			</div>
			
		</div>
	</div>-->
	
	
	<div class='product_container'>
		<div class='product_wrapper'>
			
			<div class='product_title'>Products</div>
			
			<div class='product_content'>
				
				<div class='app_report_table' style="margin-bottom: 0;">
					<table>
					<?php 
				
						foreach ( $result['product'] as $row )
						{
							echo "<tr class='product' data-nav='product' data-id='{$row['id']}'><td>{$row['product']}</td></tr>";
						}
				
					?>
					</table>
				</div>
				
			</div>
			
		</div>
	</div>
	
	
	<div class='job_container'>
		<div class='job_wrapper'>
			
			<div class='job_title'>Jobs</div>
			
			<div class='job_tabs'>
				
				<?php
				
					$first = true;
				
					foreach ( $result['jobLocation'] as $location )
					{
						
						if ( $first )
						{
							echo "<div class='job_tab selected' data-location='{$location['locationID']}'>{$location['location']}</div>";
							$first = false;
						}
						else
						{
							echo "<div class='job_tab' data-location='{$location['locationID']}'>{$location['location']}</div>";
						}
						
					}
				
				?>
				
			</div>
			
			<div class='job_content'>
				
				<?php
				
				if ( empty( $result['job'] ) )
				{
					echo "No Jobs";
				}
				else
				{
					
					$first = true;
					
					foreach ( $result['jobLocation'] as $location )
					{
					
						if ( $first )
						{
							echo "<table class='inventory_tab_content' data-id='{$location['locationID']}'>";
							$first = false;
						}
						else
						{
							echo "<table class='inventory_tab_content' data-id='{$location['locationID']}' style='display: none;'>";
						}
						
						echo "<thead><tr>
							<th>Job #</th>
							<th>Description</th>
							<th>Deliver Date</th>
						</tr></thead>";
						
						foreach ( $result['job'] as $job )
						{
							echo "<tr>";
							
							foreach ( $job as $key => $value )
							{
								
								switch ( $key )
								{
									
									case 'jobID':
										echo '<td>' . $value . '</td>';
										break;
									
									case 'description':
										echo '<td>' . $value . '</td>';
										break;
										
									case 'deliverDate':
										echo '<td>' . $value . '</td>';
										break;
										
								}
								
							}
							
							echo "</tr>";
							
						}
						
						echo "</table>";
					
					}
				}
				
				?>
				
				<div class='clearfix' style='margin-top: 20px'></div>
				
			</div>
			
		</div>
	</div>
				
				
	<?php
		}
	?>
	
	
	
</div>

<script>

	( function ($) {
		
		$(document).on("click", ".product_container .product", function() {
			
			if ( $(this).attr("data-nav") != undefined ) {
				
					generateURL( $(this) );
				
			}
			
		});
		
	})(jQuery);

</script>