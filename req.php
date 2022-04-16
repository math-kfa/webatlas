<?php
	// query to return all dragonfly locations based on selection in the dropdown list
	
	//if selection is not empty	
	if($_POST["select_specie"]){
		$selected_sp = $_POST["select_specie"];
		
		$host = "____________";
		$port = "5432";
		$dbname = "atlas-odonate-ain_bd";
		$dbuser = "atlas-odonate-ain";
		$userpass = "_______________";
		
		$conn = pg_connect("host=$host port=$port dbname=$dbname user=$dbuser password=$userpass");
		
		// if database connection is true
		if ($conn){
			// result from SQL query
			$result = pg_query($conn, "SELECT * FROM odonate_ain_table WHERE nom_latin LIKE '$selected_sp' AND latitude IS NOT null AND longitude IS NOT null;");
			
			// if result not empty
			if (!empty($result)) {
				
				// creation of geoJson with all locations			
				$geodata = '{"type": "FeatureCollection", "features": [';
				
				$i = 1;
				// for each row in result, append one line in geoJson 
				foreach(pg_fetch_all($result) as $ligne) {
					$geodata .= '{"geometry": {"type": "Point", "coordinates": ["'.$ligne['longitude'].'", "'.$ligne['latitude'].'"]},"id": "'.$i.'", "type": "Feature", "properties": {"id_specie": "'.$ligne['n_bd_dep'].'", "nom_latin": "'.$ligne['nom_latin'].'", "date_obs": "'.$ligne['date_obs'].'", "observateur": "'.$ligne['observateur'].'", "altitude": "'.$ligne['altitude'].'", "nombre": "'.$ligne['nombre'].'"}},';
					$i++;
				}
				$geodata = substr($geodata,0,strlen($geodata)-1);
				$geodata .= ']}';
				echo $geodata;}
				
			else {echo "Aucune correspondance dans la base de données";}
			
		} else{
			echo "Problème de connexion à la base de données lors du lancement de la requête - PostgreSQL";
		}
		
	} else{
		echo "Erreur lors de l'exécution de la requête pour l'espèce sélectionnée : ".$_POST["select_specie"]."\nLa sélection a retourné une valeur ne pouvant être traitée";
	}
	
?>