<?php

/**
 * Класс-парсер для .AIR файлов от GDS Amadeus
 *
 * TODO сборка билета из нескольких файлов
 * @author ikoolik
 *
 */
class GDS_Parser_AIR {
	
	private $aDefaults = array();
	
	public function __construct($aDefaults) {
		$this->aDefaults = $aDefaults; //забираем значения по-умолчанию
	}
	
	
	/**
	 * Метод возвращает массив данных о билете, сформированный из файла
	 *
	 * @param string $sFile путь у файлу
	 * @return array массив данных о билете, полученный из файла
	 */
	public function getTicketFromFile($sFile) {
		$aLines =  file($sFile);
		
		//Разрываем содержимое файла на секции
		$aRawSections = array();
		foreach($aLines as $sLine) {
			if(preg_match("#^([A-Z]+)#",$sLine, $aMatch)) {
				$sSectionKey = $aMatch[1];
			}
			$aRawSections[$sSectionKey] .= $sLine;
		}
		
		//Обрабатываем секции
		$aSections = array();
		foreach($aRawSections as $sSectionCode => $sSectionText) {
			$aSections[$sSectionCode] = $this->parseSection($sSectionText, $sSectionCode);
		}
		
		//Начинаем сборку Biletoid_Variant
		$iFlightNum = 0;
		foreach($aSections['H'] as $ikey => $aRawFlightPart) { //в секии H данные о перелетах
			$oDepApt = Biletoid_References::getAirportByCode($aRawFlightPart['DepAptCode']);
			$oArrApt = Biletoid_References::getAirportByCode($aRawFlightPart['ArrAptCode']);
			$oAirline = Biletoid_References::getAirlineByCode($aRawFlightPart['AirlineCode']);
			$sDepDate = date("Y-m-d",strtotime($aRawFlightPart['DepDate']));
			$sDepTime = date("H:i:s",strtotime($aRawFlightPart['DepTime']));
			$sArrDate = date("Y-m-d",strtotime($aRawFlightPart['ArrDate']));
			$sArrTime = date("H:i:s",strtotime($aRawFlightPart['ArrTime']));
			$aFlightPart = array(
					'groupId' => ($iFlightNum+1),
					'platingCarrierId' => $oAirline->id,
					'flight' => array(
							'code' => $aRawFlightPart['FlightNum'],
							'fromCityId' => $oDepApt->city_id,
							'toCityId' => $oArrApt->city_id,
							'fromAirportId' => $oDepApt->id,
							'toAirportId' => $oArrApt->id,
							'airlineCode' => $oAirline->code,
							'dateBegin' => $sDepDate,
							'timeBegin' => $sDepTime,
							'dateEnd' => $sArrDate,
							'timeEnd' => $sArrTime,
							'distance' => (int) $aRawFlightPart['NauticalMiles'],
							'aircraft' => array(
									'name' => $aRawFlightPart['Aircraft'],
									'code' => $aRawFlightPart['Aircraft'],
							),
							'fromTerminal' => array(
									'code' => $aRawFlightPart['DepTerminal']
							),
					)
			);
			$aVariant['variants'][] = $aFlightPart;
			
			//обрабатываем указатель на группы сегментов
			//значение Х в StopoverIndicators указывает на то,
			//что пункт назначения егмента не конечный пункт перелета, но пересадка
			if($aSections['H'][($ikey+1)]['StopoverIndicators'] != 'X') {
				$iFlightNum++;
			};
		}
		$aVariant['priceDetail'] = array(
				array(
						'totalValue' => $aSections['K']['FullFare']
				)
		);
		$oVariant = new Biletoid_Variant($aVariant, array('why do we need this array? T_T'));
		
		//расставляем номера билетов по пассажирам
		foreach($aSections['T'] as $iKey => $sTKT) {
			$aSections['I'][$iKey]['ticket_number'] = $sTKT;
		}
		//собираем возвращаемые данные
		$aTicket = array(
				'pnr' => $aSections['MUC']['pnr'],
				'aPassports' => $aSections['I'],
				'oVariant' => $oVariant,
				'TKT' => implode(";", $aSections['T'])
		);
		return $aTicket;
	}
	
