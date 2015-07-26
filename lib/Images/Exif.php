<?php
	/**
	 * EXIF data handler
	 * @since Version 3.10.0
	 * @package Railpage
	 * @author Michael Greenhill
	 */
	 
	namespace Railpage\Images;
	
	use Railpage\AppCore;
	use Railpage\Url;
	use Railpage\Debug;
	use Exception;
	use InvalidArgumentException;
	use DateTime;
	
	class Exif extends AppCore {
		
		/**
		 * EXIF data format version
		 * @since Version 3.10.0
		 * @const float EXIF_FORMAT_VERSION
		 */
		
		const EXIF_FORMAT_VERSION = 1.2202;
		
		/**
		 * Get EXIF data from an image
		 * @since Version 3.10.0
		 * @param \Railpage\Images\Image $Image
		 * @param boolean $force
		 * @return array
		 */
		
		public function getImageExif(Image $Image, $force = false) {
			
			if (!$force && isset($Image->meta['exif']) && $Image->meta['exif_format_version'] >= self::EXIF_FORMAT_VERSION) {
				$Image->meta['exif']['camera_make'] = self::normaliseCameraMake($Image->meta['exif']['camera_make']); 
				$Image->meta['exif']['camera_model'] = self::normaliseCameraModel($Image->meta['exif']['camera_model']); 
				
				return $Image->meta['exif'];
			}
			
			/**
			 * Fetch EXIF from the image provider API
			 */
			
			$Provider = $Image->getProvider(); 
			
			$exif = $Provider->getExif($Image->photo_id);
			
			$exif_formatted = $this->getExifIDs($exif);
			$Image->meta['exif'] = $exif_formatted;
			$Image->meta['exif_format_version'] = self::EXIF_FORMAT_VERSION;
			$Image->commit(); 
			
			/**
			 * Insert into our database
			 */
			
			$query = "INSERT INTO image_exif ( 
						  image_id, camera_id, lens_id, lens_sn_id,
						  aperture, exposure_id, exposure_program_id, 
						  focal_length, iso, white_balance_id
					  ) VALUES (
					      %d, %d, %d, %d, 
						  %s, %d, %d, 
						  %s, %s, %s
					  ) ON DUPLICATE KEY UPDATE
					  	  camera_id = VALUES(camera_id), lens_id = VALUES(lens_id),
						  lens_sn_id = VALUES(lens_sn_id), aperture = VALUES(aperture),
						  exposure_id = VALUES(exposure_id), exposure_program_id = VALUES(exposure_program_id),
						  focal_length = VALUES(focal_length), iso = VALUES(iso), 
						  white_balance_id = VALUES(white_balance_id)";
			
			$query = sprintf($query, 
				$this->db->quote($Image->id), 
				$this->db->quote($exif_formatted['camera_id']),
				$this->db->quote($exif_formatted['lens_id']), 
				$this->db->quote($exif_formatted['lens_sn_id']),
				$this->db->quote($exif_formatted['aperture']), 
				$this->db->quote($exif_formatted['exposure_id']),
				$this->db->quote($exif_formatted['exposure_program_id']), 
				$this->db->quote($exif_formatted['focal_length']),
				$this->db->quote($exif_formatted['iso_speed']), 
				$this->db->quote($exif_formatted['white_balance_id'])
			);
			
			#printArray($query);
			#printArray($exif_formatted);die;
			
			$this->db->query($query); 
			
			return $exif_formatted;
			
		}
		
		/**
		 * Get IDs for EXIF values
		 * @since Version 3.10.0
		 * @param array $exif
		 * @return array
		 */
		
		private function getExifIDs($exif) {
			
			$required = [ 
				"camera_make", 
				"camera_model",
				"lens_model", 
				"lens_serial_number",
				"exposure", 
				"exposure_program",
				"white_balance",
				"software"
			];
			
			foreach ($required as $key) {
				if (!isset($exif[$key])) {
					$exif[$key] = "Unknown";
				}
			}
			
			$exif['camera_make'] = self::normaliseCameraMake($exif['camera_make']); 
			$exif['camera_model'] = self::normaliseCameraModel($exif['camera_model']); 
			
			$query = "SELECT 
				(SELECT id FROM image_camera WHERE make = ? AND model = ?) AS camera_id,
				(SELECT id FROM image_lens WHERE model = ?) AS lens_id,
				(SELECT id FROM image_lens_sn WHERE sn = ?) AS lens_sn_id,
				(SELECT id FROM image_exposure WHERE exposure = ?) AS exposure_id,
				(SELECT id FROM image_exposure_program WHERE program = ?) AS exposure_program_id,
				(SELECT id FROM image_whitebalance WHERE whitebalance = ?) AS white_balance_id,
				(SELECT id FROM image_software WHERE name = ?) AS software_id";
			
			$params = [
				$exif['camera_make'],
				$exif['camera_model'],
				$exif['lens_model'],
				$exif['lens_serial_number'],
				$exif['exposure'],
				$exif['exposure_program'],
				$exif['white_balance'],
				$exif['software'],
			];
			
			$row = $this->db->fetchRow($query, $params); 
			
			foreach ($row as $column => $val) {
				if (!filter_var($val, FILTER_VALIDATE_INT)) {
					$row[$column] = $this->createNewExif($column, $exif); 
				}
			}
			
			$exif = array_merge($exif, $row);
			ksort($exif); 
			
			return $exif;
			
		}
		
		/**
		 * Normalise the camera make
		 * @since Version 3.10.0
		 * @param string $make
		 * @return string
		 */
		
		private static function normaliseCameraMake($make) {
			
			$find = [ 
				"NIKON CORPORATION",
				"EASTMAN KODAK COMPANY",
				"DIGITAL CAMERA",
				"OLYMPUS CORPORATION",
				"FUJIFILM",
				"FUJI PHOTO FILM CO., LTD."
			];
			
			$replace = [
				"Nikon",
				"Kodak",
				"",
				"Olympus",
				"Fujifilm",
				"Fujifilm"
			];
			
			$make = preg_replace("/([0-9]+)(D DIGITAL)/", "$1D", $make);
			$make = str_replace($find, $replace, $make); 
			
			return trim($make);
			
		}
		
		/**
		 * Normalise the camera model
		 * @since Version 3.10.0
		 * @param string $model
		 * @return string
		 */
		
		private static function normaliseCameraModel($model) {
			
			$model = preg_replace("/(CANON|Canon|NIKON|NIKON CORPORATION|KODAK|KODAK EASYSHARE) /", "", $model);
			$model = preg_replace("/([0-9]+)(D DIGITAL)/", "$1D", $model);
			$model = preg_replace("/(EASYSHARE )([A-Z0-9]+)( ZOOM DIGITAL)/", "Easyshare $2 Zoom", $model);
			$model = preg_replace("/([A-Z0-9]+)( ZOOM DIGITAL)/", "$1 Zoom", $model);
			$model = str_replace("CAMERA", "", $model);
			$model = str_replace("EOS DIGITAL REBEL XT", "EOS 350D", $model);
			$model = str_replace("EOS Kiss Digital N", "EOS 350D", $model);
			$model = str_replace("EOS Rebel T1i", "EOS 500D", $model);
			$model = str_replace("EOS Kiss X3", "EOS 500D", $model);
			
			return trim($model);
			
		}
		
		/**
		 * Create a new EXIF value in our database
		 * @since Version 3.10.0
		 * @param string $type
		 * @param array $exif
		 * @return int
		 */
		
		private function createNewExif($type, $exif) {
			
			switch ($type) {
				
				case "camera_id" :
					$data = [ "make" => $exif['camera_make'], "model" => $exif['camera_model'] ];
					$table = "image_camera";
					break;
				
				case "lens_id" : 
					$data = [ "model" => $exif['lens_model'] ];
					$table = "image_lens";
					break;
				
				case "lens_sn_id" : 
					$data = [ "sn" => $exif['lens_serial_number'] ];
					$table = "image_lens_sn";
					break;
				
				case "exposure_id" : 
					$data = [ "exposure" => $exif['exposure'] ];
					$table = "image_exposure";
					break;
				
				case "exposure_program_id" : 
					$data = [ "program" => $exif['exposure_program'] ];
					$table = "image_exposure_program";
					break;
				
				case "white_balance_id" : 
					$data = [ "whitebalance" => $exif['white_balance'] ];
					$table = "image_whitebalance";
					break;
				
				case "software_id" :
					$data = [ "name" => $exif['software'] ];
					$table = "image_software";
					break;
			
			}
			
			$this->db->insert($table, $data); 
			
			$id = $this->db->lastInsertId(); 
			
			return $id;
			
		}
		
		/**
		 * Format EXIF data
		 * @since Version 3.10.0
		 * @param array $exif
		 * @return array
		 */
		
		public function formatExif($exif) {
			
			$format = array();
			
			// Aperture
			if (isset($exif['aperture'])) {
				$format[] = array(
					"icon" => "<i class='f' style='width:16px;height:16px;display:inline-block;background: url(https://cloud.githubusercontent.com/assets/11262717/6442104/90d16e0e-c0ed-11e4-8b58-9f25df36f775.png) center;background-size:cover;'></i>",
					"label" => "Aperture",
					"value" => sprintf("<em>ƒ</em>/%s", $exif['aperture'])
				);
			}
			
			if (isset($exif['exposure'])) {
				$format[] = array(
					"icon" => "", 
					"label" => "Exposure", 
					"value" => $exif['exposure']
				);
			}
			
			$format[] = array(
				"icon" => "", 
				"label" => "Camera", 
				"value" => sprintf("%s %s", $exif['camera_make'], $exif['camera_model'])
			);
			
			$format[] = array(
				"icon" => "", 
				"label" => "Lens", 
				"value" => $exif['lens_model']
			);
			
			$format[] = array(
				"icon" => "", 
				"label" => "ISO", 
				"value" => $exif['iso_speed']
			);
			
			if (isset($exif['focal_length'])) {
				$format[] = array(
					"icon" => "", 
					"label" => "Focal length", 
					"value" => sprintf("%smm", $exif['focal_length'])
				);
			}
			
			foreach ($format as $key => $val) {
				if ($val['value'] == "Unknown" || $val['value'] == "Unknown Unknown" || is_null($val['value'])) {
					unset($format[$key]);
				}
			}
			
			
			return $format;
			
		}
		
	}