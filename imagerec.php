<!DOCTYPE html>
<html lang="en">
<head>
  <title>Bootstrap Example</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>
<body>
<?php
//print_r($_FILES);exit;
$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
// Check if image file is a actual image or fake image
if(isset($_POST["submit"])) {
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if($check !== false) {
        //echo "File is an image - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }
}
// Check if file already exists
if (file_exists($target_file)) {
    //echo "Sorry, file already exists.";
    $uploadOk = 0;
}
// Check file size
if ($_FILES["fileToUpload"]["size"] > 5000000) {
    echo "Sorry, your file is too large.";
    $uploadOk = 0;
}
// Allow certain file formats
if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
&& $imageFileType != "gif" ) {
    echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    $uploadOk = 0;
}
// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    //echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
} else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
       // echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}
// Read text from the uploaded image
    require 'vendor/autoload.php';

    use Aws\Rekognition\RekognitionClient;

    $options = array('region' => 'us-west-2',  'version' => '2016-06-27','credentials' => [
        'key'    => 'AKIAIBKTAHLYLA6HKTVA',
        'secret' => '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'
    ] );

    $rekognition = new RekognitionClient($options);

    #Get local image
    $fp_image = fopen($target_file, 'r');
    $image = fread($fp_image, $_FILES["fileToUpload"]["size"]);
    fclose($fp_image);
 # Call DetectFaces
    $result = $rekognition->DetectText(array(
       'Image' => array(
          'Bytes' => $image,
       ),
       'Attributes' => array('ALL')
       )
    );

    # Display info for each detected text
//var_dump($result);
    for ($n=0;$n<sizeof($result["TextDetections"]); $n++){
	if($result['TextDetections'][$n]['Type'] == "LINE" && $result['TextDetections'][$n]['Confidence'] >= 95){
      		print 'License# : ' . $result['TextDetections'][$n]['DetectedText'] . "<br/> "
//		. PHP_EOL
//      		.  PHP_EOL
      		.  PHP_EOL . PHP_EOL;
		$license = $result['TextDetections'][$n]['DetectedText'];
	}
    }
$license = str_replace( ' ', '', $license );
$license = trim($license, '"');
// Read vehicle information
	
$url = 'https://integration-qa.cdk.com/Vehicle/vehicleExtract/delta';
$xml = '<VehicleExtractService>
   <Request>
      <Sender>
         <CreatorNameCode>DS</CreatorNameCode>
         <SenderNameCode>DS</SenderNameCode>
         <ActivationID>19032</ActivationID>
         <DealerCountryCode>US</DealerCountryCode>
         <LanguageCode>en-US</LanguageCode>
         <DeliveryPendingMailIndicator>True</DeliveryPendingMailIndicator>
      </Sender>
      <Destination>
         <DestinationNameCode>DS</DestinationNameCode>
         <ActivationID>19032</ActivationID>
         <DealerTargetCountry>US</DealerTargetCountry>
      </Destination>
      <CreationDateTime>2017-07-06:01:01:-06:00</CreationDateTime>
   </Request>
   <SearchCriteria>
      <StartDate></StartDate>
      <VIN></VIN>
      <CustNo></CustNo>
      <License>'.$license.'</License>
   </SearchCriteria>
</VehicleExtractService>';
function curl_get($url, $xml){
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $xml );
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
}



$sXML = curl_get($url, $xml);
/*$oXML = new SimpleXMLElement($sXML);
print_r($oXML->Detail->VehicleDetails->VehicleInformation);
foreach($oXML->Detail->VehicleDetails->VehicleInformation as $a=>$b){
	$data[$a] = (string)$b;
}
echo json_encode($data);*/
$simple = simplexml_load_string($sXML);
 