	private function parseSection($sSectionText, $sSectionCode) {
		//формируем название метода, которым будем парсить
		$sRequieredMethod = 'parse'.$sSectionCode.'Section';
		return $this->$sRequieredMethod($sSectionText);
	}
	
	private function parseMUCSection($sSectionText) {
		$aSectionData = array (
				'pnr' => substr($sSectionText, 6,6)
		);
		return $aSectionData;
	}
	
	private function parseASection($sSectionText) {
		$aSectionData = explode(";", substr($sSectionText,2,strlen($sSectionText)));
		list($sAirlineCode, $iAirlineNum) = explode(" ",$aSectionData[1]);
		$aSection = array(
				'airlineName' => $aSectionData[0],
				'airlineCode' => $sAirlineCode,
				'airlineNum' => $iAirlineNum
		);
		return array_map('trim',$aSection);
	}
	
	private function parseHsection($sSectionText) {
		$aLines = explode("\r\n",$sSectionText);
		$aFlights = array();
		foreach($aLines as $sFlightLine) {
			if(preg_match("#^H-000#", $sFlightLine) || strlen($sFlightLine) === 0) {
				continue;
			} else {
				$aRawFlight = explode(";",$sFlightLine);
				$aFlights[(intval(substr($aRawFlight[0], 2, 3)))] = array(
						'SegmentIdentifier' => substr($aRawFlight[0], 2, 3),
						'StopoverIndicators' => substr($aRawFlight[1], 3,1),
						'FlightNum' => substr($aRawFlight[5], 6, 4),
						'Class' => substr($aRawFlight[5], 11, 1),
						'DepDate' => substr($aRawFlight[5], 15,5),
						'DepTime' => substr($aRawFlight[5], 20,4),
						'DepAptCode' => substr($aRawFlight[1], 4,3),
						'ArrDate' => substr($aRawFlight[5], 30,5),
						'ArrTime' => substr($aRawFlight[5], 25,4),
						'ArrAptCode' => substr($aRawFlight[3], 0,3),
						'AirlineCode' => trim(substr($aRawFlight[5], 0,3)),
						'Aircraft' => ''
				);
			}
		}
		return $aFlights;
	}
	
	private function parseTSection($sSectionText) {
		$aLines = explode("\r\n",$sSectionText);
		foreach($aLines as $iKey => $sTicketLine) {
			if(strlen($sTicketLine)) {
				$aLines[$iKey] = substr((preg_replace("#-#","",$sTicketLine)), 2);
			} else {
				unset($aLines[$iKey]);
			}
		}
		return $aLines;
	}
	
	private function parseISection($sSectionText) {
		$aPersonLines = explode("\r\n",$sSectionText);
		$aPersons = array();
		foreach($aPersonLines as $iKey => $sPersonLine) {
			if(strlen($sPersonLine)) {
				$aPersonData = array_map('trim',explode(";",$sPersonLine));
				$aNameParts = explode("/",$aPersonData[1]);
				$sLastName = substr($aNameParts[0],2);
				if(preg_match("#MRS$#", $aNameParts[1])) {
					$sFirstName = substr($aNameParts[1],0,(strlen($aNameParts[1])-4));
					$iSexId = 2;
				} else {
					$iSexId = 1;
					$sFirstName = substr($aNameParts[1],0,(strlen($aNameParts[1])-3));
				}
				$aPerson = array(
						//'id' => intval(substr($aPersonData[1], 0,2)),
						'first_name' => ucwords(strtolower($sFirstName)),
						'last_name' => ucwords(strtolower($sLastName)),
						'sex_id' => $iSexId,
						//дальше - дефолтные значения
						'country_id' => $this->aDefaults['passport']['country_id'],
						'type_id' => $this->aDefaults['passport']['type_id'],
						'number' => $this->aDefaults['passport']['number'],
						'birthday' => $this->aDefaults['passport']['birthday'],
						'user_id' => $this->aDefaults['user_id']
				);
				$aPersons[] = $aPerson;
			}
		}
		return $aPersons;
	}
	private function parseKSection($sSectionText) {
		$aSectionParts = explode(";",$sSectionText);
		return array(
				'FullFare' => intval(substr($aSectionParts[12], 3))
		);
	}
	public function __call($sMethod, $aParams) {
		return false;
	}
}

?>