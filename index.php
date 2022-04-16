<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		
		<title>Atlas des Odonates de l'Ain</title>
		
		<!-- Leaflet -->
		<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A==" crossorigin=""/>
		<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js" integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA==" crossorigin=""></script>
		
		<!-- jQuery (pour l'ajax) -->
		<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
		
		<!-- Bootstrap -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		
		<!-- Leaflet Marker Cluster -->
		<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
		<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css">
		<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css">
		
		<!-- Leaflet easy button -->
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.css">
		<script src="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.js"></script>
		<!-- Font Awesome -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
		
		<!-- Departmental limits -->
		<script src="./aindpt.js"></script>
		
		<!-- CSS Style file -->
		<link rel="stylesheet" href="./style.css"/>
	</head>
	
	<body>
		<!-- page bootstrap -->
		<div class="container-fluid">
			<!-- database connection -->
			<?php
				$dbuser = "__________";
				$userpass = "__________";
				
				try{
					$dbh = new PDO('pgsql:host=postgresql-atlas-odonate-ain.alwaysdata.net;port=5432;dbname=atlas-odonate-ain_bd', $dbuser, $userpass);
				}
				catch (PDOException $e){
					// report error message
					print "<b>Problème de connexion à la base de données</b> - PHP Data Objects (PDO) extension<br />Erreur : " . $e->getMessage() . "<br/>";
					die();
				}
			?>
			
			
			<div class="title">
				<h1>Bienvenue sur l'Atlas des Odonates de l'Ain</h1>
			</div>
			
			<!-- adaptative logo bootstrap -->
			<div class="row">
				<div>	
					<img class="img-fluid" src="logogrpls.jpg" alt="Logo Groupe Sympetrum" style="width:10%;z-index:10;position:relative;left:88%;top:90px">
				</div>
			</div>
			
			<!-- dropdown list adaptative bootstrap -->
			<div class="container-fluid">
				<div class="row text-center justify-content-center"  style="margin-top:-13%;z-index: 10;position:relative">
					<select name="select_specie" id="specie" style="height:38px;font-size:22px;text-align:center;box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2)">
						<option selected disabled hidden style='display: none' value=''>-- Sélectionner une espèce --</option>
						<?php
							// retrieve all dragonfly species from the database
							$stmt = $dbh->prepare("SELECT DISTINCT nom_latin FROM odonate_ain_table
							WHERE nombre > 0  AND nom_latin NOT IN ('Anisoptera','Odonata','Zygoptera') AND nom_latin NOT LIKE '%ae'
							ORDER BY nom_latin;");
							$stmt->execute();
							$lignes = $stmt->fetchAll();
							foreach($lignes as $row){ ?>
								<option><?php echo $row['nom_latin'] ?></option>
							<?php } ?>
					</select>
				</div>
			</div>
			
			<!-- loading spinner responsive bootstrap -->
			<div class="text-center">
				<div>
					<!-- define loading spinner -->
					<img src="red_spinner.gif" width="50px" height="30px" style="display:none;position:absolute;top:145px;left:49%;opacity:0.6;z-index:999;" id="spinner">
				</div>
			</div>
			
			
			
			<!-- map adaptative bootstrap --> 
			<div class="row" id="mapid">
				<!-- full width -->
				<div class="col-lg-12 col-md-12 col-sd-12 col-xs-12"></div>
			</div>
			
			


			<script type="text/javascript">
				// process specie selection request using ajax method !important
				function req(value_search) {
					$.ajax({
						url: 'req.php',
						type: 'POST',
						data: {
							'select_specie': value_search,
						},
						beforeSend: function() {
							// display loading spinner
							$("#spinner").css("display","block");
						},
						success: function(results) {
							console.log(results);
							// clear previously loaded marker layer
							cluster.clearLayers();
							try{
								// parse the result of the query sent to the database and create a javascript object
								data = JSON.parse(results);
								console.log(data);
								
								// Add the layer resulting from the species selection on the map base
								const specie_layer = L.geoJSON(data, {
									onEachFeature: onEachFeature
								}).addTo(cluster);
								map.addLayer(cluster);
								
								// Automatic zoom on the displayed layer
								map.flyToBounds(specie_layer.getBounds());
								
								$("#spinner").css("display","none");
							} catch (e) {
								console.log(e);
								alert("Résultat de la requête : \n" + results);
								alert(" La requête n'a pas pu être traitée \n Erreur : " + e.name + "\n Message : " + e.message + "\n");
								$("#spinner").css("display","none");
							}
							
						},
						// Error log
						error: function(error) {
							console.log(error);
							alert(" La requête n'a pu être traitée (méthode ajax) \n Erreur : " + error.name + "\n Message : " + error.message + "\n");
							$("#spinner").css("display","none");
						}
					})
				}
				
				// run the query when changing the selection in the dropdown list
				$('#specie').on('change', function() {
					req(this.value);
				});
				
				// region of interest
				const ain = {lat: 46.09049, lng: 5.39848};
				const zoomLevel = 9.50;
				
				// create a map using Leaflet; zoomSnap defines the zoom range
				const map = L.map('mapid', {zoomControl: false, minZoom:5,  zoomSnap: 0.25}).setView([ain.lat, ain.lng], zoomLevel);
				

				// Mapbox layer
				const mapbox_layer = L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
					attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
					minZoom: 0,
					maxZoom: 18,
					id: 'mapbox/streets-v11',
					tileSize: 512,
					zoomOffset: -1,
					accessToken: '_____________'
				});
				//mapbox_layer.addTo(map);


				// Google Maps layer
				const gmap_layer = L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
					minZoom: 0,
					maxZoom: 18,
					id: 'google maps',
					subdomains:['mt0','mt1','mt2','mt3']
				});
				gmap_layer.addTo(map);
				

				// Google Satellite layer
				const gsat_layer = L.tileLayer('https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
					minZoom: 0,
					maxZoom: 18,
					id: 'google sat',
					subdomains:['mt0','mt1','mt2','mt3']
				});
				//gsat_layer.addTo(map);
				

				// Ain department layer
				const aindpt = L.geoJSON(aindpt,{
					style:function(onEachFeature){
						return{
							color:'#8b0000'		
						}
					}
				}).addTo(map);


				// control layers
				const baseLayers = {"Mapbox": mapbox_layer, "Google Maps": gmap_layer, "Satellite": gsat_layer};
				const overlays = {"Departement ain":aindpt};
				L.control.layers(baseLayers, overlays, {position : 'topleft'}).addTo(map);
				L.control.zoom({position: 'topleft'}).addTo(map);


				// cluster layer of location markers
				const cluster = L.markerClusterGroup();
				
				
				// Pop-up on location marker
				function onEachFeature(feature, layer) {
					// format date
					const fr_date = new Date(feature.properties.date_obs);
					const month = ["Janv","Fév","Mars","Avr","Mai","Juin","Juil","Août","Sept","Oct","Nov","Déc"];
					const fr_date = fr_date.getDate() + " " + month[fr_date.getMonth()] + " " + fr_date.getFullYear();
					
					layer.bindPopup("<p><b>"+feature.properties.nom_latin+"</b><br>Observateur : "+feature.properties.observateur+"<br>Date d'observation : "+fr_date+"<br>Nombre d'adultes observés : "+feature.properties.nombre+"</p>");
				}
				
				// button to return to initial view
				L.easyButton("fa-rotate-left fa-lg",
				function(reset, map){map.setView([ain.lat, ain.lng], zoomLevel);},
				"Vue initiale \r(zoom et centrage)").addTo(map);

			</script>
			
			<!-- references with some bootstrap-->
			<div class="float-right" style="float:right;color:aliceblue;background:rgb(36, 7, 7, 0.7);z-index: 10;position:relative;margin-top:-60px;font:Arial;font-size: 14px">Réalisation : Mathias Kalfayan & Haytham Chaari</br>Sources : <a href="http://www.sympetrum.fr/">Groupe Sympétrum</a></div><br>

			
		</div>
	</body>
</html>