$arr = json_decode( json_encode($simple) , 1);
/*print("<pre>");
print_r($arr);
print("</pre>");*/
$vehid = $arr['Detail']['VehicleDetails']['VehicleInformation']['VehID'];
//echo "vehid ".$vehid;
?>
<div class="container">
  <h2>Vehicle 360&#176;</h2>
  <div class="panel-group">
    <div class="panel panel-primary">
      <div class="panel-heading font-weight-bold">Vehicle Information <?php echo $license;?></div>
      <div class="panel-body">
	<div class="row">
		<div class="col-md-6">
	<b>VIN</b> : <?php echo $arr['Detail']['VehicleDetails']['VehicleInformation']['VIN'];?>
		</div>
		<div class="col-md-6">
				<b>Vehicle License</b> : <?php echo $arr['Detail']['VehicleDetails']['VehicleInformation']['LicenseNo'];?>
      		</div>
	</div>
	<div class="row">
                <div class="col-md-6">
                <b>Make</b> : <?php echo $arr['Detail']['VehicleDetails']['VehicleInformation']['MakeName'];?>
		</div>
                <div class="col-md-6">
                <b>Model</b> : <?php echo $arr['Detail']['VehicleDetails']['VehicleInformation']['ModelName'];?>
		</div>
        </div>

		<br/><b>In Service Date</b> : <?php echo $arr['Detail']['VehicleDetails']['VehicleInformation']['InServiceDate'];?>
	<br/>	<b>Last Service Date</b> : <?php echo $arr['Detail']['VehicleDetails']['VehicleInformation']['LastServiceDate'];?>

	</div>
    </div>
<?php

    $vin = $arr['Detail']['VehicleDetails']['VehicleInformation']['VIN'];
    $url = "https://integration-qa.cdk.com/repairorder/openrepairorderdelta";
    $xml = '<?xml version="1.0" encoding="utf-8"?>
<RepairOrder>
  <Request>
    <Sender>
      <CreatorNameCode>DS</CreatorNameCode>
       <SenderNameCode>DS</SenderNameCode>
       <ActivationID>19032</ActivationID>
       <DealerCountryCode>US</DealerCountryCode>
       <LanguageCode>en-US</LanguageCode>
       <DeliveryPendingMailIndicator>True</DeliveryPendingMailIndicator>
    </Sender>
    <Destination>
       <DestinationNameCode>DS</DestinationNameCode>
       <ActivationID>19032</ActivationID>
       <DealerTargetCountry>US</DealerTargetCountry>
    </Destination>
    <CreationDateTime>2017-07-06:01:01:-06:00</CreationDateTime>
  </Request>
  <RepairOrderSearchCriteria>
    <RepairOrderDetail>
      <StartDate></StartDate>
      <RONumber></RONumber>
      <VIN>'.$vin.'</VIN>
    </RepairOrderDetail>
  </RepairOrderSearchCriteria>
</RepairOrder>';
//echo "vin ".$vin;
$sXML = curl_get($url, $xml);

$simple = simplexml_load_string($sXML);
 
$arr = json_decode( json_encode($simple) , 1);

$issue = $arr['Detail']['RepairOrder']['RepairOrderDetails']['Line']['lineServiceRequest'];

/*print("<pre>");
print_r($arr);
print("</pre>");
*/
?>
 <div class="panel panel-primary">
      <div class="panel-heading font-weight-bold">Service History</div>
      <div class="panel-body">
        <div class="row">
                <div class="col-md-6">
        <b>Service 1</b> : 01/02/2017 
                </div>
                <div class="col-md-6">
                                <b>Service 2</b> : 01/03/2018
                </div>
        </div>
        <div class="row">
                <div class="col-md-6">
                <b>Details</b> : <?php echo $issue;?>
                </div>
                <div class="col-md-6">
                <b>Details</b> : Glass break
                </div>
        </div>

               <!-- <br/><b>In Service Date</b> : <?php echo $arr['Detail']['VehicleDetails']['VehicleInformation']['InServiceDate'];?>
        <br/>   <b>Last Service Date</b> : <?php echo $arr['Detail']['VehicleDetails']['VehicleInformation']['LastServiceDate'];?>
-->
        </div>
    </div>
 <div class="panel panel-primary">
      <div class="panel-heading font-weight-bold">Vehicle Health</div>
      <div class="panel-body">
        <div class="row">
                <div class="col-md-6">
        <b>RPM</b> : 
                </div>
                <div class="col-md-6">
                                <b>Mileage</b> : 
                </div>
        </div>
        <div class="row">
                <div class="col-md-6">
                <b>Throttle Position</b> : 
                </div>
                <div class="col-md-6">
                <b>Speed</b> : 
                </div>
        </div>

                <br/><b>Short Term Fuel Trim</b> : 
        <br/>   <b>Long Term Fuel Trim</b> : 

        </div>
    </div>
<?php include_once('realtime.html'); ?>
  </div>
</div>
</body>
</html>
