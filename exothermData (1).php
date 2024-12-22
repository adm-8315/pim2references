<?php

	/**
	 * Includes
	 */
	
	require_once("inc/dev_tools/print_dev.php");


	/**
	 * Variables
	 */

	$result = array();
	$started = array();
	$csv = "";
	$series = array();
	$rowCount = array();
	$data = array();
	$slots = array();


	/**
	 * MySQL
	 */
	
	$query = "
		SELECT
			qct.qcTestID,
			qct.slot,
			UNIX_TIMESTAMP(qct.startTime) as 'startTime',
			UNIX_TIMESTAMP(qct.stopTime) as 'stopTime',
			qct.water,
			qct.mix,
			qct.vib,
			qct.lotcode,
			m.materialID as 'materialID',
			m.material as 'material'
		FROM
			qcTest qct
		LEFT JOIN
			material m
			ON qct.material = m.materialID
		WHERE
			qct.qcTestID = ?
		LIMIT 1
		";

	$values = array(
		$_GET['id']
	);
	
	$result['qcTest'] = dbquery( $query, $values );
	
	foreach( $result['qcTest'] as $row )
	{
		$series[$row['qcTestID']] = $row['startTime'];
		$started[$row['slot']] = array( 
			"material" => $row['material'],
			"water" => $row['water'],
			"mix" => $row['mix'],
			"vib" => $row['vib'],
			"startTime" => date( 'D, M j h:i A', $row['startTime'] ),
			"stopTime" => date( 'D, M j h:i A', $row['stopTime'] ),
			"lotcode" => $row['lotcode']
		);
		
		if ( $row['stopTime'] == null )
		{
			$started[$row['slot']]['stopTime'] = null;
		}
	}
	
	foreach ( $series as $id => $startTime )
	{
		
		$query = "
			SELECT
				UNIX_TIMESTAMP(timestamp) as 'timestamp',
				temperature
			FROM
				qcTestData qctd
			LEFT JOIN
				qcTest qct
				ON qctd.qcTest = qct.qcTestID
			WHERE
				qctd.qcTest = ?
			AND
				qctd.timestamp >= qct.startTime
			AND
				if(
					qct.stopTime is null,
					1,
					qctd.timestamp <= qct.stopTime
				)
		";

		$values = array(
			$id
		);
		
		$result['series'.$id] = dbquery( $query, $values );
		
	}
	

	/**
	 * Process
	 */
		
	foreach ( $series as $id => $startTime )
	{
		
		$slots[] = $id;
	
		foreach ( $result['series'.$id] as $row )
		{
		
			$time = ($row['timestamp'] - $startTime);
		
			if ( empty( $data[$time] ) )
			{
				$data[$time] = array();
				for( $i = 0; $i < 8; $i++ )
				{
					$data[$time][$i + 1] = null;
				}
			}
		
			$data[$time][(array_search($id, $slots) + 1)] = $row['temperature'];
		
		}
	
	}
		
	ksort( $data );
	
	foreach ( $data as $date => $row )
	{
		$csv .= "[" . $date . ",";
		
		foreach( $row as $value )
		{
			
			if ( $value == null )
			{
				$csv .= "null,";
			}
			else
			{
				$csv .= $value . ",";
			}
			
		}
		
		$csv = rtrim( $csv, ',' );
		
		$csv .= '],';
	}
	
	$csv = rtrim( $csv, ',' );
	
	
	if ( empty($csv) )
	{
		
		$csv = '[';
		
		for( $i = 0; $i <= 8; $i++ )
		{
			$csv .= 'null,';
		}
		
		$csv = rtrim( $csv, ',' );
		
		$csv .= ']';
		
	}
	
	
?>

<div class='content'>
	
	<div class='exotherm'>
		
		<table>
			
			<tr>
				<th>Test ID</th>
				<td><?php echo $result['qcTest'][0]['qcTestID']; ?></td>
				<th>Slot</th>
				<td><?php echo $result['qcTest'][0]['slot']; ?></td>
			</tr>
			
			<tr>
				<th>Material</th>
				<td><?php echo $result['qcTest'][0]['material']; ?></td>
				<th>Lot Code</th>
				<td><?php echo $result['qcTest'][0]['lotcode']; ?></td>
			</tr>
			
			<tr>
				<th>Start Time</th>
				<td><?php echo date( 'D, M j h:i A', $result['qcTest'][0]['startTime'] ); ?></td>
				<th>Stop Time</th>
				<td><?php echo date( 'D, M j h:i A', $result['qcTest'][0]['stopTime'] ); ?></td>
			</tr>
			
			<tr>
				<th>Water</th>
				<td><?php echo $result['qcTest'][0]['water']; ?></td>
				<th></th>
				<td></td>
			</tr>
			
			<tr>
				<th>Mix</th>
				<td><?php echo $result['qcTest'][0]['mix']; ?></td>
				<th>Vibration</th>
				<td><?php echo $result['qcTest'][0]['vib']; ?></td>
			</tr>
			
		</table>
		
	</div>
	
	<div id="graphdiv"></div>
</div>

<style>

.exotherm {
	width: 100%;
}

.exotherm table {
	width: 100%;
	border: 1px solid black;
}

.exotherm table th {
	text-align: right;
	padding-right: 20px;
}

</style>

<script src='js/dygraph-combined.js'></script>
<script type="text/javascript">

	var started = <?php

	$outString = '[';
	
	foreach ($started as $id => $info )
	{
		$outString .= ( $id + 1 ) . ',';
	}
	
	$outString = rtrim( $outString, ',' );
	
	echo $outString . ']';
	
	?>;

	g = new Dygraph(

		// containing div
		document.getElementById("graphdiv"),
		[
			<?php echo $csv; ?>
		],
		{
			  width: '900',
			  height: '400',
			  labels: ["Time"<?php for( $i = 0; $i <= 7; $i++ ){ echo ",'" . ($i + 1) . "'"; } ?>],
			  valueRange: [60, 100],
			  connectSeparatedPoints: true,
			  colors: ['#396AB1', '#DA7C30', '#3E9651', '#CC2529', '#535154', '#6B4C9A', '#922428', '#948B3D'],
			  axes: {
			  	x: {
					valueFormatter: function(ms) {
						var hours = Math.floor( ms / 3600 );
						var min = Math.floor( (ms % 3600) / 60 );
						return hours + 'h:' + min + 'm';
					},
					axisLabelFormatter: function(d, gran) {
						var hours = Math.floor( d / 3600 );
						return hours + 'h';
					}
				}
			},
			highlightSeriesOpts: {
	          strokeWidth: 4,
	          strokeBorderWidth: 1,
	          highlightCircleSize: 5
	        },
			strokeWidth: 2
		}
	);
	
	$('.exothermRow').on("mouseout", function() {
		for( var i = 0; i < 8; i++ )
		{
			g.setVisibility(i, true);
		}
	});
	
	$('.exothermRow').on("mouseover", function() {
		var selected = $(this).attr('id').substring(4,5);
		
		g.clearSelection();
		g.highlightSet_ = null;		
		
		g.updateOptions({strokeWidth: 2.0});
		
		if ( $.inArray((parseInt(selected) + 1), started) > -1 )
		{
			for( var i = 0; i < 8; i++ )
			{
				g.setVisibility(i, false);
			}
			g.setVisibility(selected, true);
		}
		else
		{
			for( var i = 0; i < 8; i++ )
			{
				g.setVisibility(i, true);
			}
		}
	});
	
</script>